{tpl "/plugin/sysmanage/header"}

<style type='text/css'>
    .pbox_wrap{
        width: 800px;
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
</style>


{tpl "/plugin/sysmanage/header-close"}


<div class="pbox_wrap">


    <dl>
        <dt>
            <ul><li class="c0">序号</li><li class="c1">插件名称</li>
            <li class="c2">命名空间</li><li class="c3">版本</li>
            <li class="c4">状态</li><li class="c5">操作</li></ul>
        </dt>

        {loop $plugins $k=>$plugin}

             <dd>

                 <ul>
                     <li class="c0">{$n}</li>
                     <li class="c1">{$plugin['name']}</li>
                     <li class="c2">{$k}</li>
                     <li class="c3">{$plugin['version']}</li>
                     <li class="c4">{if $plugin['isclose']}关闭{else}开启{/if}</li>
                     <li class="c5">{if $plugin['isclose']}<a>点击开启</a>{else}<a>点击关闭</a>{/if}</li>
                 </ul>

             </dd>

        {/loop}

    </dl>


</div>



{tpl "/plugin/sysmanage/footer-start"}


{tpl "/plugin/sysmanage/footer"}