<?php
// 加入 DirectoryLister class
require_once('resources/DirectoryLister.php');
// 初始化 DirectoryLister 对象
$lister = new DirectoryLister();
// 限制访问当前目录
ini_set('open_basedir', getcwd());
if (isset($_GET['zip'])) {
    $dirArray = $lister->zipDirectory($_GET['zip']);
} else {
    // 初始化目录数组
    if (isset($_GET['dir'])) {
        $dirArray = $lister->listDirectory($_GET['dir']);
    } else {
        $dirArray = $lister->listDirectory('.');
    }
    define('PATH', __DIR__ . '/resources/');
    // 初始化页面
    include('resources/index.php');
}
