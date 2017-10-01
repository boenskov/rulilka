<?php
$script_start=time();
$update_step=100;

set_time_limit(100);

require_once "_utils.php";

echo "<h1>Состояние конфигурации</h1>";

# текущий конфиг
$conf=findDBConfig();
echo "<pre><h2>Локальное подключение:</h2>";
var_export($conf);
echo "\n";

if(empty($conf)){
    echo "Локальный конфиг не найден!\n";
} else {
# проверим соединение
    $local = testDB($conf["dns"], $conf["u"], $conf["p"]);
    $local->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    echo "\nСостояние: " . (empty($local) ? "<span style='color:red'>Недоступно</span>" : "<span style='color:green'>Online</span>") . "\n";
}
echo "</pre>";

$conf=require "config.sys.php";
$etalon_config=$conf["etalon"];
echo "<pre><h2>Эталон:</h2>";
var_export($etalon_config);
echo "\n";

if(empty($etalon_config)){
    echo "Конфиг эталона не найден!\n";
} else {
# проверим соединение
    $etalon = testDB($etalon_config["db"]["dns"], $etalon_config["db"]["u"], $etalon_config["db"]["p"]);
    $etalon->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    echo "\nСостояние: " . (empty($etalon) ? "<span style='color:red'>Недоступно</span>" : "<span style='color:green'>Online</span>") . "\n";
}
echo "</pre>";

if(empty($local) || empty($etalon)) die("ошибка подключения к БД");

echo "<h1>Таблицы для пересоздания</h1>";
$tables_q=$etalon->query("SHOW TABLES")->fetchAll(PDO::FETCH_ASSOC);
$tables=[];
$skip_tables=[];
foreach($tables_q as $i){
    $tbl=reset($i);
    $tables[]=$tbl;
    # проверим на кеши
    if(!empty($etalon_config["cache_tables"])) {
        foreach($etalon_config["cache_tables"] as $t){
            if(preg_match("~{$t}~",$tbl)) $skip_tables[]=$tbl;
        }
    }
}
echo "<div><b>Найдены таблицы (".count($tables)."): </b>".join(", ",$tables)."</div>";
echo "<div><b>Таблицы кешей (не переносить данные) (".count($skip_tables)."): </b>".join(", ",$skip_tables)."</div>";
#var_dump($tables);
echo "<hr>";
$tbl_cnt=0;
foreach($tables as $tbl){
    $tbl_cnt++;
    echo "Таблица <b>$tbl</b> ($tbl_cnt из ".count($tables).")<br>";
    $schema=$etalon->query("SHOW CREATE TABLE $tbl")->fetchColumn(1);
    $fields=$etalon->query("SHOW COLUMNS FROM $tbl")->fetchAll(PDO::FETCH_ASSOC);
    $fields=array_column($fields,"Field");
    echo "Поля: ".join(", ",$fields)."<br>";
    #var_dump($fields);
    #var_dump($schema);

    # посчитаем сколько записей есть на эталоне
    $count=$etalon->query("SELECT count(*) FROM `$tbl`")->fetchColumn();
    echo "Строк: $count<br>";
    # извлечем информацию об индексах
    $keys=[];
    $ai=[]; # скорректированные autoincrement поля
    if($count>1) {
        echo "<b>...Старая схема</b></br>\n";
        echo "$schema<br>\n";

        # разобьем информацию о схеме на строки
        $schema=explode("\n",$schema);
        # извлечем перввую и последню строку
        $schema_start=array_shift($schema);
        $schema_end=array_pop($schema);
        # остальные строки содержат описания строк.... пройдемся по ним
        foreach($schema as $line){
            if(preg_match('#(PRIMARY KEY|UNIQUE KEY `.*`|KEY `.*`) \(`.*`\)#u',$line,$m)) {

            }
        }


//        # если строк много, то сначала удалим ключи, а потом их восстановим
//        if(preg_match_all('#(PRIMARY KEY|UNIQUE KEY `.*`|KEY `.*`) \(`.*`\)#u',$schema,$m))
//        {
//            #echo "<br><b>INDEX:</b>";
//            #var_dump($m[0]);
//            $keys=$m[0];
//            #echo "</b>";
//
//            # вычистим эти ключи из схемы
//            foreach($keys as $k){
//                $schema=str_replace(",\n  $k","",$schema);
//                #$schema=preg_replace('#,\s$k#umis',"",$schema);
//            }
//
//            # найдем и уберем поле autoincrement
//            $schema=explode("\n",$schema);
//            foreach($schema as &$v){
//                if(strpos($v," AUTO_INCREMENT ")!==false) {
//                    $ai[]=$v;
//                    $v=str_replace(" AUTO_INCREMENT "," ",$v);
//                }
//            }
//            $schema=join("\n",$schema);
//            echo "<b>Убрали ключи из схемы</b><br>";
//        }
    }


    echo "...DROP</br>\n";
    $q="DROP TABLE IF EXISTS $tbl";
    echo "$q<br>";
    $local->exec($q);
    echo "...CREATE</br>\n";
    echo "$schema<br>\n";
    $local->exec($schema);

    if(in_array($tbl,$skip_tables)){
        echo "...Таблица-кеш...оставим пустой<br>\n";
    } else {
        echo "...COPY</br>\n";

        # подсчет строк
        if($count) {
            /** @var string $q шаблон запроса на вставку*/
            $q="INSERT INTO $tbl (`".join("`,`",$fields)."`) VALUES ";
            /** @var string $qpart шаблон значений полей для запроса на вставку*/
            $qpart="(%".join("%,%",$fields)."%)";
            echo $q."<br>\n";
            //$prepare=$local->prepare($q);

            $timer = time();
            $timeout=3;
            $local->exec("LOCK TABLE $tbl WRITE");
            for ($start = 0; $start < $count; $start+=$update_step) {
                $res=$etalon->query("SELECT * FROM $tbl LIMIT {$start},{$update_step}")->fetchAll(PDO::FETCH_ASSOC);
                if(!empty($res)){
                    $qparts=[];
                    foreach($res as $r){
                        $pp=[];
                        #$qp=$q;
                        foreach($r as $k=>$v) #$pp[":{$k}"]=$v;
                        {
                            if(is_null($v)) $v="NULL";
                            elseif(is_string($v)) $v=$local->quote($v);
                            $pp["%{$k}%"]=$v;
                        }
                        #$qp=str_replace(array_keys($pp),array_values($pp),$q);
                        $qparts[]=str_replace(array_keys($pp),array_values($pp),$qpart);
                        #echo "$qp<br>";
                        #$local->exec($qp);
                        #var_dump($pp);echo "<br>";
                        #$prepare->execute($pp);
                        #$local->query($q,$pp);
                    }
                    $qp=$q.join(", ",$qparts);
                    #echo $qp."<hr>";
                    $local->exec($qp);
                }
                if(($timer+$timeout)<time()){
                    $timer=time();
                    echo "... обработано $start из $count (".intval($start/$count*100)."%)... время ".(time()-$script_start)."sec...<br>\n";
                    flush();
                }
            }
            $local->exec("UNLOCK TABLES");
            echo "Готово!<br>";
        }
    }
    echo "Текущее время выполнения ".(time()-$script_start)."<br>";
    if(!empty($keys)) {
        echo "<b>Восстановим ключи таблиц</b><br>";
        $q="ALTER TABLE `$tbl` ADD ".join(", ADD ",$keys);
        echo "$q<br>";
        $local->exec($q);
        echo "Текущее время выполнения " . (time() - $script_start) . "<br>";
    }
    if(!empty($ai)){
        echo "<b>Восстановим AUTOINCREMENT поля</b><br>";
        $x=reset($ai);
        if(substr($x,-1,1)==",") $x=substr($x,0,-1);
        $q="ALTER TABLE `$tbl` MODIFY ".$x;
        echo "$q<br>";
        $local->exec($q);
        echo "Текущее время выполнения " . (time() - $script_start) . "<br>";
    }


    echo "<hr>";
}
echo "Итоговое выполнения ".(time()-$script_start)."<br>";
echo "<script>alert('Готово!');</script>";

