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

function importDBFromFile($file,$local){
    echo "<h1>Импорт из бекапа $file</h1>";

    $size = filesize($file);
    echo "Размер файла: $size<br>";
    $timer = $script_start = time();
    $timeout = 5;

    $h = fopen($file, "r");

    $cur_query = [];

    /** @var  $cnt int количество обработанных строк */
    $cnt = 0;
    /** @var  $q_cnt int количество выполненных запросов */
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

}