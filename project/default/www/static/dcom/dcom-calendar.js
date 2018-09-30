// JavaScript Document

//日历控件by一世心城

(function() {
	
	/**
	   bindinputdom
	   showmonth:1, 显示月份的个数
	   disablebeforetoday:false,
	   sdCallback, 选中回调
	   inputdate,  用户输入的日期，格式2015-10-24
	*/
	
	var binddomlist=[];
	
	function getDatesFromDataFormat(datestr){
		
		datestr=Xl.trim(datestr||'');
		var year,month,date;
		if(/^\d{4}-(0[1-9])|1([0-2])-([0-2][1-9])|(3[0-1])$/.test(datestr)){	
			var mt=datestr.match(/(\d{4})-(\d{2})-(\d{2})/);
			if(mt){
				year=mt[1];
				month=mt[2];
				date=mt[3];
				return {year:year,month:parseInt(month),date:parseInt(date)};
			}
		}
		return null;
		
	}
	function getDates(y,m,d){
		
		if(y&&m){
			d=d||0;
			nowdate=new Date(y,m,d);
		}else{
			var nowdate=new Date();
		}
		var year=nowdate.getFullYear();
		var month=nowdate.getMonth()+1;
		var date=nowdate.getDate();
		var day=nowdate.getDay();
		return {year:year,month:month,date:date,day:day};	
	}
	
	function getDayName(day){
		
		var weeks=['日','一','二','三','四','五','六'];
		
		if(!Xl.isUndefined(day)){
			if(Xl.inArray(day,[0,1,3,4,5,6])){
				return weeks[day];
			}
		}
		return weeks;
		
	}
	function getNextMonth(y,m){
		
		m++;
		if(m==13){
			m=1;
			y++;
		}
		
		return {year:y,month:m};
		
	}
	
	function getPreMonth(y,m){
		m--;
		if(m==0){
			m=12;
			y--;
		}
		return {year:y,month:m};
	}
	function getPreYear(y,m){
		
		y--;		
		return {year:y,month:m};
		
	}
	function getNextYear(y,m){
		
		y++;
		return {year:y,month:m};
	}
	
	function Calendar(pm,dcom){
		
		Xl.extend(this,Xl.Event);
		var __t=this;
		var _bindinputdom=pm['bindinputdom']||null;
		var _showmonth=pm['showmonth']||1;
		var _disablebeforeday=pm['disablebeforeday']||'';//'today';
		var _beforeinputdom=pm['beforeinputdom']||null;
		var _inputdate=pm['inputdate']||$(_bindinputdom).val()||"";
		var _sdCallback=pm['sdCallback']||null;
		var _dvtop=pm['dvtop']||0; //偏移高度为0	
		var _dvleft=pm['dvleft']||0;	
		var _sddates=getDatesFromDataFormat(_inputdate);
		
		__t.float=pm['float']||'left';
		
		if(!Xl.inArray(_bindinputdom,binddomlist)){
			binddomlist.push(_bindinputdom);
		}
		
		if(_showmonth!=2){
			_showmonth=1;
		}
		var _sdyear,_sdmonth,_sddate;
		var _nowdates=getDates();
		var _nowyear=_sdyear=_nowdates['year'];
		var _nowmonth=_sdmonth=_nowdates['month'];
		var _nowdate=_sddate=_nowdates['date'];
		if(_sddates){
			_sdyear=_sddates['year'];   //当前的年
			_sdmonth=_sddates['month']; //当前的月
			_sddate=_sddates['date']; //当前的日期
		}
		var _dbds=null;
		
		if(Xl.isDomObject(_disablebeforeday)){
			_beforeinputdom=_disablebeforeday;
		}else{
			if(_disablebeforeday=="today"){
				_dbds=_nowdates;
			}else{	
				_dbds=getDatesFromDataFormat(_disablebeforeday);
			}
		}
		
		function getDateForFormat(d){
			
			if(Xl.isEmpty(d)){
				return false;
			}
			if(Xl.isString(d)){
				d=getDatesFromDataFormat(d);
			}
			if(d&&Xl.isObject(d)){
				var year=d['year'];
				var month=d['month'];
				var date=d['date'];
				
				if(month<10){
					month="0"+month;
				}
				if(date<10){
					date="0"+date;
				}
				
				return year.toString()+month.toString()+date.toString();
				
			}
			return false;
			
		}
		
		
		function compareDay(a,b){
			
			a=getDateForFormat(a);
			b=getDateForFormat(b);
			
			if(a==false||b==false){
				return false;
			}
			if(a>b){
				return 1;
			}
			if(a==b){
				return 0;
			}
			if(a<b){
				return -1;
			}
			
		}
		
		function getDayNodeHtm(d,y,m){
			
			var cls=[];
			var od={year:y,month:m,date:d};
			var isinvalid=false;
			if(!Xl.isEmpty(_dbds)){
				if(compareDay(_dbds,od)===1){
					cls.push('invalid');
					isinvalid=true;
				}
			}
			if(_beforeinputdom){
				
				var bfyears=$(_beforeinputdom).val();
				bfyears=getDatesFromDataFormat(bfyears);
				if(bfyears){
					if(compareDay(bfyears,od)==1){
						cls.push("invalid");
						isinvalid=true;
					}
					if(compareDay(bfyears,od)==0){
						cls.push('beforeselected');
					}
				}
				
			}
			var name=d;
			if(compareDay(_nowdates,od)===0){
				cls.push('today');
				name="今天";
			}
			if(_sddates){
				if(compareDay(_sddates,od)===0){
					cls.push('selected');
				}
			}
			
			if(cls.length!=0){
				cls='class="'+cls.join(' ')+'"';
			}else{
				cls='';
			}
			var dts='';
			if(!isinvalid){
				dts='data-year="'+y+'" data-month="'+m+'" data-date="'+d+'" data-event="select"';
			}
			
			return '<i '+cls+' '+dts+' >'+name+'</i>';
			
		}
		
		function getMothHtm(y,m){
			
			var k=0;
			var firstday=new Date(y,m-1,1).getDay(); //当月第一天的星期数
			var daycount=new Date(y,m,0).getDate(); //当月的天数
			var htm='<div class="dcom-calendar-wrap" ><div class="mt">'+y+'年'+m+'月</div><dl class="cw"><dt>';
			
			var wks=getDayName();
			
			for(var i=0;i<7; i++){
				
				var cls="";
				if(i==0){
					cls='class="sunday"';
				}else if(i==6){
					cls='class="saturday"';
				}
				
				htm+='<i '+cls+' >'+wks[i]+'</i>';
			}
			htm+='</dt>';
			
			for(var i=0;i<6;i++){
				htm+='<dd>';
				//行数
				for(var j=0;j<7;j++){
					//列，星期
					if(i==0){
						if(j<firstday){
							htm+='<i class="invalid">&nbsp;</i>';
						}else{
							htm+=getDayNodeHtm(k+1,y,m);
							k++;
						}
					}else{
						
						if(k>=daycount){
							
							break;
						}
						htm+=getDayNodeHtm(k+1,y,m);
						k++;
					}
					
				}
				htm+'</dd>';
				
			}
			htm+='</dl></div></div>';
			
			return htm;
			
		}
		
		__t.init=function(){
			
			if(!_bindinputdom){
				Xl.alert("绑定对象不存在");
				return;
			}
			__t.create();
			__t.addEvent();
			
		};
		
		__t.create=function(){
			
			var box=Xl.E("dcom-calendar-box");
			if(!box){
				box=Xl.addDivToBody("dcom-calendar-box");
				box.className="dcom-calendar-box";
			}
			
			var htm='';
			
			htm+=getMothHtm(_sdyear,_sdmonth);
			
			if(_showmonth==2){
				
				var nxm=getNextMonth(_sdyear,_sdmonth);
				
				htm+=getMothHtm(nxm['year'],nxm['month']);
			}
			
			htm+='<a href="javascript:;" title="上一月" data-event="premonth" class="topre"></a>';
			htm+='<a href="javascript:;" title="上一年" data-event="preyear" class="topreyear"></a>';
			htm+='<a href="javascript:;" title="下一年" data-event="nextyear" class="tonextyear"></a>';
			htm+='<a href="javascript:;" data-event="nextmonth" title="下一月" class="tonext"></a>';
			htm+='<div class="clear"></div>';
			
			Xl.sgData(box,"year",_sdyear);
			Xl.sgData(box,"month",_sdmonth);
			
			var oft=$(_bindinputdom).offset();
			
			var h=parseInt($(_bindinputdom).height());
			var pt=parseInt($(_bindinputdom).css("padding-top"));
			var pb=parseInt($(_bindinputdom).css("padding-bottom"));
			var bt=parseInt($(_bindinputdom).css("border-top-width"));
			var bb=parseInt($(_bindinputdom).css("border-bottom-width"));
			var top=oft.top+pt+pb+bt+bb+_dvtop+h+(Xl.isIE7()?2:10);
			var left=oft.left+_dvleft-bb/2;
			
			var w=_showmonth*266;
			var vs=Xl.getViewSize();
			if((left+w)>vs.clientWidth){
				left=vs.clientWidth-w;
			}
			$(box).css({left:left+"px",top:top+"px",display:'block'}).html(htm).width(w);
			
		};
		__t.addEvent=function(){
			
			var box=Xl.E("dcom-calendar-box");
			
			this.destroyProxyEvent(box); //解绑事件，防止重复注册事件
			__t.addProxyEvent("premonth",__t.toPreMonth);
			__t.addProxyEvent("nextmonth",__t.toNextMonth);
			__t.addProxyEvent("preyear",__t.toPreYear);
			__t.addProxyEvent("nextyear",__t.toNextYear);
			__t.addProxyEvent("select",__t.selectDay);
			__t.registProxyEvent(box,'click',true);
			
			Xl.registGlobalEvent();
		    Xl.setG("Event/rootclickFunc>_g_dcom_closecalendar",function(e){
				if(!Xl.inArray(e.target,binddomlist)){
				    __t.closePanel();
				}
			});
			
		};
		__t.showPanel=function(){
			_inputdate=$(_bindinputdom).val()||"";
			_sddates=getDatesFromDataFormat(_inputdate);
			__t.create();
			__t.addEvent();
			
		};
		__t.closePanel=function(){
			
			var box=Xl.E("dcom-calendar-box");
			if(box){
				$(box).hide();
			}
			
		};
		__t.toPreMonth=function(tid,pid){
			
			var year=Xl.sgData(pid,"year");
			var month=Xl.sgData(pid,"month");
			var dm=getPreMonth(year,month);
			_sdyear=dm['year'];
			_sdmonth=dm['month'];
		
			__t.create();
			
		};
		__t.toNextMonth=function(tid,pid){
			var year=Xl.sgData(pid,"year");
			var month=Xl.sgData(pid,"month");
			var dm=getNextMonth(year,month);
			
			_sdyear=dm['year'];
			_sdmonth=dm['month'];
			__t.create();
			
		};
		__t.toPreYear=function(tid,pid){
			var year=Xl.sgData(pid,"year");
			var month=Xl.sgData(pid,"month");
			var dm=getPreYear(year,month);
			_sdyear=dm['year'];
			_sdmonth=dm['month'];
		
			__t.create();
		};
		__t.toNextYear=function(tid,pid){
			
			var year=Xl.sgData(pid,"year");
			var month=Xl.sgData(pid,"month");
			var dm=getNextYear(year,month);
			
			_sdyear=dm['year'];
			_sdmonth=dm['month'];
			__t.create();
		};
		__t.selectDay=function(tid,pid){
			var year=Xl.sgData(tid,"year");
			var month=Xl.sgData(tid,"month");
			var date=Xl.sgData(tid,"date");
			
			if(month<10){
				month="0"+month;
			}
			if(date<10){
				date="0"+date;
			}
			var fortmatdate=year+"-"+month+"-"+date;
			$(_bindinputdom).val(fortmatdate);
			__t.closePanel();
			
			if(Xl.isFunction(_sdCallback)){
				_sdCallback(fortmatdate,year,month,date);
			}
			
		};
		
		__t.init();
		
	}
	
	
	new Xl.Class({
		outinterface: ["open"],
		isInit: false,
		init: function() {
			Xl.Dcom.addCom("calendar", this)
		},
		callouti: function(oiname, param) {
			this.iswait = false;
			if ($.inArray(oiname, this.outinterface) == -1) {
				alert("调用接口不存在", "error");
				return
			}
			if ($.isFunction(this["outi_" + oiname])) {
				this["outi_" + oiname](param || "")
			} else {
				alert("调用接口没实现")
			}
		},
		outi_open: function(p) {
			
			var creator=p['creator']||this;
			var getCalendar=p['getCalendar']||null;
			
			var mcalendar=new Calendar(p,this);
			
			if(Xl.isFunction(getCalendar)){
				getCalendar.call(creator,mcalendar,this);
			}
			
		}
		
	});
	
	
	
	
})();