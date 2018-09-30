// JavaScript Document
(function(){

    "use strict";
	
    new Xl.Class({
	  	 outinterface:['open'], /*对外结构*/
		 mdlg:null,
		 alerttimer:null,
		 iswait:false,
		 init:function(){
			 Xl.Dcom.addCom("sys/importdatadlg",this);//注册组建
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
			 this.d_failcallback=p['failcallback']||null;
			 this.d_gatekey=p['gateway'];  //网关,提交地址
			 this.d_width=p['width'];      //宽度
			 this.d_title=p['title'];
			 this.d_attachdata=p['attachdata']||{};
			 
			 this.guid=Xl.getGuid();

             if(!this.d_gatekey){
                 this.d_gatekey=Xl.GU("/dcom/sys/import");
             }

             Xl.Dcom.setBindData("sys/importdatadlg","obj_"+this.guid,this);
			 var hiddenhtml=[];
			 Xl.forIn(this.d_attachdata,function(i,node){
				 hiddenhtml.push('<input type="hidden" name="'+i+'" value="'+node+'">');
			 });
			 hiddenhtml=hiddenhtml.join('');
			 
			 var A=['<div class="dcom-importdata-body">'];
			 
			 A.push(['<iframe name="dcom-importdata-post-iframe" style="display:none;"></iframe>',
			         '<div class="dcom-importdata-control">',
			         '<a><i></i>选择Excel文件上传并导入数据</a>',
					 '<form enctype ="multipart/form-data" action="',this.d_gatekey,'" method="post" target="dcom-importdata-post-iframe">',
					 '<div style="display:none;">',hiddenhtml,'</div>', 
					 '<input type="hidden" name="FORMHASH" value="', $_FORMHASH, '">',
					 '<input type="hidden" name="uid" value="',$_M.uid,'">',
					 '<input type="hidden" name="objid" value="obj_',this.guid,'">',
					 '<input type="hidden" name="sessionkey" value="',$_sessionkey,'">',
					 '<input type="file" name="excelfile" >',
					 '</form>',
			         '</div>',
					 '<div class="dcom-importdata-tip">数据格式如下：</div>',
					 '<div class="dcom-importdata-columns"><ul>'].join(''));
			 
			 Xl.forIn(this.d_columns,function(i,node){
				 if(Xl.isObject(node)){
				    A.push('<li>',node['no'],':',node['name'],'</li>');
				 }
			 });
			 A.push('</ul></div></div>');

		     Xl.dlg({
				creator:this, //创建者
				getDlgObj:function(){	
					this.param['isOpenMove']=true;
					this.param['moveType']=3; //1,2,3,4
					this.stopWindow();
					
				},
				title:this.d_title||'导入数据',
				width:this.d_width||0,
				height:250,
				ismodal:true,
				htmlContent:A.join(''),
				closeCallback:p['closeCallback']||function(){},
				afterCall:function(mdlg){
					this.mdlg=mdlg;
					//注册事件
					this.wrapdom=Xl.E(".dcom-importdata-body");
					this.addEvent();
				}
		    });
			 
		 },
		 addEvent:function(){
			 
			 var __this=this;
			 //注册事件
			 $(this.wrapdom).find('input[type="file"]').change(function(e) {
				  __this.upload(this);
             });
			 
		 },
		 getisexcelfile: function(path) {
			if (path) {
				var picarr = path.split('.');
				var filedot = picarr.pop();
				filedot = filedot.toLowerCase();
				if (Xl.inArray(filedot, ['exe', 'dll','jpg','bmp','png','gif','txt','word'])) {
					return false;
				}
			}
			return true;
		 },
		 upload:function(thisid){
			 
			 var val=$(thisid).val();
			 //判断是否是excel格式
			 if(Xl.isEmpty(val)){
				 return;
			 }
			 if(!this.getisexcelfile(val)){
				 Xl.alert("上传文件格式不正确");
				 return;
			 }
			 $(this.wrapdom).find('form').submit();
			 
		 },
		 fail:function(msg){
			 
			 Xl.alert(msg,"error");
			 $(this.wrapdom).find('input[type="file"]').val('');
			 if(Xl.isFunction(this.d_failcallback)){
				 this.d_failcallback();
			 }
			 
		 },
		 succ:function(msg) {

             Xl.alert(msg || "导入成功", "right", 4000);
             this.mdlg.closeWindow();
             if (Xl.isFunction(this.d_callback)) {
                 this.d_callback();
             }

         }

	});
	
})();