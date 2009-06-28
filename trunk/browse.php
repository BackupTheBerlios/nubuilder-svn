<?php
/*
** File:           browse.php
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

session_start( );
$dir                             = $_GET['dir'];
$ses                             = $_GET['ses'];
$form_ses                        = $_GET['form_ses'];
$f                               = $_GET['f'];
$p                               = $_GET['p'];
$o                               = $_GET['o'];
$d                               = $_GET['d'];
$s                               = $_GET['s'];
$prefix                          = $_GET['prefix'];
$search                          = $_POST['search'];
if($_POST['newsearch'] == '1'){$p = 1;}

include("../$dir/database.php");
include('common.php');

if(passwordNeeded($f)){
	$session = nuSession($ses);
	if($session->foundOK == ''){
		print 'you have been logged out..';
		return;
	}
}



	$nuBrowse                      = new Browse();
	$nuBrowse->loadAfterConstruct($f, $p, $o, $d, $s, $prefix, $dir, $ses, $form_ses, $session->sss_zzsys_user_id);

	

function addJSFunction($pCode){

	global $nuBrowse;
	$nuBrowse->appendJSFunction($pCode);

}

	
	
$nuBrowse->execute();

print "$nuBrowse->CRLF$nuBrowse->CRLF<script>$nuBrowse->CRLF$nuBrowse->CRLF/*$nuBrowse->CRLF$nuBrowse->CRLF".$nuBrowse->SQL->SQL."$nuBrowse->CRLF$nuBrowse->CRLF*/$nuBrowse->CRLF$nuBrowse->CRLF</script>";

nuRunQuery("DROP TABLE IF EXISTS `$nuBrowse->TT`");

class Browse{

    public  $Row                   = array();
    public  $Column                = array();
    public  $searchField           = array();
    public  $form                  = array();
    public  $setup                 = array();
    public  $jsFunctions           = array();
    public $arrayOfHashVariables   = array();
    public  $pageLength            = 0;
    public  $startTime             = 0;
    public  $CRLF                  = "\n";
    public  $TAB                   = '    ';
    public  $pageHeader            = '';
    public  $pageBody              = '';
    public  $pageFooter            = '';
    public  $SQL                   = null;
    public  $rowHeight             = 0;
    public  $searchString          = '';
    public  $pageWhereClause       = '';
    public  $pageSQL               = '';
    public  $theFormID             = '';
    public  $oldFormID             = '';
    public  $PageNo                = 0;
    public  $orderBy               = '';
    public  $isDescending          = '';
    public  $isLookup              = '';
    public  $lookFor               = '';
    public  $TT                    = '';
    public  $rowPrefix             = '';
    public  $customDirectory       = '';
    public  $session               = '';
    public  $form_session          = '';
    public  $old_sql_string        = '';
    public  $new_sql_string        = '';
    public  $zzsys_user_id         = '';

    function loadAfterConstruct($theFormID, $thePageNumber, $theOrderBy, $isDescending, $theSearchString, $subformPrefix, $dir, $ses, $form_ses, $zzsys_user_id){

   	$this->TT                  = TT();           //---temp table name
	$this->rowPrefix           = $subformPrefix;
	$this->customDirectory     = $dir;
	$this->session             = $ses;
	$this->form_session        = $form_ses;
	$this->PageNo              = $thePageNumber;
	$this->orderBy             = $theOrderBy;
	$this->isDescending        = $isDescending;
	$this->oldFormID           = $theFormID;
	$this->theFormID           = $this->getFormID($theFormID);
	$this->searchString        = $theSearchString;
	$this->pageLength          = 27;
	$this->rowHeight           = 20;
	$this->startTime           = time();
	$this->setup               = nuSetup();
	$this->getColumnInfo($this->theFormID);
	$this->form                = formFields($this->theFormID);
	$this->defaultJSfunctions();
	$this->zzsys_user_id       = $zzsys_user_id;
//----------create an array of hash variables that can be used in any "hashString" 
	$sesVariables                    = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$sesVariables['#TT#']            = $this->TT;
	$sesVariables['#browseTable#']   = $this->TT;
	$sesVariables['#formSessionID#'] = $form_ses;
	$sysVariables                    = sysVariablesToHashArray($form_ses);                            //--values in sysVariables from the calling lookup page
	$this->arrayOfHashVariables      = joinHashArrays($sysVariables, $sesVariables);                  //--join the arrays together
	
	
	//----------allow for custom code----------------------------------------------
	$globalValue                     = getglobalValue($this->session);
	$browseTable                     = $this->TT;

	eval(replaceHashVariablesWithValues($this->arrayOfHashVariables, $this->form->sfo_custom_code_run_before_browse));

	$this->old_sql_string      = $this->form->sfo_sql;
	$this->new_sql_string      = replaceHashVariablesWithValues($this->arrayOfHashVariables, $this->form->sfo_sql);
	
	$s   =      "\n/*\n old : $this->old_sql_string \n*/\n";
	$s   = $s . "\n/*\n new : $this->new_sql_string \n*/\n";
	$this->appendJSfuntion($s);
	
	$this->SQL                 = new sqlString($this->new_sql_string);
	$this->buildWhereClause($theSearchString);
	$this->SQL->setWhere($this->pageWhereClause);
	$this->SQL->setOrderBy($this->buildOrderBy());
	$this->pageBody            = $this->buildBody();
	$this->pageHeader          = $this->buildHeader();

}

private function getFormID($formID){
	
// get form_id from object_id (if $formID is not a form_id)
	$T                     = nuRunQuery("SELECT count(*) FROM zzsys_form WHERE zzsys_form_id = '$formID'");
	$R                     = db_fetch_row($T);
	if($R[0] == 1){ // is a form_id
		$this->isLookup    = false;
		return $formID;
	}
	$t                     = nuRunQuery("SELECT sob_lookup_zzsysform_id FROM zzsys_object WHERE zzsys_object_id = '$formID'");
	$r                     = db_fetch_object($t);
	$this->isLookup        = true;
	$this->lookFor         = $formID;  //---the field name of the form that this selection will be for
	return $r->sob_lookup_zzsysform_id;

}

private function getColumnInfo($formID){

// get properties for all the fields on the browse page
	$t                         = nuRunQuery("SELECT * FROM zzsys_browse WHERE sbr_zzsys_form_id = '$formID' ORDER BY sbr_order");
	while($r                   = db_fetch_object($t)){
		
		if($r->sbr_visible     == '1'){
			$this->Column[]    = $r;
		}
		if($r->sbr_searchable  == '1'){
			$this->searchField[]    = $r;
		}
	}
	
}

    public function buildOrderBy(){
    	
    	if($this->orderBy         == ''){
    		$s                     = $this->SQL->DorderBy;
    	}else{

			$s                     = ' ORDER BY ' . $this->Column[$this->orderBy]->sbr_sort;
			if($this->isDescending == '1'){
				$s                 = $s . ' DESC';
			}
    		
    	}
    	return $s;
    }


    public function buildWhereClause($pString){

		if($pString == '' AND $_POST['search'] == ''){
			$this->pageWhereClause = $this->SQL->where;
		}else{
			$dq = '"';
// add querystring search ('s') + user entry (POST)
			$string              = str_replace('\"', '"', $_POST['search']). ' ' . $pString;
	    	$searchFor           = array();
	    	$explode             = array();
// put strings that are in quotes into an array
			$strings             = getArrayFromString($string,'"');
			for($i = 0 ; $i < count($strings) ; $i++){
				$searchFor[]     = $strings[$i];
			}
	
// remove strings that are in quotes from the original string
			for($i = 0 ; $i < count($searchFor) ; $i++){
				$string          = str_replace('"' . $searchFor[$i] . '"', ' ', $string);
			}

			$string          = str_replace('"', '\"', $string);
// put individual words or strings into an array
			$explode             = explode(' ', $string);		
			for($i = 0 ; $i < count($explode) ; $i++){
				if($explode[$i] != ''){
					$searchFor[] = $explode[$i];
				}
			}
	
// build the start of the where clause
			$whereClause                 = iif($this->SQL->where == '', ' WHERE (', $this->SQL->where . '  AND (');
			$AND                         = '';
//loop through all the words or phrases
			for($i = 0 ; $i < count($searchFor) ; $i++){
				if(substr_count($searchFor[$i], ' ') == 0 and substr($searchFor[$i],0,1) == '-'){
					$LIKE = ' NOT LIKE ';
					$ANDOR = ' AND ';
					$searchFor[$i] = substr($searchFor[$i],1);
				}else{
					$LIKE = ' LIKE ';
					$ANDOR = ' OR ';
				}
				$columnWhere = '';
	
// loop through all the searchable fields
				for($ii = 0 ; $ii < count($this->searchField) ; $ii++){

					if($LIKE == ' NOT LIKE '){
						$removeNull = 'IF(ISNULL(' . formatSQLWhereCriteria($this->searchField[$ii]) . '),"",' . formatSQLWhereCriteria($this->searchField[$ii]) . ')';
					}else{
						$removeNull = formatSQLWhereCriteria($this->searchField[$ii]);
					}
					if($columnWhere == ''){
						$columnWhere = $columnWhere . " \n$AND (\n($removeNull $LIKE $dq%" . $searchFor[$i] . '%") ';
						$AND         = ') AND';
					}else{
						$columnWhere = $columnWhere . "\n" . " $ANDOR ($removeNull $LIKE $dq%" . $searchFor[$i] . '%") ';
					}
				}
				$whereClause             = $whereClause . $columnWhere;
			}
			$this->pageWhereClause       = $whereClause . '))';
		}
	}

    public function buildHeader(){

		$dq      = '"';
		$tempAr  = array(); 
		$s       =      "$this->CRLF<div id='logo' style='cursor:hand;overflow:hidden;position:absolute;top:0; left:10;  width:150;  height:70'  >$this->CRLF";
        if(!$this->isLookup){
    		if($this->setup->set_home_mouse_up==''){
    			$s   = $s . "<br/><input  id='home_id' type='button' class='actionButton' accesskey='h' value='Home' onclick='backToIndex()'/>&nbsp;$this->CRLF";
    		}else{
                $s   = $s . "<img  id='home_id' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_home_mouse_up . "' alt='Home' onmouseup=\"this.src=getImage('" . $this->setup->set_home_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_home_mouse_up . "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_home_mouse_down . "')\" onclick='backToIndex()'/>$this->CRLF";
    		}
        }
		$s       = $s . "</div>$this->CRLF";
		$s       = $s . "$this->CRLF<div id='actionButtons' style='overflow:hidden;position:absolute;top:10; left:165;  width:660;  height:70'  >$this->CRLF";
		$s       = $s . "<table align='center'><tr style='border-width:0;' align='center'><td style='border-width:0;' align='center'>$this->CRLF";
		$s       = $s . "<u>S</u>earch<input name='newsearch' id='newsearch' type='hidden'> <input  accesskey='s' onchange=$dq document.forms[0]['displayPage'].value=1;changeAction();$dq class='searchcolor' name='search' id='search' size='60' value=\"".str_replace('"', "&quot;", $_POST['search'])."\"/>&nbsp;$this->CRLF";
		if($this->setup->set_search_mouse_up==''){
			$s   = $s . "<input type='submit' class='actionButton' value='Search'/>&nbsp;$this->CRLF";
		}else{
			$s   = $s . "<div style='overflow:hidden;position:absolute;top:0;left:0;height:0;width:0'><input type='submit' class='actionButton' value='Search'/></div>$this->CRLF";
            $s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_search_mouse_up . "' alt='Search' onmouseup=\"this.src=getImage('" . $this->setup->set_search_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_search_mouse_up . "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_search_mouse_down . "')\" onclick='document.thebrowse.submit()'/>$this->CRLF";
		}

//		if($this->form->sfo_add_button    == '1'  and displayCondition($this->form->sfo_add_button_display_condition, '', $tempAr)){
		if($this->form->sfo_add_button    == '1'  and displayCondition($this->arrayOfHashVariables, $this->form->sfo_add_button_display_condition)){
			$title = iif($this->form->sfo_add_title == '','Add Record',$this->form->sfo_add_title);
			if($this->setup->set_add_mouse_up==''){
				$s   = $s . "<input type='button' class='actionButton' value='$title' accesskey='a' onclick='addThis()'/>&nbsp;$this->CRLF";
			}else{
                $s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_add_mouse_up . "' alt='$title' onmouseup=\"this.src=getImage('" . $this->setup->set_add_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_add_mouse_up . "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_add_mouse_down . "')\" onclick='addThis()'/>$this->CRLF";
			}
		}
		$s       = $s . "</td></tr>$this->CRLF";
		$s       = $s . $this->buildPageNumbers();
		$s       = $s . "</table>$this->CRLF";
		$s       = $s . "</div>$this->CRLF";
        $dbc     = '';
        if($this->zzsys_user_id == 'globeadmin' and $this->form->sys_setup != '1'){
            $dbc = " ondblclick='openForm(\"form\",\"$this->oldFormID\");' ";
        }
		$s       = $s . "$this->CRLF<div $dbc id='pagetitle' style='padding:10;font-size:x-small;font-family:tahoma;text-align:center;overflow:hidden;position:absolute;top:-7; left:830;  width:150;  height:30'  >$this->CRLF";
		$s       = $s . "<i>".$this->form->sfo_title."</i></div>$this->CRLF";
		$s       = $s . "$this->CRLF<div id='loggedin' style='padding:10;font-size:x-small;font-family:tahoma;text-align:center;overflow:hidden;position:absolute;top:25; left:830;  width:150;  height:45'  >$this->CRLF";
		$s       = $s . $GLOBALS['zzsys_user_name']."<br/>".$this->setup->set_title."</div>$this->CRLF";

		return $s;

	}




    public function buildPageNumbers(){

		$dq      = '"';
		$s       =      "\n<tr border='1' padding='0' align='center' style='vertical-align:top;border-width:0;vertical-align:top;'><td style='border-width:0;' border='1' >\n";
		$s       = $s . "<span onclick='runPage(-1)' style='cursor:hand;' class='sp'<font size='2'>Page <b><</b></font>&nbsp;</span>\n";
		$s       = $s . "<span class='sp'><input class='searchcolor' onchange='runPage(0)' style='text-align:center;width:40' name='displayPage' value='" . $_GET['p'] . "'></span>\n";
		$s       = $s . "<span onclick='runPage(1)' style='cursor:hand;' class='sp'>&nbsp;<font size='2'><b>></b></font></span>\n";
		$s       = $s . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>\n\n";

		return $s;

	}

    private function displayJavaScript(){

        print "$this->CRLF$this->CRLF<!-- Form Functions -->$this->CRLF";
		print makeCSS();
        print "<script type='text/javascript'>$this->CRLF";
        print "/* <![CDATA[ */$this->CRLF";
        for($i=0;$i<count($this->jsFunctions);$i++){
            print $this->jsFunctions[$i];
            print "$this->CRLF$this->CRLF";
        }
        print "/* ]]> */ $this->CRLF";
        print "</script>$this->CRLF<!-- End Of Form Functions -->$this->CRLF$this->CRLF";

    }

    private function defaultJSfunctions(){
        $C   = $this->CRLF;
        $div = count($this->Column) + 1;
        $s   =      "function MIN(rw){//---mouse over menu$C";
        $s   = $s . "   for(i = 0 ; i < $div ; i++){ $C";
        $s   = $s . "      document.getElementById(rw+i).style.backgroundColor='" . $this->setup->set_browse_background_hover_color . "';$C";
        $s   = $s . "      document.getElementById(rw+i).style.color='" . $this->setup->set_browse_hover_color . "';$C";
        $s   = $s . "   } ;$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

   		if($this->form->sfo_access_without_login != '1'){
            $s   =      "self.setInterval('checknuC()', 1000) $C$C";
        }

        $s   = $s . "function checknuC(){ $C";
        $s   = $s . "   if(nuReadCookie('nuC') == null){ $C";
        $s   = $s . "      pop = window.open('', '_parent');$C";
        $s   = $s . "      pop.close();$C";
        $s   = $s . "   }$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);


        $s   =      "function LoadThis(){//---load form$C";
        $s   = $s . "   if(window.nuLoadThis){;$C";
        $s   = $s . "      nuLoadThis();$C";
        $s   = $s . "   };$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function MOUT(rw){//---mouse out menu$C";
        $s   = $s . "   for(i = 0 ; i < $div ; i++){ $C";
        $s   = $s . "      document.getElementById(rw+i).style.backgroundColor='';$C";
        $s   = $s . "      document.getElementById(rw+i).style.color='';$C";
        $s   = $s . "   } ;$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function PIN(pthis){//--$C";
        $s   = $s . "   document.getElementById(pthis.id).style.color='" . $this->setup->set_page_number_hover_color . "';$C";
        $s   = $s . "   document.getElementById(pthis.id).style.fontWeight='bold';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function POUT(pthis){//--$C";

        $s   = $s . "   document.getElementById(pthis.id).style.color='';$C";
        $s   = $s . "   document.getElementById(pthis.id).style.fontWeight='';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        
        $s   =      "function getImage(pID){ $C"; 
        $s   = $s . "   return 'formimage.php?dir='+customDirectory()+'&iid='+pID; $C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);



        $s   =      "function TIN(pthis){ $C";
        $s   = $s . "   document.getElementById(pthis.id).style.color='" . $this->setup->set_hover_color . "';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function TOUT(pthis){ $C";
        $s   = $s . "   document.getElementById(pthis.id).style.color='';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function runPage_old(pPageNo){ $C";
        $s   = $s . "   document.forms[0].action = 'browse.php?x=1&s=$this->searchString&dir=$this->customDirectory&form_ses=$this->form_session&ses=$this->session&prefix=$this->rowPrefix&f=$this->oldFormID&o=$this->orderBy&d=$this->isDescending&p='+pPageNo;$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);


        $s   =      "function changeAction(){ $C";
        $s   = $s . "   document.forms[0].action = 'browse.php?x=1&s=$this->searchString&dir=$this->customDirectory&form_ses=$this->form_session&ses=$this->session&prefix=$this->rowPrefix&f=$this->oldFormID&o=$this->orderBy&d=$this->isDescending&p=1';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function runPage(pAdd){ $C";
        $s   = $s . "   var thePage = parseInt(document.forms[0]['displayPage'].value);$C";
        $s   = $s . "   thePage     = thePage + pAdd;$C";
        $s   = $s . "   if(thePage < 1){thePage = 1;}$C";
        $s   = $s . "   document.forms[0].action = 'browse.php?x=1&s=$this->searchString&dir=$this->customDirectory&form_ses=$this->form_session&ses=$this->session&prefix=$this->rowPrefix&f=$this->oldFormID&o=$this->orderBy&d=$this->isDescending&p='+thePage;$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);


        $s   =      "function runOrder(pOrderNo, pDesc){ $C";
        $s   = $s . "   document.forms[0].action = 'browse.php?x=1&s=$this->searchString&dir=$this->customDirectory&form_ses=$this->form_session&ses=$this->session&prefix=$this->rowPrefix&f=$this->oldFormID&p=1&o='+pOrderNo+'&d='+pDesc;$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function customDirectory(){ $C";
        $s   = $s . "   return '$this->customDirectory';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function form_session_id(){ $C";
        $s   = $s . "   return '$this->form_session';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function session_id(){ $C";
        $s   = $s . "   return '$this->session';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function doIt(pID){ $C";
        if($this->isLookup){
	        $s   = $s . "   getRecordFromList('$this->lookFor', '$this->rowPrefix', pID);$C";
        }else{
	        $s   = $s . "   openForm('$this->theFormID',pID);$C";
        }
        $s   = $s . "   return true;$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function addThis(){ $C";
        $s   = $s . "   openForm('$this->theFormID','-1');$C";
        $s   = $s . "};$C";
        $this->appendJSfuntion($s);

    }


    public function appendJSfuntion($pValue){
        $this->jsFunctions[]=$pValue;
    }

//--same as above but spelt correctly
    public function appendJSfunction($pValue){
        $this->jsFunctions[]=$pValue;
    }

    private function displayHeader(){

		print "<html>$this->CRLF";
		print "<head>$this->CRLF";
		print "<meta http-equiv='Content-Type' content='text/html;'/>$this->CRLF";
		print "<title>".$this->form->sfo_title."</title>$this->CRLF";
		print "<script type='text/javascript' src='common.js' language='javascript'></script>$this->CRLF";

		$this->displayJavaScript();

		print "</head>$this->CRLF";
		print "<body onload='LoadThis()' >$this->CRLF";
		print "<form name='thebrowse' id='thebrowse' action='' method='post'>$this->CRLF";
		print $this->pageHeader;

    }

    private function buildFooter(){

        $this->pageFooter = "$this->CRLF</form>$this->CRLF</body>$this->CRLF</html>$this->CRLF$this->CRLF";
        $build            = time()- $this->startTime;
        $this->pageFooter = $this->pageFooter . "<!--built in $build seconds-->";
		return $this->pageFooter;
    }


      public function buildBody(){

            $left              = 0;
            $pageWidth         = 968;
            $percentAmount     = 1;
            $select            = '';
	    $this->SQL->removeAllFields();
            $s                 =  "<div id='BorderDiv'  class='nuborder' class='unselected' style='overflow:hidden;position:absolute;top:80; left:10;  width:972;    height:600' ></div>$CRLF";
            $s                 = $s . "<div id='browse' class='unselected' style='vertical-align:text-bottom;visibility:visible;overflow:hidden;position:absolute;top:85; left:12;  width:$pageWidth;  height:30'  >$this->CRLF";
            for($i             = 0 ; $i < count($this->Column) ; $i++){
				$this->SQL->addField($this->Column[$i]->sbr_display);
				$w             = $this->Column[$i]->sbr_width * $percentAmount;
				$a             = align($this->Column[$i]->sbr_align);
				$s             = $s . "$this->TAB<div class='unselected' style='overflow:hidden;position:absolute;text-align:$a;top:0;left:$left;width:$w;height:20'>$this->CRLF";
				$desc          = iif($i == $this->orderBy AND $this->isDescending == '', '1', '');
				$s             = $s . "$this->TAB$this->TAB<span style='cursor:hand;font-size:x-small;font-family:tahoma;font-weight:bold;height:50' id='title$i' onmouseout='TOUT(this)' onmouseover='TIN(this)' onclick='runOrder($i, \"$desc\")'>&nbsp;".$this->Column[$i]->sbr_title."&nbsp;</span>$this->CRLF";
				$s             = $s . "$this->TAB</div>$this->CRLF";
				$left          = $left + $w;
            }
            
            $s                 = $s . "</div>$this->CRLF";
            $s                 = $s . "<div id='browse' class='browse' style='visibility:visible;overflow:hidden;position:absolute;top:105; left:12;  width:$pageWidth;  height:556'  >$this->CRLF";
            $page              = ($this->PageNo -1) * $this->pageLength;
            $this->SQL->addField($this->form->sfo_primary_key);
            $primaryKeyNumber  = count($this->SQL->fields)-1;
//tofile('browse sql:'.$this->SQL->SQL);
            $t                 = nuRunQuery($this->SQL->SQL . " LIMIT $page, 27");
            $top               = -10;
            $row               = 0;
            while($r           = db_fetch_row($t)){
            	  $theID       = '"' . $r[$primaryKeyNumber] . '"';
                  $rowname     = 'rw'.substr('0'.$row,-2);
                  $param       = '"'.$rowname.'"';
                  $left        = 0;
                  $top         = $top + $this->rowHeight;
                  for($i       = 0 ; $i < count($this->Column) ; $i++){
                        $w     = $this->Column[$i]->sbr_width * $percentAmount;
                        $a     = align($this->Column[$i]->sbr_align);
                        $s     = $s . "$this->TAB<div onmouseover='MIN($param)' onmouseout='MOUT($param)' onclick='doIt($theID)' class='browse' id='$rowname$i' style='cursor:hand;text-align:$a;top:$top;left:$left;width:$w;height:$this->rowHeight'>$this->CRLF";
                        $s     = $s . "$this->TAB$this->TAB&nbsp;".formatTextValue($r[$i], $this->Column[$i]->sbr_format)."&nbsp;$this->CRLF";
                        $s     = $s . "$this->TAB</div>$this->CRLF";
                        $left  = $left + $w;
                  }
                  $w           = iif($left < $pageWidth, $pageWidth - $left,0);
                  $s           = $s . "$this->TAB<div onmouseover='MIN($param)' onmouseout='MOUT($param)' onclick='doIt($theID)' class='browse' id='$rowname$i' style='cursor:hand;top:$top;left:$left;width:$w;height:$this->rowHeight'>$this->CRLF";
                  $s           = $s . "$this->TAB</div>$this->CRLF";
// allow keyboad movement                  
                  $s           = $s . "$this->TAB<div onmouseover='MIN($param)' onmouseout='MOUT($param)' class='browse' id='hidden$rowname$i' style='top:$top;left:$left;width:0;height:$this->rowHeight'>$this->CRLF";
                  $s           = $s . "$this->TAB<input onfocus='MIN($param)' onblur='MOUT($param)' >$this->CRLF";
                  $s           = $s . "$this->TAB</div>$this->CRLF";

                  $row         = $row + 1;
            }
            $s                 = $s . '</div>';

//------print login name
		$slogin = "<div id='BorderDiv'   class='nuborder' style='overflow:hidden;position:absolute;top:664;left:12;width:968;height:15;text-align:right' >$CRLF";
		$t = nuRunQuery("SELECT * FROM zzsys_user WHERE zzsys_user_id = '$this->zzsys_user_id'");
		$r = db_fetch_object($t);

		if($this->form->sfo_help != ''){
			$help = "title='help' onclick='openHelp(" . '"' . $this->form->zzsys_form_id  . '")' . "'";
		}else{
			$help = "";
		}
		
		if($r->sus_login_name == ''){$r->sus_login_name = 'globeadmin';}
		$slogin = $slogin . "<span id='nuuser' class='unselected' style='font-size:10;font-weight:normal' $help >$r->sus_login_name&nbsp;</span></div>$CRLF";
		$slogin = $slogin . "<div id='BorderDiv3'   class='nuborder' style='overflow:hidden;position:absolute;top:664;left:12;width:200;height:15;text-align:left' >$CRLF";
		$slogin = $slogin . "<span id='powered' class='unselected' style='font-size:10;font-weight:normal'>Powered by nuBuilder</span></div>$CRLF";
            
        return $s.$slogin;

    }
    
    
      public function displayBody(){
        print $this->pageBody;
    }

    
      public function displayFooter(){
        print $this->pageFooter;
    }

      public function execute(){

        $this->displayHeader();
        $this->displayBody();
        $this->buildFooter();
        $this->displayFooter();
   		nuRunQuery("DROP TABLE IF EXISTS $this->TT");

    }

}




function formatSQLWhereCriteria($pBrowseObject){

	if($pBrowseObject->sbr_format == ''){
		return $pBrowseObject->sbr_display;
	}else{
		//-- get number and date format array
		$sFormat = textFormatsArray();
		return str_replace("??", $pBrowseObject->sbr_display, $sFormat[$pBrowseObject->sbr_format]->sql);
	}

}







?>
