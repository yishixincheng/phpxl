;!(function(){
    var __t={
        outinterface:['open'], /*对外结构*/
        init:function(){
            Xl.extend(this,Xl.Event);
            Xl.Dcom.addCom("sys/tip",this);//注册组建
        },
        callouti:function(oiname,param){
            //调用接口,必须函数
            if(!Xl.inArray(oiname,this.outinterface)){
                alert("调用接口不存在","error");
                return;
            }
            if(Xl.isFunction(this['outi_'+oiname])){
                this['outi_'+oiname](param||'');//调用接口
            }else{
                alert("调用接口没实现");
            }
        },
        outi_open:function(p){
            this._cancelFlag = p.cancelFlag||false;
            this._cancelCallback=p.cancelCallback||null;
            this._okCallback=p.okCallback||null;
            this._className=p.className||'';
            this._title=p.title||'';
            this._text = p.text||'';
            this._oktext=p.oktext||'确定';
            this._canceltext=p.canceltext||"取消";
            this.createListView(p);//创建对话框
        },
        createLayerbg:function(){
            // 创建遮罩
            var dom=Xl.E("dcom-sys-tipsnoborder-graybg");
            if(dom){
                return;
            }
            dom=Xl.addDivToBody("dcom-sys-tipsnoborder-graybg");
            dom.className="dcom-sys-tipsnoborder-graybg g-graybg";
        },
        removeLayerbg:function(){
            var dom=Xl.E("dcom-sys-tipsnoborder-graybg");
            if(dom){$(dom).remove();}
        },
        createListView:function(p){
            var __this=this;
            var A=[];
            __this.createLayerbg();
            A.push('<div class="dcom-sys-tipsnoborder-modalbg"></div>');
            A.push('<div class="dcom-sys-tipsnoborder-body '+this._className+'" id="dcom-sys-tipsnoborder-body">');
            A.push('<div class="dcom-sys-dlg-title" id="dcom-sys-tipsnoborder-title">');
            A.push('<i class="dcom-sys-dlg-ico"></i>');
            A.push('<h3>'+__this._title+'</h3>');
            A.push('<div class="dcom-sys-dlg-title-attach"></div>');
            A.push('</div>');
            A.push('<div class="dcom-sys-tipsnoborder-container" id="dcom-sys-tipsnoborder-container">');
            A.push('<span class="dcom-sys-tipsnoborder-span">'+__this._text+'</span>');
            A.push('<a class="dcom-sys-tipsnoborder-ok" data-event="ok">'+__this._oktext+'</a>');
            if(this._cancelFlag){
                A.push('<a class="dcom-sys-tipsnoborder-cancel" data-event="cancel">'+__this._canceltext+'</a>');
            }
            A.push('<div class="dcom-sys-tipsnoborder-layerbg g-layerbg" id="dcom-sys-tipsnoborder-layerbg'+'"></div>');
            A.push('</div>');
            A.push('<a class="dcom-sys-dlg-close g-layerclose" id="dcom-sys-dlg-close">x</a>');
            A.push('</div>');
            __this.dlg=Xl.addDivToBody("div");
            __this.dlg.className="dcom-sys-tipsnoborder";
            __this.dlg.innerHTML=A.join('');
            __this.createListContent();


            Xl.centerWindow(Xl.E("dcom-sys-tipsnoborder-body"),$("#dcom-sys-tipsnoborder-body").width(),$("#dcom-sys-tipsnoborder-body").height());
            __this.addEvent();
            $("#dcom-sys-dlg-close").click(function(e) {
                __this.removeWindow();
            });

        },
        createListContent:function(){
        },
        addEvent:function(){
            this.addProxyEvent("ok",this.okCallback);
            if(this._cancelFlag){
                this.addProxyEvent("cancel",this.cancelCallback);
            }
            this.registProxyEvent("dcom-sys-tipsnoborder-body");
        },
        okCallback:function(){
            this.removeWindow();
            if(Xl.isFunction(this._okCallback)){
                this._okCallback();
            }
        },
        removeWindow:function(){
            this.destroy();
            this.removeLayerbg();
            $(this.dlg).remove();
        },
        cancelCallback:function(){
            this.removeWindow();
            if(Xl.isFunction(this._cancelCallback)){
                this._cancelCallback();
            }
        }

    };
    __t.init();
})();