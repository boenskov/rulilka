<?php

$title="GIT - управление репозиторием";
require_once "_head.php";
require_once "_utils.php";

echo "<nav>
<a style='float: right;' href='update_db_etalon.php'>[ Обновление БД из эталона ]</a> 
</nav>";

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
    echo "\nСостояние: " . (empty($local) ? "<span style='color:red'>Недоступно</span>" : "<span style='color:green'>Online</span>") . "\n";
}

echo "</pre>";

$conf=loadConfig(true);
$etalon_conf = $conf["etalon"];
echo "<pre><h2>Эталон:</h2>";
var_export($etalon_conf);
echo "\n";

if (empty($etalon_conf)) {
    echo "Конфиг эталона не найден!\n";
} else {
# проверим соединение
    $etalon = testDB($etalon_conf["db"]["dns"], $etalon_conf["db"]["u"], $etalon_conf["db"]["p"]);
    echo "\nСостояние: " . (empty($etalon) ? "<span style='color:red'>Недоступно</span>" : "<span style='color:green'>Online</span>") . "\n";
}

echo "<hr>";

if(!empty($local) && !empty($etalon))
    echo "<p><a href='update_db_etalon.php'>[ Обновление БД из эталона ]</a></p>";
else
    echo "<p>Обновление из эталона недоступно</p>";

if(!empty($local))
    echo "<p><a href='update_db_sql.php'>[ Обновление БД из бэкапа ]</a></p>";
else
    echo "<p>Обновление из бэкапа недоступно</p>";

echo "</pre>";

require_once "_bottom.php";