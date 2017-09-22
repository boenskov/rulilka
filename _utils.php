<?php

function findD7Config(){
    $file="../sites/default/settings.php";
    if(file_exists($file)){
        include $file;
        if(!empty($databases) && !empty($databases["default"]["default"])) {
            $conf=$databases["default"]["default"];
            $conf_pdo=[
                "dns"=>"{$conf["driver"]}:host={$conf["host"]};dbname={$conf["database"]};port=".(empty($conf["port"])?"3306":$conf["port"]).";charset=UTF8",
                "user"=>$conf["username"],
                "pas"=>$conf["password"]
            ];
            return $conf_pdo;
        }
    }
    return false;
}

function testDB($dns,$u,$p){
    try {
        $db=new PDO($dns,$u,$p);
        return $db;
    } catch (PDOException $e) {
        echo 'Подключение не удалось: ' . $e->getMessage();
        return false;
    }
}

