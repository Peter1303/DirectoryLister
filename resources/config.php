<?php

return array(

    // 基本设置
    'hide_dot_files'            => true,
    'list_folders_first'        => true,
    'list_sort_order'           => 'natcasesort',
    'external_links_new_window' => true,

    // 隐藏文件
    'hidden_files' => array(
        '.ht*',
        '*/.ht*',
        'resources',
        'resources/*',
        'ErrorFiles',
        'ErrorFiles/*',
        'analytics.inc',
        '*.php',
        '*.html',
        '.well-known',
        '.well-known/*',
        '*/README.html',
        'README.html',
        'robots.txt'
    ),

    // 如果存在于目录中，则直接浏览目录而不是浏览链接
    'index_files' => array(
        'index.htm',
        'index.html',
        'index.php'
    ),

    // 自定义排序顺序
    'reverse_sort' => array(
        // 'path/to/folder'
    ),

    // 允许以zip文件格式下载目录
    'zip_dirs' => false,

    // 直接将zip文件以文件流传输到客户端，无缓存
    'zip_stream' => true,

    'zip_compression_level' => 0,

    // 禁用特定目录的zip下载
    'zip_disable' => array(
        // 'path/to/folder'
    ),

);
