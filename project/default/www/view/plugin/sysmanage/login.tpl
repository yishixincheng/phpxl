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
            HOST:'{HOST}'
        };
        var $_M={
            uid:"",
            nickname:"",
            avatar:"",
            roleid:"",
            rolename:""
        };
        var $_C={
            cityname:"{GetG('city/cityname')}",
            citycode:"{GetG('city/citycode')}",
            cityflag:"{GetG('city/cityflag')}"
        };
        var $_G={
            siteurl:"",
            avatar:"",
            citycode:"",
            cityname:""
        };
        var $_FORMHASH='{FORMHASH}';
        var $_sessionkey="";
        var $_PlugPath="{PLUGIN_PATH}";

    </script>

    <link rel="stylesheet" type="text/css" href="{:cssrootpath}global.css">

    <link rel="stylesheet" type="text/css" href="/view/plugin/sysmanage/css/login.css">

</head>
<body>


<div class="wrap">
    <div class="login-bg">
    </div>

    <div class="content">

        <div class="login-form" id="Id_login_wap">
            <form onSubmit="return false;">
                <p>系统管理后台登录</p>
                <div class="username"><i class="page-icon icon-username"></i>
                    <input value="" id="Id_username" data-bind="value:username" type="text" placeholder="用户名">
                </div>
                <div class="password"><i class="page-icon icon-password"></i>
                    <input value="" id="Id_password" data-bind="value:password" type="password" placeholder="登录密码">
                </div>
                <div class="checkcodebox" id="Id_checkcode_box"><i class="page-icon icon-password"></i>
                    <input value="" data-bind="value:checkcode" type="text" placeholder="输入验证码">
                    <span id="Id_checkcode_img"></span>
                </div>
                <div class="login-btn">
                    <button data-event="submit">登&nbsp;&nbsp;&nbsp;&nbsp;录</button>
                </div>
                <div style="height: 20px">&nbsp;</div>
            </form>
        </div>

    </div>

</div>

<script type="text/javascript" src="/view/plugin/sysmanage/js/login.js?v=1"></script>
</body>
</html>