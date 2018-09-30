// JavaScript Document

(function() {
	new Xl.Class({
		outinterface: ["open"],
		isInit: false,
		mdlg:null,
		init: function() {
            Xl.Dcom.addCom("map", this);
			
		},
		callouti: function(oiname, param) {
			this.iswait = false;
			if ($.inArray(oiname, this.outinterface) == -1) {
				alert("调用接口不存在", "error");
				return
			}
			if ($.isFunction(this["outi_" + oiname])) {
				this["outi_" + oiname](param || "")
			} else {
				alert("调用接口没实现")
			}
		},
		outi_open: function(p) {
			
			var width=p['width']||800;
			var height=p['height']||400;
			var posx=p['posx'];
			var posy=p['posy'];
			var ismark=p['ismark']||0; //是否是标记地图
			var dragCallback=p['dragCallback']||null;
			var submitCallback=p['submitCallback']||null;
			var container=p['container']||null;
			var ispop=p['ispop']||false;
			var cityname=p['cityname']||'';
			var shownav=p['shownav'];
			var title=p['title']||"地图";
			var lspm=p['lspm']||'';
			
			this.setParam("dragCallback",dragCallback);
			this.setParam("submitCallback",submitCallback);
			
			
			
			var vsize=Xl.getViewSize();
			
		    if(width>(vsize.clientWidth-50)){
	 	        width=vsize.clientWidth-50;
	        }
			if(height>(vsize.clientHeight-20)){
				height=vsize.clientHeight-20;
			}
			
			if(posx&&posy){
				var url=Xl.GU({m:'plugs',r:'markmap',width:width,height:height,posx:posx,posy:posy,shownav:shownav,ismark:ismark,lspm:lspm});
			}else{
				var url=Xl.GU({m:'plugs',r:'markmap',width:width,height:height,cityname:cityname,shownav:shownav,ismark:ismark,lspm:lspm});
			}
			var guid=Xl.getGuid();
			var maphtml='<div class="g-mapcontrol-container" style="width:'+width+'px; height:'+height+'px;">';
			maphtml+='<iframe id="g_mapiframe-'+guid+'" style="width:'+width+'px; height:'+height+'px;" scrolling="no" frameborder="0" marginheight="0" marginwidth="0" ></iframe>';
			if(ismark){
				maphtml+='<a class="dcom-map-markbutton" data-event="marksubmit">确认标记</a>';
			}
			maphtml+='</div>';
			
			var __t=this;
			
			if(ispop){

                Xl.dlg({
					creator:this, //创建者
					getDlgObj:function(){	
						this.param['isOpenMove']=true;
						this.param['moveType']=3; //1,2,3,4
						this.stopWindow();
						__t.mdlg=this;
					},
					title:title,
					width:width,
					height:height,
					htmlContent:maphtml,
					closeCallback:function(){},
					afterCall:function(){
						$("#g_mapiframe-"+guid).attr("src",url);
						__t.addEvent();
					}
				});
			
			}else{
				$(container).html(maphtml);
				$("#g_mapiframe-"+guid).attr("src",url);
				__t.addEvent();
			}
			
		},
		addEvent:function(){
			
			this.addProxyEvent("marksubmit",this.markSubmit);
			this.registProxyEvent(".g-mapcontrol-container");
			
		},
		dragCallback:function(bmapx,bmapy){
			
			var point={bmapx:bmapx,bmapy:bmapy};
			this.setParam("point",point);
			var dgck=this.getParam("dragCallback");
			if(Xl.isFunction(dgck)){
				dgck(point);
			}
		},
		markSubmit:function(){
			
			var sbcb=this.getParam("submitCallback");
			var point=this.getParam("point");
			if(Xl.isFunction(sbcb)){
				sbcb(point);
			}
			if(this.mdlg){
				this.mdlg.closeWindow();//关闭窗口
			}
		}
	})
})();