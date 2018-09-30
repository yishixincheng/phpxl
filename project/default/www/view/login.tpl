<!DOCTYPE HTML>
<html>
<head>
    <meta charset="{CHARSET}">
    <title>{$Title}</title>
    <meta name="keywords" content="{$Config['seo_keyword']}">
    <meta name="description" content="{$Config['seo_dis']}">
    <meta name="technicalsupport" content="{$Config['seo_authorname']}">
    <meta name="company" content="{$Config['seo_company']}">
    <meta name="system-development-author" content="{$Config['auth']}">
    <script type="text/javascript" src="{:jsrootpath}jquery.js"></script>
    <script type="text/javascript" src="{:jsrootpath}jquery.lazyload.js"></script>
    <script type="text/javascript" src="{:jsrootpath}json.js"></script>
    <script type="text/javascript" src="{:jsrootpath}xl.config.js"></script>
    <script type="text/javascript" src="{:jsrootpath}xl.js"></script>
    <script type="text/javascript" src="{:jslibpath}xl.tpl.js"></script>
    <script type="text/javascript" src="{:jslibpath}xl.form.js"></script>
    <script type="text/javascript" src="{:jslibpath}xl.formset.js"></script>
    <script type="text/javascript" src="{:jslibpath}xl.store.js"></script>

    <script type="text/javascript">

        var $_R={
            R_G:'{ROUTE_G}',
            R_M:'{ROUTE_M}',
            R_R:'{ROUTE_R}',
            urlmode:'{$Config["urlmode"]}',
            HOST:'{HOST}'
        };
        var $_M={
            uid:"{$member['uid']}",
            nickname:"{$member['nickname']}",
            avatar:"{$member['avatar']}",
            roleid:"{$member['roleid']}",
            rolename:"{$member['rolename']}"
        };
        var $_C={
            cityname:"{GetG('city/cityname')}",
            citycode:"{GetG('city/citycode')}",
            cityflag:"{GetG('city/cityflag')}"
        };
        var $_G={
            siteurl:"{$Config['site_url']}",
            avatar:"{$Config['avatar_src']}",
            defaultimg:"{$Config['defaultimg']}",
            shengcode:"{$Config['webshengcode']}",
            shengname:"{$Config['webshengname']}",
            citycode:"{$Config['webcitycode']}",
            cityname:"{$Config['webcityname']}"
        };
        var $_FORMHASH='{FORMHASH}';
        var $_sessionkey="{GetG('member/sessionkey')}";
        var $_PlugPath="{PLUGIN_PATH}";
        var g_adads=[];
        var g_onlineqqs="{$Config['online_qqs']}";
        var g_firstaccess="{$Config['firstaccess']}";

    </script>

    <link rel="stylesheet" type="text/css" href="{:cssrootpath}global.css">

    <link rel="stylesheet" type="text/css" href="/view/css/login.css">

</head>
<body>


<div class="wrap">
    <div class="login-bg">
    <ul class="login-bg-ul">
        <li>
            <img src="/static/images/login_bg.png" alt="登录背景">
        </li>
    </ul>
    </div>
  
    <div class="content">

        <div class="login-form" id="Id_login_wap">
            <form onSubmit="return false;">
                <p>乐房通数据管理中心</p>
                <div class="username">
                    <input value="" id="Id_username" data-bind="value:username" type="text" placeholder="用户名">
                </div>
                <div class="password">
                    <input value="" id="Id_password" data-bind="value:password" type="password" placeholder="登录密码">
                </div>
                <div class="checkcodebox" id="Id_checkcode_box">
                    <input value="" data-bind="value:checkcode" type="text" placeholder="输入验证码">
                    <span id="Id_checkcode_img"></span>
                </div>
                <div class="login-btn">
                    <button data-event="submit">登&nbsp;&nbsp;&nbsp;&nbsp;录</button>
                </div>
            </form>
        </div>
    </div>
    <div class="switch-city">{GetG("city/cityname")}<a data-event="choice_city">[切换城市]</a></div>
</div>

<script type="text/javascript" src="/view/js/login.js?v=1"></script>
</body>
</html>