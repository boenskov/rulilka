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
    echo "...DROP</br>";
    $q="DROP TABLE IF EXISTS $tbl";
    echo "$q<br>";
    $local->exec($q);
    echo "...CREATE</br>";
    echo "$schema<br>";
    $local->exec($schema);
    if(in_array($tbl,$skip_tables)){
        echo "...Таблица-кеш...оставим пустой<br>";
    } else {
        echo "...COPY</br>";

        $count=$etalon->query("SELECT count(*) FROM `$tbl`")->fetchColumn();
        echo "Строк: $count<br>";
        if($count) {
            $q="INSERT INTO $tbl (`".join("`,`",$fields)."`) VALUES ";
            $qpart="(%".join("%,%",$fields)."%)";
            echo $q."<br>";
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


    echo "<hr>";
}
echo "Итоговое выполнения ".(time()-$script_start)."<br>";
echo "<script>alert('Готово!');</script>";

