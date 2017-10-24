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
$l_conf=loadConfig();
//var_dump($l_conf);
if($l_conf!==false && !empty($l_conf["backup_dirs"]))
    $dirs=$l_conf["backup_dirs"];
$dirs[]=__DIR__."/..";

$dir=reset($dirs);
echo "<p>Каталог бэкапа: <b>$dir</b></p>";

if(!isset($_GET["go"])){
    echo "<p><a href='?go'>[ Создать ]</a></p>";
    exit();
} else {

    $file=$dir."/".date("Y-m-d-H-i-s").".sql";
    echo "<p>Файл бэкапа: <b>$file</b></p>";
#var_dump($conf);
    $db_conf=explode(";",$conf["dns"]);
    $db=[];
    foreach($db_conf as $v){
        $vv=explode("=",$v);
        if(count($vv)==2) $db[$vv[0]]=$vv[1];
    }
    if(empty($db)) die("Конфиг не найден или некорректен");
#    echo "<hr>";
#    var_dump($db);

    $cmd="mysqldump -u {$conf["u"]} -p{$conf["p"]} --host={$db["mysql:host"]} --port={$db["port"]} {$db["dbname"]}";
    $cmd.=" > $file";
echo $cmd;

    $cmd="umask 000 && {$cmd}";
    system($cmd." 2>&1",$result);

    if($result) echo "\n<b>ОШИБКА</b>";
    else echo "\n<b>OK</b>";

}

