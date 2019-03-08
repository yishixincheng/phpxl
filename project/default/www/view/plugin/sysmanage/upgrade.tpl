{tpl "/plugin/sysmanage/header"}

<link rel="stylesheet" type="text/css" href="/view/plugin/sysmanage/css/upgrade.css">

{tpl "/plugin/sysmanage/header-close"}


<div class="thispage" id="thispage_eventproxy">


    <div class="version_info">
        <div class="version_no">
            当前版本：{$versioninfo}
        </div>
        <div class="version_check">
            <a data-event="checkversion">点击检测新版本</a>
        </div>
    </div>

    <div class="upgrade_title"></div>

    <div class="upgrade_progressbarbox">

    </div>
    <div class="upgrade_info">

    </div>




</div>



{tpl "/plugin/sysmanage/footer-start"}

<script type="text/javascript" src="/view/plugin/sysmanage/js/upgrade.js"></script>

{tpl "/plugin/sysmanage/footer"}