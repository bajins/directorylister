# 优化的DirectoryLister


![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)


## 自定义修改

- 比如README的上下边距
- 添加对表格样式多样化的支持
- 添加对锚点定位支持，使用方式：在链接后面加上`#标签的id`，或者点击标题前的锚点链接
    1. [Markdown解析添加锚点](https://github.com/woytu/DirectoryLister/commit/1c14776e15a35a680ec02a95278abbb1777a950a)
    2. [add 添加锚点可点击的a标签](https://github.com/woytu/DirectoryLister/commit/1d1f90220de2e948f0bba086786e33fb353f7189)

  > 示例链接：https://www.woytu.com#readme


- 化繁为简：去除顶部链接，如果需要顶部链接版本，请看分支[top-links](https://github.com/woytu/DirectoryLister/tree/top-links)
- [添加gitter聊天室](https://github.com/woytu/DirectoryLister/commit/154df157974ac6f883e3484761ad951e0da90ae6)
- [添加留言](https://github.com/woytu/DirectoryLister/commit/67de302d611e4cf011d8fdee8b7e649e662a1d76)
- [修改网站标题为统一在配置文件中设置](https://github.com/woytu/DirectoryLister/commit/0fab9eae60df0926a06a5859f4d528b859b8be4c)
- 添加面包屑导航栏
- 添加配置`readme_mode`（README文档文件的解析模式：html、md）



![新旧式样手机效果对比](/sample-graph.png)



## 安装

下载后，解压并上传到已经搭建好 PHP环境 的服务器中，然后就可以上传文件和创建文件夹了！

- Github打包：https://github.com/woytu/DirectoryLister/archive/master.zip


## 文件结构

- 假设你的虚拟主机是 `/home/wwwroot/xxx.xx`

```
├─ resources/
│   ├ themes/
│   │ └ bootstrap/
│   │    ├ css/                 # 样式文件夹
│   │    │  └ style.css         # 自定义样式文件
│   │    ├ fonts/               # 字体文件夹
│   │    ├ img/                 # 图片文件夹
│   │    ├ js/                  # JavaScript脚本文件夹
│   │    │  └ prism.js          # 自定义js脚本文件
│   │    ├ default_bulletin.php # 顶部公告栏文件
│   │    ├ default_footer.php   # 底部公共文件
│   │    ├ default_header.php   # 顶部公共文件（可以放网站流量统计代码）
│   │    └ index.php            # 网页主文件，其中可以修改顶部公告栏内容
│   │
│   ├ DirectoryLister.php       # 核心函数处理文件
│   ├ Parsedown.php             # Markdown解析依赖
│   ├ config.php                # 配置文件
│   └ fileTypes.php             # 文件类型定义图标文件
│
├ README.md                     # 该文件夹页面内的 说明简介文件
├ index.php                     # 入口文件
│
└ ......
```

* [https://www.php.net/manual/zh](https://www.php.net/manual/zh)

- [https://github.com/erusev/parsedown/wiki/Extensions-and-Related-Libraries](https://github.com/erusev/parsedown/wiki/Extensions-and-Related-Libraries)
- [https://github.com/mrgeneralgoo/typecho-markdown](https://github.com/mrgeneralgoo/typecho-markdown)

+ [https://getcomposer.org](https://getcomposer.org)
+ [https://github.com/michelf/php-markdown](https://github.com/michelf/php-markdown)
+ [https://github.com/thephpleague/commonmark](https://github.com/thephpleague/commonmark)
+ [https://github.com/PHPOffice](https://github.com/PHPOffice)


* [https://packagist.org/search/?q=orm](https://packagist.org/search/?q=orm)
* [https://www.php.net/manual/zh/refs.database.abstract.php](https://www.php.net/manual/zh/refs.database.abstract.php)
* [https://github.com/catfan/Medoo](https://github.com/catfan/Medoo)
* [https://github.com/gabordemooij/redbean](https://github.com/gabordemooij/redbean)
* [https://github.com/doctrine/orm](https://github.com/doctrine/orm)
* [https://github.com/yiisoft/db](https://github.com/yiisoft/db)
* [https://github.com/ADOdb/ADOdb](https://github.com/ADOdb/ADOdb)


## 注意事项：

### 不显示文件和目录

> 如果安装`lnmp`一键包上传`DirectoryLister`后，不显示文件和目录，那么可能是`PHP`函数` scandir `被禁用了，取消禁用即可。

``` bash
sed -i 's/,scandir//g' /usr/local/php/etc/php.ini
# 取消scandir函数禁用
/etc/init.d/php-fpm restart
# 重启 PHP生效
```

### 程序放在网站子目录不显示`README.md`的解决方法

> 因为程序有个判断 `README.md` 路径的代码，而如果是正常使用域名或IP(即使加上)，都是可以自适应的。
>
> 但是如果把程序放在子目录下，就会无法获取正确 `README.md` 路径，需要你手动修改下程序里的一句代码。
>
> 假设你将程序放在了子目录 `zimulu` 中（也就是 `http://xxx.xx/zimulu` 才能访问到程序网页）。
>
> 首先打开该文件： `/resources/themes/bootstrap/index.php`  
>
> 找到第5行的： `$suffix_array = explode('.', $_SERVER['HTTP_HOST']);`  
>
> 将其修改为： `$suffix_array = explode('.', $_SERVER['HTTP_HOST']."/zimulu");`

### 简介功能说明

> 可以在每个文件夹下面放一个 `README.md` 文件，这个文件里写着简介说明内容即可，格式参考自带的示例文件。
>
> 为了避免中文乱码，把 `README.md` 文件用`UTF-8无BOM编码`保存！

### 文件修改说明

> 修改网站中头部`导航标题`，去`/resources/config.php`这个文件里替换`web_title`的值为自己要改的。  
>
> 修改网站顶部公告栏内容，去`/resources/themes/bootstrap/default_bulletin.php`这个文件里编辑。  

> 网站头部公共文件：`/resources/themes/bootstrap/default_header.php `

> 网站底部公共文件：`/resources/themes/bootstrap/default_footer.php `

> 如果想要插入流量统计代码，那只需要把代码写到 `default_header.php` 文件内即可。

---


本程序基于[逗比魔改](https://github.com/ToyoDAdoubi/DirectoryLister)并基于[Directory Lister原版](https://github.com/DirectoryLister)魔改

- [https://github.com/helloxz/zdir](https://github.com/helloxz/zdir)
