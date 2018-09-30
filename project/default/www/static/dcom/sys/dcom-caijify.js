(function(){
    "use strict";
    function H_MainJsqx(){
        var __t = this;
        this.init=function(){
            this.createDlg();
            setTimeout(function(){
                __t.initListView();
            },300)
        };
        this.sosoparam={
            page:1,
            num:10
        };
        this.createDlg=function(){
            var __t = this;
            var tplhtml = this.getContainerHtm();
            Xl.dlg({
                creator: this, //创建者
                getDlgObj: function () {
                    this.param.isOpenMove = true;
                    this.param.moveType = 1; //1,2,3,4
                    __t.mdlg = this;
                },
                className: 'bus_dlg',
                width: 1028,
                title: '公寓配套',
                height: 512,
                htmlContent: tplhtml,
                closeCallback: function () {},
                afterCall: function (mdlg) {
                    this._init();
                    this.mdlg.resizeWindow();
                }
            });
        };
        this.getContainerHtm=function(){
            var A='<div class="dcom-dtgl-box">' +
                '</div>';
            return A;
        };
        this._init=function(){
            this.wrapdom = $(".dcom-dtgl-box").get(0);
            this.createFormsetView();
        };
        this.initListView=function(){

            Xl.Dcom.callc("list","open",{
                creator:this,
                wrap:Xl.E(".ky_list"),
                getListObject:this._getListObj,
                headViewHtml:this._headViewHtml,
                nodeViewHtml:this._nodeViewHtml,
                dataSource:this._getDataSource,
                isMoveEnter:true,
                registEvent:this._registEvent,
                setOrderByValueCallback:function(key,value){
                    this.sosoparam[key]=value; //0代表默认，1降序，2升序
                    this._reloadPage();
                },
                fenyeTipTpl:'共搜到{{allcount}}结果',
                fenyeCallback:this._fenyeCallback,
                noResultTip:'暂无价格信息'
            });
        };
        this._getDataSource=function(func){
            var __t=this;
            Xl.request(Xl.GU("/sys/config/getgyptlist"),this.sosoparam,function(d,isok){ // 获取数据
                if(isok){
                    if(Xl.isFunction(func)){
                        __t.allcount=d.allcount;
                        __t.datalist=d.datalist;
                        func(d,__t.sosoparam.page,__t.sosoparam.num);
                    }
                }else{
                    Xl.alert(d.msg||"获取数据失败","error");
                }

            });
        };
        this._getListObj=function(obj){
            this._listObj=obj;
        };
        this._headViewHtml=function(){
            return [{title:'序号'},{title:'配套名称'},{title:'操作'}];
        },
        this._nodeViewHtml=function(v,row,thisid){
                var __t  =this;
                var htm='';
                htm+='<a data-event="edit">编辑</a>&nbsp;|&nbsp;<a data-event="del">删除</a>';
                return ['<ul>',
                    '<li class="c1">',v.torder,'</li>',
                    '<li class="c2">',v.name,'</li>',
                    '<li class="c3">',htm,'</li>',
                    '</ul>'].join('');
            },
        this._fenyeCallback=function(page){
            this.sosoparam.page=page;
        };
        this._reloadPage=function(page){
            if(page&&Xl.isNumber(page)){
                this.sosoparam.page=page;
            }
            // this._listObj.reLoad();
        };

        this._registEvent=function (listobj) {
            var __t=this;
            listobj.addProxyEvent("edit",function(tid,pid){
                __t.e_edit(tid,pid);
            });
            listobj.addProxyEvent("del",function(tid,pid){
                __t.e_del(tid,pid);
            });
        },
        this.e_edit=function (tid) {
            var bdnode=$(tid).parents("dd").get(0);
            var row=Xl.sgData(bdnode,"row");
            var data=this._listObj.getNodeDataByRow(row);
            var __t=this;
            var p={
                id:data.id,
                name:data.name,
                orders:data.torder,
                flag:true,
                callback:function () {
                    __t._reloadPage();
                }
            };
            $('.bus_dlg').get(0).style.zIndex=1997;
            new addeditPeitaoDlg(p);
        },
        this.e_del=function (tid) {
            var bdnode=$(tid).parents("dd").get(0);
            var row=Xl.sgData(bdnode,"row");
            var data=this._listObj.getNodeDataByRow(row);
            var __t=this;
            Xl.confirm('确认删除吗?',function () {
                Xl.request(Xl.GU('/sys/config/delgypt'),{id:data.id},function (d,isok) {
                    if(isok){
                        Xl.alert('删除成功','right');
                        __t._reloadPage();
                    }else{
                        Xl.alert('删除失败','error');
                    }
                })
            })
        }
        this.createFormsetView=function(){
            var __t=this;
            var param = {
                wrap: this.wrapdom,
                controls: [
                    {type:'own',x:0,y:4,htmlContent:'<a data-event="add">添加配套</a>',className:'add-btn'},
                    {type: 'own', x: 0, y: 45, className: 'ky_list'}
                ]
            };
            this._formsetObj=Xl.formset.mapCtrls(param);
            this.addEvent();
        };
        this.addEvent=function(){
            var __t=this;
            this._formsetObj.bindAddProxyEvent("add",function(tid,pid){
                __t.e_add(tid,pid);
            });
        };
        this.e_add=function(){
            var __t=this;
            var p={
                flag:false,
                callback:function () {
                    __t._reloadPage();
                }
            };
            $('.bus_dlg').get(0).style.zIndex=1997;
            new addeditPeitaoDlg(p);
        };
        this.e_close=function(){
            this.mdlg.closeWindow();
        };
        this.init();
    }

    function addeditPeitaoDlg(p) {
        this.init=function () {
            this._flag=p.flag||false;
            this._callback=p.callback||null;
            this._id=p.id||'';
            this.orders=p.orders||'';
            this.name=p.name||'';
            this.createDlg();
        };
        this.createDlg=function () {
            var __t = this;
            var tplhtml = this.getContainerHtm();
            Xl.dlg({
                creator: this, //创建者
                getDlgObj: function () {
                    this.param.isOpenMove = true;
                    this.param.moveType = 1; //1,2,3,4
                    __t.mdlg = this;
                },
                className: 'addpeitaodlg',
                title: '添加配套',
                width: 320,
                htmlContent: tplhtml,
                height: 230,
                closeCallback: function () {},
                afterCall: function (mdlg) {
                    this._init();
                    this.mdlg.resizeWindow();
                }
            });
        };
        this.getContainerHtm = function(){
            return ['<div class="peitao_dlg"></div>'].join('');
        };
        this._init=function(){
            this.createView();
        };
        this.createView=function () {
            var param={
                wrap:Xl.E('.peitao_dlg'),
                controls:[
                    {key:'orders',type:'input',title:'序&emsp;&emsp;号',x:'10',y:'29'},
                    {key:'name',type:'input',title:'配套名称',x:'10',y:'68'},
                    {type:'own',htmlContent:'<a data-event="save">保存</a><a data-event="close">取消</a>',x:'54',y:'122',className:'dcom-ptsoso'}
                ]
            };
            this._formsetObj=Xl.formset.mapCtrls(param);
            this.addEvent();
            var __t=this;
            if(this._id){
                __t._formsetObj.setValueByKey('orders',__t.orders);
                __t._formsetObj.setValueByKey('name',__t.name);
            }
        };
        this.addEvent=function () {
            this._formsetObj.bindAddProxyEvent('close',function () {
                this.mdlg.closeWindow();
            },this);
            this._formsetObj.bindAddProxyEvent('save',function () {
                this.e_save();
            },this);
        };
        this.e_save=function () {
            var data=this._formsetObj.getValues();
            var tip,errtip;
            if(this._id){
                tip='编辑成功';
                errtip='编辑失败';
                data.id=this._id;
            }else{
                tip='添加成功';
                errtip='添加失败';
            }
            var __t=this;
            Xl.request(Xl.GU('/sys/config/setgypt'),data,function (d,isok) {
                if(isok){
                    Xl.alert(tip,'right');
                    if(Xl.isFunction(__t._callback)){
                        __t._callback();
                    }
                    __t.mdlg.closeWindow();
                    $('.bus_dlg').get(0).style.zIndex=19999
                }else{
                    Xl.alert(d.msg||errtip,'error');
                }
            })
        };
        this.init();
    }
    new Xl.Class({
        outinterface: ['open'], /*对外结构*/
        init: function () {
            Xl.Dcom.addCom("sys/caijify", this);//注册组建
        },
        callouti: function (oiname, param) {
            //调用接口,必须函数
            this.iswait = false;
            if (!Xl.inArray(oiname, this.outinterface)) {
                alert("调用接口不存在！");
                return;
            }
            if (Xl.isFunction(this['outi_' + oiname])) {
                this['outi_' + oiname](param || '');//调用接口
            } else {
                alert("调用接口没实现");
            }
        },
        outi_open: function (p) {
            new H_MainJsqx(p);
        }
    });
})();