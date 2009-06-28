<?php
/*
** File:           run_report_pdf_v1.php
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

$dir = $_GET['dir'];
include_once("../$dir/database.php");
include_once('common.php');
define('FPDF_FONTPATH','fpdf/fonts/');
require('fpdf/fpdf.php');
$setup = nuSetup();
//eval($setup->set_php_code);

class reportDisplay extends FPDF{

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
    public $reportTitle				   = '';
    public $justDidPageBreak           = false;
    public $PN                         = 1;
    
    public $stackheight				   = 0;
    public $imagecount				   = 0;

    public $next					   = null;
    public $nextRecord				   = null;
    public $totalRows				   = 0;
    public $rowCount				   = 0;
    public $hasExplicitPageBreak	   = false;

    public $canGrowBy				   = 0;

    function __construct($parameters, $ACTIVITY){

    	$this->resize                  = 0.0679;
    	//$this->resize                  = 1.0;
    	$this->reportID                = $ACTIVITY->zzsys_activity_id;
 		$this->reportTitle			   = $ACTIVITY->sat_all_description;
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
        $hashSelectionFormVariables    = arrayToHashArray($this->selectionFormVariables);
		$this->tablesUsed              = getSelectionFormTempTableNames($parameters, $this->selectionFormVariables);
		$this->TT                      = TT();
		$this->sumTT                   = TT();
		$this->tablesUsed[]            = $this->TT;
		$dataTable                     = $this->TT;
		$formValue                     = $this->selectionFormVariables;
		$globalValue                   = getglobalValue($_GET['ses']);
//----------create an array of hash variables that can be used in any "hashString"
		$sesVariables                  = recordToHashArray('zzsys_session', 'zzsys_session_id', $_GET['ses']);  //--session values (access level and user etc. )
		$sesVariables['#dataTable#']   = $this->TT;
		$sysVariables                  = sysVariablesToHashArray($_GET['form_ses']);                            //--values in sysVariables from the calling lookup page
		$arrayOfHashVariables          = joinHashArrays($sysVariables, $sesVariables);                          //--join the arrays together
		$arrayOfHashVariables          = joinHashArrays($arrayOfHashVariables, $hashSelectionFormVariables);    //--put temp table names for listboxes

		//----------allow for custom code----------------------------------------------
		eval(replaceHashVariablesWithValues($arrayOfHashVariables, $ACTIVITY->sat_report_data_code));

//		eval($ACTIVITY->sat_report_data_code);
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
		$this->justDidPageBreak		   = false;
    }

	public function buildReport(){

		if ($this->pageLength >= 1000 && $this->pageLength <= 1100) {
			$orientation = 'P';
		} else if ($this->pageLength >= 700 && $this->pageLength <= 800) {
			$orientation = 'L';
		} else {
			$orientation = 'P';
		}
		$unit='in';
		$format='A4';

		$this->stackheight = 0;
	
    	//Call parent constructor
    	$this->FPDF($orientation,$unit,$format);
    	$this->AddPage();
    	$this->pageNumber = $this->pageNumber + 1;
    	//tofile("Run Thru Total Pages ".$GLOBALS['CountTotalPages']);  
    	if ($GLOBALS['CountTotalPages'] == 0) {
    		$GLOBALS['TotalPages'] = $this->pageNumber;
    	}

		$this->SetTitle($this->reportTitle);

     	$t                             = nuRunQuery("SELECT * FROM $this->TT $this->orderBy");
     	$this->next					   = nuRunQuery("SELECT * FROM $this->TT $this->orderBy");
		$this->totalRows 						= mysql_num_rows($t);
     	$lastRecord                    = array();
     	$isFirstRecord                 = true;
     	$counter                       = 0;

		$this->rowCount = 0;
     	
     	while($thisRecord              = db_fetch_array($t)){
     		$this->nextRecord		   = db_fetch_array($this->next);
     		$counter = $counter+1;
    		//tofile("Run Thru Total Pages ".$GLOBALS['CountTotalPages']);      		
//=======start off with all the headers for first record=================================
			if($isFirstRecord){
				//tofile("Report Start: ".$this->stackheight);
//---build report header and first page header
				$this->buildSection($this->reportHeader, false, $thisRecord);
 				$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->reportHeader]->Height); 				
				//tofile($this->report->Sections[$this->reportHeader]->Height);
				//tofile("Report Header: ".$this->stackheight);
				//tofile("ReportHeader ".$this->reportHeader);
				$this->buildSection($this->pageHeader, false, $thisRecord);
				//tofile("PageHeader ".$this->pageHeader);
 				$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->pageHeader]->Height); 
				//tofile("Page Header: ".$this->stackheight);				
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
						//tofile("Section: ".($i * 2) + 6);
						if ($this->hasExplicitPageBreak == false) {
							//tofile("Page Footer Not End: ".$this->stackheight);
							$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[(($i * 2) + 6)]->Height);
						}
					}
				}
			}						
			
//---build headers forward to group that has changed  (eg. $buildHeader=9   build 14,12,10)
			if($isFirstRecord){$buildHeader = 0;}
			if($buildHeader != -1 or $isFirstRecord){
				for ($i = $buildHeader ; $i < count($this->breakOn) ; $i++){
						$this->buildSection(($i * 2) + 5, true, $thisRecord);
						//tofile("Section: ".($i * 2) + 6);
						$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[(($i * 2) + 5)]->Height);
						//tofile("Section Header: ".$this->stackheight);
				}
			}
//---build detail section
			$this->rowCount = $this->rowCount + 1;
			//tofile("Detail 1: ".$this->stackheight);			
			$this->buildSection(0, true, $thisRecord);
			$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[0]->Height) + $this->canGrowBy;
			//tofile("Grow By: ".$this->canGrowBy);
			$this->canGrowBy = 0;			
			//tofile("Detail 2: ".$this->stackheight);

     		$lastRecord                     = $thisRecord;
			$isFirstRecord                  = false;
     	}

//=======finish off footers at end of report============================================
//---build last group footers
		for ($i = 20 ;  $i >= 0 ; $i--){
			$this->buildSection($this->footerNumbers[$i], true, $lastRecord);
			$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->footerNumbers[$i]]->Height);
			//tofile("Build last stack heights: ".$this->toScale($this->report->Sections[$this->footerNumbers[$i]]->Height));
		}
		$this->buildSection($this->reportFooter, false, $lastRecord);
		//tofile("ReportFooter ".$this->reportFooter);
		//tofile("Last Stack height: ".$this->stackheight);
		$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->reportFooter]->Height);
		//tofile("Report Footer: ".$this->stackheight);
		$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength;
		//tofile("This Page Length: ".$this->thisPageLength);
		//tofile("Filler : ".$this->stackheight);
		//tofile("Space to End of Page: ".$spaceToEndOfPage." ".$this->toScale($spaceToEndOfPage));
		
		$pageFooterHeight		    = $this->toScale($this->report->Sections[$this->pageFooter]->Height);
		$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength - iif($this->hasExplicitPageBreak==true,$pageFooterHeight,0);		
		//tofile("Page Lengths Final: ".$this->useablePageLength." ".$this->thisPageLength);
		//tofile("Space To End: ".$spaceToEndOfPage);
		$this->stackheight = $this->stackheight + $spaceToEndOfPage;
		//tofile("PageFooter ".$this->stackheight);
 		$s                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n   </div>\n\n";
		$this->addReportSection(false, $spaceToEndOfPage, $s, '-1', 'filler');
		//tofile("Stack Height 2: ".$this->stackheight);
		$this->buildSection($this->pageFooter, false, $lastRecord, true);
		//tofile("PageFooter ".$this->pageFooter);
		$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->pageFooter]->Height);
		$this->stackheight = 0;
	}
    

	public function buildSection($sectionNumber, $canGrow, $tableRecord, $lastSection = false){
		//tofile('buildSection: '.$sectionNumber);
 		//$this->growBy                       = 0;
 		//$sectionControlsHTML                = $this->buildControls($controlSectionNumber, $canGrow, $tableRecord);
 		//$height                             = $this->canGrowBy + $this->toScale($this->report->Sections[$sectionNumber]->Height);
 		$height                               = $this->toScale($this->report->Sections[$sectionNumber]->Height);

		/*if ($sectionNumber == 0) {
			tofile("Grow By Height: ".$this->canGrowBy);
		}*/

//--------------------------check if page footer is needed--------------------------------
		if($sectionNumber != $this->pageFooter and $sectionNumber != $this->pageHeader){
			//tofile($this->thisPageLength." ".$height." : ".($this->thisPageLength + $height)." ".$this->useablePageLength);
	 		if($this->thisPageLength + $height >= floor($this->useablePageLength)){

				//$pageFooterHeight = $this->toScale($this->report->Sections[$this->pageFooter]->Height);
	 			//$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength-$pageFooterHeight;
	 			$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength;

	 			$this->stackheight = $this->stackheight + $spaceToEndOfPage;
	 			//tofile("PageFooter ".$this->stackheight);
		 		$s                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n";
		 		$s                          = $s . "   </div>\n\n";
				$this->addReportSection(false, $height, $s, $sectionNumber, 'filler');
		       	$this->setCurrentLength($height);
				$this->buildSection($this->pageFooter, false, $tableRecord);
				//tofile("PageFooter ".$this->pageFooter);
				$this->stackheight = 0;
				//tofile("Add Page");
				$this->AddPage();
     			//tofile("0 Total Pages: ".$this->pageNumber);
    			$this->pageNumber = ($this->pageNumber) + 1;

    			//tofile("Run Thru Total Pages ".$GLOBALS['CountTotalPages']);    				
    			if ($GLOBALS['CountTotalPages'] == 0) {
    				$GLOBALS['TotalPages'] = $this->pageNumber;
    			}
    			
				//$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->pageFooter]->Height);
				//$this->stackheight = 0;
				$this->thisPageLength       = 0;
				$this->buildSection($this->pageHeader, false, $tableRecord);
				$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->pageHeader]->Height);
			}
		}


		$controlSectionNumber               = $sectionNumber;
		$sectionNumber                      = $this->arrayNoForSectionNo($sectionNumber);
		if($this->report->Sections[$sectionNumber]->Name == ''){return;}
 		$n                                  = $this->report->Sections[$sectionNumber]->Name;
 		$this->growBy                       = 0;
 		
 		//tofile("If Count Total Pages ".$GLOBALS['CountTotalPages']);
 		//if ($GLOBALS['CountTotalPages'] == 1) {
			//tofile("Build Section If Count Total Pages ".$GLOBALS['CountTotalPages']);
 			$sectionControlsHTML                = $this->buildControls($controlSectionNumber, $canGrow, $tableRecord);
		//}
 		$height                             = $this->growBy + $this->toScale($this->report->Sections[$sectionNumber]->Height);
 		$top                                = $this->thisPageLength;
 		
 		//if ($sectionNumber == 0) {
 			//$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[0]->Height);
 		//}
 		
 		

//--------------------------end of check--------------------------------------------------
 		$top                                = $this->thisPageLength;
		if($height == 0){$height='';}
 		$s                                  = "   <div class='$n' style='position:relative;height:$height'>\n";
       	$this->setCurrentLength($height);
 		$s                                  = $s . $sectionControlsHTML;
 		$s                                  = $s . "   </div>\n\n";
 		if($sectionNumber == $this->pageFooter){//--add page break
 			
	 		$s                              = $s . "</div>\n<!-- end of page -->\n\n";//--end page div
	 		if(!$lastSection){
		 		$s                              = $s . "<div class='PageBreak' style='position:relative;'>.</div>\n\n";
	 			$s                              = $s . "\n\n<!-- start of page -->\n<div style='height:$this->pageLength'>\n";//--end page div
	 		}
 		}
		
		$this->addReportSection($sectionNumber == $this->pageFooter, $height, $s, $sectionNumber, $this->sectionType($sectionNumber));
		//tofile("Page footer stack height: ".$this->stackheight);

		if($this->justDidPageBreak == true) {

			if ($this->rowCount < $this->totalRows) {

				//echo "Ended Group footer and force page break<br>";
				$this->justDidPageBreak = false;

				for ($i = 20 ;  $i >= 0 ; $i--){
					$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->footerNumbers[$i]]->Height);
				}

				$pageFooterHeight = $this->toScale($this->report->Sections[$this->pageFooter]->Height);
	 			$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength-$pageFooterHeight;
				//tofile("Page Lengths: ".$this->useablePageLength." ".$this->thisPageLength);
				//tofile("Space To End: ".$spaceToEndOfPage);	
	
	 			$this->stackheight = $this->stackheight + $spaceToEndOfPage;
	 			//echo $this->useablePageLength." ".$this->thisPageLength." ".$pageFooterHeight."<br>";
	 			if($this->reportFooter != $sectionNumber){
			 		$ss                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n";
			 		$ss                          = $ss . "   </div>\n\n";
			 		
	 			}
	 			
				$this->addReportSection(false, $height, $ss, $sectionNumber, 'filler');
	 			
		       	$this->setCurrentLength($height);
				//tofile("PageFooter ".$this->stackheight);		       	
				$this->buildSection($this->pageFooter, false, $tableRecord);
				$this->stackheight = 0;			
				$this->AddPage();
				
    			$this->pageNumber = ($this->pageNumber) + 1;
    			
    			if ($GLOBALS['CountTotalPages'] == 0) {
    				$GLOBALS['TotalPages'] = $this->pageNumber;

    			}

				$this->thisPageLength       = 0;
				//tofile("Page Top Stack height: ".$this->stackheight);
				$this->buildSection($this->pageHeader, false, $this->nextRecord);
				$this->stackheight	= $this->stackheight + $this->toScale($this->report->Sections[$this->pageHeader]->Height);
				//tofile("Stack height after header: ".$this->stackheight);		
			}
		}		
		
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
/*
    public function getSelectionFormVariables($pID){
    	
    	$v                                  = array();
    	$t                                  = nuRunQuery("SELECT count(*) AS thecount, sva_name, sva_value FROM zzsys_variable WHERE sva_id = '$pID' GROUP BY sva_name");
    	while($r                            = db_fetch_object($t)){
    		if($r->thecount                 == 1){
    			$v[$r->sva_name]            = $r->sva_value;
    		}else{
		    	$T                          = nuRunQuery("SELECT * FROM zzsys_variable WHERE sva_id = '$pID' AND sva_name = '$R->sva_name'");
    			$tableName                  = TT();
    			$v[$R->sva_name]            = $tableName;
    			nuRunQuery("CREATE TABLE $tableName (id VARCHAR(15) NOT NULL, $tableName VARCHAR(15) NULL ,PRIMARY KEY (id), INDEX ($tableName))");
    			$this->tablesUsed[]         = $tableName;
		    	while($R                    = db_fetch_object($T)){
		    		$id                     = uniqid('1');
		    		nuRunQuery("INSERT INTO $tableName (id, $tableName) VALUES ('$id', '$R->sva_value')");
		    	}
    		}
    	}
    	return $v;
    }
*/
    public function addSelectionFormVariablesToTable($v){
    	
        $t1                                 = nuRunQuery("SELECT * FROM $this->TT");
        $fieldArray                         = tableFieldNamesToArray($t1);
    	$s                                  = "SELECT NOW() as timestamp ";
		while(list($key, $value)            = each($v)){
//            if($key!=''){
            if($key!='' and !in_array($key,$fieldArray) and $key!='timestamp'){  //--stop duplicate field names
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
				$s                          = ' Order By ';
			}else{
				$s                          = $s . ', ';
			}
			$s                              = $s . $this->report->Groups[$i]->Field;
			$s                              = $s ." ".$this->report->Groups[$i]->SortOrder;
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
			if($this->report->Controls[$i]->ControlType=='109' or $this->report->Controls[$i]->ControlType=='Field'){
				if(substr($this->report->Controls[$i]->ControlSource, 0, 6)=='=Sum([' || substr($this->report->Controls[$i]->ControlSource, 0, 4)=='sum('){
					$SumOn                  = str_replace('=Sum([','',$this->report->Controls[$i]->ControlSource);
					$SumOn                  = str_replace('])','',$SumOn);
					
	    			$SumOn              	= str_replace('sum(','',$SumOn);
		       		$SumOn              	= str_replace(')','',$SumOn);					
					
					$SumOn                  = trim($SumOn);
					if (!in_array ($SumOn, $FieldList)) {
						$s                  = $s . ", Sum($SumOn) AS $SumOn"."_Sum";
						$FieldList[]        = $SumOn;
					}
				}
				if(strtoupper(substr($this->report->Controls[$i]->ControlSource, 0, 8))=='PERCENT('){
					$SumOn                  = substr(trim($this->report->Controls[$i]->ControlSource),8,-1);
					$SumOn                  = trim($SumOn);
       				$sumFields          = array();
       				$sumFields              = explode(',',$SumOn);
					if (!in_array ($sumFields[0], $FieldList)) {
						$s                  = $s . ", Sum($sumFields[0]) AS $sumFields[0]"."_Sum";
						$FieldList[]        = $sumFields[0];
					}
					if (!in_array ($sumFields[1], $FieldList)) {
						$s                  = $s . ", Sum($sumFields[1]) AS $sumFields[1]"."_Sum";
						$FieldList[]        = $sumFields[1];
					}
				}			
				
			}
		}
	
		if(count($this->report->Groups) > 0){
			nuRunQuery("CREATE TABLE $this->sumTT $s FROM $this->TT $g");
			//tofile("CREATE TABLE $this->sumTT $s FROM $this->TT $g");
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

		//tofile("Build Controls");
		$s                                  = '';
	    $setup                              = nuSetup();
		$dq                                 = '"';
		//tofile("Count Controls ".count($this->report->Controls));
		for($i = 0 ; $i < count($this->report->Controls) ; $i++){
			if($this->report->Controls[$i]->Section == $sectionNumber){
				//tofile("Get Control");
				
				$Name                       = $this->report->Controls[$i]->Name;
				$Source                     = $this->report->Controls[$i]->ControlSource;
				$Controltype                = $this->report->Controls[$i]->ControlType;
				//tofile("Control Type: ".$this->report->Controls[$i]->ControlType);
				$Top                        = $this->toScale($this->report->Controls[$i]->Top);
				$Width                      = $this->toScale($this->report->Controls[$i]->Width);
				$Height                     = $this->toScale($this->report->Controls[$i]->Height);
				$Left                       = $this->toScale($this->report->Controls[$i]->Left);
				$Section                    = $this->report->Controls[$i]->Section;
				
				$Fontname                   = $this->report->Controls[$i]->FontName;
				$Fontsize                   = $this->report->Controls[$i]->FontSize;			
				
				if (intval($Fontsize)>=12) {
					$Fontsize = $Fontsize - 4;
				} else if (intval($Fontsize)==11) {
					$Fontsize = $Fontsize - 3;					
				} else if (intval($Fontsize)<=10) {
					$Fontsize = $Fontsize - 2;					
				}
				
				$Fontweight                 = $this->report->Controls[$i]->FontWeight;			
    			$Fontname 					= iif($Fontname == null, 'Arial', $Fontname);
    			$Fontname 					= iif($Fontname != 'Arial', 'Arial', $Fontname);
    			$Fontweight 				= iif($Fontweight == 'bold', 'B', '');				

    			$Caption					= $this->report->Controls[$i]->Caption;
    			$ln 						= iif($Caption == '', 2 , 0);

				$TextAlign 					= $this->report->Controls[$i]->TextAlign;
    			$TextAlign 					= $this->GetTextAlign($TextAlign);

				$ForeColor					= $this->report->Controls[$i]->ForeColor;
				$BackColor 					= $this->report->Controls[$i]->BackColor;
				$BackStyle 					= $this->report->Controls[$i]->BackStyle;
    			$BorderWidth 				= $this->report->Controls[$i]->BorderWidth;
    			$BorderColor 				= $this->report->Controls[$i]->BorderColor;
				
				$Format                     = $this->report->Controls[$i]->Format;
				$Decimal                    = $this->report->Controls[$i]->DecimalPlaces;
				$IsHyperlink                = $this->report->Controls[$i]->IsHyperlink;
				$ReportTag                  = $this->report->Controls[$i]->Tag;
				$LikeClause                 = $this->report->Controls[$i]->SmartTags;
				$LikeClause                 = str_replace("\"","",$LikeClause);

				//$ForeColor					= $this->html2rgb('#'.$ForeColor);
				//$BackColor 					= $this->html2rgb('#'.$BackColor);
				//$BorderColor 				= $this->html2rgb('#'.$BorderColor);

				/*if ($BackColor == '' || $BackColor == strtolower('#FFFFFF') || $BackColor == strtolower('FFFFFF')) {
					$BackStyle = 0;
				} else {
					$BackStyle = 1;
				}*/

				$ForeColor					= iif(substr($ForeColor,0,1)=='#',$ForeColor,'#'.$ForeColor);
				$BackColor					= iif(substr($BackColor,0,1)=='#',$BackColor,'#'.$BackColor);
				$BorderColor				= iif(substr($BorderColor,0,1)=='#',$BorderColor,'#'.$BorderColor);

				$ForeColor					= $this->html2rgb($ForeColor);
				$BackColor 					= $this->html2rgb($BackColor);
				$BorderColor 				= $this->html2rgb($BorderColor);
				
				$this->SetTextColor($ForeColor[0],$ForeColor[1],$ForeColor[2]);
				$this->SetFillColor($BackColor[0],$BackColor[1],$BackColor[2]);
				$this->SetDrawColor($BorderColor[0],$BorderColor[1],$BorderColor[2]);

				if ($BorderWidth > 0) {
					$this->SetLineWidth($BorderWidth/100);
					$BorderWidth = 1;
				}
				//$BorderWidth = 1;


    			//$Top = (($this->stackheight + $Top)/100) + 0.3;
    			$Left = ($Left / 100) + 0.2;
    			//$Width = ($Width / 100) + 0.002;
    			$Width = ($Width / 100);
    			$Height = $Height / 100;
    			
				$Top = (($this->stackheight + ($Top*1.0))/100) + 0.25;
				
					
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'page break'){
					$this->justDidPageBreak = true;
					$this->hasExplicitPageBreak = true;
					continue;
				}
				
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'image'){
                    $thetag             = $this->report->Controls[$i]->ControlSource;
                    $imageCode          = $this->report->Controls[$i]->Graph;  //-- the code (sim_code in zzsys_image)
                    $thedir             = $_GET['dir'];
                    
                    for($a=0;$a<count($this->fields);$a++){
                        //-----replace any strings, with hashes around them, in the querystring that match
                        //-----fieldnames in the table
                        //-----with values from the table
                        //-----e.g. id=#ID# could become id=007
                        $thetag = str_replace('#'.$this->fields[$a].'#', $record[$this->fields[$a]], $thetag);
                    }
                    $thetag= str_replace(" ","%20",$thetag); // Escapes any spaces in the parameters/arguments                    
                    //tofile("Graph Tag: ".$thetag);                    
		    		$imageT             = nuRunQuery("SELECT zzsys_image_id FROM zzsys_image WHERE sim_code = '$imageCode'");
                    $imageR             = db_fetch_row($imageT);
                    $s                  = $s . "   <div  style='position:absolute;left:$Left;top:$Top;height:$Height'>\n";
					$s                  = $s . "      <img src='formimage.php?dir=$thedir&iid=$imageR[0]&$thetag'>\n";
                    $s                  = $s . "   </div>\n";

					//tofile("http://dev.nubuilder.com/productionnu2/formimage.php?dir=$thedir&iid=$imageR[0]&$thetag");
					$logo = fopen("http://dev.nubuilder.com/productionnu2/formimage.php?dir=".$thedir."&iid=".$imageR[0]."&".$thetag,"r");

					file_put_contents('temp'.$this->imagecount.'.png',$logo);

					if (filesize('temp'.$this->imagecount.'.png') > 0) {					
					$im = imagecreatefrompng('temp'.$this->imagecount.'.png');
					imageinterlace($im,0);
					imagepng($im,'temp'.$this->imagecount.'.png');					

					
					$imageDimensions = getimagesize('temp'.$this->imagecount.'.png');
					$imageWidth = $imageDimensions[0] * 0.01;
					$imageHeight = $imageDimensions[1] * 0.01;
                    $this->Image('temp'.$this->imagecount.'.png',$Left,$Top,$imageWidth,$imageHeight);
					}
                    unlink('temp'.$this->imagecount.'.png');
                    
                   	$this->imagecount++;
                    
                    $this->Rect($Left,$Top,$imageWidth,$imageHeight, 'D'); 

                    
                    continue;
				}				
				
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'graph' || $this->report->Controls[$i]->ControlType == 'Graph'){
					if ($this->controlType($this->report->Controls[$i]->ControlType) == 'graph') {
                    	$thetag             = $this->report->Controls[$i]->ControlSource;
                    	$thename            = $this->report->Controls[$i]->Graph;
						tofile("lowercase graph: ".$thetag);
					} else if ($this->report->Controls[$i]->ControlType == 'Graph') {
                    	$thetag             = $this->report->Controls[$i]->ControlSource;
                    	$thename            = $this->report->Controls[$i]->Graph;	
						tofile("uppercase graph: ".$thetag);                    						
					}
                    $thedir             = $_GET['dir'];
                    
                    for($a=0;$a<count($this->fields);$a++){
                        //-----replace any strings, with hashes around them, in the querystring that match
                        //-----fieldnames in the table
                        //-----with values from the table
                        //-----e.g. id=#ID# could become id=007
                        $thetag = str_replace('#'.$this->fields[$a].'#', $record[$this->fields[$a]], $thetag);
                    }
                    
		    		$addSession         = '&ses='. $_GET['ses'];                    
                    
                    $thetag= str_replace(" ","%20",$thetag); // Escapes any spaces in the parameters/arguments
                    //tofile("Graph Tag: ".$thetag);
                    									
					$logo = fopen("http://www.nubuilder.com/productionnu2/graph_report.php?dir=$thedir$addSession&activityID=$this->reportID&graph_name=$thename&$thetag","r");

					file_put_contents('temp'.$this->imagecount.'.png',$logo);
					$imageDimensions = getimagesize('temp'.$this->imagecount.'.png');
					$imageWidth = $imageDimensions[0] * 0.01;
					$imageHeight = $imageDimensions[1] * 0.01;
                    $this->Image('temp'.$this->imagecount.'.png',$Left,$Top,$imageWidth,$imageHeight);
                    unlink('temp'.$this->imagecount.'.png');
                   	$this->imagecount++;
                    
                    $this->Rect($Left,$Top,$imageWidth,$imageHeight, 'D');                 
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'label' || $this->report->Controls[$i]->ControlType == 'Label'){

                	if (substr($this->report->Controls[$i]->ControlSource, 0 ,4) == '<img') {
                		//tofile("Test Caption ".$this->GetImageLink($ReportClass->Controls[$i]->Caption));
                		//tofile("Show Header fields ".$Name." ".$Source." ".$Caption." ".$Fontname." P".$PageNumber." ".$Top);
                		$this->SetXY($Left,$Top);
                		$output = $this->parse($this->report->Controls[$i]->ControlSource);
                		$image_properties = $this->GetImageSourceAndDimensions($output);
                		if (substr($image_properties['source'],-4,4) == '.jpg' || substr($image_properties['source'],-4,4) == '.png') {
                			$this->Image($image_properties['source'],$Left,$Top,$image_properties['width'],$image_properties['height']);
                		}
                		continue;
					}

	                if($this->report->Controls[$i]->Tag!=$this->report->Controls[$i]->ControlType){
						//tofile("Check Tag ".$this->report->Controls[$i]->Tag);
	    				//$s                  = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'><img src='$setup->seStartingDirectory/reports/".$this->report->Controls[$i]->Tag.".jpg'></div>\n";
                		$this->SetXY($Left,$Top);			
                		//$this->Image($setup->seStartingDirectory."/reports/".$ReportClass->Controls[$i]->Tag.".jpg",$Left,$Top,$Width,$Height);
                		if ($ReportClass->Controls[$i]->Caption!='') {
                			$this->SetFont($Fontname,$Fontweight,$Fontsize);
    						$this->SetXY($Left, $Top);
							$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign);
							//tofile("Caption: ".$Caption);
                		}                	
                		continue;	                
	                
	                }


					if($Source=='="Page " & [Page] & " of " & [Pages]' || $Source=='Page #thePageNumber# of #totalNumberOfPages#'){
							$Caption = "Page ".$this->pageNumber." of ".$GLOBALS['TotalPages'];
    						$this->SetFont($Fontname,$Fontweight,$Fontsize);
    						$this->SetXY($Left, $Top);
    						$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackStyle);
							$s                      = $s . "   <div class='$Name'>Page #thePageNumber# of #totalNumberOfPages#</div>\n";
							continue;
					}else if($Source=='=Date()' || $Source=='=Now()'){
							
							if ($Format == '' || $Format == '20' || $Format == 'Long Date') {
								$formattedValue = date('d-M-Y H:i');
							} else {
								$formattedValue     = date($this->accessDateFormatToPHPDateFormat($Format));
							}
							
    						$Caption = $formattedValue;
    						$this->SetFont($Fontname,$Fontweight,$Fontsize);
    						$this->SetXY($Left, $Top);
    						$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackStyle);
							$s                  = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
							continue;
					}
	                
					$s                      = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>".$this->report->Controls[$i]->Caption."</div>\n";
                	if (strlen($Caption) == 1 && $BorderWidth >= 1) {
 								
						$this->SetDrawColor($BorderColor[0],$BorderColor[1],$BorderColor[2]);
						$this->SetFillColor($BorderColor[0],$BorderColor[1],$BorderColor[2]);
                		$BackStyle = 1;
                	}



					$this->SetFont($Fontname,$Fontweight,$Fontsize);
					//tofile("Label: ".$this->report->Controls[$i]->ControlSource);
    				$output = $this->parse($this->report->Controls[$i]->Caption);
    				if ($output[0]['innerhtml'] == null) {
    					//tofile("Label used: ".$this->report->Controls[$i]->Caption);
    					$break_variants = array("<br />","<BR />","<br/>","<BR/>");
    					$string = str_replace($break_variants,"<br>",$this->report->Controls[$i]->Caption);
    					$lines = explode("<br>",$string);
    									
    					if (sizeof($lines) > 1) {
    						$string = "";
    						for ($j = 0; $j < sizeof($lines);$j++) {
    							$lines[$j] = trim($lines[$j]);
    							$string = $string.$lines[$j]."\n";
    						}
    					}
    									
    					if (sizeof($lines) > 1) {
    							//tofile("more than 1 line");
    							$Height = $this->CalculateLineHeight($Fontsize);
    							$this->SplitLines($Width,$Height,$string,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top,0,$i);
    					} else {
    							//tofile("1 line only");
    							$this->SetXY($Left, $Top);
    							$this->Cell($Width,$Height,$this->report->Controls[$i]->Caption,$BorderWidth,$ln,$TextAlign,$BackStyle);
    					}
    				} else {
    						$this->SetXY($Left, $Top);
    						$this->SetHTMLAttributes($Width,$Height,$output,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top);
    				}
    									//$this->Cell($Width,$Height,$r[$ReportClass->Controls[$i]->ControlSource],$BorderWidth,$ln,$TextAlign,$BackColor);	
    									
    				//$this->SetFont($Fontname,$Fontweight,$Fontsize);
    				//$this->SetXY($Left, $Top);
    				//$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackStyle); 						
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'text area' || $this->report->Controls[$i]->ControlType == 'Field'){
					
					$formattedValue = $record[$this->report->Controls[$i]->ControlSource];
					
					$formattedValue = str_replace("&nbsp;"," ",$formattedValue);
					$formattedValue = str_replace("&nbsp"," ",$formattedValue);
					
	                if($this->report->Controls[$i]->Tag!=''){
	    				$s                  = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'><img src='$setup->seStartingDirectory/reports/".$this->report->Controls[$i]->Tag.".jpg'></div>\n";
	                }
	                
					//Sum
					$theSource                  = $this->report->Controls[$i]->ControlSource;
					//tofile("Source: ".strtoupper(substr($theSource, 0, 8)));
					if(substr($theSource, 0, 6)=='=Sum([' OR strtoupper(substr($theSource, 0, 10))=='=PERCENT([' OR substr($theSource, 0, 4)=='sum(' OR strtoupper(substr($theSource, 0, 8))=='PERCENT('){
	                    $sumIt=(substr($theSource, 0, 6)=='=Sum([' || substr($theSource, 0, 4)=='sum(');
	                   	//tofile(substr($theSource, 0, 10)." flag ".$sumIt);
	                    if($sumIt){  //--if the sum function
	    					$SumOn              = str_replace('=Sum([','',$theSource);
		       				$SumOn              = str_replace('])','',$SumOn);

	    					$SumOn              = str_replace('sum(','',$SumOn);
		       				$SumOn              = str_replace(')','',$SumOn);
	
		       				$selectFields       = 'sum('.$SumOn.'_Sum) as answer ';
	                    }else{        //--if the Percent function
	    					$SumOn              = substr($theSource,8);
	    					//tofile("Sum On: ".$SumOn);
		       				$SumOn              = str_replace(']','',$SumOn); //-- remove right square brackets
		       				$SumOn              = str_replace('[','',$SumOn); //-- remove left square brackets
		       				$SumOn              = str_replace(')','',$SumOn); //-- remove only right bracket
		       				$SumOn              = str_replace(' ','',$SumOn); //-- remove any spaces
		       				$theFields          = explode(',', $SumOn);   //-- split left and right field
		       				$selectFields       = 'sum('.$theFields[0].'_Sum) as divideIt, sum('.$theFields[1].'_Sum) as BYY ';
							//tofile("Select Fields: ".$selectFields);
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
						
						//tofile("Sum Value: ".$formattedValue);
						
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

						$formattedValue  = formatTextValue($formattedValue, $Format);

    					$Caption = $formattedValue;
    					$this->SetFont($Fontname,$Fontweight,$Fontsize);
    					$this->SetXY($Left, $Top);
    					$this->Cell($Width,$Height,$this->SetStringByWidth($Caption,$Width-0.1),$BorderWidth,$ln,$TextAlign,$BackStyle);

	
						$s                       = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
					}else{
						//page display
						if($Source=='="Page " & [Page] & " of " & [Pages]' || $Source=='Page #thePageNumber# of #totalNumberOfPages#'){
							$Caption = "Page ".$this->pageNumber." of ".$GLOBALS['TotalPages'];
    						$this->SetFont($Fontname,$Fontweight,$Fontsize);
    						$this->SetXY($Left, $Top);
    						$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackStyle);
							$s                      = $s . "   <div class='$Name'>Page #thePageNumber# of #totalNumberOfPages#</div>\n";
						}else{
							//date display
							if($Source=='=Date()' OR $Source=='=Now()'){

								if ($Format == '' || $Format == '20' || $Format == 'Long Date') {
									$formattedValue = date('d-M-Y H:i');
								} else {
									$formattedValue     = date($this->accessDateFormatToPHPDateFormat($Format));
								}								
								
								//$formattedValue     = date($this->accessDateFormatToPHPDateFormat($Format));
    							$Caption = $formattedValue;
    							
    							$this->SetFont($Fontname,$Fontweight,$Fontsize);
    							$this->SetXY($Left, $Top);
    							$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackStyle);
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
								
								$formattedValue  = formatTextValue($formattedValue, $Format);
								//tofile($formattedValue);							
								if($this->report->Controls[$i]->CanGrow == 'True' and $canGrow){

									//$oldValue               = $formattedValue;
									
									$lines = $this->SplitAndWordWrapText($formattedValue,$Width);
    								if (sizeof($lines) > 1) {
    									$string = "";
    									for ($j = 0; $j < sizeof($lines);$j++) {
    										$lines[$j] = trim($lines[$j]);
    										$string = $string.$lines[$j]."<br>";
    									}
    								}
    								
    								
    								$this->SetFont($Fontname,$Fontweight,$Fontsize);	
    								if (sizeof($lines) > 1) {
    									//tofile("more than 1 line");
    									$Height = $this->CalculateLineHeight($Fontsize);
    									$this->SplitLines($Width,$Height,$string,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top,sizeof($lines),$i);
    								} else {
    									//tofile("1 line only");
    									$this->SetXY($Left, $Top);
    									$this->Cell($Width,$Height,$lines[0],$BorderWidth,$ln,$TextAlign,$BackStyle);
    								}					
									
									/*$formattedValue         = wordwrap($formattedValue, iif(is_numeric($ReportTag) == false,10,$ReportTag), "<br />"); 
									$formattedValue         = nl2br($formattedValue);

									$lines                  = substr_count($formattedValue, "<br />")+1;
									$defaultHeight          = $Height;
									//$Height                 = $Height * iif($lines == 0, 1, $lines);
									if($this->growBy < $Height - $defaultHeight){
										$this->growBy       = $Height - $defaultHeight;
									}
    								$this->SetFont($Fontname,$Fontweight,$Fontsize);
    								$this->SetXY($Left, $Top);

    								$this->Cell($Width,$Height,$this->SetStringByWidth($formattedValue,$Width-0.1),$BorderWidth,$ln,$TextAlign,$BackStyle);*/									

								}else{
    								$Caption = $formattedValue;

									$output = $this->parse($Caption);
									
    								//tofile("Test Caption: ".$Caption);
    								$this->SetFont($Fontname,$Fontweight,$Fontsize);
    								$this->SetXY($Left, $Top);

									if ($BackColor == '' || $BackColor == strtolower('#FFFFFF') || $BackColor == strtolower('FFFFFF')) {
										$BackStyle = 0;
									} else {
										$BackStyle = 1;
									}

									if ($output[0]['innerhtml'] == null) {    								
    									//tofile($this->GetStringWidth($Caption)." ".$Width." ".$Caption);
    									$this->Cell($Width,$Height,$this->SetStringByWidth($Caption,$Width-0.1),$BorderWidth,$ln,$TextAlign,$BackStyle);
									} else {
									
										$this->SetHTMLAttributes($Width,$Height,$output,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top);
									}
									//tofile($BorderColor[0]." ".$BorderColor[1]." ".$BorderColor[2]);    								
    								//tofile("Border Width: ".$BorderWidth);									
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
		if( $pControlNumber == 'Image'){return 'image';}
	}
	
	public function toScale($pSize){
		//tofile("Before Resize: ".iif($pSize == '',0,$pSize)." After Resize: ".iif($pSize == '',0,$pSize) * $this->resize);
		$size = 0;
		if ($pSize == '') {
			$size = 0;
		} else {
			if (substr($pSize,-2,2) == 'px') {
				$size = intval($pSize);
			} else {
				$size = intval($pSize) * $this->resize;
			}
		}
		//return iif($pSize == '',0,$pSize) * $this->resize;
		return $size;
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
		   if($FontName                     == 'Arial'){
		       $FontSize                    = $this->report->Controls[$i]->FontSize*1.3;
		   }else{
		       $FontSize                    = $this->report->Controls[$i]->FontSize*1.5;
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
    
    function GetTextAlign($align) {
	
		if ($align == '0' || $align == '1') {
			return 'L';
		} else if ($align == '2') {
			return 'C';
		} else if ($align == '3') {
			return 'R';
		}
		
		if ($align == 'left') {
			return 'L';
		} else if ($align == 'center') {
			return 'C';
		} else if ($align == 'right') {
			return 'R';
		}		
	
	}

	function html2rgb($color){    
		
		if ($color[0] == '#')
		   $color = substr($color, 1);
		
		if ($color == 'black') {
			$r = 0; $g = 0; $b = 0;
			return array($r, $g, $b);
		}
		
		if ($color == 'white') {
			$r = 255; $g = 255; $b = 255;
			return array($r, $g, $b);			
		}
		   
		if (strlen($color) == 6)
		    list($r, $g, $b) = array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);
	 	elseif (strlen($color) == 3)
		   	list($r, $g, $b) = array($color[0], $color[1], $color[2]);
		else
		   	return false;
		
		$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
		return array($r, $g, $b);
	}
	
	function GetImageLink($html) {
		$link = strtok($html,'"');
		$link = strtok('/');
		$link = strtok('"');
	
		return $link;
	}	

	function GetFillColor($color) {
	
		if ($color == '000000') {
			return 1;
		} else {
			return 0;
		}
	
	}


	function SetHTMLAttributes($Width,$Height,$output,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top) {

		$linebreak = $ln;
	
		for ($i = 0; $i < sizeof($output); $i++) {
		
			if ($output[$i]['name'] == 'FONT') {	
				if ($output[$i]['attr']['COLOR'] == 'red') {
					$this->SetTextColor(255,0,0);
				}
				if ($output[$i]['attr']['COLOR'] == 'darkorange') {
					$this->SetTextColor(255,140,0);				
				}
			}
		
			if ($output[$i]['name'] == 'B') {
				$this->SetFont('Arial','B');
			}
		
			if ($output[$i]['name'] == 'I') {
				$this->SetFont('Arial','I');
			}		
				
			$Caption = $output[$i]['tagData'];
		
			if ($output[$i]['name'] == 'BR') {	
				if($i == 0) {
					$Top = $Top - ($Height / 3);
				} else {
					$Top = $Top + 0.15;				
				}
				$this->SetXY($Left,$Top);
			}
		
			$this->SetFillColor($BackColor[0],$BackColor[1],$BackColor[2]);
			//$this->Cell($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackStyle);
			tofile($this->SetStringByWidth($Caption,$Width-0.1));
			$this->Cell($Width,$Height,$this->SetStringByWidth($Caption,$Width-0.1),$BorderWidth,$ln,$TextAlign,$BackStyle);
			//if ($output[$i]['name'] == 'BR') {

				//tofile('Line break: '.$Caption);
				//$linebreak = 1;
				//$this->Ln();
			//}			
		}
		$this->SetTextColor(0,0,0);
	}

	function SplitLines($Width,$Height,$Caption,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top,$CanGrowLines,$ControlsCount) {
		//$Caption = "<br>".$Caption;
		//$output = $this->parse($Caption);
		//$this->SetHTMLAttributes($Width,$Height,$output,$BorderWidth,$ln,$TextAlign,$BackColor,$BackStyle,$Left,$Top);
		//tofile($Caption);
		$Caption = str_replace("<br>","\n",$Caption);
		$this->SetXY($Left,$Top);
		$this->MultiCell($Width+0.1,$Height,$Caption,$BorderWidth,$TextAlign,$BackStyle);
		tofile("CanGrowLines: ".$CanGrowLines);
		if ($CanGrowLines > 0) {
			//$this->canGrowBy = (($Height/$this->resize) * $CanGrowLines) + ($this->toScale($this->report->Sections[0]->Height) - ($Height/$this->resize));
			$this->canGrowBy = (($Height/$this->resize) * $CanGrowLines) + ($this->toScale($this->report->Sections[0]->Height) - ($this->toScale($this->report->Controls[$ControlsCount]->Top)+$this->toScale($this->report->Controls[$ControlsCount]->Height)));
			$this->setCurrentLength($this->canGrowBy);
		}
	}

	function SplitAndWordWrapText($text, $Width) {
		
		$str_array = array();
		$str = $text;
		$count = 0; 
		while (strlen($str) > 0) {
			$tempstr = $this->SetStringByWidth($str,$Width-0.1);
			$str = str_replace($tempstr,'',$str);
			$str_array[$count] = $tempstr;
			$count++;
		}
		
		return $str_array;
	}

	function GetImageSourceAndDimensions($output) {
	
		//$Tag = $output[0]['name'];
		$properties = array();
		$properties['source'] = $output[0]['attr']['SRC'];
		$properties['width'] = $output[0]['attr']['WIDTH'] * 0.01;
		$properties['height'] = $output[0]['attr']['HEIGHT'] * 0.01;	
	
		return $properties;
	}

    function get_html () { 
        return $this->_html; 
    } 

    function parse($strInputXML) { 
        $this->output = array(); 

        // Translate entities 
        $strInputXML = $this->translate_entities($strInputXML); 

        $this->_parser = xml_parser_create (); 
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, true); 
        xml_set_object($this->_parser,$this); 
        xml_set_element_handler($this->_parser, "tagOpen", "tagClosed"); 
           
        xml_set_character_data_handler($this->_parser, "tagData"); 
       
        $this->strXmlData = xml_parse($this->_parser,$strInputXML ); 

        if (!$this->strXmlData) { 
            $this->xml_error = true; 
            $this->xml_error_code = xml_get_error_code($this->_parser); 
            $this->xml_error_string = xml_error_string(xml_get_error_code($this->_parser)); 
            $this->xml_error_line_number =  xml_get_current_line_number($this->_parser); 
            return false; 
        } 

        return $this->output; 
    } 


    function tagOpen($parser, $name, $attr) { 
        // Increase level 
        $this->_level++; 

        // Create tag: 
        $newtag = $this->create_tag($name, $attr); 

        // Build tag 
        $tag = array("name"=>$name,"attr"=>$attr, "level"=>$this->_level); 

        // Add tag 
        array_push ($this->output, $tag); 

        // Add tag to this level 
        $this->_tags[$this->_level] = $tag; 

        // Add to HTML 
        $this->_html .= $newtag; 

        // Add to outline 
        $this->_outline .= $this->_level . $newtag; 
    } 

    function create_tag ($name, $attr) { 
        // Create tag: 
        # Begin with name 
        $tag = '<' . strtolower($name) . ' '; 

        # Create attribute list 
        foreach ($attr as $key=>$val) { 
            $tag .= strtolower($key) . '="' . htmlentities($val) . '" '; 
        } 

        # Finish tag 
        $tag = trim($tag); 
         
        switch(strtolower($name)) { 
            case 'br': 
            case 'input': 
                $tag .= ' /'; 
            break; 
        } 

        $tag .= '>'; 

        return $tag; 
    } 

    function tagData($parser, $tagData) { 
        if(trim($tagData)) { 
            if(isset($this->output[count($this->output)-1]['tagData'])) { 
                $this->output[count($this->output)-1]['tagData'] .= $tagData; 
            } else { 
                $this->output[count($this->output)-1]['tagData'] = $tagData; 
            } 
        } 

        $this->_html .= htmlentities($tagData); 
        $this->_outline .= htmlentities($tagData); 
    } 
   
    function tagClosed($parser, $name) { 
        // Add to HTML and outline 
        switch (strtolower($name)) { 
            case 'br': 
            case 'input': 
                break; 
            default: 
            $this->_outline .= $this->_level . '</' . strtolower($name) . '>'; 
            $this->_html .= '</' . strtolower($name) . '>'; 
        } 

        // Get tag that belongs to this end 
        $tag = $this->_tags[$this->_level]; 
        $tag = $this->create_tag($tag['name'], $tag['attr']); 

        // Try to get innerHTML 
        $regex = '%' . preg_quote($this->_level . $tag, '%') . '(.*?)' . preg_quote($this->_level . '</' . strtolower($name) . '>', '%') . '%is'; 
        preg_match ($regex, $this->_outline, $matches); 

        // Get innerHTML 
        if (isset($matches['1'])) { 
            $innerhtml = $matches['1']; 
        } 
         
        // Remove level identifiers 
        $this->_outline = str_replace($this->_level . $tag, $tag, $this->_outline); 
        $this->_outline = str_replace($this->_level . '</' . strtolower($name) . '>', '</' . strtolower($name) . '>', $this->_outline); 

        // Add innerHTML 
        if (isset($innerhtml)) { 
            $this->output[count($this->output)-1]['innerhtml'] = $innerhtml; 
        } 

        // Fix tree 
        $this->output[count($this->output)-2]['children'][] = $this->output[count($this->output)-1]; 
        array_pop($this->output); 

        // Decrease level 
        $this->_level--; 
    } 

    function translate_entities($xmlSource, $reverse =FALSE) { 
        static $literal2NumericEntity; 
         
        if (empty($literal2NumericEntity)) { 
            $transTbl = get_html_translation_table(HTML_ENTITIES); 

            foreach ($transTbl as $char => $entity) { 
                if (strpos('&#038;"<>', $char) !== FALSE) continue; 
                    $literal2NumericEntity[$entity] = '&#'.ord($char).';'; 
                } 
            } 

            if ($reverse) { 
                return strtr($xmlSource, array_flip($literal2NumericEntity)); 
            } else { 
                return strtr($xmlSource, $literal2NumericEntity); 
            } 
      }
      
	function CalculateLineHeight($Fontsize) {
		$lineheight = (1/72) * $Fontsize;
		return $lineheight;
	}

	function ConvertSpecialSymbols($str) {
		
		/*$symbols = array('&#174'=>'®');
		for ($i = 0; $i < sizeof($symbols); $i++) {
			$replacedstr = str_replace($symbols[$i], $symbols[$i][0], $str);
		}*/
		$replacedstr = str_replace('&#174','®',$str);
		return $replacedstr;
	}
	
	function SetStringByWidth($str, $Width) {
		$newStr = '';
		$strArray = str_split($str);
		for ($i = 0; $i < sizeof($strArray); $i++) {
			$newStr = $newStr.$strArray[$i];
			if ($this->GetStringWidth($newStr) >= $Width) {
				return $newStr;
			}
		}
		
		return $newStr;
	}
	
	/*function StripPX($val) {
		//tofile("Before PX replace ".$val);
		//$val = str_replace("px","",$str);
	
		$val = intval($val);
		//tofile("After PX replace ".$val);	
		return $val;
	}*/
	
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



function run_pdf_report(){



	//session_start( );

	$parameters                      = $_GET['ses'];
	$form_ses                        = $_GET['form_ses'];
	$report                          = $_GET['r'];
	$dir                             = $_GET['dir'];

	$session = nuSession($parameters, false);
	/*if($session->foundOK == ''){
		print 'you have been logged out..';
		return;
	}*/

	$setup                    = nuSetup();
	$T                        = nuRunQuery("SELECT * FROM zzsys_activity WHERE sat_all_code = '$report'");
	$A                        = db_fetch_object($T);
	//----------allow for custom code----------------------------------------------
//--already done now..	eval($A->sat_report_display_code);
	$id                       = uniqid('1');
	$thedate                  = date('Y-m-d H:i:s');
	$dq                       = '"';

	if($A->zzsys_activity_id !=''){
		//$viewer               = $_SESSION['zzsys_user_id'];
		$viewerSQL 			  = nuRunQuery("SELECT * FROM zzsys_session WHERE zzsys_session_id = '$parameters'");
		$viewerObj 			  = db_fetch_object($viewerSQL);
		$viewer               = $viewerObj->sss_zzsys_user_id;
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



}


function MakeReport($parameters, $ACTIVITY){

	//tofile("Count Total Pages: ".$GLOBALS['TotalPages']);

	$GLOBALS['CountTotalPages'] = 0;
	$GLOBALS['TotalPages'] = 0;
	$theReport                         = new reportDisplay($parameters, $ACTIVITY);
	$theReport->SetMargins(1,1);	
	$theReport->pageLength             = $ACTIVITY->sat_report_page_length;
	$theReport->setPageLength($ACTIVITY->sat_report_page_length);
	$theReport->buildReport();
	
	//tofile("Total Pages: ".$GLOBALS['TotalPages']);
	
	$GLOBALS['CountTotalPages'] = 1;
	//tofile("Count Total Pages ".$GLOBALS['CountTotalPages']);
	$theReport                         = new reportDisplay($parameters, $ACTIVITY);
	$theReport->SetMargins(1,1);	
	$theReport->pageLength             = $ACTIVITY->sat_report_page_length;
	$theReport->setPageLength($ACTIVITY->sat_report_page_length);
	$theReport->buildReport();
	
	//print $theReport->styleSheet;
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
	$pageNo                            = $pageNo - 1;
	for($i = 0 ; $i < count($theReport->section) ; $i++){
		$theReport->section[$i]->html  = str_replace('#totalNumberOfPages#',$pageNo,$theReport->section[$i]->html);
	}
	for($i = 0 ; $i < count($theReport->section) ; $i++){
		//print $theReport->section[$i]->html;
	}
	//print "\n</body></html>";
	for($i = 0 ; $i < count($theReport->tablesUsed) ; $i++){
		nuRunQuery("DROP TABLE " . $theReport->tablesUsed[$i]);
	}
	
	$theReport->SetDisplayMode(100); 
	$theReport->Output();
tofile('goot here3');
}

?>
