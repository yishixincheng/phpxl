!function(){


    new Xl.Class({

        init:function(){

             this.autoResizePage();

        },
        autoResizePage:function(){

            var viewSz=Xl.getViewSize();
            var g_headerbox=Xl.E("g_headerbar");
            var g_mainarea=Xl.E("g_mainarea");
            var g_navbox=Xl.E("g_navbox");
            var g_contentbox=Xl.E("g_contentbox");
            var h_height=$(g_headerbox).height();
            var m_min_height=viewSz.clientHeight-h_height-3;
            $(g_navbox).height(viewSz.clientHeight);
            $(g_contentbox).css({"min-height":m_min_height+"px"});
            var n_width=$(g_navbox).width();
            var c_width=viewSz.clientWidth-n_width-10;
            $(g_contentbox).width(c_width);

            var __t=this;
            window.setTimeout(function () {
                __t.autoResizePage();
            },20);

        }

    });

}();
