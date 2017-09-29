<?php

$title="GIT - управление репозиторием";
require_once "_head.php";
require_once "_utils.php";

echo "<nav>
<a style='float: right;' href='updatedb.php'>[ Обновление БД из эталона ]</a> 
</nav>";

echo "<h1>Состояние конфигурации</h1>";

# текущий конфиг
$conf=findD7Config();
echo "<pre><h2>Локальное подключение:</h2>";
var_export($conf);
echo "\n";

if(empty($conf)){
    echo "Локальный конфиг не найден!\n";
} else {
# проверим соединение
    $c = testDB($conf["dns"], $conf["u"], $conf["p"]);
    echo "\nСостояние: " . (empty($c) ? "<span style='color:red'>Недоступно</span>" : "<span style='color:green'>Online</span>") . "\n";
}

echo "</pre>";

$conf=require "config.sys.php";
$etalon=$conf["etalon"];
echo "<pre><h2>Эталон:</h2>";
var_export($etalon);
echo "\n";

if(empty($etalon)){
    echo "Конфиг эталона не найден!\n";
} else {
# проверим соединение
    $c = testDB($etalon["db"]["dns"], $etalon["db"]["u"], $etalon["db"]["p"]);
    echo "\nСостояние: " . (empty($c) ? "<span style='color:red'>Недоступно</span>" : "<span style='color:green'>Online</span>") . "\n";
}

echo "</pre>";

require_once "_bottom.php";