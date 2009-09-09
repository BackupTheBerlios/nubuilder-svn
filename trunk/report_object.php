<?php
/*
** File:           report_object.php
** Author:         nuSoftware
** Created:        2007/04/26
** Last modified:  2009/07/15
**
** Copyright 2004, 2005, 2006, 2007, 2008, 2009 nuSoftware
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

function build_report_object(){

	$GLOBALS['time'] = array();
	$ses                           = $_GET['ses'];
	$form_ses                      = $_GET['form_ses'];
	$report                        = $_GET['r'];
	$dir                           = $_GET['dir'];
	$get_array                     = array();

        $emailer                        = $_GET['emailer'];	

	while(list($key, $value)       = each($_GET)){
		$get_array["#$key#"]       = $value;
	}

	include_once("../$dir/database.php");
	include_once('common.php');

	//--------security check-------------------------------
	if ($emailer != '1') {
		if(activityPasswordNeeded($report)){
			$session                   = nuSession($ses, false);
			if($session->foundOK == ''){
				print 'you have been logged out..';
				return;
			}
		}
	}
	//---------------------------------------------------

	$id                            = uniqid('1');
	$thedate                       = date('Y-m-d H:i:s');
	$dq                            = '"';
	$setup                         = nuSetup();
	$T                             = nuRunQuery("SELECT * FROM zzsys_activity WHERE sat_all_code = '$report'");
	$activity                      = db_fetch_object($T);
	//----------allow for custom code----------------------------------------------
//--already done now..	eval($activity->sat_report_display_code);   //---(Reporting Class)

	$displayClass                 = new Reporting();
	$REPORT                       = new REPORT($displayClass, $dir, $ses);

	if($activity->zzsys_activity_id ==''){
		print 'No Such Report...';
		return;
	}
	$viewer				           = $session->sss_zzsys_user_id;
	$s                             =      "INSERT INTO zzsys_report_log (zzsys_report_log_id, ";
	$s                             .= "srl_zzsys_activity_id, srl_date ,srl_viewer) ";
	$s                             .= "VALUES ('$id', '$report', '$thedate', '$viewer')";
	nuRunQuery($s);

	$s                             =      "SELECT count(*), MAX(sva_expiry_date) FROM zzsys_variable ";
	$s                             .= "WHERE sva_id = '$form_ses' ";
	$s                             .= "GROUP BY sva_expiry_date";
	$t1                            = nuRunQuery($s);
	$r1                            = db_fetch_row($t1);
	$numberOfVariables             = $r1[0];
	$expiryDate                    = $r1[1];



	 if($numberOfVariables     == 0){  //---must have at least 1 variable
		print 'Report has Expired...';
		return;
	 }

	$s                             =      "DELETE FROM zzsys_variable ";
	$s                             .= "WHERE sva_id = '$form_ses' ";
	$s                             .= "AND sva_name = 'ReportTitle'";
	nuRunQuery($s);
	setnuVariable($form_ses, $expiryDate, 'ReportTitle', $activity->sat_all_description);

	$TT                            = TT();          //--Temp table name
	//----------create an array of hash variables that can be used in any "hashString"
	$sesVariables                  = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$sesVariables['#dataTable#']   = $TT;
	$sesVariables['#TT#']          = $TT;
	$GLOBALS['TT']                 = $TT; 
	$dataTable                     = $TT;
	$sysVariables                  = sysVariablesToHashArray($form_ses);                            //--values in sysVariables from the calling lookup page
	$arrayOfHashVariables          = joinHashArrays($sysVariables, $sesVariables);                  //--join the arrays together
	$formValue                     = array();

	while(list($key, $value)   = each($sesVariables)){
		$formValue[substr($key,1,-1)] = $value;
	}

	//-------------------------------build $TT with PHP----------------------------------------
	$v                             = getSelectionFormVariables($form_ses);
	$hashV                         = arrayToHashArray($v);
	$arrayOfHashVariables          = joinHashArrays($arrayOfHashVariables, $hashV);                  //--join the arrays together
	$REPORT->tablesUsed            = getSelectionFormTempTableNames($form_ses, $v); //--temp tables to delete when finished
	$formValue                     = $v;
	$nuHashVariables               = $arrayOfHashVariables;   //--added by sc 23-07-2009
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $activity->sat_report_data_code));
	$REPORT->no_data               = addVariablesToTT($TT, $v);

	nuRunQuery("ALTER TABLE `$TT` ADD `nu__id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");

	$REPORT->nuloopThroughRecords($TT);
}


//=========================================================================================
//===============OTHER FUNCTIONS===========================================================
//=========================================================================================
function hex2bin($h){

	if (!is_string($h)) return null;
	$r='';
	for ($a=0; $a<strlen($h); $a+=2) { $r.=chr(hexdec($h{$a}.$h{($a+1)})); }
	return $r;

}

function buildClassCode($pSection, $pPageNo = -1){

	$swap_comma   = "'.\"'\".'";

	$s            = "\n";
	$s            .= '   $section_array["id"]'."        = '$pSection->id';\n";
	$s            .= '   $section_array["page_id"]'."        = '$pSection->page_id';\n";
	$s            .= '   $section_array["name"]'."        = '$pSection->name';\n";
	$s            .= '   $section_array["height"]'."        = '$pSection->height';\n";
	$s            .= '   $section_array["original_height"]'."        = '$pSection->original_height';\n";
	$s            .= '   $section_array["extra_growth"]'."        = '$pSection->extra_length';\n";
	$s            .= '   $section_array["background_color"]'."        = '$pSection->background_color';\n";
	$s            .= '   $section_array["printed_to"]'."        = '$pSection->printed_to';\n";
	$s            .= '   $section_array["record"]'."        = array();\n";
	$s            .= '   $section_array["controls"]'."        = array();\n";
	$s            .= "\n\n";
    
    while(list($key, $value)   = each($pSection->record)){
		list($key, $value)     = each($pSection->record);
		$V        = bin2hex($value);
		$s        .= '   $record['."'".$key."'".']   '."='$V';\n";
	}

	for($i = 0 ; $i < count($pSection->controls) ; $i++){
		$ctrl     = $pSection->controls[$i];
		$s        .= "\n\n";
		$s        .= '$controls['.$i.']["id"]'."       = '$ctrl->id';\n";
		$s        .= '$controls['.$i.']["section_id"]'."       = '$ctrl->section_id';\n";
		$s        .= '$controls['.$i.']["top"]'."       = $ctrl->top;\n";
		$s        .= '$controls['.$i.']["original_top"]'."       = $ctrl->top;\n";
		$s        .= '$controls['.$i.']["bottom_gap"]'."       = $ctrl->bottom_gap;\n";
		$s        .= '$controls['.$i.']["name"]'."       = '$ctrl->name';\n";
		$s        .= '$controls['.$i.']["type"]'."       = '$ctrl->type';\n";
		$s        .= '$controls['.$i.']["source"]'."       = '$ctrl->source';\n";
		$s        .= '$controls['.$i.']["width"]'."       = $ctrl->width;\n";
		$s        .= '$controls['.$i.']["height"]'."       = $ctrl->height;\n";
		$s        .= '$controls['.$i.']["original_height"]'."       = $ctrl->original_height;\n";
		$s        .= '$controls['.$i.']["left"]'."       = $ctrl->left;\n";
		$s        .= '$controls['.$i.']["extra_growth"]'."       = $ctrl->extra_growth;\n";
		$s        .= '$controls['.$i.']["extra_height"]'."       = $ctrl->extra_height;\n";
		$s        .= '$controls['.$i.']["last_used"]'."       = $ctrl->last_used;\n";
		$s        .= '$controls['.$i.']["graph"]'."       = '$ctrl->graph';\n";
		$s        .= '$controls['.$i.']["font_name"]'."       = '$ctrl->font_name';\n";
		$s        .= '$controls['.$i.']["font_size"]'."       = '$ctrl->font_size';\n";
		$s        .= '$controls['.$i.']["font_weight"]'."       = '$ctrl->font_weight';\n";
		$s        .= '$controls['.$i.']["text_align"]'."       = '$ctrl->text_align';\n";
		$s        .= '$controls['.$i.']["background_color"]'."       = '$ctrl->background_color';\n";
		$s        .= '$controls['.$i.']["color"]'."       = '$ctrl->color';\n";
		$s        .= '$controls['.$i.']["border_width"]'."       = '$ctrl->border_width';\n";
		$s        .= '$controls['.$i.']["border_color"]'."       = '$ctrl->border_color';\n";
		$s        .= '$controls['.$i.']["border_style"]'."       = '$ctrl->border_style';\n";
		$s        .= '$controls['.$i.']["graph"]'."       = '$ctrl->graph';\n";
		$s        .= '$controls['.$i.']["bottom_gap"]'."       = '$ctrl->bottom_gap';\n";
		$s        .= '$controls['.$i.']["format"]'."       = '$ctrl->format';\n";
		$s        .= '$controls['.$i.']["report"]'."       = '$ctrl->report';\n";
		$s        .= '$controls['.$i.']["parameters"]'."       = '$ctrl->parameters';\n";
		$s        .= '$controls['.$i.']["can_grow"]'."       = '$ctrl->can_grow';\n";
		$s        .= '$controls['.$i.']["completed"]'."       = false;\n";
		$s        .= "\n";

		for($t = 0 ; $t < count($pSection->controls[$i]->text_string) ; $t++){
			$T    = str_replace("'", $swap_comma ,$pSection->controls[$i]->text_string[$t]);
			$s    .= '$controls['.$i.']["text_string"]['.$t.']  '."='$T';\n";
		}
		$s        .= "\n";
		
		if($pPageNo == -1){
			for($a = 0 ; $a < count($pSection->controls[$i]->above) ; $a++){
				$AA   = $pSection->controls[$i]->above[$a];
				$s    .= '$controls['.$i.']["above"]['.$a.']        '."='$AA';\n";
			}
		}
		
	}

	$s        .= "\n\n\n" . '    while(list($key, $value)   = each($record)){' . "\n";
	$s        .=            '       $record[$key] = hex2bin($value);' . "\n";
	$s        .=            "    }\n";
	
	$compressed                                         = gzcompress($s, 9);
	$count                                              = count($GLOBALS['nuSections']);
	$GLOBALS['nuSections'][$count]['the_section']       = $compressed;
	$GLOBALS['nuSections'][$count]['the_page']          = $pPageNo;
	
}





function formatForPDFandHTML($pString){

	$style                           = '';
	$found                           = true;
	$color                           = array();
	$bgcolor                         = array();
	$html                            = '';
	$bold                            = '';
	$italic                          = '';
	$underline                       = '';
	
	
	while($found){
		$found                       = false;

		if(substr($pString, 0, 6)    == '#BOLD#'){

			$bold                    = '1';
			$found                   = true;
			$pString                 = substr($pString, 6);
			if(strpos($style,'B')    === false){
				$style              .= 'B';
			}
			
		}

		if(substr($pString, 0, 8)    == '#ITALIC#'){

			$italic                  = '1';
			$found                   = true;
			$pString                 = substr($pString, 8);
			if(strpos($style,'I')    === false){
				$style              .= 'I';
			}
			
		}

		if(substr($pString, 0, 11)   == '#UNDERLINE#'){

			$underline               = '1';
			$found                   = true;
			$pString                 = substr($pString, 11);
			if(strpos($style,'U')    === false){
				$style              .= 'U';
			}
			
		}

		if(substr($pString, 0, 7)    == '#COLOR#'){

			$html                   .= ';color:#' . substr($pString, 7, 6);
			$found                   = true;
			$color                   = rgbcolor(substr($pString, 7, 6), array());
			$pString                 = substr($pString, 13);
			
		}

		if(substr($pString, 0, 9)    == '#BGCOLOR#'){

			$html                   .= ';background-color:#' . substr($pString, 9, 6);
			$found                   = true;
			$bgcolor                 = rgbcolor(substr($pString, 9, 6), array());
			$pString                 = substr($pString, 15);
			
		}
		
		$return                      = array();
		$return['string']            = $pString;
		$return['style']             = $style;
		$return['color']             = $color;
		$return['bgcolor']           = $bgcolor;
		$return['bold']              = $bold;
		$return['italic']            = $italic;
		$return['underline']         = $underline;
		$return['html']              = $html;

		return $return;
		
	}

}




	function rgbcolor($pColor, $html){

		if($html[strtoupper($pColor)] == ''){
			$nColor     = str_replace('#','',$pColor);
			$color['r'] = hexdec(substr($nColor, 0, 2));
			$color['g'] = hexdec(substr($nColor, 2, 2));
			$color['b'] = hexdec(substr($nColor, 4, 2)); 
		}else{
			if($pColor == ''){
				$nColor     = str_replace('#','',$pColor);
				$color['r'] = 0;
				$color['g'] = 0;
				$color['b'] = 0; 
			}else{
				$nColor     = $html[strtoupper($pColor)];
				$color['r'] = hexdec(substr($nColor, 0, 2));
				$color['g'] = hexdec(substr($nColor, 2, 2));
				$color['b'] = hexdec(substr($nColor, 4, 2)); 
			}
		}
		return $color;
	}


	function html_colors(){

		$html           = array();
		$html['ALICEBLUE'] = 'F0F8FF';
		$html['ANTIQUEWHITE'] = 'FAEBD7';
		$html['AQUA'] = '00FFFF';
		$html['AQUAMARINE'] = '7FFFD4';
		$html['AZURE'] = 'F0FFFF';
		$html['BEIGE'] = 'F5F5DC';
		$html['BISQUE'] = 'FFE4C4';
		$html['BLACK'] = '000000';
		$html['BLANCHEDALMOND'] = 'FFEBCD';
		$html['BLUE'] = '0000FF';
		$html['BLUEVIOLET'] = '8A2BE2';
		$html['BROWN'] = 'A52A2A';
		$html['BURLYWOOD'] = 'DEB887';
		$html['CADETBLUE'] = '5F9EA0';
		$html['CHARTREUSE'] = '7FFF00';
		$html['CHOCOLATE'] = 'D2691E';
		$html['CORAL'] = 'FF7F50';
		$html['CORNFLOWERBLUE'] = '6495ED';
		$html['CORNSILK'] = 'FFF8DC';
		$html['CRIMSON'] = 'DC143C';
		$html['CYAN'] = '00FFFF';
		$html['DARKBLUE'] = '00008B';
		$html['DARKCYAN'] = '008B8B';
		$html['DARKGOLDENROD'] = 'B8860B';
		$html['DARKGRAY'] = 'A9A9A9';
		$html['DARKGREEN'] = '006400';
		$html['DARKKHAKI'] = 'BDB76B';
		$html['DARKMAGENTA'] = '8B008B';
		$html['DARKOLIVEGREEN'] = '556B2F';
		$html['DARKORANGE'] = 'FF8C00';
		$html['DARKORCHID'] = '9932CC';
		$html['DARKRED'] = '8B0000';
		$html['DARKSALMON'] = 'E9967A';
		$html['DARKSEAGREEN'] = '8FBC8F';
		$html['DARKSLATEBLUE'] = '483D8B';
		$html['DARKSLATEGRAY'] = '2F4F4F';
		$html['DARKTURQUOISE'] = '00CED1';
		$html['DARKVIOLET'] = '9400D3';
		$html['DEEPPINK'] = 'FF1493';
		$html['DEEPSKYBLUE'] = '00BFFF';
		$html['DIMGRAY'] = '696969';
		$html['DODGERBLUE'] = '1E90FF';
		$html['FIREBRICK'] = 'B22222';
		$html['FLORALWHITE'] = 'FFFAF0';
		$html['FORESTGREEN'] = '228B22';
		$html['FUCHSIA'] = 'FF00FF';
		$html['GAINSBORO'] = 'DCDCDC';
		$html['GHOSTWHITE'] = 'F8F8FF';
		$html['GOLD'] = 'FFD700';
		$html['GOLDENROD'] = 'DAA520';
		$html['GRAY'] = '808080';
		$html['GREEN'] = '008000';
		$html['GREENYELLOW'] = 'ADFF2F';
		$html['HONEYDEW'] = 'F0FFF0';
		$html['HOTPINK'] = 'FF69B4';
		$html['INDIANRED '] = 'CD5C5C';
		$html['INDIGO '] = '4B0082';
		$html['IVORY'] = 'FFFFF0';
		$html['KHAKI'] = 'F0E68C';
		$html['LAVENDER'] = 'E6E6FA';
		$html['LAVENDERBLUSH'] = 'FFF0F5';
		$html['LAWNGREEN'] = '7CFC00';
		$html['LEMONCHIFFON'] = 'FFFACD';
		$html['LIGHTBLUE'] = 'ADD8E6';
		$html['LIGHTCORAL'] = 'F08080';
		$html['LIGHTCYAN'] = 'E0FFFF';
		$html['LIGHTGOLDENRODYELLOW'] = 'FAFAD2';
		$html['LIGHTGREY'] = 'D3D3D3';
		$html['LIGHTGREEN'] = '90EE90';
		$html['LIGHTPINK'] = 'FFB6C1';
		$html['LIGHTSALMON'] = 'FFA07A';
		$html['LIGHTSEAGREEN'] = '20B2AA';
		$html['LIGHTSKYBLUE'] = '87CEFA';
		$html['LIGHTSLATEGRAY'] = '778899';
		$html['LIGHTSTEELBLUE'] = 'B0C4DE';
		$html['LIGHTYELLOW'] = 'FFFFE0';
		$html['LIME'] = '00FF00';
		$html['LIMEGREEN'] = '32CD32';
		$html['LINEN'] = 'FAF0E6';
		$html['MAGENTA'] = 'FF00FF';
		$html['MAROON'] = '800000';
		$html['MEDIUMAQUAMARINE'] = '66CDAA';
		$html['MEDIUMBLUE'] = '0000CD';
		$html['MEDIUMORCHID'] = 'BA55D3';
		$html['MEDIUMPURPLE'] = '9370D8';
		$html['MEDIUMSEAGREEN'] = '3CB371';
		$html['MEDIUMSLATEBLUE'] = '7B68EE';
		$html['MEDIUMSPRINGGREEN'] = '00FA9A';
		$html['MEDIUMTURQUOISE'] = '48D1CC';
		$html['MEDIUMVIOLETRED'] = 'C71585';
		$html['MIDNIGHTBLUE'] = '191970';
		$html['MINTCREAM'] = 'F5FFFA';
		$html['MISTYROSE'] = 'FFE4E1';
		$html['MOCCASIN'] = 'FFE4B5';
		$html['NAVAJOWHITE'] = 'FFDEAD';
		$html['NAVY'] = '000080';
		$html['OLDLACE'] = 'FDF5E6';
		$html['OLIVE'] = '808000';
		$html['OLIVEDRAB'] = '6B8E23';
		$html['ORANGE'] = 'FFA500';
		$html['ORANGERED'] = 'FF4500';
		$html['ORCHID'] = 'DA70D6';
		$html['PALEGOLDENROD'] = 'EEE8AA';
		$html['PALEGREEN'] = '98FB98';
		$html['PALETURQUOISE'] = 'AFEEEE';
		$html['PALEVIOLETRED'] = 'D87093';
		$html['PAPAYAWHIP'] = 'FFEFD5';
		$html['PEACHPUFF'] = 'FFDAB9';
		$html['PERU'] = 'CD853F';
		$html['PINK'] = 'FFC0CB';
		$html['PLUM'] = 'DDA0DD';
		$html['POWDERBLUE'] = 'B0E0E6';
		$html['PURPLE'] = '800080';
		$html['RED'] = 'FF0000';
		$html['ROSYBROWN'] = 'BC8F8F';
		$html['ROYALBLUE'] = '4169E1';
		$html['SADDLEBROWN'] = '8B4513';
		$html['SALMON'] = 'FA8072';
		$html['SANDYBROWN'] = 'F4A460';
		$html['SEAGREEN'] = '2E8B57';
		$html['SEASHELL'] = 'FFF5EE';
		$html['SIENNA'] = 'A0522D';
		$html['SILVER'] = 'C0C0C0';
		$html['SKYBLUE'] = '87CEEB';
		$html['SLATEBLUE'] = '6A5ACD';
		$html['SLATEGRAY'] = '708090';
		$html['SNOW'] = 'FFFAFA';
		$html['SPRINGGREEN'] = '00FF7F';
		$html['STEELBLUE'] = '4682B4';
		$html['TAN'] = 'D2B48C';
		$html['TEAL'] = '008080';
		$html['THISTLE'] = 'D8BFD8';
		$html['TOMATO'] = 'FF6347';
		$html['TURQUOISE'] = '40E0D0';
		$html['VIOLET'] = 'EE82EE';
		$html['WHEAT'] = 'F5DEB3';
		$html['WHITE'] = 'FFFFFF';
		$html['WHITESMOKE'] = 'F5F5F5';
		$html['YELLOW'] = 'FFFF00';
		$html['YELLOWGREEN'] = '9ACD32';
		return $html;
	}


function addVariablesToTT($TT, $v){//--join variables to the temp table and return true if the temp table had no data
    $t1                        = nuRunQuery("SELECT * FrOM $TT");
    $fieldArray                = tableFieldNamesToArray($t1);
    $s                         = "SELECT NOW() as timestamp ";
    while(list($key, $value)   = each($v)){
        if($key!='' and !in_array($key,$fieldArray) and $key!=''){  //--stop duplicate field names
            $value             = str_replace('\"', '"', $value);
        	$s                 .= ' , "' . $value . '" AS ' . $key . ' ';
        }
    }
    $t                         = nuRunQuery("SELECT COUNT(*) FROM $TT");
    $r                         = db_fetch_row($t);
    if($r[0] == 0){
    	nuRunQuery("DROp TABLE $TT");
    	nuRunQuery("CREATE TABLE $TT $s");
    	$noData                = true;
    }else{
    	nuRunQuery("CREATE TABLE a$TT $s, $TT.* FROM $TT");
    	nuRunQuery("DRoP TABLE $TT");
    	nuRunQuery("CREATE TABLE $TT SELECT * FROM a$TT");
    	nuRunQuery("DrOP TABLE a$TT");
    	$noData                = false;
    }
    return $noData;

}

function pixels($pSize){ //--remove the 'px' from a html size

    $pSize                     = str_replace('px', '', $pSize);
    if($pSize == ''){
        $pSize                 = '0';
    }
    return $pSize;

}


function formatControlValue($pControl, $pRecord, $pReport, $pGroup = 0){//---format a control's value 

    $s                 = array();
    if($pControl->type == 'PageBreak'){
        return $s;
    }
    if($pControl->type == 'Graph'){
        $s[]           = buildGraphImage($pControl, $pRecord);
        return $s;
    }
    if($pControl->type == 'Label'){
        $s[]           = $pControl->source;
        return $s;
	}
    if($pControl->type == 'Field'){
		if($pControl->can_grow){
			$text      = new nuText($pControl, $pRecord[$pControl->source]);
			$s         = $text->lines;
        }else{
			$s[]       = formatFields($pControl, $pRecord, $pGroup, $pReport);
        }
		
        return $s;
	}
}

function buildGraphImage($pControl, $pRecord){

	$thesource          = $pControl->source;
	$thename            = $pControl->graph;
	$thedir             = $_GET['dir'];
	$addSession         = '&ses='. $_GET['ses'];

	if($pControl->format == 'image'){

		$t              = nuRunQuery("SELECT * FROM zzsys_image WHERE sim_code = '$pControl->graph'");
		$r              = db_fetch_object($t);
		return "<img src='" . getPHPurl() . "formimage.php?dir=$thedir&iid=$r->zzsys_image_id' width='$pControl->width' height='$pControl->height'/>";
		
	}else{
	
		foreach ($pRecord as $key => $value) {
			//-----replace any strings, with hashes around them, in the querystring that match
			//-----fieldnames in the table
			//-----with values from the table
			//-----e.g. id=#ID# could become id=007

			$thesource      = str_replace("#$key#", $value, $thesource);
		}
		reset($pRecord);
		$t                  = nuRunQuery("SELECT * FROM zzsys_activity WHERE sat_all_code = '" . $_GET['r'] . "'");
		$r                  = db_fetch_object($t);
		return "<img src='graph_report.php?dir=$thedir$addSession&activityID=$r->zzsys_activity_id&graph_name=$thename&$thesource' width='$pControl->width' height='$pControl->height' />";
	}
	
}



function formatFields($pControl, $pRecord, $pGroup, $pReport){
	
	$nuFunction      = nuReportFunction($pControl->source);
	if($nuFunction   == 0){
		return formatTextValue($pRecord[$pControl->source], $pControl->format);
	}else{
		return calcFunction($nuFunction, $pControl, $pRecord, $pGroup, $pReport);
	}
	
}


function calcFunction($nuFunction, $pControl, $pRecord, $pGroup, $pReport){
	

	$FieldList              = array();
		$strip_out                          = array('=','SUM','PERCENT','sum','precent','Sum','Percent','(',')','[',']',' ');  //--strings to strip out of source

	if($nuFunction == 1 or $nuFunction == 2){          //--sum or percent function
		if($pReport->no_data){return 0;}
		$field_list         = str_replace($strip_out, '', $pControl->source);  
		$fields             = explode (',', $field_list);
		if($nuFunction == 1){
			$selectFields   = "SUM(".$fields[0]."_Sum) as answerA ";
		}else{
			$selectFields   = "SUM(".$fields[0]."_Sum) as answerA, SUM(".$fields[1]."_Sum) as answerB ";
		}
		$groupSQL           = $pReport->getGroupBySql($pReport->sectionLevel($pControl->section), $selectFields, $pRecord);
		$t                  = nuRunQuery($groupSQL);
		$r                  = mysql_fetch_row($t);
		if($nuFunction == 1){
			return formatTextValue($r[0], $pControl->format);
		}else{
			if($r[0]==0){
				return formatTextValue(0, $pControl->format);
			}else{
				return formatTextValue($r[0] / $r[1], $pControl->format);
			}
		}
	}
	if($nuFunction == 3 or $nuFunction == 4){          //--date or now function
		$formats           = textFormatsArray();
		$n                 = date("H:i:s");
		$d                 = date($formats[$pControl->format]->phpdate);
		if($nuFunction == 4){  //--now function
			$d = "$d $n";
		}
		return $d;
	}
}



function nuReportFunction($pString){  //--- see if the start of the string is like any of the array items
	
	$functions      = array();        //--built in functions
	$functions[]    = 'SUM(';         //--SUM function                     1
	$functions[]    = 'PERCENT(';     //--PERCENT function              2
	$functions[]    = 'DATE(';        //--DATE function                    3 
	$functions[]    = 'NOW(';         //--NOW function                    4

	for($i = 0 ; $i < count($functions); $i++){
		if(stristr($pString, $functions[$i])!=''){
				return $i + 1;
		}
	}

	return 0;                         //--not a function                     0

}


//=========================================================================================
//===============CLASSES===================================================================
//=========================================================================================


//==create report object===================================================================

class REPORT{

    public $no_data                         = false;
    public $page_height                     = 0;
    public $normal_page_height              = 0;
    public $page_width                      = 0;
    public $printable_height                = 0;
    public $detail_section                  = null;
    public $report_header                   = null;
    public $report_footer                   = null;
    public $page_header                     = null;
    public $page_footer                     = null;
    public $header_sections                 = array();
    public $footer_sections                 = array();
    public $overflow                        = null;
    public $groups                          = array();
    public $group_count                     = 0;
    public $page_total                      = 0;
    public $pages                           = array();
    public $current_page                    = null;
    public $is_first_record                 = true;
    public $current_record                  = array();
    public $last_record                     = array();
    public $jsFunctions                     = array();
    public $printable_height_remaining      = 0;
    public $TT                              = '';
    public $sumTT                           = '';
    public $display_class                   = '';
    public $customDirectory                 = '';
    public $session                         = '';
    public $orientation                     = '';
    public $paper_type                      = '';
	public $header_numbers                  = array();
	public $footer_numbers                  = array();
	public $tablesUsed                      = array();
	
    function __construct($pReport, $pCustomDirectory, $pSession){

		$this->header_numbers               = array(5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31);
		$this->footer_numbers               = array(6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32);
		$this->display_class                = $pReport;

        $this->detail_section               = new REPORT_section($pReport,0);

        $this->report_header                = new REPORT_section($pReport,1);
        $this->report_footer                = new REPORT_section($pReport,2);
        $this->page_header                  = new REPORT_section($pReport,3, false);   //--section cannot grow
        $this->page_footer                  = new REPORT_section($pReport,4, false);   //--section cannot grow

        $this->customDirectory              = $pCustomDirectory;
        $this->session                      = $pSession;

        $this->orientation                  = $pReport->Orientation;
        $this->paper_type                   = $pReport->PaperType;

        $this->page_width                   = pixels($pReport->Width);
		if(pixels($pReport->Height)=='0'){
			$pReport->Height                = 1000;
		}
		$the_page_height                    = pixels($pReport->Height);
		if($the_page_height == ''){  //-- get height from activity record (backwards compatability)

			$heightTable                   = nuRunQuery("SELECT sat_report_page_length FROM zzsys_activity WHERE sat_all_code = '" . $_GET['r']. "'");
			$heightRecord                  = db_fetch_object($heightTable);
			$the_page_height               = pixels($heightRecord->sat_report_page_length);
		}

        $this->page_height                  = $the_page_height;
        $this->normal_page_height           = $the_page_height;
        $this->printable_height             = 10000000;
        $this->reset_remaining_height();

        for($i = 0 ; $i < count($this->header_numbers) ; $i ++){
            $this->header_sections[]        = new REPORT_section($pReport,$this->header_numbers[$i]);
        }
        for($i = 0 ; $i < count($this->footer_numbers) ; $i ++){
            $this->footer_sections[]        = new REPORT_section($pReport,$this->footer_numbers[$i]);
        }

        for($i = 0 ; $i < count($pReport->Groups) ; $i ++){
            $this->groups[]                 = $pReport->Groups[$i];
        }
        $this->group_count                  = count($this->groups);
		$this->sumTT                        = TT();
    }

	public function orderBy($dataTable){

		if($this->no_data){return '';}
		for($i = 0 ; $i < count($this->groups) ; $i++){
			if($i==0){
				$s                          = 'Order By ';
			}else{
				$s                          .= ', ';
			}
			$s                              .= $this->groups[$i]->Field . ' ';
			$s                              .= $this->groups[$i]->SortOrder;
			$index                          =      $this->groups[$i]->Field;
	        nuRunQuery("ALTER TABLE $dataTable ADD INDEX ($index)");
		}
		return $s;

	}

    public function reduce_remaining_height($pReduceBy){                                        //-- reduce remaining height
		$this->printable_height_remaining = $this->printable_height_remaining - $pReduceBy;
    }

    public function get_remaining_height(){                                                     //-- get remaining height
        return $this->printable_height_remaining;
    }

    public function reset_remaining_height(){                                                   //-- reset remaining height
	
		$this->printable_height_remaining  = $this->printable_height;
    }

    public function nuloopThroughRecords($pDataTableName){

		$this->TT                          = $pDataTableName;
		$this->sumTotals();
        $this->current_page                = new nuPage();
		
        //--get first record (for report header)
        $report_order                      = $this->orderBy($pDataTableName);
        $report_data                       = nuRunQuery("SELECT * FROM $pDataTableName $report_order LIMIT 0 , 1");
        $this->current_record              = db_fetch_array($report_data);
		$this->current_record['is_nureport_header'] = '1';  //-- used when building final class
//--report header
        $this->buildDynamicSection($this->report_header, $this->current_record, 0);
        $this->buildDynamicSection($this->page_header, $this->current_record, 0);

        //--get whole temp table
        $report_order                      = $this->orderBy($pDataTableName);
        $report_data                       = nuRunQuery("SELECT * FROm $pDataTableName $report_order");
        //--loop through table
        while($this->current_record        = db_fetch_array($report_data)){
			$this->buildDetailSection();
        }

        //--build all final group footers
        for($i = $this->group_count-1 ; $i >= 0 ; $i --){
            $this->buildDynamicSection($this->footer_sections[$i], $this->last_record, $i);
        }
//--report footer
        $this->buildDynamicSection($this->report_footer, $this->last_record, 0);

        $this->pages[0]                     = $this->current_page;
		nuRunQuery("dROP TABLE $this->TT");
		if(!$this->no_data){
			nuRunQuery("drOP TABLE $this->sumTT");
		}

        $this->page_height                  = $this->normal_page_height;
        $this->printable_height             = $this->page_height - $this->page_header->height - $this->page_footer->height;
        $this->reset_remaining_height();
		$this->printable_height_remaining  = $this->printable_height + $this->page_header->height + $this->page_footer->height;  //-- longer for first page
		buildFinalObject($this);
		$this->deleteTempTables();

    }

	public function buildDetailSection(){

        if($this->is_first_record){ //--print start of report
            $groupBackTo                    = 0;
        }else{ //--check if any group values have changed
            $groupBackTo                    = $this->group_count;
            for($i = 0 ; $i < $this->group_count ; $i ++){
                if($this->current_record[$this->groups[$i]->Field] != $this->last_record[$this->groups[$i]->Field]){
                    $groupBackTo            = $i;
                    break;
                }
            }
        }
        //--print required footers
        for($i = $this->group_count-1 ; $i >= $groupBackTo ; $i --){
            if(!$this->is_first_record){ //--no footers for the first record
                $this->buildDynamicSection($this->footer_sections[$i], $this->last_record, $i);
            }
        }

        //--print required headers
        for($i = $groupBackTo ; $i < $this->group_count ; $i ++){
            $this->buildDynamicSection($this->header_sections[$i], $this->current_record, $i);
        }
        //--now..(finally!) build detail section
        $this->buildDynamicSection($this->detail_section, $this->current_record, 0);

        $count                              = count($this->current_page->sections);
        $this->last_record                  = $this->current_record;
        $this->is_first_record              = false;

	}


	public function sectionLevel($pSectionNumber){
		if (in_array ($pSectionNumber, $this->header_numbers)) {
			for($i = 0 ; $i < count($this->header_numbers) ; $i++){
				if($this->header_numbers[$i]==$pSectionNumber){
					return $i;
				}
			}
		}

		if (in_array ($pSectionNumber, $this->footer_numbers)) {
			for($i = 0 ; $i < count($this->footer_numbers) ; $i++){
				if($this->footer_numbers[$i]==$pSectionNumber){
					return $i;
				}
			}
		}

	}


	public function getGroupBySql($pSectionLevel, $pSelectFields, $pRecord){
	
		$dq  = '"';
		for($i=0;$i<=$pSectionLevel;$i++){
			if($i==0){
				if($this->groups[$i]->Field<>''){
					$whereClause = $this->groups[$i]->Field." = $dq".$pRecord[$this->groups[$i]->Field]."$dq";
				}
			}else{
				if($this->groups[$i]->Field<>''){
					$whereClause = $whereClause.' AND '.$this->groups[$i]->Field." = $dq".$pRecord[$this->groups[$i]->Field]."$dq";
				}
			}
		}
		if(strlen($pSectionLevel)==0){
			return "SELECT $pSelectFields FROM $this->sumTT";
		}else{
			return "SELECT $pSelectFields FROM $this->sumTT WHERE $whereClause";
		}
		
	}

	
	
	
	
	public function sumTotals(){

		if($this->no_data){return;}
		$FieldList                          = array();
		$strip_out                          = array('=','SUM','PERCENT','sum','precent','Sum','Percent','(',')','[',']',' ');  //--strings to strip out of source


//-- make group by list
		$s                                  = $this->groups[0]->Field;
		for($i = 1 ; $i < count($this->groups) ; $i++){
			$s                              .= ','   . $this->groups[$i]->Field;
		}
		$groupByList                        = $s;

//-- set up summed columns
		for($i = 0 ; $i < count($this->display_class->Controls) ; $i++){
			if($this->display_class->Controls[$i]->ControlType=='109' or $this->display_class->Controls[$i]->ControlType=='Field'){

				$nuFunction      = nuReportFunction($this->display_class->Controls[$i]->ControlSource);
				if($nuFunction   == 1 or $nuFunction   == 2 ){  //--SUM=1  ,  PERCENT = 2
					$field_list                 = str_replace($strip_out, '', $this->display_class->Controls[$i]->ControlSource);  
					$fields                     = explode (',', $field_list);
//-- sum first array element regardless of it being SUM or PERCENT				
					if (!in_array ($fields[0], $FieldList)) {
						$s                  .= ", Sum($fields[0]) AS ".$fields[0]."_Sum";
						$FieldList[]        = $fields[0];
					}
//-- sum second array element if PERCENT				
					if($nuFunction == 2){   //--PERCENT
						if (!in_array ($fields[1], $FieldList)) {
							$s                  .= ", Sum($fields[1]) AS ".$fields[1]."_Sum";
							$FieldList[]        = $fields[1];
						}
					}
				}
			}
		}

		if(count($this->groups) > 0){
			$s = "CREATE TABLE $this->sumTT SELECT $s FROM $this->TT GROUP BY $groupByList";
			nuRunQuery($s);
	        $this->addIndexes($groupByList);
		}else{
			$s = "CREATE TABLE $this->sumTT SELECT 1";
			nuRunQuery($s);
		}

		$this->tablesUsed[]                 = "$this->sumTT";
	}

	
	
	public function deleteTempTables(){
	
	    for ($i = 0 ; $i < count($this->tablesUsed) ; $i++) {
            nuRunQuery("DROP TABLE IF EXISTS `" . $this->tablesUsed[$i] .  "`") ;
	    }
	}
	
	
	public function addIndexes($groupByList){
	
	    $t = nuRunQuery("SELECT * FRoM $this->sumTT");
		nuRunQuery("ALTER TABLE `$this->sumTT` ADD PRIMARY KEY ( $groupByList ) ");
		$a = explode(',', $groupByList);
	    for ($i = 0 ; $i < count($a) ; $i++) {
            nuRunQuery("ALTER TABLE $this->sumTT ADD INDEX (`". $a[$i] ."`)") ;
	    }
	}
	

	public function buildDynamicSection($pSection, $pRecord, $pGroup){//--populate controls NOT page headers or footers

		if($pSection->height != 0){ //-- ignore section if height is 0
			$temp_section_controls                             = array();
			for($i = 0 ; $i < count($pSection->controls) ; $i ++){
				$temp_section_controls[$i]                     = clone $pSection->controls[$i];
				$temp_section_controls[$i]->text_string        = formatControlValue($temp_section_controls[$i], $pRecord, $this, $pGroup);
			}

			$newSection                                        = new nuSection($pSection, $this->current_page);
			$newSection->record                                = $pRecord;
			$this->buildControls($newSection, $temp_section_controls);
			buildClassCode($newSection);
		}

	}


    public function buildControls($pNewSection, $pControls){

        for($i = 0 ; $i < count($pControls) ; $i++){
            $control_height                                = pixels($pControls[$i]->height);
            $pNewSection->controls[]                       = new nuControl($pControls[$i], $pNewSection);
            $count                                         = count($pNewSection->controls);
            $pNewSection->controls[$count-1]->text_string  = $pControls[$i]->text_string;
            $lines                                         = count($pControls[$i]->text_string);
			$pNewSection->controls[$count-1]->extra_growth = (iif($lines == 0,1,$lines) * $control_height) - $control_height;
            $pNewSection->controls[$count-1]->extra_height = (iif($lines == 0,1,$lines) * $control_height) - $control_height;
            if($pNewSection->extra_growth                  < $pNewSection->controls[$count-1]->extra_growth){
                $pNewSection->extra_growth                 = $pNewSection->controls[$count-1]->extra_growth;
            }
        }
		$this->reshuffleControls($pNewSection->controls);
		//-- resize section height
		$max                                               = $pNewSection->height;

        for($i = 0 ; $i < count($pNewSection->controls) ; $i ++){
			$ctrl                                          = $pNewSection->controls[$i];
			$max                                           = max($max, $ctrl->top + $ctrl->height + $ctrl->bottom_gap + $ctrl->extra_growth);
		}
		$pNewSection->height                               = $max;
	}

	
      
    public function reshuffleControls($pControls){
         
		for($i = 0 ; $i < count($pControls) ; $i ++){
			$pControls[$i]->move_down      = $this->move_controls_down($pControls, $i);
			$pControls[$i]->top            = $pControls[$i]->top + $pControls[$i]->move_down;
		}
	}

        function move_controls_down($pControls, $box_number){ //-- calculate if there are boxes partially above this box
            
            for($i = 0 ; $i < $box_number; $i ++){
                if($this->is_above($pControls[$box_number], $pControls[$i])){
                    $pControls[$box_number]->above[]      = $i;
                    for($I = 0 ; $I < count($pControls[$box_number]->above) ; $I ++){
                        $pControls[$I]->above[]  = $pControls[$box_number]->above[$I];
                    }

					$biggest_height         = 0;
                    for($I = 0 ; $I < count($pControls[$box_number]->above) ; $I ++){
						$above_id          = $pControls[$box_number]->above[$I];
						$this_height       = $pControls[$above_id]->extra_height + $pControls[$above_id]->move_down;
						if($biggest_height <= $this_height){
							$biggest_height = $this_height;
							$previous_box  = $pControls[$box_number]->above[$I];
						}
					}
                }
            }
            $go_down        = $pControls[$previous_box]->extra_height + $pControls[$previous_box]->move_down;
            return $go_down;
        }        

        function is_above($bottom, $top){ //-- calculate if top is partially over bottom
            
            $bl             = $bottom->left;
            $br             = $bottom->left + $bottom->width;
            $tl             = $top->left;
            $tr             = $top->left + $top->width;
            
            if($br >= $tr and $br <= $tl){return true;} 
            if($bl >= $tr and $bl <= $tl){return true;} 
            if($bl <= $tr and $br >= $tl){return true;} 
            
            return false;
        }        



    public function displayJavaScript(){

        $s =  "\n\n\n";
        $s .= "<script type='text/javascript'>\n";
        $s .= "/* <![CDATA[ */\n";
        $s .= "\n";
        $s .= "function LoadThis(){//---load form\n";
        $s .= "   if(window.nuLoadThis){;\n";
        $s .= "      nuLoadThis();\n";
        $s .= "   }\n";
        $s .= "}\n";
        $s .= "\n";

        $s .= "self.setInterval('checknuC()', 1000); \n\n";

        $s .= "function checknuC(){ \n";
        $s .= "   if(nuReadCookie('nuC') == null){ \n";
        $s .= "      pop = window.open('', '_parent');\n";
        $s .= "      pop.close();\n";
        $s .= "   }\n";
        $s .= "}\n";

        $s   .= "function customDirectory(){  \n";
        $s   .= "   return '$this->customDirectory'; \n";
        $s   .= "} \n";

        $s   .= "function session_id(){ //-- id that remains the same until logout \n";
        $s   .= "   return '$this->session'; \n";
        $s   .= "} \n";

        for($i=0;$i<count($this->jsFunctions);$i++){
            $s .= $this->jsFunctions[$i];
            $s .= "\n\n";
        }
        $s .= "\n\n";
        $s .= "/* ]]> */ \n";
        $s .= "</script>\n\n\n";
        return $s;

    }


	
	
}

//==create report section ===================================================================

class REPORT_section{

      public $name                   = '';
      public $height                 = 0;
      public $original_height        = 0;
      public $extra_growth           = 0;
      public $background_color       = '';
      public $controls               = array();

      function __construct($pReport, $pSection, $pCanGrow = true){

        $this->name                                  = $pReport->Sections[$pSection]->Name;
        $this->height                                = pixels($pReport->Sections[$pSection]->Height);
        $this->original_height                       = pixels($pReport->Sections[$pSection]->Height);
        $this->background_color                      = $pReport->Sections[$pSection]->BackColor;
		$N                                           = $this->name;
        $GLOBALS['nuReport'][$N]['height']           = $pReport->Sections[$pSection]->Name;
        $GLOBALS['nuReport'][$N]['original_height']  = pixels($pReport->Sections[$pSection]->Height);
        $GLOBALS['nuReport'][$N]['extra_growth']     = pixels($pReport->Sections[$pSection]->Height);
        $GLOBALS['nuReport'][$N]['background_color'] = $pReport->Sections[$pSection]->BackColor;

        $this->buildControls($pReport, $pSection);

      }

      public function buildControls($pReport, $pSection){

          for($i=0 ; $i < count($pReport->Controls) ; $i++){
              if($pReport->Controls[$i]->Section == $pSection){
                  $this->controls[]   = new REPORT_control($pReport->Controls[$i]);
              }
          }
		  sort($this->controls);
      }
}


//==create report control===================================================================

class REPORT_control{

    public $top                  = 0;
    public $name                 = '';
    public $type                 = '';
    public $source               = '';
    public $adjusted_top         = 0;
    public $width                = 0;
    public $height               = 0;
    public $left                 = 0;
    public $graph                = '';
    public $font_name            = '';
    public $font_size            = '';
    public $border_width         = 0;
    public $border_color         = '';
    public $border_style         = '';
    public $background_color     = '';
    public $color                = '';
    public $text_align           = '';
    public $format               = '';
    public $report               = '';
    public $parameters           = '';
    public $can_grow             = false;
    public $last_used            = -1;       //---last 'text_string' element printed
    public $text_string          = array();
    public $font_array           = array();
    public $extra_growth         = 0;
    public $section              = 0;

    function __construct($pControl){


        $this->name              = $pControl->Name;
        $this->type              = $pControl->ControlType;
        $this->source            = $pControl->ControlSource;
        $this->top               = pixels($pControl->Top);
        $this->width             = pixels($pControl->Width);
        $this->height            = pixels($pControl->Height);
        $this->left              = pixels($pControl->Left);
        $this->font_name         = $pControl->FontName;
        $this->font_size         = $pControl->FontSize;
        $this->font_weight       = $pControl->FontWeight;
        $this->text_align        = $pControl->TextAlign;
        $this->background_color  = $pControl->BackColor;
        $this->color             = $pControl->ForeColor;
        $this->border_width      = $pControl->BorderWidth;
        $this->border_color      = $pControl->BorderColor;
        $this->border_style      = 'solid';
        $this->graph             = $pControl->Graph;
        $this->format            = $pControl->Format;
        $this->report            = $pControl->Report;
        $this->parameters        = $pControl->Parameters;
        $this->can_grow          = $pControl->CanGrow == 'True';
        $this->section           = $pControl->Section;

    }

}



//==create final objects ready for html or pdf===================================================================

class nuReport{
    public $pages                    = array();
    public $page_height              = 0;
    public $page_width               = 0;
    public $customDirectory          = '';
    public $session                  = '';
    public $paper_type               = '';
    public $orientation              = '';
    public $javascript               = '';
    public $timestamp                = '';

    function __construct($pOneLongPage, $pJS){

        $this->customDirectory       = $pOneLongPage->customDirectory;
        $this->session               = $pOneLongPage->session;
        $this->javascript            = $pJS;
        $this->paper_type            = $pOneLongPage->paper_type;
        $this->orientation           = $pOneLongPage->orientation;
		$this->timestamp             = date('Y-m-d H:i:s');
    }

	
}



class nuPage{

    public $id                       = '';
    public $page_number              = 0;
    public $sections                 = array();

    function __construct(){
		$this->id                    = uniqid('1');
    }
}


class nuSection{

    public $id                       = '';
    public $page_id                  = '';
    public $name                     = '';
    public $height                   = 0;
    public $original_height          = 0;
    public $extra_growth             = 0;
    public $background_color         = '';
    public $record                   = array();
    public $printed_to               = 0; //--section rebuilt to here
    public $controls                 = array();

    function __construct($pSection, $pPage, $pGroupNo = -1){

		$this->page_id                 = $pPage->id;
		$this->name                    = $pSection->name;
        $this->height                  = $pSection->height;
        $this->original_height         = $pSection->height;
        $this->background_color        = $pSection->background_color;
		$this->id                      = uniqid('1');
		$this->record                  = $pSection->record;
    }


	public function load_from_array($pArray, $pRecord){
	
		$this->name                     = $pArray["name"];
		$this->height                   = $pArray["height"];
		$this->original_height          = $pArray["original_height"];
		$this->extra_growth             = $pArray["extra_growth"];
		$this->background_color         = $pArray["background_color"];
		$this->record                   = $pRecord;
		$this->printed_to               = 0; //--section rebuilt to here
		$this->controls                 = array();

	}

    public function section_height(){

		$completed                 = $this->finished('section height');
//===============================================================sc changed 25-3-9
//		$new_height                = 0;
		$new_height                = max($this->original_height, $this->height);
//===============================================================
		for($i = 0 ; $i < count($this->controls) ; $i  ++){

			$C                     = $this->controls[$i];
			if($completed){
				$control_total     = $C->top + $C->height + $C->extra_growth + $C->bottom_gap;   //-- if completed then add bottom_gap	
			}else{
				$control_total     = $C->top + $C->height + $C->extra_growth;
			}
			$new_height            = max($new_height, $control_total);
		}
		return $new_height;
    }

    public function finished($from = ''){
		for($i = 0 ; $i < count($this->controls) ; $i  ++){
			if(!$this->controls[$i]->completed($this->name)){
				return false;
			}
		}
		return true;
    }

    public function add_record($pRecord){
		$this->record == $pRecord;
    }


    public function push_down_from(){

		$not_finished            = 0;

		for($i = 0 ; $i < count($this->controls) ; $i  ++){

			$used                = $this->controls[$i]->last_used;
			$lines               = count($this->controls[$i]->text_string);
			$controls            = count($this->controls);

			if($used + 1 < $lines and $used != -1){
				if($i + 1 != count($this->controls)){
					return $i;
				}
			}
		}

		return count($this->controls);
    }

}


class nuControl{

    public $id                         = '';
    public $section_id                 = '';
    public $name                       = '';
    public $type                       = '';
    public $source                     = '';
    public $top                        = 0;
    public $original_top               = 0;
    public $bottom_gap                 = 0;
    public $width                      = 0;
    public $height                     = 0;
    public $original_height            = 0;
    public $left                       = 0;
    public $extra_growth               = 0;
    public $extra_height               = 0;
    public $last_used                  = -1;
    public $graph                      = '';
    public $font_name                  = '';
    public $font_size                  = '';
    public $font_weight                = '';
    public $text_align                 = '';
    public $background_color           = '';
    public $color                      = '';
    public $border_width               = '';
    public $border_color               = '';
    public $border_style               = '';
    public $format                     = '';
    public $report                     = '';
    public $parameters                 = '';
    public $can_grow                   = '';
    public $completed                  = false;
    public $text_string                = array();
    public $above                      = array();
    public $section                    = 0;

    function __construct($pControl, $pSection){

	if(null != $pControl || null != $pSection){
		$this->section_id              = $pSection->id;
		$this->name                    = $pControl->name;
        $this->type                    = $pControl->type;
        $this->source                  = $pControl->source;
        $this->top                     = $pControl->top;
        $this->original_top            = $pControl->top;
        $this->width                   = $pControl->width;
        $this->height                  = $pControl->height;
        $this->original_height         = $pControl->height;
        $this->left                    = $pControl->left;
        $this->font_name               = $pControl->font_name;
        $this->font_size               = $pControl->font_size;
        $this->font_weight             = $pControl->font_weight;
        $this->text_align              = $pControl->text_align;
        $this->background_color        = $pControl->background_color;
        $this->color                   = $pControl->color;
        $this->border_width            = $pControl->border_width;
        $this->border_color            = $pControl->border_color;
        $this->border_style            = $pControl->border_style;
        $this->graph                   = $pControl->graph;
        $this->format                  = $pControl->format;
        $this->report                  = $pControl->report;
        $this->parameters              = $pControl->parameters;
        $this->can_grow                = $pControl->can_grow;
        $this->bottom_gap              = $pSection->height - ($pControl->top + $pControl->height + $pControl->extra_growth);
        $this->section                 = $pControl->section;
	}

}

    public function bottom(){
		return $this->height + $this->extra_growth + $this->top;
    }
	
    public function top(){
	
		if($this->last_used == -1){
			return $this->top;
		}else{
			return 0;
		}
    }
	
    public function line_count(){
		return count($this->text_string);
    }
	
    public function completed($sname){
		$unused_lines = array_slice($this->text_string, $this->last_used + 1, 1000000);
		return count($unused_lines) == 0 || $this->height < 1;
    }
	
    public function get_lines($available_height){
		if($this->original_height != 0){
			$fittable_rows       = floor($available_height / $this->original_height);                      //-- rows that can fit before the next page break	
		}else{
			$fittable_rows		 = 0;
		}
		$line_array          = array_slice($this->text_string, $this->last_used + 1, $fittable_rows);  //-- array of rows that will be used
		$this->last_used     = $this->last_used + count($line_array);                                  //-- reset number of rows currently used
		return $line_array;

	}

	
    public function load_from_array($pcontrol){

		$this->id                  = $pcontrol["id"]                    ;
		$this->section_id          = $pcontrol["section_id"]            ;
		$this->top                 = $pcontrol["top"]                   ;
		$this->original_top        = $pcontrol["original_top"]          ;
		$this->bottom_gap          = $pcontrol["bottom_gap"]            ;
		$this->name                = $pcontrol["name"]                  ;
		$this->type                = $pcontrol["type"]                  ;
		$this->source              = $pcontrol["source"]                ;
		$this->width               = $pcontrol["width"]                 ;
		$this->height              = $pcontrol["height"]                ;
		$this->original_height     = $pcontrol["original_height"]       ;
		$this->left                = $pcontrol["left"]                  ;
		$this->extra_growth        = $pcontrol["extra_growth"]          ;
		$this->extra_height        = $pcontrol["extra_height"]          ;
		$this->last_used           = $pcontrol["last_used"]             ;
		$this->graph               = $pcontrol["graph"]                 ;
		$this->font_name           = $pcontrol["font_name"]             ;
		$this->font_size           = $pcontrol["font_size"]             ;
		$this->font_weight         = $pcontrol["font_weight"]           ;
		$this->text_align          = $pcontrol["text_align"]            ;
		$this->background_color    = $pcontrol["background_color"]      ;
		$this->color               = $pcontrol["color"]                 ;
		$this->border_width        = $pcontrol["border_width"]          ;
		$this->border_color        = $pcontrol["border_color"]          ;
		$this->border_style        = $pcontrol["border_style"]          ;
		$this->graph               = $pcontrol["graph"]                 ;
		$this->bottom_gap          = $pcontrol["bottom_gap"]            ;
		$this->format              = $pcontrol["format"]                ;
		$this->report              = $pcontrol["report"]                ;
		$this->can_grow            = $pcontrol["can_grow"]            ;
		$this->parameters          = $pcontrol["parameters"]            ;
		$this->completed           = $pcontrol["completed"]             ;
	
		for($t = 0 ; $t < count($pcontrol['text_string']) ; $t ++){
			$this->text_string[$t]  = $pcontrol['text_string'][$t];
		}
	
		for($a = 0 ; $a < count($pcontrol['above']) ; $a ++){
			$this->above[$a]  = $pcontrol['above'][$a];
		}
    }
}


class nuText{

    public $id                       = ''; 
    public $control_id               = ''; 
    public $browser                  = ''; 
    public $box_width                = 0; 
    public $paragraph                = ''; 
    public $font_size                = ''; 
    public $font_type                = ''; 
    public $font_name                = ''; 
    public $font_weight              = ''; 
    public $lines                    = array();

    function __construct($pControl, $pText){

		$this->control_id            = $pControl->id;
		$this->browser               = 'explorer';
		$this->width                 = $pControl->width;
		$this->paragraph             = $pText;
		$this->font_size             = $pControl->font_size;
		$this->font_type             = $pControl->font_type;
		$this->font_name             = $pControl->font_name;
		$this->font_weight           = $pControl->font_weight;

		//-- split paragraph into lines,
		//--- loop through lines, splitting and creating
		//--- new lines if they get too long
		$this->split_paragraph();

	}
	  
	function split_paragraph(){

		$lines                         = array();
        $this->paragraph               = nl2br($this->paragraph);
		$swap_br                       = array('<br/>','<Br />','<Br/>','<BR />','<BR/>');
        $this->paragraph               = str_replace($swap_br,'<br />', $this->paragraph);
        $this->paragraph               = str_replace(chr(10), "", $this->paragraph);
    	$lines                         = explode("<br />", $this->paragraph);
		$break_points                  = array(' ', '-', ',', '=', '/');
		$width_percent                 = $this->width * 1.2;
		for($i = 0 ; $i < count($lines) ; $i ++){
			$this_string               = $lines[$i];
			$string_length             = 0;
			$can_break                 = 0;
			$last_break                = 0;
			$start_from                = 0;       
			$I                         = 0;

			if($this->character_size($this_string) <= $width_percent){

			$this->lines[]         = $this_string;                                                   //-- add new line
			}else{
				while(strlen($this_string) > 0){                                                         //-- loop through until all characters have been chopped off
					for($I = 0 ; $I < strlen($this_string) ; $I++){
						
						if(in_array($this_string[$I], $break_points)){                                   //--- check for valid place to break a line
							$can_break         = $I;                                                     //-- set new valid break point
						}

						$left_string           = substr($this_string, 0, $I);
						if($this->character_size($left_string) > $width_percent){                        //-- too big to fit without chopping
							if($can_break == 0){ $can_break = $I;}                                       //-- set chop off point
							$new_line              = substr($left_string, 0, $can_break);                //-- create new line
							$this->lines[]         = $new_line;                                          //-- add add new line
							$this_string           = str_replace($new_line, '', $this_string);           //-- remove new line from remaining string
							$can_break             = 0;                                                  //-- reset break
							$I                     = 0;                                                  //-- reset for loop
						}

					}
					$this->lines[]                 = $this_string;                                       //-- add remaining part of the string
					$this_string                   = '';                                                 //-- remove new line from remaining string
				}
			}
		}
	}


		function character_size($pString){
			
			putenv('GDFONTPATH=C:\Windows\Fonts');                        // you need to tell GD2 where your fonts reside
			$fontname           = "/opt/lampp/lib/fonts/arial.ttf";
			$bbox=ImageTTFBBox($this->font_size, 0, $fontname, $pString);
			if( $bbox === false ) {
print $fontname.'<br/>';
			}
			$right_text         = $bbox[2];                               // right co-ordinate
			$left_text          = $bbox[0];                               // left co-ordinate
			$width_text         = $right_text - $left_text;               // how wide is it?
			return $width_text * 1;

		}

}


function formatString($pString, $pControl){

    return string_length($pString, $pControl->font_size, $pControl->font_name, $pControl->width);

}



function buildFinalObject($OLP){
//-- OLP = one long page

	$Final_Object                                                 = new nuReport($OLP, $OLP->displayJavaScript());
	$Final_Object->page_height                                    = $OLP->page_height;
	$Final_Object->page_width                                     = $OLP->page_width;
	$new_page                                                     = new nuPage();
	

	$reportSectionsArray            = $GLOBALS['nuSections'];

	unset($GLOBALS['nuSections']);
	
	for($G = 0 ; $G < count($reportSectionsArray) ; $G++){

		unset($section_array);
		unset($record);
		unset($controls);
		unset($S);
		
		$nuS = gzuncompress($reportSectionsArray[$G]['the_section']);

		eval($nuS);                                                                               //-- make section_array, record and controls from table record
		

		$S                                                        = new nuSection($emptySection, $new_page); //-- build a section using nothing
		$S->load_from_array($section_array, $record);

		for($ct = 0 ; $ct < count($controls) ; $ct ++){                                                                //-- add controls to rebuilt section
		
			$S->controls[$ct]                                     = new nuControl(null, null);
			$S->controls[$ct]->load_from_array($controls[$ct]);
		}
		if($S->original_height > $OLP->get_remaining_height()){ //====start another page because the next section won't fit
			
			$new_section                                          = new nuSection($S, $new_page);
			$new_section->height                                  = $OLP->get_remaining_height();                      //-- build a blank section the size of the remaining height
			$new_section->name                                    = 'Padding';                                         //-- name this section as 'PageBreak'
			buildClassCode($new_section, count($Final_Object->pages));

//==========page footer===========================
			if($S->name != "Report_Header"){
				$page_footer                                      = $OLP->page_footer;
				$temp_section                                     = new nuSection($page_footer, $new_page);

				for($pf = 0 ; $pf < count($page_footer->controls) ; $pf ++){
					$temp_control                                 = new nuControl($page_footer->controls[$pf], $S);
					$temp_control->text_string                    = formatControlValue($temp_control, $S->record, $OLP);
					$temp_section->controls[]                     = $temp_control;
				}
				$temp_section->record                             = $new_section->record;
				buildClassCode($temp_section, count($Final_Object->pages));
			}
//==========end page footer=======================
			$Final_Object->pages[]                                = $new_page;
			$new_page                                             = new nuPage();                                  //-- build new page
//==========page header===========================
			if($S->name != "Report_Header"){
				$page_header                                      = $OLP->page_header;
				$temp_section                                     = new nuSection($page_header, $new_page);
				for($pf = 0 ; $pf < count($page_header->controls) ; $pf ++){
					$temp_control                                 = new nuControl($page_header->controls[$pf], $S);
					$temp_control->text_string                    = formatControlValue($temp_control, $S->record, $OLP);
					$temp_section->controls[]                     = $temp_control;
				}
				$temp_section->record                             = $new_section->record;
				buildClassCode($temp_section, count($Final_Object->pages));
			}
//==========end page header=======================
			$OLP->reset_remaining_height();
			
		}
	
		while(!$S->finished('while')){
			$new_section                                     = new nuSection($S, $new_page);
			for($c = 0 ; $c < count($S->controls) ; $c ++){                                                           //-- LOOP THROUGH ALL CONTROLS
				if($S->controls[$c]->top + 1 < $OLP->get_remaining_height()){
					$new_control                             = new nuControl($S->controls[$c], $S);                   //-- setup control properties
					if($new_control->type == 'PageBreak'){
						$new_section->height                 = $new_section->section_height();                        //-- resize height to lowest control
						$OLP->reduce_remaining_height($new_section->height);                                          //-- reset the remaining height of the page
						buildClassCode($new_section, count($Final_Object->pages));
						$new_section                         = new nuSection($S, $new_page);
						$new_section->height                 = $OLP->get_remaining_height();                          //-- build a blank section the size of the remaining height
						$new_section->name                   = 'PageBreak';                                           //-- name this section as 'PageBreak'
							
						break;
					}else{
						$new_control->top                    = $S->controls[$c]->top();                                     //-- set height to 0 if not first time through
						$usable_height                       = $OLP->get_remaining_height() - $new_control->top;            //-- area this control has to fit in
						$new_control->text_string            = $S->controls[$c]->get_lines($usable_height);                 //-- put fittable rows into the control
						if($new_control->type == 'Graph'){
							$new_control->last_used          = 0;                                                           //-- set graph as completed
						}
						$new_control->height                 = count($new_control->text_string) * $new_control->height;     //-- resize height of this control
						$new_section->controls[]             = $new_control;                                                //-- add control to current section
					}
				}else{
					if($OLP->get_remaining_height() > 0){
						$S->controls[$c]->top                = $S->controls[$c]->top - $OLP->get_remaining_height();        //-- reduce control's top for next section
					}
				}
			}
			if($new_control->type != 'PageBreak'){
				$new_section->height                              = $new_section->section_height();                     //-- resize height to lowest control
			}
			$OLP->reduce_remaining_height($new_section->height);                                                        //-- reset the remaining height of the page
			buildClassCode($new_section, count($Final_Object->pages));

			if(!$S->finished('if') or $new_control->type == 'PageBreak'){                                               //-- add a page break
				$new_section                                          = new nuSection($S, $new_page);
				$new_section->height                                  = $OLP->get_remaining_height();                   //-- build a blank section the size of the remaining height
				$new_section->name                                    = 'Padding';                                      //-- name this section as 'PageBreak'
				buildClassCode($new_section, count($Final_Object->pages));
			
//==========page footer===========================
				if($S->name != "Report_Header"){
					$page_footer                                      = $OLP->page_footer;
					$temp_section                                     = new nuSection($page_footer, $new_page);

					for($pf = 0 ; $pf < count($page_footer->controls) ; $pf ++){
						$temp_control                                 = new nuControl($page_footer->controls[$pf], $S);
						$temp_control->text_string                    = formatControlValue($temp_control, $S->record, $OLP);
						$temp_section->controls[]                     = $temp_control;
					}

					$temp_section->record                             = $new_section->record;
					buildClassCode($temp_section, count($Final_Object->pages));
				}
//==========end page footer=======================
				$Final_Object->pages[]                                = $new_page;
				$new_page                                             = new nuPage();                                  //-- build new page
//==========page header===========================
				if($S->name != "Report_Header"){
					$page_header                                      = $OLP->page_header;
					$temp_section                                     = new nuSection($page_header, $new_page);
					for($pf = 0 ; $pf < count($page_header->controls) ; $pf ++){
						$temp_control                                 = new nuControl($page_header->controls[$pf], $S);
						$temp_control->text_string                    = formatControlValue($temp_control, $S->record, $OLP);
						$temp_section->controls[]                     = $temp_control;

					}
					$temp_section->record                             = $new_section->record;
					buildClassCode($temp_section, count($Final_Object->pages));
				}
//==========end page header=======================

//-- push down remaining objects by the remaining gap that couldn't be used on this page
				$push_down_from                                       = $S->push_down_from();
				$add_height                                           = $OLP->get_remaining_height();
				$grow_section                                         = false;

				for($pd = $push_down_from ; $pd < count($S->controls) ; $pd++){
				$S->controls[$pd]->top                                = $S->controls[$pd]->top + $add_height;
					$grow_section                                     = true;
				}
				if($grow_section){
					$S->height                                        = $S->height + $add_height;
				}
				
				$OLP->reset_remaining_height();
			}	
		}
	}

//==========padding between report footer and page footer
	$new_section                                                  = new nuSection($S, $new_page);
	$new_section->height                                          = $OLP->get_remaining_height();                          //-- build a blank section the size of the remaining height
	$new_section->name                                            = 'PageBreak';                                           //-- name this section as 'PageBreak'
	buildClassCode($new_section, count($Final_Object->pages));
	
//==========page footer===========================
	$page_footer                                                  = $OLP->page_footer;
	$temp_section                                                 = new nuSection($page_footer, $new_page);
	$temp_section->record                                         = $S->record;

	for($pf = 0 ; $pf < count($page_footer->controls) ; $pf ++){
		$temp_control                                             = new nuControl($page_footer->controls[$pf], $S);
		$temp_control->text_string                                = formatControlValue($temp_control, $S->record, $OLP);
		$temp_section->controls[]                                 = $temp_control;
	}

	$temp_section->record                                         = $new_section->record;
	buildClassCode($temp_section, count($Final_Object->pages));
	$Final_Object->pages[]                                        = $new_page;

	build_report($Final_Object);
tofile('end '.date('Y-m-d H:i:s'));

}





?>
