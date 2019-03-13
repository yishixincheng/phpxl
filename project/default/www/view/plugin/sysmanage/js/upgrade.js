!function(){

    //升级软件
    new Xl.Class({

        _isloading:false,
        init:function () {

            this.addEvent();
        },
        addEvent:function(){

            this.addProxyEvent("checkversion",this.event_checkVersion);
            this.addProxyEvent("downloadsoft",this.event_downLoadSoft);
            this.addProxyEvent("installsoft",this.event_installSoft);
            this.registProxyEvent("#thispage_eventproxy");

        },
        event_checkVersion:function(tid,pid){

            var __t=this;
            Xl.request("/sysmanage/upgrade/checkversion",{},function(d,isok){

                if(isok){

                    if(d.tiptype==1){

                        __t.createUpgradePage(d);

                    }else{
                        Xl.alert("已是最新版本，无须升级！");
                    }

                }else{
                    Xl.alert(d.msg||"接口错误","error");
                }

            });

        },
        createUpgradePage:function (data) {
            $(".version_no").html("最新版本："+data.versioninfo||"");
            $(".version_check").html('<a data-event="downloadsoft">点击下载最新版本</a>');
            this.data_softsize=data.softsize||0;
        },
        event_downLoadSoft:function (tid,pid) {

            if(this._isloading){
                Xl.alert("正在下载中，请稍后...");
                return;
            }
            $(".upgrade_title").html("正在下载安装，请稍后...");
            this._isloading=true;
            this.downloadSoft(1);


        },
        downloadSoft:function(step){
            var __t=this;

            Xl.request("/sysmanage/upgrade/downloadsoft",{stepex:step,rand:Math.random()},function(d,isok){
                if(isok){
                    if(d.isover=="1"){
                        __t.downloadOver();
                    }else{
                        __t.downloadProgress(d);
                        step++;
                        __t.downloadSoft(step); //递归调用
                    }
                }else{
                    Xl.alert(d.msg||"下载错误","error");
                }
            },0);

        },
        downloadOver:function(){
            $(".upgrade_progressbar").css({width:"100%"});
            $(".upgrade_title").html("下载完毕，请继续安装");

            $(".version_check").html('<a data-event="installsoft">点击安装</a>');

        },
        downloadProgress:function(d){

            var fseek=d.fseek; //偏移
            var html=['<div class="upgrade_progressbarwrap"><div class="upgrade_progressbar"></div></div>'];
            $(".upgrade_progressbarbox").html(html.join(''));
            var pt=(fseek/this.data_softsize)*100;
            if(pt>100){
                pt=100;
            }
            $(".upgrade_progressbar").css({width:pt+"%"});

        },
        event_installSoft:function(tid,pid){

            //安软软件包
            if(this._isinstalling){
                Xl.alert("正在安装，请稍后...");
                return;
            }

            $(".upgrade_info").show();
            $(".upgrade_progressbarbox").hide();

            $(".upgrade_title").html("正在解压文件，请稍后...");

            this._isinstalling=true;

            this.installSoft(1);

        },
        installSoft:function(step){

            var __t=this;

            Xl.request("/sysmanage/upgrade/installsoft",{step:step,rand:Math.random()},function(d,isok){
                if(isok){
                    if(step==1){
                        if(d.isover=="1"){
                            __t.unzipOver(d);
                            __t.installSoft(2);
                        }
                    }else{

                        if(d.isover=="1"){
                            __t.installOver();
                        }

                    }

                }else{
                    Xl.alert(d.msg||"安装错误","error");
                }
            },0);

            if(step==2){
                $(".upgrade_progressbarbox").hide();
                $(".upgrade_info").show();
                this.startFetchIntallLog();
            }

        },
        unzipOver:function(d){

            $(".upgrade_title").html("解压完毕，正在拷贝文件,请稍后...");
        },
        installOver:function(){
            this._isinstallover=true;
            $(".upgrade_title").html("安装完毕");
        },
        startFetchIntallLog:function(){

            if(this._isinstallover){
                return;
            }

            var __t=this;
            Xl.request("/sysmanage/upgrade/readinstalllog",{rand:Math.random()},function(d,isok){

                if(isok){
                    __t.showInstallLog(d);
                    __t.startFetchIntallLog();
                }

            },0)

        },
        showInstallLog:function(d){

            var html='';
            Xl.forIn(d,function(i,v){
                html+='<p>'+v+'</p>';
            });

            $(".upgrade_info").html(html);

        }


    });

}();