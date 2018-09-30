

(function(){
	"use strict";
 

    function Switch_org(p){
        Xl.inherit(this,Xl.Event);

       // this.isswitch=p.isswitch;
        //this._callback=p.callback;
        this.init=function(){
            this.createDlg();
        };
        this.createDlg=function(){
            var __t = this;
            var tplhtml = this.getContainerHtm();
            Xl.dlg({
                creator: this, //创建者
                getDlgObj: function () {
                    this.param.isOpenMove = true;
                    this.param.moveType = 1; //1,2,3,4
                    __t.mdlg = this;
                },
                className: 'citylistdlg',
                title: '切换岗位',
                width: 400,
                htmlContent: tplhtml,
                height: 300,
                closeCallback: function () {

                },
                afterCall: function (mdlg) {
                    this._init();
                    this.mdlg.resizeWindow();
                }

            });
        };
        this.getContainerHtm=function(){
            return ['<div class="dcom-org-box"></div>'].join('');

        };
        this._init=function(){
            this.wrapdom = $(".dcom-org-box").get(0);
            this.createFormsetView();

            this.addEvent();
        };
        this.createFormsetView=function() {
            var __t=this;

            Xl.request(Xl.GU("/getmyroles"),{uid:$_M.uid},function(d,isok){
                if(isok){
                        var html="<div class='current_info'>\
                        <span class='org_title'>当前岗位&emsp;&emsp;</span>\
                        <span class='org_name'>"+d.curr.orgname+"-"+d.curr.rolename+"</span></div>\
                        <div class='main_info'>\
                        <span class='org_title'>主岗名称&emsp;&emsp;</span>\
                        <ul class='org_name'>\
                        <li data-event='checkbox_select' data-roleid='"+d.main.roleid+"' data-atorg='"+d.main.atorg+"'>"+d.main.orgname+"-"+d.main.rolename+"</li>\
                        </ul></div>\
                        <div class='other_info'>\
                        <span class='org_title'>兼职岗位&emsp;&emsp;</span>\
                        <ul class='org_name'></ul></div>";

                        $(".dcom-org-box").append(html);

                        Xl.forIn(d.jianzhi,function(i,v){
                            $(__t.wrapdom).find(".other_info ul").append("<li data-event='checkbox_select' data-roleid='"+v.roleid+"' data-atorg='"+v.atorg+"'>"+v.orgname+"-"+v.rolename+"</li>");
                        });
 

                }else{

                }
            });

        };


        this.addEvent=function(){
            var __t=this;
            this.addProxyEvent("checkbox_select",this.e_checkbox_select);
            this.registProxyEvent(".dcom-org-box");

        };
        this.e_checkbox_select=function(tid,pid){
            $(".dcom-org-box").find("li").removeClass("active");
            $(tid).addClass("active");

            var atorg=Xl.sgData($(tid),"atorg");
            var roleid=Xl.sgData($(tid),"roleid");
            Xl.request(Xl.GU("/setmycurrrole"),{atorg:atorg,roleid:roleid},function(d,isok){
                if(isok){
                  Xl.alert("恭喜您，切换岗位成功！系统正为您刷新...","right");
                  
                  window.setTimeout(function(){

                      window.location.reload();

                  },1000);

                }else{

                    Xl.alert(d.msg||"切换失败","error");

                }
            });



        };
        this.init();

    }



    new Xl.Class({
        outinterface: ['open'], /*对外结构*/
        init: function () {
            Xl.Dcom.addCom("sys/switchorg", this);//注册组建
        },
        callouti: function (oiname, param) {
            //调用接口,必须函数
            this.iswait = false;
            if (!Xl.inArray(oiname, this.outinterface)) {
                alert("调用接口不存在！");
                return;
            }
            if (Xl.isFunction(this['outi_' + oiname])) {
                this['outi_' + oiname](param || '');//调用接口
            } else {
                alert("调用接口没实现");
            }
        },
        outi_open: function (p) {
            new Switch_org(p);
        }
    });
})();