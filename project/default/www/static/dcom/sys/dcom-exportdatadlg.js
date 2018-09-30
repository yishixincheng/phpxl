// JavaScript Document
(function(){

    "use strict";

    new Xl.Class({
	  	 outinterface:['open'], /*对外结构*/
		 mdlg:null,
		 alerttimer:null,
		 iswait:false,
		 init:function(){
			 Xl.Dcom.addCom("sys/exportdatadlg",this);//注册组建
		 },
		 callouti:function(oiname,param){
			 //调用接口,必须函数
			 this.iswait=false;
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
			 
			 //根据参数构建页面
			 this.d_columns=p['columns']||[]; //数据结构样式[{no:'A1',name:'名字'}]
			 this.d_callback=p['callback']; //导入后的回调函数
			 this.d_failcallback=p['failcallback']||this.d_callback;
			 this.d_gatekey=p['gateway'];  //网关,提交地址
			 this.d_width=p['width'];      //宽度
			 this.d_title=p['title'];
			 this.d_columns=p['columns']||[];
			 this.d_attachdata=p['attachdata']||{};
			 this.d_attachfunc=p['attachfunc']||null;
			 this.d_controls=p['controls']||[];
			 this.d_createcallback=p['createcallback']||[];
			 this.guid=Xl.getGuid();

             Xl.Dcom.setBindData("sys/importdatadlg","obj_"+this.guid,this);
			 
			 if(!this.d_gatekey){
				 this.d_gatekey=Xl.GU("/dcom/sys/export");
			 }
			 
			 var A=['<div class="dcom-exportdata-body">'];
			 if(this.d_controls.length!=0){
				 A.push('<h3>筛选条件</h3>');
			 }
			 A.push(['<div class="dcom-exportdata-condition">',
					'</div>',
			        '<div class="dlg-exportdata-butt">',
				    '<div class="dlg-exportdata-pagenum"></div>',
					'<a href="javascript:;" data-event="exportdata"><i></i>导出数据到Excel表中</a>',
					'<form enctype ="multipart/form-data" action="',this.d_gatekey,'" method="post" target="_new">',
					'<div class="dcom-exportdata-hiddenfields" style="display:none;"></div>',
	                '<input type="hidden" name="FORMHASH" value="', $_FORMHASH, '">',
					'<input type="hidden" name="uid" value="',$_M['uid'],'">',
					'<input type="hidden" name="objid" value="obj_',this.guid,'">',
					'<input type="hidden" name="sessionkey" value="',$_sessionkey,'">',
					'</form>',
					'</div>',
					'</div>'].join(''));
			 
		     Xl.dlg({
				creator:this, //创建者
				getDlgObj:function(){	
					this.param['isOpenMove']=true;
					this.param['moveType']=3; //1,2,3,4
					
				},
				title:this.d_title||'导出数据',
				width:this.d_width||0,
				htmlContent:A.join(''),
				height:100,
				ismodal:true,
				closeCallback:p['closeCallback']||function(){},
				afterCall:function(mdlg){
					this.mdlg=mdlg;
					//注册事件
					this.wrapdom=Xl.E(".dcom-exportdata-body");
					var __t=this;
					 __t.mdlg.resizeWindow();
					 __t.mdlg.stopWindow();
					 __t.createpagenumView();
					 __t.addEvent();
					 if(Xl.isFunction(__t.d_createcallback)){
						 __t.d_createcallback.call(__t);
					 }

				}
				
		    });
			 
		 },
        createpagenumView:function(){
		  var param={
		  	wrap:Xl.E(".dlg-exportdata-pagenum"),
			controls:[
				{key:'num',type:'input',title:'导出条数',tip:'条',value:'1000',className:'dcom-exportdata-num',x:'0',y:'0' },
                {key:'page',type:'input',title:'当前页数',tip:'页',value:'1',className:'dcom-exportdata-page',x:'143',y:'0' }
			]
		  };
		  this._formsetObj=Xl.formset.mapCtrls(param);
		},
		 addEvent:function(){
			 var __t=this;
			 __t.addProxyEvent("exportdata",__t.exportData);
			 __t.registProxyEvent(__t.wrapdom);
			 
		 },
		 exportData:function(tid,pid){
			var params={};
			if(this.d_attachdata&&Xl.isObject(this.d_attachdata)&&!Xl.isArray(this.d_attachdata)){
				Xl.extend(params,this.d_attachdata);
			}

			if(Xl.isFunction(this.d_attachfunc)){
			    var attachdatas=this.d_attachfunc();	
				if(attachdatas&&Xl.isObject(attachdatas)&&!Xl.isArray(attachdatas)){
					Xl.extend(params,attachdatas);
				}
			}
            var page=$(".dcom-exportdata-page").find("input").val();
			var num=$(".dcom-exportdata-num").find("input").val();
			Xl.extend(params,{page:page});
			Xl.extend(params,{num:num});
			var paramhtm=[];
			Xl.forIn(params,function(i,nd){
				if(Xl.isString(i)&&!Xl.isUndefined(nd)){
				    paramhtm.push('<input name="'+i+'" type="hidden" value="'+nd+'">');
				}
			},this);
			$(this.wrapdom).find(".dcom-exportdata-hiddenfields").html(paramhtm.join(''));
			$(this.wrapdom).find("form").submit();
			this.mdlg.closeWindow();
		 }

	});
	
})();