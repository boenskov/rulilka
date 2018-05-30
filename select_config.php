<?php

//echo "<nav>
//<a style='float: right;' href='update_db_etalon.php'>[ Обновление БД из эталона ]</a>
//</nav>";

$config_basedir=__DIR__."/../sites/default";

$files=glob($config_basedir."/config_*.php");
$rulilka_config_file=$config_basedir."/rulilka.conf.php";

$rulilka_conf=[];
if(file_exists($rulilka_config_file))
    $rulilka_conf=require $rulilka_config_file;

$current_branch=trim(str_replace("* ","",exec("cd .. && git branch | grep '*'")));



$need_save=false;
if(!empty($_POST["save"])){
    $rulilka_conf["active"]=$_POST["current"];
    $need_save=true;
}
if(!empty($_POST["link"])){
    reset($_POST["link"]);
    $c=key($_POST["link"]);
    # убираем привязки к другим конфигам
    if(!empty($rulilka_conf["link"])){
        foreach ($rulilka_conf["link"] as &$v){
            if(is_array($v))
                $v=array_filter($v,function($v) use($current_branch){return $v!=$current_branch;});
        }
    }

    # делаем новую привязку
    $rulilka_conf["link"][$c][]=$current_branch;
    array_unique($rulilka_conf["link"][$c]);
    $need_save=true;
}
if(!empty($_POST["unlink"])){
    reset($_POST["unlink"]);
    $c=key($_POST["unlink"]);
    $rulilka_conf["link"][$c]=array_filter($rulilka_conf["link"][$c],function($v) use($current_branch){return $v!=$current_branch;});
    $need_save=true;
}

if($need_save){
    # надо сохранить конфиг
    file_put_contents($rulilka_config_file,"<?php\nreturn ".var_export($rulilka_conf,true).";\n");
    header("Location: ".basename(__FILE__));
    exit();
}


$title="GIT - управление репозиторием";
require_once "_head.php";
require_once "_utils.php";

echo "<h1>Доступные конфигурации</h1>";


if(empty($files)){

    ?>Альтернативные конифгурации не найдены. Необходимо добавить файлы 'config_*.php' содержащие настройки конфигураций.<?

} else {


    /** @var  string $current текущий конфиг */
    $current = $rulilka_conf["active"] ?? "default";
    if (!empty($rulilka_conf["link"])) {
        foreach ($rulilka_conf["link"] as $k => $v) {
            if(is_array($v) && in_array($current_branch,$v))
                $current=$k;
        }
    }


    $branches="";
    $current_branch_is_linked=false;
    ?>
    <p>Текущая ветка: <b><?
        echo $current_branch."</b>";
        if(!empty($rulilka_conf["link"])) {
            foreach ($rulilka_conf["link"] as $k => $v) {
                if (is_array($v) && in_array($current_branch, $v)) {
                    echo " (привязана к конфигу $k)";
                    $current_branch_is_linked=true;
                }
            }
        }
    ?></p>
    <p>Текущий конфиг: <b><?
            echo $current."</b>";
            if(!empty($rulilka_conf["link"][$current]))
                echo "(привязки: ".join(", ",$rulilka_conf["link"][$current]).")";
            ?></p>
    <p>Текущие настройки БД: <?php
        require $config_basedir."/settings.php";
        echo "db=".($databases["default"]["default"]["database"]??"??");
        echo ", user=".($databases["default"]["default"]["username"]??"??");
        ?></p>
    <form action="?" method="post">
    <table border="1">
    <tr><th>Конфиг</th><th>Активный</th><th>Привязка к ветке</th><th>Действие</th></tr>
    <tr>
        <td>(default)</td>
        <td>
            <? if(!$current_branch_is_linked) {
                ?><input type="radio" name="current"
                         value="default" <?= $current == "default" ? "checked" : ""; ?>><?php
            }
        ?>
        </td>
        <td><?=$branches;?></td><td></td>
    </tr>
    <?php

    foreach($files as $file){
        $conf_name=basename($file,".php");
        $branches=!empty($rulilka_conf["link"][$conf_name])?join(", ",$rulilka_conf["link"][$conf_name]):"";
        $linked=!empty($rulilka_conf["link"][$conf_name]) && in_array($current_branch,$rulilka_conf["link"][$conf_name]);
        ?><tr>
        <td><?=$conf_name;?></td>
        <td>
            <?php
            if(!$current_branch_is_linked) {
                ?><input type="radio" name="current" value="<?= $conf_name; ?>" <?= $current == $conf_name ? "checked" : ""; ?>><?php
            } else {
                if($current == $conf_name) {
                    ?><input type="radio" checked disabled><?php
                    ;
                }
            }
            ?>
        </td>
        <td><?=$branches;?></td>
        <td>
            <?php
            if(!$linked) {
                ?><input type="submit" value="Привязать <?=$conf_name;?> к <?=$current_branch;?>" name="link[<?=$conf_name;?>]"><?php
            }else {
                ?><input type="submit" value="Отвязать <?=$conf_name;?> от ветки <?=$current_branch;?>" name="unlink[<?=$conf_name;?>]"><?php
            }
                ?>
        </td>
    </tr><?
    }

    ?></table>
        <p>Один конфиг можно привязать к нескольким веткам. Если текущая ветка привязана к конфигу, то будет
            загружен именно этот конфиг (можно сверить по параметрам подключения БД).</p>
        <p>Изменение дефолтного конфига возможно только при отвязывании текущей ветки.</p>
        <?php
        if(!$current_branch_is_linked) {
            ?>
            <p>Нажав сохранить вы укажите новую активную дефолтную конфигурацию.</p>
            <input type="submit" value="Сохранить" name="save">
            <?php
        } else {
            ?><p>Текущая ветка привязана к конфигу</p><?php
        }
            ?>
</form>
<?
}

?>
    <hr>
    <small>
        <p>Как использовать?</p>
        <p>Для возможности выбора необходимой конфигурации в рулилке надо выполнить следующие условия
        <ol>
            <li>добавить в начало settings.php строку "if(require "rulilka_config_manager.php") return;"
                которая позволит загрузить выбранный конфиг. (После этих строк должен идти дефолтный конфиг)</li>
            <li>добавить в sites/default отдельные конфиги с названиями "config_{*}.php"
                (config_1.php,config_A.php, config_blablabla.php)</li>
            <li>после настроек и выбора в рулилке нужных баз будет создан дополнительный файл rulilka.conf.php
                с настройками (НЕ ДОБАВЛЯТЬ ЭТОТ ФАЙЛ В РЕПКУ!!!)</li>
        </ol>
        </p>
    </small>

<?php
require_once "_bottom.php";