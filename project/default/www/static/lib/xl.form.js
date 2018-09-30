// JavaScript Document
//表单类
(function(){

    "use strict";

    Xl.Form={
        addHidenField: function(id, name, value, istextarea) {
            if (istextarea) {
                $(id).append('<textarea style="display:none;" name="' + name + '" >' + value + '</textarea>');
            } else {
                $(id).append('<input name="' + name + '" value="' + value + '" type="hidden" />');
            }
        }

    };

    Xl.Control={

        __Select__ListDom:[],
        __Select__ListDom_Add:function(listdom){
            var isExist=false;
            Xl.forIn(Xl.Control.__Select__ListDom,function(i,v){
                if(v==listdom){
                    isExist=true;
                    return '__break';
                }
            });
            if(!isExist){
                Xl.Control.__Select__ListDom.push(listdom);
            }
        },
        __Select__ListDom_Remove:function(listdom){

            if(Xl.isNumber(listdom)){
                Xl.Control.__Select__ListDom.splice(listdom,1);
            }else{
                var pos=null;
                Xl.forIn(Xl.Control.__Select__ListDom,function(i,v){
                    if(v==listdom){
                        pos=i;
                        return '__break';
                    }
                });
                if(pos!==null){
                    Xl.Control.__Select__ListDom.splice(pos,1);
                }
            }

        },

        __Select__ListDom_Empty:function(){
            Xl.Control.__Select__ListDom=[];
        },
        __Select__ListDom_Gets:function(){
            return Xl.Control.__Select__ListDom;
        },
        Select:function(p){

            var SelectCtrl=function(){
                //选择控件
                var __t=this;
                __t.isInit=false;
                var datalist=p.datalist||null; //如果函数代表异步执行
                var sdCallback=p.sdCallback||null; //选中回调
                var dsCallback=p.dsCallback||null; //数据设置回调
                var ctrldom=p.ctrldom||null;   //绑定控件
                var wrapdom=p.wrapdom||null;   //容器dom
                var listdom=null,inputdom=null;
                var classname=p.classname||'';
                var zIndex=p.zIndex||1;
                var tip=p.tip||'';
                var value=p.value||'';  //选择的值
                var maxShowNum=p.maxShowNum||2;
                this.droptype=p.droptype||0;
                this.v_preInput=null; //之前输入值
                this.lock_Input=false;
                this.pointer_dropItem=0;
                this.count_dropItem=0;
                this.dropMode=p.dropMode||0;
                this.__isdisable=false;
                this.tabIndex=p.tabIndex;
                this._focusCallback=p.focusCallback||null;
                this._blurCallback=p.blurCallback||null;
                //选择器类型，0代表单选，1代表多选，2代表2级多选，3，输入选择下拉框.4，输入下拉框
                var selectTypeMap={select:0,mselect:1,dselect:2,dropselect:3,wselect:4};

                var defaultSdIndex=p.defaultSdIndex||null;

                if(Xl.isPlainObject(value)){
                    if(value.hasOwnProperty("index")){
                        defaultSdIndex=parseInt(value.index);
                    }
                    if(value.hasOwnProperty("value")){
                        value=value.value;
                    }else{
                        value="";
                    }
                }
                var width=0,height=0;

                width=p.width||parseInt($(wrapdom).width())||100;
                height=p.height||parseInt($(wrapdom).height())||25;

                var setCurrTipHook=p.setCurrTipHook||null;

                var paneltop=0;
                if(Xl.isUndefined(p.paneltop)){
                    paneltop=height;
                }else{
                    paneltop=parseInt(p.paneltop||0);
                    if(paneltop<0){
                        paneltop=height+paneltop;
                    }
                }
                //合并
                if(Xl.isNumber(p.type)){
                    this.type=p.type||0;
                }else{
                    this.type=selectTypeMap[p.type||'select'];
                }
                this.value=value;
                Xl.extend(this,Xl.Event);
                var splitlineheight=height-6;
                var xlicotop=Math.floor((height-4)/2);

                __t.create=function(){

                    this.draw(true);
                    this.__addEvent();

                };
                __t.draw=function(_isinit,callback){

                    if(Xl.isEmpty(ctrldom)){

                        var htm='';
                        htm+='<div class="g-select-control '+(classname||'')+'" style="z-index:'+zIndex+';'+(width?'width:'+width+'px;':'')+(height?'height:'+height+'px;':'')+'">';
                        htm+='<div data-event="select" class="g-select-input">';
                        if(this.type==3||this.type==4){
                            htm+='<div class="g-select-input-input g-select-input-type'+this.type+'" style="line-height:'+height+'px;width:'+width+'px">';
                        }else{
                            htm+='<div class="g-select-input-input g-select-input-type'+this.type+'" style="line-height:'+height+'px;width:'+(width-20)+'px;">';
                        }
                        if(this.type==3||this.type==4){
                            htm+='<input type="text" '+(this.tabIndex?'tabindex="'+this.tabIndex+'"':"")+'>';
                            htm+='<span data-event="hiddentip">'+tip+'</span>';
                        }else{
                            htm+=tip;
                        }
                        htm+='</div>';
                        if(this.type==3||this.type==4){
                            //htm+=tip;
                        }else{
                            htm+='<div class="g-select-input-butt">';
                            htm+='<a data-event="select" class="">';
                            htm+='<i style="top:'+xlicotop+'px;"></i>';
                            htm+='</a>';
                            htm+='</div>';
                        }
                        htm+='</div>';
                        htm+='<div class="g-select-list">';
                        htm+='</div>';
                        htm+='</div>';
                        $(wrapdom).html(htm);
                        ctrldom=$(wrapdom).children(".g-select-control").get(0);

                    }
                    listdom=$(ctrldom).children(".g-select-list").get(0);
                    inputdom=$(ctrldom).find(".g-select-input-input").get(0);
                    Xl.Control.__Select__ListDom_Add(listdom);
                    $(listdom).css({top:paneltop+"px"});

                    var border_width=parseInt($(inputdom).parent().css("border-left-width"))||0;
                    if(border_width){

                        $([ctrldom,listdom]).width(width+2*border_width);
                    }


                    __t.__initControl(callback);

                };
                __t.setDisabled=function(){
                    __t.__isdisable=true;
                };
                __t.setEnabled=function(){
                    __t.__isdisable=false;
                };
                __t.__initControl=function(callback){

                    if(this.type==3||this.type==4){
                        this.d_inputDom=$(inputdom).find("input").get(0);
                        this.d_inputTipDom=$(inputdom).find("span").get(0);
                        if(this.type==3){
                            return;
                        }
                    }

                    if(Xl.isFunction(datalist)){

                        if(Xl.isFunction(callback)){

                            datalist(function(d){
                                __t.dataToView(d);
                                callback();
                            });

                        }else{
                            datalist(__t.dataToView);
                        }
                    }else{
                        __t.dataToView(datalist||[]);
                        if(Xl.isFunction(callback)){
                            callback();
                        }
                    }


                };

                __t.setTip=function(xtip){

                    tip=xtip||'';
                    if(this.type==3||this.type==4){
                        $(this.d_inputTipDom).html(tip);
                    }else{
                        $(inputdom).html(tip);
                    }
                };
                __t.redraw=function(pm,isNeedCall){

                    //重新载入数据
                    if(!Xl.isUndefined(pm.datalist)){
                        datalist=pm.datalist;
                    }
                    if(!Xl.isUndefined(pm.value)){
                        this.value=pm.value;
                    }
                    this.draw(false,function(){
                        if(isNeedCall){
                            if(Xl.isFunction(sdCallback)){
                                sdCallback(__t.getParam("valuelist"));
                            }
                        }
                        if(!Xl.isUndefined(pm.tip)){
                            __t.setTip(pm.tip);
                        }
                    });

                };
                __t.__addEvent=function(){

                    __t.addProxyEvent("select",__t.popSelectPanel);
                    __t.addProxyEvent("option",__t.selectItem);
                    __t.addProxyEvent("submit",__t.submit);

                    if(__t.type==3){
                        __t.__addInputSelectEvent();
                    }else if(__t.type==4){
                        __t._addInputWSelectEvent();
                    }

                    __t.registProxyEvent(ctrldom,'click',true);
                    Xl.registGlobalEvent();
                    Xl.setG("Event/rootclickFunc>_g_closecontrol_"+Xl.getGuid(),function(){
                        if(listdom){
                            $(listdom).hide();
                            listdom.isShow=false;
                        }
                    });

                };
                __t.getCaretPos=function(Indom){

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
                };

                __t.__inciseKw=function(kw,inputCtl){
                    if(kw==""){
                        return "";
                    }
                    var caretPos=__t.getCaretPos(inputCtl);
                    if(caretPos===0){
                        var len=inputCtl.value.length;
                        if(len!=0){
                            caretPos=len;
                        }
                    }
                    var kwArr=kw.split(',');//切割
                    var startPos=0;
                    var endPos=0;
                    var rtKw="";
                    if(Xl.isUndefined(inputCtl.__bindKwData)){
                        inputCtl.__bindKwData={};
                    }
                    inputCtl.__bindKwData.kwStr=kw;
                    inputCtl.__bindKwData.kwArr=kwArr;
                    inputCtl.__bindKwData.caretPos=caretPos;
                    for(var i=0;i<kwArr.length;i++){
                        startPos=endPos+(i==0?0:1);
                        endPos=startPos+kwArr[i].length;
                        if(caretPos>=startPos&&caretPos<=endPos){
                            rtKw=kwArr[i];
                            inputCtl.__bindKwData.kw=rtKw;
                            inputCtl.__bindKwData.ci=i;
                            break;
                        }
                    }

                    return rtKw;

                };

                __t.__dealDropValueListForCallback=function(kw,inputCtl){

                    var vlist=this.getParam("valuelist");

                    if(Xl.isEmpty(kw)){
                        vlist=[];
                        inputCtl.__bindKwData={};
                        this.setParam("valuelist",[]);
                    }else{
                        var kwArr=kw.toString().split(',');
                        var tmpVList=[];
                        for(var i in kwArr){
                            for(var j in vlist){
                                if(vlist[j]['name']==kwArr[i]){
                                    tmpVList.push(vlist[j]);
                                    break;
                                }
                            }
                        }
                        vlist=tmpVList;
                    }
                    if(Xl.isFunction(dsCallback)){
                        dsCallback(vlist); //数据设置回调
                    }

                };
                __t.__addInputSelectEvent=function(){

                    this.addProxyEvent("hiddentip",this.e_hiddenTip);

                    $(this.d_inputDom).blur(function(){
                        var val=this.value;
                        if(/^\s*$/g.test(val)){
                            $(__t.d_inputTipDom).show();
                            if(Xl.isFunction(sdCallback)){
                                if(__t.dropMode!=1){
                                    sdCallback({value: '', name: ''});//清空选择
                                }
                            }
                        }
                        if(__t.dropMode==1){
                            var valArr=val.toString().split(',');
                            var tmpArr=[];
                            for(var i in valArr){
                                if(!Xl.inArray(valArr[i],tmpArr)){
                                    tmpArr.push(valArr[i]);
                                }
                            }
                            val=tmpArr.join(',');
                            if(/.+?,$/.test(val)){
                                this.value=val.substr(0,val.length-1);
                            }else{
                                this.value=val;
                            }
                            __t.__dealDropValueListForCallback(val,this);
                        }
                        if(Xl.isFunction(__t._blurCallback)){
                            __t._blurCallback(val);
                        }
                    }).focus(function(){

                        var val=this.value;
                        if(__t.dropMode==1){
                            if(val&&!/.+?,$/.test(val)){
                                val+=",";
                                this.value=val;
                            }
                            __t.__inciseKw(val,this);
                        }

                        if(Xl.isFunction(__t._focusCallback)){
                            __t._focusCallback(val);
                        }
                    });

                    $(this.d_inputDom).on("keyup",function(e){
                        var v=$(this).val();
                        if(v==__t.v_preInput){
                            __t.lock_Input=true;
                            return;
                        }
                        __t.lock_Input=false;
                        __t.v_preInput=v;
                        __t.pointer_dropItem=0;
                        if(Xl.isFunction(datalist)){

                            if(__t.dropMode==1){
                                //切割查询字符
                                v=__t.__inciseKw(v,this);
                            }

                            datalist(__t.dataToView,v||'');
                        }else{
                            __t.dataToView(datalist);
                        }
                    });

                    $(this.d_inputDom).on("keydown",function(e){

                        if(e.keyCode==38){
                            __t.moveDropSelectItem("up");
                        }else if(e.keyCode==40){
                            __t.moveDropSelectItem("down");
                        }else if(e.keyCode==13){
                            __t.dropItemselectByPointer();
                        }

                    });
                };
                __t._addInputWSelectEvent=function(){

                    this.addProxyEvent("hiddentip",this.e_hiddenTip);
                    $(this.d_inputDom).blur(function(){

                        var val=$(this).val();
                        if(/^\s*$/g.test(val)){
                            $(__t.d_inputTipDom).show();
                            if(Xl.isFunction(sdCallback)){

                                sdCallback({value:'',name:''});//清空选择
                            }
                        }else{

                            if(Xl.isFunction(sdCallback)){
                                sdCallback({value:val,name:val});
                            }

                        }
                        if(Xl.isFunction(__t._blurCallback)){
                            __t._blurCallback(val);
                        }

                    }).focus(function(e){

                        var val=$(this).val();
                        $(listdom).show();
                        listdom.isShow=true;
                        if(Xl.isFunction(datalist)){
                            datalist(__t.dataToView,v||'');
                        }else{
                            __t.dataToView(datalist);
                        }
                        if(Xl.isFunction(__t._focusCallback)){
                            __t._focusCallback(val);
                        }
                    }).click(function(e){
                        return false;
                    });

                };
                __t.moveDropSelectItem=function(direction){

                    if(direction=="up"){
                        this.pointer_dropItem--;
                        if(this.pointer_dropItem<=0){
                            this.pointer_dropItem=this.count_dropItem;
                        }
                    }else{
                        this.pointer_dropItem++;
                        if(this.pointer_dropItem>this.count_dropItem){
                            this.pointer_dropItem=1;
                        }
                    }
                    $(listdom).find("dl>dd").each(function(index, element) {
                        if(index==__t.pointer_dropItem-1){
                            $(this).addClass("on");
                            if(__t.droptype==1){
                                var txt=$(this).text();
                                __t.v_preInput=txt;
                                $(__t.d_inputTipDom).hide();
                                $(__t.d_inputDom).val(txt);
                            }
                        }else{
                            $(this).removeClass("on");
                        }
                    });

                };
                __t.dropItemselectByPointer=function(){

                    if(this.pointer_dropItem===0){
                        return;
                    }
                    $(listdom).find("dl>dd").each(function(index, element) {
                        if(index==__t.pointer_dropItem-1){
                            var txt=$(this).text();
                            __t.v_preInput=txt;
                            __t.setItem({name:txt,value:Xl.sgData(this,"value")});
                            if(Xl.isFunction(setCurrTipHook)){
                                txt=setCurrTipHook(txt);
                            }
                            $(__t.d_inputDom).val(txt);
                            $(__t.d_inputTipDom).hide();
                            __t.hideListPanel();

                            return false;
                        }
                    });

                };
                __t.e_hiddenTip=function(tid,pid){
                    $(tid).hide();
                    Xl.Dom.focus(this.d_inputDom);
                };
                __t.popSelectPanel=function(tid,pid){
                    if(__t.isInit){
                        if(__t.__isdisable){
                            return;
                        }
                        if(Xl.isUndefined(listdom.isShow)){
                            listdom.isShow=false;
                        }
                        if(!listdom.isShow){
                            $(listdom).show();
                        }else{
                            $(listdom).hide();
                        }
                        listdom.isShow=!listdom.isShow;
                        var listdoms=Xl.Control.__Select__ListDom_Gets();
                        Xl.forIn(listdoms,function(i,v){
                            if(v!=listdom){
                                if(v){
                                    v.isShow=false;
                                    $(v).hide();
                                }else{
                                    Xl.Control.__Select__ListDom_Remove(i);
                                }
                            }
                        });

                    }

                };
                __t.dataToView=function(dl){

                    __t.isInit=true;
                    if(Xl.isString(__t.value)){
                        __t.value=__t.value.split(',');
                    }else if(Xl.isNumber(__t.value)){
                        __t.value=[__t.value];
                    }
                    //根据类型不同分别映射
                    switch(__t.type){
                        case 0:
                            __t.dataToViewForSelect(dl);
                            __t.dataToSdForSelect();
                            break;
                        case 1:
                            __t.dataToViewForMSelect(dl);
                            __t.dataToSdForMSelect();
                            break;
                        case 2:
                            __t.dataToViewForDSelect(dl);
                            __t.dataToSdForDSelect();
                            break;
                        case 3:
                            __t.dataToViewForDropSelect(dl);
                            break;
                        case 4:
                            __t.dataToViewForWSelect(dl);
                            __t.dataToSdForWSelect();
                            break;
                    }

                };
                __t.dataToSdForWSelect=function(){

                    var valuelist={};
                    $(listdom).find("dl>dd").each(function(index, element) {
                        var value=Xl.sgData(this,"value");

                        if(Xl.inArray(value,__t.value)||(defaultSdIndex!==null&&defaultSdIndex===index)){
                            var name=$(this).text();
                            valuelist={value:value,name:name};
                            return false;
                        }
                    });
                    if(Xl.isEmpty(valuelist)){
                        if(__t.value){
                            valuelist={value:__t.value,name:__t.name};
                        }
                    }

                    __t.setParam("valuelist",valuelist);
                    if(valuelist.value) {
                        __t.setDropSelectText(valuelist.value||valuelist.name);
                    }

                };
                __t.setDropSelectText=function(text){

                    if(__t.type==3||__t.type==4){
                        $(__t.d_inputDom).val(text);
                    }

                };
                __t.reSelected=function(value){

                    if(value){
                        if(Xl.isString(value)){
                            __t.value=value.split(',');
                        }else if(Xl.isNumber(value)){
                            __t.value=[value];
                        }else{
                            __t.value=value;
                        }
                    }
                    switch(__t.type){
                        case 0:
                            __t.dataToSdForSelect();
                            break;
                        case 1:
                            __t.dataToSdForMSelect();
                            break;
                        case 2:
                            __t.dataToSdForDSelect();
                            break;
                        case 3:
                            __t.dataToSdForDropSelect();
                            break;
                        case 4:
                            __t.dataToSdForWSelect();
                            break;
                    }

                    //设置选择项目
                    if(Xl.isFunction(sdCallback)){
                        var v=__t.getParam("valuelist");
                        sdCallback(v);
                    }

                };
                __t.dataToSdForDropSelect=function() {
                    var name = '';
                    var value = '';
                    if(this.dropMode==1){

                        if(Xl.isArray(this.value)) {
                            this.setParam("valuelist", this.value);
                            var names=[];
                            Xl.forIn(this.value,function(i,v){
                                names.push(v.name);
                            });
                            $(__t.d_inputDom).val(names.join(','));
                        }
                    }else {
                        if (Xl.isArray(this.value)) {
                            $(listdom).find("dl>dd").each(function (index, element) {
                                var value = Xl.sgData(this, "value");
                                if (Xl.inArray(value, __t.value)) {
                                    name = $(this).text();
                                    value = Xl.sgData(this, "value");
                                }
                            });
                        } else {
                            name = this.value['name'];
                            value = this.value['value'];
                        }
                        __t.setItem({name: name, value: value});
                        if (Xl.isFunction(setCurrTipHook)) {
                            name = setCurrTipHook(name);
                        }
                        __t.v_preInput = name;
                        $(__t.d_inputDom).val(name);
                    }
                    if (value == "0" || Xl.isEmpty(value)) {
                        $(__t.d_inputTipDom).show();
                    } else {
                        $(__t.d_inputTipDom).hide();
                    }


                };
                __t.dataToSdForSelect=function(){

                    var valuelist=[];
                    $(listdom).find("dl>dd").each(function(index, element) {
                        var value=Xl.sgData(this,"value");

                        if(Xl.inArray(value,__t.value)||(defaultSdIndex!==null&&defaultSdIndex===index)){
                            var name=$(this).text();
                            valuelist={value:value,name:name};
                            return false;
                        }
                    });
                    __t.setParam("valuelist",valuelist);
                    __t.setSdToInputBox();

                };
                __t.dataToSdForMSelect=function(){

                    var valuelist=[];
                    $(listdom).find("dl>dd").each(function(index, element) {
                        var value=Xl.sgData(this,"value");
                        if(Xl.inArray(value,__t.value)){
                            var name=$(this).text();
                            valuelist.push({value:value,name:name});
                            //设置选中状态
                            Xl.sgData(this,"selected",1);
                            $(this).addClass("checked");
                        }
                    });
                    __t.setParam("valuelist",valuelist);
                    __t.setSdToInputBox();

                };
                __t.dataToSdForDSelect=function(){

                    var valuelist=[];

                    $(listdom).find("dl>dt").each(function(index, element) {
                        var value=Xl.sgData(this,"value");
                        if(Xl.inArray(value,__t.value)){
                            var name=$(this).text();
                            valuelist.push({value:value,name:name,parent:0});
                            Xl.sgData(this,"selected",1);
                            $(this).addClass("checked");
                            $(listdom).find('dl>dd[data-parent="'+value+'"]').each(function(index, element) {
                                var cdname=$(this).text();
                                var cdvalue=Xl.sgData(this,"value");
                                valuelist.push({value:cdvalue,name:cdname,parent:value});
                                Xl.sgData(this,"selected",1);
                                $(this).addClass("checked");
                            });
                        }

                    });
                    $(listdom).find('dl>dd').each(function(index, element) {

                        var value=Xl.sgData(this,"value");
                        if(Xl.inArray(value,__t.value)){
                            var sd=Xl.sgData(this,"selected");
                            if(sd!=1){
                                //选中
                                var name=$(this).text();
                                var parent=Xl.sgData(this,"parent");
                                valuelist.push({value:value,name:name,parent:parent});
                                Xl.sgData(this,"selected",1);
                                $(this).addClass("checked");
                            }
                        }

                    });
                    $(listdom).find("dl>dt").each(function(index, element) {

                        var value=Xl.sgData(this,"value");
                        var name=$(this).text();
                        var sd=Xl.sgData(this,"selected");
                        if(sd==1){
                            return true; //继续
                        }
                        var isallsd=1;
                        var ishavechild=0;
                        $(listdom).find('dl>dd[data-parent="'+value+'"]').each(function(index, element) {
                            var tsd=parseInt(Xl.sgData(this,"selected"))||0;
                            isallsd=isallsd&&tsd;
                            ishavechild=1;
                        });
                        if(isallsd&&ishavechild){
                            $(this).addClass("checked");
                            Xl.sgData(this,"selected",1);
                            valuelist.push({value:value,name:name,parent:0});
                        }

                    });

                    __t.setParam("valuelist",valuelist);
                    __t.setSdToInputBox();

                };
                __t.dataToViewForDropSelect=function(dl){

                    if(Xl.isArray(dl)){
                        this.count_dropItem=dl.length;
                    }else{
                        this.count_dropItem=0;
                    }

                    var htm=['<dl>'];
                    Xl.forIn(dl,function(i,v){
                        if(Xl.isUndefined(v.id)&&Xl.isUndefined(v.value)){
                            return '__continue';
                        }
                        if(Xl.isEmpty(v.value)){
                            v.value=v.id||0;
                        }
                        htm.push(['<dd title="',v.name||'','" data-event="option" data-value="',v.value,'">',v.name||'','</dd>'].join(''));

                    },this);

                    htm.push('</dl>');
                    listdom.isShow=1;
                    $(listdom).html(htm.join('')).show();

                };
                __t.dataToViewForWSelect=function(dl){

                    var htm=['<dl>'];
                    Xl.forIn(dl,function(i,v){

                        if(v.type==="split"){
                            htm.push(['<dd class="g-select-option-split">',v.name||'','</dd>'].join(''));
                        }else{
                            htm.push(['<dd title="',(v.name||''),'" data-event="option" data-value="',(v.name||v.value||''),'">',(v.name||v.value||''),'</dd>'].join(''));
                        }

                    },this);
                    htm.push('</dl>');
                    $(listdom).html(htm.join(''));

                };
                __t.dataToViewForSelect=function(dl){

                    var htm=['<dl>'];
                    Xl.forIn(dl,function(i,v){
                        if(Xl.isUndefined(v.id)&&Xl.isUndefined(v.value)){
                            return '__continue';
                        }
                        if(Xl.isEmpty(v.value)){
                            v.value=v.id||0;
                        }
                        if(v.type==="split"){
                            htm.push(['<dd class="g-select-option-split">',v.name||'','</dd>'].join(''));
                        }else{
                            htm.push(['<dd data-event="option" data-value="',v.value,'">',v.name||'','</dd>'].join(''));
                        }

                    },this);
                    htm.push('</dl>');
                    $(listdom).html(htm.join(''));

                };
                __t.dataToViewForMSelect=function(dl){

                    var htm=['<dl>'];
                    var text=[];
                    var j=0;
                    Xl.forIn(dl,function(i,v){
                        if(Xl.isUndefined(v.id)&&Xl.isUndefined(v.value)){
                            return '__continue';
                        }
                        if(Xl.isEmpty(v.value)){
                            v.value=v.id||0;
                        }
                        htm.push(['<dd data-event="option" data-value="',v.value,'">',v.name||'',
                            '<i></i></dd>'].join(''));

                    },this);
                    htm.push('</dl><a data-event="submit" class="g-select-submit">确定选择</a>');

                    $(listdom).html(htm.join(''));

                };
                __t.dataToViewForDSelect=function(dl){

                    //两级选择，选择父项的会自动选择所有子项，选择所有子项会自动选择父项
                    //dl结构
					/*
					 [{id:'id',name:'name','child':[{id:'id','name':'name'}]}]
					 */
                    var htm=['<dl>'];
                    var j=0;
                    Xl.forIn(dl,function(i,v){
                        if(Xl.isEmpty(v.value)){
                            v.value=v.id||0;
                        }
                        htm.push(['<dt data-event="option" data-value="',v.value,'">',v.name||'',
                            '<i></i></dt>'].join(''));
                        if(v.child&&Xl.isArray(v.child)){
                            Xl.forIn(v.child,function(ii,vv){

                                if(Xl.isEmpty(vv.value)){
                                    vv.value=vv.id||0;
                                }

                                htm.push(['<dd data-event="option" data-value="',vv.value,'" data-parent="',v.value,'">',vv.name||'',
                                    '<i></i></dd>'].join(''));
                            },this);
                        }

                    },this);

                    htm.push('</dl><a data-event="submit" class="g-select-submit">确定选择</a>');

                    $(listdom).html(htm.join(''));

                };
                __t.selectItem=function(tid,pid){

                    switch(__t.type){
                        case 0:
                            __t.selectItemForSelect(tid,pid);
                            break;
                        case 1:
                            __t.selectItemForMSelect(tid,pid);
                            break;
                        case 2:
                            __t.selectItemForDSelect(tid,pid);
                            break;
                        case 3:
                            __t.selectItemForDropSelect(tid,pid);
                            break;
                        case 4:
                            __t.selectItemForWSelect(tid,pid);
                            break;
                    }

                };
                __t.selectItemForWSelect=function(tid,pid){

                    var value=Xl.sgData(tid,"value");
                    var name=$(tid).text();
                    __t.setItem({name:name,value:value});
                    if(Xl.isFunction(setCurrTipHook)){
                        name=setCurrTipHook(name);
                    }
                    __t.v_preInput=name;
                    $(__t.d_inputTipDom).hide();
                    $(__t.d_inputDom).val(name);
                    __t.hideListPanel();

                };
                __t.selectItemForSelect=function(tid,pid){
                    __t.setInputValueBySd(tid,pid);
                };
                __t.selectItemForDropSelect=function(tid,pid){

                    if(this.dropMode==1){

                        //切割词模式选择器
                        var value=Xl.sgData(tid,"value");
                        var name=$(tid).text();
                        var bindKwData=this.d_inputDom.__bindKwData||{};

                        if(bindKwData.kwStr){
                            //如果重复则返回
                            var dd = bindKwData.kwStr.split(',');
                            if($.inArray(name,dd) != -1){
                                __t.hideListPanel();
                            }
                        }

                        var ci=bindKwData.ci||0;
                        bindKwData.kwArr = bindKwData.kwArr||[];
                        bindKwData.kwArr[ci]=name;

                        //设置映射关系
                        var valuelist=this.getParam("valuelist")||[];
                        valuelist[ci]={value:value,name:name};

                        this.setParam("valuelist",valuelist);
                        $(__t.d_inputTipDom).hide();
                        $(__t.d_inputDom).val(bindKwData.kwArr.join(',')).focus();
                        __t.hideListPanel();

                        if(Xl.isFunction(sdCallback)){
                            sdCallback({value:value,name:name});
                        }

                    }else {
                        __t.setInputValueBySd(tid, pid, true);
                    }



                };
                __t.setInputValueBySd=function(tid,pid,isval){

                    var value=Xl.sgData(tid,"value");
                    var name=$(tid).text();
                    __t.setItem({name:name,value:value});
                    if(Xl.isFunction(setCurrTipHook)){
                        name=setCurrTipHook(name);
                    }
                    if(isval){
                        __t.v_preInput=name;
                        $(__t.d_inputTipDom).hide();
                        $(__t.d_inputDom).val(name);
                    }else{
                        $(inputdom).html(name);
                    }
                    __t.hideListPanel();

                };
                __t.hideListPanel=function(){
                    $(listdom).hide();
                    listdom.isShow=false;
                };
                __t.selectItemForMSelect=function(tid,pid){

                    var value=Xl.sgData(tid,"value");
                    var sd=Xl.sgData(tid,"selected");
                    if(sd==1){
                        $(tid).removeClass("checked");
                        Xl.sgData(tid,"selected",0);
                    }else{
                        $(tid).addClass("checked");
                        Xl.sgData(tid,"selected",1);
                    }


                };
                __t.selectItemForDSelect=function(tid,pid){

                    var value=Xl.sgData(tid,"value");
                    var sd=Xl.sgData(tid,"selected");
                    if(sd==1){
                        $(tid).removeClass("checked");
                        Xl.sgData(tid,"selected",0);
                    }else{
                        $(tid).addClass("checked");
                        Xl.sgData(tid,"selected",1);
                    }

                    value=Xl.sgData(tid,"value");
                    var parent=Xl.sgData(tid,"parent");

                    if(parent){
                        //选择子类
                        var isallsd=1;
                        $(listdom).find('dl>dd[data-parent="'+parent+'"]').each(function(index, element) {
                            var tsd=parseInt(Xl.sgData(this,"selected"))||0;
                            isallsd=isallsd&&tsd;
                        });
                        var pdom=$(listdom).find('dl>dt[data-value="'+parent+'"]').get(0);
                        if(pdom){

                            if(isallsd){
                                $(pdom).addClass("checked");
                                Xl.sgData(pdom,"selected",1);
                            }else{
                                $(pdom).removeClass("checked");
                                Xl.sgData(pdom,"selected",0);
                            }
                        }

                    }else{
                        //选择父类
                        $(listdom).find("dl>dd").each(function(index, element) {
                            var pid=Xl.sgData(this,"parent");
                            if(pid==value){
                                if(sd==1){
                                    $(this).removeClass("checked");
                                    Xl.sgData(this,"selected",0);
                                }else{
                                    $(this).addClass("checked");
                                    Xl.sgData(this,"selected",1);
                                }
                            }

                        });

                    }

                };
                __t.setItem=function(v){
                    __t.setParam("value",v.value);
                    __t.setParam("name",v.name);

                    $(inputdom).val(v.name);

                    if(Xl.isFunction(sdCallback)){
                        sdCallback(v);
                    }

                };
                __t.getItem=function(){
                    var value=__t.getParam("value");
                    var name=__t.getParam("name");
                    return {value:value,name:name};

                };
                __t.submit=function(){

                    //多选和二级多选确定选择
                    var sdlist=[];
                    $(listdom).find("dl>dd,dl>dt").each(function(index, element) {
                        var value=Xl.sgData(this,"value");
                        var pid=Xl.sgData(this,"parent")||0;
                        var name=$(this).text();
                        if(Xl.sgData(this,"selected")==1){
                            sdlist.push({value:value,name:name,pid:pid});
                        }
                    });
                    __t.setParam("valuelist",sdlist);
                    __t.setSdToInputBox();
                    if(Xl.isFunction(sdCallback)){
                        sdCallback(sdlist);
                    }
                    __t.hideListPanel();

                };
                __t.setSdToInputBox=function(){

                    //将用户选择的内容提取到input框中显示
                    var valuelist=__t.getParam("valuelist");
                    if(__t.type===0){
                        valuelist=valuelist||{};
                        var title=valuelist.name||tip;
                        $(inputdom).text(title);

                    }else{
                        valuelist=valuelist||[];
                        if(Xl.isEmpty(valuelist)){
                            $(inputdom).text(tip);
                            return;
                        }
                        var showtips=[];
                        Xl.forIn(valuelist,function(i,v){
                            if(i<maxShowNum){
                                showtips.push(v.name);
                            }else{
                                showtips.push('...');
                                return '__break';
                            }
                        });

                        $(inputdom).text(showtips.join(','));

                    }


                };

                __t.create();

            };

            return new SelectCtrl();


        }
    };


})();