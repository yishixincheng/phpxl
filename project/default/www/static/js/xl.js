/**
 Ds2.0
 by一世心城
 date:2015-09-01
 github:https://github.com/yishixincheng/Ds
 date:2017-17-29 名称由Ds改成Xl和后端保持同一命名
 **/
(function(_W_){

    'use strict';
    var toString={}.toString,slice=[].slice,UA=_W_.navigator.userAgent.toLowerCase();


    var CONFIG=window.XL_CONFIG||{
            isDebug:false, //开发状态
            rootUrl:'/',
            dcomRootUrl:'static/dcom/'
        };
    if(typeof window.console=="undefined"){
        window.console={
            log:function(str){
            }
        };
    }

    if (!Array.prototype.forEach) {
        Array.prototype.forEach = function(callback, thisArg) {
            var T, k;
            if (this == null) {
                throw new TypeError(" this is null or not defined");
            }
            var O = Object(this);
            var len = O.length >>> 0;
            if (!Xl.isFunction(callback)) {
                throw new TypeError(callback + " is not a function");
            }
            if (thisArg) {
                T = thisArg;
            }
            k = 0;
            while (k < len) {
                var kValue;
                if (k in O) {
                    kValue = O[k];
                    callback.call(T, kValue, k, O);
                }
                k++;
            }
        };
        Array.prototype.map = function(fun)
        {
            var len = this.length;
            if (!Xl.isFunction(fun))
                throw new TypeError();

            var res = new Array(len);
            var thisp = arguments[1];
            for (var i = 0; i < len; i++)
            {
                if (i in this)
                    res[i] = fun.call(thisp, this[i], i, this);
            }
            return res;
        };
        Array.prototype.filter=function(fun){
            var len = this.length;
            if (!Xl.isFunction(fun))
                throw new TypeError();
            var res = new Array();
            var thisp = arguments[1];
            for (var i = 0; i < len; i++)
            {
                if(fun.call(thisp, this[i])){
                    res.push(this[i]);
                }
            }
            return res;
        };
    }
    function isArray(v){
        return toString.call(v) == '[object Array]';
    }
    function isjQueryObject(obj){
        if(obj instanceof jQuery) {
            return true;
        }
        return false;
    }
    function isDomObject(obj){
        return  ( typeof HTMLElement === 'function' ) ? (obj instanceof HTMLElement):(obj && typeof obj === 'object' && obj.nodeType === 1 && typeof obj.nodeName === 'string');
    }
    function isUndefined(v){
        return toString.call(v) == '[object Undefined]'||(typeof v=="undefined");
    }
    function isObject(v){
        if(isUndefined(v)){
            return false;
        }
        return toString.call(v) == '[object Object]';
    }
    function isFunction(v){
        return toString.call(v) == '[object Function]';
    }
    function findNodeByAtrr(node,attr,value){
        if(node.nodeType == 1){
            if(value===null||Xl.isUndefined(value)){
                if(node.getAttribute(attr)){
                    return attr;
                }
            }
            else if(node.getAttribute(attr)==value){
                return node;
            }
            if(node.hasChildNodes){
                var sonnodes = node.childNodes;
                for (var i = 0; i < sonnodes.length; i++) {
                    var sonnode = sonnodes.item(i);
                    if(sonnode.nodeType == 1){
                        var rt=findNodeByAtrr(sonnode,attr,value);
                        if(rt){
                            return rt;
                        }
                    }
                }
            }
        }
    }
    function isChildDom(node,pnode){

        if(pnode.nodeType==1){
            if(node===pnode){
                return 1;
            }
            if(pnode.hasChildNodes){
                var sonnodes = pnode.childNodes;
                for (var i = 0; i < sonnodes.length; i++) {
                    var sonnode = sonnodes.item(i);
                    if(sonnode.nodeType == 1){
                        if(sonnode===node){
                            return true;
                        }
                        var rt=isChildDom(node,sonnode);
                        if(rt){
                            return rt;
                        }
                    }
                }
            }
        }

    }
    function bubbleFind(node,each,pnode,proxy){

        //冒泡查找
        if(!(isDomObject(node)&&isFunction(each))){
            return false;
        }
        pnode=pnode||document.documentElement;
        proxy=proxy||window;

        var target=null;

        while(node!==null&&node!=pnode){

            if(node.nodeType==1){

                if(each.call(proxy,node)){
                    target=node;
                    break;
                }
            }
            node=node.parentNode;
        }

        return target;

    }
    function bindValAndDom(param){
        var value=param.value;
        var domstr=param.dom;
        var type=param.type;
        var check=param.check; //Number,Float
        var checkfail=param.checkfail||null;
        var valname=param.valname; //变量名称
        var maxrange=param.maxrange; //变量取值范围
        var callback=param.callback;
        var listenChange=param.listenChange||null;
        var pdom=param.pdom; //从某dom下查询
        var dom=null;
        callback=Xl.isFunction(type)?type:callback;
        if(Xl.isString(domstr)){
            domstr=Xl.trim(domstr);
            if(/(text|value|html)\:[\w-]+/g.test(domstr)){

                pdom=Xl.E(pdom);

                dom=Xl.Dom.getDomByDataBind(domstr,pdom);
            }else{
                dom=$(domstr).get(0);
            }
            var m=domstr.match(/(text|value|html)\:/g);
            if(m){
                m=m[0];type=m.slice(0,-1);
            }
        }else{
            dom=$(domstr).get(0);
        }
        if(!dom){return value;}

        var initHasVal=true;
        if(Xl.isUndefined(value)){
            value=dom.value;
            initHasVal=false;
        }
        var tip=Xl.sgData(dom,'tip')||'',owncheckmsg=false;
        if(!Xl.isUndefined(param.owncheckmsg)){
            owncheckmsg=param.owncheckmsg||false;
        }else{
            owncheckmsg=Xl.sgData(dom,"owncheckmsg")||false;
            if(owncheckmsg=="false"){
                owncheckmsg=false;
            }
        }
        type=type||"value";
        var interfacefun=function(){
        };
        var attach={value:value,
            type:type,
            tip:tip,
            dom:dom,
            callback:callback,
            isDispatch:1,
            check:check,
            owncheckmsg:owncheckmsg,
            valname:valname,
            maxrange:maxrange,
            listenChange:listenChange,
            checkfail:checkfail,
            initHasVal:initHasVal};

        Xl.extend(interfacefun,attach);

        return interfacefun;
    }


    /**
     简单的Promise为了兼容IE
     **/
    function Promise(callback){
        this.callback=callback;
    }
    Promise.prototype={
        then:function(s,f){

            this.s=s;
            this.f=f;
            if(Xl.isFunction(this.callback)){
                this.callback.call(this,this.resolve,this.reject);
            }
        },
        resolve:function(param){
            //成功后回调函数
            if(Xl.isFunction(this.s)){
                this.s.call(this,param);
            }
        },
        reject:function(param){
            if(Xl.isFunction(this.f)){
                this.f.call(this,param);
            }
        }
    };
    function forIn(obj,each,proxy){
        proxy=proxy||obj;
        var i,rt;
        if(isObject(obj)){
            for(i in obj){
                if(obj.hasOwnProperty(i)){
                    if(isFunction(each)){
                        rt=each.call(proxy,i,obj[i]);
                        if(rt=="__break"){
                            break;
                        }
                        if(rt=="__continue"){
                            continue;
                        }
                    }
                }
            }
            return;
        }
        if(isArray(obj)){
            for(i=0;i<obj.length;i++){
                if(isFunction(each)){
                    rt=each.call(proxy,i,obj[i]);
                    if(rt=="__break"){
                        break;
                    }
                    if(rt=="__continue"){
                        continue;
                    }
                }
            }
        }

    }
    function Copy(obj){
        if(typeof(obj)!="object" || obj===null)return obj;
        var o={};
        if(isArray(obj)){
            o=[];
        }
        forIn(obj,function(i,v){
            if(isArray(v)||(isObject(v)&& v!==null&&!isDomObject(v))){
                o[i]=Copy(v);
            }else{
                o[i]=v;
            }
        });
        return o;

    }

    function Xl(p,callback){

        //访问Xl属性，如果不存，则到配置文件中的搜寻
        if(Xl.isEmpty(p)){return Xl;} //返回自身
        p=Xl.trim(p);
        var selector=[];
        if(Xl.isString(p)){
            selector=(((p.replace(/(\s*(\.|\||>|\.)\s*)|(\s+)/g,',')).split(',')).filter(function(f){
                return !Xl.isEmpty(f);
            })).slice(0,5);
        }else if(Xl.isArray(p)){
            selector=p;
        }else if(Xl.isFunction(p)){
            //如果是函数就等dom加载完后回调
            if(Xl.Ready.done){return p();}
            if(Xl.Ready.timer){
                Xl.Ready.funlist.push(p);
            }else{
                Xl.Ready.funlist=[p];
                Xl.Ready.timer=window.setInterval(function(){
                    if(Xl.Ready.done){return false;}
                    if(document&&document.getElementById&&document.body){
                        window.clearInterval(Xl.Ready.timer);
                        Xl.Ready.timer=null;
                        Xl.Ready.funlist.forEach(function(f){
                            f();
                        });
                        Xl.Ready.funlist=null;
                        Xl.Ready.done=true;
                    }
                },13);
            }
            return;
        }
        if(Xl.isEmpty(selector)){
            return;
        }
        //解析数组
        var proto=selector.shift();
        if(!Xl.hasOwnProperty(proto)){
            //不存在的属性
            var fl=Xl.CONFIG.relyList[proto];
            if(Xl.isEmpty(fl)){
                Xl.alert("访问的属性"+proto+"不存在");
                return;
            }
            Xl.include(fl,function(){
                if(Xl.isFunction(callback)){
                    var val=Xl.accessObjFromKeys(Xl[proto],selector);
                    callback.call(Xl[proto],val);
                }
            });
            return null;
        }
        var val=Xl.accessObjFromKeys(Xl[proto],selector);
        if(Xl.isFunction(callback)){
            callback.call(Xl[proto],val);
        }
        return val;

    }
    Xl.extend=function(target, source){
        var stype=typeof source;
        if (typeof target!=="object"&&!isFunction(target) ) {
            target = {};
        }
        if(stype==='undefined'||stype ==='boolean'){
            source=target;
            target=this;
        }
        var cs=Copy(source);
        for (var p in cs) {
            if (cs.hasOwnProperty(p)) {
                target[p] = cs[p];
            }
        }
        return target;
    };

    Xl.inherit=function(target,parent){

        parent=Copy(parent);
        for (var p in parent){
            if (parent.hasOwnProperty(p)) {
                if(Xl.isUndefined(target[p])){
                    target[p] = parent[p];
                }
            }
        }
        target.parent=parent;

        return target;
    };

    Xl.extend({
        VERSION:'2.0.0',
        CONFIG:CONFIG,
        isHtm5:window.applicationCache?true:false,
        Ready:{},
        Cache: {},
        Promise:_W_.Promise||Promise,
        forIn:forIn,
        copy:Copy,
        getBrowerV:function(){
            if(!Xl.isEmpty(Xl._browerVersion)){return Xl._browerVersion;}
            var b=Xl._browerVersion={},u= UA;
            var s;
            (s = u.match(/msie ([\d.]+)/)) ? (b.ie = s[1]) :
                (s = u.match(/firefox\/([\d.]+)/)) ? (b.firefox = s[1]) :
                    (s = u.match(/chrome\/([\d.]+)/)) ? (b.chrome = s[1]) :
                        (s = u.match(/opera.([\d.]+)/)) ? (b.opera = s[1]) :
                            (s = u.match(/version\/([\d.]+).*safari/)) ? (b.safari) = s[1] : 0;
            return b;
        },
        isIE8:function(){
            //是否是ie8以下版本包含ie8
            var v=Xl.getBrowerV();
            return v.ie?(parseInt(v.ie)<9?true:false):false;
        },
        isIE7:function(){
            var v=Xl.getBrowerV();
            return v.ie?(parseInt(v.ie)<8?true:false):false;
        },
        getGuid:function(){
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){
                var r=Math.random()*16|0;
                var v=c=='x'?r:(r&0x3|0x8);
                return v.toString();
            }).toUpperCase();
        },
        setG:function(key, value) {
            function P(r, k, v) {
                var ka = k.split(">");
                var len = ka.length;
                if (len == 1) {
                    r[ka[0]] = v;
                } else if (len == 2) {
                    r[ka[0]] ? '' : (r[ka[0]] = {});
                    r[ka[0]][ka[1]] = v;
                }
            }
            if (typeof key == "string") {
                var K = key.split('/');
                var len = K.length;
                switch (len) {
                    case 1:
                        P(Xl.Cache, K[0], value);
                        break;
                    case 2:
                        Xl.Cache[K[0]] ? '' : (Xl.Cache[K[0]] = {});
                        P(Xl.Cache[K[0]], K[1], value);
                        break;
                    case 3:
                        Xl.Cache[K[0]] ? '' : (Xl.Cache[K[0]] = {});
                        Xl.Cache[K[0]][K[1]] ? '' : (Xl.Cache[K[0]][K[1]] = {});
                        P(Xl.Cache[K[0]][K[1]], K[2], value);
                        break;
                }
            }
        },
        getG:function(key) {
            if (typeof key == "string") {
                var K = key.split('/');
                var len = K.length;
                try {
                    switch (len) {
                        case 1:
                            return Xl.Cache[K[0]];
                        case 2:
                            return Xl.Cache[K[0]][K[1]];
                        case 3:
                            return Xl.Cache[K[0]][K[1]][K[2]];
                    }
                } catch (err) {
                    return 'undefined';
                }
            }
        },
        _getGArr:function(key){
            var arr=Xl.getG(key);
            if(Xl.isEmpty(arr)||!Xl.isArray(arr)){
                arr=[];
            }
            return arr;
        },
        pushG:function(key,v){
            var vs=Xl._getGArr(key);
            vs.push(v);
            Xl.setG(key,vs);
            return vs;
        },
        popG:function(key){
            var vs=Xl._getGArr(key);
            vs.pop();
            Xl.setG(key,vs);
            return vs;
        },
        shiftG:function(key){
            var vs=Xl._getGArr(key);
            vs.shift();
            Xl.setG(key,vs);
            return vs;
        },
        unshiftG:function(key,v){
            var vs=Xl._getGArr(key);
            vs.unshift(v);
            Xl.setG(key,vs);
            return vs;
        },
        isUndefined:isUndefined,
        isBoolean:function(v){
            return toString.call(v) == '[object Boolean]';
        },
        isNumber:function(v){
            if(Xl.isString(v)){
                if(/^\s*((-|\+)?)\d+?(\.?)\d*\s*$/.test(v)){
                    return true;
                }
            }
            return toString.call(v) == '[object Number]';
        },
        isString:function(v){
            return toString.call(v) == '[object String]';
        },
        isArray:isArray,
        isFunction:isFunction,
        isObject:isObject,
        isRegExp:function(v){
            return toString.call(v) == '[object RegExp]';
        },
        isPlainObject:function(v){
            if(!Xl.isObject(v)){
                return false;
            }
            for(var i in v){
                if(Xl.isFunction(v[i])){
                    return false;
                }
            }
            return true;
        },
        isDomObject:isDomObject,
        isjQueryObject:isjQueryObject,
        isChildDom:isChildDom,
        bubbleFind:bubbleFind,
        inArray:function(value,array,ct){
            var rt=-1;
            Xl.forIn(array,function(i,v){
                if(ct){
                    if(v===value){
                        rt=i;
                    }
                }else{
                    if(v==value){
                        rt=i;
                    }
                }
            });
            if(rt==-1){
                return false;
            }
            return true;
        },
        isEmpty:function(at){
            if(typeof at=="undefined"){return true;}
            if(at===null){return true;}
            if(typeof at=="string"){
                if(/^\s*$/g.test(at)){return true;}
            }
            if(Xl.isArray(at)){if(at.length===0){return true;}}
            if($.isPlainObject(at)){for(var i in at){return false;}
                return true;}
        },
        leftStr:function(a,len,b){

            if(!Xl.isString(a)){
                return a;
            }
            if(Xl.isUndefined(b)){
                b=true;
            }
            if(b){
                return a.substr(0,len);
            }else if(a.length>len){
                return a.substr(0,len)+'...';
            }else{
                return a;
            }
        },
        trim:function(s){
            if(!Xl.isString(s)){return s;}
            return s.replace(/(^\s*)|(\s*$)/g,'');
        },
        removeFrom:function(obj,find){

            if(Xl.isArray(obj)){
                obj=obj.filter(function(x){return x!=find;});
            }
            return obj;

        },
        include:function(fs,cb,obj){
            //导入文件
            obj=obj||Xl;
            fs=Xl.isArray(fs)?fs:fs.split(',');
            var fa=[];
            fs.forEach(function(f){
                var fr=[];
                if(f.indexOf(",")!=-1){
                    fr=f.split(',');
                }else{
                    fr=[f];
                }
                fa=fa.concat(fr);
            });
            fa=fa.map(function(f){
                if(f.indexOf(".js")!=-1){
                    return {file:f,type:'js'};
                }else if(f.indexOf(".css")!=-1){
                    return {file:f,type:'css'};
                }else{
                    return {file:f+".js",type:'js'};
                }
            });
            if(Xl.isFunction(cb)){
                var odoms=[];
                var _selfcall=function(fa){
                    var fn=fa.shift();
                    if(Xl.isEmpty(fn)){cb.call(obj,odoms);return;}
                    var promise=Xl[fn.type=="css"?"_loadCssFile":"_loadJsFile"](fn.file,cb).then(function(odom){
                        if(!Xl.isUndefined(odom)){odoms.push(odom);}
                        _selfcall(fa);

                    });
                };
                _selfcall(fa);

            }else{

                for( var index in fa){
                    var f=fa[index];
                    if(f.type=="css"){
                        Xl.addCssToHead(f.file,cb,obj,index);
                    }else{
                        Xl.addJsToHead(f.file,cb,obj,index);
                    }
                }
            }

        },
        debug:function(info){
            var dom=Xl.E("g_debug");
            if(!dom){
                dom=Xl.addDivToBody("g_debug");
                dom.style.display="none";
            }
            if(Xl.isEmpty(info)){info=null;}
            info===null?$(dom).empty():$(dom).append(info.toString());
        },
        addDivToBody:function(id, tag) {
            tag = tag || 'div';
            var oBody = document.getElementsByTagName('BODY').item(0);
            var odiv = document.createElement(tag);
            odiv.setAttribute('id', id);
            odiv.guid = Xl.getGuid();
            oBody.appendChild(odiv);
            return odiv;
        },
        _putLoadFileToCache:function(fn){
            var c=Xl._getGArr("includeFileCache/list");
            var tmpArr=[],isOverlap=false;
            if(Xl.isEmpty(c)){
                tmpArr.push(fn);
            }else{
                c.map(function(f){
                    if(Xl._equalSameFile(f.file,fn.file)){
                        isOverlap=true;
                        return fn;
                    }
                    return f;
                });
                tmpArr=c;
                if(!isOverlap){
                    tmpArr.push(fn);
                }
            }
            Xl.setG("includeFileCache/list",tmpArr);
        },
        _inLoadedFileQuque:function(file){
            //是否加载过该文件
            var c=Xl._getGArr("includeFileCache/list");
            var isExist=false;
            c.forEach(function(f){
                if(Xl._equalSameFile(f.file,file)){
                    //文件存在
                    isExist=true;
                }
            });
            return isExist;
        },
        _equalSameFile:function(file1,file2){

            var mtc1=file1.match(/^([^\?]+)(\?(.+?=?.*))?$/);
            var mtc2=file2.match(/^([^\?]+)(\?(.+?=?.*))?$/);

            if(!mtc1||!mtc2){
                return false;
            }
            if(mtc1[1]==mtc2[1]){
                return true;
            }
            return false;

        },
        _removeLoadFileFromCache:function(file){
            var c=Xl._getGArr("includeFileCache/list");
            var tmpArr=[];
            c.forEach(function(f){
                if(!Xl._equalSameFile(f.file,file)){
                    tmpArr.push(f);
                }
            });
            Xl.setG("includeFileCache/list",tmpArr);
        },
        _setLoadFileStatus:function(file,isload){
            var fn={file:file,isload:isload};
            var c=Xl._getGArr("includeFileCache/list");
            c=c.map(function(f){
                if(Xl._equalSameFile(f.file,fn.file)){
                    f.isload=isload;
                }
                return f;
            });
            Xl.setG("includeFileCache/list",c);
        },
        _getLoadFileStatus:function(file){
            var c=Xl._getGArr("includeFileCache/list");
            var isload=0;
            c.forEach(function(f){
                if(!Xl._equalSameFile(f.file,file)){
                    isload=f.isload||0;
                    return;
                }
            });
            return isload;
        },
        _loadCssFile:function(css,func){
            return Xl._dyLoadFile(css,func,'css');
        },
        _loadJsFile:function(js, func){
            return Xl._dyLoadFile(js,func,'js');
        },
        _loadIndexCssFile:function(css, func){
            return Xl._dyLoadIndexFile(css,func,'css');
        },
        _loadIndexJsFile:function(js, func){
            return Xl._dyLoadIndexFile(js,func,'js');
        },
        _dyLoadIndexFile:function(file,func,type){
            var promise=null;
            promise=new Xl.Promise(function(resolve,reject){
                if(Xl._inLoadedFileQuque(file)){
                    var poll=null,btime=Xl.getTime();
                    var polltimer=window.setTimeout(poll=function(){
                        if(Xl._getLoadFileStatus(file)==1){
                            resolve.call(promise);
                            window.clearTimeout(polltimer);
                            return;
                        }
                        var etime=Xl.getTime();
                        if((etime-btime)>1000){
                            window.clearTimeout(polltimer);
                            return;
                        }
                        window.setTimeout(poll,13);
                    },0);
                    return;
                }
                Xl._putLoadFileToCache({file:file,isload:0});
                var oHead=document.getElementsByTagName('HEAD').item(0);
                var ochildren = oHead.children;
                var oBottom = ochildren[ochildren.length-2];
                var ofile=null;
                if(type=="css"){
                    ofile = document.createElement("link");
                    ofile.type = "text/css";
                    ofile.href = file;
                    ofile.rel = "stylesheet";
                }else{
                    ofile = document.createElement("script");
                    ofile.type = "text/javascript";
                    ofile.src = file;
                }
                oHead.insertBefore(ofile,oBottom);
                ofile.guid = Xl.getGuid();
                if (Xl.isFunction(func)) {
                    if (Xl.isIE8()) {
                        ofile.onreadystatechange = function() {
                            if (ofile.readyState == 'loaded' || ofile.readyState == 'complete') {
                                resolve.call(promise,ofile);
                                Xl._setLoadFileStatus(file,1);
                            }
                        };

                    } else {
                        ofile.onload = function() {
                            resolve.call(promise,ofile);
                            Xl._setLoadFileStatus(file,1);
                        };
                    }
                }

            });
            return promise;

        },
        _dyLoadFile:function(file,func,type){
            var promise=null;
            promise=new Xl.Promise(function(resolve,reject){
                if(Xl._inLoadedFileQuque(file)){
                    var poll=null,btime=Xl.getTime();
                    var polltimer=window.setTimeout(poll=function(){
                        if(Xl._getLoadFileStatus(file)==1){
                            resolve.call(promise);
                            window.clearTimeout(polltimer);
                            return;
                        }
                        var etime=Xl.getTime();
                        if((etime-btime)>1000){
                            window.clearTimeout(polltimer);
                            return;
                        }
                        window.setTimeout(poll,13);
                    },0);
                    return;
                }
                Xl._putLoadFileToCache({file:file,isload:0});
                var oHead=document.getElementsByTagName('HEAD').item(0);
                var ofile=null;
                if(type=="css"){
                    ofile = document.createElement("link");
                    ofile.type = "text/css";
                    ofile.href = file;
                    ofile.rel = "stylesheet";
                }else{
                    ofile = document.createElement("script");
                    ofile.type = "text/javascript";
                    ofile.src = file;
                }
                oHead.appendChild(ofile);
                ofile.guid = Xl.getGuid();
                if (Xl.isFunction(func)) {
                    if (Xl.isIE8()) {
                        ofile.onreadystatechange = function() {
                            if (ofile.readyState == 'loaded' || ofile.readyState == 'complete') {
                                resolve.call(promise,ofile);
                                Xl._setLoadFileStatus(file,1);
                            }
                        };

                    } else {
                        ofile.onload = function() {
                            resolve.call(promise,ofile);
                            Xl._setLoadFileStatus(file,1);
                        };
                    }
                }

            });
            return promise;

        },
        addCssToHead:function(css, func, obj,param) {
            Xl._loadCssFile(css,func).then(function(ocss){
                //代表加载成功
                func.call(obj,ocss,param);//加载成功回调
            });
        },
        addJsToHead:function(js, func, obj,param) {
            Xl._loadJsFile(js,func).then(function(oscript){
                //代表加载成功
                func.call(obj,oscript,param);
            });
        },
        addJsToHeadIndex:function(js, func, obj,param){
            Xl._loadIndexJsFile(js,func).then(function(oscript){
                //代表加载成功
                func.call(obj,oscript,param);
            });
        },
        addCssToHeadIndex:function(css, func, obj,param){
            Xl._loadIndexCssFile(css,func).then(function(oscript){
                //代表加载成功
                func.call(obj,oscript,param);
            });
        },
        fetchObjByKeys:function(keys,obj){
            if(Xl.isString(keys)){
                keys=keys.split(',');
            }
            var tem={};
            if(Xl.isArray(keys)){
                for(var k in keys){
                    if(obj.hasOwnProperty&&obj.hasOwnProperty(keys[k])){
                        tem[keys[k]]=obj[keys[k]];
                    }else{
                        tem[keys[k]]=obj[keys[k]];
                    }
                }
            }
            return tem;
        },
        accessObjFromKeys:function(obj,arr){

            if(!Xl.isEmpty(arr)){
                arr.forEach(function(p){
                    if(obj.hasOwnProperty(p)){
                        obj=obj[p];
                    }else{
                        Xl.alert("访问的属性"+p+"不存在");
                    }
                });
            }
            return obj;
        },
        getViewSize:function(obj) {
            obj = Xl.E(obj)||document.documentElement;
            var scrollTop,scrollLeft;
            if (obj == document.documentElement) {
                scrollTop = obj.scrollTop||document.body.scrollTop;
                scrollLeft = obj.scrollLeft||document.body.scrollLeft;
            }
            var screen = window.screen;
            var vsize=Xl.fetchObjByKeys("clientWidth,clientHeight,offsetWidth,offsetHeight",obj);
            vsize.screen=screen;
            vsize.scrollTop=scrollTop;
            vsize.scrollLeft=scrollLeft;

            return vsize;
        },
        registGlobalEvent:function() {
            function _getregistnonamefunction(key) {
                return function(e) {
                    var funcs = Xl.getG('Event/' + key);
                    var rt=true;
                    if (!Xl.isEmpty(funcs)) {
                        var funcsArr=[];
                        Xl.forIn(funcs,function(i,func){
                            funcsArr.unshift(func);
                        });
                        Xl.forIn(funcsArr,function(i,func){
                            if(Xl.isFunction(func)){
                                rt=func.call(this,e);
                                if(rt===false){
                                    return '__break';
                                }
                            }
                        },this);
                    }
                    if(rt===false){
                        return rt;
                    }
                };
            }
            if (Xl._isregistglobalevent) {
                return;
            }
            Xl._isregistglobalevent = true;
            $(window).bind("scroll", _getregistnonamefunction('scrollFunc')).bind("resize", _getregistnonamefunction('resizeFunc'));
            $(window).unload(_getregistnonamefunction('rootUnloadFunc'));
            $(document).bind("click", _getregistnonamefunction('rootclickFunc'));
            $(document).bind("mousedown", _getregistnonamefunction('rootmousedownFunc'));
            $(document).bind("mouseup", _getregistnonamefunction('rootmouseupFunc'));
            $(document).bind("keyup", _getregistnonamefunction('rootkeyupFunc'));
            $(document).bind("keydown", _getregistnonamefunction('rootkeydownFunc'));
        },
        //Xl.E
        E:function(id,parent) {
            if (Xl.isString(id)){
                id=Xl.trim(id);
                if(id.charAt(0)=="#"){
                    return $(id).get(0);
                }else {
                    //data选择器
                    var m=id.match(/\[(data-[a-z]+?)(=[\"|\'](.+)?[\"|\'])?\]/);
                    if(m){
                        if(document.querySelectorAll){
                            parent=parent||document;
                            return parent.querySelector(m[0]);
                        }else{
                            //ie7
                            var attr=m[1];
                            var value=m[3];
                            if(!value){
                                value=null;
                            }
                            return findNodeByAtrr(parent||document.body||document.documentElement,attr,value);
                        }
                    }else if(/[\.\>\s\:\$|(|)]/g.test(id)){
                        return $(id).eq(0).get(0);
                    }
                }
                return document.getElementById(id);
            } else if (Xl.isDomObject(id)) {
                return id;
            }else if( id instanceof jQuery){
                return id.get(0);
            }
            return null;
        },
        Ajax:function(u,d,dt,t,s,a,b,c){try{$.ajax({'url':u,data:d,dataType:dt,type:t,success:s,async:a,beforeSend:b,complete:c});}catch(e){}},
        centerWindow:function(ob, w, h) {
            var sn = Xl.getViewSize();
            var bh = (sn.clientHeight - h) / 2;
            bh = bh < 0 ? 0 : bh;
            var scrolltop = sn.scrollTop + bh;
            var bw = Math.abs((sn.clientWidth - w) / 2);
            ob.style.left = bw + "px";
            ob.style.top = scrolltop + "px";
        },
        date:function(fmt,time){
            if(Xl.isUndefined(time)){
                time=Xl.getTime();
            }else{
                time=time*1000; //转换为毫秒
            }
            if(Xl.isUndefined(fmt)){
                fmt="yyyy-MM-dd hh:mm:ss";
            }
            var t=new Date(time);
            var o = {
                "M+" : t.getMonth()+1,
                "d+" : t.getDate(),
                "h+" : t.getHours(),
                "m+" : t.getMinutes(),
                "s+" : t.getSeconds(),
                "q+" : Math.floor((t.getMonth()+3)/3),
                "S"  : t.getMilliseconds()
            };
            if(/(y+)/.test(fmt)){
                fmt=fmt.replace(RegExp.$1, (t.getFullYear()+"").substr(4 - RegExp.$1.length));
            }
            for(var k in o){
                if(new RegExp("("+ k +")").test(fmt)){
                    fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
                }
            }
            return fmt;
        },
        getTime:function(){
            return (new Date()).getTime();
        },
        strToTime: function(date) {
            if (date === 0) {
                return 0;
            }
            var moth,d;
            if (date) {
                var datearr = date.split('-');
                if (datearr[1].substr(0, 1) == "0") {
                    moth = parseInt(datearr[1].substr(1, 1)) - 1;
                } else {
                    moth = parseInt(datearr[1]) - 1;
                }
                d = new Date(datearr[0], moth, datearr[2]);
                return d.getTime();
            } else {
                d = new Date();
                var year = d.getFullYear();
                moth = d.getMonth();
                var day = d.getDate();
                d = new Date(year, moth, day);
                return d.getTime();
            }
        },
        getDJSformat: function(endtime) {
            if (!/^\d+$/g.test(endtime)) {
                endtime = Xl.strToTime(endtime);
            } else {
                endtime = endtime * 1000;
            }
            var d = new Date();
            var nowtime = d.getTime();
            if (nowtime > endtime) {
                return {timeend:true};
            }
            var cm = (endtime - nowtime) / 1000;
            var day = Math.floor(cm / (3600 * 24));
            if (day > 365 * 10) {
                return {timeend:false,day:day,hour:0,minute:0,second:0};
            }
            cm = cm - day * 3600 * 24;
            var hour = Math.floor(cm / 3600);
            cm = cm - hour * 3600;
            var minute = Math.floor(cm / 60);
            cm = cm - minute * 60;
            var second = parseInt(cm);
            return {timeend:false,day:day,hour:hour,minute:minute,second:second};
        },
        getImageSize: function(src, func) {
            var i = new Image();
            i.src = src;
            if ($.isFunction(func)) {
                if (i.complete) {
                    func({
                        w: i.width,
                        h: i.height,
                        src: src
                    });
                } else {
                    i.onload = function() {
                        func({
                            w: i.width,
                            h: i.height,
                            src: src
                        });
                    };
                }
            } else {
                return {
                    w: i.width,
                    h: i.height,
                    src: src
                };
            }
        },
        GU: function(src) {

            var url;
            if (Xl.isPlainObject(src)) {
                url=[];
                Xl.forIn(src,function(i,v){
                    if(Xl.isString(v)){
                        url.push(v);
                    }
                });
                url=url.join('/');
            } else {
                url = src;
            }

            if(url.substr(0,1)=="/"){
                url=Xl.Router.getUrlRoot()+url;
            }else if(!/^((http\:)|(https\:)|(ftp\:)).+?$/.test(url)){
                url=Xl.Router.getUrlRoot()+"/"+url;
            }

            return url;

        },
        //滚动到指定位置
        scrollPage: function(top, speed) {

            if (typeof top == "undefined") {
                top = 0;
            } else if (typeof top == "string") {
                if (/^\d+$/.test(top)) {
                    top = parseInt(top);
                } else {
                    top = $(top).offset().top;
                }
            }
            speed = speed || 100;
            try {
                $("html,body").animate({
                    scrollTop: top
                }, speed);
            } catch (err) {}
        },
        zoomSize:function(sw,sh,boxw,boxh){
            var w,h;
            boxw=boxw||20000;boxh=boxh||20000;
            if(sw<=boxw&&sh<boxh){return {w:sw,h:sh};}
            var b1=boxw/sw;var b2=boxh/sh;
            var b=sw/sh;
            if(b1<=b2){
                w=boxw;h=w/b;return{w:w,h:h};
            }else{
                h=boxh;w=b*h;
                return{w:w,h:h};
            }
        },
        sgData:function(id,d,v){
            var dom=null;
            dom=Xl.E(id);
            if(!dom){return '';}
            if(dom.dataset){
                if(Xl.isUndefined(v)){
                    return dom.dataset[d];
                }else{
                    dom.dataset[d]=v;
                }
            }else{
                if(Xl.isUndefined(v)){
                    return dom.getAttribute("data-"+d);
                }else{
                    dom.setAttribute("data-"+d,v);
                }
            }
        },
        getLen:function(obj){
            if(Xl.isNumber(obj)){
                obj=obj.toString();
            }
            if(Xl.isArray(obj)||Xl.isString(obj)){
                return obj.length;
            }else if(Xl.isPlainObject(obj)){
                var len=0;
                for(var i in obj){
                    if(obj.hasOwnProperty(i)){
                        len++;
                    }
                }
                return len;
            }
            return 0;
        }

    });
    Xl.attach = {};

    Xl.Mem={
        set:function(key,value){
            var keyarr=[];
            if(key.indexOf('/')){
                keyarr=key.split('/');
            }else{
                keyarr=[key];
            }
            var ov=Xl.store.get(keyarr[0])||{};
            var keylen=Xl.getLen(keyarr);
            switch(keylen){
                case 2:
                    ov[keyarr[1]]=value;
                    break;
                case 3:
                    ov[keyarr[1]]=ov[keyarr[1]]||{};
                    ov[keyarr[1]][keyarr[2]]=value;
                    break;
                default:
                    ov=value;
                    break;
            }
            Xl.store.set(keyarr[0],ov);
        },
        get:function(key){
            var keyarr=[];
            if(key.indexOf('/')){
                keyarr=key.split('/');
            }else{
                keyarr=[key];
            }
            var ov=Xl.store.get(keyarr[0])||{};
            var keylen=Xl.getLen(keyarr);
            switch(keylen){
                case 2:
                    return ov[keyarr[1]];
                case 3:
                    ov[keyarr[1]]=ov[keyarr[1]]||{};
                    return ov[keyarr[1]][keyarr[2]];
                default:
                    return ov;
            }
        }

    };

    Xl.Dcom={
        version:Math.random(),
        registComs: {},
        comsStore:{},
        loadedCache:{},
        callc:function(comname, func, param) {

            var comkey=comname;
            //支持子目录，类似命名空间
            comname=comname.replace("\\","/");
            param = param || '';
            var comobj = Xl.Dcom.getCom(comname);
            if (comobj !== null) {
                if (Xl.isFunction(func)) {
                    func.call(Xl.Dcom);
                } else {
                    comobj.callouti(func, param);
                }
            } else {

                if(Xl.isUndefined(this.loadedCache[comkey])){

                    this.loadedCache[comkey]=1; //加载中
                    var rdm='',_path='',_comname='';
                    if(/\//.test(comname)){
                        var patharr=comname.split("/");
                        _comname=patharr.pop();
                        _path=patharr.join('/');
                        _path+="/";
                    }else{
                        _comname=comname;
                    }
                    var jsFile=Xl.CONFIG.dcomRootUrl+_path+"dcom-" +_comname + ".js";
                    var cssFile=Xl.CONFIG.dcomRootUrl+_path+"css/dcom-" +_comname + ".css";

                    if(Xl.CONFIG.isDebug){
                        rdm="?"+Xl.Dcom.version;
                    }else{
                        rdm="?v="+Xl.CONFIG.V;
                    }

                    jsFile+=rdm;
                    cssFile+=rdm;

                    Xl.include([cssFile,jsFile],function(doms) {
                        var comobj = Xl.Dcom.getCom(comname);
                        if(comobj!==null){
                            comobj.bindDoms=doms;
                            if (Xl.isFunction(func)) {
                                func.call(Xl.Dcom);
                            } else {
                                comobj.callouti(func, param);
                            }
                        }
                        Xl.Dcom.loadedCache[comkey]=2; //加载完成
                    });

                }else{

                    window.setTimeout(function(){
                        Xl.Dcom.callc(comkey,func,param); //循环检测
                    },10);

                }

            }

        },
        addCom:function(comname, obj) {
            Xl.Dcom.registComs["dcom-" + comname] = obj;
            obj.dcomobjstr = "Xl.Dcom.registComs.dom-" + comname;
        },
        getCom:function(comname) {
            return Xl.Dcom.registComs["dcom-" + comname] || null;
        },
        removeCom:function(comname,holdbinddata) {
            //移除组建
            var dcom=Xl.Dcom.registComs["dcom-" + comname];
            if(dcom.bindDoms){
                dcom.bindDoms.map(function(fd){$(fd).remove();}); //删除加载文件
            }
            if(Xl.isFunction(dcom.distroy)){
                dcom.distroy(); //释放内存
            }
            dcom=null;
            if(!holdbinddata){
                this.emptyBinData(comname);
            }
        },
        setBindData:function(comname,key,value){

            if(!Xl.isObject(this.comsStore[comname])){
                this.comsStore[comname]={};
            }

            this.comsStore[comname][key]=value;

        },
        getBindData:function (comname,key) {
            if(!Xl.isObject(this.comsStore[comname])){
                return null;
            }else{
                return this.comsStore[comname][key];
            }
        },
        delBindData:function(comname,key){
            if(Xl.isObject(this.comsStore[comname])){
                delete this.comsStore[comname][key];
            }
        },
        emptyBinData:function(comname){
            if(Xl.isObject(this.comsStore[comname])){
                delete this.comsStore[comname];
            }
        }

    };


    //弹出对话框
    Xl.dlg=function(param){
        return Xl.Dcom.callc("sys/dlg","open",param);
    };
    //提示框
    Xl.alert=function(tip,type,time,func){
        return Xl.Dcom.callc("sys/alert","open",{tip:tip,type:type||'',time:time||2000,func:func||null});
    };

    //查看大图
    Xl.lookbigpic=function(param){
        return Xl.Dcom.callc("sys/lookbigpic","look",param);
    };
    //查看大图（轮播）
    Xl.lunbobigpic=function(param){
        return Xl.Dcom.callc("sys/lunbobigpic","look",param);
    };
    //ajax请求
    Xl.request=function(url,data,dataType,type,success,async,style,disablelock,callbackhook,objhook){
        if(Xl.isUndefined(style)){
            style=1;
        }else if(Xl.isNumber(style)){
            if(style!="0"||style!=1){
                style=0;
            }
        }
        Xl.Dcom.callc("sys/request","open",{url:url,data:data||{},dataType:dataType||'',
            type:type,success:success||null,async:async,style:style,
            disablelock:disablelock||false,callbackhook:callbackhook||null,
            objhook:objhook||null});
    };

    Xl.confirm=function(tip,fun,cancel){

        Xl.Dcom.callc("sys/confirm","open",{tip:tip,callback:fun,cancelCallback:cancel});

    };

    Xl.Router={
        getHash:function(hf){
            hf=hf||window.location.hash||window.location.href;
            return hf.replace( /^[^#]*#?(.*)$/, '$1' );
        },
        isSameUrl:function(url1,url2){
            url1=url1||'';
            url2=url2||'';

            url1=url1.replace(/#.*/,"");
            url2=url2.replace(/#.*/,"");

            if(url1==url2){
                return true;
            }
            return false;
        },
        getUrlP:function(href){

            href=href||window.location.href;
            href=href.replace(/(\s*http:\/\/[^\?]+?\?)(.*)/g,"$2");
            var rt=null;
            if(href){
                var hrefArr=href.split('&');
                if(hrefArr){
                    rt={};
                    Xl.forIn(hrefArr,function(i,v){
                        var vv=v.split('=');
                        if(vv&&vv.length==2){
                            rt[vv[0]]=decodeURIComponent(vv[1]);
                        }
                    });
                }
            }
            return rt;
        },
        getUrlRoot:function(href){

            //获取当前根域名
            href=href||window.location.href;
            if(!/^((http\:)|(https\:)|(ftp\:)).+?$/.test(href)){
                href="http://"+href;
            }
            href=href.replace(/^([^\/]+?\:\/\/[^\/]+)(\/?.*)$/,"$1"); //去掉尾部

            return href; //不带斜杠结尾

        }
    };

    Xl.Reg={
        isEmail:function(str){
            return /\s*[a-zA-Z0-9]+@[a-z0-9]+?\.[a-z0-9]\s*/g.test(str);
        },
        isTel:function(str){
            return /^\s*1(3|4|5|6|7|8|9)\d{9}\s*$/g.test(str);
        },
        isHanzi:function(str,ispart){
            if(ispart){
                return /[\u4e00-\u9fa5]/g.test(str);
            }
            return /^\s*[\u4e00-\u9fa5]+\s*$/g.test(str);
        }
    };

    Xl.Dom={
        getDomByDataBind:function(databind,parent){
            return Xl.E('[data-bind="'+databind+'"]',parent);
        },
        getCaretPos:function(Indom){

            var CaretPos = 0;   // IE Support
            if (document.selection) {
                Indom.focus ();
                var Sel = document.selection.createRange ();
                Sel.moveStart ('character', -Indom.value.length);
                CaretPos = Sel.text.length;
            }
            else if (Indom.selectionStart || Indom.selectionStart == '0') {
                CaretPos = Indom.selectionStart;
            }
            return (CaretPos);
        },
        insertAtCaret:function(Indom,text)
        {
            //IE support
            if (document.selection)
            {
                Indom.focus();
                var sel = document.selection.createRange();
                text = ""+text+"";
                sel.text = text;
                sel.select();
            }
            else if (Indom.selectionStart || Indom.selectionStart == '0')
            {
                var startPos = Indom.selectionStart;
                var endPos = Indom.selectionEnd;
                var restoreTop = Indom.scrollTop;
                text = ""+text+"";
                Indom.value = Indom.value.substring(0, startPos) + text + Indom.value.substring(endPos,Indom.value.length);
                if (restoreTop > 0)
                {
                    Indom.scrollTop = restoreTop;
                }
                Indom.focus();
                Indom.selectionStart = startPos + text.length;
                Indom.selectionEnd = startPos + text.length;
            } else {
                Indom.value += text;
                Indom.focus();
            }
        },
        backAtCaret:function(Indom){
            //IE support
            if (document.selection)
            {
                var cursurPosition=-1;
                var pretext=Indom.value;
                Indom.focus();
                var range= document.selection.createRange();
                range.moveStart("character",-Indom.value.length);
                cursurPosition=range.text.length; //光标位置
                if(cursurPosition===0){
                    return;
                }
                var val=pretext.substring(0,cursurPosition-1)+pretext.substring(cursurPosition);
                Indom.value=val;
                range.move('character', cursurPosition-1);
                range.select();
            }
            else if (Indom.selectionStart || Indom.selectionStart == '0')
            {
                var startPos = Indom.selectionStart;
                var endPos = Indom.selectionEnd;
                var restoreTop = Indom.scrollTop;
                Indom.value = Indom.value.substring(0, startPos-1) + Indom.value.substring(endPos,Indom.value.length);
                if(restoreTop > 0)
                {
                    Indom.scrollTop = restoreTop;
                }
                Indom.focus();
                startPos--;
                Indom.selectionStart = startPos;
                Indom.selectionEnd = startPos;
            }else{
                Indom.value=Indom.value.substring(0,-1);
                Indom.focus();
            }
        },
        focus:function(ctrl,pos){
            var val=ctrl.value;
            if(Xl.isNumber(pos)){
                pos=parseInt(pos);
                if(pos<0){
                    pos=val.length+1+pos;
                }
                pos=pos<0?0:(pos>val.length?val.length:pos);
            }else{
                pos=val.length;
            }
            if(ctrl.setSelectionRange) {
                ctrl.focus();
                ctrl.setSelectionRange(pos,pos);
            } else if (ctrl.createTextRange){
                var range = ctrl.createTextRange();
                range.collapse(true);
                range.moveEnd('character', pos);
                range.moveStart('character', pos);
                range.select();
            }
        }

    };

    Xl.Event={
        __proxyevents__:{},
        __registevents__:[],
        __data__:{},
        setParam:function(key,value){this.__data__["__Inf__"+key]=value;},
        getParam:function(key){return this.__data__["__Inf__"+key];},
        hook:function(type,func){
            if(Xl.isEmpty(this.hookList)){this.hookList={};}
            if(!Xl.isFunction(this.hookList['hooklist-'+type])){
                this.hookList['hooklist-'+type]=func;
            }
        },
        tagger:function(type,param){
            if(Xl.isEmpty(this.hookList)){this.hookList={};}
            var func=this.hookList['hooklist-'+type];
            if(Xl.isFunction(func)){
                func.call(this,param);
            }
        },
        addProxyEvent:function(event,callback){
            if(Xl.isFunction(callback)&&Xl.isString(event)){
                this.__proxyevents__[event]=callback;
            }
        },
        removeProxyEvent:function(event){
            delete this.__proxyevents__[event];
        },
        registProxyEvent:function(proxydom,defaultevent,stopdocumentclick,eachclick){

            if(Xl.isString(proxydom)){
                proxydom=Xl.trim(proxydom);
                if(!/[\.\>\s]/g.test(proxydom)){
                    proxydom=Xl.E(proxydom);
                    if(!proxydom){
                        Xl.alert("没有指定代理对象","error");
                        return;
                    }
                }
            }
            defaultevent=defaultevent||'click';
            if($.inArray(defaultevent,['click','touchstart','touchend','mousedown','mouseup','dblclick'])==-1){
                Xl.alert("代理事件不合法");
                return;
            }
            var t=this;
            this.__registevents__.push({dom:proxydom,event:defaultevent});
            $(proxydom)[defaultevent](function(e){
                var en=Xl.sgData(e.target,'event');
                var eventhost=e.target;
                if(!en){
                    eventhost=Xl.bubbleFind(e.target,function(node){
                        if(en=Xl.sgData(node,'event')){
                            return true;
                        }
                        return false;
                    },proxydom);

                }
                if(en&&eventhost){
                    if(Xl.isFunction(t.__proxyevents__[en])){
                        t.__proxyevents__[en].call(t,eventhost,this,defaultevent,e);
                    }
                }
                if(!stopdocumentclick){
                    var funcs=Xl.getG('Event/rootclickFunc');
                    Xl.forIn(funcs,function(i,func){
                        if (Xl.isFunction(func)) {
                            func.call(document,e);
                        }
                    });
                }
                if(Xl.isFunction(eachclick)){
                    eachclick.call(this,e);
                }
                e.stopPropagation(); //阻止事件冒泡
            });
        },
        destroyProxyEvent:function(proxydom,defaultevent){
            //销毁绑定的事件
            if(defaultevent===null){
                $(proxydom).unbind();
                return;
            }
            defaultevent=defaultevent||'click';
            $(proxydom).unbind(defaultevent);
        },
        destroy:function(){

            this.__proxyevents__={};
            this.__data__={};
            Xl.forIn(this.__registevents__,function(i,v){

                if(v&&v.dom){
                    this.destroyProxyEvent(v.dom,v.event);
                }

            },this);
            this.__registevents__=[];

        }
    };
    //框架调度类
    Xl.Dispatch=function(){

    };

    Xl.extend(Xl.Dispatch,{
        bindValAndDom:bindValAndDom,
        BAD:bindValAndDom
    });


    Xl.Model=function(data){


        var _M=function(){
            this.data={};
            this.length=0;
            Xl.extend(this,Xl.Event); //继承

            this.init=function(){
                if(Xl.isObject(data)){
                    for(var i in data){
                        this.set(i,data[i]);
                        this.length++;
                    }
                }
            };
            this.set=function(key,value){

                var protectedvalue=null;
                var issamevalue=false;
                if(this.data[key]){
                    if(Xl.isFunction(value)){
                        issamevalue=value.value==this.data[key].value;
                    }else{
                        issamevalue=value==this.data[key].value;
                    }
                    protectedvalue=this.data[key].value;
                    this.data[key].prevalue=this.data[key].value;
                }
                //如果绑定的dom节点，注册绑定
                if(Xl.isFunction(value)&&value.isDispatch==1){

                    this.hook("change",this.changeMapToControl);
                    $(value.dom).unbind("change").on("change",this.changeMapToModel(key,value));
                    if(!Xl.isFunction(this.data[key])){
                        this.data[key]=Xl.extend(function(){},this.data[key]||{});
                    }
                    if(protectedvalue!==null&&!Xl.isUndefined(protectedvalue)){
                        if(!value.initHasVal) {
                            value.dom.value =protectedvalue||value.dom.value;
                            if(value.type==="html"){
                                value.dom.innerHTML=value.dom.value;
                            }
                            value.value=protectedvalue||value.dom.value;
                        }
                    }
                    if(value.tip){
                        if(value.value===""){
                            $(value.dom).addClass("tipstatus");
                        }
                        $(value.dom).unbind("focus").on("focus",function(e) {
                            var tv=$(this).val();
                            if(tv==value.tip){
                                $(this).val('').removeClass("tipstatus");
                            }
                        }).unbind("blur").on("blur",function(e){
                            var tv=$(this).val();
                            if(Xl.isEmpty(tv)){
                                $(this).val(value.tip).addClass("tipstatus");
                            }
                        });
                    }

                }else{
                    value={value:value};
                    this.data[key]=this.data[key]||{};
                }
                Xl.extend(this.data[key],value);
                if(Xl.isFunction(this.data[key])&&this.data[key].isDispatch==1){
                    if(!issamevalue){
                        this.tagger("change",this.data[key]);
                    }
                }
                if(!this.data.hasOwnProperty(key)){
                    this.length++;
                }
                return this;
            };
            this.getBindData=function(key){
                return this.data[key];
            };
            this.getDom=function(key){

                var bindData=this.getBindData(key);
                if(!bindData){
                    return null;
                }
                return bindData.dom;
            };
            this.get=function(key){
                return this.data[key]?this.data[key].value:null;
            };
            this.gets=function(isfilter,excepts){
                var data={};
                if(isfilter===true){
                    excepts=excepts||[];
                    if(Xl.isString(excepts)){
                        excepts=excepts.split(',');
                    }
                    if(!Xl.isArray(excepts)){
                        excepts=[];
                    }
                    Xl.forIn(this.data,function(i,v){
                        if(v.prevalue!=v.value){
                            data[i]=v.value;
                        }else{
                            if(Xl.inArray(i,excepts)){
                                data[i]=v.value;
                            }
                        }
                    });
                }else{
                    for(var key in this.data){
                        data[key]=this.data[key].value;
                    }
                }
                return data;
            };
            this.del=function(key){

                var d={};
                for(var i in this.data){
                    if(i==key){
                        this.length--;
                    }else{
                        d[i]=this.data[i];
                    }
                }
                this.data=d;
                return this;
            };
            this.changeMapToControl=function(param){

                if(param){
                    var tip=param.tip||'';
                    var value=param.value;
                    var callback=param.callback;
                    var type=param.type;
                    var dom=param.dom;
                    if(value!==""){
                        tip=value;
                    }
                    if(Xl.isFunction(callback)){
                        callback(param);
                    }else{
                        if(type=="html"){
                            $(dom).html(tip);
                        }else if(type=="text"){
                            $(dom).text(tip);
                        }else{
                            $(dom).val(tip);
                        }
                    }
                    if(Xl.isFunction(param.listenChange)){
                        if(Xl.isUndefined(dom.changeCallCount)){
                            dom.changeCallCount=0;
                        }
                        dom.changeCallCount++;
                        param.listenChange(value,param.dom,this,"changemaptocontrol");
                    }
                }

            };
            this.changeMapToModel=function(key,value){

                var t=this;
                return function(){
                    if(value.type=="value"){
                        if(value.nochange){return;}
                        var v=$(this).val();
                        var tip=Xl.sgData(this,"tip")||"";
                        if(v===tip){
                            v="";
                        }
                        var check=t.data[key].check||value.check;
                        if(!Xl.isUndefined(check)){
                            if(!t.checkValue(v,check,t.data[key].checkfail,t.data[key].valname||key,t.data[key].maxrange,t.data[key].owncheckmsg,this)){
                                return;
                            }
                        }
                        Xl.extend(t.data[key],{value:v});
                        if(Xl.isFunction(t.data[key].listenChange)){
                            if(Xl.isUndefined(t.data[key].changeCallCount)){
                                t.data[key].changeCallCount=0;
                            }
                            t.data[key].changeCallCount++;
                            t.data[key].listenChange(v,t.data[key].dom,t,"changemaptomodel");
                        }

                    }
                };
            };
            this.checkValue=function(value,check,checkfail,tip,maxw,owncheckmsg,binddom){

                if(Xl.isString(check)){
                    check=check.toLowerCase();
                    switch(check){
                        case 'number':
                            check=/\d+/g;
                            break;
                        case 'hanzi':
                            check=/[\u4e00-\u9fa5]+/g;
                            break;
                        case 'float':
                            check=/\d+\.\d+/g;
                            break;
                        case 'telphone':
                            check=/^\s*1(3|4|5|6|7|8)\d{9}\s*$/g;
                            break;
                        case 'email':
                            check=/\s*[a-zA-Z0-9]+@[a-z0-9]+?\.[a-z0-9]\s*/g;
                            break;
                    }
                }
                if(Xl.isRegExp(check)){
                    if(check.test(value)){
                        return true;
                    }else{
                        Xl.alert(tip+"格式不正确");
                        if(Xl.isFunction(checkfail)){
                            checkfail.call(this,binddom||null);
                        }
                        return false;
                    }
                }
                if(Xl.isFunction(check)){
                    if(check.call(this,value)){
                        return true;
                    }else{
                        if(!owncheckmsg){
                            Xl.alert(tip+"格式不正确");
                        }
                        if(Xl.isFunction(checkfail)){
                            checkfail.call(this,binddom||null);
                        }
                        return false;
                    }
                }
                var minw = 1;
                if (Xl.isString(maxw)) {
                    var mmarr = maxw.split('-');
                    minw = parseInt(mmarr[0]);
                    maxw = parseInt(mmarr[1]);
                } else {
                    maxw = maxw || 100;
                }
                if(Xl.isNumber(value)){
                    if(value<minw){
                        Xl.alert(tip + "不能小于"+ minw);
                        return false;
                    }
                    if(value>maxw){
                        Xl.alert(tip + "不能大于"+ maxw);
                        return false;
                    }
                }
                var len = value.length;
                if (len < minw) {
                    Xl.alert(tip + "不能低于" + minw + "个字");
                    return false;
                }
                if (len > maxw) {
                    Xl.alert(tip + "不能超过" + maxw + "个字");
                    return false;
                }
                return true;

            };

            this.init();

        };
        return new _M();
    };
    var __Classs__={};


    if(Xl.CONFIG.isDebug){
        Xl.Classs=__Classs__; //方便查看，上线时去掉
    }
    Xl.Class_Exist=function(classname){

        //检测类是否定义
        return __Classs__[classname]||null;

    };

    Xl.Class=function(classname,obj,parent){

        var isCreateClass=false; //是否是创建类
        if(Xl.isString(classname)){
            if(/\b(extends)\b/g.test(classname)){
                var ca=classname.split("extends");
                if(ca.length!=2){
                    throw new Error("类继承错误");
                }
                classname=Xl.trim(ca[0]);
                parent=Xl.trim(ca[1]); //父类
            }else{
                if(arguments.length==1){
                    if(Xl.isIE8()){
                        Xl.alert("浏览器版本太低！",'notice',100000);
                    }
                    if(__Classs__[classname]){
                        return __Classs__[classname];
                    }else{
                        throw new Error("类:"+classname+"没有定义");
                    }
                }
            }
            isCreateClass=true;
        }
        obj=Xl.isObject(classname)?(function(){parent=obj;return classname;})():obj;
        if(!Xl.isObject(obj)){
            obj={};
        }
        if(Xl.isString(parent)){
            parent=__Classs__[parent]||{};
        }
        if(Xl.isFunction(parent)){
            parent=parent.prototype;
        }

        if(!Xl.isObject(parent)){
            parent={};
        }
        var proxy=obj||{};
        Xl.inherit(parent,Xl.Event);
        Xl.inherit(proxy,parent);
        if(!proxy.hasOwnProperty('init')){

            proxy.init=function(){
            };
        }
        if(isCreateClass){
            var amfun=function(){
                Xl.forIn(proxy,function(i,v){
                    if(!Xl.isFunction(v)){
                        this[i]=Copy(v);
                    }
                },this);
                this.init.apply(this,arguments);
            };
            proxy.classname=classname;
            Xl.extend(amfun.prototype,proxy);
            __Classs__[classname]=amfun;
            return amfun; //创建一个类
        }else{
            if(this==Xl){
                throw new Error("Class类需要实例化");
            }
            Xl.extend(this,proxy);
            this.init(); //创建一个类并实例化
        }
    };

    Xl.extend(_W_,{Xl:Xl});



})(window);