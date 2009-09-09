<?php
/*
** File:           browsesmall.php
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


$dir                             = $_GET['dir'];         //-- directory of database.php
$ses                             = $_GET['ses'];         //-- zzsys_session_id in zzsys_session
$objectID                        = $_GET['ob'];          //-- zzsys_object_id in zzsys_object of selected lookup
$form_ses                        = $_GET['form_ses'];    //-- id used in zzsys_variables to hold the form's values while not on that form
$f                               = $_GET['f'];           //-- zzsys_form_id in zzsys_form
$fr                              = $_GET['fr'];          //-- record id that called this lookup
$p                               = $_GET['p'];           //-- page no.
$o                               = $_GET['o'];           //-- column to order by
$d                               = $_GET['d'];           //-- how to order the column to order by (1=descending)
$s                               = $_POST['search'];     //-- words to search for from previous submit
//if($_POST['newsearch'] == '1'){$p = 1;}

include("../$dir/database.php");
include('common.php');

$session = nuSession($ses);
if($session->foundOK == ''){
	print 'you have been logged out..';
	return;
}

$search                        = $_POST['search'];
if($_POST['newsearch'] == '1'){$p = 1;}
$nuBrowse                      = new Browse($f, $p, $o, $d, $s, '', $objectID, $fr);

$nuBrowse->execute();

class Browse{

    public  $Row               = array();
    public  $Column            = array();
    public  $form              = array();
    public  $callingForm       = array();
    public  $objectID          = array();
    public  $setup             = array();
    public  $jsFunctions       = array();
    public  $pageLength        = 0;
    public  $startTime         = 0;
    public  $CRLF              = "\n";
    public  $TAB               = '    ';
    public  $pageHeader        = '';
    public  $pageBody          = '';
    public  $pageFooter        = '';
    public  $SQL               = null;
    public  $rowHeight         = 0;
    public  $searchString      = '';
    public  $pageWhereClause   = '';
    public  $pageSQL           = '';
    public  $theFormID         = '';
    public  $PageNo            = 0;
    public  $orderBy           = '';
    public  $isDescending      = '';
    public  $isLookup          = false;
    public  $lookFor           = '';
    public  $TT                = '';
    public  $rowPrefix         = '';
    public  $recordID          = '';

    function __construct($theFormID, $thePageNumber, $theOrderBy, $isDescending, $theSearchString, $subformPrefix, $objectID, $recordID){

   	$this->TT                  = TT();           //---temp table name
   	$this->theFormID           = $theFormID;
   	$this->objectID            = $objectID;
   	$this->recordID            = $recordID;
	$this->rowPrefix           = $subformPrefix;
	$this->PageNo              = $thePageNumber;

	$this->orderBy             = $theOrderBy;
	$this->isDescending        = $isDescending;
	$this->form                = formFields($theFormID);
	if($objectID == ''){
		$this->callingForm     = formFields($theFormID);
	}else{
		$this->isLookup        = true;
		$t                     = nuRunQuery("SELECT sob_lookup_zzsysform_id FROM zzsys_object WHERE zzsys_object_id = '$objectID'");
		$r                     = db_fetch_row($t);
		$this->callingForm     = formFields($r[0]);

		while(list($key, $va)  = each($_POST)){
			$uniq              = uniqid('1');
			$key               = str_replace("'", "\\'", $key);
			nuRunQuery("INSERT INTO zzsys_small_form_value (zzsys_small_form_value_id, sfv_form_record, sfv_name, sfv_value) VALUES ('$uniq', '$theFormID$this->recordID$ses" . $_GET['ses'] . "', '$key', '$va')");
		}
	}

	$this->searchString        = $theSearchString;
	$this->pageLength          = 10;
	$this->rowHeight           = 20;
	$this->startTime           = time();
	$this->setup               = nuSetup();
	$this->getColumnInfo($this->callingForm->zzsys_form_id);
	$this->defaultJSfunctions();
	if($this->callingForm->sfo_sql_run_before_display != ''){
		$beforeDisplaySQL      = replaceVariablesInString($this->TT,$this->callingForm->sfo_sql_run_before_display, '');
	    	$sqlStatements = array();
	    	$sqlStatements = explode(';', $beforeDisplaySQL);
	    	//---create a tempfile to be used later as object is being built.
	    	for($i = 0 ; $i < count($sqlStatements) ; $i++){
	    		if(trim($sqlStatements[$i]) != ''){
				    nuRunQuery($sqlStatements[$i]);
	    		}
	    	}
	}
	$this->SQL                 = new sqlString(replaceVariablesInString($this->TT,$this->callingForm->sfo_sql, ''));
	$this->buildWhereClause($theSearchString);
	$this->SQL->setWhere($this->pageWhereClause);
	$this->SQL->setOrderBy($this->buildOrderBy());
	$this->pageBody            = $this->buildBody();
	$this->pageHeader          = $this->buildHeader();

}


private function getColumnInfo($formID){

// get properties for all the fields on the browse page
	$t                         = nuRunQuery("SELECT * FROM zzsys_browse WHERE sbr_zzsys_form_id = '$formID' ORDER BY sbr_order");
	while($r                   = db_fetch_object($t)){
		
		if($r->sbr_visible     == '1'){
			$this->Column[]    = $r;
		}
	}
	
}

    public function buildOrderBy(){
    	
    	if($this->orderBy         == ''){
    		$s                     = $this->SQL->DorderBy;
    	}else{

			$s                     = ' ORDER BY ' . $this->Column[$this->orderBy]->sbr_display;
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
				for($ii = 0 ; $ii < count($this->Column) ; $ii++){
					if($this->Column[$ii]->sbr_searchable == '1'){
						if($columnWhere == ''){
							$columnWhere = $columnWhere . " \n$AND (\n(" . $this->Column[$ii]->sbr_display . " $LIKE $dq%" . $searchFor[$i] . '%") ';
							$AND         = ') AND';
						}else{
							$columnWhere = $columnWhere . "\n" . " $ANDOR (" . $this->Column[$ii]->sbr_display . " $LIKE $dq%" . $searchFor[$i] . '%") ';
						}
					}
				}
				$whereClause             = $whereClause . $columnWhere;
			}
			$this->pageWhereClause       = $whereClause . '))';
		}
	}

    public function buildHeader(){

		$dq      = '"';
		$s       =      "$this->CRLF<table width='100%'>$this->CRLF";
		$s       = $s . "<tr><td align='left'><a href='formsmall.php?x=1&f=index&fr=-1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "'>Home</a></td><td align='right'>".$this->callingForm->sfo_title."</td></tr>$this->CRLF";
		$s       = $s . "<tr>$this->CRLF";
		$s       = $s . "<td colspan='3' align='center'>$this->CRLF";
		$s       = $s . "<u>S</u>earch<input name='newsearch' id='newsearch' type='hidden'> <input accesskey='s' onkeyup=$dq newAction();$dq class='searchcolor' name='search' id='search' value='".str_replace('\\"', '"', $_POST['search'])."'/></td></tr>&nbsp;$this->CRLF";
		$s       = $s . "<tr><td colspan='3' align='center'><input type='submit' class='actionButton' value='Search'/>&nbsp;$this->CRLF";
		if($this->callingForm->sfo_add_button    == '1' ){
			$title = iif($this->callingForm->sfo_add_title == '','Add Record',$this->callingForm->sfo_add_title);
			$s   = $s . "<input type='button' class='actionButton' value='$title' accesskey='a' onclick='addThis()'/>&nbsp;$this->CRLF";
		}
		$s       = $s . "</td></tr>$this->CRLF";
		$s       = $s . $this->buildPageNumbers() . "</table>$this->CRLF";

		return $s;

	}


    public function buildPageNumbers(){

		$dq      = '"';
		$s       = $s . "<tr align='center'><td align='center' colspan='2'><b>Page</b>$this->CRLF";

		$this->SQL->removeAllFields();
		$this->SQL->addField($this->callingForm->sfo_primary_key);
		$t       = nuRunQuery($this->SQL->SQL);
		$rows    = ceil(db_num_rows($t) / $this->pageLength);
		if($rows > 15){$rows = 15;}
		for($i   = 1 ; $i <= $rows ; $i++){
			if($i == $this->PageNo){
				$s   = $s . " <font id='Page$i'>&nbsp;<b>$i</b> </font>$this->CRLF";
			}else{

//newPage(pPageNo)
//				$s   = $s . " <a href='browsesmall.php?x=1&s=$this->searchString&f=$this->theFormID&o=$this->orderBy&d=$this->isDescending&p=$i&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&ob=$this->objectID&fr=" . $_GET['fr'] . "'>$i</a>$this->CRLF";
				$s   = $s . " <a href='#' onclick='newPage($i)'>$i</a>$this->CRLF";



			}
		}
		$s       = $s . "</td></tr>$this->CRLF";

		return $s;

	}

    private function displayJavaScript(){

        print "$this->CRLF$this->CRLF<!-- Form Functions -->$this->CRLF";
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
        $s   = $s . "      document.getElementById(rw+i).style.backgroundColor='darkgray';$C";
        $s   = $s . "   } ;$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function MOUT(rw){//---mouse out menu$C";
        $s   = $s . "   for(i = 0 ; i < $div ; i++){ $C";
        $s   = $s . "      document.getElementById(rw+i).style.backgroundColor='';$C";
        $s   = $s . "   } ;$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function PIN(pthis){//--$C";
        $s   = $s . "   document.getElementById(pthis.id).style.backgroundColor='darkgray';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function POUT(pthis){//--$C";
        $s   = $s . "   document.getElementById(pthis.id).style.backgroundColor='white';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);


        $s   =      "function TIN(pthis){ $C";
        $s   = $s . "   document.getElementById(pthis.id).style.backgroundColor='darkgray';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function TOUT(pthis){ $C";
        $s   = $s . "   document.getElementById(pthis.id).style.backgroundColor='';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function newAction(){ $C";
        $s   = $s . "   document.forms[0].action = 'browsesmall.php?x=1&f=$this->theFormID&ob=$this->objectID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] .  "&fr=" . $_GET['fr'] . "&p=1&o=$this->orderBy&d=$this->isDescending';$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function newOrder(pOrderNo, pDesc){ $C";
        $s   = $s . "   document.forms[0].action = 'browsesmall.php?x=1&f=$this->theFormID&ob=$this->objectID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&fr=" . $_GET['fr'] . "&p=1&o='+pOrderNo+'&d='+pDesc;$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function newRecord(){ $C";
        $s   = $s . "   document.forms[0].action = 'browsesmall.php?x=1&f=$this->theFormID&ob=$this->objectID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&fr=" . $_GET['fr'] . "&p=1&o='+pOrderNo+'&d='+pDesc;$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function newPage(pPageNo){ $C";
        $s   = $s . "   document.forms[0].action = 'browsesmall.php?x=1&f=$this->theFormID&ob=$this->objectID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&fr=" . $_GET['fr'] . "&p=' + pPageNo + '&o=$this->orderBy&d=$this->isDescending';$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "}$C";
        $this->appendJSfuntion($s);

        $s   =      "function addThis(){ $C";
        $s   = $s . "   document.forms[0].action = 'formsmall.php?x=1&f=$this->theFormID&fr=-1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "';$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "};$C";
        $this->appendJSfuntion($s);



    }


    public function appendJSfuntion($pValue){
        $this->jsFunctions[]=$pValue;
    }

    private function displayHeader(){

		print "<html>$this->CRLF";
		print "<head>$this->CRLF";
		print "<meta http-equiv='Content-Type' content='text/html;/>$this->CRLF";
		print "<title>".$this->callingForm->sfo_title."</title>$this->CRLF";
		print "<script type='text/javascript' src='common.js' language='javascript'></script>$this->CRLF";

		$this->displayJavaScript();
		
		print "</head>$this->CRLF";
		print "<body>$this->CRLF";
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

            $percentAmount     = 1;
            $select            = '';
            $dq                = '"';
			$this->SQL->removeAllFields();

            $s                 =      "<table >$this->CRLF";
            $s                 = $s . "<tr >$this->CRLF";
            for($i             = 0 ; $i < count($this->Column) ; $i++){
				$this->SQL->addField($this->Column[$i]->sbr_display);
				$a             = align($this->Column[$i]->sbr_align);
				$s             = $s . "$this->TAB<th align='$a' bgcolor='lightgrey'>$this->CRLF";
				$desc          = iif($i == $this->orderBy AND $this->isDescending == '', '1', '');
				$s             = $s . "$this->TAB$this->TAB<a href='#' onclick='newOrder($dq$i$dq, $dq$desc$dq)'>" . $this->Column[$i]->sbr_title . "</a>$this->CRLF";
				$s             = $s . "$this->TAB</th>$this->CRLF";
            }
            
            $s                 = $s . "</tr>$this->CRLF";
            $page              = ($this->PageNo -1) * $this->pageLength;
tofile("$page              = ($this->PageNo -1) * $this->pageLength");
            $this->SQL->addField($this->callingForm->sfo_primary_key);
            $primaryKeyNumber  = count($this->SQL->fields)-1;
            $t                 = nuRunQuery($this->SQL->SQL . " LIMIT $page, 9");
            $top               = -15;
            $row               = 0;
            while($r           = db_fetch_row($t)){
            	  $theID       = $r[$primaryKeyNumber];
                  $rowname     = 'rw'.substr('0'.$row,-2);
                  $param       = '"'.$rowname.'"';
                  $left        = 0;
                  $top         = $top + $this->rowHeight;
				  $s           = $s . "<tr id='browse'>$this->CRLF";
				  $color       = iif($color == 'Gainsboro','white','Gainsboro');
                  for($i       = 0 ; $i < count($this->Column) ; $i++){
                        $w     = $this->Column[$i]->sbr_width * $percentAmount;
                        $a     = align($this->Column[$i]->sbr_align);
                       	$s = $s . "$this->TAB<td bgcolor='$color' align='$a'>$this->CRLF";
                        if($i  == 0){
                        	if($this->isLookup){
                        		$theObject = $this->objectID;
		                        $s = $s . "$this->TAB$this->TAB&nbsp;<a href='formlookupsmall.php?x=1&f=$this->theFormID&ob=$theObject&r=$theID&fr=" . $_GET['fr'] . "&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "'>".formatTextValue($r[$i], $this->Column[$i]->sbr_format)."</a>&nbsp;$this->CRLF";
                        	}else{
		                        $s = $s . "$this->TAB$this->TAB&nbsp;<a href='formsmall.php?x=1&f=$this->theFormID&fr=$theID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "'>".formatTextValue($r[$i], $this->Column[$i]->sbr_format)."</a>&nbsp;$this->CRLF";
                        	}
                        }else{
	                        $s = $s . "$this->TAB$this->TAB&nbsp;".formatTextValue($r[$i], $this->Column[$i]->sbr_format)."&nbsp;$this->CRLF";
                        }
                        $s     = $s . "$this->TAB</td>$this->CRLF";
                  }
                        $s     = $s . "$this->TAB</tr>$this->CRLF";
                  $row         = $row + 1;
            }
            $s                 = $s . '</table>';
            
            return $s;

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



?>
