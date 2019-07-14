<!DOCTYPE html>
<?php

require_once 'settings.php';

$md_path_all = $lister->getListedPath();
$breadcrumbs = $lister->listBreadcrumbs();

$notice = '';
if (notice) {
    $file_notice =  PATH . 'notice.txt';
    if (file_exists($file_notice)) {
        $fop = fopen($file_notice, "r");
        $notice = fread($fop, filesize($file_notice));
    }
}
?>
<html>
<head>
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>文件索引</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <meta name="description" content="文件索引">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link href="//cdn.bootcss.com/mdui/0.4.2/css/mdui.css" rel="stylesheet">
    <link href="//cdn.bootcss.com/mdui/0.4.2/css/mdui.min.css" rel="stylesheet">

    <script src="//cdnjs.loli.net/ajax/libs/mdui/0.4.2/js/mdui.min.js"></script>
    <script src="//cdn.bootcss.com/jquery/2.1.1/jquery.min.js"></script>

    <style type="text/css">
        body{
            font-family:"Helvetica Neue", Helvetica, Microsoft Yahei, sans-serif
        }

        .app-title a {
            color: #ffffff
        }

        .font {
            font-family:"Helvetica Neue", Helvetica, Microsoft Yahei, sans-serif;
        }

        a {
            text-decoration: none
        }

        #wrap{
            display: flex;
            padding: 25px 25px 25px;
            justify-content: flex-start;
        }

        .notice {
            padding-left: 25px;
            width: 10000px;
        }

        marquee {
            font-family:"Helvetica Neue", Helvetica, Microsoft Yahei, sans-serif;
            font-size: 18px;
            color: #535353;
            width: 100%;
        }

        .foot{
            position:fixed;
            bottom:0px;
            box-sizing:border-box;
            padding:15px 15px 15px 15px;
            width:100%;
            background-color:#f0f0f0;
            color:#787878;
            font-weight:300;
            font-size:15px;
            line-height:25px;
        }

        .foot>a{
            color:#464646
        }

    </style>
</head>
<body class="mdui-appbar-with-toolbar mdui-theme-primary-blue-grey mdui-theme-accent-pink mdui-loaded">
<div class="mdui-appbar mdui-appbar-fixed">
    <div class="mdui-toolbar mdui-color-theme">
        <span class="mdui-typo-headline app-title">
            <?php foreach($breadcrumbs as $breadcrumb): ?>
                <?php if ($breadcrumb != end($breadcrumbs)): ?>
                    <a href="<?php echo $breadcrumb['link']; ?>"><?php echo $breadcrumb['text']; ?></a>
                    <span> / </span>
                <?php else: ?>
                    <?php echo $breadcrumb['text']; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </span>
        <div class="mdui-toolbar-spacer"></div>
        <button mdui-menu="{target: '#menu'}" class="mdui-btn mdui-btn-icon"><i class="mdui-icon material-icons">more_vert</i></button>
        <ul class="mdui-menu" id="menu">
            <li class="mdui-menu-item">
                <a href="https://github.com/Peter1303/DirectoryLister" target="_blank" class="mdui-ripple">Github</a>
            </li>
            <li class="mdui-divider"></li>
            <li class="mdui-menu-item">
                <a href="https://peter1303.top/" target="_blank" class="mdui-ripple">我的博客</a>
            </li>
        </ul>
    </div>
</div>
<!-- 内容 -->
<div class="mdui-container mdui-center" <?php if (footer) { ?>style="padding-bottom:<?php echo empty(icp) ? '60' : '100' ?>px;"<?php } ?>>
    <?php if (notice) { ?>
        <div id="wrap">
            <i class="<?php if (circle_icon) {echo 'mdui-list-item-avatar ';} ?>mdui-list-item-icon mdui-icon mdui-icon material-icons">chat</i>
            <div class="notice">
                <marquee direction="left"><?php echo $notice ?></marquee>
            </div>
        </div>
        <div class="mdui-divider"></div>
    <?php } ?>
    <ul class="mdui-list" id="list">
        <?php $folder = 0; $file = 0; ?>
        <?php if (!empty($dirArray)) {
            foreach($dirArray as $name => $fileInfo): ?>
                <?php if (is_file($fileInfo['file_path'])){
                    if ($folder == 0 && type) { ?>
                        <li class="mdui-subheader-inset font" id="tip_file">文件</li>
                    <?php } $folder ++; ?>
                    <li class="mdui-list-item mdui-ripple" onclick="window.open('<?php echo $fileInfo['url_path']; ?>','_self');">
                        <i class="<?php if (circle_icon) {echo 'mdui-list-item-avatar ';} ?>mdui-list-item-icon mdui-icon material-icons"><?php echo $fileInfo['icon_class']; ?></i>
                        <div class="mdui-list-item-content" onclick="window.open('<?php echo $fileInfo['url_path']; ?>','_self')">
                            <div class="mdui-list-item-title">
                                <?php echo $name; ?>
                            </div>
                            <div class="mdui-list-item-text mdui-list-item-one-line">
                                <?php echo $fileInfo['file_size']; ?>
                                <div style="float: right"><?php echo $fileInfo['mod_time']; ?></div>
                            </div>
                        </div>
                    </li>
                    <?php if (divider) { ?>
                        <li class="mdui-divider-inset mdui-m-y-0"></li>
                    <?php } ?>
                <?php } else {
                    if ($file == 0) { ?>
                        <?php if ($name != '..' && type) { ?>
                            <li class="mdui-subheader-inset font" id="tip_folder">文件夹</li>
                        <?php } ?>
                    <?php } $file ++; ?>
                    <li class="mdui-list-item mdui-ripple" onclick="window.open('<?php echo $fileInfo['url_path']; ?>','_self');">
                        <i class="<?php if (circle_icon) {echo 'mdui-list-item-avatar ';} ?>mdui-list-item-icon mdui-icon material-icons"><?php echo $fileInfo['icon_class']; ?></i>
                        <div class="mdui-list-item-content">
                            <div class="mdui-list-item-title">
                                <?php echo $name == '..' ? '返回上一层' : $name ?>
                            </div>
                            <div class="mdui-list-item-text mdui-list-item-one-line">
                                &nbsp;
                                <?php if ($name != '..') { ?>
                                    <div style="float: right"><?php echo $fileInfo['mod_time']; ?></div>
                                <?php } ?>
                            </div>

                        </div>
                    </li>
                    <?php if (divider) { ?>
                        <li class="mdui-divider-inset mdui-m-y-0"></li>
                    <?php } ?>
                <?php }?>
            <?php endforeach;
        } ?>
    </ul>
</div>
<?php if (footer) {
    include 'footer.php';} ?>
<script>
    <?php if (count) { ?>
    <?php if ($folder != 0) { ?>
    $('#tip_folder').text('文件夹(<?php echo $folder + 1; ?>个)');
    <?php } ?>
    <?php if ($file != 0) { ?>
    $('#tip_file').text('文件(<?php echo $file + 1; ?>个)');
    <?php } ?>
    <?php } ?>
</script>
</body>
</html>
