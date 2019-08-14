<?php

// 包含DirectoryLister类
require_once('resources/DirectoryLister.php');

// 初始化DirectoryLister对象
$lister = new DirectoryLister();

// 限制对当前目录的访问
ini_set('open_basedir', getcwd());

if (isset($_GET['zip'])) {

    $dirArray = $lister->zipDirectory($_GET['zip']);
} else {

    // 初始化目录数组
    if (isset($_GET['dir'])) {
        // 调用DirectoryLister.listDirectory，再调用DirectoryLister._readDirectory
        $dirArray = $lister->listDirectory($_GET['dir']);
    } else {
        $dirArray = $lister->listDirectory('.');
    }

    // 定义主题路径
    if (!defined('THEMEPATH')) {
        define('THEMEPATH', $lister->getThemePath());
    }

    // 设置主题索引的路径
    $themeIndex = $lister->getThemePath(true) . '/index.php';

    // 初始化主题
    if (file_exists($themeIndex)) {
        include($themeIndex);
    } else {
        die('ERROR: Failed to initialize theme');
    }
}
