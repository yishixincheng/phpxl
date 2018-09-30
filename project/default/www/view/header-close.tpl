<body>
<div class="g-wrap">
<div class="g-top g-header clearfix">
  <div id="g-logo" class="fl"><a href="http://www.seeteam.cn" target="_blank"> <img src="/static/images/logo1.png" alt="logo"> </a></div>
  <div class="g-citychange fl">
     <a class="g-city-btn">[{GetG('city/cityname')}]</a>
     <a class="change_project" data-event="change_project" style="color:#4c98e6">[切换城市]</a>
  </div>
  <div id="g-login-area" class="fr">
    <div class="g-message fl">{GetG('member/rolename')}&nbsp;&nbsp;&nbsp;&nbsp;{$member['username']}&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ff0000;"><a data-event="revise_psw" class="revise_psw">修改密码</a></span></div>

    <div class="quit fl"><a data-event="unlogin"><i class="page-icon icon-quit"></i></a></div>
  </div>
</div>

<div class="g-main clearfix" id="gu-body">
<div class="g-sidebar fl" id="gu-left">
        
  <div class="g-sidebar-admin clearfix">

     <img src="{if GetG('member/avatar')}{GetG('member/avatar')}{else}/static/images/top.png{/if}" alt="头像" class="fl">
     <div class="g-memberinfo fl">  
        <span class="admin-name">{GetG('member/rolename')}</span>
        <span>{$member['username']}</span>
     </div>
  </div>
  <div class="g-sidebar-menu">
    <ul class="menu-main"> 
    
      {loop $acnavs $an}
         
        <li class="treeview tr0{$an['id']}"> 
            <span class="box-color bg{$an['id']}"></span> 
            <a {if $an['url']} href="{$an['url']}" {if $an['target']}target="{$an['target']}" {/if} {/if}  class="navter {if $an['curr']==1}active{/if}">
              <i class="icon-{$an['ico']}"></i> {$an['title']} <i class="icon-arrow"></i>
            </a>
            <ul class="treeview-menu" {if $an['curr']==1}style="display:block;"{/if}>
              {loop $an['subnav'] $sn}

                 <li>
                      {if $sn['subnavs']}
                          <a href="{$sn['url']}" class="{if $sn['curr']==1}current{/if}">{$sn['title']}</a>
                          <dl>
                              {loop $sn['subnavs'] $subn}
                                  <dd><a {if $subn['target']} target="{$subn['target']}" {/if} {if $subn['event']} data-event="{$subn['event']}" {/if} href="{$subn['url']}">{$subn['title']}</a></dd>
                              {/loop}
                          </dl>
                      {else}
                          <a  {if $sn['target']} target="{$sn['target']}" {/if} {if $sn['event']} data-event="{$sn['event']}" {/if} {if $sn['url']}href="{$sn['url']}"{/if}   class="{if $sn['curr']==1}current{/if}">{$sn['title']}</a>
                      {/if}

                 </li>
              {/loop}
            </ul>
        </li>
         
         
      {/loop}
    
    </ul>
  </div>
</div>

<div class="g-content fr" id="gu-right">

  
  <div class="title">
     {loop $acpgds $ag}
         <a href="{$ag['url']}" {if $ag['curr']==1}class="curr"{/if}>{$ag['title']}</a>
         {if $ag['end']!=1}
         &gt;
         {/if}
     {/loop}
  </div>  