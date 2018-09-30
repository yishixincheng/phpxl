{php $member=GetG('member');$Config=GetG("Global/Config");$urls=$Config['urls'];}
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="{CHARSET}">
    <title>管理中心-{$Title}</title>
    <meta name="keywords" content="{config('system/seo_keyword')}">
    <meta name="description" content="{config('system/seo_dis')}">
    <meta name="technicalsupport" content="{config("system/seo_authorname")}">
    <meta name="company" content="{config("system/seo_company")}">
    <meta name="system-development-author" content="{config('system/auth')}">
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
            uid:"{$member['uid']}",
            username:"{$member['username']}",
            truename:"{$member['truename']}",
            avatar:"{$member['avatar']}",
            roleid:"{$member['roleid']}",
        };
        var $_C={
            cityname:"{GetG('city/cityname')}",
            citycode:"{GetG('city/citycode')}",
        };
        var $_G={
            siteurl:"{$Config['site_url']}",
            avatar:"{$Config['avatar_src']}",
            defaultimg:"{$Config['defaultimg']}"
        };
        var $_MulPriv='{$mulpriv}';
        var $_FORMHASH='{FORMHASH}';
        var $_sessionkey="{GetG('member/sessionkey')}";
        var $_DataSetVersion="{$Config['datasetversion']}";        

    </script>

    <link rel="stylesheet" type="text/css" href="{:cssrootpath}global.css">
    <link rel="stylesheet" type="text/css" href="/view/css/public.css">


