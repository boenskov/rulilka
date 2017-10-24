<?php

$title="GIT - управление репозиторием";
require_once "_head.php";



global $currentBranch;
$currentBranch=false;

echo "<nav>
<a href='?pull'>[ Сделать pull ]</a> 
<a href='?ccall'>[ drush cc all ]</a> 
<a href='?updatedb'>[ drush updatedb ]</a> 
<a href='?cff'>[ drush demka_createfakefiles ]</a> 
</nav>";


if(!empty($_GET["checkout"])){
    $b=$_GET["checkout"];
    if(strpos($b,"/")){
        $b=explode("/",$b);
        $b=end($b);
    }
    _block("Переключение на  $b","active");
    _cmd("git checkout $b");
    _block();
    exit();
}

if(!empty($_GET["del_branch"])){
    $b=$_GET["del_branch"];
    if(strpos($b,"/")){
        $b=explode("/",$b);
        $b=end($b);
    }
    _block("Удаление локальной ветки $b","active");
    _cmd("git branch -D $b");
    _block();
    exit();
}

if(isset($_GET["pull"])){
    _block("Pull...","active");
    _cmd("git pull");
    _block();
}

if(isset($_GET["ccall"])){
    _block("Сброс кеша D7...","active");
    _cmd("drush cc all");
    _block();
}

if(isset($_GET["updatedb"])){
    _block("Накатываем обновы базы...","active");
    _cmd("drush updatedb -y");
    _block();
}

if(isset($_GET["cff"])){
    _block("Воссоздаем отсутствующее файло...","active");
    _cmd("drush demka_createfakefiles");
    _block();
}

//if(isset($_GET["cc"])){
//    echo "<pre>".makeHead("Сброс кешей...");
//    $cmd="umask 002 && drush cc all";
//    echo "\$ <i>$cmd</i>\n\n";
//    system($cmd." 2>&1",$result);
//    echo "</pre>";
//}
//
//if(isset($_GET["updatedb"])){
//    echo "<pre>".makeHead("Обновление  структуры БД...");
//    $cmd="umask 002 && drush -y updatedb";
//    echo "\$ <i>$cmd</i>\n\n";
//    system($cmd." 2>&1",$result);
//    echo "</pre>";
//}

echo "<table width='100%'><tr><td width='50%' valign='top'>";
showBranches();
echo "</td><td valign='top'>";
showStatus();
echo "</td></tr></table>";

$branch=$currentBranch;
if(!empty($_GET["show_branch"])) $branch=$_GET["show_branch"];
showLog($branch);


function showBranches(){
    _block("Локальные ветки");
    $o=[];
    exec("git branch --list",$o);
    _showBranchItems($o);
    _block();

    _block("Удаленные ветки");
    $o=[];
    exec("git branch -r",$o);
    _showBranchItems($o/*,false*/);
    _block();

}

function _showBranchItems($o/*,$can_checkout=true*/){
    #var_dump($o);
    global $currentBranch;
    if(!empty($o)){
        echo "<table class='styled'><tr><th width='1%'>Ветка</th><th>Операции</th></tr>";

        foreach($o as $oo){
            $detached=false;
            if(preg_match("#\(detached from ([a-f0-9]*)\)#",$oo,$d)){
                $currentBranch=$d[1];
                $current=true;
                $b=$d[1];
                $detached=true;
            } elseif(preg_match("#\HEAD detached at ([a-f0-9]*)#",$oo,$d)){
                $currentBranch=$d[1];
                $current=true;
                $b=$d[1];
                $detached=true;
            } else {
                $i = explode(" ", trim($oo));
                if (count($i) == 2) {
                    $current = true;
                    array_shift($i);
                } else $current = false;
                $b = reset($i);
                if ($current) $currentBranch = $b;
            }
            $can_delete=true;
            if(strpos($b,"master")===0) $can_delete=false;
            $can_checkout=true;
            $remote=strpos($b,"/")!==false;
            echo "<tr>";
            echo "<td><nobr><a href='?show_branch=$b'>"
                .($current?"&gt; <b>":"")
                .$b
                .($current?"</b>":"")
                ."</a>"
                .($detached?" (detached)":"")
                ."</nobr></td><td>"
                .(($current || !$can_checkout)?"":" <a href='?checkout=$b' style='color: green;'><b>[checkout]</b></a>")
                .(($current || !$can_delete || $remote)?"":" <a href='?del_branch=$b' style='color:red;margin-left: 30px;' onclick='return confirm(\"Удалить локальную ветку?\")'>[del]</a>")
                ."</td>"
            ;
        }
        echo "</table>";
    } else
        echo "Отсутствуют\n";
}


function showLog($branch){
    _block("Коммиты ветки $branch");
    $o=[];
    #exec("git log --first-parent --pretty=\"#%H  %<|(15)%an  %<(17)%ai  %s %+b\" --graph $branch",$o);
    exec("git log --first-parent --pretty=\"~%H~%an~%ai~%s~%+b\" --graph $branch",$o);
#    var_dump($o);
    _showLogItems($o);
    _block();
}

function _showLogItems($o){
    global $currentBranch;
    #var_dump($currentBranch);
    if(!empty($o)){
        echo "<table class='styled'><tr><th>sha</th><th>Операции</th><th>Описание</th><th>Автор</th><th>Дата</th></tr>";
        $lines=[];
        $last_sha=false;
        foreach($o as $oo){
            $p=explode("~",$oo);
            if(count($p)>4){
                $graph=array_shift($p);
                $sha=array_shift($p);
                $author=array_shift($p);
                $date=array_shift($p);
                $text=join(" ",$p);

                # преобразования
                $date=date("d.m.Y H:i",strtotime($date));
                $op=[];
                $op[]="<a href='?checkout=$sha'>[checkout]</a>";
                if(strpos($sha,"$currentBranch")!==false) {
                    $sha="<b>&gt;&gt;&gt; $currentBranch</b>";
                }
                $lines[$sha]=[$sha,$author,$date,$op,"text"=>$text];
                $last_sha=$sha;
            } else {
                if(!empty($last_sha)) $lines[$last_sha]["text"].="<br>$oo";
            }
            #$oo=preg_replace("|(#([0-9a-f]+)) |",'<a href="?commit=$2">[$2]</a> <a href=\'?checkout=$2\'>[выбрать]</a>',$oo);
            #$oo=preg_replace("|(#([0-9a-f]+)) |",'[$2] <a href=\'?checkout=$2\'>[checkout]</a>',$oo);
//            if(strpos($oo,"[$currentBranch")!==false) {
//                $oo="<b>".str_replace("[$currentBranch", "&gt;&gt;&gt; [" . $currentBranch, $oo)."</b>";
//                #$oo="<b>$oo</b>";
//            }
            #echo $oo."\n";
        }
        foreach($lines as $l){
            # вывод
            echo "<tr>
                    <td><nobr>{$l[0]}</nobr></td>
                    <td><nobr> ".join(" ",$l[3])." </nobr></td>
                    <td>{$l["text"]}</td>
                    <td><nobr>{$l[1]}</nobr></td>
                    <td><nobr>{$l[2]}</nobr></td>
                    </tr>
                    ";

        }
        echo "</table>";
    } else
        echo "Отсутствуют\n";
}

function showStatus(){
    _block("Статус");
    system("git status"." 2>&1",$result);
    _block();
}


require_once "_bottom.php";