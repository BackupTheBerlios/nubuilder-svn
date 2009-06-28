<?php
/*
** File:           run_report_html_v1.php
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

	$parameters                      = $_GET['ses'];
	$form_ses                        = $_GET['form_ses'];
	$report                          = $_GET['r'];
	$dir                             = $_GET['dir'];

        $emailer			= $_GET['emailer'];

	include_once("../$dir/database.php");
	include_once('common.php');

	if ($emailer != '1') {
		if(activityPasswordNeeded($report)){
			$session = nuSession($parameters, false);
			if($session->foundOK == ''){
				print 'you have been logged out..';
				return;
			}
		}
	}

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
		$viewer				  = $session->sss_zzsys_user_id;
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
	if($pageNo != 1){
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

    public  $customDirectory           = '';
    public  $session                   = '';            //---id that remains the same throughout login time
//    public  $formsessionID             = '';            //---Form Session ID (unique ID for this instance of this form)
//    public  $access_level              = '';
//    public  $zzsys_user_id             = '';
//    public  $zzsys_user_login_id       = '';
//    public  $zzsys_user_group_name     = '';

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
    public $justDidPageBreak           = false;
    public $PN                         = 1;
    public $jsFunctions                = array();
    public $next					   = null;
    public $nextRecord				   = null;
    public $totalRows				   = 0;
    public $rowCount				   = 0;
    public $hasExplicitPageBreak	   = false;
	public $sectionGrow				   = 0;
	public $sectionGrowStack		   = 0;
	public $nextPageSectionGrow		   = '';
	public $growAddHeight			   = 0;
	public $growByTotal				   = 0;
	public $growBySectionNumber		   = -1;
	public $nextPageGrowText		   = array();
	public $nextPageGrowByControls	   = array();




    function __construct($parameters, $ACTIVITY){

        $this->customDirectory         = $_GET['dir'];
        $this->session                 = $_GET['ses'];

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
		$this->TT                      = $dataTable;
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

     	$t                             = nuRunQuery("SELECT * FROM $this->TT $this->orderBy");
     	$this->next					   = nuRunQuery("SELECT * FROM $this->TT $this->orderBy");
		$this->totalRows 						= mysql_num_rows($t);
     	$lastRecord                    = array();
     	$isFirstRecord                 = true;
     	$counter                       = 0;
		$this->rowCount                = 0;

     	while($thisRecord              = db_fetch_array($t)){
     		$this->nextRecord		   = db_fetch_array($this->next);
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
						//if (($i*2)+6 == 6)
						//echo "Put tra_number_footer<br>";
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
			$this->rowCount = $this->rowCount + 1;
			$this->buildSection(0, true, $thisRecord);
     		$lastRecord                     = $thisRecord;
			$isFirstRecord                  = false;
     	}

//=======finish off footers at end of report============================================
//---build last group footers
		for ($i = 20 ;  $i >= 0 ; $i--){
			
			$this->buildSection($this->footerNumbers[$i], true, $lastRecord);
		}
		$this->buildSection($this->reportFooter, false, $lastRecord);
		//$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength;
		$pageFooterHeight		    = $this->toScale($this->report->Sections[$this->pageFooter]->Height);
		$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength - iif($this->hasExplicitPageBreak==true,$pageFooterHeight,0);
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
 		$this->growByTotal					= 0;
		$this->growAddHeight = 0; 		
 			$sectionControlsHTML                = $this->buildControls($controlSectionNumber, $canGrow, $tableRecord); 			
 		if ($this->growByTotal > 0 && $sectionNumber != 0) {
 			$height                             = $this->growByTotal;
		} else {
 			$height                             = $this->growBy + $this->toScale($this->report->Sections[$sectionNumber]->Height);
 		}
 		$top                                = $this->thisPageLength;

//--------------------------check if page footer is needed--------------------------------
		$addPageBreak						= false;
		if($sectionNumber != $this->pageFooter and $sectionNumber != $this->pageHeader){

	 		if($this->thisPageLength + $height >= floor($this->useablePageLength) && $this->sectionGrow == 0){
				//echo "<b>put footer</b><br>";
	 			$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength;
	 			if($this->reportFooter != $sectionNumber){
			 		//echo "Put filler<br>";
			 		$ss                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n";
			 		$ss                          = $ss . "   </div>\n\n";
			 		
	 			}

					$this->addReportSection(false, $height, $ss, $sectionNumber, 'filler');
	 			//} 			
		       	$this->setCurrentLength($height);
				$this->buildSection($this->pageFooter, false, $tableRecord);
				$addPageBreak				= true;
				if($GLOBALS['pageBreak']){
			 		$s                      =      "   <div class='PageBreak' style='position:relative;height:0'></div>\n";
				}
				$GLOBALS['pageBreak'] = false;
				$this->addReportSection(false, $height, $s, $sectionNumber, 'aPageBreak');
				$this->thisPageLength       = 0;
				$this->pages = $this->pages + 1;
				$this->buildSection($this->pageHeader, false, $tableRecord);				
			}

		}
//--------------------------end of check--------------------------------------------------
		$this->sectionGrow = 0;
		
 		$top                                = $this->thisPageLength;
		if($height == 0){$height='';}
		//echo $n."<br>";
		
		
 		$s                                  = "   <div class='$n' style='position:relative;height:$height;background-color:$sectionColor'>\n";

       	$this->setCurrentLength($height);
       	
 		$s                                  = $s . $sectionControlsHTML;
 		$s                                  = $s . "   </div>\n\n";

		if ($n == 'Page_Header' && $this->pages > 0) {
			//if ($this->sectionGrow == 1) {
			$sectionColor                       = $this->report->Sections[$sectionNumber]->BackColor;
			if($sectionColor=='#ebebeb'){$sectionColor='#ffffff';}
			$index                      = $this->arrayNoForSectionNo($this->growBySectionNumber);
			if($this->report->Sections[$sectionNumber]->Name == ''){return;}
 			$n                                  = $this->report->Sections[$index]->Name;

				$growByText = $this->insertRemainingGrowByFieldText();
 				if ($this->growByTotal > 0) {
 					$height                             = $this->growByTotal+20;
				} else {
 					$height                             = $this->growBy + $this->toScale($this->report->Sections[$index]->Height);
 				}

				if ($this->sectionGrow == 1) {
					$s = $s . "   <div class='$n' style='position:relative;height:$height;background-color:$sectionColor'>\n";
					$s = $s . $growByText;
					$s = $s . "</div>\n\n";
 					$this->setCurrentLength($height);
 					$this->sectionGrow = 0;
 					$this->nextPageGrowText = null;
				}

		}

 		if($sectionNumber == $this->pageFooter){//--add page break
 			//echo "<br><br>";
	 		$s                              = $s . "</div>\n<!-- end of page -->\n\n";//--end page div
	 		if(!$lastSection){
		 		$s                              = $s . "<div class='PageBreak' tag='test' style='position:relative;'>.</div>\n\n";
	 			$s                              = $s . "\n\n<!-- start of page -->\n<div style='height:$this->pageLength'>\n";//--end page div
	 			//echo "aa<br>";
	 			//echo "<br><br>Start Page<br>";
	 		}
 		}
		$this->addReportSection($sectionNumber == $this->pageFooter, $height, $s, $sectionNumber, $this->sectionType($sectionNumber));

		//echo "cc<br>";		
		if($this->justDidPageBreak == true) {

			if ($this->rowCount < $this->totalRows) {

				//echo "Ended Group footer and force page break<br>";
				$this->justDidPageBreak = false;
				$pageFooterHeight = $this->toScale($this->report->Sections[$this->pageFooter]->Height);
	 			$spaceToEndOfPage           = $this->useablePageLength - $this->thisPageLength-$pageFooterHeight;
	 			if($this->reportFooter != $sectionNumber){
			 		$ss                          =      "   <div name='filler' style='position:relative;height:$spaceToEndOfPage'>\n";
			 		$ss                          = $ss . "   </div>\n\n";
			 		
	 			}
	 			
				$this->addReportSection(false, $height, $ss, $sectionNumber, 'filler');
	 			
		       	$this->setCurrentLength($height);
		       	
				$this->buildSection($this->pageFooter, false, $tableRecord);

				$addPageBreak				= true;
				if($GLOBALS['pageBreak']){
			 		$s                      =      "   <div class='PageBreak' style='position:relative;height:0'></div>\n";
				}
				$GLOBALS['pageBreak'] = false;
				
				$this->thisPageLength       = 0;
				$this->pages = $this->pages + 1;
				$this->buildSection($this->pageHeader, false, $this->nextRecord);				
				
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

    public function addSelectionFormVariablesToTable($v){
    	
        $t1                                 = nuRunQuery("SELECT * FROM $this->TT");
        $fieldArray                         = tableFieldNamesToArray($t1);
    	$s                                  = "SELECT NOW() as timestamp ";
        $columnCount                        = 0;
		while(list($key, $value)            = each($v)){
		  $columnCount                      = $columnCount + 1;
            if($key!='' and !in_array($key,$fieldArray) and $key!=''){  //--stop duplicate field names

		//changed by SG 29/Jan/2009
            	//$value                      = str_replace('\"', '"', $value);
    	    	//$s                              = $s . ' , "' . $value . '" AS ' . $key . ' ';
		$value                      = addslashes($value);
		$s                          = "$s , '$value' AS `$key` ";		
            }
            if($columnCount>200){break;}
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
			if($this->report->Controls[$i]->ControlType=='109' or $this->report->Controls[$i]->ControlType=='Field'){
				if(strtoupper(substr($this->report->Controls[$i]->ControlSource, 0, 4))=='SUM('){
					$SumOn                  = substr(trim($this->report->Controls[$i]->ControlSource),4,-1);
					$SumOn                  = trim($SumOn);
					if (!in_array ($SumOn, $FieldList)) {
						$s                  = $s . ", Sum($SumOn) AS $SumOn"."_Sum";
						$FieldList[]        = $SumOn;
					}
				}
				if(strtoupper(substr($this->report->Controls[$i]->ControlSource, 0, 6))=='=SUM(['){
					$SumOn                  = substr(trim($this->report->Controls[$i]->ControlSource),6,-2);
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
					//echo "Percent ".$sumFields[0]." ".$sumFields[1];
				}

			}







		}
	
		if(count($this->report->Groups) > 0){
			nuRunQuery("CREATE TABLE $this->sumTT $s FROM $this->TT $g");
			//echo "CREATE TABLE $this->sumTT $s FROM $this->TT $g";
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
		//echo "useable:".$this->useablePageLength."   thispage:".$this->thisPageLength."   totalpage:".$this->totalLength."<br>";
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
		
		$sortedControls = $this->sortSectionControlsByTopPositionOrder($sectionNumber,$this->report->Controls);

		for($j = 0; $j < count($sortedControls);$j++) {	
			$pSection = $sortedControls[$j]["Section"];
			$pName = $sortedControls[$j]["Name"];
			$pControlSource = $sortedControls[$j]["Source"];

			$i = $this->getDisplayControlIndex($pSection,$pName,$pControlSource);

			if($this->report->Controls[$i]->Section == $sectionNumber) {
				$Name                       = $this->report->Controls[$i]->Name;
				$Source                     = $this->report->Controls[$i]->ControlSource;
				$ControlType                = $this->report->Controls[$i]->ControlType;
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
				$Report                     = $this->report->Controls[$i]->Report;
				$Parameters                 = $this->report->Controls[$i]->Parameters;
				$LikeClause                 = $this->report->Controls[$i]->SmartTags;
				$LikeClause                 = str_replace("\"","",$LikeClause);
				//image

				if ($this->sectionGrow == 1 && $sectionNumber != 0) {
					$Top = $Top + $this->sectionGrowStack;
				}
	
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'page break'){
					$this->justDidPageBreak = true;
					$this->hasExplicitPageBreak = true;
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
        		    $addSession         = '&ses='. $_GET['ses'];
                    $s                  = $s . "   <div  style='position:absolute;left:$Left;top:$Top;height:$Height'>\n";
        		    $s                  = $s . "      <img src='graph_report.php?dir=$thedir$addSession&activityID=$this->reportID&graph_name=$thename&$thetag'>\n";
                    $s                  = $s . "   </div>\n";
				}
//========================================================================================	
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
		    $imageT             = nuRunQuery("SELECT zzsys_image_id FROM zzsys_image WHERE sim_code = '$imageCode'");
                    $imageR             = db_fetch_row($imageT);
                    $s                  = $s . "   <div  style='position:absolute;left:$Left;top:$Top;height:$Height'>\n";
					$s                  = $s . "      <img src='formimage.php?dir=$thedir&iid=$imageR[0]&$thetag'>\n";
                    $s                  = $s . "   </div>\n";
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'label'){

						if($Source=='=Date()' || $Source=='=Now()'){
							
							if ($Format == '' || $Format == '20' || $Format == 'Long Date') {
								$formattedValue = date('d-M-Y H:i');
							} else {
								$formattedValue     = date($this->accessDateFormatToPHPDateFormat($Format));
							}
							
    						//$Caption = $formattedValue;
							$s                  = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";

						} else if ($Source=='="Page " & [Page] & " of " & [Pages]') { 

							$formattedValue = "Page #thePageNumber# of #totalNumberOfPages#";
							$s                  = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$formattedValue</div>\n";
							//echo $s."<br>";
						} else {					
							$s                      = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>".$this->report->Controls[$i]->Caption."</div>\n";
						}
				}
//========================================================================================	
				if($this->controlType($this->report->Controls[$i]->ControlType) == 'text area'){  //--Field
					$displayValue           = $record[$this->report->Controls[$i]->ControlSource];
					//Sum
					$theSource              = $this->report->Controls[$i]->ControlSource;
					$builtInFunction        = false;
//============numbering pages (using a label)=======================================					
//---- Page #thePageNumber# of #totalNumberOfPages#      eg. Page 6 of 12
//==================================================================================					
					if($theSource=='="Page " & [Page] & " of " & [Pages]' || $theSource=='Page #thePageNumber# of #totalNumberOfPages#'){
							$displayValue   = "Page #thePageNumber# of #totalNumberOfPages#";
					}

					if (strtoupper($theSource) == '=NOW()' || strtoupper($theSource) == '=DATE()') {	
						if ($Format == '' || $Format == '20' || $Format == 'Long Date') {
							$displayValue = date('d-M-Y H:i');
						} else {
							$displayValue     = date($this->accessDateFormatToPHPDateFormat($Format));
						}
					}

					if(strtoupper(substr($theSource, 0, 4)) == 'SUM('){          //--sum for header or footer sections
    					$sumField           = substr($theSource, 4, -1);
	       				$selectFields       = 'SUM(' . trim($sumField) . '_Sum) as answer ';
						$groupSQL           = $this->getGroupBySql($this->sectionLevel($sectionNumber), $selectFields, $record);
						$t1                 = nuRunQuery($groupSQL);
						$r1                 = mysql_fetch_row($t1);
						$displayValue       = $r1[0];
					}
					
	               if(strtoupper(substr($theSource, 0, 6)) == '=SUM(['){  //--if the sum function
    					$sumField           = substr($theSource, 6, -2);
	       				$selectFields       = 'SUM(' . trim($sumField) . '_Sum) as answer ';
						$groupSQL           = $this->getGroupBySql($this->sectionLevel($sectionNumber), $selectFields, $record);
						$t1                 = nuRunQuery($groupSQL);
						$r1                 = mysql_fetch_row($t1);
						$displayValue       = $r1[0];
	                }
					
					if(strtoupper(substr($theSource, 0, 8)) == 'PERCENT('){      //--percent of 2 sums

    					$sumField           = substr($theSource, 8, -1);
	       				$sumFields          = array();
	       				$sumFields          = explode(',',$sumField);
	       				$selectFields       = 'SUM(' . trim($sumFields[0]) . '_Sum) as answer1, SUM(' . trim($sumFields[1]) . '_Sum) as answer2 ';
						$groupSQL           = $this->getGroupBySql($this->sectionLevel($sectionNumber), $selectFields, $record);
						$t1                 = nuRunQuery($groupSQL);
						$r1                 = mysql_fetch_row($t1);
                        if($r1[1]==0){
                            $displayValue   = 0; //--because nothing can be divided by zero
                        }else{
                            $displayValue   = ($r1[0] / $r1[1]) * 100;
                        }

						
					}

					$displayValue  = formatTextValue($displayValue, $Format);

					if($this->report->Controls[$i]->CanGrow == 'True' and $canGrow){
						$oldValue              = $displayValue;
						$break_variants = array("<br>","<BR>","<br />","<BR />","<br/>","<BR/>");
						$displayValue = str_replace($break_variants, "\n",$displayValue);
						if($this->report->nuBuilder == '1'){
							$textWidth         = str_replace('px', '',$this->report->Controls[$i]->Width)/($this->report->Controls[$i]->FontSize/2);
							$displayValue      = wordwrap($displayValue, $textWidth, "<br />"); 
						}else{
							$displayValue      = wordwrap($displayValue, iif($ReportTag=='',10,$ReportTag), "<br />"); 
						}
						$displayValue          = nl2br($displayValue);
						$lines                 = substr_count($displayValue, "<br />")+1;
						$linesArray			   = explode("<br />",$displayValue);

						if ($lines <= 5) {
							$Height = $Fontsize*1.20;
						} else if ($lines > 5 && $lines <= 15) {
							$Height = $Fontsize*1.30;												
						} else if ($lines > 15 && $lines <= 25) {
							$Height = $Fontsize*1.40;
						} else if ($lines > 25) {
							$Height = $Fontsize*1.50;
						}
												
						$defaultHeight         = $Height;
						$Height                = $Height * $lines;
						$this->growByHeight = $this->growByHeight + $Height;

						if($this->growBy < $Height - $defaultHeight){
							$this->growBy      = $Height - $defaultHeight;
							if ($sectionNumber != 0) {
								$sectionHeight = $this->toScale($this->report->Sections[$sectionNumber]->Height);
								$growByHeight = $this->growBy;
								$growString = "";
								$pos = count($linesArray)-1;
							
								if ($this->growAddHeight == 0) {
									$this->growAddHeight = $this->thisPageLength;
								}

								if ($this->growAddHeight + $growByHeight > $this->useablePageLength) {
									for ($k = count($linesArray)-1; $k >= 0; $k--) {
										$Height = $defaultHeight * $k;
										$this->growBy = $Height - $defaultHeight;
										$growByHeight = $this->growBy + $sectionHeight;
										if ($this->growAddHeight + $growByHeight < $this->useablePageLength) {
											$pos = $k;
											$this->attachGroupHeaderFieldGrowByText($this->report->Controls[$i]);
											$this->growBySectionNumber = $Section;
											break;
										}
									}

									for ($k = 0; $k < $pos; $k++) {
										$growString = $growString.$linesArray[$k]."<br />";
									}								
								
									$index = 0;
									for ($k = $pos; $k < count($linesArray);$k++) {
										$this->nextPageGrowText[$index] = $linesArray[$k];
										$index++;
									}
								
									$displayValue = $growString;
								} else {
									$this->growAddHeight = $this->growAddHeight + $growByHeight;
								}
								$this->growByHeight = $Height;
								$this->sectionGrowStack = $growByHeight;
								$this->sectionGrow = 1;
							}
						}
						
						if ($sectionNumber != 0) {
							$this->growByTotal = $this->growByTotal + $Height;
						}
						
						$s                        = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$this->growByHeight'>$displayValue</div>\n";
					}else{
//------create drilldown-------------------
						if($Report != ''){
							$reportlink       = $Report;
							$sessionlink      = $_GET['ses'];
							$formsessionlink  = $_GET['form_ses'];
							$dirlink          = $_GET['dir'];
							$displayValue     = "<a class='$Name' href='runreport.php?x=1&dir=$dir&ses=$sessionlink&form_ses=$formsessionlink&r=$Report'>$displayValue</a>";
						}
//-----------------------------------------
						$s                        = $s . "      <div class='$Name' style='position:absolute;top:$Top;height:$Height'>$displayValue</div>\n";
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
		if( $pControlNumber == 'Image'){return 'image';}
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
		$s                                  = $s . "<script type='text/javascript' src='common.js' language='javascript'></script>\n";
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
		if($TextAlign==''){
			$TextAlign = $this->report->Controls[$i]->TextAlign;
		}
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

	        $StyleString                    = "overflow:hidden;position:absolute;font-size:$FontSize";
	        $StyleString                    = "$StyleString;font-family:$FontName";
	        $StyleString                    = "$StyleString;font-weight:$FontWeight";
	        $StyleString                    = "$StyleString;background-color:$BackColor";
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
		$s                                  = $s . ".PageBreak { page-break-before:always;font-size:1 }\n";
		$s                                  = $s . ".H1 { font-size: x-large; color: red }\n";
		$s                                  = $s . "@media print {.dontPrintMe{display : none; style='top:0;left:0'}}\n";
		$s                                  = $s . "</style>\n";
		$s                                  = $s . $this->displayJavaScript();
		$s                                  = $s . "\n<body onload='LoadThis()' >\n";
		return $s;
	}


    private function displayJavaScript(){

        $s =  "\n\n\n";
        $s = $s . "<script type='text/javascript'>\n";
        $s = $s . "/* <![CDATA[ */\n";
        $s = $s . "\n";
        $s = $s . "function LoadThis(){//---load form\n";
        $s = $s . "   if(window.nuLoadThis){;\n";
        $s = $s . "      nuLoadThis();\n";
        $s = $s . "   }\n";
        $s = $s . "}\n";
        $s = $s . "\n";


        $s = $s . "self.setInterval('checknuC()', 1000); \n\n";

        $s = $s . "function checknuC(){ \n";
        $s = $s . "   if(nuReadCookie('nuC') == null){ \n";
        $s = $s . "      pop = window.open('', '_parent');\n";
        $s = $s . "      pop.close();\n";
        $s = $s . "   }\n";
        $s = $s . "}\n";





        $s   = $s . "function customDirectory(){  \n";
        $s   = $s . "   return '$this->customDirectory'; \n";
        $s   = $s . "} \n";

        $s   = $s . "function session_id(){ //-- id that remains the same until logout \n";
        $s   = $s . "   return '$this->session'; \n";
        $s   = $s . "} \n";

/*
        $s   = $s . "function form_session_id(){ //--just for this instance of this form \n";
        $s   = $s . "   return '$this->formsessionID'; \n";
        $s   = $s . "} \n";


        $s   = $s . "function access_level(){  \n";
        $s   = $s . "   return '$this->access_level'; \n";
        $s   = $s . "} \n";


        $s   = $s . "function zzsys_user_id(){  \n";
        $s   = $s . "   return '$this->zzsys_user_id'; \n";
        $s   = $s . "} \n";


        $s   = $s . "function nusmall(){  \n";
        $s   = $s . "   return false; \n";
        $s   = $s . "} \n";


        $s   = $s . "function zzsys_user_group_name(){  \n";
        $s   = $s . "   return '$this->zzsys_user_group_name'; \n";
        $s   = $s . "} \n";


        $s   = $s . "function sd(){  \n";
        $s   = $s . "   return '$this->customDirectory'; \n";
        $s   = $s . "} \n";


        $s   = $s . "function web_root_path(){  \n";
        $s   = $s . "   return '".$this->setup->set_web_root_path."'; \n";
        $s   = $s . "} \n";



        $s   = $s . "function getImage(pID){  \n";
        $s   = $s . "   return 'formimage.php?dir='+sd()+'&iid='+pID; \n";
        $s   = $s . "} \n";

*/







        for($i=0;$i<count($this->jsFunctions);$i++){
            $s = $s . $this->jsFunctions[$i];
            $s = $s . "\n\n";
        }
        $s = $s . "\n\n";
        $s = $s . "/* ]]> */ \n";
        $s = $s . "</script>\n\n\n";
        return $s;

    }

    public function appendJSfunction($pValue){
        $this->jsFunctions[] = $pValue;
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


	public function getGroupBySql($pSectionLevel, $pSelectFields, $pRecord){
	
		$dq  = '"';
		for($i=0;$i<=$pSectionLevel;$i++){
			if($i==0){
				if($this->report->Groups[$i]->Field<>''){
					$whereClause = $this->report->Groups[$i]->Field." = $dq".$pRecord[$this->report->Groups[$i]->Field]."$dq";
				}
			}else{
				if($this->report->Groups[$i]->Field<>''){
					$whereClause = $whereClause.' AND '.$this->report->Groups[$i]->Field." = $dq".$pRecord[$this->report->Groups[$i]->Field]."$dq";
				}
			}
		}
		if(strlen($pSectionLevel)==0){
			return "SELECT $pSelectFields FROM $this->sumTT";
		}else{
			return "SELECT $pSelectFields FROM $this->sumTT WHERE $whereClause";
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
    
    
    public function sortSectionControlsByTopPositionOrder($sectionNumber,$Controls) {
    	
    	$count = 0;
    	$sectionControls = array();
    	for($i = 0 ; $i < count($Controls) ; $i++){
    		if ($Controls[$i]->Section == $sectionNumber) {
    			$sectionControls[$count]["Name"] 		= $Controls[$i]->Name;
				$sectionControls[$count]["Source"]      = $Controls[$i]->ControlSource;
				$sectionControls[$count]["ControlType"] = $Controls[$i]->ControlType;
				$sectionControls[$count]["Top"]         = str_replace("px","",$Controls[$i]->Top);
				$sectionControls[$count]["Width"]       = $Controls[$i]->Width;
				$sectionControls[$count]["Height"]      = $Controls[$i]->Height;
				$sectionControls[$count]["Left"]        = $Controls[$i]->Left;
				$sectionControls[$count]["Section"]     = $Controls[$i]->Section;
				$sectionControls[$count]["Fontname"]    = $Controls[$i]->FontName;
				$sectionControls[$count]["Fontsize"]    = $Controls[$i]->FontSize;
				$sectionControls[$count]["Format"]      = $Controls[$i]->Format;
				$sectionControls[$count]["Decimal"]    = $Controls[$i]->DecimalPlaces;
				$sectionControls[$count]["IsHyperlink"] = $Controls[$i]->IsHyperlink;
				$sectionControls[$count]["ReportTag"]   = $Controls[$i]->Tag;

				$sectionControls[$count]["Report"]      = $Controls[$i]->Report;
				$sectionControls[$count]["Parameters"]  = $Controls[$i]->Parameters;

				$sectionControls[$count]["LikeClause"]  = $Controls[$i]->SmartTags;    			
    			$count++;
    		}
    	}

    	
    	foreach ($sectionControls as $key => $row) {
    		$top[$key]  = $row['Top'];
	}
  		
  	if (count($top) > 0) {
    		array_multisort($top,SORT_ASC,$sectionControls);
  	}

	return $sectionControls;
    }
    
    public function getDisplayControlIndex($pSection,$pName,$pControlSource) {

		for($i = 0; $i<count($this->report->Controls); $i++) {
			$Section = $this->report->Controls[$i]->Section;
			$Name = $this->report->Controls[$i]->Name;
			$ControlSource = $this->report->Controls[$i]->ControlSource;

			if ($Section == $pSection && $Name == $pName && $ControlSource == $pControlSource) {
				$index = $i;
				return $index;
			}
		}
    	
    	return -1;
    }

	public function attachGroupHeaderFieldGrowByText($SectionControls) {
		$this->nextPageGrowByControls = $SectionControls;
	}
	
	public function insertRemainingGrowByFieldText() {
		$Name                       = $this->nextPageGrowByControls->Name;
		$Source                     = $this->nextPageGrowByControls->ControlSource;
		$ControlType                = $this->nextPageGrowByControls->ControlType;
		$Top                        = $this->toScale($this->nextPageGrowByControls->Top);
		$Width                      = $this->toScale($this->nextPageGrowByControls->Width);
		$Height                     = $this->toScale($this->nextPageGrowByControls->Height);
		$Left                       = $this->toScale($this->nextPageGrowByControls->Left);
		$Section                    = $this->nextPageGrowByControls->Section;
		$Fontname                   = $this->nextPageGrowByControls->FontName;
		$Fontsize                   = $this->nextPageGrowByControls->FontSize;
		$Format                     = $this->nextPageGrowByControls->Format;
		$Decimal                    = $this->nextPageGrowByControls->DecimalPlaces;
		$IsHyperlink                = $this->nextPageGrowByControls->IsHyperlink;
		$ReportTag                  = $this->nextPageGrowByControls->Tag;
		$Report                     = $this->nextPageGrowByControls->Report;
		$Parameters                 = $this->nextPageGrowByControls->Parameters;
		$LikeClause                 = $this->nextPageGrowByControls->SmartTags;
		$$LikeClause                 = str_replace("\"","",$LikeClause);	

		$Top = 0;
		
		if (count($this->nextPageGrowText) > 0) {
						$linesArray			   = $this->nextPageGrowText;
						$lines = count($this->nextPageGrowText);
						
	   					if($Fontname=='Arial'){
	       					$Height = $Fontsize*1.3;
	   					}else{
	      					$Height = $Fontsize*1.5;
	   					}	
						
						$defaultHeight         = $Height;
						$Height                = $Height * $lines;
						
						if($this->growBy < $Height - $defaultHeight){
							$this->growBy      = $Height - $defaultHeight;
							
							$sectionHeight = $this->toScale($this->report->Sections[$Section]->Height);
							$growByHeight = $this->growBy;
							$growString = "";
							$pos = count($linesArray)-1;
													
							if ($this->growAddHeight == 0) {
								$this->growAddHeight = $this->thisPageLength;
							}
							
							if ($this->growAddHeight + $growByHeight > $this->useablePageLength) {
								for ($k = count($linesArray)-1; $k >= 0; $k--) {
									$Height = $defaultHeight * $k;
									$this->growBy = $Height - $defaultHeight;
									$growByHeight = $this->growBy + $sectionHeight;
									if ($this->growAddHeight + $growByHeight < $this->useablePageLength) {
										$pos = $k;
										$this->attachGroupHeaderFieldGrowByText($this->report->Controls[$i]);
										$this->growBySectionNumber = $Section;
										break;
									}
								}

								for ($k = 0; $k < $pos; $k++) {
									$growString = $growString.$linesArray[$k]."<br />";
								}								
								
								for ($k = $pos; $k < count($linesArray);$k++) {
									$this->nextPageGrowText = $this->nextPageGrowText.$linesArray[$k];
								}
								
								$displayValue = $growString;
							} else {
								$this->growAddHeight = $this->growAddHeight + $growByHeight;
								
								for ($k = 0; $k < count($linesArray); $k++) {
									$displayValue = $displayValue.$linesArray[$k]."<br />";
								}
								
							}
							$this->growByHeight = $this->growByHeight + $Height;
							$this->sectionGrowStack = $growByHeight;
							$this->sectionGrow = 1;
						}
						$this->growByTotal = $this->growByTotal + ($Height+$sectionHeight);
						$ss = "      <div class='$Name' style='position:absolute;top:$Top;height:".($Height+$sectionHeight)."'>$displayValue</div>\n";
		} else {
			$ss = '';
		}
		return $ss;
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
