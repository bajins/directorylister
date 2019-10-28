<?php

return array(
    // 网站标题
    'web_title'                 => 'Woytu Soft',
    // 基本设置
    'hide_dot_files'            => true,
    'list_folders_first'        => true,
    'list_sort_order'           => 'natcasesort',
    // 主题名称
    'theme_name'                => 'bootstrap',
    'external_links_new_window' => true,
    // README文档文件的解析模式：html、md
    'readme_mode'               => "md",

    // 隐藏文件
    'hidden_files' => array(
        '.ht*',
        '*/.ht*',
        'resources',
        'resources/*',
        'ErrorFiles',
        'ErrorFiles/*',
        'analytics.inc',
        'header.php',
        'footer.php',
        '*/README.*',
        'README.*',
        'admin',
        '*.xml'
    ),

    // Files that, if present in a directory, make the directory
    // a direct link rather than a browse link.
    'index_files' => array(
        'index.htm',
        'index.html',
        'index.php',
        '*/README.*',
        'README.*'
    ),

    // 文件 hash 阈值
    'hash_size_limit' => 268435456, // 256 MB

    // 自定义排序顺序
    'reverse_sort' => array(
        // 'path/to/folder'
    ),

    // 允许以zip文件格式下载目录
    'zip_dirs' => false,

    // 直接将zip文件内容流式传输到客户端，
    // 没有任何临时文件
    'zip_stream' => true,

    'zip_compression_level' => 0,

    // 禁用特定目录的zip下载
    'zip_disable' => array(
        // 'path/to/folder'
    ),

);
