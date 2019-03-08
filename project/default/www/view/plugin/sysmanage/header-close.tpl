




</head>
<body>


<div class="wrap">


    <div class="headerbar" id="g_headerbar">
        <h3>系统管理后台</h3>
    </div>

    <div class="mainarea" id="g_mainarea">

        <div class="navbox" id="g_navbox">

            <div class="nav">

                <ul>

                    {loop $navs $nav}

                        <li {if $currnav==$nav['page']}class="curr"{/if}><a href="{$nav['url']}">{$nav['name']}</a></li>

                    {/loop}

                </ul>

            </div>


        </div>

        <div class="crumbsnav">&nbsp;</div>

        <div class="contentbox" id="g_contentbox">

