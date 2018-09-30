;!function(){

    "use strict";

    /**
     * 树形结构控件
     */

    Xl.Tree=function (p) {

        var __t=this;
        __t.dataStruct=p.dataStruct||null;  //初始数据结构
        __t.wrapDom=p.wrapDom;              //容器dom对象
        __t.eachCallBack=p.eachCallBack||null; //遍历对象的回调函数
        __t.sdCallBack=p.sdCallBack||null;
        __t.flexCallBack=p.flexCallBack||null; //展开伸缩回调
        __t.selectDom=null;


        Xl.inherit(this,Xl.Event);

        __t.init=function(){
 
            if(__t.dataStruct){
                var treeObj=__t.fromDataStruct(__t.dataStruct);
                __t.createTreeView(treeObj); //创建树形结构视图
                __t._treeRootNode=treeObj;

            }
            __t.addEvent(); //注册事件

        };

        __t.getTreeRootNode=function () {

            return __t._treeRootNode;
        };

        //重新加载
        __t.reload=function (dataStruct) {

            __t.dataStruct=dataStruct||null;

            $(__t.wrapDom).empty();

            if(__t.dataStruct){
                var treeObj=__t.fromDataStruct(__t.dataStruct);
                __t.createTreeView(treeObj); //创建树形结构视图
                __t._treeRootNode=treeObj;
            }

        };

        __t.createTreeView=function (treeobj) {

            //创建视图
            __t.eachNode(treeobj,function (treenode) {
                treenode.createDomNode(__t.wrapDom); //创建dom节点
            });

        };

        __t.fromDataStruct=function(dataStruct){

            //递归创建节点
            if(!dataStruct){
                return null;
            }

            var treeObj=__t.createNode(dataStruct['data']);
            __t.recuCreateNode(dataStruct,treeObj);
            return treeObj;

        };
        __t.createNode=function(data){
          
            //创建节点
            return new Xl.TreeNode(data,__t);
            
        };
        
        __t.recuCreateNode=function (org,treenode) {

            if(!org.children){
                return;
            }
            if(Xl.isArray(org.children)){
                if(org.children.length===0){
                    return;
                }
            }
            Xl.forIn(org.children,function (i,orgNode) {
                var childrenTreeNode=treenode.insertNode(orgNode.data);
                __t.recuCreateNode(orgNode,childrenTreeNode);
            });

        };

        //遍历树节点
        __t.eachNode=function(treenode,callback){

            if(Xl.isFunction(callback)) {
                var rt = callback(treenode);
                if(rt==="__break"){
                    //是否跳出遍历
                    return rt;
                }
            }

            var childtreenodes=treenode.getChildrenNode();

            if(childtreenodes.length===0){
                return null;
            }

            Xl.forIn(childtreenodes,function (i,childtreenode) {
                __t.eachNode(childtreenode,callback);
            });
            return true;

        };

        /**
         * 注册事件
         */
        __t.addEvent=function () {

            __t.addProxyEvent("select",__t.event_SelectTreeNode);
            __t.addProxyEvent("flex",__t.event_FlexTreeNode);
            __t.registProxyEvent(__t.wrapDom);
            __t.registProxyEvent(__t.wrapDom,"dblclick");


        };

        __t.event_SelectTreeNode=function(tid,pid,eventtype,event){

            if(eventtype=="dblclick"){
                __t.event_FlexTreeNode($(tid).find("i.xltree-node-arrow").get(0),pid);
            }else{
                if(__t.selectDom){
                    $(__t.selectDom).removeClass("sd");
                }
                __t.selectDom=tid;
                $(tid).addClass("sd");

                if(Xl.isFunction(__t.sdCallBack)){

                    var bindTreeNode = $(tid).parent().get(0).__bindTreeNode;

                    __t.sdCallBack(bindTreeNode);
                }
            }

        };
        __t.event_FlexTreeNode=function (tid,pid) {

            //展开节点
            var liNodeDom=$(tid).parents("li").get(0);

            if(liNodeDom){

                var bindTreeNode=liNodeDom.__bindTreeNode;
                if(bindTreeNode){
                    var isopen=bindTreeNode.flexTreeNode();
                    if(Xl.isFunction(__t.flexCallBack)){
                        __t.flexCallBack(bindTreeNode,isopen);
                    }
                }
            }


        };



        __t.init();

    };

    //树形数据节点类
    Xl.TreeNode=function(data,treeObj) {

        //创建数据节点
        var __t=this;
        __t._treedatastruct=null;
        __t.childrenNodes=[];
        __t._i=0;
        __t._level=0;
        __t.inSiblingIndex=0; //在兄弟节点的索引位置
        __t.parentNode=null; //父节点
        __t.liDomNode=null;
        __t.treeObj=treeObj||null;
        __t.addInnerNodeLock=false;

        __t.init=function(){


            __t._treedatastruct={data:data,children:null}; //创建根节点

        };

        __t.insertNode=function(data){

            var childTreeNode=new Xl.TreeNode(data);
            childTreeNode.parentNode=this;
            childTreeNode.inSiblingIndex=__t._i;
            __t.childrenNodes[__t._i]=childTreeNode;
            __t._i++;
            childTreeNode.treeObj=__t.treeObj;
            childTreeNode._level=this._level+1;

            return childTreeNode;

        };

        __t.getTreeData=function(){

            var treestruct=__t._treedatastruct;

            var children=[];

            var childrenCount=__t.childrenNodes.length;

            if(childrenCount>0){

                for(var i=0;i<childrenCount;i++){

                    children[i]=__t.childrenNodes[i].getTreeData();
                }
            }

            if(children.length>0){
                treestruct.children=children;
            }

            return treestruct;

        };

        //获取父节点对象
        __t.getParentNode=function(){
            return __t.parentNode;
        };

        __t.getChildrenNode=function(index){

            if(Xl.isUndefined(index)){
                return __t.childrenNodes;
            }
            return __t.childrenNodes[index];

        };
        /**
         * 移除自身
         */
        __t.remove=function () {

            if(__t.parentNode===null){

                //移除的是根节点
                __t._treedatastruct=null;
                __t.childrenNodes=[];
                __t._i=0;

            }else{

                __t.parentNode.removeNode(__t.inSiblingIndex);

            }

        };

        __t.removeNode=function(index){

            var childcount=childrenNodes.length; //子节点个数
            for(var i=index;i<childcount-1;i++){
                __t.childrenNodes[i]=__t.childrenNodes[i+1];
                __t.childrenNodes[i].inSiblingIndex=i; //改变在兄弟节点顺序
            }
            __t.childrenNodes.pop(); //移除最后一个节点

        };

        __t.setData=function(data){

            __t._treedatastruct.data=data;

        };

        __t.getData=function(){

            return __t._treedatastruct.data;
        };

        /**
         * 操作创建DOM对象
         */
        __t.createDomNode=function(wrapDom){

            var data=__t.getData();
            var name='节点'; //节点名称
            var open=0;      //是否展开
            var haschildren=0; //是否有子节点
            if(Xl.isObject(data)){
                name=data.name;
                open=Xl.isNumber(data.__open)?parseInt(data.__open):data.__open||0;
                haschildren=Xl.isNumber(data.__folder)?parseInt(data.__folder):data.__folder||0;
            }else{
                name=data;
            }
            var liDomNode=document.createElement("li");

            liDomNode.__bindTreeNode=this;

            var className=[];
            if(open){
                $(liDomNode).addClass("xltree-node-open");
                liDomNode.__open=1; //代表打开
            }else{
                $(liDomNode).removeClass("xltree-node-close");
            }
            if(haschildren){
                liDomNode.__folder=1;
            }
            if(!Xl.isEmpty(className)) {
                liDomNode.className=className.join(' ');
            }
            var liClassName='';
            if(!Xl.isEmpty(data.__class)){
                liClassName=data.__class;
            }

            liDomNode.innerHTML='<a class="xltree-data '+liClassName+'" data-event="select">'+
                                 '<i class="xltree-node-arrow" data-event="flex"></i><i class="xltree-node-ffico"></i><span>'+name+'</span></a>';

            __t.liDomNode=liDomNode;

            __t.dom_setStyle(liDomNode);

            if((!__t.parentNode||__t.parentNode.liDomNode===null)&&wrapDom){

                //根节点
                $(wrapDom).addClass("xltree-box");
                __t.dom_appendToUl(wrapDom,liDomNode);

            }else{

                if(__t.parentNode.liDomNode.__folder!==1){
                    __t.parentNode.liDomNode.__folder=1;
                    __t.dom_setStyle(__t.parentNode.liDomNode); //改变文件夹样式
                }

                __t.dom_appendToUl(__t.parentNode.liDomNode,liDomNode);
            }
            __t.wrapDom=wrapDom;
        };

        __t.dom_setStyle=function(liDomNode){

            var ffname='';

            var $_ffico=$(liDomNode).find("a .xltree-node-ffico").eq(0);
            var $_arrow=$(liDomNode).find("a .xltree-node-arrow").eq(0);

            if(liDomNode.__folder===1){
                ffname='folder';
                $_ffico.addClass("xltree-node-folder-ico").removeClass("xltree-node-file-ico");
            }else{
                ffname='file';
                $_ffico.addClass("xltree-node-file-ico").removeClass("xltree-node-folder-ico");
            }
            var pnode=liDomNode.__bindTreeNode.parentNode;
            var cnode=liDomNode.__bindTreeNode;
            var bcname='';
            if(pnode&&cnode.inSiblingIndex===(pnode.childrenNodes.length||1)-1){
                bcname='bottom';
                $(liDomNode).addClass("xltree-node-libottom");
            }else{
                if(pnode===null||pnode.liDomNode===null){
                    bcname='root';
                }else{
                    bcname='center';
                    $(liDomNode).addClass("xltree-node-licenter");
                }
            }

            if(liDomNode.__open===1){
                $(liDomNode).addClass("xltree-node-open").removeClass("xltree-node-close");
                $_arrow.attr("class","xltree-node-arrow xtree-"+ffname+"-linkline-"+bcname+"-open");
            }else{
                $(liDomNode).addClass("xltree-node-close").removeClass("xltree-node-open");
                $_arrow.eq(0).attr("class","xltree-node-arrow xtree-"+ffname+"-linkline-"+bcname+"-close");
            }

        };

        __t.dom_appendToUl=function(wrapDom,liDomNode){

            var level=__t._level;
            if($(wrapDom).children("ul").length===0){
                $(wrapDom).append('<ul class="xltree-ul xltree-level'+level+'"></ul>');
            }
            $(wrapDom).find("ul").eq(0).append(liDomNode);

        };

        __t.flexTreeNode=function(){
            //伸缩树节点
            if(!__t.liDomNode.__folder){
                return;
            }
            if(__t.liDomNode.__open){
                //代表展开节点
                __t.liDomNode.__open=0;
            }else{
                __t.liDomNode.__open=1;
            }
            __t.dom_setStyle(__t.liDomNode);
            return __t.liDomNode.__open;

        };

        //移除所有内部节点
        __t.removeInnerNodes=function () {

            this.childrenNodes=[];
            this._i=0;
            $(this.liDomNode).find("ul.xltree-ul").remove();

        };

        //添加子节点
        __t.addInnerNodes=function (treenodestruct) {

            if(__t.addInnerNodeLock){
                return;
            }
            if(!Xl.isPlainObject(treenodestruct)){
                throw new Error("数据格式不正确");
            }
            // if(!Xl.isArray(treenodestruct.children)){
            //     throw new Error("子节点数据没有指明");
            // }
            __t.removeInnerNodes();
            var treeObj=this.treeObj;
            treeObj.recuCreateNode(treenodestruct,this); //附加到当前子节点
            //映射到节点视图
            treeObj.eachNode(__t,function (treenode) {

                if(treenode===__t){
                    return;
                }
                treenode.createDomNode(); //创建dom节点
            });
            __t.addInnerNodeLock=true;
        };

        //设置点击节点样式
        __t.setNameNodeClassName=function(classname){

            $(__t.liDomNode).find("a.xltree-data").eq(0).attr("class","xltree-data "+classname);

        };

        __t.init();
        
    };

}();