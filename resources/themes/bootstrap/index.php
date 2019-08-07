<!DOCTYPE html>
<?php
header("Content-type: text/html; charset=utf-8");
$md_path_all = $lister->getListedPath();
$suffix_array = explode('.', $_SERVER['HTTP_HOST']);
$suffix = end($suffix_array);
$md_path = explode($suffix, $md_path_all);
if ($md_path[1] != "") {
    $md_path_last = substr($md_path[1], -1);;
    if ($md_path_last != "/") {
        $md_file = "." . $md_path[1] . "/README.html";
    } else {
        $md_file = "." . $md_path[1] . "README.html";
    }
}
if (file_exists($md_file)) {
    $md_text = file_get_contents($md_file);
} else {
    $md_text = "";
}
?>
<html lang="zh-CN">

<head>
    <title>woytu Soft
        <?php echo $md_path_all; ?>
    </title>
    <!-- 网站LOGO -->
    <link rel="shortcut icon" href="resources/themes/bootstrap/img/folder.png" />
    <!-- CSS基本库 -->
    <link rel="stylesheet" href="resources/themes/bootstrap/css/bootstrap.min.css" />
    <!-- 网站图标CSS式样 -->
    <link rel="stylesheet" href="resources/themes/bootstrap/css/font-awesome.min.css" />
    <!--<link rel="stylesheet" href="resources/themes/bootstrap/css/all.min.css" />
    <link rel="stylesheet" href="resources/themes/bootstrap/css/v4-shims.min.css" />-->
    <!-- 网站主要式样 -->
    <link rel="stylesheet" href="resources/themes/bootstrap/css/style.css" />
    <!-- 代码高亮样式 -->
    <link rel="stylesheet" href="resources/themes/bootstrap/css/prism.css" />

    <!-- JS基本库 -->
    <script src="resources/themes/bootstrap/js/jquery.min.js"></script>
    <!-- JS基本库 -->
    <script src="resources/themes/bootstrap/js/bootstrap.min.js"></script>
    <!-- 代码高亮JS依赖 -->
    <script src="resources/themes/bootstrap/js/prism.js"></script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <?php file_exists('analytics.inc') ? include('analytics.inc') : false; ?>

    <!-- header start -->
    <?php file_exists('header.php') ? include('header.php') : include($lister->getThemePath(true) . "/default_header.php"); ?>
    <!-- header end -->
</head>

<body>
    <div class="path-announcement navbar navbar-default navbar-fixed-top">
        <div class="path-announcement2 container">
            <!-- 顶部公告栏 start -->
            <p style="color:red">
                <i class="fa fa-volume-down"></i>
                <?php file_exists('bulletin.php') ? include('bulletin.php') : include($lister->getThemePath(true) . "/default_bulletin.php"); ?>
            </p>
            <!-- 顶部公告栏 end -->
        </div>
    </div>
    <div class="container" id="container_top">
        <div class="page-content container" id="container_page">
            <!-- 系统错误消息  -->
            <?php if ($lister->getSystemMessages()) : ?>
                <?php foreach ($lister->getSystemMessages() as $message) : ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo $message['text']; ?>
                        <a class="close" data-dismiss="alert" href="#">&times;</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- content -->
            <div id="directory-list-header">
                <div class="row">
                    <div class="col-md-7 col-sm-6 col-xs-10">文件</div>
                    <div class="col-md-2 col-sm-2 col-xs-2 text-right">大小</div>
                    <div class="col-md-3 col-sm-4 hidden-xs text-right">最后修改时间</div>
                </div>
            </div>
            <ul id="directory-listing" class="nav nav-pills nav-stacked">
                <?php foreach ($dirArray as $name => $fileInfo) : ?>
                    <li data-name="<?php echo $name; ?>" data-href="<?php echo $fileInfo['url_path']; ?>">
                        <a href="<?php echo $fileInfo['url_path']; ?>" class="clearfix" data-name="<?php echo $name; ?>">
                            <div class="row">
                                <span class="file-name col-md-7 col-sm-6 col-xs-9">
                                    <i class="fa <?php echo $fileInfo['icon_class']; ?> fa-fw"></i>
                                    <?php echo $name; ?>
                                </span>
                                <span class="file-size col-md-2 col-sm-2 col-xs-3 text-right">
                                    <?php echo $fileInfo['file_size']; ?>
                                </span>
                                <span class="file-modified col-md-3 col-sm-4 hidden-xs text-right">
                                    <?php echo $fileInfo['mod_time']; ?>
                                </span>
                            </div>
                        </a>
                        <?php if (is_file($fileInfo['file_path'])) : ?>
                        <?php else : ?>
                            <?php if ($lister->containsIndex($fileInfo['file_path'])) : ?>
                                <a href="<?php echo $fileInfo['file_path']; ?>" class="web-link-button" <?php if ($lister->externalLinksNewWindow()) : ?>target="_blank" <?php endif; ?>>
                                    <i class="fa fa-external-link"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- READMNE start -->
        <?php
        if ($md_text != "") {
            // 多行字符串开始
            $readme_top = '
                    <div class="container readme-background" id="readmeTop">
                        <div class="Box-header px-2 clearfix">
                            <h3 class="Box-title pr-3">
                                <svg class="octicon octicon-book" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true">
                                    <path fill-rule="evenodd" 
                                        d="M3 5h4v1H3V5zm0 3h4V7H3v1zm0 2h4V9H3v1zm11-5h-4v1h4V5zm0 2h-4v1h4V7zm0 2h-4v1h4V9zm2-6v9c0 .55-.45 1-1 1H9.5l-1 1-1-1H2c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1h5.5l1 1 1-1H15c.55 0 1 .45 1 1zm-8 .5L7.5 3H2v9h6V3.5zm7-.5H9.5l-.5.5V12h6V3z">
                                    </path>
                                </svg> README
                            </h3>
                        </div>
                        <div class="readme" id="readme">
                        ';
            echo $readme_top, $md_text, "</div>", "</div>";
        }
        ?>
        <!-- READMNE end -->

        <!-- 留言 -->
        <!-- Valine -->
        <div id="vcomments"></div>

        <!-- 来必力 -->
        <!-- <div id="lv-container" data-id="city" data-uid="MTAyMC80NTE3MC8yMTY4OA=="></div> -->

        <!-- Gitalk -->
        <!-- <div id="gitalk-container"></div> -->

        <!-- Gitment -->
        <!-- <div id="gitment-container"></div> -->

    </div>

    <!-- footer start -->
    <hr id="footer_hr" style="margin-bottom: 0;margin-top: 40px;" />
    <footer class="container">
        <div class="footer">
            <?php file_exists('footer.php') ? include('footer.php') : include($lister->getThemePath(true) . "/default_footer.php"); ?>
        </div>
    </footer>
    <!-- footer end -->

    <script type="text/javascript">
        // 在html全部加载完了才执行
        window.onload = function() {
            anchorPositioning();
            changeDivHeight();
        }
        // onresize 事件会在窗口或框架被调整大小时发生
        window.onresize = function() {
            changeDivHeight();
        }

        function anchorPositioning() {
            // 获取整个 URL 为字符串。
            var url = window.location.href;
            // 判断URL中是否带#号
            if (url.indexOf("#") != -1) {
                var divId = url.split("#")[1];
                // document.getElementById(divId).scrollIntoView(true);
                // window.location.hash = divId;
                $('html,body').animate({
                    scrollTop: $("#" + divId).offset().top - 150 + "px"
                }, 500);
            }
        }


        function changeDivHeight() {
            if (document.getElementById("container_readme")) {
                container_readme.style.marginBottom = '0';
            }

            ScrollHeight_body = document.body.offsetHeight;
            InnerHeight_window = window.innerHeight;
            container_top.style.minHeight = '0';
            ClientHeight_top = container_top.clientHeight + 60;
            ClientHeight_top1 = ClientHeight_top + 69;
            ClientHeight_top2 = ClientHeight_top1 - 60;
            container_top.style.minHeight = '';

            if (ScrollHeight_body > ClientHeight_top2) {
                footer_hr.style.marginTop = '0';
            } else {
                footer_hr.style.marginTop = '40px';
            }

            if (ScrollHeight_body > InnerHeight_window) {
                if (ClientHeight_top > InnerHeight_window) {
                    container_top.style.marginBottom = '0';
                    // container_page.style.marginBottom = '0';
                    if (document.getElementById("container_readme")) {
                        container_readme.style.marginTop = '20px';
                    }
                } else {
                    footer_hr.style.marginTop = '40px';
                    container_top.style.marginBottom = '';
                    container_page.style.marginBottom = '';
                    if (document.getElementById("container_readme")) {
                        container_readme.style.marginTop = '';
                    }
                }
            } else {
                if (ScrollHeight_body < ClientHeight_top1) {
                    container_top.style.marginBottom = '0';
                    // container_page.style.marginBottom = '0';
                    if (document.getElementById("container_readme")) {
                        container_readme.style.marginTop = '20px';
                    }
                } else {
                    footer_hr.style.marginTop = '40px';
                    container_top.style.marginBottom = '';
                    container_page.style.marginBottom = '';
                    if (document.getElementById("container_readme")) {
                        container_readme.style.marginTop = '';
                    }
                }
            }
        }
    </script>

    <!-- Valine https://valine.js.org/ -->
    <script src='//unpkg.com/valine/dist/Valine.min.js'></script>
    <script>
        new Valine({
            el: '#vcomments',
            appId: 'm9S5QXsdju39LvMs8ooRRIiF-MdYXbMMI',
            appKey: 'UfBRjySkb4bjPiFuH0Pxe3a9'
        })

        // 来必力 https://www.livere.com
        /*(function (d, s) {
            var j, e = d.getElementsByTagName(s)[0];
            if (typeof LivereTower === 'function') {
                return;
            }
            j = d.createElement(s);
            j.src = 'https://cdn-city.livere.com/js/embed.dist.js';
            j.async = true;
            e.parentNode.insertBefore(j, e);
        })(document, 'script');*/

        // Gitalk unpkg.com/docsify/lib/plugins/gitalk.min.js
        /*const gitalk = new Gitalk({
            clientID: '40cfe11992c4ef076a4b',
            clientSecret: 'b43dc6b3740a306bec40c25c2db1ecc6c02e7716',
            repo: 'woytu.github.io',
            owner: 'woytu',
            admin: ['woytu'],
            // facebook-like distraction free mode
            distractionFreeMode: false
        });*/

        // Gitment https://imsun.net/posts/gitment-introduction/
        /*var gitment = new Gitment({
            //id: '页面 ID', // 可选。默认为 location.href
            owner: 'woytu',
            repo: 'woytu.github.io',
            oauth: {
                client_id: '40cfe11992c4ef076a4b',
                client_secret: 'b43dc6b3740a306bec40c25c2db1ecc6c02e7716',
            },
        });*/
    </script>

    <!-- https://gitter.im -->
    <script>
        ((window.gitter = {}).chat = {}).options = {
            //room替换成自己的聊天室名称即可，room的名称规则是：username/roomname
            room: 'woytu/community'
        };
    </script>
    <script src="https://sidecar.gitter.im/dist/sidecar.v1.js" async defer></script>

</body>

</html>