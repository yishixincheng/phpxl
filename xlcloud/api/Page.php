<?php

namespace xl\api;

/**
 * Class Page
 * @package xl\api
 * 分页代码实现
 */

final class Page extends XlApiBase{

    protected $page=1;      //当前页
    protected $shownum=5; //显示数量默认是5
    protected $allpage;   //总页数
    protected $urlfunc;   //url生成函数
    protected $divclass="__xlfenye_body";  //分页classname
    protected $isblank=false;   //是否新窗口打开

    public function run(){

        $cp=$this->page;
        $showp=$this->shownum;
        $allp=$this->allpage;
        $func=$this->urlfunc;

        if(!is_callable($func)){
            return '';
        }

        if($this->allpage<=1){
            return '';
        }
        $cp=$cp?$cp:1;
        $showp=$showp>$allp ? $allp:$showp; //当前显示的页数
        $spl=floor($showp/2);
        $b=$cp-$spl<=0 ? 1:($cp-$spl);//计算显示的起始页
        $e=$cp+$spl>=$allp ? $allp:($cp+$spl);//计算终止页
        if(($cp+$spl)>=$allp){
            $b=$allp-$showp+1;
        }
        $b=$b<1 ?1 :$b;
        $ht= '<div class="'.$this->divclass.'">';
        $ht.= $cp>1 ? '<a href="'.$func("page",($cp-1)).'" class="left_arrow">上一页</a>':"";

        $blankhtm=$this->isblank?'target="__blank"':'';

        if($b>=2)
        {
            $ht.='<a '.$blankhtm.' href="'.$func("page",1).'">1</a>';
            if($b>2)
            {
                $ht.='<a class="split">...</a>';
            }
        }

        for($i=$b;$i<=$e;$i++)
        {
            if($i==$cp){
                $ht.='<a '.$blankhtm.' class="curr" href="'.$func("page",$i).'">'.$i.'</a>';
            }else{
                $ht.='<a '.$blankhtm.' href="'.$func("page",$i).'" >'.$i.'</a>';
            }
        }
        if($e<$allp-1)
        {
            $ht.='<a class="split">...</a>';
            $ht.='<a '.$blankhtm.' href="'.$func("page",$allp).'" >'.$allp.'</a>';
        }
        $ht.= $cp<$allp ? '<a class="right_arrow" href="'.$func("page",$cp+1).'" >下一页</a>':""; //当前不是尾页显示下一页

        $ht.='<span class="allpage">共'.$allp.'页</span>';

        $ht.= '</div>';

        return $ht;

    }


}