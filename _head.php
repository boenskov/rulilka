<html>
<head>
    <title>Рулилка<?=!empty($title)?" - $title":"";?></title>
    <meta charset="utf-8">
    <style>
        body {font-family: monospace;font-size: 12px;}
        nav { border-bottom:1px solid silver;padding: 5px;font-size: 12px;}
        nav:first-child { border-bottom:2px dashed black;padding: 10px;font-size: 16px;}
        nav a,nav a:link,nav a:visited {padding: 3px 10px;text-decoration: none;color: blue;}
        nav a:hover {text-shadow: 0 0 3px green;}
        fieldset {margin-top: 10px;}
        fieldset.active {background-color: #dbffdb;border: 2px solid green;}
        table.styled {border: 1px solid black;}
        table.styled th {border: 1px solid black;background-color: #aaa;}
        table.styled td {padding: 0 4px;vertical-align: top;}
        table.styled tr:nth-child(odd) {background-color: #eee;}
        table.styled tr:hover {background-color: #ccc;}
        code {display: block;white-space: pre-wrap;background-color: sandybrown;padding: 10px;}

    </style>
</head>
<body>
<nav>
    <?php
    $nav=[
        "git.php"=>"GIT",
        "config.php"=>"Конфиг",
        "readme.php"=>"readme",
    ];
    foreach($nav as $k=>$v){
        if(basename($_SERVER["SCRIPT_NAME"])==$k) $v="<b>={$v}=</b>"; else $v=" $v ";
        echo "<a href='{$k}'>[{$v}]</a> ";
    }
    ?>
</nav>

<?php
# определение основных функций


/** Создание блока из fieldset
 * @param string|bool $name Заголовок блока или оставить пустым если надо закрыть блок
 */
function _block($name=false,$class=false){
    if(!empty($name)) echo "<fieldset".(!empty($class)?" class='$class'":"")."><legend>[ $name ]</legend><pre>";
    else echo "</fieldset>";
}

function _cmd($cmd){
    echo "<code>&gt; $cmd</code>";
    $cmd="umask 002 && {$cmd}";
    system($cmd." 2>&1",$result);

    if($result) echo "\n<b>ОШИБКА</b>";
}