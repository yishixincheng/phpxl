<?php

namespace xl\classs;

use xl\base\XlClassBase;

import("@xl.third.phpexcel.PHPExcel");

class OpexcelClass extends XlClassBase{

    public function __construct()
    {
        parent::__construct();
    }
    public function exportExcel($datastruct){

        ob_end_clean();
        $d_header=$datastruct['header'];
        $d_list=$datastruct['list'];
        $d_title=$datastruct['title'];


        if(!(is_array($d_header)&&is_array($d_list))){
            return;
        }

        $objPHPExcel=new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(9);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);
        $objActiveSheet=$objPHPExcel->setActiveSheetIndex(0);

        foreach($d_header as $k=>$v){
            $objActiveSheet->setCellValue($k.'1', $v['name']);
            //$objActiveSheet->getColumnDimension($k)->setAutoSize(true);
            $objActiveSheet->getColumnDimension($k)->setWidth(20);
            $objActiveSheet->getStyle($k.'1')->getFont()->setBold(true);
        }

        $i=2;
        foreach($d_list as $row)
        {
            foreach($d_header as $k=>$v){
                $objActiveSheet->setCellValueExplicit($k.$i,$row[$k],\PHPExcel_Cell_DataType::TYPE_STRING);//设置单元格的内容为字符串格式
            }
            $i++;
        }

        $objPHPExcel->getActiveSheet()->setTitle($d_title);
        $objPHPExcel->setActiveSheetIndex(0);

        $filename="$d_title".date('YmdHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        $objWriter->save('php://output');
        exit;

    }
    private function getAbcIndex($index){

        if(!$this->_abcindex){

            $this->_abcindex=array('-','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
                'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX',
                'AY','AZ');
        }

        if($index==0){
            return $this->_abcindex;
        }


        return $this->_abcindex[$index];

    }
    private function _formatDataList($columnmap,$datalist){

        $abcindex=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX',
            'AY','AZ');

        $datastruct=array();
        $header=array();
        $list=array();

        $abcmap=array();
        foreach($columnmap as $k=>$v){
            $header[$abcindex[$k]]=$v;
            $abcmap[$v['key']]=$abcindex[$k];
        }
        foreach($datalist as $dl){

            if(is_array($dl)){

                $listnode=array();
                foreach($dl as $key=>$val){

                    if($abcmap[$key]){
                        $listnode[$abcmap[$key]]=$val;
                    }

                }
                $list[]=$listnode;

            }
        }

        return array('header'=>$header,'list'=>$list);

    }
    public function exportFormatDataToExcel($ds){

        //根据格式化数据导出excel表格

        $d_title=$ds['title'];
        $d_columnnum=$ds['columnnum'];
        $d_rownum=$ds['rownum'];
        $d_attachdata=$ds['attachdata'];
        if(!is_array($d_attachdata)){
            return;
        }

        $objPHPExcel=new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(9);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);
        $objActiveSheet=$objPHPExcel->setActiveSheetIndex(0);


        foreach($d_attachdata as $dad){

            if($dad['type']=="show"){

                $row=$dad['row'];
                $column=$dad['column'];
                if(preg_match("/^(\d+)-(\d+)$/",$column,$mt)){
                    $abcindex1=$this->getAbcIndex($mt[1]);
                    $abcindex2=$this->getAbcIndex($mt[2]);
                    $objActiveSheet->setCellValue($abcindex1.$row,$dad['text']);
                    $objActiveSheet->mergeCells($abcindex1.$row.":".$abcindex2.$row);
                    $rowcolumnno=$abcindex1.$row;
                }else{
                    $abcindex=$this->getAbcIndex($column);
                    $objActiveSheet->setCellValue($abcindex.$row,$dad['text']);
                    $rowcolumnno=$abcindex.$row;
                }
                if($dad['bold']){
                    $objActiveSheet->getStyle($rowcolumnno)->getFont()->setBold(true);
                }
                if($dad['fontsize']){
                    $objActiveSheet->getStyle($rowcolumnno)->getFont()->setSize($dad['fontsize']);
                }
                if($dad['center']){
                    $objActiveSheet->getStyle($rowcolumnno)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }

            }else if($dad['type']=="list"){

                //列表
                $row=(int)$dad['row'];
                $columnmap=$dad['columnmap'];
                $dataarr=$this->_formatDataList($columnmap,$dad['datalist']);
                $d_header=$dataarr['header'];
                $d_list=$dataarr['list'];

                foreach($d_header as $k=>$v){
                    $objActiveSheet->setCellValue($k.$row, $v['name']);
                    $objActiveSheet->getColumnDimension($k)->setWidth(20);
                    $objActiveSheet->getStyle($k.$row)->getFont()->setBold(true);
                }

                $i=$row+1;
                foreach($d_list as $rowindex)
                {
                    foreach($d_header as $k=>$v){
                        $objActiveSheet->setCellValueExplicit($k.$i,$rowindex[$k],\PHPExcel_Cell_DataType::TYPE_STRING);//设置单元格的内容为字符串格式
                    }
                    $i++;
                }
                $objPHPExcel->getActiveSheet()->setTitle($d_title);
                $objPHPExcel->setActiveSheetIndex(0);

            }
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$d_title.date("YmdHis").'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');


        exit;
    }
    public function uploadGetData($filename='excelfile'){

        $file_ext=pathinfo($_FILES[$filename]['name'], PATHINFO_EXTENSION);
        $file=$_FILES[$filename]['tmp_name'];
        $filetype=$_FILES[$filename]['type'];
        if(is_uploaded_file($file)){
            //已经上传到临时文件，读取
            if(($file_ext=="xlsx"&&$filetype=="application/octet-stream")||($filetype=="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")){
                $type=2007;
            }elseif(($file_ext=="xls"&&$filetype=="application/octet-stream")||($filetype=="application/vnd.ms-excel"||$filetype=="application/x-excel")){
                $type=2003;
            }else{
                return ['status'=>'fail','msg'=>'上传文件格式不正确'];
            }
            switch($type)
            {
                case '2003':
                    $objReader = \PHPExcel_IOFactory::createReader('Excel5');
                    break;
                case '2007':
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    break;
            }

            if(!isset($objReader)){
                return ['status'=>'fail','msg'=>'excel版本不支持'];
            }

            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($file);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $highestRow = $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);

            $dataArray=array();
            for ($row = 1; $row <= $highestRow; ++$row)
            {
                $orderrow=array();
                for ($col = 0; $col < $highestColumnIndex; ++$col)
                {
                    $val=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                    $orderrow[]=$val;
                }
                $dataArray[]=$orderrow;
            }

            if(empty($dataArray)){

                return ['status'=>'fail','msg'=>'Excel表数据不能为空'];
            }
            if(count($dataArray)==1){
                if(is_array($dataArray[0])&&count($dataArray[0])==1){
                    if(empty($dataArray[0][0])){
                        return ['status'=>'fail','msg'=>'Excel表数据不能为空'];
                    }
                }
            }
            return ['status'=>'success','result'=>$dataArray];

        }

        return ['status'=>'fail','msg'=>'上传文件错误'];

    }

}
