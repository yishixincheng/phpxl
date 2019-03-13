{tpl "/plugin/sysmanage/header"}

<style type='text/css'>
    .pbox_wrap{
        width: 1000px;
        margin: auto;
        padding-top: 50px;
    }
    .pbox_wrap dl{
        width: 100%;
    }
    .pbox_wrap dl ul{
        height: 30px;
        line-height: 30px;
        clear: both;
    }
    .pbox_wrap dl ul li{
        float: left;
        text-align: center;
        margin: -1px 0 0 -1px;
        border: 1px solid #999;
        height: 30px;
    }
    .pbox_wrap dl dt{
        font-weight: bold;
    }
    .pbox_wrap .c0{
        width: 80px;
    }
    .pbox_wrap .c1{
        width: 120px;
    }
    .pbox_wrap .c2{
        width: 120px;
    }
    .pbox_wrap .c3{
        width: 150px;
    }
    .pbox_wrap .c4{
        width: 100px;
    }
    .pbox_wrap .c5{
        width: 100px;
    }
    .pbox_wrap .c6{
        width: 200px;
    }

    ._progressbarbox{
        height: 15px;
        width:150px;
        margin: auto;
        margin-top: 7.5px;
        background: #aeaca2;
    }
    ._progressbar{
        height: 15px;
        width: 0;
        background: #807c6c;
    }

</style>


{tpl "/plugin/sysmanage/header-close"}


<div class="pbox_wrap">


    <dl>
        <dt>
            <ul><li class="c0">序号</li><li class="c1">插件名称</li>
            <li class="c2">命名空间</li><li class="c3">版本</li>
            <li class="c4">状态</li><li class="c5">操作</li><li class="c6">新版本</li></ul>
        </dt>

        {loop $plugins $k=>$plugin}

             <dd>

                 <ul>
                     <li class="c0">{$n}</li>
                     <li class="c1">{$plugin['name']}</li>
                     <li class="c2">{$k}</li>
                     <li class="c3">{$plugin['version']}</li>
                     <li class="c4">{if $plugin['isclose']}关闭{else}开启{/if}</li>
                     <li class="c5" data-plugintype="{$k}">
                         {if $k=="accredit"}-{else}
                                 {if $plugin['isclose']}<a data-event="open">点击开启</a>{else}<a data-event="close">点击关闭</a>{/if}
                         {/if}
                     </li>
                     <li class="c6" data-plugintype="{$k}" data-pluginname="{$plugin['name']}" data-softversion="{$plugin['softversion']}" data-currsoftversion="{$plugin['currsoftversion']}" data-version="{$plugin['version']}" data-lastversion="{$plugin['lastversion']}" data-downloadurl="{$plugin['downloadurl']}">
                         {if $plugin['lastversion']}
                             {if $plugin['newplugin']}
                                 <a data-event="download">下载</a>
                             {else}
                                 <a data-event="upgrade">更新</a>
                             {/if}
                         {else}
                             -
                         {/if}
                     </li>

                 </ul>

             </dd>

        {/loop}

    </dl>


</div>



{tpl "/plugin/sysmanage/footer-start"}

<script type="text/javascript" src="/view/plugin/sysmanage/js/pluginmanage.js"></script>

{tpl "/plugin/sysmanage/footer"}