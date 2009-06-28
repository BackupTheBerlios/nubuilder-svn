<?php
/*
** File:           run_report_pdf_v2.php
** Author:         nuSoftware
** Created:        2007/04/26
** Last modified:  2009/06/22
**
** This file is part of the nuBuilder source package and is licensed under the
** GPLv3. For support on developing in nuBuilder, please visit the nuBuilder
** wiki and forums. For details on contributing a patch for nuBuilder, please
** visit the `Project Contributions' forum.
**
**   Website:  http://www.nubuilder.com
**   Wiki:     http://wiki.nubuilder.com
**   Forums:   http://forums.nubuilder.com
*/

define('FPDF_FONTPATH','fpdf/fonts/');
require('fpdf/fpdf.php');
include_once('report_object.php');   //--build report object used for making HTML and PDF


class nuPDF extends FPDF{

	public $page_no           = 0;
	public $section_top       = 0;
	public $total_pages       = 0;	
	public $imagecount        = 0;
	public $temp_filename     = '';
	public $html              = array();
	public $timestamp         = '';
	
    function __construct($orientation,$unit,$format){
		parent::__construct($orientation,$unit,$format);
		$this->temp_filename  = dirname(__FILE__) . '/temppdf/' . TT();
		$this->html           = html_colors();
		$this->timestamp      = date('Y-m-d H:i:s');
	}

	public function buildSection($S){
	

		if($S->height != '0' or $S->height !='0px'){//--dont build it if the height is zero
		
			for($c = 0 ; $c < count($S->controls) ; $c++){
				$this->buildObject($S->controls[$c]);
			}
			$this->section_top = $this->section_top + hresize($S->height) + 0;
        }
	}

	public function buildObject($C){

		$lineFormat       = formatForPDFandHTML($C->text_string[0]);       //-- get any formatting stuff from the beginning of the string eg. #BOLD#
		$can_grow         = $C->can_grow == '1';
		$temp_name        = $this->temp_filename . $this->imagecount;
		$this->imagecount = $this->imagecount + 1;
		$draw             = 'DF';
		$BC               = $C->border_color;
		if(lresize($C->border_width) == 0 or $BC == 'transparent' or strtolower($BC) == 'white' or strtolower($BC) == '#ffffff' or strtolower($BC) == 'ffffff'){
			$draw         = str_replace('D', '', $draw);
		}
		$BC               = $C->background_color;
		if($BC == 'transparent' or strtolower($BC) == 'white' or strtolower($BC) == '#ffffff' or strtolower($BC) == 'ffffff'){
			$draw         = str_replace('F', '', $draw);
		}
		if(count($lineFormat['bgcolor']) == 0){  //-- no specific bg color for this object
			$color        = rgbcolor($C->background_color, $this->html);
		}else{
			$color        = $lineFormat['bgcolor'];
			if(strpos($draw, 'F') === false){
				$draw     = $draw.'F';
			}
		}
		$this->SetFillColor($color['r'], $color['g'], $color['b']);
		$color            = rgbcolor($C->border_color, $this->html);
		$this->SetDrawColor($color['r'], $color['g'], $color['b']);

		if(count($lineFormat['color']) == 0){  //-- no specific color for this object
			$color        = rgbcolor($C->color, $this->html);
		}else{
			$color        = $lineFormat['color'];
		}

		$this->SetTextColor($color['r'], $color['g'], $color['b']);
		$field_width      = hresize($C->width);
		$this->SetLineWidth(lresize($C->border_width));
		if($C->type != 'Graph'){
			if($draw != ''){
				$this->Rect(hresize($C->left), vresize($C->top) + $this->section_top+2, $field_width, hresize($C->height) + hresize($C->extra_growth),$draw);
			}
		}

		if(strpos($lineFormat['style'], 'B') === false){  //-- no specific boldness for this object
			if($C->font_weight == 'bold'){
				$weight      = 'B';
			}else{
				$weight      = '';
			}
		}else{
			$weight      = 'B';
		}
		
		$this->SetFont($C->font_name,$weight ,fresize($C->font_size));
		$offset_top      = hresize($C->original_height) * .2;
		
		if($C->type == 'Graph'){
			if($C->format == 'image'){
				$PT          = nuRunQuery("SELECT zzsys_image_id FROM zzsys_image WHERE sim_code = '$C->graph'");
				$PR          = db_fetch_object($PT);
				$logo        = fopen(getPHPurl()."formimage.php?dir=".$_GET['dir']."&iid=$PR->zzsys_image_id","r");
			}else{
				$chop_at     = strrpos($C->text_string[0], " width='");  //-- chops off the "width='523' height='246' />" bit of the image
				$url         = substr($C->text_string[0],10, $chop_at - 11);
				$url		 = str_replace(" ","%20",$url);
				$logo        = fopen(getPHPurl().$url,"r");
			}

			file_put_contents($temp_name.'.png',$logo);

			if (filesize($temp_name . '.png') > 0) {	

				$im          = imagecreatefrompng($temp_name.'.png');
				imageinterlace($im,0);
				imagepng($im,$temp_name . '.png');		
				$this->Image($temp_name . '.png', hresize($C->left), vresize($C->top) + $this->section_top+2, $field_width, hresize($C->height) + hresize($C->extra_growth)); 
				unlink($temp_name . '.png');
			}

		}else{
		
			$string_array         = array();

			if($can_grow){                                 //-- already an array
				$string_array     = $C->text_string;
			}else{                                         //-- create an array
				$swap_br          = array(chr(13), '<br/>','<Br />','<Br/>','<BR />','<BR/>');
				$the_string       = str_replace($swap_br, '<br />', $C->text_string[0]);
				$string_array     = explode("<br />", $the_string);
			}
			for($ts     = 0; $ts < count($string_array) ; $ts++){

				$data             = '';
				if($ts == 0){
					$data         = $lineFormat['string']; //-- string with formatting removed
				}else{
					$data         = $string_array[$ts];
				}
				$data             = str_replace('#totalNumberOfPages#', $this->total_pages, $data);
				$data             = str_replace('#thePageNumber#', $this->page_no, $data);
				$data             = str_replace('&nbsp;', ' ', $data);
				$data             = str_replace('&nbsp', ' ', $data);
				if(strtoupper($data) == '=NOW()' or strtoupper($data) == 'NOW()'){
					$data         = $this->timestamp;
				}


				$text_width       = $this->GetStringWidth($data);
				$resized_length   = fwresize($text_width);
				$offset_left      = 0;
				if($C->text_align == 'right'){
					$offset_left  = vresize($C->width - $resized_length);
				}
				if($C->text_align == 'center'){
					$offset_left  = vresize(($C->width - $resized_length)/2);
				}

				if(!$can_grow and count($string_array) > 1){                              //-- dont realign top
					$text_top     = vresize($C->top) + ((fresize($C->font_size)/2) * (1 * (1 + $ts))) + $this->section_top + 2;
				}else{
					$text_top     = vresize($C->top) + (hresize($C->original_height) * (1 * (1 + $ts))) + $this->section_top + 2;
				}
				$left             = vresize($C->left);

				$this->Text(hresize($C->left) + $offset_left, ($text_top - $offset_top), $data);
			}
		}

		if($cHeight != 0){

		}
	}


	
}	

function run_pdf_report(){

	build_report_object();

}

function build_report($report_object){   //-- this function is called from report_object.php


	if($report_object->paper_type == ''){
		$report_object->paper_type        = 'A4';
	}
	if($report_object->orientation == ''){
		$report_object->orientation       = 'P';
	}

	$pdf                                  = new nuPDF($report_object->orientation, 'mm', $report_object->paper_type);
		
	$page_height                          = $report_object->page_height;
	$sectionArray                         = $GLOBALS['nuSections'];
	$pdf->total_pages                     = $sectionArray[count($sectionArray)-1]['the_page'] +1;
    $obj                                  = array();
    $st                                   = '';
	$the_page                             = -1;


	
	
	for($G = 0 ; $G < count($sectionArray) ; $G++){

		unset($section_array);
		unset($record);
		unset($controls);
		unset($S);

		$nuS = gzuncompress($sectionArray[$G]['the_section']);
		eval($nuS);                                                                           //-- make section_array, record and controls from table record
		$S                                = new nuSection($emptySection, $new_page);          //-- build a section using nothing
		$S->load_from_array($section_array, $record);

		for($ct = 0 ; $ct < count($controls) ; $ct ++){                                       //-- add controls to rebuilt section

			$S->controls[$ct]             = new nuControl(null, null);
			$S->controls[$ct]->load_from_array($controls[$ct]);
		
		}
		
		if($sectionArray[$G]['the_page'] != $the_page){	//--start new page
			$pdf->section_top             = 0;
			$the_page                     = $sectionArray[$G]['the_page'];
	    	$pdf->AddPage();
			$pdf->page_no                 = $pdf->page_no + 1;
		}
		$pdf->buildSection($S);

    }
	$pdf->Output('bob.pdf','I');
}



function fresize($pSize){ //-- font size
	$size      = str_replace('px','',$pSize);
	return $size * .75;
}

function fwresize($pSize){ //-- font width
	$size      = str_replace('px','',$pSize);
	return $size * 3.80;
}


function vresize($pSize){
	$size      = str_replace('px','',$pSize);
	return $size * .28;
}


function hresize($pSize){
	$size      = str_replace('px','',$pSize);
	return $size * .27;
}

function lresize($pSize){
	$size      = str_replace('px','',$pSize);
	return $size * .3;
}








?>
