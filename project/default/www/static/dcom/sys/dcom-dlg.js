// JavaScript Document

(function(){
	
	"use strict";
	
	var __t={
	  	 outinterface:['open'], /*对外结构*/
		 dlgs:[], //组建所创建的dlgs数组
		 init:function(){
			 Xl.Dcom.addCom("sys/dlg",this);//注册组建
		 },
		 callouti:function(oiname,param){
			 	
			    // alert(oiname);
				//调用接口,必须函数
				if($.inArray(oiname,__t.outinterface)==-1){
					alert("调用接口不存在","error");
					return;
				}
				if($.isFunction(__t['outi_'+oiname])){
					__t['outi_'+oiname](param||'');//调用接口
				}else{
					alert("调用接口没实现");
				}
		 },
		 lockDlg:function(creator){

			 if(creator&&creator!=this){
				 if(creator.__dcom_dlg_islock){
				 	return true;
				 }else{
				 	return false;
				 }
				 creator.__dcom_dlg_islock=true; //锁定
			 }
			 return false;
		 },
		 unLockDlg:function(creator){
			 if(creator&&creator!=this){
				 creator.__dcom_dlg_islock=false; //锁定
			 }
		 },
		 outi_open:function(p){

		 	 //防止多次弹出对话框，增加锁定功能
			 var creator=p.creator||this;
			 if(__t.lockDlg(creator)){
			 	 return;
			 }
			 var getDlgObj=p.getDlgObj||null; //获得接口
			 var afterCall=p.afterCall||null;
			 var dlg=new __t.dlg(p); //创建对话框
			 
			 Xl.setG("dlg/currActDlg",dlg); //当前活跃对象
			 
			 Xl.pushG("dlg/list",dlg); //压入数据
			 if(Xl.isFunction(getDlgObj)){
				 getDlgObj.call(dlg,dlg); //获得接口对象
			 }
			 if(Xl.isFunction(afterCall)){
				 afterCall.call(creator,dlg);
			 }
			
		 },
		 createLayerbg:function(__this){
			 //创建遮罩
			 if(__t.dlgs.length!==0){return;}
			 var dom=Xl.E("dcom-sys-dlg-graybg");
			 if(dom){return;}
			 dom=Xl.addDivToBody("dcom-sys-dlg-graybg");
			 dom.className="dcom-sys-dlg-graybg g-graybg";

			 if(__this.param.zIndex){
				 dom.style.zIndex=__this.param.zIndex;
			 }

		 },
		 removeLayerbg:function(){
			 if(Xl._getGArr("dlg/list").length!==0){
				 return;
			 }
			 var dom=Xl.E("dcom-sys-dlg-graybg");
			 if(dom){$(dom).remove();}
		 },
		 getdlgnum:function(){
			 
			 return Xl.getLen(Xl._getGArr("dlg/list"))||0;
		 },
		 dlg:function(p){
			  var __this=this;
			  __this.param={
						 title:p.title||'', //存在时候启用标题栏
						 width:p.width||600,
						 height:p.height||400,
						 skintype:p.skintype||1,
						 src:p.src||'', //存在时启用框架
						 className:p.className||'',//附在外层class上，供用户自定义修改
						 zIndex:p.zIndex||null, //层级
						 htmlContent:p.htmlContent||'', //填充内容
						 disableDrag:p.disableDrag||false, //是否禁止拖动
						 disableGraybg:p.disableGraybg||false,
				         preCloseCallback:p.preCloseCallback||null,
						 closeCallback:p.closeCallback||false,
						 isOpenMove:p.isOpenMove||false,
						 stopLeft: p.stopLeft||null,
						 stopTop: p.stopTop||null,
						 moveType: p.moveType||1,
						 rebDistance: p.rebDistance||20,
						 speed: p.speed||200,
						 easing: p.easing||null,
						 moveStopCallback: p.moveStopCallback||null,
						 ismodal:p.ismodal||false,
						 attachHtml:p.attachHtml||''
						 
			   };
			   Xl.setG("Is/opendlg",true); //记录状态
			   __this.setCloseCallback=function(closeCallback){
					 if(Xl.isFunction(closeCallback)){
						__this.param.closeCallback=closeCallback;
					 }
					 return __this;
			   };
			   __this.init=function(){
				   __this.guid=Xl.getGuid();
				   var A=[];
				   if(!__this.param.disableGraybg){
				   		__t.createLayerbg(__this);
				   }
				   var modalclass='';
				   if(__this.param.ismodal){
					   if(__t.getdlgnum()>0){
					       A.push('<div class="dcom-sys-dlg-modalbg"></div>');
						   modalclass='dcom-sys-modeldlg';
					   }
				   }
				   A.push('<div class="dcom-sys-dlg-body '+__this.param.className+' dlg-skin-'+__this.param.skintype+' '+modalclass+'" id="dcom-sys-dlg-body-'+__this.guid+'">');
				   if(__this.param.title){
					   A.push('<div class="dcom-sys-dlg-title" id="dcom-sys-dlg-title-'+__this.guid+'"><i class="dcom-sys-dlg-ico"></i><h3>'+__this.param.title+'</h3><div class="dcom-sys-dlg-title-attach"></div></div>');
				   }
				   A.push('<div class="dcom-sys-dlg-container" id="dcom-sys-dlg-container-'+__this.guid+'">');
				   
				   if(__this.param.src){
					   A.push('<iframe scrolling="no" frameborder="0" id="dcom-sys-dlg-container-iframe-'+__this.guid+'" src="" allowtransparency="true" ></iframe>');
				   }else{
					   A.push(__this.param.htmlContent);
				   }
				   A.push('</div>');
				   
				   A.push('<div class="dcom-sys-dlg-layerbg g-layerbg" id="dcom-sys-dlg-layerbg-'+__this.guid+'"></div>');
				   
				   A.push('<a href="javascript:;" class="dcom-sys-dlg-close g-layerclose" id="dcom-sys-dlg-close-'+__this.guid+'">×</a>');
				   
				   A.push(__this.param.attachHtml||'');
				   
				   A.push('</div>');
				   
				   A.push('</div>');
				   
				   __this.dlg=Xl.addDivToBody("div");
				   __this.dlg.id="dcom-sys-dlg-"+this.guid;
				   __this.dlg.className="dcom-sys-dlg";
				   __this.dlg.innerHTML=A.join('');

				   if(__this.param.zIndex){
					   $("#dcom-sys-dlg-body-"+__this.guid).css("z-index",__this.param.zIndex+1);
				   }
				   
				   $("#dcom-sys-dlg-close-"+__this.guid).click(function(e) {
                       if ($(e.target).parent().attr('class').indexOf('subway_dlg')>0 || $(e.target).parent().attr('class').indexOf('gongjiao_dlg')>0) {
                           $('.config_dlg').get(0).style.zIndex=19999
                       }
                       if ($(e.target).parent().attr('class').indexOf('selected_city')>0) {
                           $('.NewPostdlg').get(0).style.zIndex=19999
					   }
                       if ($(e.target).parent().attr('class').indexOf('fyprice-dlg')>0) {
                           $('.config_dlg').get(0).style.zIndex=19999
                       }
                       if ($(e.target).parent().attr('class').indexOf('addpeitaodlg')>0) {
                           $('.bus_dlg').get(0).style.zIndex=19999
                       }
                       if ($(e.target).parent().attr('class').indexOf('editrent')>0) {
                           $('.price_dlg').get(0).style.zIndex=19999
                       }
                       __this.closeWindow();
                   });

				   if(__this.param.src){
					   __this.fillFrame();
				   }else{
					   
				       __this.resizeWindow(__this.param.width,__this.param.height);
				   }
				   if(!__this.param.disableDrag){
					   Xl.include("/static/lib/xl.drag.js",function(){
					       
						   new Xl.Drag({
							   target:"#dcom-sys-dlg-body-"+__this.guid,
							   hander:"#dcom-sys-dlg-title-"+__this.guid,
							   isStart:true,
							   limitViewRange:true,
							   callback:function(){
								   
							   }
						   });
					       
					   });
				   }
				   
				   return __this;
			   };
			   __this.fillFrame=function(){
						
					var iframe = Xl.E('dcom-sys-dlg-container-iframe-' + __this.guid);
					if (iframe.attachEvent) {
						iframe.attachEvent("onload", function() {
							__this.reinitIframe(iframe);
						});
					} else {
						iframe.onload = function() {
							__this.reinitIframe(iframe);
						};
					}
					iframe.src=__this.param.src;
			   };
			   __this.setHtmlContent=function(html){
				   
				   $("#dcom-sys-dlg-container-"+__this.guid).html(html);
				   
				   return __this;
			   };
			   __this.setTitleAttachHtml=function(html){

			   	   //设置标题附加内容
				   $(__this.dlg).find(".dcom-sys-dlg-title-attach").html(html);

			   };
			   __this.reinitIframe=function(iframe){
				    iframe=iframe||Xl.E("dcom-sys-dlg-container-iframe-"+__this.guid);
					try {
						var view=Xl.getViewSize();
						var iframec = iframe.contentWindow;
						this.iframeContent = iframec;
						var bHeight = iframec.document.body.scrollHeight;
						var dHeight = iframec.document.documentElement.scrollHeight;
						var bWidth = iframec.document.body.scrollWidth;
						var dWidth = iframec.document.documentElement.scrollWidth;
						var width = Math.max(bWidth,dWidth,view.clientWidth);
						var height = Math.max(bHeight, dHeight);
						if(this.param.width){
							width=this.param.width;
						}
						if(this.param.height){
							height=this.param.height;
						}						
						iframe.height = height;
						iframe.width = width;
						iframe.parentNode.style.width = width + "px";
						$("#dcom-sys-dlg-container-"+__this.guid).height(height).width(width);
						__this.centerWindow();
					} catch (err) {alert(err);}
			   
				 
		   };
		   __this.setBodySize=function(){
			  
				  var dom=$("#dcom-sys-dlg-container-"+__this.guid);
				  var cw=dom.width();
				  var ch=dom.height();
				  var border_width=parseInt(dom.css("border-width"))||0;
				  var cpl=parseInt(dom.css("padding-left"))||0;
				  var cpr=parseInt(dom.css("padding-right"))||0;
				  var cpt=parseInt(dom.css("padding-top"))||0;
				  var cpb=parseInt(dom.css("padding-bottom"))||0;
				  cw+=cpl+cpr+border_width*2;ch+=cpt+cpb+border_width*2;var th=0; //标题高度
				  
				  if(__this.param.title){
					 th=$("#dcom-sys-dlg-title-"+__this.guid).width(cw).height(); //一样的宽
				  }
				  ch+=th;
				  $("#dcom-sys-dlg-body-"+__this.guid).width(cw).height(ch);
				  
				  if(Xl.isIE7){
					  $("#dcom-sys-dlg-layerbg-"+__this.guid).height(ch+10);
				  }
				  return __this;
			  
		   };
		   __this.centerWindow=function(){
			   
			   var d=$("#dcom-sys-dlg-body-"+__this.guid);
			   var w=d.width();
			   var h=d.height();
			   Xl.centerWindow(d.get(0),w,h);
			   return __this;
			   
		   };
		   __this.resizeWindow=function(w,h,force){
			   //w,h存在时，定义外层的宽高
			   //不存在时，根据主体的宽高，计算大小
			   if(!w&&!h){
				  if(!__this.param.src){
                      __this.setBodySize();
				  }else{
                      __this.reinitIframe();
                  }
				  __this.centerWindow();
				  return;
			   }
			   var view=Xl.getViewSize();
			   var dom=$("#dcom-sys-dlg-container-"+__this.guid);
			   var cw=dom.width();
			   var ch=dom.height();
			   var cpl=parseInt(dom.css("padding-left"))||0;
			   var cpr=parseInt(dom.css("padding-right"))||0;
			   var cpt=parseInt(dom.css("padding-top"))||0;
			   var cpb=parseInt(dom.css("padding-bottom"))||0;
			   cw+=cpl+cpr;ch+=cpt+cpb;var th=0; //标题高度
			   if(w){
				   if(!Xl.isIE7){
				     w=Math.max(w,cw);
				   }
				  w=Math.min(w,view.clientWidth);
				  $("#dcom-sys-dlg-body-"+__this.guid).width(w);
				  if(__this.param.title){
					 $("#dcom-sys-dlg-title-"+__this.guid).width(w);
				  }
			  }
			  if(h){
				  if(__this.param.title){
					 th=$("#dcom-sys-dlg-title-"+__this.guid).height(); //一样的宽
				  }
				  if(!force){
					  h=Math.max(ch+th,h);
				  }
				  $("#dcom-sys-dlg-body-"+__this.guid).height(h);
				  var minch=h-th;
				  $("#dcom-sys-dlg-container-"+__this.guid).css("min-height",minch+"px");
				  if(Xl.isIE7){
					  $("#dcom-sys-dlg-layerbg-"+__this.guid).height(h+10);
				  }
			  }
			  
			  __this.stopWindow();

			  return __this;
			   
		   };
		   __this.stopWindow = function(sz) {
				sz = sz || Xl.getViewSize();
				var p = __this.param;
				if (p.isOpenMove) {
					var boxdom = Xl.E("dcom-sys-dlg-body-" + __this.guid);
					var boxwidth = $(boxdom).width();
					var boxheight = $(boxdom).height();
					var centerLeft = (sz.clientWidth - boxwidth) / 2;
					var centerTop = (sz.clientHeight - boxheight) / 2 + sz.scrollTop;
					var startLeft = 0,
						startTop = 0;
					var stopLeft = p.stopLeft === null ? centerLeft : p.stopLeft;
					var stopTop = p.stopTop === null ? centerTop : (p.stopTop + sz.scrollTop);
					
					stopTop=stopTop<sz.scrollTop?sz.scrollTop:stopTop;
					
					var midTop = stopTop;
					var midLeft = stopLeft;
					if (p.moveType == 1) {
						startLeft = stopLeft;
						startTop = sz.scrollTop - boxheight;
						midTop += p.rebDistance;
					} else {
						if (p.moveType == 2) {
							startLeft = stopLeft;
							startTop = sz.scrollTop + sz.clientHeight;
							midTop -= p.rebDistance;
						} else {
							if (p.moveType == 3) {
								startLeft = -boxwidth;
								startTop = stopTop;
								midLeft += p.rebDistance;
							} else {
								if (p.moveType == 4) {
									startLeft = boxwidth + sz.clientWidth;
									startTop = stopTop;
									midLeft -= p.rebDistance;
								}
							}
						}
					}
					$(boxdom).css({
						left: startLeft + "px",
						top: startTop + "px"
					}).animate({
						top: midTop + "px",
						left: midLeft + "px"
					}, p.speed || 200, p.easing, function() {
						$(boxdom).animate({
							top: stopTop + "px",
							left: stopLeft + "px"
						}, 200, function() {
							if ($.isFunction(p.moveStopCallback)) {
								p.moveStopCallback();
							}
						});
					});
				} else {
					if (p.stopLeft === null && p.stopTop === null) {
						__this.centerWindow(sz);
					} else {
						$("#dcom-sys-dlg-body-" + __this.guid).css({
							left: p.stopLeft + "px",
							top: (p.stopTop + sz.scrollTop) + "px"
						});
					}
				}
				return __this;
			};
			
			__this.closeWindow = function(forceClose) {
				if(!forceClose&&Xl.isFunction(__this.param.preCloseCallback)){
					__this.param.preCloseCallback(function(confrimclose){
						if(confrimclose){
							__this.closeWindow(true);
						}
					});
					return;
				}
				Xl.setG("Is/opendlg", false);
				var target = Xl.E("dcom-sys-dlg-" + __this.guid);
				if (target) {
					target.parentNode.removeChild(target);
					if ($.isFunction(__this.param.closeCallback)) {
						__this.param.closeCallback();
					}
				}
				Xl.setG("dlg/list",Xl.removeFrom(Xl._getGArr("dlg/list"),this)); //从数组中移除
				__t.removeLayerbg();
				__t.unLockDlg(p.creator); //解锁
			};
			
			__this.init();
	  
	  
	  }
			 
		
		 
	};
	
	__t.init();
	
	
})();