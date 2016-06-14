<?php

/**
 * excel 文件 处理类
 * @author liuwh
 * @date 2014-7-8
 */
namespace Admin\Org\Util;

use Think\Think;

class ExcelComponent extends Think {
	protected $_xls;
	protected $_row = 1;
	protected $_maxRow = 0;
	protected $_tableParams;
	protected $_head = array(
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
	);

	public function __construct() {
		vendor ( "PHPExcel.PHPExcel" );
	}

	public function createWorksheet() {
		$this->_xls = new \PHPExcel ();
		$this->_row = 1;
		return $this;
	}

	/**
	 *
	 * @param string $file        	
	 * @return ExcelComponent
	 */
	public function loadWordsheet($file) {
		$this->_xls = \PHPExcel_IOFactory::load ( $file );
		$this->setActiveSheet ( 0 );
		$this->_row = 1;
		
		return $this;
	}

	/**
	 *
	 * @param string $name        	
	 * @return ExcelComponent
	 */
	public function addSheet($name) {
		$index = $this->_xls->getSheetCount ();
		$this->_xls->createSheet ( $index )->setTitle ( $name );
		$this->setActiveSheet ( $index );
		
		return $this;
	}

	public function setActiveSheet($sheet) {
		$this->_maxRow = $this->_xls->setActiveSheetIndex ( $sheet )->getHighestRow ();
		$this->_row = 1;
		
		return $this;
	}

	/**
	 * Overloaded __call
	 * Move call to PHPExcel instance
	 *
	 * @param
	 *        	string function name
	 * @param
	 *        	array arguments
	 * @return the return value of the call
	 */
	public function __call($name, $arguments) {
		return call_user_func_array ( [ 
				$this->_xls,
				$name 
		], $arguments );
	}

	public function setSheetName($name) {
		$this->_xls->getActiveSheet ()->setTitle ( $name );
		
		return $this;
	}

	public function setDefaultFont($name, $size) {
		$this->_xls->getDefaultStyle ()->getFont ()->setName ( $name );
		$this->_xls->getDefaultStyle ()->getFont ()->setSize ( $size );
		
		return $this;
	}

	public function setRow($row) {
		$this->_row = ( int ) $row;
		
		return $this;
	}

	/**
	 * Start table - insert table header and set table params
	 *
	 * @param array $data
	 *        	data with format:
	 *        	label - table heading
	 *        	width - numeric (leave empty for "auto" width)
	 *        	filter - true to set excel filter for column
	 *        	wrap - true to wrap text in column
	 * @param array $params
	 *        	table parameters with format:
	 *        	offset - column offset (numeric or text)
	 *        	font - font name of the header text
	 *        	size - font size of the header text
	 *        	bold - true for bold header text
	 *        	italic - true for italic header text
	 * @return $this for method chaining
	 */
	public function addTableHeader($data, $params = array()) {
		// offset
		$offset = 0;
		if (isset ( $params ['offset'] )) {
			$offset = is_numeric ( $params ['offset'] ) ? ( int ) $params ['offset'] : \PHPExcel_Cell::columnIndexFromString ( $params ['offset'] );
		}
		// font name
		if (isset ( $params ['font'] )) {
			$this->_xls->getActiveSheet ()->getStyle ( $this->_row )->getFont ()->setName ( $params ['font'] );
		}
		// font size
		if (isset ( $params ['size'] )) {
			$this->_xls->getActiveSheet ()->getStyle ( $this->_row )->getFont ()->setSize ( $params ['size'] );
		}
		// bold
		if (isset ( $params ['bold'] )) {
			$this->_xls->getActiveSheet ()->getStyle ( $this->_row )->getFont ()->setBold ( $params ['bold'] );
		}
		// italic
		if (isset ( $params ['italic'] )) {
			$this->_xls->getActiveSheet ()->getStyle ( $this->_row )->getFont ()->setItalic ( $params ['italic'] );
		}
		// set internal params that need to be processed after data are inserted
		$this->_tableParams = [ 
				'header_row' => $this->_row,
				'offset' => $offset,
				'row_count' => 0,
				'auto_width' => [ ],
				'filter' => [ ],
				'wrap' => [ ] 
		];
		
		foreach ( $data as $d ) {
			// set label
			$this->_xls->getActiveSheet ()->setCellValueByColumnAndRow ( $offset, $this->_row, $d ['label'] );
			// set width
			if (isset ( $d ['width'] ) && is_numeric ( $d ['width'] )) {
				$this->_xls->getActiveSheet ()->getColumnDimensionByColumn ( $offset )->setWidth ( ( float ) $d ['width'] );
			} else {
				$this->_tableParams ['auto_width'] [] = $offset;
			}
			// filter
			if (isset ( $d ['filter'] ) && $d ['filter']) {
				$this->_tableParams ['filter'] [] = $offset;
			}
			// wrap
			if (isset ( $d ['wrap'] ) && $d ['wrap']) {
				$this->_tableParams ['wrap'] [] = $offset;
			}
			$offset ++;
		}
		$this->_row ++;
		return $this;
	}

	/**
	 * Write array of data to current row
	 *
	 * @param array $data        	
	 * @return $this for method chaining
	 */
	public function addTableRow($data, $validate = 0) {
		$offset = $this->_tableParams ['offset'];
		
		foreach ( $data as $d ) {
			$this->_xls->getActiveSheet ()->setCellValueByColumnAndRow ( $offset ++, $this->_row, $d );
		}
		$this->_row ++;
		$this->_tableParams ['row_count'] ++;
		return $this;
	}

	/**
	 * 导出指定列表类型
	 *
	 * @param string $data        	
	 * @param number $offest        	
	 * @param number $start        	
	 * @param number $max        	
	 * @return ExcelComponent
	 */
	public function addTableRowValiadte($data, $offest, $start = 2, $max = 300) {
		$max = $max - $start;
		for($i = $start; $i <= $max; $i ++) {
			$this->_xls->getActiveSheet ()->getCellByColumnAndRow ( $offest, $i )->getDataValidation ()->setType ( \PHPExcel_Cell_DataValidation::TYPE_LIST )->setErrorStyle ( \PHPExcel_Cell_DataValidation::STYLE_INFORMATION )->setAllowBlank ( false )->setShowInputMessage ( true )->setShowErrorMessage ( true )->setShowDropDown ( true )->setErrorTitle ( '输入的值有误' )->setError ( '您输入的值不在下拉框列表内.' )->setPromptTitle ( '列表类型' )->setFormula1 ( '"' . $data . '"' );
		}
		return $this;
	}

	/**
	 * End table - set params and styles that required data to be inserted first
	 *
	 * @return $this for method chaining
	 */
	public function addTableFooter() {
		// auto width
		foreach ( $this->_tableParams ['auto_width'] as $col ) {
			$this->_xls->getActiveSheet ()->getColumnDimensionByColumn ( $col )->setAutoSize ( true );
		}
		// filter (has to be set for whole range)
		if (count ( $this->_tableParams ['filter'] )) {
			$this->_xls->getActiveSheet ()->setAutoFilter ( \PHPExcel_Cell::stringFromColumnIndex ( $this->_tableParams ['filter'] [0] ) . ($this->_tableParams ['header_row']) . ':' . \PHPExcel_Cell::stringFromColumnIndex ( $this->_tableParams ['filter'] [count ( $this->_tableParams ['filter'] ) - 1] ) . ($this->_tableParams ['header_row'] + $this->_tableParams ['row_count']) );
		}
		// wrap
		foreach ( $this->_tableParams ['wrap'] as $col ) {
			$this->_xls->getActiveSheet ()->getStyle ( \PHPExcel_Cell::stringFromColumnIndex ( $col ) . ($this->_tableParams ['header_row'] + 1) . ':' . \PHPExcel_Cell::stringFromColumnIndex ( $col ) . ($this->_tableParams ['header_row'] + $this->_tableParams ['row_count']) )->getAlignment ()->setWrapText ( true );
		}
		return $this;
	}

	/**
	 * Write array of data to current row starting from column defined by offset
	 *
	 * @param array $data        	
	 * @return $this for method chaining
	 */
	public function addData($data, $offset = 0) {
		// solve textual representation
		if (! is_numeric ( $offset )) {
			$offset = \PHPExcel_Cell::columnIndexFromString ( $offset );
		}
		foreach ( $data as $d ) {
			$this->_xls->getActiveSheet ()->setCellValueByColumnAndRow ( $offset ++, $this->_row, $d );
		}
		$this->_row ++;
		
		return $this;
	}

	/**
	 * Get array of data from current row
	 *
	 * @param int $max        	
	 * @return array row contents
	 */
	public function getTableData($max = 100) {
		if ($this->_row > $this->_maxRow) {
			return false;
		}
		$data = [ ];
		
		for($col = 0; $col < $max; $col ++) {
			$data [] = $this->_xls->getActiveSheet ()->getCellByColumnAndRow ( $col, $this->_row )->getValue ();
		}
		$this->_row ++;
		
		return $data;
	}

	/**
	 * 获取表格总行数
	 *
	 * @return number
	 */
	public function getHightRow() {
		return $this->_xls->getActiveSheet ()->getHighestRow ();
	}

	/**
	 * 获取一行表格数据
	 *
	 * @param integer $row        	
	 * @param number $max        	
	 * @param array $imageRow
	 *        	有图片列
	 * @return multitype:mixed
	 */
	public function getOneTable($row, $max = 20, array $imageRow = []) {
		$data = [ ];
		for($col = 0; $col < $max; $col ++) {
			$data [] = $this->_xls->getActiveSheet ()->getCellByColumnAndRow ( $col, $row )->getFormattedValue ();
		}
		return $data;
	}

	/**
	 * 返回
	 * @param unknown $colName
	 */
	public function getColIndex($colName){
		return strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $colName);
	}
	/**
	 * 获取Excel中图片
	 * 
	 * @param unknown $basePath        	
	 * @return Ambigous <string, unknown>
	 */
	public function getImage($basePath) {
		foreach ( $this->_xls->getActiveSheet ()->getDrawingCollection () as $drawing ) {
			$xy = $drawing->getCoordinates ();
			$path = $basePath;
			if ($drawing instanceof \PHPExcel_Worksheet_Drawing) {
				$filename = $drawing->getPath ();
				$imageFileName = $drawing->getIndexedFilename ();
				$path = $path . uniqid().$drawing->getIndexedFilename ();
				copy ( $filename, $path );
				$result [$xy][] = $path;
			}
		}
		return $result;
	}

	/**
	 * 获取phpexcel 接口
	 *
	 * @param
	 *        	$writer
	 * @return PHPExcel_Writer_Iwriter
	 */
	public function getWriter($writer) {
		return \PHPExcel_IOFactory::createWriter ( $this->_xls, $writer );
	}

	/**
	 * 以文件形式保存
	 *
	 * @param string $file
	 *        	文件路径
	 * @param string $writer        	
	 * @return bool
	 */
	public function save($file, $writer = 'Excel2007') {
		$objWriter = $this->getWriter ( $writer );
		return $objWriter->save ( $file );
	}
    /**
     * 表头数据
     * @param 表头数据
     */
	public function head($head,$font="Candara",$size="16",$width="50"){
		foreach($head as $key=>$v){
			$this->_xls->getActiveSheet()->getStyle($this->_head[$key]."1")->getFont()->setName('Candara');
			$this->_xls->getActiveSheet()->getColumnDimension($this->_head[$key])->setWidth($width);
			$this->_xls->getActiveSheet()->getStyle($this->_head[$key]."1")->getFont()->setSize($size);
			$this->_xls->getActiveSheet()->setCellValue($this->_head[$key]."1", $v);
		}
		return $this;
	}
	/**
	 * excel数据
	 * @param  $data
	 */
	public function listData($data,$key){
		$i=2;
		foreach($data as $v){
			foreach($key as $k=>$vo){
				if(is_string($v[$vo])){
					$this->_xls->getActiveSheet()->setCellValueExplicit($this->_head[$k].$i,$v[$vo],'s');//设置成字窜格式
				}else{
					$this->_xls->getActiveSheet()->setCellValue($this->_head[$k].$i,$v[$vo]);
				}
			}
			$i++;
		}
		return $this;
	}
	/**
	 * 浏览器输出excel
	 *
	 * @param string $file
	 *        	文件名
	 * @param string $writer        	
	 * @return exit on this call
	 */
	public function output($filename = 'export.xlsx', $writer = 'Excel2007') {
		ob_end_clean ();
		// 设置http header
		header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
		header ( 'Cache-Control: max-age=0' );
		// 输出文件
		$objWriter = $this->getWriter ( $writer );
		$objWriter->save ( 'php://output' );
		exit ();
	}

	/**
	 * 回收内存
	 *
	 * @return void
	 */
	public function freeMemory() {
		$this->_xls->disconnectWorksheets ();
		unset ( $this->_xls );
	}

	/**
     * excel表高级设置 可设置列宽和对齐方式等
     * @param array $advancedHead 表设置
     * @param string $horizontal 水平对齐方式
     * @param int $width 列宽
     * @param int $size 字体大小
     * @param string $font 字体
	 * @return obj
     */
	public function advancedHead($advancedHead, $horizontal="left", $width="50", $size="16", $font="Candara"){
		foreach($advancedHead as $key => $val){
			$this->_xls->getActiveSheet()->getStyle($this->_head[$key]."1")->getFont()->setName('Candara');
			$this->_xls->getActiveSheet()->getColumnDimension($this->_head[$key])->setWidth( $val['width'] ? $val['width'] : $width );
			$this->_xls->getActiveSheet()->getStyle($this->_head[$key])->getAlignment()->setHorizontal( $val['horizontal'] ? $val['horizontal'] : $horizontal );
			$this->_xls->getActiveSheet()->getStyle($this->_head[$key]."1")->getFont()->setSize($size);
			$this->_xls->getActiveSheet()->setCellValue($this->_head[$key]."1", $val['title']);
		}
		return $this;
	}

}