{tpl "/plugin/sysmanage/header"}

<link rel="stylesheet" type="text/css" href="/view/plugin/sysmanage/css/index.css">

<style type="text/css">

    ._box{
        width: 200px;
        margin: auto;
        padding-top: 100px;
    }
    ._line{
        margin-bottom: 20px;
        height: 30px;
    }
    ._line a{
        display: block;
        width: 200px;
        height: 30px;
        color: #fff;
        background: #807c6c;
        border-radius: 5px;
        font-size: 16px;
        text-align: center;
        cursor:pointer;
        line-height: 30px;
    }

</style>


{tpl "/plugin/sysmanage/header-close"}


<div class="_box">

    <div class="_line"><a href="javascript:;" data-type="1" data-event="clearcache">清除路由缓存</a></div>
    <div class="_line"><a href="javascript:;" data-type="2" data-event="clearcache">清除模版缓存</a></div>

</div>



{tpl "/plugin/sysmanage/footer-start"}

<script type="text/javascript">

    new Xl.Class({

        init:function () {
            this.addEvent();
        },
        addEvent:function(){
            this.addProxyEvent("clearcache",this.event_clearCache);
            this.registProxyEvent("._box");
        },
        event_clearCache:function(tid,pid){

            var type=Xl.sgData(tid,"type");

            Xl.request("/sysmanage/clearcache",{type:type},function(d,isok) {
                if(isok){
                    Xl.alert("清理成功！","right");
                }else{
                    Xl.alert(d.msg||"清理失败","error");
                }
            });

        }

    });

</script>

{tpl "/plugin/sysmanage/footer"}