<?php
$script_start=time();
$update_step=100;

set_time_limit(0);

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

if(empty($local)) die("ошибка подключения к БД");

$dirs=[];
$conf=loadConfig();
if($conf!==false && !empty($conf["backup_dirs"]))
    $dirs=$conf["backup_dirs"];
$dirs[]=__DIR__."/..";

# ищем файлы
$files=[];
foreach($dirs as $d){
    $items=glob($d."/*.sql");
    if(!empty($items)) foreach($items as $item){
        if($item!="." && $item!="..")
            $files[crc32($item)]=realpath($item);
    }
}
#var_dump($files);

if(empty($_GET["file"])){
    # поиск файлов с бекапами
    echo "<h2>SQL файлы</h2>";
    echo "Поиск произведен по каталогам ".join(", ",$dirs)."<br><br>";
    foreach($files as $k=>$v){
        echo "<a href='?file=$k'>$v</a><br>";
    }
    echo "<br>Укажите файл из которого необходимо развернуть бэкап.<br>
    Файл бэкапа должен представлять из себя стандартный бекап mysqldump или phpMyAdmin и должен содержать инструкции на создание таблиц и их заполнение<br>  
    Восстановление базы из бекапа может занять длительное время.<br>";
} else {

    $file = $files[$_GET["file"]];
    if (empty($file)) die ("Некорректный файл");
    if (!file_exists($file)) die ("Файл $file не существует");

    importDBFromFile($file,$local);
/*
 *     echo "<h1>Импорт из бекапа $file</h1>";

    $size = filesize($file);
    echo "Размер файла: $size<br>";
    $timer = $script_start = time();
    $timeout = 5;

    $h = fopen($file, "r");

    $cur_query = [];

    /* * @var  $cnt int количество обработанных строк * /
    $cnt = 0;
    /** @var  $q_cnt int количество выполненных запросов * /
    $q_cnt = 0;
    while (($l = fgets($h, 100000000)) !== false) {
#    echo "line: $l <br>";
        # если не пусто, значит идет процесс сбора команды
        if (!empty($cur_query)) {
            #echo "add<br>";
            $cur_query[] = $l;
        } else {
            # ищем момент начала инструкций
            if (preg_match("#^(create|set|insert|alter) #i", $l)) {
                $cur_query[] = $l;
                #echo "start<br>";
            }
        }
        # проверим, что эта инструкция не является последней...если последняя, то выполним её
        if (preg_match('#;\s?$#', $l) && !empty($cur_query)) {
            $q = join("", $cur_query);
            if (preg_match("#^CREATE TABLE `(.*)`#u", $q, $m)) {
                # если команда на создание таблицы, то сначала её надо дропнуть, что бы не было ошибок
                $qq = "DROP TABLE IF EXISTS `" . $m[1] . "`";
                $local->exec($qq);
                #echo "<div style='border: 1px dashed red;'>".$qq."</div>";
            }
            #echo "<div style='border: 1px dashed red;'>".$q."</div>";
            $local->exec($q);
            $q_cnt++;
            #$local
            $cur_query = [];
#        flush();
        }

        if (($timer + $timeout) < time()) {
            $timer = time();
            echo "... обработано $cnt строк, $q_cnt запросов выполнено,  позиция " . ftell($h) . " из $size (" . intval(ftell($h) / $size * 100) . "%)... время " . (time() - $script_start) . "sec...<br>\n";
            flush();
        }

        $cnt++;
    }

    */
    echo "Итоговое время выполнения " . (time() - $script_start) . "<br>";
    echo "<script>alert('Готово!');</script>";
}