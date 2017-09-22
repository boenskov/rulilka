<?php

# скрипт запаковки папки в архив phar
$file= __DIR__ . "/ruli.phar";
if(file_exists($file)) unlink($file);
#if(file_exists($file.".gz")) unlink($file.".gz");
$phar =new Phar($file);
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__), RecursiveIteratorIterator::SELF_FIRST);
foreach($objects as $name => $object){
    # пропускаем файлы которые не надо добавлять
    if(basename($name)==basename($file)) continue;
    if(basename($name)==basename($file.".gz")) continue;
    if(basename($name)==basename(__FILE__)) continue;
    if(strpos($name,"/.git/")!==false) continue;
    if(strpos($name,"/.idea/")!==false) continue;
    if(basename($name)=="..") continue;
    if(basename($name)==".") continue;
    if(is_dir($name)) continue;


    echo "$name\n";
    #$code=file_get_contents($name);
//    if(strpos($code,"<?php")===0){
//        $code=php_strip_whitespace($name);
//#		echo "$code";
//#		echo "=====================================================\n";
//#		exit();
//    }
    #$phar->addFromString(str_replace(__DIR__."/","",$name),$code);
    $phar->addFile($name,str_replace(__DIR__."/","",$name));
}
$phar->setStub('<?php
Phar::webPhar();
__HALT_COMPILER(); ?>');

$phar->compressFiles(Phar::GZ);
#$phar->compress(Phar::GZ,'.phar.gz');

