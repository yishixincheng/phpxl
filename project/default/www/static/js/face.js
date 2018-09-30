// JavaScript Document

var _d_face={
	items:[{i:0,name:'神马',src:'horse2_thumb.gif'},
	{i:1,name:'浮云',src:'fuyun_thumb.gif'},
	{i:2,name:'给力',src:'geili_thumb.gif'},
	{i:3,name:'围观',src:'wg_thumb.gif'},
	{i:4,name:'威武',src:'vw_thumb.gif'},
	{i:5,name:'囧',src:'j_thumb.gif'},
	{i:6,name:'礼物',src:'liwu_thumb.gif'},
	{i:7,name:'微笑',src:'smilea_thumb.gif'},
	{i:8,name:'大笑',src:'tootha_thumb.gif'},
	{i:9,name:'狂笑',src:'laugh.gif'},
	{i:10,name:'可爱',src:'tza_thumb.gif'},
	{i:11,name:'可怜',src:'kl_thumb.gif'},
	{i:12,name:'挖鼻屎',src:'kbsa_thumb.gif'},
	{i:13,name:'吃惊',src:'cj_thumb.gif'},
	{i:14,name:'害羞',src:'shamea_thumb.gif'},
	{i:15,name:'挤眼',src:'zy_thumb.gif'},
	{i:16,name:'闭嘴',src:'bz_thumb.gif'},
	{i:17,name:'鄙视',src:'bs2_thumb.gif'},
	{i:18,name:'爱你',src:'lovea_thumb.gif'},
	{i:19,name:'流泪',src:'sada_thumb.gif'},
	{i:20,name:'偷笑',src:'heia_thumb.gif'},
	{i:21,name:'亲亲',src:'qq_thumb.gif'},
	{i:22,name:'生病',src:'sb_thumb.gif'},
	{i:23,name:'太开心',src:'mb_thumb.gif'},
	{i:24,name:'懒得理你',src:'ldln_thumb.gif'},
	{i:25,name:'右哼哼',src:'yhh_thumb.gif'},
	{i:26,name:'左哼哼',src:'zhh_thumb.gif'},
	{i:27,name:'嘘',src:'x_thumb.gif'},
	{i:28,name:'衰',src:'cry.gif'},
	{i:29,name:'委屈',src:'wq_thumb.gif'},
	{i:30,name:'吐',src:'t_thumb.gif'},
	{i:31,name:'打哈欠',src:'k_thumb.gif'},
	{i:32,name:'抱抱',src:'bba_thumb.gif'},
	{i:33,name:'怒',src:'angrya_thumb.gif'},
	{i:34,name:'疑问',src:'yw_thumb.gif'},
	{i:35,name:'馋嘴',src:'cza_thumb.gif'},
	{i:36,name:'拜拜',src:'88_thumb.gif'},
	{i:37,name:'思考',src:'sk_thumb.gif'},
	{i:38,name:'汗',src:'sweata_thumb.gif'},
	{i:39,name:'困',src:'sleepya_thumb.gif'},
	{i:40,name:'睡觉',src:'sleepa_thumb.gif'},
	{i:41,name:'钱',src:'money_thumb.gif'},
	{i:42,name:'失望',src:'sw_thumb.gif'},
	{i:43,name:'酷',src:'cool_thumb.gif'},
	{i:44,name:'花心',src:'hsa_thumb.gif'},
	{i:45,name:'哼',src:'hatea_thumb.gif'},
	{i:46,name:'鼓掌',src:'gza_thumb.gif'},
	{i:47,name:'晕',src:'dizzya_thumb.gif'},
	{i:48,name:'悲伤',src:'bs_thumb.gif'},
	{i:49,name:'抓狂',src:'crazya_thumb.gif'},
	{i:50,name:'黑线',src:'h_thumb.gif'},
	{i:51,name:'阴险',src:'yx_thumb.gif'},
	{i:52,name:'怒骂',src:'nm_thumb.gif'},
	{i:53,name:'心',src:'hearta_thumb.gif'},
	{i:54,name:'伤心',src:'unheart.gif'},
	{i:55,name:'猪',src:'pig.gif'},
	{i:56,name:'蛋糕',src:'cake.gif'},
	{i:57,name:'笑哈哈',src:'lxhwahaha_thumb.gif'},
	{i:58,name:'泪流满面',src:'lxhtongku_thumb.gif'},
	{i:59,name:'推撞',src:'dintuizhuang_thumb.gif'},
	{i:60,name:'石化',src:'shihua_thumb.gif'},
	{i:61,name:'伤不起',src:'shangbuqi.gif'},
	{i:62,name:'秒杀',src:'miaosha.gif'},
	{i:63,name:'亲',src:'qin.gif'},
	{i:64,name:'有木有',src:'youmuyou.gif'},
	{i:65,name:'便便',src:'bianbian.gif'},
	{i:66,name:'ok',src:'ok_thumb.gif'},
	{i:67,name:'good',src:'good_thumb.gif'},
	{i:68,name:'弱',src:'sad_thumb.gif'},
	{i:69,name:'不要',src:'no_thumb.gif'}],
	getfacesrc:function(src){
		//获得全路径
		return '/static/images/face/'+src;
	},
	formatcontent:function(content){
		//将表情标签替换为路径
		if($.isEmpty(content)){return '';}
		return content.replace(/(\[([^\[]+)\])/g,function($1){
			
			for(var i in _d_face.items){
				if("["+_d_face.items[i]['name']+"]"==$1){
					return '<img title="'+_d_face.items[i]['name']+'" src="'+_d_face.getfacesrc(_d_face.items[i]['src'])+'" />';
				}
			}
		});
	}
};

dssys.plugs.popfacecard=function(thisid,func){
//表情面板
var t=this;
t.init=function(){
	//先获得表情主体div
	t.createcard(t.getfacediv());
	t.addevent();
	
};
t.getfacediv=function(){
	
	var A=['<div class="if_p_facecard_body">',
	'<ul>'];
	
	var items=_d_face.items;
	var len=items.length;
	
	for(var i=0;i<len;i++){
		A.push(['<li class="if_p_faceitem" facetxt="[',items[i]['name'],']">',
		'<img src="',_d_face.getfacesrc(items[i]['src']),'" title="',items[i]['name'],'" /></li>'].join(''));
	}
	A.push('</ul></div>');
	return A.join('');
};
t.createcard=function(div){
				//构造明片
	t.izone=new dssys.cpopcard(320,280,13856969832,true);
	t.izone.createcard();
	t.izone.filldiv(div);
	//设置显示坐标
	var oft=$(thisid).offset();
	var w=$(thisid).width(),h=$(thisid).height();
	var top=oft.top+h+8;
	var left=oft.left-25;
	t.izone.setcardposition(left,top);
	t.izone.setarrdirection('up',30,-8);
};
t.addevent=function(){
	//注册事件
	$(".if_p_faceitem").click(function(){
			if($.isFunction(func)){
				func.call(this);
			}
			//关闭对话框
			t.izone.closewindow();
	});
}

t.init();	
}


