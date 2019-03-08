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
            uid:"{GetG("member/uid")}",
            nickname:"{GetG("member/username")}",
            roleid:"1",
            rolename:"系统管理员"
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

    <link rel="stylesheet" type="text/css" href="/view/plugin/sysmanage/css/public.css">