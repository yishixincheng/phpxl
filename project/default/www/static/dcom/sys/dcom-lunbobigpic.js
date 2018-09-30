(function(){

    "use strict";
    var __t = {
        outinterface:['look'], /*对外结构*/
        alerttimer:null,
        pics:[],
        init:function(){
            Xl.Dcom.addCom("sys/lunbobigpic",this);//注册组建
        },
        callouti:function(oiname,param){
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
				 if(creator.__dcom_pic_islock){
				 	return true;
				 }else{
				 	return false;
				 }
				 creator.__dcom_pic_islock=true; //锁定
			 }
			 return false;
		 },
		 unLockDlg:function(creator){
			 if(creator&&creator!=this){
				 creator.__dcom_pic_islock=false; //锁定
			 }
		 },
         createLayerbg:function(__this){
			 //创建遮罩
			 if(__t.pics.length!==0){return;}
			 var dom=Xl.E("dcom-sys-pic-graybg");
			 if(dom){return;}
			 dom=Xl.addDivToBody("dcom-sys-pic-graybg");
			 dom.className="dcom-sys-pic-graybg g-picbg";
			 if(__this.param.zIndex){
				 dom.style.zIndex=__this.param.zIndex;
			 }
             $("html").css("overflow-y","hidden");
             $(dom).width(Xl.getViewSize().clientWidth);
             $(dom).height(Xl.getViewSize().clientHeight);
		 },
         outi_look:function(p){
             //防止多次弹出对话框，增加锁定功能
			 var creator=p.creator||this;
             var images=p.creator.images||[];
             __t.imgarr=[];
             for(var i=0;i<images.length;i++){
                 if(images[i].dataset.lunbobigpic=='lunbobigpic'){
                    __t.imgarr.push({src:images[i].src,w:images[i].naturalWidth,h:images[i].naturalHeight});
                }
             }
             if(__t.lockDlg(creator)){
			 	 return;
			 }
             if(!p.src){
                 Xl.alert("缺少必要参数","error");
                 return;
             }
             var afterCall=p.afterCall||null;
             var bpic = new __t.bpic(p);
             Xl.setG("pic/currActPic",bpic); //当前活跃对象
             Xl.pushG("pic/list",bpic); //压入数据
             if(Xl.isFunction(afterCall)){
				 afterCall.call(creator,bpic);
			 }
         },
         removeLayerbg:function(){
			 if(Xl._getGArr("pic/list").length!==0){
				 return;
			 }
             var dom=Xl.E("dcom-sys-pic-graybg");
			 if(dom){$(dom).remove();}
		 },
         
         getdlgnum:function(){
			 return Xl.getLen(Xl._getGArr("pic/list"))||0;
		 },
         bpic:function(p){
            var __this=this;
            __this.num=0;
            __this.imgdata = function () {
                return $('.dcom-sys-pic-imgwrap li')
            };
            __this.param = {
                domtype:p.domtype||'',
                title:p.title,//显示的标题 不传则不显示
                nowNum:p.point || 1,//当前图片是第几张
                sum:p.pic_list.length,
                type:p.type||"default",
                src:p.src||'',//必须
                pic_list:p.pic_list||[],
                picname:p.picname||'',
                closeCallback:p.closeCallback||false,//关闭回调
                disableGraybg:p.disableGraybg||false,//不创建遮罩
                zIndex:p.zIndex||null, //层级
                preCloseCallback:p.preCloseCallback||null,
            };
            Xl.setG("Is/lookpic",true);
            __this.setCloseCallback=function(closeCallback){
                if(Xl.isFunction(closeCallback)){
                __this.param.closeCallback=closeCallback;
                }
                return __this;
            };
            __this.init = function(){
                __this.guid=Xl.getGuid();
                var A = [];
                if(!__this.param.disableGraybg){
                    __t.createLayerbg(__this);
                }
                if($('.dcom-sys-pic-body')){
                    $('.dcom-sys-pic-body').parent().remove();
                }
                A.push('<div class="dcom-sys-pic-body" id="dcom-sys-pic-body-'+__this.guid+'">');
                A.push('<a href="javascript:;" class="dcom-sys-pic-close g-layerclose" id="dcom-sys-pic-close-'+__this.guid+'"></a>');
                A.push('</div>');
                __this.pic=Xl.addDivToBody("div");
                __this.pic.id="dcom-sys-pic-"+this.guid;
                __this.pic.innerHTML=A.join('');
                Xl.getImageSize(__this.param.src,function(info){
                    __this.injectIMG(__this.param.src,info.w,info.h);
                });
                __this.resizeWindow();
                if(__this.param.zIndex){
                    $("#dcom-sys-pic-body-"+__this.guid).css("z-index",__this.param.zIndex+1);
                }
                $("#dcom-sys-pic-close-"+__this.guid).click(function(e) {
                    $('#dcom-sys-pic-graybg').remove();
                    __this.closeWindow(); 
                });
                $("#dcom-sys-pic-graybg").on("click",function(e){
                    $('#dcom-sys-pic-graybg').remove();
                    __this.closeWindow();
                });
                $("#dcom-sys-pic-the-"+__this.guid).on("click",function(e){
                    $('#dcom-sys-pic-graybg').remove();
                    __this.closeWindow();
                });
                
            };
            __this.injectIMG = function(src,width,height){
                
                var sizes = __this.getFinalSize(width,height);
                var finalWidth = sizes.finalWidth;
                var finalHeight = sizes.finalHeight;
                $(".dcom-sys-pic-body").append('<div class="dcom-sys-pic-imgwrap">'+(__this.param.title?'<div class="dcom-sys-pic-title">'+__this.param.title+'<span class="dcom-sys-pic-nowNum">'+__this.param.nowNum+'</span>/'+__this.param.sum+'</div>':"") +
                    '<div class="dcom-sys-pic-arrow dcom-sys-pic-left" data-event="left"><</div>' +
                    '<div class="dcom-sys-pic-arrow dcom-sys-pic-right" data-event="right">></div></div>');
                if(__this.param.type==="fullscreen"||__this.param.type===2){
                    //这里留着等后面扩展
                }else{
                    // __t.imgarr.length
                    if(__this.param.pic_list.length>1){
                        var htm=[];
                        htm.push('<ul>');
                        Xl.forIn(__this.param.pic_list,function (i,v) {
                            var cls='';
                            if(src==v.src){
                                cls='curs';
                            }
                            var ws='',hs='';
                            if(v.w){
                                ws=v.w;
                                hs=v.h
                            }else{
                                Xl.getImageSize(v.src,function(info){
                                    ws=info.w;
                                    hs=info.h;
                                });
                            }
                           htm.push('<li class="'+cls+'"><img src="'+v.src+'" alt="查看大图"  width="'+ws+'" height="'+hs+'" class="dcom-sys-pic-img" id="dcom-sys-pic-the-'+__this.guid+'"></li>');
                        });
                        htm.push('</ul>');
                        $(".dcom-sys-pic-body").find(".dcom-sys-pic-left").before(htm.join(''));
                    }else{
                        $(".dcom-sys-pic-body").find(".dcom-sys-pic-imgwrap").append('<img alt="查看大图" src="'+src+'" width="'+finalWidth+'" height="'+finalHeight+'" class="dcom-sys-pic-img" id="dcom-sys-pic-the-'+__this.guid+'" style="z-index: 44005;">');
                        $('.dcom-sys-pic-arrow').hide();
                    }
                }
                __this.addEvent();
            };
             __this.addEvent=function(){
                 $(".dcom-sys-pic-left").on("click",function(e){
                     __this.leftClick ();
                 });
                 $(".dcom-sys-pic-right").on("click",function(e){
                     __this.rightClick ();
                 });
             };
            __this.getFinalSize = function(width,height){
                var width = parseInt(width);
                var height = parseInt(height);
                var size = Xl.getViewSize();
                var finalWidth = width;
                var finalHeight = height;
                if((width<size.clientWidth)&&(height<size.clientHeight)){
                    finalWidth = width;
                    finalHeight = height;
                    if(__this.param.title){
                        var percent = Math.fround(height/width);
                        if(Math.abs(finalHeight-size.clientHeight)<140){
                            finalWidth=finalWidth-(140-Math.abs(finalHeight-size.clientHeight))/percent;
                            finalHeight=finalHeight-140+Math.abs(finalHeight-size.clientHeight);
                        }
                    }

                }else if((width<size.clientWidth)&&(height>=size.clientHeight)){
                    var percent = Math.fround(height/(size.clientHeight));
                    finalHeight = size.clientHeight;
                    finalWidth = width/percent;

                    if(__this.param.title){
                        if(Math.abs(finalHeight-size.clientHeight)<140){
                            finalWidth=finalWidth-(140-Math.abs(finalHeight-size.clientHeight))/percent;
                            finalHeight=finalHeight-140+Math.abs(finalHeight-size.clientHeight);
                        }
                    }

                }else if((width>=size.clientWidth)&&(height<size.clientHeight)){
                    var percent = Math.fround(width/(size.clientWidth));
                    finalWidth = size.clientWidth;
                    finalHeight = height/percent;

                    if(__this.param.title){
                        if(Math.abs(finalHeight-size.clientHeight)<140){
                            finalWidth=finalWidth-(140-Math.abs(finalHeight-size.clientHeight))*percent;
                            finalHeight=finalHeight-140+Math.abs(finalHeight-size.clientHeight);
                        }
                    }


                }else if((width>=size.clientWidth)&&(height>=size.clientHeight)){
                    var widthPercent = Math.fround(width/(size.clientWidth));
                    var heightPercent = Math.fround(height/(size.clientHeight));
                    var maxPercent = Math.max(widthPercent,heightPercent);

                    finalHeight = height/maxPercent;
                    finalWidth = width/maxPercent;

                    if(__this.param.title){
                        var percent = Math.fround(finalWidth/finalHeight);
                        if(Math.abs(finalHeight-size.clientHeight)<140){
                            finalWidth=finalWidth-(140-Math.abs(finalHeight-size.clientHeight))*percent;
                            finalHeight=finalHeight-140+Math.abs(finalHeight-size.clientHeight);
                        }
                    }
                }
                return {
                    finalWidth:finalWidth,
                    finalHeight:finalHeight
                }
            };
            __this.resizeWindow = function(){
                $(window).resize(function() {
                    $("#dcom-sys-pic-graybg").width(Xl.getViewSize().clientWidth);
                    $("#dcom-sys-pic-graybg").height(Xl.getViewSize().clientHeight);
                });
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
				Xl.setG("Is/lookpic", false);
                __t.removeLayerbg();
				var target = Xl.E("dcom-sys-pic-" + __this.guid);
				if (target) {
					target.parentNode.removeChild(target);
					if ($.isFunction(__this.param.closeCallback)) {
						__this.param.closeCallback();
					}
				}
				Xl.setG("pic/list",Xl.removeFrom(Xl._getGArr("pic/list"),this)); //从数组中移除
				__t.unLockDlg(p.creator); //解锁
                $("html").css("overflow-y","scroll");
			};
            __this.leftClick = function ()  {
                var data = __this.imgdata();
                __this.num--;
                if(__this.num == -1) {
                    __this.num = data.length - 1 ;
                }
                $(".dcom-sys-pic-nowNum").html(__this.num+1);
                for(var i=0;i<data.length;i++) {
                    if (data[i].classList.contains('curs')){
                        data[i].classList.remove('curs');
                        data[__this.num].classList.add('curs')
                    }
                }
            };
            __this.rightClick = function ()  {
                var data = __this.imgdata();
                 __this.num++;
                if(__this.num == data.length) {
                    __this.num = 0;
                }
                $(".dcom-sys-pic-nowNum").html(__this.num+1);
                for(var i=0;i<data.length;i++) {
                    if ($(data[i]).attr('class')=='curs'){
                        $(data[i]).removeClass('curs');
                        $(data[__this.num]).addClass('curs');
                    }

                }
            };
            __this.init();
         }


    };

    __t.init();



})();


