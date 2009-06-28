<?php
/*
** File:           run_report_html_v2.php
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

function run_html_report(){

	include_once('report_object.php');   //--build report object used for making HTML and PDF
	build_report_object();

}


function build_report($report_object){   //-- this function is called from report_object.php

	$page_height                          = $report_object->page_height;
	$sectionArray                         = $GLOBALS['nuSections'];
	$total_pages                          = $sectionArray[count($sectionArray)-1]['the_page'] +1;
    $obj                                  = array();
    $st                                   = '';
	$the_page                             = -1;
	$timestamp                            = date('Y-m-d H:i:s');

	tofile('mid html 1: '.date("H:i:s"));
	for($G = 0 ; $G < count($sectionArray) ; $G++){

		unset($section_array);
		unset($record);
		unset($controls);
		unset($S);

		$nuS = gzuncompress($sectionArray[$G]['the_section']);
		eval($nuS);                                                                               //-- make section_array, record and controls from table record
		$S                                    = new nuSection($emptySection, $new_page);          //-- build a section using nothing
		$S->load_from_array($section_array, $record);

		for($ct = 0 ; $ct < count($controls) ; $ct ++){                                           //-- add controls to rebuilt section
		
			$S->controls[$ct]                                      = new nuControl(null, null);
			$S->controls[$ct]->load_from_array($controls[$ct]);
		
		}


		if(!in_array($S->name, $obj)){

			$style                     = 'position:relative;height:' . $S->height;

			if($S->background_color == '#ebebeb'){
				$S->background_color   = '#FFFFFF';
			}

			$style                    .= ';background-color:' . $S->background_color . ';border-width:0';
			$st                       .= '   .' . $S->name . ' { ' . $style . "}\n";
			$obj[]                     = $S->name;

		}

		for($c = 0 ; $c < count($S->controls) ; $c++){


			if(!in_array($S->controls[$c]->name, $obj)){
				
				$name                    = $S->controls[$c]->name;
				$style                   = 'overflow:hidden;position:absolute;font-size:' . $S->controls[$c]->font_size; 
				$style                   .= ';font-family:'                               . $S->controls[$c]->font_name;
				$style                   .= ';font-weight:'                               . $S->controls[$c]->font_weight;
				$style                   .= ';background-color:'                          . $S->controls[$c]->background_color;
				$style                   .= ';color:'                                     . $S->controls[$c]->color;
				$style                   .= ';text-align:'                                . $S->controls[$c]->text_align;
//				$style                   .= ';top:'                                       . $S->controls[$c]->top;
				$style                   .= ';left:'                                      . $S->controls[$c]->left;
				$style                   .= ';width:'                                     . $S->controls[$c]->width;
				$style                   .= ';border-width:'                              . $S->controls[$c]->border_width;
				$style                   .= ';border-color:'                              . $S->controls[$c]->border_color;
				$style                   .= ';border-style:'                              . $S->controls[$c]->border_style;
				$st                      .= '   .' . $S->controls[$c]->name . ' { ' . $style . "}\n";
				$obj[]                    = $S->controls[$c]->name;

			}
		}
    }


    $st1                                   = "<html>\n<title></title>\n";
    $st1                                  .= "<script type='text/javascript' src='common.js' language='javascript'></script>\n";
    $st1                                  .= "<style type='text/css'>\n\n\n";
    print $st1 . $st . "</style>\n\n\n\n" . $report_object->javascript . "\n\n\n\n\n<body onload='LoadThis()' >\n\n\n\n";;

	
	
	for($G = 0 ; $G < count($sectionArray) ; $G++){

		unset($section_array);
		unset($record);
		unset($controls);
		unset($S);

		$nuS = gzuncompress($sectionArray[$G]['the_section']);
		eval($nuS);                                                                               //-- make section_array, record and controls from table record
		$S                                    = new nuSection($emptySection, $new_page);          //-- build a section using nothing
		$S->load_from_array($section_array, $record);

		for($ct = 0 ; $ct < count($controls) ; $ct ++){                                           //-- add controls to rebuilt section
		
			$S->controls[$ct]                                      = new nuControl(null, null);
			$S->controls[$ct]->load_from_array($controls[$ct]);
		
		}


		if($sectionArray[$G]['the_page'] != $the_page){	//--end and start new page
			if($the_page != -1){                //--if not first loop
				print "</div>\n\n";
				print "<div style='position:relative;page-break-before:always;font-size:1'>.</div>\n\n";
			}
			print "<div style='height:$page_height'>\n";
			$the_page                     = $sectionArray[$G]['the_page'];
		}

		if($S->height != '0' or $S->height !='0px'){//--dont build it if the height is zero

			print "   \n\n   <div class='$S->name' style='height:$S->height;'>\n\n";

			for($c = 0 ; $c < count($S->controls) ; $c++){

				$lineFormat = formatForPDFandHTML($S->controls[$c]->text_string[0]);       //-- get any formatting stuff from the beginning of the string eg. #BOLD#
				$cHeight    = $S->controls[$c]->height + $S->controls[$c]->extra_growth;
				$cName      = $S->controls[$c]->name;
				$cTop       = $S->controls[$c]->top;
				$data       = '';
				$style      = $lineFormat['html'];
				$tags       = '';
				$endtags    = '';

				if($lineFormat['bold'] == '1'){
					$tags     = $tags    . '<B>';
					$endtags  = $endtags . '</B>';
				}
				if($lineFormat['italic'] == '1'){
					$tags     = $tags    . '<I>';
					$endtags  = $endtags . '</I>';
				}
				if($lineFormat['underline'] == '1'){
					$tags     = $tags    . '<U>';
					$endtags  = $endtags . '</U>';
				}
				for($ts     = 0; $ts < count($S->controls[$c]->text_string) ; $ts++){
					if($ts == 0){
						$data   = $data . iif($ts == 0,'','<br />') . $lineFormat['string'];
					}else{
						$data   = $data . iif($ts == 0,'','<br />') . $S->controls[$c]->text_string[$ts];
					}
				}
				$CC             = $S->controls[$c];
				if($cHeight!='0px'){
					$data       = str_replace('#totalNumberOfPages#', $total_pages, $data);
					$data       = str_replace('#thePageNumber#', ($sectionArray[$G]['the_page'] + 1), $data);
					if(strtoupper($data) == '=NOW()' or strtoupper($data) == 'NOW()'){ //-- set a timestamp
						$data   = $timestamp;
					}
					print "           <div class='$cName' style='top:$cTop;height:$cHeight$style'>$tags$data$endtags</div>\n";
				}
			}
        }
        print "\n   </div>\n";
    }
    print "</div>\n\n</html>\n\n";
}

?>
