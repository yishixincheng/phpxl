;!function(){



    new Xl.Class({

        init:function(){

            this.createForm();
            this.getData();
        },
        createForm:function(){

            var param = {
                wrap: Xl.E("._box"),
                className: 'savemodel-dlg-formset',
                controls: [
                    {key:'seo_webname',x:'0',y:'0',type: 'input',title:'网站名称：',ismust:true},
                    {key:'site_url',x:'0',y:'50',type:'input',title:'网站url：',ismust:true},
                    {key:'site_domain',x:0,y:100,type:'input',title:'网站domain：'},
                    {key:'seo_keyword',x:0,y:150,type:'textarea',title:'关键字：'},
                    {key:'seo_dis',x:0,y:250,type:'textarea',title:'描述：'},
                    {key:'closesite',x:0,y:350,type:'checkbox',title:'关闭站点：'},
                    {key:'closetip',x:0,y:380,type:'textarea',title:'关闭站点提示：'},
                    {type: 'own',x:120,y:500,htmlContent: '<a class="_btn" data-event="save">保存</a>'}
                ]
            };

            this._formsetObj=Xl.formset.mapCtrls(param);

            this.addProxyEvent("save",this.event_save);
            this.registProxyEvent(Xl.E("._box"));

        },
        getData:function () {

            var __t=this;
            Xl.request("/sysmanage/getbasedata",{},function(d,isok){

                if(isok){

                    __t._formsetObj.setValueByKey("seo_webname",d.seo_webname||'');
                    __t._formsetObj.setValueByKey("site_url",d.site_url||'');
                    __t._formsetObj.setValueByKey("site_domain",d.site_domain||'');
                    __t._formsetObj.setValueByKey("seo_keyword",d.seo_keyword||'');
                    __t._formsetObj.setValueByKey("seo_dis",d.seo_dis||'');
                    __t._formsetObj.setValueByKey("closesite",d.closesite||0);
                    __t._formsetObj.setValueByKey("closetip",d.closetip||"");

                }

            },0);

        },
        event_save:function(tid,pid){

            var data=this._formsetObj.getValues();

            if(Xl.isEmpty(data.seo_webname)){
                Xl.alert("网站名称必填！");
                return;
            }
            if(Xl.isEmpty(data.site_url)){
                Xl.alert("网站url必填");
                return;
            }

            Xl.request("/sysmanage/setbasedata",data,function(d,isok){

                if(isok){
                    Xl.alert("保存成功！","right");
                }else{
                    Xl.alert("保存失败","error");
                }

            },0);

        }



    });


}();