// JavaScript Document

//配置文件

window.XL_CONFIG={
	   isDebug:true, //开发状态
	   V:330401947,
	   rootUrl:'/',
	   dcomRootUrl:'/static/dcom/',
	   libRootUrl:'/static/lib/',
	   relyList:{
		   Form:'form|css|js',
		   Drag:'drag',
		   bindForm:'form|css|js',
		   Imager:'form|css|js',
		   AD:'ad|css|js'
	   }
};

(function(c){
	
	"use strict";
	
	var relyList=c.relyList||{};
	for(var i in relyList){
		var p=relyList[i];
		if(typeof p!="string"){
			relyList[i]='';
			continue;
		}
		if(p.indexOf('.js')!=-1||p.indexOf('.css')!=-1){
			continue;
		}
		var mt=p.match(/(\|css)|(\|js)/g);
		var dotcss='',dotjs='.js';
		if(mt&&typeof mt[0]!="undefined"){
			dotcss='.css';
		}
		var fn=p.replace(/(\|css)|(\|js)/g,'');
		fn=fn.replace(/(^\s*)|(\s*$)/g,'');
		var jsfile=c.libRootUrl+"xl."+fn+dotjs;
		if(dotcss){
			var fa=[],cssfile=c.libRootUrl+"css/xl."+fn+dotcss;
			fa.push(cssfile);
			fa.push(jsfile);
			relyList[i]=fa;
		}else{
			relyList[i]=jsfile;
		}
	}
	
})(window.XL_CONFIG);
