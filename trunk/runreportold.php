<?php
/*
** File:           runreportold.php
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

session_start( );

$parameters                      = $_GET['ses'];
$form_ses                        = $_GET['form_ses'];
$report                          = $_GET['r'];
$dir                             = $_GET['dir'];

include("../$dir/database.php");
include('common.php');
$session = nuSession($parameters, false);
if($session->foundOK == ''){
	print 'you have been logged out..';
	return;
}

$setup                    = nuSetup();
$T                        = nuRunQuery("SELECT * FROM zzsys_activity WHERE sat_all_code = '$report'");
$A                        = db_fetch_object($T);
//----------allow for custom code----------------------------------------------
eval($A->sat_report_display_code);
$id                       = uniqid('1');
$thedate                  = date('Y-m-d H:i:s');
$dq                       = '"';

if($A->zzsys_activity_id !=''){
	$viewer               = $_SESSION['zzsys_user_id'];
    $s                    = "INSERT INTO zzsys_report_log (zzsys_report_log_id, ";
    $s                    = $s . "srl_zzsys_activity_id, srl_date ,srl_viewer) ";
	$s                    = $s . "VALUES ('$id', '$report', '$thedate', '$viewer')";
	nuRunQuery($s);
}else{
    print 'No Such Report...';
    return;
}

$s                        = "SELECT count(*), MAX(sva_expiry_date) FROM zzsys_variable ";
$s                        = $s . "WHERE sva_id = '$form_ses' ";
$s                        = $s . "GROUP BY sva_expiry_date";
$t1                       = nuRunQuery($s);
$r1                       = db_fetch_row($t1);
$numberOfVariables        = $r1[0];
$expiryDate               = $r1[1];

//---must have at least 1 variable
if($numberOfVariables     > 0){

	$s                    = "DELETE FROM zzsys_variable ";
	$s                    = $s . "WHERE sva_id = '$form_ses' ";
	$s                    = $s . "AND sva_name = 'ReportTitle'";
	nuRunQuery($s);
	setnuVariable($form_ses, $expiryDate, 'ReportTitle', $A->sat_all_description);
    MakeReport($form_ses, $A);
}else{
    print 'Report has Expired...';
}


function MakeReport($parameters, $ACTIVITY){

	$theReport                         = new reportDisplay($parameters, $ACTIVITY);
	$theReport->pageLength             = $ACTIVITY->sat_report_page_length;
	$theReport->setPageLength($ACTIVITY->sat_report_page_length);
	$theReport->buildReport();
	print $theReport->styleSheet;
	$pageNo                            = 1;
//-first page start div 
	$theReport->section[0]->html       = "\n<!-- start of page -->\n<div style='height:$theReport->pageLength'>\n" .  $theReport->section[0]->html;
//-last page end div 
	$theReport->section[count($theReport->section)-1]->html      = $theReport->section[count($theReport->section)-1]->html . "</div>\n<!-- end of page -->\n\n";//--end page div


	for($i = 0 ; $i < count($theReport->section) ; $i++){
		$theReport->section[$i]->html  = str_replace('#thePageNumber#',$pageNo,$theReport->section[$i]->html);
		if($theReport->section[$i]->isFooter){
			$pageNo                    = $pageNo + 1;
		}
	}
	if($pageNo!=1){
		$pageNo                        = $pageNo - 1;
	}
	for($i = 0 ; $i < count($theReport->section) ; $i++){
		$theReport->section[$i]->html  = str_replace('#totalNumberOfPages#',$pageNo,$theReport->section[$i]->html);
	}
	for($i = 0 ; $i < count($theReport->section) ; $i++){
		print $theReport->section[$i]->html;
	}
	print "\n</body></html>";
	for($i = 0 ; $i < count($theReport->tablesUsed) ; $i++){
		nuRunQuery("DROP TABLE " . $theReport->tablesUsed[$i]);
	}
}

class reportDisplay{

    public $headerNumbers              = array();
    public $footerNumbers              = array();
	public $alignment                  = array();
	public $selectionFormVariables     = array();
    public $tablesUsed                 = array();
    public $fields                     = array();
    public $section                    = array();
    public $breakOn                    = array();
    public $pageNumber                 = 0;
    public $pages                      = 0;
    public $pageLength                 = 0;
    public $useablePageLength          = 0;
    public $thisPageLength             = 0;
    public $totalLength                = 0;
    public $resize                     = 0;
    public $TT                         = '';
    public $sumTT                      = '';
    public $styleSheet                 = '';
    public $report                     = null;
    public $orderBy                    = '';
    public $noData                     = false;
    public $reportHeader               = 1;
    public $reportFooter               = 2;
    public $pageHeader                 = 3;
    public $pageFooter                 = 4;
    public $hasReportHeader            = false;
    public $hasReportFooter            = false;
    public $hasPageHeader              = false;
    public $hasPageFooter              = false;
    public $growBy                     = 0;
    public $reportID                   = '';

    function __construct($parameters, $ACTIVITY){
    	
    	$this->resize                  = 0.0679;
    	$this->reportID                = $ACTIVITY->zzsys_activity_id;
 
		$this->report                  = new Reporting();
	    $this->hasReportHeader         = $this->validSection($this->reportHeader);
	    $this->hasReportFooter         = $this->validSection($this->reportFooter);
	    $this->hasPageHeader           = $this->validSection($this->pageHeader);
	    $this->hasPageFooter           = $this->validSection($this->pageFooter);
		$this->fixControlNames();
		$this->buildBreakOnArray();
		$this->headerNumbers           = array(5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31);
		$this->footerNumbers           = array(6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32);
		$this->alignment               = array('left', 'left', 'center', 'right');
		$this->selectionFormVariables  = getSelectionFormVariables($parameters);
		$this->tablesUsed              = getSelectionFormTempTableNames($parameters, $this->selectionFormVariables);
		$this->TT                      = TT();
		$this->sumTT                   = TT();
		$this->tablesUsed[]            = $this->TT;
		$dataTable                     = $this->TT;
		$formValue                     = $this->selectionFormVariables;
		eval($ACTIVITY->sat_report_data_code);
		$this->TT                      = $dataTable;
//		BuildTable($this->selectionFormVariables, $this->TT);
		$this->addSelectionFormVariablesToTable($this->selectionFormVariables);
		$t                             = nuRunQuery("SELECT * FROM $this->TT LIMIT 0 , 1");
		$this->fields                  = tableFieldNamesToArray($t);
		if($_GET['tt'] != ''){//--- create a temp table to debug
			nuRunQuery("CREATE TABLE " . $_GET['tt'] . " SELECT * FROM $this->TT");
		}
		$this->sumTotals();
		$this->orderBy                 = $this->orderByClause();
		$this->styleSheet              = $this->buildStyleSheet();
    }
	
	public function buildReport(){
tofile('step 1 :'.time());

     	$t                             = nuRunQuery("SELECT * FROM $this->TT $this->orderBy");
     	$lastRecord                    = array();
     	$isFirstRecord                 = true;
     	$counter                       = 0;
     	while($thisRecord              = db_fetch_array($t)){
//tofile('step 1.5 :'.time());
     		$counter = $counter+1;
//=======start off with all the headers for first record=================================
			if($isFirstRecord){
//---build report header and first page header
				$this->buildSection($this->reportHeader, false, $thisRecord);
				$this->buildSection($this->pageHeader, false, $thisRecord);
				if($this->noData){return;}
				$lastRecord           = $thisRecord;
			}
//=======loop through records============================================================
//---check if groups have changed
			$buildHeader = -1;
			for ($i = count($this->breakOn) ; $i >= 0 ; $i--){
				if($lastRecord[$this->breakOn[$i]] != $thisRecord[$this->breakOn[$i]]){
					$buildHeader = $i;
				}
			}
	
//---build footers back to group that has changed  (eg. $buildHeader=9   build 9,11,13)
			if(!$isFirstRecord){
				if($buildHeader != -1){
					for ($i = count($this->breakOn) ; $i >= $buildHeader ; $i--){
						$this->buildSection(($i * 2) + 6, true, $lastRecord);
					}
				}
			}
//---build headers forward to group that has changed  (eg. $buildHeader=9   build 14,12,10)
			if($isFirstRecord){$buildHeader = 0;}
			if($buildHeader != -1 or $isFirstRecord){
				for ($i = $buildHeader ; $i < count($this->breakOn) ; $i++){
						$this->buildSection(($i * 2) + 5, true, $thisRecord);
				}
			}
//---build detail section
			$this->buildSection(0, true, $thisRecord);
     		$lastRecord                     = $thisRecord;
			$isFirstRecord                  = false;
     	}
tofile('step 2 :'.time());

//=======finish off footers at end of report============================================
//---build last group footers
		for ($i = 20 ;  $i >= 0 ; $i--){
			$this->buildSection($this->footerNumbers[$i], true, $lastRecord);
		}
		$this->buildSection($this->reportFooter, false, $lastRecord);
		$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength; 
 		$s                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n   </div>\n\n";
		$this->addReportSection(false, $spaceToEndOfPage, $s, '-1', 'filler');
		$this->buildSection($this->pageFooter, false, $lastRecord, true);
	}
    

	public function buildSection($sectionNumber, $canGrow, $tableRecord, $lastSection = false){

		$controlSectionNumber               = $sectionNumber;
		$sectionColor                       = $this->report->Sections[$sectionNumber]->BackColor;
		if($sectionColor=='#ebebeb'){$sectionColor='#ffffff';}
		$sectionNumber                      = $this->arrayNoForSectionNo($sectionNumber);
		if($this->report->Sections[$sectionNumber]->Name == ''){return;}
 		$n                                  = $this->report->Sections[$sectionNumber]->Name;
 		$this->growBy                       = 0;
 		$sectionControlsHTML                = $this->buildControls($controlSectionNumber, $canGrow, $tableRecord);
 		$height                             = $this->growBy + $this->toScale($this->report->Sections[$sectionNumber]->Height);
 		$top                                = $this->thisPageLength;
//--------------------------check if page footer is needed--------------------------------
		$addPageBreak						= false;
		if($sectionNumber != $this->pageFooter and $sectionNumber != $this->pageHeader){
	 		if($this->thisPageLength + $height > $this->useablePageLength){
	 			$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength; 
	 			if($this->reportFooter != $sectionNumber){
			 		$s                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n";
			 		$s                          = $s . "   </div>\n\n";
	 			}
				$this->addReportSection(false, $height, $s, $sectionNumber, 'filler');
		       	$this->setCurrentLength($height);
				$this->buildSection($this->pageFooter, false, $tableRecord);
				$addPageBreak				= true;
				if($GLOBALS['pageBreak']){
			 		$s                      =      "   <div class='PageBreak' style='position:relative;height:0'>.</div>\n";
				}
				$GLOBALS['pageBreak'] = false;
				$this->addReportSection(false, $height, $s, $sectionNumber, 'aPageBreak');
				$this->thisPageLength       = 0;
				$this->buildSection($this->pageHeader, false, $tableRecord);
			}
		}
//--------------------------end of check--------------------------------------------------
 		$top                                = $this->thisPageLength;
		if($height == 0){$height='';}
 		$s                                  = "   <div class='$n' style='position:relative;height:$height;background-color:$sectionColor'>\n";
       	$this->setCurrentLength($height);
 		$s                                  = $s . $sectionControlsHTML;
 		$s                                  = $s . "   </div>\n\n";
 		if($sectionNumber == $this->pageFooter){//--add page break
	 		$s                              = $s . "</div>\n<!-- end of page -->\n\n";//--end page div
	 		if(!$lastSection){
		 		$s                              = $s . "<div class='PageBreak' tag='test' style='position:relative;'>.</div>\n\n";
	 			$s                              = $s . "\n\n<!-- start of page -->\n<div style='height:$this->pageLength'>\n";//--end page div
	 		}
 		}
		
		$this->addReportSection($sectionNumber == $this->pageFooter, $height, $s, $sectionNumber, $this->sectionType($sectionNumber));
	}


	public function sectionType($sectionNumber){

		if($sectionNumber == 0){
			return 'detail';
		}
		if(in_array ($sectionNumber, $this->headerNumbers)){
			return 'header';
		}
		if(in_array ($sectionNumber, $this->footerNumbers)){
			return 'footer';
		}
		if($sectionNumber == 3){
			return 'page header';
		}
		if($sectionNumber == 4){
			return 'page footer';
		}
		if($sectionNumber == 1){
			return 'report header';
		}
		if($sectionNumber == 2){
			return 'report footer';
		}
		return -1;
		
	}

    public function addSelectionFormVariablesToTable($v){
    	
    	$s                                  = "SELECT 'x' as xes ";
		while(list($key, $value)            = each($v)){
            if($key!=''){
            	$value                      = str_replace('\"', '"', $value);
    	    	$s                              = $s . ' , "' . $value . '" AS ' . $key . ' ';
            }
		}
		$t                                  = nuRunQuery("SELECT COUNT(*) FROM $this->TT");
		$r                                  = db_fetch_row($t);
		if($r[0] == 0){
			nuRunQuery("DROP TABLE $this->TT");
			nuRunQuery("CREATE TABLE $this->TT $s");
			$this->noData                   = true;
		}else{
			nuRunQuery("CREATE TABLE a$this->TT $s, $this->TT.* FROM $this->TT");
			nuRunQuery("DROP TABLE $this->TT");
			nuRunQuery("CREATE TABLE $this->TT SELECT * FROM a$this->TT");
			nuRunQuery("DROP TABLE a$this->TT");
		}
    }

	public function orderByClause(){

		if($this->noData){return;}
		for($i = 0 ; $i < count($this->report->Groups) ; $i++){
			if($i==0){
				$s                          = 'Order By ';
			}else{
				$s                          = $s . ', ';
			}
			$s                              = $s . $this->report->Groups[$i]->Field . ' ';
			$s                              = $s . $this->report->Groups[$i]->SortOrder;
			$index                          = $this->report->Groups[$i]->Field;
	        nuRunQuery("ALTER TABLE $this->TT ADD INDEX ($index)");
		}
		return $s;
		
	}

    
    public function fixControlNames(){
		for($i=0;$i<=count($this->report->Controls)-1;$i++){
			$n = $this->report->Controls[$i]->Name;
			$n = str_replace('/', '__', $n);
			$n = str_replace(' ', '__', $n);
			$this->report->Controls[$i]->Name = $n;
		}
    }


	public function sumTotals(){
		if($this->noData){return;}
		$FieldList                          = array();
		for($i = 0 ; $i < count($this->report->Groups) ; $i++){
			if($i == 0){
				$s                          = 'SELECT '   . $this->report->Groups[$i]->Field;
				$g                          = 'GROUP BY ' . $this->report->Groups[$i]->Field;
			}else{
				$s                          = $s . ', '   . $this->report->Groups[$i]->Field;
				$g                          = $g . ', '   . $this->report->Groups[$i]->Field;
			}
		}
		for($i = 0 ; $i < count($this->report->Controls) ; $i++){
			if($this->report->Controls[$i]->ControlType=='109'){
				if(substr($this->report->Controls[$i]->ControlSource, 0, 6)=='=Sum(['){
					$SumOn                  = str_replace('=Sum([','',$this->report->Controls[$i]->ControlSource);
					$SumOn                  = str_replace('])','',$SumOn);
					$SumOn                  = trim($SumOn);
					if (!in_array ($SumOn, $FieldList)) {
						$s                  = $s . ", Sum($SumOn) AS $SumOn"."_Sum";
						$FieldList[]        = $SumOn;
					}
				}
			}
		}
	
		if(count($this->report->Groups) > 0){
			nuRunQuery("CREATE TABLE $this->sumTT $s FROM $this->TT $g");
	        $this->addIndexes();
		}else{
			nuRunQuery("CREATE TABLE $this->sumTT SELECT 1");
	    }
		$this->tablesUsed[]                 = "$this->sumTT";
	}

	public function validSection($pSectionNumber){
		for($i = 0 ; $i < count($this->report->Sections) ; $i++){
			if($this->report->Sections[$i]->SectionNumber == $pSectionNumber){
				return true;
			}
		}
		return false;
	}
	
	public function setPageLength($pageLength){
		$this->pageLength                   = $pageLength;
		$this->useablePageLength            = $pageLength;
		if($this->hasPageFooter){
			$this->useablePageLength        = $pageLength - ($this->report->Controls[$this->pageFooter]->Height * $this->resize);      
		}
	}

	public function setCurrentLength($pAddLength){
		
		$this->thisPageLength               = $this->thisPageLength + $pAddLength;
		$this->totalLength                  = $this->totalLength + $pAddLength;
		if($this->useablePageLength         < $this->thisPageLength+1){
			$this->thisPageLength           = 0;
		}
		
	}
	public function addIndexes(){
	
	    $t = nuRunQuery("SELECT * FROM $this->sumTT");
	    for ($i = 0 ; $i < mysql_num_fields($t) ; $i++) {
	        $r = mysql_fetch_field($t);
            nuRunQuery("ALTER TABLE $this->sumTT ADD INDEX ($r->name)") ;
	    }
	}



	public function buildControls($sectionNumber, $canGrow, $record){

		$s                                  = '';
	    $setup                              = nuSetup();
		$dq                                 = '"';
		for($i = 0 ; $i < count($this->report->Controls) ; $i++){
			if($this->report->Controls[$i]->Section == $sectionNumber){
				$Name                       = $this->report->Controls[$i]->Name;
				$Source                     = $this->report->Controls[$i]->ControlSource;
				$Controltype                = $this->report->Controls[$i]->Controltype;
				$Top                        = $this->toScale($this->report->Controls[$i]->Top);
				$Width                      = $this->toScale($this->report->Controls[$i]->Width);
				$Height                     = $this->toScale($this->report->Controls[$i]->Height);
				$Left                       = $this->toScale($this->report->Controls[$i]->Left);
				$Section                    = $this->report->Controls[$i]->Section;
				$Fontname                   = $this->report->Controls[$i]->FontName;
				$Fontsize                   = $this->report->Controls[$i]->FontSize;
				$Format                     = $this->report->Controls[$i]->Format;
				$Decimal                    = $this->report->Controls[$i]->DecimalPlaces;
				$IsHyperlink                = $this->report->Controls[$i]->IsHyperlink;
				$ReportTag                  = $this->report->Controls[$i]->Tag;
				$LikeClause                 = $this->report->Controls[$i]->SmartTags;
				$LikeClause                 = str_replace("\"","",$LikeClause);
				//image
	
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'page break'){
					$GLOBALS['pageBreak'] = true;
	                  	$s                = "</div><div style='height:0'></div><div>";
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'graph'){
                    $thetag             = $this->report->Controls[$i]->ControlSource;
                    $thename            = $this->report->Controls[$i]->Graph;
                    $thedir             = $_GET['dir'];
                    for($a=0;$a<count($this->fields);$a++){
                        //-----replace any strings, with hashes around them, in the querystring that match
                        //-----fieldnames in the table
                        //-----with values from the table
                        //-----e.g. id=#ID# could become id=007
                        $thetag = str_replace('#'.$this->fields[$a].'#', $record[$this->fields[$a]], $thetag);
                    }
                    $s                  = $s . "   <div  style='position:absolute;left:$Left;top:$Top;height:$Height'>\n";
					$s                  = $s . "      <img src='graph_report.php?dir=$thedir&activityID=$this->reportID&graph_name=$thename&$thetag'>\n";
                    $s                  = $s . "   </div>\n";
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'label'){
					$s                      = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>".$this->report->Controls[$i]->Caption."</div>\n";
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'text area'){
					
					$formattedValue = $record[$this->report->Controls[$i]->ControlSource];
					//Sum
					$theSource                  = $this->report->Controls[$i]->ControlSource;
					if(substr($theSource, 0, 6)=='=Sum([' OR strtoupper(substr($theSource, 0, 10))=='=PERCENT(['){
	                    $sumIt=substr($theSource, 0, 6)=='=Sum([';
	                    if($sumIt){  //--if the sum function
	    					$SumOn              = str_replace('=Sum([','',$theSource);
		       				$SumOn              = str_replace('])','',$SumOn);
	
		       				$selectFields       = 'sum('.$SumOn.'_Sum)';
	                    }else{        //--if the Percent function
	    					$SumOn              = substr($theSource,10);
		       				$SumOn              = str_replace(']','',$SumOn); //-- remove right square brackets
		       				$SumOn              = str_replace('[','',$SumOn); //-- remove left square brackets
		       				$SumOn              = str_replace(')','',$SumOn); //-- remove only right bracket
		       				$SumOn              = str_replace(' ','',$SumOn); //-- remove any spaces
		       				$theFields          = explode(',', $SumOn);   //-- split left and right field
		       				$selectFields       = 'sum('.$theFields[0].'_Sum) as divideIt, sum('.$theFields[1].'_Sum) as BYY ';
	                    }
						$SectionLevel           = $this->sectionLevel($sectionNumber);
						for($ii=0;$ii<=$SectionLevel;$ii++){
							if($ii==0){
								if($this->report->Groups[$ii]->Field<>''){
									$whereClause = $this->report->Groups[$ii]->Field." = $dq".$record[$this->report->Groups[$ii]->Field]."$dq";
								}
							}else{
								if($this->report->Groups[$ii]->Field<>''){
									$whereClause = $whereClause.' AND '.$this->report->Groups[$ii]->Field." = $dq".$record[$this->report->Groups[$ii]->Field]."$dq";
								}
							}
						}
						$SumOn=trim($SumOn);
						if(strlen($SectionLevel)==0){
							$whereClause         = "SELECT $selectFields FROM $this->sumTT";
						}else{
							$whereClause         = "SELECT $selectFields FROM $this->sumTT WHERE $whereClause";
						}
						$t1                      = nuRunQuery($whereClause);
						$r1                      = mysql_fetch_row($t1);
						$formattedValue          = $r1[0];
						if(!$sumIt){  //---using the 'PERCENT' function
	                        if($r1[1]==0){
	                            $formattedValue  = 0; //--because nothing can be divided by zero
	                        }else{
	                            $formattedValue  = ($r1[0] / $r1[1]) * 100;
	                        }
	                    }
	    				if($Format=='Fixed'){
	                         if($r1[0]!=''){
	                             $formattedValue = number_format($r1[0],$Decimal);
	                        }
	                    }//format of the result of the sum
		       			if($Format=='Currency'){
	                        if($r1[0]!=''){
	        		      		$formattedValue  = "$".number_format($r1[0],2);
	     				       	$formattedValue  = str_replace('$-','-$',$formattedValue);
	                        }
					    }
	
						$s                       = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
					}else{
						//page display
						if($Source=='="Page " & [Page] & " of  " & [Pages]'){
							$s                      = $s . "   <div class='$Name'>Page #thePageNumber# of #totalNumberOfPages#</div>\n";
						}else{
							//date display
							if($Source=='=Date()' OR $Source=='=Now()'){
								$formattedValue     = date($this->accessDateFormatToPHPDateFormat($Format));
								$s                  = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
							}else{
								//format number
								if($Format=='Fixed'){
	                                if($record[$this->report->Controls[$i]->ControlSource]!=''){
										$formattedValue  = number_format($record[$this->report->Controls[$i]->ControlSource],$Decimal);
									}
								}
								//currency number
								if($Format=='Currency'){
									$formattedValue      = "$".number_format($record[$this->report->Controls[$i]->ControlSource],2);
									$formattedValue      = str_replace('$-','-$',$formattedValue);
								}
								//date format (no todate() and now())
								if(substr($Format,0,2)=='dd' or substr($Format,0,2)=='mm' or substr($Format,0,2)=='yy' or substr($Format,0,2)=='hh' or substr($Format,0,2)=='nn' or substr($Format,0,2)=='ss'){
									//for sql format case yyyy-mm-dd
									if(substr($formattedValue,4,1)=='-' and substr($formattedValue,7,1)=='-'){
										$timestamp       = mktime(0, 0, 0, substr($formattedValue,5,2), substr($formattedValue,8,2), substr($formattedValue,0,4));
										$formattedValue  = date($this->accessDateFormatToPHPDateFormat($Format),$timestamp);
									}
								}
								if($this->report->Controls[$i]->CanGrow == 'True' and $canGrow){
									$oldValue               = $formattedValue;
									if($this->report->nuBuilder == '1'){
										$textWidth          = str_replace('px', '',$this->report->Controls[$i]->Width)/($this->report->Controls[$i]->FontSize/2);
										$formattedValue     = wordwrap($formattedValue, $textWidth, "<br />"); 
									}else{
										$formattedValue     = wordwrap($formattedValue, iif($ReportTag=='',10,$ReportTag), "<br />"); 
									}
									$formattedValue         = nl2br($formattedValue);
									$lines                  = substr_count($formattedValue, "<br />")+1;
									$defaultHeight          = $Height;
									$Height                 = $Height * $lines;
									if($this->growBy < $Height - $defaultHeight){
										$this->growBy       = $Height - $defaultHeight;
									}
									$s                      = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
								}else{
									$s                      = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
								}
							}
						}
					}
	    		}
			}
		}
		return $s;
	}
	
	public function controlType($pControlNumber){
		if( $pControlNumber == '118'){return 'page break';}
		if( $pControlNumber == '103'){return 'graph';}
		if( $pControlNumber == '100'){return 'label';}
		if( $pControlNumber == '109'){return 'text area';}
		if( $pControlNumber == 'PageBreak'){return 'page break';}
		if( $pControlNumber == 'Graph'){return 'graph';}
		if( $pControlNumber == 'Label'){return 'label';}
		if( $pControlNumber == 'Field'){return 'text area';}
	}
	
	public function toScale($pSize){
		if($this->report->nuBuilder == '1'){
			return iif($pSize == '',0,$pSize);
		}else{
			return iif($pSize == '',0,$pSize) * $this->resize;
		}
	}


	public function buildStyleSheet(){
		
		$s                                  = '';
		$s                                  = $s . "<html>\n<title></title>\n";
		$s                                  = $s . "<style type='text/css'>\n";

		for($i=0;$i<=count($this->report->Controls)-1;$i++){
	
	       $EventProcPrefix                 = $this->report->Controls[$i]->EventProcPrefix;
	       $Name                            = $this->report->Controls[$i]->Name;
	       $ControlType                     = $this->report->Controls[$i]->ControlType;
	       $ControlSource                   = $this->report->Controls[$i]->ControlSource;
	       $Format                          = $this->report->Controls[$i]->Format;
	       $DecimalPlaces                   = $this->report->Controls[$i]->DecimalPlaces;
	       $InputMask                       = $this->report->Controls[$i]->InputMask;
	       $IMEHold                         = $this->report->Controls[$i]->IMEHold;
	       $IMEMode                         = $this->report->Controls[$i]->IMEMode;
	       $IMESentenceMode                 = $this->report->Controls[$i]->IMESentenceMode;
	       $Visible                         = $this->report->Controls[$i]->Visible;
	       $Vertical                        = $this->report->Controls[$i]->Vertical;
	       $Top                             = $this->report->Controls[$i]->Top;
	       $HideDuplicates                  = $this->report->Controls[$i]->HideDuplicates;
	       $CanGrow                         = $this->report->Controls[$i]->CanGrow;
	       $CanShrink                       = $this->report->Controls[$i]->CanShrink;
	       $RunningSum                      = $this->report->Controls[$i]->RunningSum;
	       $Top                             = $this->toScale($this->report->Controls[$i]->Top);
	       $Width                           = $this->toScale($this->report->Controls[$i]->Width);
	       $Left                            = $this->toScale($this->report->Controls[$i]->Left);
	       $BackStyle                       = $this->report->Controls[$i]->BackStyle;
	       $BackColor                       = $this->report->Controls[$i]->BackColor;
	       $SpecialEffect                   = $this->report->Controls[$i]->SpecialEffect;
	       $BorderStyle                     = 'solid';
	       $OldBorderStyle                  = $this->report->Controls[$i]->OldBorderStyle;
	       $BorderColor                     = $this->report->Controls[$i]->BorderColor;
	       $BorderWidth                     = $this->report->Controls[$i]->BorderWidth;
	       $BorderLineStyle                 = $this->report->Controls[$i]->BorderLineStyle;
	       $ForeColor                       = $this->report->Controls[$i]->ForeColor;
	       $FontName                        = $this->report->Controls[$i]->FontName;
			if($this->report->nuBuilder == '1'){
		       $FontSize                    = $this->report->Controls[$i]->FontSize;
			}else{
			   if($FontName                 =='Arial'){
			       $FontSize                = $this->report->Controls[$i]->FontSize*1.3;
			   }else{
			       $FontSize                = $this->report->Controls[$i]->FontSize*1.5;
			   }
			}
	       $FontWeight                      = $this->report->Controls[$i]->FontWeight;
	       $FontItalic                      = $this->report->Controls[$i]->FontItalic;
	       $FontUnderline                   = $this->report->Controls[$i]->FontUnderline;
	       $TextFontCharSet                 = $this->report->Controls[$i]->TextFontCharSet;
	       $TextAlign                       = $this->alignment[$this->report->Controls[$i]->TextAlign];
	       $FontBold                        = $this->report->Controls[$i]->FontBold;
	       $ShortcutMenuBar                 = $this->report->Controls[$i]->ShortcutMenuBar;
	       $Section                         = $this->report->Controls[$i]->Section;
	       $Tag                             = $this->report->Controls[$i]->Tag;
	       $InSelection                     = $this->report->Controls[$i]->InSelection;
	       $ReadingOrder                    = $this->report->Controls[$i]->ReadingOrder;
	       $KeyboardLanguage                = $this->report->Controls[$i]->KeyboardLanguage;
	       $ScrollBarAlign                  = $this->report->Controls[$i]->ScrollBarAlign;
	       $NumeralShapes                   = $this->report->Controls[$i]->NumeralShapes;
	       $LeftMargin                      = $this->report->Controls[$i]->LeftMargin;
	       $TopMargin                       = $this->report->Controls[$i]->TopMargin;
	       $RightMargin                     = $this->report->Controls[$i]->RightMargin;
	       $BottomMargin                    = $this->report->Controls[$i]->BottomMargin;
	       $LineSpacing                     = $this->report->Controls[$i]->LineSpacing;
	       $IsHyperlink                     = $this->report->Controls[$i]->IsHyperlink;
	       $SmartTags                       = $this->report->Controls[$i]->SmartTags;
	       $Caption                         = $this->report->Controls[$i]->Caption;
	
//	        $StyleString                    = "overflow:hidden;position:absolute;font-size:$FontSize";
	        $StyleString                    = "overflow:hidden;position:absolute;font-size:$FontSize";
	        $StyleString                    = "$StyleString;font-family:$FontName";
	        $StyleString                    = "$StyleString;font-weight:$FontWeight";
			if($BackStyle==0){
		        $StyleString                = "$StyleString;background-color:transparent";
			}else{
		        $StyleString                = "$StyleString;background-color:$BackColor";
			}
	        $StyleString                    = "$StyleString;color:$ForeColor";
	        $StyleString                    = "$StyleString;text-align:$TextAlign";
	        $StyleString                    = "$StyleString;border-width:$BorderWidth";
	        $StyleString                    = "$StyleString;border-color:$BorderColor";
	        $StyleString                    = "$StyleString;border-style:$BorderStyle";
	        $StyleString                    = "$StyleString;top:$Top";
	        $StyleString                    = "$StyleString;left:$Left";
	        $StyleString                    = "$StyleString;width:$Width";
	        $StyleString                    = "$StyleString;margin-left:$LeftMargin";
	        $StyleString                    = "$StyleString;margin-right:$RightMargin";
			$s                              = $s . ".$Name { $StyleString }\n";
		}
		$s                                  = $s . ".PageBreak {page-break-before:always;font-size:1 }\n";
		$s                                  = $s . ".H1 { font-size: x-large; color: red }\n";
		$s                                  = $s . "@media print {.dontPrintMe{display : none; style='top:0;left:0'}}\n";
		$s                                  = $s . "</style>\n";
		$s                                  = $s . "\n<body>\n";
		return $s;
	}
	

	public function arrayNoForSectionNo($SectionNumber){//--the array number is not always the same as the section!
		for($i = 0 ; $i < count ($this->report->Sections) ; $i++){
			if($this->report->Sections[$i]->SectionNumber == $SectionNumber){
				return $i;
			}
		}
	}

	public function sectionLevel($pSectionNumber){
		
		if (in_array ($pSectionNumber, $this->headerNumbers)) {
			for($i = 0 ; $i < count($this->headerNumbers) ; $i++){
				if($this->headerNumbers[$i]==$pSectionNumber){
					return $i;
				}
			}
		}
		if (in_array ($pSectionNumber, $this->footerNumbers)) {
			for($i = 0 ; $i < count($this->footerNumbers) ; $i++){
				if($this->footerNumbers[$i]==$pSectionNumber){
					return $i;
				}
			}
		}

	}

	public function accessDateFormatToPHPDateFormat($pFormat){

		//creation of the date format
		//you can use in access : dd,mmm,mm,yyyy,yy,hh,nn,ss
		$Format=str_replace('dddd','l',$pFormat);
		$Format=str_replace('ddd','D',$Format);
		$Format=str_replace('dd','d',$Format);
		$Format=str_replace('mmmm','F',$Format);
		$Format=str_replace('mmm','M',$Format);
		$Format=str_replace('mm','m',$Format);
		$Format=str_replace('yyyy','Y',$Format);
		$Format=str_replace('yy','y',$Format);
		$Format=str_replace('hh','H',$Format);
		$Format=str_replace('nn','i',$Format);
		$Format=str_replace('ss','s',$Format);
		return $Format;
		
	}
	
	public function addReportSection($isFooter, $height, $html, $sectionNumber, $type){

		$this->section[]                            = new reportSection();
		$arrayNumber                                = count($this->section)-1;
		$sectionGroup                               = '';
		$this->section[$arrayNumber]->isFooter      = $isFooter; 
		$this->section[$arrayNumber]->height        = $height; 
		$this->section[$arrayNumber]->html          = $html; 
		$this->section[$arrayNumber]->sectionNumber = $sectionNumber; 
		$this->section[$arrayNumber]->type          = $type;
		
	}

    public function buildBreakOnArray(){
    	//--build array of groups with either a header or a footer 
    	for($I = 0 ; $I < count($this->report->Groups) ; $I++){
	    	$headerNumber                               = ($I * 2) + 6;
	    	$footerNumber                               = ($I * 2) + 5;
	    	for($i = 0 ; $i < count($this->report->Sections) ; $i++){
	    		$sectionNumber                          = $this->report->Sections[$i]->SectionNumber;
	    		if($sectionNumber == $headerNumber OR $sectionNumber == $footerNumber){
	    			if(!in_array($this->report->Groups[$I]->Field, $this->breakOn)){
		    			$this->breakOn[]                    = $this->report->Groups[$I]->Field;
	    			}
	    		}
	    	}
    	}
    }

}

class reportSection{

	public $height                          = 0;
    public $group                           = '';
    public $type                            = '';
    public $sectionNumber                   = 0;
	public $page                            = 0;
    public $html                            = '';

    function __construct(){
    	
    }
		
}




?>
