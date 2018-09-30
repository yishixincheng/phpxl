// JavaScript Document
(function(){

	 "use strict";

	/**
	 * 重新编写图片裁切类
	 * @constructor
	 */

	function Cropper(p){

		this.dragMode=p.dragMode||false;
		this.crop=p.crop;
		this.built=p.built;
		this.setParam=p.setParam;
		this._limitOutCropRect=true;
		this._isSupportWeel=true;

		this.init=function(){
			if(Xl.isFunction(this.setParam)){
				this.setParam.call(this);
			}
			this.$_viewwindow=$(this._viewwindow);//视口对象
			this._viewwidth=this.$_viewwindow.width();
			this._viewheight=this.$_viewwindow.height();
			this._initContainer();
			this.loadimg(function(img){
				this._img=img;
				this.$_canvas.append(this._img);
				this.$_img2=$('<img src="'+this._picurl+'">').appendTo(this.$_imgviewbox);
				if(Xl.isFunction(this.built)){
					this.built.call(this);
				}
			});
		};
		this.init();
	 }
	 Cropper.prototype={

		 _initContainer:function(){

		 	 this.$_viewwindow.html(['<div class="cropper-container cropper-bg">',
				                       '<div class="cropper-wrap-box">',
				                       '<div class="cropper-canvas"></div>',
				                       '</div>',
				                       '<div class="cropper-drag-box cropper-move cropper-modal"></div>',
				                       '<div class="cropper-crop-box">',
				                       '<span class="cropper-view-box"></span>',
				                       '<span class="cropper-dashed dashed-h"></span>',
									   '<span class="cropper-dashed dashed-v"></span>',
					                   '<span class="cropper-center"></span>',
					                   '<span class="cropper-face cropper-move"></span>',
					                   '<span class="cropper-line line-e cropper-hidden" data-action="e"></span>',
					                   '<span class="cropper-line line-n cropper-hidden" data-action="n"></span>',
					                   '<span class="cropper-line line-w cropper-hidden" data-action="w"></span>',
					                   '<span class="cropper-line line-s cropper-hidden" data-action="s"></span>',
					                   '<span class="cropper-point point-e cropper-hidden" data-action="e"></span>',
					                   '<span class="cropper-point point-n cropper-hidden" data-action="n"></span>',
					                   '<span class="cropper-point point-w cropper-hidden" data-action="w"></span>',
					                   '<span class="cropper-point point-s cropper-hidden" data-action="s"></span>',
					                   '<span class="cropper-point point-ne cropper-hidden" data-action="ne"></span>',
					                   '<span class="cropper-point point-nw cropper-hidden" data-action="nw"></span>',
					                   '<span class="cropper-point point-sw cropper-hidden" data-action="sw"></span>',
				                       '<span class="cropper-point point-se cropper-hidden" data-action="se"></span>',
				                       '</div>',
				                       '</div>',
			                           '</div>'].join(''));
			 this.$_canvas=this.$_viewwindow.find(".cropper-canvas");
			 this.$_cropbox=this.$_viewwindow.find(".cropper-crop-box");
			 this.$_imgviewbox=this.$_viewwindow.find(".cropper-view-box");

		 },
		 loadimg:function(callback){

			 var img = new Image();
			 img.src = this._picurl;
			 var _this=this;
			 if(img.complete){
				 if(Xl.isFunction(callback)){
				 	callback.call(this,img);
				 }
			 }else{
				 img.onload = function(){
					 if(Xl.isFunction(callback)){
						 callback.call(_this,img);
					 }
				 };
			 }

		 },

		 setCanvasData:function(param){

		 	 this._canvas_width=param.width;
			 this._canvas_height=param.height;
			 this._canvas_x=param.left;
			 this._canvas_y=param.top;

		 },
		 setCropBoxData:function(param){

		 	 this._crop_width=param.width;
			 this._crop_height=param.height;
			 this._crop_x=param.left;
			 this._crop_y=param.top;

		 },
		 getImageData:function(){

		 	return this._img;

		 },
		 getCanvasData:function(){

		 	return {
		 		width:this._canvas_width,
				height:this._canvas_height,
				x:this._canvas_x,
				y:this._canvas_y
			};

	     },
		 getCropBoxData:function() {

	     	 return {
	     	 	 width:this._crop_width,
				 height:this._crop_height,
				 x:this._crop_x,
				 y:this._crop_y
			 };

		 },
		 output:function(param){

		 	 var _p_crop=this.getCropBoxData();
			 var _p_img=this.getImageData();
			 var _p_canvas=this.getCanvasData();

			 var _canvas_x=_p_canvas.x;
			 var _canvas_y=_p_canvas.y;
			 var _canvas_w=_p_canvas.width;
			 var _canvas_h=_p_canvas.height;

			 this.$_canvas.width(_canvas_w).height(_canvas_h).css({left:_canvas_x+"px",top:_canvas_y+"px"});
			 $(this._img).width(_canvas_w).height(_canvas_h);
			 this.$_img2.width(_canvas_w).height(_canvas_h);

			 var _crop_x=_p_crop.x;
			 var _crop_y=_p_crop.y;
			 var _crop_width=_p_crop.width;
			 var _crop_height=_p_crop.height;

			 this.$_cropbox.width(_crop_width).height(_crop_height).css({left:_crop_x+"px",top:_crop_y+"px"});

			 var img_left=_canvas_x-_crop_x;
			 var img_top=_canvas_y-_crop_y;

			 this.$_img2.css({marginTop:img_top+"px",marginLeft:img_left+"px"});

			 this.crop({x:(_crop_x-this._canvas_x),y:(_crop_y-this._canvas_y),width:this._canvas_width,height:this._canvas_height});

			 this.beginDrag();

		 },
		 beginDrag:function(){

		 	 //开启拖拽功能
			 this.$_viewwindow.on("mousedown",$.proxy(this.drag_start,this));
			 this.$_viewwindow.on("mousemove",$.proxy(this.drag_move,this));
			 this.$_viewwindow.on("mouseup",$.proxy(this.drag_end,this));

			 if(this._isSupportWeel){
				 this.$_viewwindow.on("mousewheel DOMMouseScroll",$.proxy(this.event_wheel,this));
			 }

		 },
		 drag_start:function(event){
		 	 this._drag_start=true;
			 this._drag_begin_x=event.pageX;
			 this._drag_begin_y=event.pageY;
			 this._canvas_x=parseInt(this.$_canvas.css("left").replace("px",""));
			 this._canvas_y=parseInt(this.$_canvas.css("top").replace("px",""));

		 },
		 drag_move:function(event){

		 	 if(!this._drag_start){
		 	 	return;
			 }
			 this._drag_end_x=event.pageX;
			 this._drag_end_y=event.pageY;
			 var _canvas_x=parseInt(this._drag_end_x-this._drag_begin_x+this._canvas_x);
			 var _canvas_y=parseInt(this._drag_end_y-this._drag_begin_y+this._canvas_y);

			 //不能低于边界
			 if(this._limitOutCropRect){
			 	  //是否限制在矩形框之外
				  if(_canvas_x>this._crop_x){
				  	   _canvas_x=this._crop_x;
				  }
				  if(_canvas_y>this._crop_y) {
					  _canvas_y = this._crop_y;
				  }
				  var _canvas_width=parseInt(this.$_canvas.width());
				  var _canvas_height=parseInt(this.$_canvas.height());
				  var _min_x=parseInt(this._crop_x+this._crop_width-_canvas_width);
				  var _min_y=parseInt(this._crop_y+this._crop_height-_canvas_height);
				  if(_canvas_x<_min_x){
				  	  _canvas_x=_min_x;
				  }
				  if(_canvas_y<_min_y){
					  _canvas_y=_min_y;
				  }
			 }
			 this.$_canvas.css({left:_canvas_x+"px",top:_canvas_y+"px"});
			 var img_left=_canvas_x-this._crop_x;
			 var img_top=_canvas_y-this._crop_y;
			 this.$_img2.css({marginTop:img_top+"px",marginLeft:img_left+"px"});

		 },
		 drag_end:function(event){
			 this._drag_start=false;

			 this._canvas_x=parseInt(this.$_canvas.css("left").replace("px",""));
			 this._canvas_y=parseInt(this.$_canvas.css("top").replace("px",""));


			 this.crop({x:(this._crop_x-this._canvas_x),y:(this._crop_y-this._canvas_y),width:this._canvas_width,height:this._canvas_height});
		 },
		 event_wheel:function(event){

			 event.preventDefault();
			 var delta = (event.originalEvent.wheelDelta && (event.originalEvent.wheelDelta > 0 ? 1 : -1)) || (event.originalEvent.detail && (event.originalEvent.detail > 0 ? -1 : 1));
			 var radio=0.1;
			 if(delta>0){

			 }else if(delta<0){

			 }

		 }

	};

	new Xl.Class({
	  	 outinterface:['open'],
		 init:function(){
			 Xl.Dcom.addCom("cropper",this);//注册组建
		 },
		 callouti:function(oiname,param){
			//调用接口,必须函数
			this.iswait=false;
			if(!Xl.inArray(oiname,this.outinterface)){
				alert("调用接口不存在！");
				return;
			}
			if(Xl.isFunction(this['outi_'+oiname])){
				this['outi_'+oiname](param||'');//调用接口
			}else{
				alert("调用接口没实现");
			}
		 },
		 outi_open:function(p){

			 //根据弹出
			 this._picurl=p.picurl||'';
			 this._piccode=p.piccode||'';
			 this._picwidth=p.picwidth||0;
			 this._picheight=p.picheight||0;
			 this._callback=p.callback||null;
			 this._closeCallback=p.closeCallBack||null;
			 this._cutwidth=p.cutwidth||100;  //要截取的宽度和高度
			 this._cutheight=p.cutheight||100; //要截图的宽度和高度
			 this._iszoom=p.iszoom||false; //是否可以缩放
			 this._viewwidth=p.viewwidth||800;  //视口宽度
			 this._viewheight=p.viewheight||500; //视口高度
			 this._watermark=p.watermark||'';

			 if(this._picurl){
				 this._popDlg($.proxy(this.startCropper,this));
			 }
			 
		 },
		 _popDlg:function(func){
				var htm='<div class="dcom-cropper-box"><div class="dcom-cropper-canvas" style="width:'+this._viewwidth+'px;height:'+this._viewheight+'px;"></div>';
				 htm+='<input type="hidden" class="pic-src" name="pic_src">';
				 htm+='<input type="hidden" class="pic-data" name="pic_data">';
				 htm+='<div class="dcom-cropper-tools"><div class="dcom-cropper-info"></div><div class="dcom-cropper-btns"><a data-event="submit">确认裁切</a></div></div>';
				 htm+='</div>';
				 var __t=this;
				 Xl.dlg({
					creator:this,
					getDlgObj:function(){	
						this.param['isOpenMove']=true;
						__t.mdlg=this;
					},
					title:"裁切图片",
					ismodal:true, //模式对话框
					width:this._viewwidth,
					htmlContent:htm,
					closeCallback:function(){
						 //移除控件
						if(Xl.isFunction(__t._closeCallback)){
							__t._closeCallback();
						}
					},
					afterCall:function(){
						//创建组建		
						this._init();
						this.addEvent();
						if(Xl.isFunction(func)){
							func();
						}
					}
				});
		 },
		 _init:function(){
			 this._cropperbox=$(this.mdlg.dlg).find(".dcom-cropper-box").eq(0);
			 this._croppercanvas=$(this._cropperbox).find(".dcom-cropper-canvas").eq(0);
			 this._croppertools=$(this._cropperbox).find(".dcom-cropper-tools").eq(0);
			 this._cropperinfo=$(this._croppertools).find(".dcom-cropper-info").eq(0);
			 this._cropperbtns=$(this._croppertools).find(".dcom-cropper-btns").eq(0);
		 },
		 addEvent:function(){
			  
			  this.addProxyEvent("submit",this.submit);
			  this.registProxyEvent(this._cropperbtns);
		 },
		 startCropper: function () {
			    var _this = this;

			    this.cropper=new Cropper({
			    	dragMode:'move',
					setParam:function(){
			    	     this._picurl=_this._picurl;

						 this._viewwindow=_this._croppercanvas.get(0); //视口
						 this._picwidth=_this._picwidth;
						 this._picheight=_this._picheight;

					},
					crop:function(e){
						_this.cropper=this;
						var json = {x:e.x,y:e.y,height:e.height,width:e.width,rotate:e.rotate};
						_this.setCropData(json);
					},
					built:function(){

						var imgdata=this.getImageData();
						var rw=imgdata.naturalWidth; //图片原宽
						var rh=imgdata.naturalHeight; //图片原高
						var cw=rw; //容器宽
						var ch=rh; //容器高
						var bl=1,bl1=1,bl2=1;
						bl=cw/ch;
						if(cw<_this._cutwidth){
							bl1=_this._cutwidth/cw;
							cw=_this._cutwidth;
						}
						if(ch<_this._cutheight){
							bl2=_this._cutheight/ch;
							ch=_this._cutheight;
						}
						if(bl1<bl2){
							ch=_this._cutheight;
							cw=ch*bl;
						}else if(bl1>bl2){
							cw=_this._cutwidth;
							ch=cw/bl;
						}else{
							bl1=cw/_this._cutwidth;
							bl2=ch/_this._cutheight;
							if(bl1<bl2){
								cw=_this._cutwidth;
								ch=cw/bl;
							}else if(bl1>bl2){
								ch=_this._cutheight;
								cw=ch*bl;
							}else{
								cw=_this._cutwidth;
								ch=_this._cutheight;
							}
						}
						var canl=(_this._viewwidth-cw)/2;
						var cant=(_this._viewheight-ch)/2;

						this.setCanvasData({width:cw,height:ch,left:canl,top:cant});

						var c_left=(_this._viewwidth-_this._cutwidth)/2;
						var c_top=(_this._viewheight-_this._cutheight)/2;

						this.setCropBoxData({width:_this._cutwidth,height:_this._cutheight,top:c_top,left:c_left});

						this.output(true); //修改源代码了
						_this.mdlg.resizeWindow();
					}
				});
		},
		setCropData:function(json){
			
			var bili=this._cutwidth/json.width;
		    var x=bili*json.x;     //坐标x
			var y=bili*json.y;     //坐标y
			var cavdata=this.cropper.getCanvasData();
			var imgdata=this.cropper.getImageData();
			
			//获取放大倍数
			var zoom=cavdata.width/imgdata.naturalWidth; 
			//裁切
			this._postdata={
				x:x,
				y:y,
				zoom:zoom,
				picurl:this.url,
				imgcode:this._piccode,
				cutwidth:this._cutwidth,
				cutheight:this._cutheight,
				watermark:this._watermark
			};
			
		},
		submit:function(){
			
			Xl.alert("正在裁切，请稍候",'right');
			var __t=this;
			if(Xl.isEmpty(this._postdata.imgcode)){

				//不提交到后台
				Xl.alert("裁切成功","right");
				if(Xl.isFunction(__t._callback)){
					__t._callback(this._postdata);
				}
				__t.mdlg.closeWindow();
				return;
			}

			if(this.iswait){
				return;
			}
			this.iswait=true;

			Xl.request(Xl.GU("/dcom/sys/upload/crop"),this._postdata,'json','post',function(d,isok){
				__t.iswait=false;
				if(isok){
					Xl.alert("裁切成功","right");
					__t.mdlg.closeWindow(); //关闭对话框
					if(Xl.isFunction(__t._callback)){
						__t._callback(d);
					}
				}else{
					Xl.alert(d.msg||'裁切失败',"error");
				}
			},true);
			
		}
		 
	});



})();