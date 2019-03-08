;!function(){
    var DDB=Xl.Dispatch.BAD;

    new Xl.Class({
        isNeedCheckCode: false,
        init: function(){
            this._checkcodebox=Xl.E("Id_checkcode_box");
            this._checkcodeimg=Xl.E("Id_checkcode_img");
            this.$_loginbody=$(".content").eq(0);
            var __t=this;

            this._changeCheckCode();

            this.addEvent();

        },
        reInitSize: function(){

            //调整对话框位置
            var vs=Xl.getViewSize();
            var loginheight=this.$_loginbody.height();

            var top=(vs.clientHeight-loginheight)/2;

            this.$_loginbody.css({top:top+"px"});

        },
        _changeCheckCode: function(){
            var imgPath='/sysmanage/account/getcodeimg/?width=100&height=42';
            var html='<img src="'+imgPath+'" title="点击换一换" data-event="changeCheckCode" >';
            $(this._checkcodeimg).html(html);
        },
        addEvent: function(){
            var __t=this;
            var wrapdom=Xl.E("Id_login_wap");
            var data={};
            data.username=DDB({value:'', dom:"value:username", pdom:wrapdom});
            data.password=DDB({value:'', dom:'value:password', pdom:wrapdom});
            data.checkcode=DDB({value:'', dom:'value:checkcode', pdom:wrapdom});
            this.model=window.__t=Xl.Model(data);
            this.addProxyEvent("changeCheckCode", this.e_changeCheckCode);
            this.addProxyEvent("submit", this.submit);
            this.registProxyEvent(wrapdom);
        },
        e_changeCheckCode: function(tid,pid){
            var imgPath='/sysmanage/account/getcodeimg'+"/?width=100&height=42&v="+Math.random();
            $(tid).attr("src", imgPath);
        },
        submit: function(){
            var data=this.model.gets();
            if(Xl.isEmpty(data.username)){
                var nm=$("#Id_username").val();
                if(Xl.isEmpty(nm)){
                    Xl.alert("请输入用户名");
                    return;
                }else{
                    data.username=nm;
                }
            }
            if(Xl.isEmpty(data.password)){
                var pw=$("#Id_password").val();
                if(Xl.isEmpty(pw)){
                    Xl.alert("请输入密码");
                    return;
                }else{
                    data.password=pw;
                }
            }
            if(Xl.isEmpty(data.checkcode)){
                Xl.alert("请输入验证码！");
                return;
            }
            var __t=this;
            Xl.request('/sysmanage/account/logindone', data, function(d, isok){
                if(isok){
                    Xl.alert("恭喜您，登录成功，系统为您跳转","right");
                    window.location.reload();
                }else{
                    Xl.alert(d.msg || "登录失败", "error");
                }
            });
        }
    });
}();
