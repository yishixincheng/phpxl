// JavaScript Document
(function(){
	"use strict";
    var __t={
	  	 outinterface: ['open'],
		 init:function(){
			 Xl.extend(this, Xl.Event);
			 Xl.Dcom.addCom("sys/confirm", this);// 注册组建
		 },
		 callouti: function(oiname, param){
			// 调用接口,必须函数
			this.iswait=false;
			if(!Xl.inArray(oiname, this.outinterface)){
				alert("调用接口不存在");
				return;
			}
			if(Xl.isFunction(this['outi_'+oiname])){
				this['outi_'+oiname](param || '');//调用接口
			}else{
				alert("调用接口没实现");
			}
		 },
		 outi_open: function(p){
			 this._tip=p.tip || "确定操作吗？";
			 this._callback=p.callback || null;
			 this._cancelCallback=p.cancelCallback || null;
			 this._confirmtitle=p.confirmtitle || '确定';
			 this._canceltitle=p.canceltitle || "取消";
			 this.createConfirm(p);// 创建对话框
		 },
		 createLayerbg: function(){
			// 创建遮罩
			 
			 var dom=Xl.E("dcom-sys-confirm-graybg");
			 if(dom){return;}
			 dom=Xl.addDivToBody("dcom-sys-confirm-graybg");
			 dom.className="dcom-sys-confirm-graybg g-graybg";
			 
		 },
		 removeLayerbg: function(){
			 
			 var dom=Xl.E("dcom-sys-confirm-graybg");
			 if(dom){$(dom).remove();}
		 },
		 createConfirm: function(p){
			  var __this=this;
			  __this._callback=p.callback || null;
			  __this._tip=p.tip || "确定？";
			  __this._cancelCallback=p.cancelCallback || null;
			  var A=[];
			  __t.createLayerbg();
			  A.push('<div class="dcom-sys-confirm-modalbg"></div>');
			  A.push('<div class="dcom-sys-confirm-body" id="dcom-sys-confirm-body">');
			  A.push('<div class="dcom-sys-confirm-container" id="dcom-sys-confirm-container">');
			  A.push('<span class="dcom-sys-confirm-tip">'+__this._tip+'</span>');
              A.push('<div class="clearfix"><div style="width:180px;margin: 0px auto;"><button class="dcom-sys-confirm-okbutton" data-event="confirm" href="javascript:;" >'+this._confirmtitle+'</button>');
              A.push('<button class="dcom-sys-confirm-cancelbutton" data-event="cancel" href="javascript:;" >'+this._canceltitle+'</button></div></div>');
			  A.push('</div>');
			  A.push('<div class="dcom-sys-confirm-layerbg g-layerbg" id="dcom-sys-confirm-layerbg'+'"></div>');		   
			  A.push('</div>');
			  A.push('</div>'); 
			  __this.dlg=Xl.addDivToBody("div");
			  __this.dlg.className="dcom-sys-confirm";
			  __this.dlg.innerHTML=A.join('');
			  Xl.centerWindow(Xl.E("dcom-sys-confirm-body"),$("#dcom-sys-confirm-body").width(),$("#dcom-sys-confirm-body").height());
			  this.addEvent();
		 },
		 addEvent: function(){
			 this.addProxyEvent("confirm",this.confirmCallback);
			 this.addProxyEvent("cancel",this.cancelCallback);
			 this.registProxyEvent("dcom-sys-confirm-body");
			 Xl.registGlobalEvent();
			 var _t=this;
			 Xl.setG("Event/rootkeydownFunc>_sys_confirm", function(e){
				 // 快捷键
				 if(e.keyCode==27){
					 _t.cancelCallback();
					 return false;
				 }
				 if(e.keyCode==13){
					 _t.confirmCallback();
					 return false;
				 }
			});
		 },
		 removeWindow: function(){
			 this.destroy();
			 this.removeLayerbg();
			 $(this.dlg).remove();
			 Xl.setG("Event/rootkeydownFunc>_sys_confirm", null);
		 },
		 confirmCallback: function(){
			 this.removeWindow(); 
			 if(Xl.isFunction(this._callback)){
				 this._callback();
			 }
		 },
		 cancelCallback: function(){
			 this.removeWindow(); 
			 if(Xl.isFunction(this._cancelCallback)){
				 this._cancelCallback();
			 }
		 }/*最后一个对象不要加逗号，否则ie7报错*/
	};
	__t.init();
})();