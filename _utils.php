<?php

function findDBConfig(){
    $c=findD7Config();
    if(!empty($c)) return $c;
    return false;
}

function findD7Config(){
    $file=__DIR__."/../sites/default/settings.php";
    if(file_exists($file)){
        include $file;
        if(!empty($databases) && !empty($databases["default"]["default"])) {
            $conf=$databases["default"]["default"];
            $conf_pdo=[
                "dns"=>"{$conf["driver"]}:host={$conf["host"]};dbname={$conf["database"]};port=".(empty($conf["port"])?"3306":$conf["port"]).";charset=UTF8",
                "u"=>$conf["username"],
                "p"=>$conf["password"]
            ];
            return $conf_pdo;
        }
    }
    return false;
}

function testDB($dns,$u,$p)
{
    try {
        $db = new PDO($dns, $u, $p);
        return $db;
    } catch (PDOException $e) {
        echo 'Подключение не удалось: ' . $e->getMessage();
        return false;
    }
}

function loadConfig($require=false)
{
    $config_file = __DIR__ . "/config.sys.php";
    if (!file_exists($config_file)) {
        if($require)
            die("Файл с конфигом эталона не найден! Необходимо отредактировать файл config.sys.php.sample и сохранить как config.sys.php\n");
        return false;
    } else {
        return require $config_file;
    }
}
