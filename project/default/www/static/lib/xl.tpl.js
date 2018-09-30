//*artTemplate模版*//
//作者：一世心城
//loop data $v $i。$v必须加美元符号

(function(){ 
  
  function template(ct,data){
	  
	  ct=dealCt(ct);
	  if(Xl.isEmpty(ct)){
		  return ct;
	  }
	  ct=compile(ct,data);
	  return ct;
	  
  }
  function dealCt(ct){
	  if(typeof ct=="object"){
		  var dom=Xl.E(ct);
		  ct=dom.value?dom.value:dom.innerHTML;
		  
		  if(Xl.isEmpty(ct)){
			  return '';
		  }
		  return dealCt(ct);
	  }
	  if(!Xl.isString(ct)){
		  return '';
	  }
	  
	  ct=ct.replace(/^\s*|\s*$/g, '');
	  return ct;
  }

  var options = template.options = {
     openTag: '{{', 
     closeTag: '}}',
     escape: true,     
     compress: false,  
     parser: null      
  }; 
  var each = function (data, callback) {
		var i, len;        
		if (Xl.isArray(data)) {
			for (i = 0, len = data.length; i < len; i++) {
				callback.call(data, data[i], i, data);
			}
		} else {
			for (i in data) {
				callback.call(data, data[i], i);
			}
		}
  };
  var toString = function (value, type) {
		if (typeof value !== 'string') {
			type = typeof value;
			if (type === 'number') {
				value += '';
			} else if (type === 'function') {
				value = toString(value.call(value));
			} else {
				value = '';
			}
		}
		return value;
   };
   var escapeMap = {"<": "&#60;",">": "&#62;",'"': "&#34;","'": "&#39;","&": "&#38;"};
   var escapeFn = function (s) {
		return escapeMap[s];
   };
   var escapeHTML = function (c) {
		return toString(c).replace(/&(?![\w#]+;)|[<>"']/g, escapeFn);
   };
   var utils = template.utils = {
		$string: toString,
		$escape: escapeHTML,
		$each: each
   };
   var forEach = utils.$each;
   var KEYWORDS =
		// 关键字
		'break,case,catch,continue,debugger,default,delete,do,else,false'
		+ ',finally,for,function,if,in,instanceof,new,null,return,switch,this'
		+ ',throw,true,try,typeof,var,void,while,with'
		// 保留字
		+ ',abstract,boolean,byte,char,class,const,double,enum,export,extends'
		+ ',final,float,goto,implements,import,int,interface,long,native'
		+ ',package,private,protected,public,short,static,super,synchronized'
		+ ',throws,transient,volatile'
		// ECMA 5 - use strict
		+ ',arguments,let,yield'
	
		+ ',undefined';

	var REMOVE_RE = /\/\*[\w\W]*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|"(?:[^"\\]|\\[\w\W])*"|'(?:[^'\\]|\\[\w\W])*'|\s*\.\s*[$\w\.]+/g;
	var SPLIT_RE = /[^\w$]+/g;
	var KEYWORDS_RE = new RegExp(["\\b" + KEYWORDS.replace(/,/g, '\\b|\\b') + "\\b"].join('|'), 'g');
	var NUMBER_RE = /^\d[^,]*|,\d[^,]*/g;
	var BOUNDARY_RE = /^,+|,+$/g;
	var SPLIT2_RE = /^$|,+/;

    function compile(ct,data){
	     try{
	        var fn=compiler(ct);
		   return data ? new fn(data) : fn;
		 }catch(err){
			 alert(err);
		 }
	  
    }
    // 获取变量
	function getVariable (code) {
		return code
		.replace(REMOVE_RE, '')
		.replace(SPLIT_RE, ',')
		.replace(KEYWORDS_RE, '')
		.replace(NUMBER_RE, '')
		.replace(BOUNDARY_RE, '')
		.split(SPLIT2_RE);
	};
	// 字符串转义
	function stringify (code) {
		return "'" + code
		// 单引号与反斜杠转义
		.replace(/('|\\)/g, '\\$1')
		// 换行符转义(windows + linux)
		.replace(/\r/g, '\\r')
		.replace(/\n/g, '\\n') + "'";
	}
    function compiler(ct){
		var openTag = options.openTag;
		var closeTag = options.closeTag;
		var parser = options.parser;
		var compress = options.compress;
		var escape = options.escape;
		var replaces=["$out='';", "$out+=", ";", "$out"];
		var concat="$out+=text;return $out;"
		var funcs="";
		for(var i in utils){
			funcs+="var "+i+"=this['"+i+"'];";
		}
		var headerCode = "'use strict';"+funcs+"var $utils=this,"
		var vendCode=replaces[0];
		var mainCode ='';
		var splitVCode='';//分解变量
		var footerCode = "return new String(" + replaces[3] + ");"
		var uniq = {$data:1,$utils:1,$out:1};
		// html与逻辑语法分离
		forEach(ct.split(openTag), function (code) {
			code = code.split(closeTag);
			var $0 = code[0];
			var $1 = code[1];
			if (code.length === 1) {
				mainCode += html($0);
			} else {
				mainCode += logic($0);
				if ($1) {
					 mainCode += html($1);
				}
			}
		});
		//数组情况
		var mBy="if(Xl.isArray($data)){";
		mBy+="$data.forEach(function($dataitem){";
		mBy+="var $n=0,"+splitVCode+"$index=1;$n++;";
		mBy+=mainCode;
		mBy+="});"
		mBy+="}else{"
		mBy+="var $dataitem=$data,"+splitVCode+"$index=0;";
		mBy+=mainCode;
		mBy+="}";
		var code = headerCode+ vendCode + mBy + footerCode;
		try {
             var Render = new Function("$data", code);
	         Render.prototype = utils;
			 return Render;
		} catch (e) {	
			throw e;
		}
		// 处理 HTML 语句
		function html (code) {
			// 压缩多余空白与注释
			if (compress) {
				code = code
				.replace(/\s+/g, ' ')
				.replace(/<!--[\w\W]*?-->/g, '');
			}
			if (code) {
				code = replaces[1] + stringify(code) + replaces[2] + "\n";
			}
			return code;
		}
		// 处理逻辑语句
		function logic (code) {
			
			var scode=code;
			code = parser(code);
			forEach(getVariable(scode), function (name) {
				
				if(Xl.inArray(name,['if','loop','else'])){
					return;
				}
				if(name.charAt(0)=="$"){
					
					return;
				}
				if (!name || uniq[name]) {
					return;
				}
				var value;
				if (utils[name]) {
					value = "$utils." + name;
				} else {
				     value = "$dataitem." + name;
				}
				splitVCode += name + "=" + value + ",";
				uniq[name] = true;
			});
			return code + "\n";
		}
    }
	options.parser = function (code) {
	    
		code = code.replace(/^\s/, '');
		var split = code.split(/\s+/);
		var key = split.shift();
		var args = split.join(/\s+/);
		switch (key) {
			case 'if':
				code = 'if(' + args + '){';
				break;
			case 'else':
				if (split.shift() === 'if') {
					split = ' if(' + split.join(' ') + ')';
				} else {
					split = '';
				}
				code = '}else' + split + '{';
				break;
			case '/if':
				code = '}';
				break;
			case 'loop':
				object=split[0]||'$dataitem';
				if(object.charAt(0)!="$"){
					object="$dataitem."+object;
				}
				var value  = split[1] || '$value';
				var index  = split[2] || '$index';
				var param   = value + ',' + index;
				code =  '$each(' + object + ',function(' + param + '){';
				break;
			 case '/loop':
			    code = '});';
			 break;
			 default:
				var isbm=true;
				if(/^(\&).+$/.test(code)){
					//编码
					code=code.slice(1);
					isbm=false;
				}
				var s='if(Xl.isFunction('+code+')){'
				if(isbm){
					//默认是编码
					s+="$out+=$escape("+code+".call($dataitem));";
				}else{
					s+="$out+="+code+".call($dataitem);"
				}
				s+='}else{';
				if(isbm){
					//默认是编码
					s+="$out+=$escape("+code+");";
				}else{
					s+="$out+="+code+";";
				}
				s+='}';
				code=s;
				break;
		}
		
		return code;
	};

    Xl.Tpl=template;
  

})();

