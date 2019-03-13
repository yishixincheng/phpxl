!function(){


    new Xl.Class({

        init:function(){

            this.addEvent();

        },
        addEvent:function(){

            this.addProxyEvent("download",this.event_downLoad);
            this.addProxyEvent("upgrade",this.event_upgrade);
            this.addProxyEvent("install",this.event_install);
            this.addProxyEvent("open",this.event_open);
            this.addProxyEvent("close",this.event_close);
            this.registProxyEvent(".pbox_wrap");

        },
        event_downLoad:function(tid,pid){

            var $parent=$(tid).parent();
            $parent.html("<div class='_progressbarbox'><div class='_progressbar'></div></div>");
            var parent=$parent.get(0);

            var plugintype=Xl.sgData(parent,"plugintype");
            var softversion=Xl.sgData(parent,"softversion");
            var currsoftversion=Xl.sgData(parent,"currsoftversion");
            var version=Xl.sgData(parent,"version");
            var lastversion=Xl.sgData(parent,"lastversion");
            var downloadurl=Xl.sgData(parent,"downloadurl");

            var data={
                plugintype:plugintype,
                softversion:softversion,
                currsoftversion:currsoftversion,
                version:version,
                lastversion:lastversion,
                downloadurl:downloadurl
            };

            this.downLoadSoft(data,1,1,parent);

        },
        event_upgrade:function(tid,pid){

            var $parent=$(tid).parent();
            $parent.html("<div class='_progressbarbox'><div class='_progressbar'></div></div>");
            var parent=$parent.get(0);

            var plugintype=Xl.sgData(parent,"plugintype");
            var softversion=Xl.sgData(parent,"softversion");
            var currsoftversion=Xl.sgData(parent,"currsoftversion");
            var version=Xl.sgData(parent,"version");
            var lastversion=Xl.sgData(parent,"lastversion");
            var downloadurl=Xl.sgData(parent,"downloadurl");

            var data={
                plugintype:plugintype,
                softversion:softversion,
                currsoftversion:currsoftversion,
                version:version,
                lastversion:lastversion,
                downloadurl:downloadurl
            };

            this.downLoadSoft(data,2,1,parent);

        },
        downLoadSoft:function(data,type,step,dom){

            var __t=this;
            data.step=step;
            Xl.request("/sysmanage/upgrade/downloadsoftplugin",data,function(d,isok){
                if(isok){
                    if(d.isover=="1"){
                        __t.downloadOver(dom);
                    }else{
                        __t.downloadProgress(d,dom);
                        step++;
                        __t.downLoadSoft(data,type,step,dom); //递归调用
                    }
                }else{
                    Xl.alert(d.msg||"下载错误","error");
                }
            },0);
        },
        downloadProgress:function(d,dom){

            var __t=this;
            $(dom).find("._progressbar").animate({width:"100%"},100,function(){

                $(this).css({width:"0%"});
                __t.downloadProgress(d,dom);
            })

        },
        downloadOver:function(dom){

            $(dom).html("<a data-event='install'>安装</a>");
        },
        event_install:function(tid,pid){

            var $parent=$(tid).parent();
            $parent.html("<span>安装中...</span>");
            var parent=$parent.get(0);

            var plugintype=Xl.sgData(parent,"plugintype");
            var lastversion=Xl.sgData(parent,"lastversion");
            var pluginname=Xl.sgData(parent,"pluginname");

            var data={
                plugintype:plugintype,
                pluginname:pluginname,
                version:lastversion
            };

            Xl.request("/sysmanage/upgrade/installplugin",data,function(d,isok){
                if(isok){
                    if(d.isover=="1"){
                        $parent.html("安装完成")
                    }
                }else{
                    Xl.alert(d.msg||"安装错误","error");
                }
            },0);

        },
        event_open:function(tid,pid){

            var $parent=$(tid).parent();
            var parent=$parent.get(0);
            var plugintype=Xl.sgData(parent,"plugintype");

            Xl.request("/sysmanage/pluginmanage/opencloseplugin",{plugintype:plugintype,open:1},function (d,isok) {

                if(isok){
                    Xl.alert("操作成功！","right");
                    $parent.html('<a data-event="close">点击关闭</a>');
                    $parent.parent().find(".c4").html("开启");
                }else{
                    Xl.alert(d.msg||"操作失败","error")
                }

            });

        },
        event_close:function (tid,pid) {

            var $parent=$(tid).parent();
            var parent=$parent.get(0);
            var plugintype=Xl.sgData(parent,"plugintype");

            Xl.request("/sysmanage/pluginmanage/opencloseplugin",{plugintype:plugintype,open:0},function (d,isok) {

                 if(isok){
                     Xl.alert("操作成功！","right");
                     $parent.html('<a data-event="open">点击开启</a>');
                     $parent.parent().find(".c4").html("关闭");
                 }else{
                     Xl.alert(d.msg||"操作失败","error")
                 }

            });

        }

    });


}();