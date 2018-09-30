

(function () {
	
	function Loading(p) {
		
		this._p = p;
		
		this.init = function () {
			
			this.createDom();
			
		};
		this.createDom = function () {
			var dom = document.getElementById("overlay");
			if (dom) {
				return;
			}
			this._dom = document.createElement('div');
			this._dom.id = "overlay";
			this._dom.classname = "container";
			this._dom.style.position = "fixed";
			this._dom.style.top = "0px";
			this._dom.style.left = "0px";
			this._dom.style.width = "100%";
			this._dom.style.height = "100%";
			this._dom.style.zIndex = "10";
			this._dom.style.backgroundColor = "rgba(0,0,0,.7)";
			
			var children = document.body.children;
			console.log(children)
		};
		
		this.init()
	}
	
	
	var __t = {
		outinterface: ['open'], /*对外结构*/
		loadingLimer: null,
		loadComplate: false,
		init: function () {
			Xl.Dcom.addCom("sys/loading", this);//注册组建
		},
		callouti: function (oiname, param) {
			//调用接口,必须函数
			__t.iswait = false;
			if ($.inArray(oiname, __t.outinterface) == -1) {
				alert("调用接口不存在","error");
				return;
			}
			if ($.isFunction(__t['outi_'+oiname])) {
				__t['outi_'+oiname](param||'');//调用接口
			} else {
				alert("调用接口没实现");
			}
		},
        outi_init: function () {

        },
		outi_open: function (p) {
			new Loading(p);
		}

	};
	__t.init();
	
	
	
})();











