// JavaScript Document

(function(){
    //正则表达式验证规则
    function getRegexpByName(name){

        var regexpstr=name;
        switch(name){
            case 'number':
                regexpstr="/^\\d+$/";
                break;
            case 'float':
                regexpstr="/^\\d+\\.\\d+$/";
                break;
            case 'int':
                regexpstr="/^[1-9]\\d+$/";
                break;
            case 'numeric':
                regexpstr="/^\\d+(\\.\\d+)?$/";
                break;
            case 'chinese':
                regexpstr='/^[\\u4e00-\\u9fa5]+$/';
                break;
        }
        return regexpstr;
    }

    function FS_Ctrl_Factory(ctrl,formset){
        var ctrltype=ctrl.type||"own";
        if(!Xl.inArray(ctrltype,['input','select','textarea','checkbox','radio','own','upload','group'])){
            ctrltype="own";
        }
        ctrltype = ctrltype.toLowerCase();
        ctrltype = ctrltype.replace(/\b\w+\b/g, function (word) {
            return word.substring(0, 1).toUpperCase() + word.substring(1);
        });
        var className="__FS_"+ctrltype+"Ctrl";
        if(!Xl.isUndefined(ctrl.ownClass)){
            if(Xl.isString(ctrl.ownClass)){
                className=ctrl.ownClass
            }else if(Xl.isFunction(ctrl.ownClass)){
                return new ctrl.ownClass(ctrl,formset);
            }
        }

        return new (Xl.Class(className))(ctrl,formset);


    }
    Xl.Class("__FS_BaseCtrl",{
        type:'',
        arr_NoAllowParam:['formset'],
        //设置宽高
        setXy:function(x,y,z){
            if(!Xl.isUndefined(x)) {
                this._x = x;
                $(this.ctrwrapdom).css({left:x+"px"});
            }
            if(!Xl.isUndefined(y)) {
                this._y = y;
                $(this.ctrwrapdom).css({top:y+"px"});
            }
            if(z) {
                this._z = z;
            }
            if(this._z){
                $(this.ctrwrapdom).css({zIndex:this._z});
            }
        },
        getMustClassName:function(){
            if(this._ismust){
                return 'ismust';
            }
            return '';
        },
        createCtrWrap:function(){

            var odiv = document.createElement("div");
            odiv.guid = Xl.getGuid();
            odiv.className="g-formctrlnode";
            if(this._className){
                odiv.className+=" "+this._className;
            }
            odiv.style.position=this._layout;
            (this._groupwrap||this.containerdom).appendChild(odiv);
            return odiv;
        },
        getParseFieldCheckItmeIsMust:function(fieldcheckitem){

            if(Xl.isUndefined(fieldcheckitem)){
                return false;
            }
            if(Xl.isPlainObject(fieldcheckitem.relyon)){
                if(fieldcheckitem.relyon.key){
                    var bindkeyvalue=this._formset.getValueByKey(fieldcheckitem.relyon.key);
                    if(!Xl.isUndefined(fieldcheckitem.relyon.value)){
                        if(fieldcheckitem.relyon.value==bindkeyvalue){
                            return fieldcheckitem.ismust||false;
                        }else{
                            return !fieldcheckitem.ismust; //取反
                        }
                    }else if(Xl.isArray(fieldcheckitem.relyon.values)){

                        if(Xl.inArray(bindkeyvalue,fieldcheckitem.relyon.values)){
                            return fieldcheckitem.ismust||false;
                        }else{
                            return !fieldcheckitem.ismust; //取反
                        }

                    }
                }
            }else{
                return fieldcheckitem.ismust||false;
            }

            return false;

        },
        setMustFlag:function(must){

            if(Xl.isUndefined(must)){

                //未定义通过
                var fieldcheck=this.getFieldCheck();
                if(Xl.isUndefined(fieldcheck)){
                    must=false;
                }else{
                    if(Xl.isPlainObject(fieldcheck)){
                        must=this.getParseFieldCheckItmeIsMust(fieldcheck);
                    }else if(Xl.isArray(fieldcheck)){
                        Xl.forIn(fieldcheck,function(i,fieldcheckitem){
                            if(fieldcheckitem.ismust==this.getParseFieldCheckItmeIsMust(fieldcheckitem)){
                                must=fieldcheckitem.ismust;
                                return '__break';
                            }else{
                                must=!fieldcheckitem.ismust;
                            }
                        },this);

                    }
                }
            }
            this._ismust=must||false;
            if(this._ismust==true){
                $(this.ctrwrapdom).find(".g-formctrl-must").addClass("ismust");
            }else if(Xl.isUndefined(this._ismust)||this._ismust==false){
                $(this.ctrwrapdom).find(".g-formctrl-must").removeClass("ismust");
            }
        },
        //未设置默认值
        noSetSetDefault:function(k,v,dt){

            if(Xl.isUndefined(v)){
                this[k]=dt;
                return;
            }
            this[k]=v;
        },
        //提取参数
        extractParam:function(param,formset){
            this._className=param.className||'';
            this._key=param.key;
            this._x=param.x;
            this._y=param.y;
            this._z=param.z;
            this._formset=formset;
            this._tip=param.tip||'';
            this._title=param.title;
            this._groupkey=param._groupkey||"";
            this._groupwrap=param._groupwrap||null;
            this._tabindex=param.tabindex||0;
            this._layout=param._layout||"absolute";
            this.noSetSetDefault('_value',param.value,"");
            this.noSetSetDefault("_name",param.name);
            this.noSetSetDefault("_ismust",param.ismust,false);
            this.noSetSetDefault("_disabled",param.disabled,false);
            this.noSetSetDefault("_hint",param.hint,'');
            this.noSetSetDefault("_unit",param.unit,'');
            if(Xl.isUndefined(param.titleext)){
                this._titleext=formset._titleext;
            }else{
                this._titleext=param.titleext||'';
            }
            if(Xl.isUndefined(this._title)){
                if(this._name){
                    this._title=this._name+this._titleext;
                }else{
                    this._title='';
                }
            }
            if(!Xl.isUndefined(param.fieldcheck)){
                this.setFieldCheck(param.fieldcheck);
            }
        },
        //设置文件验证
        setFieldCheck:function(fieldcheck){

            this._data_fieldcheck=fieldcheck;
        },
        //获取验证文件
        getFieldCheck:function(){

            return this._data_fieldcheck;
        },
        //获取模型
        getModel:function(){
            return this._formset._model;
        },
        //设置模型值
        setModelValue:function(value){

            if(Xl.isUndefined(value)){
                return;
            }
            this._formset._model.set(this._key,value);
        },
        //获取模型值
        getModelValue:function(){
            return this._formset._model.get(this._key);
        },
        setCtrlValue:function(value){
            //设置控件的值
            this.setModelValue(value);
        },
        //设置控件参数
        setCtrlParam:function(param,value){

            if(Xl.isUndefined(this.arr_NoAllowParam)){
                this.arr_NoAllowParam=[];
            }
            if(Xl.inArray(param,this.arr_NoAllowParam)){
                throw("ctrl is not promise set param:"+param);
            }
            if(Xl.isFunction(this['hook_sP_'+param])){
                this['hook_sP_'+param](value);
            }
            this['_'+param]=value;

        },
        //显示
        show:function(){
            $(this.ctrwrapdom).show();
        },
        //隐藏
        hide:function(){
            $(this.ctrwrapdom).hide();
        },
        //删除
        remove:function(){

            $(this.ctrwrapdom).remove();
            this.destroy();
        },
        //销毁
        destroy:function(){
            $(this.ctrwrapdom).off().remove(); //移除事件并销毁
            if(Xl.isFunction(this.destroyEvent)){
                this.destroyEvent();//销毁注册事件
            }
        },
        hook_sP_className:function(className){
            $(this.ctrwrapdom).removeClass(this._className).addClass(className);
        },
        hook_sP_title:function(title){

            var titleDom=$(this.ctrwrapdom).find(".g-formctrl-title").get(0);
            if(titleDom){
                $(titleDom).html(title);
            }
        },
        hook_sP_tip:function(tip){

            var tipDom=$(this.ctrwrapdom).find(".g-formctrl-tip").get(0);
            if(tipDom){
                $(tipDom).html(tip||'');
            }
        },
        checkData:function(){

            //验证数据
            var fieldcheck=this.getFieldCheck();
            var must=true,tip='',must_excepts='',must_includes='',regexp='',regexp_tip='';
            if(Xl.isUndefined(fieldcheck)){
                must=this._ismust||false;
            }
            else{
                if(Xl.isPlainObject(fieldcheck)){
                    must=this.getParseFieldCheckItmeIsMust(fieldcheck);
                    tip=fieldcheck.tip||'';
                    must_excepts=fieldcheck.must_excepts||'';
                    must_includes=fieldcheck.must_includes||'';
                    regexp=fieldcheck.regexp||null;
                    regexp_tip=fieldcheck.regexp_tip||'';
                }else if(Xl.isArray(fieldcheck)){
                    Xl.forIn(fieldcheck,function(i,fieldcheckitem){
                        tip=fieldcheckitem.tip||'';
                        must_excepts=fieldcheckitem.must_excepts||'';
                        must_includes=fieldcheckitem.must_includes||'';
                        regexp=fieldcheckitem.regexp||null;
                        regexp_tip=fieldcheckitem.regexp_tip||'';
                        if(fieldcheckitem.ismust==this.getParseFieldCheckItmeIsMust(fieldcheckitem)){
                            must=fieldcheckitem.ismust;
                            return '__break';
                        }else{
                            must=!fieldcheckitem.ismust;
                        }
                    },this);

                }
            }
            var name=this._name||this._title||this._key||"";
            if(!tip){
                tip=name;
                //判断必选/必填项
                if(Xl.inArray(this.type,['select','checkbox','radio'])){
                    tip+='必选';
                }else{
                    tip+='必填';
                }
            }
            var val=this.getModelValue();
            if(must){
                if(!val){
                    if(Xl.isEmpty(val)){
                        Xl.alert(tip);
                        return false;
                    }else if(must_excepts){
                        if(Xl.isString(must_excepts)){
                            must_excepts=must_excepts.split(',');
                        }
                        if(Xl.isArray(must_excepts)){
                            if(!Xl.inArray(val,must_excepts)){
                                Xl.alert(tip);
                                return false;
                            }
                        }
                    }

                }else{

                    //存在
                    if(must_includes){
                        if(Xl.isString(must_includes)){
                            must_includes=must_includes.split(',');
                        }
                        if(Xl.isArray(must_includes)){
                            if(Xl.inArray(val,must_includes)){
                                Xl.alert(tip);
                                return false;
                            }
                        }
                    }

                }

            }
            //验证正则
            if(regexp){

                var regexpstr='';
                if(Xl.isString(regexp)){
                    regexpstr=regexp;
                }else{
                    regexpstr=regexp.js;
                }
                if(regexpstr) {
                    regexpstr=getRegexpByName(regexpstr);
                    regexpstr=regexpstr.replace(/(^\/)|(\/$)/g,'');
                    if(regexpstr) {
                        regexp = new RegExp(regexpstr);
                        if (!regexp.test(val)) {
                            Xl.alert(regexp_tip || (name + "格式不正确"));
                            return false;
                        }
                    }
                }
            }

            return true;
        }

    });

    //组容器
    Xl.Class("__FS_GroupCtrl extends __FS_BaseCtrl",{

        init:function(param,formset){

            this.ctrls=param.ctrls||[]; //容器 
            this._className=param.className||'';
            this._formset=formset;
            this._key=param.key;
            this._x=param.x;
            this._y=param.y;
            this._z=param.z;
            this._layout=param.layout||"absolute";
            this.createCtrl(param);
            this.parseAddCtrls();

        },
        createCtrWrap:function(){
            var odiv = document.createElement("div");
            odiv.guid = Xl.getGuid();
            odiv.className="g-formctrlgroup";
            if(this._className){
                odiv.className+=" "+this._className;
            }
            odiv.style.position="absolute";
            this.containerdom.appendChild(odiv);
            return odiv;
        },
        //创建组件
        createCtrl:function(param){

            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();
            this.setXy(this._x,this._y);

        },

        parseAddCtrls:function(){

            //解析添加组件
            Xl.forIn(this.ctrls,function(i,v){

                this.addCtrl(v);

            },this);

        },
        addCtrl:function(ctrl){

            //向组容器添加组件
            ctrl._groupkey=this._key;
            ctrl._groupwrap=this.ctrwrapdom;
            ctrl._layout=this._layout;
            this._formset._createCtrl(ctrl);

        }

    });

    Xl.Class("__FS_InputCtrl extends __FS_BaseCtrl",{
        //初始化
        init:function(param,formset){

            this.extractParam(param,formset);
            this.type='input';
            this._inputType=param.inputType||'text';
            this._inputTip=param.inputTip||'';
            this._focusCallback=param.focusCallback||null;
            this._blurCallback=param.blurCallback||null;
            this._changeCallback=param.changeCallback||null;
            this._autoComplete=param.autoComplete||false;
            this.createCtrl(param);
            this.bindModel();
            this.addEvent();
        },
        createCtrl:function(param){

            var __t=this;
            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();
            this.setXy(this._x,this._y);
            var mustclass=this.getMustClassName();
            var unithtml='<i></i>';
            var tabindexstyle='';
            if(this._unit){
                unithtml='<i class="g-formctrl-unit">'+this._unit+'</i>';
            }
            if(this._tabindex){
                tabindexstyle='tabindex="'+this._tabindex+'"';
            }
            var _disabled=""
            if(this._disabled){
                _disabled="disabled";
            }
            var _autocomplete='';

            if(this._inputType=="password"){
                if(!this._autoComplete){
                     _autocomplete='<input type="text" style="display:none"><input type="password" style="display:none">';
                }
            }

            $(this.ctrwrapdom).html(['<div class="g-formctrl-input">',
                '<span class="g-formctrl-must ',mustclass,'">*</span>',
                '<span class="g-formctrl-title">',this._title,'</span>',
                '<span class="g-formctrl-inputwrap" title="',(this._hint||''),'">',
                _autocomplete,
                '<input type="',this._inputType,'" ',_disabled,' data-bind="value:',this._key,'" '+tabindexstyle+'>',
                unithtml,
                '</span>',
                '<span class="g-formctrl-tip">',this._tip,'</span></div>'].join(''));


            this.inputDom=$(this.ctrwrapdom).find(".g-formctrl-inputwrap>input").get(0);
        },
        //绑定模型
        bindModel:function(){
            var dsp={value:this._value,dom:'value:'+this._key,pdom:this.ctrwrapdom};

            var __t=this;
            dsp.listenChange=function(value,_dm,_md,_ty){
                if(Xl.isFunction(__t._changeCallback)) {
                    __t._changeCallback(value,_dm,_md,_ty);
                }
            };

            this.setModelValue(Xl.Dispatch.BAD(dsp));
        },
        //添加事件
        addEvent:function(){

            var __t=this;
            $(this.inputDom).on("focus",function(e){
                var val=$(this).val();
                if(val==__t._inputTip){
                    $(this).val('');
                    __t.setModelValue('');
                }
                if(Xl.isFunction(__t._focusCallback)){
                    __t._focusCallback.call(this,val,e,this,__t);
                }
            }).on("blur",function(e){
                var val=$(this).val();
                if(Xl.isEmpty(val)){
                    __t.setCtrlValue('');
                }
                if(Xl.isFunction(__t._blurCallback)){
                    __t._blurCallback.call(this,val,e,this,__t);
                }
            });
            if(__t._inputTip&&Xl.isEmpty(__t._value)){
                $(this.inputDom).val(__t._inputTip);
            }
        },
        destroyEvent:function(){
            //销毁注册的事件
        },
        //设置控件值
        setCtrlValue:function(value){

            this.setModelValue(value);
            if(Xl.isEmpty(value)){
                $(this.inputDom).val(this._inputTip);
            }

        },
        bindCalendar:function(param){

            //装载日历控件
            param=param||{};
            var callback=param.callback||null;
            var today=param.today||null;
            var _inputdom=this.inputDom;
            $(_inputdom).off("keyup");
            $(_inputdom).off("click");
            $(_inputdom).on("keyup",function(){
                $(this).val('');
                __t.setModelValue("");
            });
            var __t=this;
            $(_inputdom).click(function(e){
                var v=__t.getModelValue();
                var bid=this;
                if(bid.mcalendar){
                    bid.mcalendar.showPanel();
                }else{
                    Xl.Dcom.callc("calendar","open",{
                        bindinputdom:this,
                        disablebeforeday:today,
                        getCalendar:function(obj){
                            bid.calendar=obj;
                        },
                        sdCallback:function(datestr){
                            __t.setModelValue(datestr);
                            if(Xl.isFunction(callback)){
                                callback(datestr);
                            }
                        }
                    });
                }

            });

        },
        bindMonthCal:function(param){

            //装载月日历控件
            param=param||{};
            var isdisable=param.isdisable||false;
            var callback=param.callback||null;
            var _inputdom=this.inputDom;
            $(_inputdom).off("keyup");
            $(_inputdom).off("click");
            var _inputdate=$(_inputdom).val()||"";
            if(isdisable){
                $(_inputdom).on("keyup",function(){
                    $(this).val('');
                    __t.setModelValue("");
                });
            }
            var __t=this;
            $(_inputdom).click(function(e){
                var v=__t.getModelValue();
                var bid=this;
                if(bid.mcalendar){
                    bid.mcalendar.showPanel();
                }else{
                    Xl.Dcom.callc("monthselect","open",{
                        bindinputdom:this,
                        getCalendar:function(obj){
                            bid.calendar=obj;
                        },
                        sdCallback:function(datestr){

                            __t.setModelValue(datestr);
                            if(Xl.isFunction(callback)){
                                callback(datestr);
                            }
                        }
                    });
                }

            });

        },

        hook_sP_unit:function(unit){

            var unitDom=$(this.ctrwrapdom).find(".g-formctrl-inputwrap>i").get(0);
            if(unitDom){
                if(unit){
                    $(unitDom).html(unit).attr("class","g-formctrl-unit");
                }else{
                    $(unitDom).html("").attr("class","");
                }
            }

        },
        hook_sP_hint:function(hint){

            $(this.ctrwrapdom).attr("title",hint||'');

        },
        hook_sP_tabindex:function(tabindex){

            if(tabindex) {
                this.inputDom.tabIndex = tabindex;
            }
        }


    });
    Xl.Class("__FS_SelectCtrl extends __FS_BaseCtrl",{

        init:function(param,formset){
            this.extractParam(param,formset);
            this.type='select';
            this._selectType=param.selectType||0;
            this._panelTop=param.panelTop||21;
            this._datalist=param.datalist||[];
            this._sdCallback=param.sdCallback||null;
            this._dropMode=param.dropMode||0;
            this._focusCallback=param.focusCallback||null;
            this._blurCallback=param.blurCallback||null;
            this.noSetSetDefault("_selectTip",param.selectTip,"");
            this.createCtrl(param);
            if(!Xl.isEmpty(this.datalist)){
                this.dataListMapToCtrl(this._datalist);
            }
            this.createSelectCtrol();
            this.setModelValue(this._value);

        },
        createCtrl:function(param){

            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();
            this.setXy(this._x,this._y);

            var mustclass=this.getMustClassName();

            $(this.ctrwrapdom).html(['<div class="g-formctrl-select">',
                '<span class="g-formctrl-must ',mustclass,'">*</span>',
                '<span class="g-formctrl-title">',this._title,'</span>',
                '<span class="g-formctrl-selectwrap" title="',(this._hint||''),'"></span>',
                '<span class="g-formctrl-tip">',this._tip,'</span></div>'].join(''));


            this._selectWrapDom=$(this.ctrwrapdom).find(".g-formctrl-selectwrap").get(0);

        },
        createSelectCtrol:function(){

            var __t=this;
            this._selectObj=Xl.Control.Select({
                datalist:this._datalist,
                wrapdom:this._selectWrapDom,
                paneltop:this._panelTop,
                type:this._selectType||0,
                dropMode:this._dropMode||0,
                tabIndex:this._tabindex,
                value:this._value,
                tip:this._selectTip,
                focusCallback:function(val) {
                    __t._formset._model.set(__t._key, '');
                    if(Xl.isFunction(__t._focusCallback)){
                        var rt=__t._focusCallback(val);
                        if(rt==="__break"){
                            return;
                        }
                    }
                },
                blurCallback:function(val){

                    if(Xl.isFunction(__t._blurCallback)){
                        var rt=__t._blurCallback(val);
                        if(rt==="__break"){
                            return;
                        }
                    }
                    if(Xl.inArray(__t._selectType,["3",3,"dropselect"])){
                        __t._formset._model.set(__t._key + "_name", val|| '');
                    }

                },
                dsCallback:function(d){
                    //数据设置回调
                    if(Xl.inArray(__t._selectType,["3",3,"dropselect"])){
                        if(__t._dropMode==1){

                            console.log(d);

                            if(Xl.isEmpty(d)){
                                __t.setModelValue("");
                            }else{
                                var ids=[];
                                Xl.forIn(d,function(i,v){
                                   ids.push(v.value||v.id);
                                });
                                __t.setModelValue(ids.join(','));
                                __t._formset._model.set(__t._key + "_arr",d);
                            }
                        }
                    }
                },
                sdCallback:function(d){

                    //选中回调
                    if(Xl.isEmpty(d)){
                        return;
                    }
                    if(Xl.inArray(__t._selectType,["1",1,"mselect"])){
                        var values=[];
                        Xl.forIn(d,function(i,v){
                            values.push(v.value);
                        });
                        __t.setModelValue(values.join(','));
                    }else if(Xl.inArray(__t._selectType,["3",3,"dropselect"])){

                        if(__t._dropMode!=1) {
                            __t._formset._model.set(__t._key + "_name", d.name || '');
                            __t.setModelValue(d.value);
                        }

                    }else{
                        __t.setModelValue(d.value);
                    }

                    if(Xl.isFunction(__t._sdCallback)){
                        __t._sdCallback(d);
                    }
                }
            })

        },
        dataListMapToCtrl:function (datalist) {
            this._datalist=datalist;
        },
        setDataSource:function(datasource){

            //设置数据源
            if(!Xl.isArray(datasource)) {
                if(Xl.isFunction(datasource)){
                    this._selectObj.redraw({datalist: datasource});
                }
                return;
            }
            if(datasource[0]&&datasource[0].datalist){

                Xl.forIn(datasource,function(i,v){
                    if(v.key){
                        var value=this._formset.getValueByKey(v.key);
                        if(Xl.inArray(value,v.values)){
                            this._selectObj.redraw({datalist:v.datalist});
                            return "__break";
                        }
                    }
                },this);

            }else {
                //直接映射
                this._selectObj.redraw({datalist: datasource});
            }
        },
        setCtrlValue:function(value){
            if(Xl.inArray(this._selectType,["3",3,"dropselect"])){
                if(this._dropMode==1){
                    //传递的是一个数据
                    if(Xl.isArray(value)){

                        var ids=[];
                        Xl.forIn(value,function(i,v){
                            ids.push(v.value||v.id);
                        });
                        this.setModelValue(ids.join(','));
                        this._formset._model.set(this._key + "_arr",value);

                    }
                }else{
                    if (Xl.isPlainObject(value)) {
                        this._formset._model.set(this._key + "_name", value.name);
                        this.setModelValue(value.value || value.id); //设置到模型
                    } else {
                        this._formset._model.set(this._key + "_name", "");
                    }
                }
            }else{
                this.setModelValue(value);
            }
            this._selectObj.reSelected(value);
        },
        hook_sP_panelTop:function(pt){

            this._selectObj.setPanelTop(pt);
        },
        hook_sP_selectTip:function(tip){

            this._selectObj.setTip(tip||'');
        },
        destroyEvent:function(){
            
        },
        getExtensionValue:function () {
        	console.log(1111)
        }

    });
    Xl.Class("__FS_TextareaCtrl extends __FS_BaseCtrl",{

        init:function(param,formset){
            this.extractParam(param,formset);
            this.type='textarea';
            this.createCtrl(param);
            this.bindModel();
        },
        createCtrl:function(param){

            var __t=this;
            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();
            this.setXy(this._x,this._y);

            var tabindexstyle='';
            if(this._tabindex){
                tabindexstyle='tabindex="'+this._tabindex+'"';
            }

            var mustclass=this.getMustClassName();
            var _disabled="";
            if(this._disabled){
                    _disabled="disabled";
            }
            $(this.ctrwrapdom).html(['<div class="g-formctrl-textarea">',
                '<span class="g-formctrl-must ',mustclass,'">*</span>',
                '<span class="g-formctrl-title">',this._title,'</span>',
                '<span class="g-formctrl-textareawrap" title="',(this._hint||''),'"><textarea data-bind="value:',this._key,'" ',_disabled,' ',tabindexstyle,'></textarea></span>',
                '<span class="g-formctrl-tip">',this._tip,'</span></div>'].join(''));

            this.inputDom=$(this.ctrwrapdom).find(".g-formctrl-textareawrap>textarea").get(0);

        },
        bindModel:function(){

            this.setModelValue(Xl.Dispatch.BAD({value:this._value,dom:'value:'+this._key,pdom:this.ctrwrapdom}));
        },
        setCtrlValue:function(value){
            this.setModelValue(value);
        },
        hook_sP_tabindex:function(tabindex){

            if(tabindex) {
                this.inputDom.tabIndex = tabindex;
            }
        }

    });
    Xl.Class("__FS_CheckboxCtrl extends __FS_BaseCtrl",{

        init:function(param,formset){

            this.extractParam(param,formset);
            this.type='checkbox';
            this._isSingle=param.mulSelect?false:true;
            this._sdCallback=param.sdCallback||null;
            this._datalist=param.datalist||null;
            this.createCtrl();
            if(!this._isSingle){
                this.createCheckBoxCtrl();
            }
            this.addEvent();
        },
        createCtrl:function(){

            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();
            this.setXy(this._x,this._y);
            var mustclass=this.getMustClassName();
            var html=['<div class="g-formctrl-checkbox">',
                '<span class="g-formctrl-must ',mustclass,'">*</span>',
                '<span class="g-formctrl-title">',this._title,'</span>',
                '<span class="g-formctrl-checkboxwrap">'];
            if(this._isSingle){
                html.push('<i class="g-formctrl-checkbox-item" data-event="checkbox_check_'+this._key+'"></i>');
            }
            html.push('</span><span class="g-formctrl-tip">'+this._tip+'</span></div>');

            $(this.ctrwrapdom).html(html.join(''));

            if(this._isSingle){
                this._mCheckBoxDom=$(this.ctrwrapdom).find(".g-formctrl-checkbox-item").get(0);
            }else{
                this._mCheckWrap=$(this.ctrwrapdom).find(".g-formctrl-checkboxwrap").get(0);
            }

            this.setCtrlValue(this._value||0);

        },
        addEvent:function(){

            if(this._isSingle) {
                this._formset.bindAddProxyEvent("checkbox_check_" + this._key, this.toggleChecked, this);
            }else{
                this._formset.bindAddProxyEvent("checkbox_check_"+this._key,this.toggleItemChecked,this);
            }

        },
        toggleItemChecked:function(tid,pid) {

            //选择check节点每一项
            var vlist=this.getModelValue()||[];
            var value=Xl.sgData(tid,"value");
            if(!Xl.isArray(vlist)){
                vlist=vlist?vlist.toString().split(','):[];
            }
            var i=-1; //位置
            Xl.forIn(vlist,function(k,v){
                if(v==value){
                   i=k;
                   return '__break';
                }
            });
            if(i==-1){
                vlist.push(value);
            }else{
                vlist.splice(i,1);
            }
            this.setCtrlValue(vlist.join(','));

        },
        toggleChecked:function(tid,pid){

            var v=this.getModelValue();
            if(!v){v=1;}else{v=0;}
            this.setCtrlValue(v);

        },
        setCtrlValue:function(value){

            this.setModelValue(value);
            if(this._isSingle){

                if(/^\d+$/.test(value)){
                    value=parseInt(value);
                }

                if(!value){
                    $(this._mCheckBoxDom).removeClass("checked");
                }else{
                    $(this._mCheckBoxDom).addClass("checked");
                }
            }else{
                //多选选项
                var vlist=[];
                if(Xl.isArray(value)){
                    vlist=value;
                }else{
                    if(value){
                        vlist=value.toString().split(',');
                    }
                }
                $(this._mCheckWrap).find(".g-formctrl-checkbox-item").each(function(i,el){
                    var currV=Xl.sgData(this,"value");
                    if(Xl.inArray(currV,vlist)){
                        $(this).addClass("checked");
                    }else{
                        $(this).removeClass("checked");
                    }
                });

            }

            if(Xl.isFunction(this._sdCallback)){
                this._sdCallback(value);
            }

        },
        createCheckBoxCtrl:function(){
            var html=[];
            Xl.forIn(this._datalist,function(i,v){
                html.push(['<label class="g-formctrl-checkbox-item" data-value="',v.value||v.id,'" data-event="checkbox_check_',this._key,'"><i></i>'+v.name+'</label>'].join(''));
            },this);

            $(this._mCheckWrap).html(html.join(''));
        },
        setDataSource:function(datasource){

            //设置数据源
            if(!Xl.isArray(datasource)) {
                return;
            }
            if(datasource[0]&&datasource[0].datalist){
                Xl.forIn(datasource,function(i,v){
                    if(v.key){
                        var value=this._formset.getValueByKey(v.key);
                        if(Xl.inArray(value,v.values)){
                            this._datalist=v.datalist;
                            this.createCheckBoxCtrl();
                            this.setCtrlValue(this.getModelValue());
                            return "__break";
                        }
                    }
                },this);

            }else {
                //直接映射
                this._datalist=datasource;
                this.createCheckBoxCtrl();
                this.setCtrlValue(this.getModelValue());
            }
        }

    });

    //radio控件
    Xl.Class("__FS_RadioCtrl extends __FS_BaseCtrl",{

        init:function(param,formset){
            this.extractParam(param,formset);
            this.type="radio";
            this._datalist=param.datalist||[];
            this._sdCallback=param.sdCallback||null;
            this.createCtrl();
            this.createRadioCtrl();
            this.setCtrlValue(this._value||0);
            this.addEvent();

        },
        createCtrl:function(){

            var __t=this;
            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();
            this.setXy(this._x,this._y);

            var mustclass=this.getMustClassName();

            $(this.ctrwrapdom).html(['<div class="g-formctrl-radiobox">',
                '<span class="g-formctrl-must ',mustclass,'">*</span>',
                '<span class="g-formctrl-title">',this._title,'</span>',
                '<span class="g-formctrl-radiowrap">',
                '</span>',
                '<span class="g-formctrl-tip">',this._tip,'</span></div>'].join(''));

            this._radioWrapDom=$(this.ctrwrapdom).find(".g-formctrl-radiowrap").get(0);

        },
        createRadioCtrl:function(){

            //创建radio控件
            var html=[];
            Xl.forIn(this._datalist,function(i,v){

                html.push(['<label class="g-formctrl-radio-item" data-value="',v.value||v.id,'" data-event="radio_check_',this._key,'"><i></i>'+v.name+'</label>'].join(''));

            },this);

            $(this._radioWrapDom).html(html.join(''));

        },
        setCtrlValue:function(value){

            this.setModelValue(value);

            $(this._radioWrapDom).find(".g-formctrl-radio-item").each(function(index){

                var bindval=Xl.sgData(this,"value");
                if(bindval==value){
                    $(this).addClass("checked");
                }else{
                    $(this).removeClass("checked");
                }
            });
            if(Xl.isFunction(this._sdCallback)){
                this._sdCallback(value);
            }

        },
        addEvent:function(){

            this._formset.bindAddProxyEvent("radio_check_"+this._key,this._e_selectItem,this);

        },
        _e_selectItem:function (tid,pid) {

            if(this._disabled){
                return;
            }
            var bindval=Xl.sgData(tid,"value");
            this.setCtrlValue(bindval);
        },
        setDataSource:function(datasource){

            //设置数据源
            if(!Xl.isArray(datasource)) {
                return;
            }
            if(datasource[0]&&datasource[0].datalist){
                Xl.forIn(datasource,function(i,v){
                    if(v.key){
                        var value=this._formset.getValueByKey(v.key);
                        if(Xl.inArray(value,v.values)){
                            this._datalist=v.datalist;
                            this.createRadioCtrl();
                            this.setCtrlValue(this.getModelValue());
                            return "__break";
                        }
                    }
                },this);

            }else {
                //直接映射
                this._datalist=datasource;
                this.createRadioCtrl();
                this.setCtrlValue(this.getModelValue());
            }
        }
    });

    //自定义控件
    Xl.Class("__FS_OwnCtrl extends __FS_BaseCtrl",{

        init:function(param,formset){
            this.extractParam(param,formset);
            this.type='own';
            this.createCtrl(param);
        },
        createCtrl:function (param) {

            var __t=this;
            this.containerdom=this._formset.getContainerDom();
            this.ctrwrapdom=this.createCtrWrap();

            this.setXy(this._x,this._y);
            var _htmlContent=param.htmlContent||'';

            $(this.ctrwrapdom).html(_htmlContent);

        },
        setHtmlContent:function(htmlContent){

            _htmlContent=htmlContent||'';
            $(this.ctrwrapdom).html(htmlContent);
        }

    });


    //图片上传组件
    Xl.Class("__FS_UploadCtrl extends __FS_BaseCtrl", {

        init: function (param, formset) {

            this.extractParam(param, formset);
            this.type = 'upload';
            this.maxCount = param.maxCount || 1; //最多能上传几张图片
            this.uploadUrl = param.uploadUrl || null;       //上传url
            this.crop = param.crop || null;       //裁切参数
            this.opType = param.picOpType || 0;      //图片操作类型
            this.width = param.picWidth;          //操作图片的宽度
            this.height = param.picHeight;        //操作图片的高度
            this.isNotDel = param.isNotDel || false;       //不能删除
            this.watermark = param.watermark||null;        //水印
            this._Callback=param.Callback||null;
            if (!this._tip) {
                if (this.maxCount > 1) {
                    this._tip = "最多可上传" + this.maxCount + "张图片";
                }

            }
            this.createCtrl(param);
            this.setCtrlValue(this._value);
            this.addEvent();
        },
        createCtrl: function (param) {

            var __t = this;
            this.containerdom = this._formset.getContainerDom();
            this.ctrwrapdom = this.createCtrWrap();
            this.setXy(this._x, this._y);
            var mustclass = this.getMustClassName();
            $(this.ctrwrapdom).html(['<div class="g-formctrl-picbox">',
                '<span class="g-formctrl-must ', mustclass, '">*</span>',
                '<span class="g-formctrl-title">', this._title, '</span>',
                '<span class="g-formctrl-picwrap">',
                '</span>',
                '<span class="g-formctrl-tip">', this._tip, '</span></div>'].join(''));


            this.picwrap = $(this.ctrwrapdom).find(".g-formctrl-picwrap").get(0); //图片容器

        },
        setCtrlValue: function (value) {

            //图片数据节点value:[{imgcode:piccode,picurl:picurl,width:width,height:height}]
            this.setModelValue(value);

            //数据映射到视图
            this.dataMapView(value);
            if(Xl.isFunction(this._Callback)){
                this._Callback(value);
            }
        },
        dataMapView: function (datalist) {

            datalist = datalist || []; //数据数组
            var piclen = datalist.length; //图片数量

            var htm = ['<ul>'];

            Xl.forIn(datalist, function (i, v) {
                //[{picurl:'/dsa/.png'},{}]

                htm.push(['<li><div class="g-form-piclist-node">',
                    '<img src="', (v.picurl || ''), '" width="100" height="100">',
                    '</div>',
                    '<i class="g-ll-moveleft"  data-index="', i, '" data-key="', this._key, '" data-event="moveleftpic_',this._key,'"></i>',
                    '<i class="g-ll-moveright"  data-index="', i, '" data-key="', this._key, '" data-event="moverightpic_',this._key,'"></i>'].join(''));
                if (!this.isNotDel) {
                    htm.push('<i class="g-ll-del" data-index="', i, '" data-key="', this._key, '" data-event="delpic_',this._key,'" title="点击删除"></i>')
                }
                htm.push('</li>');

            }, this);

            if (piclen < this.maxCount) {

                htm.push(['<li><a class="g-form-addpicbox" data-bind="file:', this._key, '-file" href="javascript:;">',
                    '<i></i>',
                    '</a></li>'].join(''));

            }

            htm.push('<div class="clear"></div></ul>');

            $(this.picwrap).html(htm.join(''));

            this.removePicControl(); //移除控件
            if(piclen < this.maxCount){
                this.registUploadEvent();
            }


        },
        registUploadEvent: function () {

            var __t = this;
            var datakey = "file:" + this._key + "-file";
            var datadom = Xl.Dom.getDomByDataBind(datakey, this.picwrap);

            Xl.Dcom.callc("sys/upload","open",{uploadUrl:this.uploadUrl,handle:datadom,zIndex:25000,picW:this.width||0,picH:this.height||0,opType:this.opType,
                crop:this.crop,watermark:this.watermark,
                succCallBack:function(imgcode, picurl, width, height){

                    var piclist = __t.getModelValue() || [];
                    piclist.push({imgcode: imgcode, picurl: picurl, width: width, height: height});
                    //映射到控件
                    __t.setCtrlValue(piclist);

                },
                failCallBack:function(status,msg){
                    Xl.alert(msg,"error");//失败回调
                },
                getDcomObj:function(obj){

                    //获取组件对象
                    __t.pushToPicControl(obj);
                }
            });

        },
        pushToPicControl: function (icontrol) {

            if (this.icontrolcache == null) {
                this.icontrolcache = [];
            }

            this.icontrolcache.push(icontrol);

        },
        removePicControl:function(key){

            if(this.icontrolcache){
                while(this.icontrolcache.length>0){
                    try {
                        this.icontrolcache.pop().removeControl();
                    } catch (err) {
                    }
                }
            }
        },
        addEvent:function(){

            this._formset.bindAddProxyEvent("moveleftpic_"+this._key,this.moveLeftImgNode,this);
            this._formset.bindAddProxyEvent("moverightpic_"+this._key,this.moveRightImgNode,this);
            this._formset.bindAddProxyEvent("delpic_"+this._key,this.delImgNode,this);

        },
        moveLeftImgNode:function(tid,pid){

            var index=parseInt(Xl.sgData(tid,"index")); //当前图片索引
            var piclist=this.getModelValue()||[];
            if(index==0){
                Xl.alert("已是第一张图片，无法移动！");
                return;
            }
            var tmpobj=piclist[index-1];
            piclist[index-1]=piclist[index];
            piclist[index]=tmpobj;

            this.setCtrlValue(piclist);

        },
        moveRightImgNode:function(tid,pid){

            var index=parseInt(Xl.sgData(tid,"index")); //当前图片索引
            var piclist=this.getModelValue()||[];
            var len=piclist.length;
            if(index==len-1){
                Xl.alert("已是最后一张图片，无法移动！");
                return;
            }
            var tmpobj=piclist[index+1];
            piclist[index+1]=piclist[index];
            piclist[index]=tmpobj;

            this.setCtrlValue(piclist);
        },
        delImgNode:function(tid,pid){

            var piclist=this.getModelValue()||[];
            var index=parseInt(Xl.sgData(tid,"index")); //当前图片索引
            piclist.splice(index,1);
            this.setCtrlValue(piclist);
        },
        destroyEvent:function(){
            this.removePicControl();
        }

    });

    Xl.Class("__FS_Map",{
        _ctrlobjs:{},
        _ctrlanonymous:[],
        init:function(p){
            this._model=Xl.Model({});
            this._wrap=p.wrap;
            this._controls=p.controls;
            this._registEvent=p.registEvent||null;
            this._className=p.className||'';
            this._skin=p.skin||'';
            if(Xl.isUndefined(p.titleext)){
                this._titleext='：';
            }else{
                this._titleext=p.titleext||'';
            }
            this._wrapdom=Xl.E(p.wrap);
            this._initContainter();
            this._parseCtrolsAndMap();
            this._addEvent();

        },
        _initContainter:function(){

            if(this._className){
                $(this._wrapdom).addClass(this._className);
            }
            if(this._skin){
                this.setSkin(this._skin);
            }

        },
        _parseCtrolsAndMap:function(){

            Xl.forIn(this._controls,function(i,ctrl){
                this._createCtrl(ctrl);
            },this);

        },
        bindAddProxyEvent:function(event,callback,proxy){

            this.addProxyEvent(event,function(tid,pid){

                if(Xl.isFunction(callback)){
                    if(proxy){
                        callback.call(proxy,tid,pid);
                    }else{
                        callback(tid,pid);
                    }
                }
            });
        },
        bindRemoveProxyEvent:function(event){

            this.removeProxyEvent(event);
        },
        _addEvent:function(){

            if(Xl.isFunction(this._registEvent)){
                this._registEvent.call(this);
            }
            this.registProxyEvent(this._wrapdom);
        },
        _createCtrl:function(ctrl){

            var ctrlobj=FS_Ctrl_Factory(ctrl,this);
            if(ctrl.key){
                this._ctrlobjs[ctrl.key]=ctrlobj;
            }else {
                //匿名控件
                this._ctrlanonymous.push(ctrl);
            }
        },
        getAllCtrl:function(){
            return this._ctrlobjs;
        },
        getCtrlObj:function(key){
            return this._ctrlobjs[key];
        },
        getCtrlType:function(key){

            if(this._ctrlobjs[key]){
                return this._ctrlobjs[key].type;
            }
            return null;
        },
        getCtrlTitle:function(key){
            return this._ctrlobjs[key].title;
        },
        getCtrlWrapDom:function(key){
            return this.getCtrlObj(key).ctrwrapdom;
        },
        setCtrlXy:function(key,x,y){
            this.getCtrlObj(key).setXy(x,y);
        },
        setCtrlClassName:function(key,className){
            this.getCtrlObj(key).setCtrlParam('className',className);
        },
        setCtrlName:function(key,name){
            this.getCtrlObj(key).setCtrlParam('name',name);
        },
        setCtrlTitle:function(key,title){
            this.getCtrlObj(key).setCtrlParam('title',title);
        },
        setCtrlTip:function(key,tip){
            this.getCtrlObj(key).setCtrlParam('tip',tip);
        },
        getContainerDom:function(){
            return this._wrap;
        },
        setMust:function(key,must){
            var obj=this.getCtrlObj(key);
            obj.setMustFlag(must);
        },
        getDSAndFC:function(fcname,callback,url){

            //获取fieldcheck和dataset
            var datasetversion=Xl.Mem.get("dataset/version")||0;
            var needcalldata=0;
            if(datasetversion!=$_DataSetVersion){
                needcalldata=1;
            }else{
                if(!Xl.Mem.get("datasetandfieldcheck/"+fcname)) {
                    needcalldata = 1;
                }
            }
            needcalldata=1;
            if(!needcalldata){
                if(Xl.isFunction(callback)){
                    callback.call(this,Xl.Mem.get("datasetandfieldcheck/"+fcname));
                    return;
                }
            }

            var __t=this;
            url=url||Xl.GU("/datasource1");
            Xl.request(url,{type:fcname},function(d,isok){
                if(isok){
                    if(needcalldata){
                        Xl.Mem.set("dataset/version",$_DataSetVersion);
                        Xl.Mem.set("datasetandfieldcheck/"+fcname,d);
                    }
                    if(Xl.isFunction(callback)) {
                        callback.call(__t,d);
                    }
                }

            },0);


        },
        mapDataSet:function(dataset){
            if(!dataset){
                return;
            }
            Xl.forIn(dataset,function(key,data){

                var selectObj=this.getCtrlObj(key);
                if(selectObj){
                    if(Xl.isFunction(selectObj.setDataSource)) {
                        selectObj.setDataSource(data);
                    }
                }
            },this);
        },
        mapFieldCheck:function(fieldcheck){

            if(!fieldcheck){
                return;
            }
            Xl.forIn(fieldcheck,function(key,data){
                var ctrlObj=this.getCtrlObj(key);
                if(ctrlObj){
                    ctrlObj.setFieldCheck(data);
                }
            },this);
            //刷新设置控件
            this.flushAllCtrlMustFlag();
        },
        checkData:function(keys){
            //检测字段是否合法
            var rt=true;
            if(keys){
                if(Xl.isString(keys)){
                    keys=keys.split(',');
                }
                if(Xl.isArray(keys)){
                    Xl.forIn(keys,function (i,key) {

                        var ctrlObj=this.getCtrlObj(key);

                        if(Xl.isFunction(ctrlObj.checkData)){
                            rt=ctrlObj.checkData();
                            if(!rt){
                                //未验证通过
                                return '__break';
                            }
                        }
                    },this);
                }
                return rt;
            }

            Xl.forIn(this._ctrlobjs,function(key,obj){

                if(Xl.isFunction(obj.checkData)){

                    rt=obj.checkData();
                    if(!rt){
                        return '__break';
                    }
                }


            },this);

            return rt;


        },
        getModel:function(){
            return this._model;
        },
        getValueByKey:function(key){

            return this._model.get(key);
        },
        setValueByKey:function(key,value,emptysettomodel){

            var ctrlobj=this.getCtrlObj(key);
            if(ctrlobj) {
                ctrlobj.setCtrlValue(value);
            }else{
                if(emptysettomodel){
                    this._model.set(key, value);
                }
            }
        },
        getValues:function(isfilter,excepts){
            return this._model.gets(isfilter,excepts);
        },
        flushAllCtrlMustFlag:function(){
            //刷新每一个控件must控件属性
            Xl.forIn(this._ctrlobjs,function(i,ctrl){
                ctrl.setMustFlag();
            },this);
        },
        setSkin:function(skin){
            //设置组件皮肤
            this._skin=skin;
            var className=$(this._wrapdom).attr("class");
            if(className){
                className=className.replace(/\b[^\s]*skin\b/g,"");
            }
            className+=' '+skin;
            $(this._wrapdom).attr("class",className);

        },
        remove:function(keys){

            if(keys){
                var keyarr=null;
                if(Xl.isArray(keys)){
                    keyarr=keys;
                }else{
                    keyarr=keys.split(',');
                }
                Xl.forIn(keyarr,function(k,v){
                    this.getCtrlObj(v).remove();
                },this);
            }else{
                Xl.forIn(this._ctrlobjs,function(k,ctl){
                    ctl.remove();
                },this);

                $(this._wrap).empty();
                this.destroyProxyEvent(this._wrapdom);

            }

        }

    });

    Xl.formset={
        __dataSourceCache:{},
        mapCtrls:function(p){
            return new (Xl.Class("__FS_Map"))(p);
        },
        getDataSource:function(pm){

            var url=pm.url||'';
            var name=pm.name||''; //数据源名
            var dsname=pm.dsname||'';
            var callback=pm.callback||null;
            var data=pm.data||{}; //提交参数
            var cachelevel=0;
            data.type=name;
            if(Xl.isUndefined(pm.cachelevel)){
                cachelevel=1;
            }else{
                cachelevel=pm.cachelevel;//1代表本页缓存，0代表不缓存
            }
            var datasource=null;
            var cachekey=name?name:url;
            if(name=="dataset"&&dsname){
                cachekey="dataset_"+dsname;
                if(data){
                    data.dsname=dsname;
                }else{
                    data= {dsname: dsname};
                }
            }
            if(cachelevel==1) {
                datasource = this.__dataSourceCache[cachekey];
            }

            if(datasource){
                if(Xl.isFunction(callback)){

                    callback(datasource);
                    return;
                }else{
                    return datasource;
                }
            }
            //后台获取
            if(!url){
                url=Xl.GU("/datasource1");
            }
            var __t=this;
            Xl.request(url,data,function(d,isok){
                if(isok){
                    __t.__dataSourceCache[cachekey]=d;
                    if(Xl.isFunction(callback)){
                     
                        callback(d);
                    }
                }else{
                    Xl.alert(d.msg||'获取数据源失败');
                }

            },0);


        }

    };



})();
