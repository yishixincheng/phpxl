// JavaScript Document

(function(){

	"use strict";

	var Ajax_ProgressBar_Lock=false;

	var __T=function(p){

        var url=p.u;
        var data=p.d;
        var dataType=p.dt;
        var type=p.t;
        var success=p.s;
        var async=p.a;
        var style=p.st;//1,顶层加载进度条，dom对象是绑定对象上加载进度条，样式自行修改
        var callbackhook=p.callbackhook;
        var objhook=p.objhook;

        if(Xl.isFunction(dataType)){
            //顺序,url,data,success,style,datatype,type,asnc
            var _success=dataType;
            var _async=(Xl.isUndefined(style)||style===null)?true:style;
            var _style=/^\d+$/g.test(type)?parseInt(type):(type||1);

            var _dataType=success||'json';
            var _type=async||'post';
            success=_success;
            async=_async;
            style=_style;
            dataType=_dataType;
            type=_type;
        }
        type=type||'get';

        if($.inArray(type,['post','get'])!=-1){
            this.Ajax(url,data,dataType,type,success,async,style,callbackhook,objhook);
        }

	};

	__T.prototype={
        Ajax:function(url,data,dataType,type,success,async,style,callbackhook,objhook){

        	var __t=this;
            data=data||{};
            if(Xl.isUndefined(data.FORMHASH)){
                if(!Xl.isUndefined(window.$_FORMHASH)){
                    data.FORMHASH=window.$_FORMHASH;
                }
            }
            if(Xl.isUndefined(data.uid)){
                if(!Xl.isEmpty(window.$_M.uid)){
                    data.uid=window.$_M.uid;
                }
            }
            if(Xl.isUndefined(data.sessionkey)){
                if(!Xl.isEmpty(window.$_sessionkey)){
                    data.sessionkey=window.$_sessionkey;
                }
            }

            if(Xl.isUndefined(data.citycode)){
                if(window.$_C.citycode){
                    data.citycode=window.$_C.citycode;
                }
            }

            var ajaxObj=Xl.Ajax(url,data,dataType,type,function(d){
                var isright=true;
                if(dataType=="json"){
                    var response=d['response'];
                    var result=d['result'];
                    if(response=="fail"){
                        isright=false;
                    }
                }else{
                    var result=d;
                }
                var rt=true;
                if(Xl.isFunction(callbackhook)){
                    rt=callbackhook(result,isright);
                }
                if(Xl.isFunction(Xl.ajaxHook)){
                    //钩子
                    rt=Xl.ajaxHook(result,isright);
                }
                if(rt===false){
                    return;
                }
                if(Xl.isFunction(success)){
                    success(result,isright);
                }


            },async,function(){
                //beforajax
                if(style===1){
                    __t.createProcessBar();
                }else if(style!=0){
                    __t.showLoading(style);
                }
            },function(){
                //completeajax
                if(style===1){
                    __t.endProcessBar();
                }else if(style!=0){
                    __t.hideLoading(style);
                }
            });

            if(Xl.isFunction(objhook)){
                objhook(ajaxObj);
            }
            Xl.ajaxObj=ajaxObj;

        },
        createProcessBar:function(){

            if(Ajax_ProgressBar_Lock){
                return;
            }

            Ajax_ProgressBar_Lock=true; //上锁
            var dombar=Xl.E("dcom_ajax_processbar");
            if(dombar){
                return;
            }
            Xl.addDivToBody("dcom_ajax_proccessbar");
            this.moveProcessBar();
        },
        moveProcessBar:function(){
            $("#dcom_ajax_proccessbar").animate({width:'90%'},200,function(){
                $(this).animate({width:'98%'},5000);
            });
        },
        endProcessBar:function(){
            $("#dcom_ajax_proccessbar").stop().animate({width:'100%'},100,function(){
                $(this).unbind();
                $(this).remove();
                Ajax_ProgressBar_Lock=false; //解锁
            });

        },
        showLoading:function(style){

            this.loadComplate=false;
            this.loadingLimer=window.setTimeout(function(){

                if(!this.loadComplate){
                    var bdom=$(style).get(0);
                    if(!bdom){
                        bdom=Xl.E(style);
                    }
                    var bindDom=document.documentElement;
                    bindDom=bdom||bindDom;
                    var boxW=$(bindDom).width();
                    var boxH=$(bindDom).height();
                    var boxOft=$(bindDom).offset();
                    var view=Xl.getViewSize();
                    var dombar=Xl.E("dcom_ajax_loading");
                    if(!dombar){
                        dombar=Xl.addDivToBody("dcom_ajax_loading");
                        dombar.innerHTML='<div class="dcom_ajax_loading-bounce1"></div><div class="dcom_ajax_loading-bounce2"></div>';
                    }
                    var loadWidth=$(dombar).width();
                    var loadHeight=$(dombar).height();
                    var left=0,top=0;
                    if(style==2){
                        //
                        left=(boxW-loadWidth)/2;
                        top=(view.clientHeight-loadHeight)/2+view.scrollTop;
                    }else{
                        left=(boxW-loadWidth)/2+boxOft.left;
                        top=(boxH-loadHeight)/2+boxOft.top;
                    }

                    $(dombar).offset({left:left,top:top});
                    window.clearTimeout(this.loadingLimer);
                }

            },500);

        },
        hideLoading:function(style){
            this.loadComplate=true;
            var dombar=Xl.E("dcom_ajax_loading");
            if(dombar){
                $(dombar).remove();
            }

        },
        distroy:function(){

            //卸载时释放内存

        }
	};


	var __t={
		outinterface:['open'], /*对外结构*/
		loadingLimer:null,
		loadComplate:false,
		init:function(){
			Xl.Dcom.addCom("sys/request",this);//注册组建
		},
		callouti:function(oiname,param){
			//调用接口,必须函数
			__t.iswait=false;
			if($.inArray(oiname,__t.outinterface)==-1){
				alert("调用接口不存在");
				return;
			}
			if($.isFunction(__t['outi_'+oiname])){
				__t['outi_'+oiname](param||'');//调用接口
			}else{
				alert("调用接口没实现");
			}
		},
        outi_init:function () {

        },
		outi_open:function(p){

			new __T(p);

		}

	};
	__t.init();

})();