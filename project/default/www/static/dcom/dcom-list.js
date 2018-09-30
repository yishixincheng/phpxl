// JavaScript Document

//列表视图组件
(function(){

	"use strict";

	var DDG=Xl.Dom.getDomByDataBind;

	Xl.Class("Dcom-ListView",{

		init:function(p){
			this._guid=Xl.getGuid();
			this._creator=p.creator||null;
			this._wrap=p.wrap||null;
			this._containerClassName=p.containerClassName||'';
			this._getListObject=p.getListObject||null;
			this._headViewHtml=p.headViewHtml||'';
			this._nodeViewHtml=p.nodeViewHtml||'';
			this._dataSource=p.dataSource||null;
			this._scrollNodeCallback=p.scrollNodeCallback||null;
			this._registEvent=p.registEvent||null;
			this._fenyeCallback=p.fenyeCallback||null;
			this._attachHtml=p.attachHtml||'';
			this._noResultTip=p.noResultTip||'无搜索结果';
			this._noResultHtml=p.noResultHtml||'';
			this._fenyeTipTpl=p.fenyeTipTpl||'';
			this._isMoveEnter=p.isMoveEnter||false;
			this._posNodeCallback=p.posNodeCallback||null;
			this._enterNodeCallback=p.enterNodeCallback||null;
			this._dblClickNodeCallback=p.dblClickNodeCallback||null;
			this._setOrderByValueCallback=p.setOrderByValueCallback||null;
			this._forbidInitCallData=p.forbidInitCallData||false;
			this._defaultTip=p.defaultTip||'';
			this._afterCreateListHtmlCallback=p.afterCreateListHtmlCallback||null;
			this._islock=p.islock||false;
			this._pointer=0;
			this._rowcount=0;
            this._rowcount=0;
            this._shownum = p.shownum||8;
			if(!this._wrap){
				Xl.alert("构建列表视图时缺少参数");
				return;
			}
			if(Xl.isFunction(this._getListObject)){
				this._getListObject.call(this._creator,this);
			}
			this.createContainer();
			this.draw();
			this.addEvent();

		},
		reInit:function(p){
			if(p){
				this.init(p);
			}else{
				this.reLoad(false);
			}
		},
		reLoad:function(isreload){

			if(Xl.isUndefined(isreload)){
				isreload=true;
			}

			//重新加载框架数据
			this.datalist=[];
			this._pointer=0;
			this._rowcount=0;
			this.draw(isreload);

		},
		createContainer:function(){

			var A=['<div class="dcom-listview-wrap ',this._containerClassName,'" data-bind="wrap">',
				'<div class="dcom-listview-head" data-bind="head"></div>',
				'<div class="dcom-listview-body" data-bind="body"></div>',
				'<div class="dcom-listview-fenye clearfix" data-bind="fenye"></div>',
				'<div class="dcom-listview-attach" data-bind="attach"></div>',
				'</div>'];
			$(this._wrap).html(A.join(''));
			this.wrapdom=DDG("wrap",this._wrap);
			this.headdom=DDG("head",this._wrap);
			this.bodydom=DDG("body",this._wrap);
			this.fenyedom=DDG("fenye",this._wrap);
			this.attachdom=DDG("attach",this._wrap);

		},
		draw:function(isreload){

			if(!isreload){
				if(Xl.isFunction(this._headViewHtml)){
					this.createHeadView(this._headViewHtml.call(this._creator,this));
				}else{
					this.createHeadView(this._headViewHtml);
				}
			}
			var _this=this;
			//调用数据列表
			if(!isreload&&this._forbidInitCallData){
				$(this.bodydom).html('<p class="dcom-listview-noresult">'+this._defaultTip+'</p>');
			}else {
				if (Xl.isFunction(this._dataSource)) {
					this._dataSource.call(this._creator, function (d, page, num) {
						_this.dataMapCtrl(d, page, num);
					});
				}
			}
			if(Xl.isFunction(this._attachHtml)){
				this.createAttachView(this._attachHtml.call(this._creator,this));
			}else{
				this.createAttachView(this._attachHtml);
			}

		},
		setAttachHtml:function(attachHtml){
			this._attachHtml=attachHtml;
			if(Xl.isFunction(attachHtml)){
				this.createAttachView(attachHtml.call(this._creator,this));
			}else{
				this.createAttachView(attachHtml);
			}
		},
		lockEnterEvent:function(){
			this._islock=true;
		},
		unlockEnterEvent:function(){
			this._islock=false;
		},
		createHeadView:function(ht){
			if(!ht){
				return;
			}
			if(Xl.isString(ht)){
				$(this.headdom).html(ht);
				return;
			}
			if(Xl.isArray(ht)){
				var A=['<ul>'];
				Xl.forIn(ht,function(i,v){

					if(v===null){
						return '__continue';
					}

					var orderbyvalue=v.orderbyvalue||0;
					var orderbyclass="";
					if(orderbyvalue==1){
						orderbyclass="down";
					}else if(orderbyvalue==2){
						orderbyclass="up";
					}
					A.push(['<li ',(v.orderbykey?' data-event="orderby" data-orderbyvalue="'+orderbyvalue+'" data-orderbykey="'+v.orderbykey+'" ':''),
						' class="c',(i+1),' ',(v.cls||''),' ',(v.orderbykey?'orderbynode':''),'">',
						(v.title||v.name||''),
						(v.orderbykey?'<i class="'+orderbyclass+'"></i>':''),
						'</li>'].join(''));
				},this);
				A.push('</ul>');
				$(this.headdom).html(A.join(''));
			}else{
				$(this.headdom).empty();
			}
		},
		createAttachView:function(ht){

			$(this.attachdom).html(ht||'');

		},
		setNoResultHtml:function(){
			if(this.fenyedom) {
				$(this.fenyedom).empty();
			}
			$(this.bodydom).html(this._noResultHtml||('<p class="dcom-listview-noresult">'+this._noResultTip+'</p>'));
		},
		dataMapCtrl:function(d,page,num){
			if(Xl.isEmpty(d)){
				this.setNoResultHtml();
				return;
			}
			var allcount=d.allcount||0;
			d=d.datalist||[];
			this._rowcount=d.length||0;
			if(this._rowcount===0){
				this.setNoResultHtml();
				return;
			}
			var A=['<dl>'];
			Xl.forIn(d,function(i,v){
				var cls='';
				if(i%2===0){
					cls='odd';
				}
				var _innerHtml='';
				if(Xl.isString(this._nodeViewHtml)){
					_innerHtml=Xl.Tpl(this._nodeViewHtml,v);
				}else if(Xl.isFunction(this._nodeViewHtml)){
					_innerHtml=this._nodeViewHtml.call(this._creator,v,i,this,function (am){
						if(Xl.isObject(am)){
							if(am.action=="setclassname"){
								cls+=" "+(am.className||"");
							}
						}
					});
				}
				A.push('<dd data-event="posrow" class="'+cls+'" data-row="'+i+'">');
				A.push(_innerHtml);
				A.push('</dd>');

			},this);

			this.datalist=d;

			A.push('</dl>');
			$(this.bodydom).html(A.join(''));
            $("dd").hover(function(){
                $(this).addClass('on');
            },function(){
                $(this).removeClass('on');
            });			
			num=num||10;
			this.page=page||1;
			allcount=parseInt(allcount); //总数量
			this.allpage=Math.floor((allcount-1)/num)+1;
			this.createFenye(allcount);
			this.scrollDeal();

			if(Xl.isFunction(this._afterCreateListHtmlCallback)){
				this._afterCreateListHtmlCallback.call(this._creator,this,d);
			}

		},
		createFenye:function(allcount){

			if(this.m_fenye){
				this.m_fenye.destroy();
			}
			this.m_fenye=new (Xl.Class("Global_Fenye"))({
				page:this.page,
				allpage:this.allpage,
				wrapdom:this.fenyedom,
				creator:this,
				shownum:this._shownum,
				tiptpl:Xl.Tpl(this._fenyeTipTpl,{allcount:allcount}),
				callback:function(page){

					var _this=this;
					if(Xl.isFunction(this._fenyeCallback)){
						this._fenyeCallback.call(this._creator,page,this);
					}
					this._dataSource.call(this._creator,function(d,page,num){
						_this.dataMapCtrl(d,page,num);
					});

				}
			});

		},
		addEvent:function(){


			this.addProxyEvent("orderby",this.e_orderBy);

			if(Xl.isFunction(this._registEvent)){
				this._registEvent.call(this._creator,this);
			}


			if(this._isMoveEnter){
				this.addProxyEvent("posrow",this.posRow);
				Xl.setG("Event/rootkeydownFunc>_movedcomlist_"+this._guid,function(e){

					if(e.keyCode==38){
						_this.movePosRowPointer("up",e);
					}else if(e.keyCode==40){
						_this.movePosRowPointer("down",e);
					}else if(e.keyCode==13){
						_this.enterNode();
					}
				});
			}

			this.registProxyEvent(this.wrapdom);//注册事件

			if(Xl.isFunction(this._dblClickNodeCallback)){
				this.registProxyEvent(this.bodydom,"dblclick");//注册事件

			}


			var _this=this;
			Xl.registGlobalEvent();
			Xl.setG("Event/scrollFunc>_g_scroll_dcom_listview_"+this._guid,function(e){
				_this.scrollDeal(e);
			});


		},
		posRow:function(tid,pid,eventtype,event){

			var row=Xl.sgData(tid,"row");

			if(eventtype=="click"){

				if(this._isMoveEnter){
					this._posRow(row);
				}

			}else if(eventtype=="dblclick"){

				if(Xl.isFunction(this._dblClickNodeCallback)){
					event.stopPropagation();
					event.preventDefault();
					this._dblClickNodeCallback.call(this._creator,tid,row);


				}

			}

		},
		movePosRowPointer:function(direction,e){

			if(this._islock){
				return;
			}

			if(this._pointer===0){
				return;
			}
			if(direction=="up"){
				this._pointer--;
				if(this._pointer<=0){
					this._pointer=this._rowcount;
				}
			}else{
				this._pointer++;
				if(this._pointer>this._rowcount){
					this._pointer=1;
				}
			}
			var __t=this;
			this._currposdom=null;
			$(this.bodydom).find("dl>dd").each(function(index, element) {
				if(index==__t._pointer-1){
					$(this).addClass("on");
					this.direction=direction;
					__t._currposdom=this;
				}else{
					$(this).removeClass("on");
				}
			});
			e.preventDefault();
			if(Xl.isFunction(this._posNodeCallback)){
				this._posNodeCallback.call(this._creator,this._currposdom);
			}

		},
		_posRow:function(row){

			row=parseInt(row); //行数
			this._pointer=row+1;

			var __t=this;

			$(this.bodydom).find("dl>dd").each(function(index, element) {

				if(index==row){
					$(this).addClass("on");
					__t._currposdom=this;
				}else{
					$(this).removeClass("on");
				}
			});

		},
		getNodeDataByRow:function(row){

			if(Xl.isEmpty(this.datalist)){
				return null;
			}
			return this.datalist[row];
		},
		enterNode:function(){


			if(this._islock){
				return;
			}

			if(!this._pointer){
				return;
			}
			if(Xl.isFunction(this._enterNodeCallback)){
				this._enterNodeCallback.call(this._creator,this._currposdom,this._pointer-1);
			}

		},
		e_orderBy:function(tid,pid){

			var orderbykey=Xl.sgData(tid,"orderbykey");
			if(!orderbykey){
				return;
			}
			if(Xl.isUndefined(tid.ordervalue)){
				tid.ordervalue=parseInt(Xl.sgData(tid,"value")||0);
			}
			if(tid.ordervalue===0){
				tid.ordervalue=1;
				$(tid).find("i").attr("class","down");
			}else if(tid.ordervalue==1){
				tid.ordervalue=2;
				$(tid).find("i").attr("class","up");
			}else{
				tid.ordervalue=0;
				$(tid).find("i").attr("class","");
			}
			if(Xl.isFunction(this._setOrderByValueCallback)){
				this._setOrderByValueCallback.call(this._creator,orderbykey,tid.ordervalue);
			}
		},
		scrollDeal:function(e){

			var _this=this;
			var vz=Xl.getViewSize();
			var scrollTop=vz.scrollTop;
			var clientHeight=vz.clientHeight;
			var mintop=scrollTop||0;
			var maxtop=mintop+clientHeight;
			$(this.bodydom).find("dl>dd").each(function(index, element) {
				var pre_inviewbox=Xl.sgData(this,"inviewbox");
				var now_inviewbox=0;
				var oft=$(this).offset();
				var inviewbox=false;
				if(oft.top>=mintop&&oft.top<=maxtop){
					Xl.sgData(this,"inviewbox",1);
					inviewbox=true;
					now_inviewbox=1;
				}else{
					Xl.sgData(this,"inviewbox",0);
					now_inviewbox=0;
				}
				if(pre_inviewbox!= now_inviewbox){
					if(Xl.isFunction(_this._scrollNodeCallback)){
						_this._scrollNodeCallback.call(_this._creator,this,inviewbox,_this);
					}
				}
			});

		},
		setDataValue:function(row,key,value,isupdaterow){
			var dataNode=this.getNodeDataByRow(row);
			if(dataNode===null){
				alert("无效行");
				return;
			}
			try{
				dataNode[key]=value;
			}catch(err){
			}
			if(isupdaterow){
				//是否更新某一个行
				this.rowDataMap(row);
			}
		},
		rowDataMap:function(row,dt){

			if(!Xl.isNumber(row)){
				return;
			}
			row=parseInt(row);
			var dataNode=this.getNodeDataByRow(row);

			dataNode=dataNode||{};

			if(Xl.isPlainObject(dt)){

				Xl.forIn(dt,function(i,v){

					if(Xl.isString(i)){
						if(dataNode.hasOwnProperty(i)){
							dataNode[i]=v;
						}else{
							if(!Xl.isPlainObject(dataNode._attach)){
								dataNode._attach={};
							}
							dataNode._attach[i]=v;
						}
					}

				});

			}
			//刷新控件
			var html='';
			if(Xl.isString(this._nodeViewHtml)){
				html=Xl.Tpl(this._nodeViewHtml,dataNode);
			}else if(Xl.isFunction(this._nodeViewHtml)){
				html=this._nodeViewHtml.call(this._creator,dataNode,row,this);
			}
			$(this.bodydom).find("dl>dd").each(function(index, element) {
				var _row=Xl.sgData(this,"row");
				if(_row==row){
					Xl.sgData(this,"islock",0); //解锁
					Xl.sgData(this,"inviewbox",0);
					$(this).html(html);
					return false;
				}
			});

			this.scrollDeal();
		},
		remove:function(){

			//yichukongjian
			if(this._wrap) {
				$(this._wrap).empty();
				this.destroyProxyEvent(this._wrap);
			}

		}



	});
	new Xl.Class({
		outinterface:['open'], /*对外结构*/
		model:null,
		iswait:false,
		sdIndex:0,
		init:function(){
			Xl.Dcom.addCom("list",this);//注册组建
		},
		callouti:function(oiname,param){
			//调用接口,必须函数
			this.iswait=false;
			if(!Xl.inArray(oiname,this.outinterface)){
				alert("调用接口不存在！");
				return;
			}
			if(Xl.isFunction(this['outi_'+oiname])){
				this['outi_'+oiname](param||'');//调用接口
			}else{
				alert("调用接口没实现");
			}
		},
		outi_open:function(p){
			var bindObj=p.bindObj;
			if(bindObj&&bindObj._dcomlistctrl){
				bindObj._dcomlistctrl.reInit(p);
			}else{
				if(bindObj){
					bindObj._dcomlistctrl=new (Xl.Class("Dcom-ListView"))(p);
				}else{
					new (Xl.Class("Dcom-ListView"))(p);
				}
			}

		}

	});

})();
