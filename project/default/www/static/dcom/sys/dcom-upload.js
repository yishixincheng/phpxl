// JavaScript Document
/**
 * 系统组件，上传图片控件
 */

!function(factory){
    "use strict";
    var key="sys/upload";
    var name="图片上传组件";
    new factory(key,name);

}(function(dcom_key,dcom_name){


    if(!Xl.Class_Exist("SYS_DCOM_UPLOAD")){

        //定义图片上传类

        Xl.Class("SYS_DCOM_UPLOAD",{

            opType:{
                'NODEAL':0,
                'FORCE':1,
                'CUT':2, //压缩
                'RCUT':3, //反窗口压缩
                'WHCHECK':4,
                'WMAXLIMIT':5,
                'WMINLIMIT':6,
                'MINWH':7
            },

            init:function(p){

                this._guid=Xl.getGuid();
                Xl.Dcom.setBindData(dcom_key,"obj_"+this._guid,this);
                if(Xl.isFunction(p.getDcomObj)){
                    p.getDcomObj(this);
                }
                this.initEvn();
                this.setParams(p);
                this.regist();

            },
            initEvn:function(){

                if(Xl.Dcom.getBindData(dcom_key,"initEvn")){
                    return null;
                }
                Xl.Dcom.setBindData(dcom_key,"initEvn",true);
                //注册全局函数
                var __t=this;
                Xl.registGlobalEvent();
                Xl.setG("Event/rootUnloadFunc>_unloadtemppic", function () {
                    __t.unloadPageRun();
                });

            },
            setParams:function(p){

                var handle=Xl.E(p.handle);
                handle.guid=this._guid; //保存guid
                this.setParam("handle",handle); //操作对象
                this.noSetSetDefault("zIndex",p.zIndex,10000);
                this.noSetSetDefault("picW",p.picW,0);
                this.noSetSetDefault("picH",p.picH,0);
                this.noSetSetDefault("opType",Xl.isNumber(p.opType)?p.opType:this.opType[p.opType],0); //图片操作类型
                this.noSetSetDefault("crop",p.crop,null);
                this.noSetSetDefault("watermark",p.watermark,null);
                this.noSetSetDefault("beforeHook",p.beforeHook,null);
                this.noSetSetDefault("mouseoverHook",p.mouseoverHook,null);
                this.noSetSetDefault("mouseoutHook",p.mouseoutHook,null);
                this.noSetSetDefault("eachImageCallBack",p.eachImageCallBack,null);
                this.noSetSetDefault("progressCallback",p.progressCallback,null);
                this.noSetSetDefault("succCallBack",p.succCallBack||null);
                this.noSetSetDefault("failCallBack",p.failCallBack||null);
                this.noSetSetDefault("uploadUrl",p.uploadUrl||"/dcom/sys/upload"); //和组件目录保持一致
                this.noSetSetDefault("unloadUrl",p.unloadUrl||"/dcom/sys/upload/unload"); //页面卸载清理未使用的图片
                this.noSetSetDefault("submitType",p.submitType,(function(){
                    if (window.File && window.FileList && window.FileReader && window.Blob) {
                        return 0;//ajax提交
                    } else {
                        return 1; //表单提交
                    }
                })());
                this.noSetSetDefault("maxCount",p.maxCount,1); //最多可以选择的图片个数
                this.noSetSetDefault("postData",p.postData,null); //附加的提交参数
                this.noSetSetDefault("btn_Left",p.btn_Left,0);
                this.noSetSetDefault("btn_Right",p.btn_Right,0);

                //系统自带参数
                this.setParam("formName","dcom_sys_upload_form_"+this._guid);
                this.setParam("formIframe","dcom_sys_upload_iframe_"+this._guid);
                this.setParam("overrideBtnName","dcom_sys_upload_form_"+this._guid+"_file");
                this.setParam("uploadingClassName","dcom-sys-upload-imgctrl-uploading_"+this._guid);

            },
            noSetSetDefault:function(k,v,dt){

                if(Xl.isUndefined(v)){
                    this.setParam(k,dt);
                }else{
                    this.setParam(k,v);
                }

            },
            unloadPageRun:function(){

                //页面卸载时清除未使用的图片
                var ryImgCodes=Xl.Dcom.getBindData(dcom_key,"ryImgCodes")||[];
                if (ryImgCodes.length > 0) {
                    Xl.request(this.getParam("unloadUrl"), {imgcodes:ryImgCodes}, 'json', 'post',null, false);
                }

            },
            regist:function(){

                //注册事件
                var __t=this;
                var handle=this.getParam("handle");
                if(!handle){
                    Xl.alert("没有绑定Dom对象");
                    return null;
                }
                var $_handle=$(handle);
                __t.$_handle=$_handle;
                $_handle.mouseenter(function(){
                    __t.posFileControl();
                    var oft = __t.$_handle.offset();
                    if (Xl.getBrowerV().ie) {
                        oft = {
                            left: oft.left+__t.getParam("btn_Left"),
                            top: oft.top+__t.getParam("btn_Right")
                        };
                    }
                    $("#"+__t.getParam("overrideBtnName")).css({
                        zIndex:__t.getParam("zIndex")
                    }).offset(oft).width($_handle.width()).height($_handle.height());
                });

            },
            posFileControl:function () {
                var __t=this;
                __t.uploadImgArr=[];
                var formName=__t.getParam("formName");
                var formIframe=__t.getParam("formIframe");
                var domform = Xl.E(formName+"_box");
                if (!domform) {
                    var postiframe = Xl.E(formIframe+"_box");
                    if (!postiframe) {
                        postiframe = Xl.addDivToBody(formIframe+"_box");
                        $(postiframe).html('<iframe name="'+formIframe+'" style="display:none;"></iframe>');
                    }
                    var domformbox = Xl.addDivToBody(formName+"_box");
                    domformbox.innerHTML = ['<form id="',formName,'" enctype ="multipart/form-data" >',
                        '<div style="display:none;" id="',formName+"_values",'"></div>',
                        '<input type="hidden" name="FORMHASH" value="', $_FORMHASH, '">',
                        '<input type="file" id="',formName,'_file" name="dcomfile" class="dcom-sys-upload-picfilecontrol" multiple >',
                        '</form>'].join('');

                    domformbox.className="dcom_sys_upload_form_box";
                    __t.$_fileDom = $(Xl.E(formName+"_file"));

                    if (__t.getParam("submitType")==0) {
                        //Ajax提交
                        __t.$_fileDom.change(function(e){
                            __t.$_handle.addClass("dcom-sys-upload-imgctrl-uploading");
                            __t.getFiles(e);
                        });

                    } else {
                        __t.$_fileDom.change(function (e) {

                            if(__t._isCheckRepeat) {
                                if (__t.isInPicListCache(this.value)) {
                                    Xl.alert("该图片已经上传，请更换一张");
                                    return;
                                }
                            }
                            if (Xl.isFunction(__t.getParam("beforeHook"))) {
                                __t.getParam("beforeHook").call(__t);
                            }
                            __t.$_handle.addClass("dcom-sys-upload-imgctrl-uploading");

                            __t.getFiles(e);

                        });
                    }
                    __t.$_fileDom.mouseover(function () {
                        if (Xl.isFunction(__t.getParam("mouseoverHook"))) {
                            __t.getParam("mouseoverHook").call();
                        }
                    }).mouseout(function () {
                        if (Xl.isFunction(__t.getParam("mouseoutHook"))) {
                            __t.getParam("mouseoutHook").call();
                        }
                    });

                }

            },
            isInPicListCache:function (filename) {
                var piclistcache=Xl.Mem.get("picupload_piclistcache")||[];
                if(!Xl.isArray(piclistcache)){
                    piclistcache=[];
                }
                var isexist=false;
                Xl.forIn(piclistcache,function(i,v){
                    if(Xl.isObject(v)){
                        if(v.filename==filename){
                            isexist=true;
                            return "__break";
                        }
                    }
                });
                __t._currfilename=filename;

                return isexist;

            },
            putToPicListCache:function(filename,imgcode){

                var piclistcache=Xl.Mem.get("picupload_piclistcache")||[];
                if(!Xl.isArray(piclistcache)){
                    piclistcache=[];
                }
                piclistcache.unshift({filename:filename,imgcode:imgcode});
                piclistcache.slice(0,300);

                Xl.Mem.set("picupload_piclistcache",piclistcache);

            },
            getFiles:function(e){

                var __t=this;

                __t.isUpload = false;
                e = e || window.event;
                //获取file input的图片列表信息
                var files = e.target.files;
                if(Xl.isUndefined(files)){
                    __t.ieOldUpload(e.target);
                    return;
                }

                var reg = /image\/.*/i;
                var filenum=files.length;
                if(filenum>__t.getParam("maxCount")){
                    Xl.alert("最多只能选择" + __t.getParam("maxCount") + "张图片");
                    return;
                }
                var flen=files.length;
                var fi=0;
                files.shift=function(){
                    if(fi==flen){
                        files.length=0;
                        return null;
                    }
                    var f=files[fi];
                    fi++;
                    return f;
                };

                //钩子处理
                var i=0;
                var crop=__t.getParam("crop");
                function hook(f,callback,i){

                    if(!f){
                        callback();
                        return;
                    }
                    if (!reg.test(f.type)) {
                        Xl.alert("你选择不是图片文件");
                        hook(files.shift(),callback,i);
                        return;
                    }
                    i++;
                    __t.uploadImgArr.push(f); //压入数组

                    var reader = new FileReader();
                    reader.onload = (function (file,i){
                        //获取图片信息
                        var fileSize = (file.size / 1024).toFixed(2) + "K", fileName = file.name, fileType = file.type;
                        return function (e) {
                            var img = new Image();
                            img.addEventListener("load", imgLoaded, false);
                            img.src = e.target.result;
                            function imgLoaded() {
                                var imgWidth = parseInt(img.width);
                                var imgHeight = parseInt(img.height);
                                if (Xl.isFunction(__t.getParam("eachImageCallBack"))) {
                                    __t.getParam("eachImageCallBack")({
                                        src: e.target.result,
                                        filename: fileName,
                                        filesize: fileSize,
                                        filetype: fileType,
                                        width: imgWidth,
                                        height: imgHeight,
                                        index:i
                                    });
                                }

                                if(crop){

                                    //启动裁切框架，进行裁切

                                    if(imgWidth==crop.width&&imgHeight==crop.height){

                                        hook(files.shift(),callback,i);

                                        //无需裁切
                                        return;

                                    }

                                    var iscrop=false;

                                    Xl.Dcom.callc("cropper", "open", {
                                        picurl: img.src,
                                        piccode: null,
                                        cutwidth: crop.width,
                                        cutheight: crop.height,
                                        viewwidth: crop.viewwidth,
                                        viewheight: crop.viewheight,
                                        picwidth: imgWidth,
                                        picheight: imgHeight,
                                        callback: function (d) {
                                            if (Xl.isFunction(crop.callback)) {
                                                crop.callback(d);
                                            }
                                            crop.zoom=d.zoom||1;
                                            crop.x=d.x||0;
                                            crop.y=d.y||0;
                                            __t.setParam("crop",crop);
                                            iscrop=true;
                                            hook(files.shift(),callback,i);

                                        },
                                        closeCallBack:function(){

                                            if(!iscrop&&!__t.getParam("notmustcrop")){
                                                //没有裁切，则禁止,继续裁切下一张
                                                __t.uploadImgArr.pop();
                                                i++;
                                                hook(files.shift(),callback,i);
                                            }

                                        }
                                    });

                                }


                            }
                        };



                    })(f,i);
                    reader.readAsDataURL(f);

                    if(!crop){
                        hook(files.shift(),callback,i);
                    }


                }

                hook(files.shift(),function(){

                    if(__t.getParam("submitType")==0){
                        __t.uploadByAjax();
                        e.target.files=null;
                        $(e.target).val('');
                    }else{
                        __t.uploadByForm(e.target);
                    }

                },i);

            },
            uploadByAjax:function () {

                var __t=this;
                var j = 0;
                function func() {

                    if (__t.uploadImgArr.length > 0 && !__t.isUpload) {
                        var singleImg = __t.uploadImgArr[j];
                        var xhr = new XMLHttpRequest();
                        if (xhr.upload) {
                            xhr.upload.addEventListener("progress",
                                function (e) {
                                    if (e.lengthComputable) {
                                        var progI = Math.round(e.loaded * 100 / e.total);
                                        /*通知进度条函数*/
                                        if (Xl.isFunction(__t.getParam("progressCallback"))) {
                                            __t.getParam("progressCallback")({
                                                process: progI,
                                                index: j + __t.index
                                            });
                                        }
                                    }
                                }, false);
                            xhr.onreadystatechange = function (e) {
                                if (xhr.readyState == 4) {
                                    if (xhr.status == 200) {
                                        //因为progress事件是按一定时间段返回数据的，所以单独progress不可能100%的，在这设置传完后强制设置100%
                                        try {
                                            var result = $.parseJSON(xhr.responseText);
                                            result['index'] = j + __t.index;
                                            if (result.status === true || result.status == "success") {
                                                //上传成功
                                                Xl.alert("上传图片成功", "right");
                                                var imgcode = result.imgcode;
                                                __t.addRyImageCode(imgcode);
                                                if (Xl.isFunction(__t.getParam("succCallBack"))) {
                                                    __t.$_handle.removeClass("dcom-sys-upload-imgctrl-uploading");
                                                    __t.getParam("succCallBack")(imgcode, result.picurl, result.width, result.height,__t.$_handle);
                                                    // $(".dcom_sys_upload_form_box").remove();
                                                }
                                            } else {
                                                Xl.alert(result.msg || '上传失败', "error");
                                                if (Xl.isFunction(__t.getParam("failCallBack"))) {
                                                    __t.$_handle.removeClass("dcom-sys-upload-imgctrl-uploading");
                                                    __t.getParam("failCallBack").call(__t);
                                                    // $(".dcom_sys_upload_form_box").remove();
                                                }
                                            }
                                            if (j < __t.uploadImgArr.length - 1) {
                                                j++;
                                                func();
                                            }
                                            
                                        }catch(error){
                                            __t.$_handle.removeClass("dcom-sys-upload-imgctrl-uploading");
                                        }
                                    } else {
                                        Xl.alert("上传失败", "error");
                                        __t.$_handle.removeClass("dcom-sys-upload-imgctrl-uploading");
                                    }
                                }
                                $(".dcom_sys_upload_form_box").remove();
                            };
                            var formdata = new FormData();
                            formdata.append("dcomfile", singleImg);
                            formdata.append("width", __t.getParam("picW"));
                            formdata.append("height", __t.getParam("picH"));
                            formdata.append("optype", __t.getParam("opType"));

                            var crop=__t.getParam("crop");
                            if(crop){
                                if(Xl.isObject(crop)){
                                    for(var i in crop){
                                        if(Xl.isFunction(crop[i])){
                                            continue;
                                        }
                                        formdata.append("crop["+i+"]",crop[i]);
                                    }
                                }
                            }
                            var watermark=__t.getParam("watermark");
                            if(watermark){
                                if(Xl.isObject(watermark)){
                                    for(var i in watermark){
                                        if(Xl.isFunction(watermark[i])){
                                            continue;
                                        }
                                        formdata.append("watermark["+i+"]",watermark[i]);
                                    }
                                }else if(Xl.isString(watermark)){
                                    formdata.append("watermark",watermark);
                                }
                            }

                            formdata.append("FORMHASH", $_FORMHASH);

                            var postData=__t.getParam("postData");

                            if(postData&&Xl.isPlainObject(postData)){

                                for(var k in postData){
                                    formdata.append(k,postData[k]);
                                }

                            }

                            // 开始上传
                            xhr.open("POST", __t.getParam("uploadUrl"), true);
                            xhr.setRequestHeader("X-Requested-With","xmlhttprequest");
                            xhr.send(formdata);
                        }
                    }
                }

                func();

            },
            ieOldUpload:function(inputDom){

                //兼容ie
                var __t=this;

                __t.uploadByForm(inputDom);


            },
            addRyImageCode:function(imgcode){


                var ryImgCodes=Xl.Dcom.getBindData(dcom_key,"ryImgCodes");

                if(Xl.isUndefined(ryImgCodes)){
                    ryImgCodes=[];
                    Xl.Dcom.setBindData(dcom_key,"ryImgCodes",ryImgCodes);
                }

                if (!Xl.inArray(imgcode, ryImgCodes)) {
                    //压到数组里
                    ryImgCodes.push(imgcode);
                }

            },
            getIsImg: function (path) {
                if (path) {
                    var picarr = path.split('.');
                    var filedot = picarr.pop();
                    filedot = filedot.toLowerCase();
                    if ($.inArray(filedot, ['exe', 'dll']) != -1) {
                        return false;
                    }
                }
                return true;
            },
            uploadByForm:function(thisid){

                var picpath = $(thisid).val();
                if (Xl.isEmpty(picpath)) {
                    return;
                }
                if (!this.getIsImg(picpath)) {
                    Xl.alert("请选择正确的图片格式", "error");
                    return;
                }
                var picw = this.getParam("picW");
                var pich = this.getParam("picH");
                var optype = this.getParam("opType");
                this.setKeyVals([{
                    name: 'width',
                    value: picw
                }, {
                    name: 'height',
                    value: pich
                },{
                    name: 'optype',
                    value: optype
                }
                ]);
                if(this.getParam("crop")){
                    this.setKeyVals("crop",this.getParam("crop"));
                }
                if(this.getParam("watermark")){
                    this.setKeyVals("watermark",this.getParam("watermark"));
                }
                this.setKeyVals("ctrlid","obj_"+this._guid);

                var postData=this.getParam("postData");

                if(postData&&Xl.isPlainObject(postData)){
                    for(var k in postData){
                        this.setKeyVals(k,postData[k]);
                    }
                }

                var formName=this.getParam("formName");
                var formIframe=this.getParam("formIframe");

                var domform = Xl.E(formName);
                domform.target = formIframe;
                domform.enctype = "multipart/form-data";
                domform.action = this.getParam("uploadUrl");
                domform.method = "post";
                domform.submit();
            },
            setKeyVals:function (keyvals, value) {

                var __t=this;
                if (!keyvals) {
                    return __t;
                }
                var formValuesName="#"+__t.getParam("formName")+"_values";
                if (Xl.isArray(keyvals)) {
                    $(formValuesName).empty();
                    for (var i in keyvals) {
                        var v = keyvals[i];
                        if (typeof v == "object") {
                            __t.setKeyVals(v.name,v.value);
                        }
                    }
                } else {
                    if(Xl.isPlainObject(value)){
                        for(var i in value){
                            __t.setKeyVals(keyvals+"["+i+"]",value[i]);
                        }
                    }else if(Xl.isArray(value)){
                        for(var i in value){
                            __t.setKeyVals(keyvals+"[]",value[i]);
                        }
                    }else{
                        Xl.Form.addHidenField(formValuesName, keyvals, value);
                    }
                }
                return __t;
            },
            callback:function (status, msg, imgcode, picurl, width, height) {

                var __t=this;
                this.$_handle.removeClass("dcom-sys-upload-imgctrl-uploading");
                if(__t._isCheckRepeat&&__t._currfilename){
                    __t.putToPicListCache(__t._currfilename,imgcode);
                }
                if (status == "fail") {
                    Xl.alert(msg, "error");
                    if (Xl.isFunction(__t.getParam("failCallBack"))) {
                        __t.getParam("failCallBack").call(__t);
                    }
                    return;
                }
                Xl.alert("上传图片成功", "right");
                __t.addRyImageCode(imgcode);
                if (Xl.isFunction(__t.getParam("succCallBack"))) {
                    __t.getParam("succCallBack")(imgcode, picurl, width, height);
                    $(".dcom_sys_upload_form_box").remove();
                }
            },
            removeControl:function(){

                var formName=this.getParam("formName");
                $(Xl.E(formName+"_box")).remove();
                this.$_handle.off(); //解绑事件

                return this;

            }



        });


    }

    var __this=this;
    __this.apis=['open']; /*对外结构*/
    Xl.Dcom.addCom(dcom_key,this);//注册组建

    __this.callouti=function(oiname,param){

        //调用接口,必须函数
        if(!Xl.inArray(oiname,__this.apis)){
            alert("调用接口不存在","error");
            return;
        }
        if(Xl.isFunction(__this['api_'+oiname])){
            __this['api_'+oiname](param||'');//调用接口
        }else{
            alert("调用接口没实现");
        }

    };
    __this.api_open=function(param){


          new (Xl.Class("SYS_DCOM_UPLOAD"))(param);


    };

});