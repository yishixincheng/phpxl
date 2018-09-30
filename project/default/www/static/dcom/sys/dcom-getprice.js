(function(){
    "use strict";
    function H_MainJsqx(a){
        var Hubobj={
            price: {}
        };
        this.jiaoyi_type = a;
        var __t = this;
        this.init=function(){
            this.createDlg();
            setTimeout(function(){
                __t.initListView();
            },300)
        };
        this.sosoparam={
            page:1,
            num:10,
            jiaoyi_type:this.jiaoyi_type
        };
        this.createDlg=function(){
            var __t = this;
            var tplhtml = this.getContainerHtm();
            if(this.jiaoyi_type == 1 ){
                Xl.dlg({
                    creator: this, //创建者
                    getDlgObj: function () {
                        this.param.isOpenMove = true;
                        this.param.moveType = 1; //1,2,3,4
                        __t.mdlg = this;
                    },
                    className: 'price_dlg',
                    width:650,
                    title: '出售均价',
                    height: 512,
                    htmlContent: tplhtml,
                    closeCallback: function () {},
                    afterCall: function (mdlg) {
                        this._init();
                        this.mdlg.resizeWindow();
                    }
                });
            }else{
                Xl.dlg({
                    creator: this, //创建者
                    getDlgObj: function () {
                        this.param.isOpenMove = true;
                        this.param.moveType = 1; //1,2,3,4
                        __t.mdlg = this;
                    },
                    className: 'price_dlg',
                    width: 473,
                    title: '出租租金',
                    height: 512,
                    htmlContent: tplhtml,
                    closeCallback: function () {},
                    afterCall: function (mdlg) {
                        this._init();
                        this.mdlg.resizeWindow();
                    }
                });
            }
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
            Xl.request(Xl.GU("/dict/price/getpricelist"),this.sosoparam,function(d,isok){ // 获取数据
                if(isok){
                    if(Xl.isFunction(func)){
                        __t.allcount=d.allcount;
                        __t.datalist=d.datalist;
                        func(d,__t.sosoparam.page,__t.sosoparam.num);
                        Hubobj.price=d.datalist;
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
            return [{title:'序号'},{title:'价格'}];
        },
            this._nodeViewHtml=function(v,row,thisid){
                var __t  =this;
                return ['<ul>',
                    '<li class="c1">',v.id,'</li>',
                    '<li class="c2">',v.nick_price,'</li>',
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
        this.createFormsetView=function(){
            var __t=this;
            var param = {
                wrap: this.wrapdom,
                controls: [
                    {type:'own',x:0,y:4,htmlContent:'<a data-event="add">价格配置</a>',className:'add-btn'},
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
            $('.price_dlg').get(0).style.zIndex=1997;

            Xl.Dcom.callc('/dict/editRent','open',
                {
                    price: Hubobj.price,
                    jiaoyi_type: this.jiaoyi_type,
                    callback: function(){
                        __t.initListView();
                    }
                });
        };
        this.e_close=function(){
            this.mdlg.closeWindow();
        };
        this.init();
    }

    new Xl.Class({
        outinterface: ['open'], /*对外结构*/
        init: function () {
            Xl.Dcom.addCom("sys/getprice", this);//注册组建
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