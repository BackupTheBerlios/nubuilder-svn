<?php
/*
** File:           formsmall.php
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

session_start();

$dir                             = $_GET['dir'];     //-- directory of database.php
$ses                             = $_GET['ses'];     //-- zzsys_session_id in zzsys_session
$f                               = $_GET['f'];       //-- zzsys_form_id in zzsys_form
$r                               = $_GET['r'];       //-- lookup's selected record id
$fr                              = $_GET['fr'];      //-- form's record id
$c                               = $_GET['c'];       //-- cloned record (1=yes)
$delete                          = $_GET['delete'];  //-- ask if you want this record deleted (1=yes)


include("../$dir/database.php");
include('common.php');
$session                         = nuSession($ses);
if($session->foundOK == ''){
	print 'you have been logged out..';
	return;
}

$al                              = $session->sss_access_level;

if($f == 'index' AND $al != 'globeadmin'){
	$inString                    = "'x'";
	$s                           = "SELECT zzsys_object_id FROM zzsys_object ";
	$s                           = $s . "INNER JOIN zzsys_access_level_object ON zzsys_object_id = sao_zzsys_object_id ";
	$s                           = $s . "INNER JOIN zzsys_access_level ON sao_zzsys_access_level_id = zzsys_access_level_id ";
	$s                           = $s . "WHERE sob_zzsys_form_id = 'index' ";
	$s                           = $s . "AND sal_name = '$al' ";
	$ttt                         = nuRunQuery($s);
	
	while($rrr                   = db_fetch_row($ttt)){
		$inString                = "$inString, '$rrr[0]'";
	}
	$inString                    = " AND zzsys_object_id IN($inString) ";
}

$runActivity                     = false;


$nuForm                          = new Form($f, $fr, $c, $delete, $runActivity, $dir, $ses);

$nuForm->access_level            = $_SESSION['access_level'];
$nuForm->session_id              = $_SESSION['session_id'];
$nuForm->zzsys_user_id           = $_SESSION['zzsys_user_id'];
$nuForm->zzsys_user_group_name   = $_SESSION['zzsys_user_group_name'];
$nuForm->inString                = $inString;

$nuForm->setSessionVariables();

$nuForm->execute();



class Form{

    public  $form                = array();
    public  $setup               = array();
    public  $recordValues        = array();
    private $formObjects         = array();
    public  $textObjects         = array();
    public  $subformNames        = array();
    private $jsFunctions         = array();
	public  $actionButtonsHTML   = '';            //---HTML that goes at the top of the form for buttons like Save,Clone etc.
    public  $CRLF                = "\n";
    public  $TAB                 = '    ';
    public  $formID              = '';            //---Primary Key of zzsys_form Table
    public  $recordID            = '';            //---Primary Key of displayed record
    private $subformID           = '';            //---Primary Key of current Subform record
    public  $formsessionID       = '';            //---Form Session ID (unique ID for this instance of this form)
    private $styleSheet          = '';
    private $startTime           = 0;
    private $tabHTML             = '';
    private $logon               = '';
    private $delete              = '';
    public  $cloning             = '';            //---Whether this will be a new record cloned from $formID's record
    public  $access_level        = '';
    public  $zzsys_user_id       = '';
    public  $zzsys_user_login_id = '';
    public  $inString            = '';
    public  $runActivity         = false;
    public  $customDirectory     = '';
    public  $session             = '';
    public  $holdingValues       = false;
	public  $bgcolor             = 'white';
    

    function __construct($theFormID, $theRecordID, $clone, $delete, $runActivity, $dir, $ses){

        $this->startTime         = time();
		$this->customDirectory   = $dir;
		$this->session           = $ses;
    	$this->formsessionID     = uniqid('1');
		$this->formID            = $theFormID;                                //---Primary Key of sysform Table
        $this->form              = formFields($theFormID);
		$this->recordID          = $theRecordID;                              //---ID of displayed record (-1 means a new record)
		if($_GET['r']==''){ //-----no 'r' passed means it hasn't been called from a lookup
			$this->holdingValues = 0;
	        nuRunQuery("DELETE FROM zzsys_small_form_value WHERE sfv_form_record = '$this->formID$this->recordID$this->session'");
		}else{
			$this->holdingValues = 1;
		}
//		$T                       = nuRunQuery("SELECT COUNT(*) FROM zzsys_small_form_value WHERE sfv_form_record = '$theFormID$theRecordID$ses' GROUP BY sfv_form_record");
//		$R                       = db_fetch_row($T);
//		$this->holdingValues     = iif($R[0]=='',0,1);

//----------allow for custom code----------------------------------------------
        if($this->form->sfo_custom_code_run_before_open != ''){
			eval($this->form->sfo_custom_code_run_before_open);
		}
//-----------custom code end---------------------------------------------------

		$this->cloning           = $clone;                                    //---Whether this will be a new record cloned from $formID's record
		$this->delete            = $delete;
        $this->setup             = nuSetup();
		$formID                  = $this->form->zzsys_form_id;
		$formTableName           = $this->form->sfo_table;
		$formPrimaryKey          = $this->form->sfo_primary_key;
		$T                       = nuRunQuery("SELECT * FROM $formTableName WHERE $formPrimaryKey = '$this->recordID'");
		$this->recordValues      = db_fetch_array($T);
		$this->runActivity       = $runActivity;

        $this->defaultJSfunctions();
        $this->createActionButtonsHTML();
    }

    private function displayTable(){

        $TAB                = $this->TAB;
        $CRLF               = $this->CRLF;
		$currentTabTitle    = '';
		$title              = '';
        $tabString          = $tabString . "<table>$CRLF";
        //---create objects
        $t              = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$this->formID' $this->inString ORDER BY sob_all_tab_number, sob_all_tab_title, sob_all_column_number, sob_all_order_number");
        while($object   = db_fetch_object($t)){
			if($currentTabTitle != $object->sob_all_tab_title){
				$currentTabTitle = $object->sob_all_tab_title;
	        	$tabString .= "$this->TAB<tr><td><b>$currentTabTitle</b></td></tr>$this->CRLF";				
			}
            $this->buildObject($object);
        	$tabString .= $this->formObjects[count($this->formObjects)-1]->objectHtml;
        }

		$tabString      = $tabString . "$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='session_id' value='$this->session'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='formsessionID' value='$this->formsessionID'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='framenumber' value='0'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='beenedited' value='0'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='recordID' value='$this->recordID'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='clone' value='$this->cloning'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='close_after_save' value='0'/>$this->CRLF";
        $tabString      = $tabString . "$this->TAB<input type='hidden' name='sfo_refresh_after_save' value='".$this->form->sfo_refresh_after_save."'/>$this->CRLF";
        $tabString      = $tabString . "</table>$CRLF";
        print $tabString;  //--html that displays objects


    }

    public function createActionButtonsHTML(){

    	$dq      = '"';
		$s       =      "<table width='100%'><tr><td align='left'>$this->CRLF";
		$s       = $s . "<a href='browsesmall.php?x=1&f=$this->formID&p=1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "'>Home</a></td><td align='right'>" . $this->form->sfo_title . "</td></tr>$this->CRLF";
		$s       = $s . "<tr><td align='center' colspan='2'>$this->CRLF";
		if($this->form->zzsys_form_id == 'index'){
			$s   = $s . '<font style="font-size:x-large">'.$this->setup->set_title.'</font>';
		}else{
	        if($this->delete == '1'){
				$s   = $s . "Are You Sure? <select name='del_ok' id='del_ok'><option selected value='0'>No don't</option><option value='1'>Yes</option></select>$this->CRLF";
	        }else{
				if($this->form->sfo_save_button    == '1' and displayCondition($this->form->sfo_save_button_display_condition, $this->recordID, $this->recordValues)){
					$title = iif($this->form->sfo_save_title == '','Save',$this->form->sfo_save_title);
					$s   = $s . "<input type='button' class='actionButton' value='$title' accesskey='s' onclick='SaveThis(0)' id='save_button'/>&nbsp;$this->CRLF";
				}
				if($this->form->sfo_close_button    == '1' and displayCondition($this->form->sfo_close_button_display_condition, $this->recordID, $this->recordValues)){
					$title = iif($this->form->sfo_close_title == '','Save & Close',$this->form->sfo_close_title);
					$s   = $s . "<input type='button' class='actionButton' value='$title' accesskey='s' onclick='SaveThis(1)' id='save_button'/>&nbsp;$this->CRLF";
				}
				if($this->form->sfo_clone_button   == '1' and $this->recordID <> '-1' and $this->cloning <> '1' and displayCondition($this->form->sfo_clone_button_display_condition, $this->recordID, $this->recordValues)){
					$title = iif($this->form->sfo_clone_title == '','Clone',$this->form->sfo_clone_title);
					$s   = $s . "<input type='button' class='actionButton' value='$title' onclick='CloneThis()' id='clone_button'/>&nbsp;$this->CRLF";
				}
				$t       = nuRunQuery("SELECT * FROM zzsys_form_action WHERE sfa_zzsys_form_id = '".$this->form->zzsys_form_id."'");
				while($r = db_fetch_object($t)){
					if(displayCondition($r->sfa_button_display_condition, $this->recordID, $this->recordValues)){
						$s   = $s . "<input type='button' class='actionButton' value='$r->sfa_button_title' onclick=$dq$r->sfa_button_javascript$dq/>&nbsp;$this->CRLF";
					}
				}
	        }
	
			if($this->form->sfo_delete_button  == '1' and $this->recordID <> '-1' and $this->cloning <> '1' and displayCondition($this->form->sfo_delete_button_display_condition, $this->recordID, $this->recordValues)){
				$title   = iif($this->form->sfo_delete_title == '','Delete',$this->form->sfo_delete_title);
				$s       = $s . "<input type='button' class='actionButton' value='$title' onclick='DeleteThis()' id='delete_button'/>&nbsp;$this->CRLF";
			}
		}
		$s               = $s . "</td></tr><tr><td bgcolor='black' colspan='2'>.</td></tr></table>$this->CRLF";
		$this->actionButtonsHTML = $s;

	}


    public function createActivityButtonHTML(){

		$t                       = nuRunQuery("SELECT * FROM zzsys_activity WHERE zzsys_activity_id = '$this->recordID'");
		$r                       = db_fetch_object($t);
		$dq                      = '"';
		$s                       = '';
	
    	if($r->sat_all_type      == 'report'){
			$s                   = "<input type='button' class='actionButton' value='Print' onclick='printIt($dq$this->recordID$dq)'/>&nbsp;$this->CRLF";
    	}
    	if($r->sat_all_type      == 'procedure'){
			$s                   = "<input type='button' class='actionButton' value='Run' onclick='runIt($dq$this->recordID$dq)'/>&nbsp;$this->CRLF";
    		
    	}
    	if($r->sat_all_type      == 'export'){
			$s                   = "<input type='button' class='actionButton' value='Export' onclick='exportIt($dq$this->recordID$dq)'/>&nbsp;$this->CRLF";
    		
    	}
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

	public function setSessionVariables(){

        $C   = $this->CRLF;
        $s   =      "function customDirectory(){ $C";
        $s   = $s . "   return '$this->customDirectory';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function session_id(){ //-- id that remains the same until logout$C";
        $s   = $s . "   return '$this->session';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function form_session_id(){ //--just for this instance of this form$C";
        $s   = $s . "   return '$this->formsessionID';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function access_level(){ $C";
        $s   = $s . "   return '$this->access_level';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function zzsys_user_id(){ $C";
        $s   = $s . "   return '$this->zzsys_user_id';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function nusmall(){ $C";
        $s   = $s . "   return true;$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function zzsys_user_group_name(){ $C";
        $s   = $s . "   return '$this->zzsys_user_group_name';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function sd(){ $C";
        $s   = $s . "   return '" . $_SESSION['customDirectory'] . "';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

	}

    private function defaultJSfunctions(){

        $C   = $this->CRLF;
		if($this->form->sfo_javascript != ''){
	        $this->appendJSfunction("$C//---- start of custom javascript ----$C$C".$this->form->sfo_javascript."$C$C//---- end of custom javascript ----");
		}
		$this->checkBlanks();

        $s   =      "function MIN(pthis){//---mouse over menu$C";
        $s   = $s . "      document.getElementById(pthis.id).style.color='silver';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function MOUT(pthis){//---mouse out menu$C";
        $s   = $s . "   document.getElementById(pthis.id).style.color='white';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function getCal(theID){//---open calendar$C";

        $s   = $s . "   var theDay      = new String(document.getElementById('theform')[theID].value)$C";
        $s   = $s . "   var theFormat   = new String(document.getElementById('theform')[theID].accept);$C";
        $s   = $s . "   openCalendar(theID, theDay, theFormat);$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function reformat(pthis){ $C";
        $s   = $s . "   if(aType[txtFormat[pthis.id]]=='date'){;$C";
        $s   = $s . "      reformat(pthis);$C";
        $s   = $s . "   }$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function getframe(){//---which hidden frame to use$C";
        $s   = $s . "   var theframe = document.getElementById('framenumber').value*1;$C";
        $s   = $s . "      if(theframe == 4){;$C";
        $s   = $s . "         theframe = 0;$C";
        $s   = $s . "      }else{ $C";
        $s   = $s . "         theframe = theframe + 1;$C";
        $s   = $s . "      }$C";
        $s   = $s . "   document.getElementById('framenumber').value = theframe;$C";
        $s   = $s . "   return 'hide' + theframe;$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function getvaluesurl(pthis){//---url used to save list to database$C";
        $s   = $s . "   var url      = 'getlist.php?x=1&type=list&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&id='+document.getElementById('formsessionID').value+'&name='+pthis.id;$C";
        $s   = $s . "   var theframe = getframe();$C";
        $s   = $s . "   parent.frames[theframe].document.location = url;$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function SaveThis(pclose){//---save record$C";
        $s   = $s . "   var vMessage = '';$C";
        $s   = $s . "   vMessage     = noblanks();$C";
        $s   = $s . "   if(vMessage != ''){;$C";
        $s   = $s . "      alert(vMessage);$C";
        $s   = $s . "      return;$C";
        $s   = $s . "   }$C";
        $s   = $s . "   if(window.nuBeforeSave){;$C";
        $s   = $s . "      if(!nuBeforeSave()){;$C";
        $s   = $s . "         return;$C";
        $s   = $s . "      };$C";
        $s   = $s . "   };$C";

        if($this->cloning == '1'){
	        $s   = $s . "   document.forms[0].action = 'formupdatesmall.php?x=1&fr=-1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&f=$this->formID';$C";
        }else{
 	        $s   = $s . "   document.forms[0].action = 'formupdatesmall.php?x=1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&fr=$this->recordID&f=$this->formID';$C";
        }
        $s   = $s . "   document.forms[0].submit();$C";

        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function CloneThis(pthis){//---save record$C";
        $s   = $s . "   if(window.nuBeforeClone){;$C";
        $s   = $s . "      if(!nuBeforeClone()){;$C";
        $s   = $s . "         return;$C";
        $s   = $s . "      };$C";
        $s   = $s . "   };$C";
        if($this->cloning == '1'){
        	$recordID = '-1';
		}else{
        	$recordID = $this->recordID;
		}
        $s   = $s . "   document.forms[0].action = 'formsmall.php?x=1&c=1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&fr=$recordID&f=$this->formID';$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);


        $s   =      "function lookupSmall(pthis, pObject){//---lookup record$C";
        $s   = $s . "   document.forms[0].action = 'browsesmall.php?x=1&f=" . $_GET['f'] . "&fr=" . $_GET['fr'] . "&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&p=1&ob='+pObject;$C";
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function CheckMenu(pItem){//---load form$C";
        $s   = $s . "   if(window.nuClickMenu){;$C";
        $s   = $s . "      if(!nuClickMenu(pItem)){;$C";
        $s   = $s . "         return;$C";
        $s   = $s . "      };$C";
        $s   = $s . "   };$C";
        $s   = $s . "   showTab(pItem);$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function LoadThis(){//---load form$C";
//        $s   = $s . "   showTab(0);$C";
        $s   = $s . "   if(window.nuLoadThis){;$C";
        $s   = $s . "      nuLoadThis();$C";
        $s   = $s . "   };$C";
//--start cursor
		$t   = nuRunQuery("SELECT sob_all_name FROM zzsys_object WHERE sob_all_start_cursor = '1' AND sob_zzsys_form_id = '" . $this->form->zzsys_form_id . "'");
		$r   = mysql_fetch_object($t);
		if($r->sob_all_name != ''){
	        $s   = $s . "   document.forms[0]['$r->sob_all_name'].focus();$C";
		}
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function DeleteThis(pthis){//---delete record$C";
        $s   = $s . "   if(window.nuBeforeDelete){;$C";
        $s   = $s . "      if(!nuBeforeDelete()){;$C";
        $s   = $s . "         return;$C";
        $s   = $s . "      };$C";
        $s   = $s . "   };$C";
        if($this->delete == '1'){
	        $s   = $s . "   document.forms[0].action = 'formdeletesmall.php?x=1&r=$recordID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&f=$this->formID';$C";
        }else{
	        $s   = $s . "   document.forms[0].action = 'formsmall.php?x=1&delete=1&r=$recordID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&f=$this->formID';$C";
        }
        $s   = $s . "   document.forms[0].submit();$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function hoverMenu(){ $C";
        $s   = $s . "   return 'green';$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function selectedMenu(){ $C";
        $s   = $s . "   return 'C0C0C0';$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function CheckIfSaved(){ $C";
        if(!$this->runActivity){
	        $s   = $s . "   if(document.getElementById('beenedited').value=='1'){ $C";
	        $s   = $s . "       event.returnValue = 'This record has NOT been saved.. (Click CANCEL to save this record)';$C";
	        $s   = $s . "   };$C";
        }
        $s   = $s . "};$C";
        $this->appendJSfunction($s);
        
        $s   =      "function untick(pBox){ $C";
        $s   = $s . "   document.getElementById('row'+pBox).checked = false;$C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);
        

    }
    
	private function checkBlanks(){
		$dq                        = '"';
		$a                         = array();
        $t                         = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$this->formID' ORDER BY IF(sob_all_type = 'subform', 1,0)");
        while($r                   = db_fetch_array($t)){
       		$fieldName             = $r['sob_all_name'];
       		$fieldTitle            = $r['sob_all_title'];
       		if($r['sob_'.$r['sob_all_type'].'_no_blanks'] == '1' OR $r['sob_'.$r['sob_all_type'].'_no_duplicates'] == '1'){
//        		$a[]           = "   if(document.getElementById('$fieldName').value == ''){return $dq'$fieldTitle' cannot be left blank$dq;}$this->CRLF";
//        		$a[]           = "   if(document.getElementById('theform').$fieldName.value == ''){return $dq'$fieldTitle' cannot be left blank$dq;}$this->CRLF";
        		$a[]           = "   if(document.theform.$fieldName.value == ''){return $dq'$fieldTitle' cannot be left blank$dq;}$this->CRLF";
       		}
        }

        $s                    =      "$this->CRLF"."function noblanks(){     $this->CRLF";
        if(count($a)>0){
	   		$s                    = $s . "   var rno          = '' $this->CRLF";
	   		$s                    = $s . "   var checkBox     = '' $this->CRLF";
	   		$s                    = $s . "   var checkField   = '' $this->CRLF$this->CRLF";
	        for($i = 0 ; $i < count($a) ; $i++){
		        $s                = $s . $a[$i];
	        }
        }
        $s                    = $s . "   return '';            $this->CRLF";
        $s                    = $s . "}                        $this->CRLF";

        $this->appendJSfunction($s);
	}

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

		$stylesheetLocation = '../' . $_SESSION['customDirectory'] . '/style.css';
		print "</head>$this->CRLF";
        print "<body onload='LoadThis()' onbeforeunload='CheckIfSaved()'>$this->CRLF";
        print "<form name='theform' id='theform' action='' method='post'>$this->CRLF";
    }

    private function displayFooter(){

        print  "$this->CRLF</form>$this->CRLF</body>$this->CRLF</html>$this->CRLF$this->CRLF";
        $build = time()- $this->startTime;
        print  "<!--built in $build seconds-->";

    }


    private function buildObject($o){
		$this->bgcolor                 = iif($this->bgcolor == 'Gainsboro','white','Gainsboro');
        $formObject                    = new formObject($this, $this->recordID);

		$formObject->bgcolor           = $this->bgcolor;
		$formObject->parentType        = 'form';
		$formObject->formsessionID     = $this->formsessionID;
        $formObject->setObjectProperties($o);
        
        //---decide whether to show or hide this object
        $formObject->displayThisObject = displayCondition($o->sob_all_display_condition, $this->recordID, $this->recordValues);
        //---get the value and format it
        if($this->holdingValues==1){
	        $formObject->objectValue                      = $this->holdingValue($this->formID.$this->recordID.$this->session, $formObject->objectProperty['sob_all_name']);
	        if($formObject->objectProperty['sob_all_type']=='lookup'){
				$formObject->lookupArray['id']            = $this->holdingValue($this->formID.$this->recordID.$this->session, $formObject->objectProperty['sob_all_name']);

//-------


            //--get sql from lookup form
            $lookupFormID         = $formObject->objectProperty['sob_lookup_zzsysform_id'];
            $t                    = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$lookupFormID'");
            $r                    = db_fetch_object($t);

            $TT                   = $this->TT;
            $browseTable          = $TT;
            eval($r->sfo_custom_code_run_before_browse);

            $SQLwithGlobalValues  = replaceVariablesInString($this->TT, $r->sfo_sql, '');
            $SQLwithRecordValues  = getSqlRecordValues($SQLwithGlobalValues, $this->parentForm->recordAndFieldValues);
            $SQL                  = new sqlString($SQLwithRecordValues);


//-------

//				$SQL                                      = new sqlString(replaceVariablesInString($formObject->TT,$formObject->objectProperty['sob_lookup_sql'], ''));
				$SQL->setWhere("WHERE ".$formObject->objectProperty['sob_lookup_id_field']." = '$formObject->objectValue'");
				$SQL->removeAllFields();
				$SQL->addField($formObject->objectProperty['sob_lookup_id_field']);
				$SQL->addField($formObject->objectProperty['sob_lookup_code_field']);
				$SQL->addField($formObject->objectProperty['sob_lookup_description_field']);
				$T                                        = nuRunQuery($SQL->SQL);
				$R                                        = db_fetch_row($T);
				$formObject->lookupArray['id']            = $R[0];
				$formObject->lookupArray['code']          = $R[1];
				$formObject->lookupArray['description']   = $R[2];
	        }
        }else{
	        $formObject->objectValue   = $formObject->setValue($this->recordID, $this->cloning, $this->recordValues[$formObject->objectProperty['sob_all_name']]);
        }
        //---create the html string that will display it
		$formObject->objectHtml        = $formObject->buildObjectHTML($this->CRLF, $this->TAB.$this->TAB.$this->TAB.$this->TAB.$this->TAB,'');
		$this->formObjects[]           = $formObject;
		nuRunQuery("DROP TABLE IF EXISTS $formObject->TT");
    }

    public function holdingValue($pFormRecord, $pName){
    	
		$t                             = nuRunQuery("SELECT sfv_value FROM zzsys_small_form_value WHERE sfv_form_record = '$pFormRecord' AND sfv_name = '$pName'");
		$r                             = db_fetch_row($t);
		return $r[0];
    }

    public function execute(){

        $this->appendJSfunction(setFormatArray());
        $this->displayHeader();
		print $this->actionButtonsHTML;
        $this->displayTable();
        $this->displayFooter();
        nuRunQuery("DELETE FROM zzsys_small_form_value WHERE sfv_form_record = '$this->formID$this->recordID$this->session'");
        
    }

}


class formObject{

	public  $objectProperty      = array();
	public  $lookupArray         = array();
	public  $objectValue         = '';
	public  $objectHTML          = '';
    public  $subformObjects      = array();
    public  $recordValues        = array();
	public  $displayThisObject   = true;
	public  $parentType          = '';
	private $parentForm          = null;
	public  $formsessionID       = '';
	public  $recordID            = '';
	private $subformRowNumber    = 0;
	public  $subformPrefix       = '';
	public  $objectName          = '';
	public  $TT                  = '';
	public  $bgcolor             = '';

    function __construct(Form $form, $pRecordID){

    	$this->parentForm        = $form;
    	$this->recordID          = $pRecordID;
    	$this->TT                = TT();           //---temp table name

    }

	public function nextRowNumber(){
		$this->subformPrefix     = $this->objectName.substr('000'.$this->subformRowNumber,-4);
		$this->subformRowNumber  = $this->subformRowNumber + 1;
	}

    public function setObjectProperties($o){

		reset($o);
		while(list($key, $value)                    = each($o)){
			$this->objectProperty[$key] = $value;
		}
//  Run sql statement(s) that are run before object is created 
// -ideal for creating a temp file that will be used by a dropdown.
 		
		if($o->sob_all_sql_run_before_display != ''){
			//---replace any hashes with variables
	    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_all_sql_run_before_display'], $this->recordID);
	    	$sqlStatements = array();
	    	$sqlStatements = explode(';', $sql);
	    	//---create a tempfile to be used later as object is being built.
	    	for($i = 0 ; $i < count($sqlStatements) ; $i++){
	    		if(trim($sqlStatements[$i]) != ''){
				    nuRunQuery($sqlStatements[$i]);
	    		}
	    	}
		}

    }

	public function setValue($pRecordID, $pClone, $pValue){


		$type         = $this->objectProperty['sob_all_type'];
		if ($type    == 'display'){
			//---replace any hashes with variables
	    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_display_sql'], $this->recordID);
	    	//---run sql and use the first row and colomn as the value for this field
		    $t        = nuRunQuery($sql);
		    $r        = db_fetch_row($t);
	    	return formatTextValue($r[0], $this->objectProperty['sob_display_format']);
    	}
		if ($type    == 'dropdown'){
	    	if($pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
				//---get default value from sob_text_default_value_sql
				if($this->objectProperty['sob_dropdown_default_value_sql']==''){
			    	return '';
				}
				//---replace any hashes with variables
		    	$sql   = replaceVariablesInString($this->TT,$this->objectProperty['sob_dropdown_default_value_sql'], $this->recordID);
		    	//---run sql and use the first row and colomn as the default value for this field
			    $t     = nuRunQuery($sql);
			    $r     = db_fetch_row($t);
		    	return $r[0];
			}
	    	return $pValue; //---return value already in record
    	}
		if ($type    == 'lookup'){
	    	if($pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
				//---get default value from sob_text_default_value_sql
				if($this->objectProperty['sob_lookup_default_value_sql']==''){
					$this->lookupArray['id']            = '';
					$this->lookupArray['code']          = '';
					$this->lookupArray['description']   = '';
			    	return '';
				}
				
				$defaultValueSQL  = replaceVariablesInString('',$this->objectProperty['sob_lookup_default_value_sql'], '');
			    $T                = nuRunQuery($defaultValueSQL);
			    $R                = db_fetch_row($T);
			    $pValue           = $R[0];
	    	}
            
            
//-------            

            //--get sql from lookup form
            $lookupFormID         = $this->objectProperty['sob_lookup_zzsysform_id'];
            $t                    = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$lookupFormID'");
            $r                    = db_fetch_object($t);

            $TT                   = $this->TT;
            $browseTable          = $TT;
            eval($r->sfo_custom_code_run_before_browse);

            $SQLwithGlobalValues  = replaceVariablesInString($this->TT, $r->sfo_sql, '');
            $SQLwithRecordValues  = getSqlRecordValues($SQLwithGlobalValues, $this->parentForm->recordAndFieldValues);
            $SQL                  = new sqlString($SQLwithRecordValues);
            
            
//-------            
            
//			$SQL                                = new sqlString(replaceVariablesInString($this->TT,$this->objectProperty['sob_lookup_sql'], ''));
tofile('sql step 1=='.$this->objectProperty['sob_lookup_sql']);
			$SQL->setWhere("WHERE ".$this->objectProperty['sob_lookup_id_field']." = '$pValue'");
			$SQL->removeAllFields();
			$SQL->addField($this->objectProperty['sob_lookup_id_field']);
			$SQL->addField($this->objectProperty['sob_lookup_code_field']);
			$SQL->addField($this->objectProperty['sob_lookup_description_field']);
			$T = nuRunQuery($SQL->SQL);
tofile('step 111');
			$R = db_fetch_row($T);
			$this->lookupArray['id']            = $R[0];
			$this->lookupArray['code']          = $R[1];
			$this->lookupArray['description']   = $R[2];
	    	return $pValue; //---return value already in record
    	}
		if ($type    == 'inarray'){
	    	if($pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
				//---get default value from sob_text_default_value_sql
				if($this->objectProperty['sob_inarray_default_value_sql']==''){
			    	return '';
				}
				//---replace any hashes with variables
		    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_inarray_default_value_sql'], $this->recordID);
		    	//---run sql and use the first row and column as the default value for this field
			    $t        = nuRunQuery($sql);
			    $r        = db_fetch_row($t);
		    	return $r[0];
			}
	    	return $pValue; //---return value already in record
    	}
		if ($type    == 'password'){
	    	return $pValue;
    	}
		if ($type    == 'text'){
	    	if($pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
				//---get default value from sob_text_default_value_sql
				if($this->objectProperty['sob_text_default_value_sql']==''){
			    	return formatTextValue('', $this->objectProperty['sob_text_format']);
				}
				//---replace any hashes with variables
		    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_text_default_value_sql'], $this->recordID);
		    	//---run sql and use the first row and colomn as the default value for this field
			    $t        = nuRunQuery($sql);
			    $r        = db_fetch_row($t);
		    	return formatTextValue($r[0], $this->objectProperty['sob_text_format']);
			}
	    	return formatTextValue($pValue, $this->objectProperty['sob_text_format']); //---return value already in record
    	}
		if ($type    == 'textarea'){
	    	if($pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
				//---get default value from sob_text_default_value_sql
				if($this->objectProperty['sob_textarea_default_value_sql']==''){
			    	return '';
				}
				//---replace any hashes with variables
		    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_textarea_default_value_sql'], $this->recordID);
		    	//---run sql and use the first row and colomn as the default value for this field
			    $t        = nuRunQuery($sql);
			    $r        = db_fetch_row($t);
		    	return $r[0];
			}
	    	return $pValue; //---return value already in record
    	}

	}

	private function addProperty($pName, $pValue){
		if($pValue==''){return;}
		return $pName . '="' . $pValue. '" ';
	}

	private function addStyle($pName, $pValue){
		return "$pName:$pValue;";
	}

	public function buildObjectHTML($CRLF, $TAB, $PREFIX){

		$dq          = '"';
		$fieldName   = $PREFIX.$this->objectProperty['sob_all_name'];

		$fieldTitle  = htmlentities($this->objectProperty['sob_all_title']);
		$fieldValue  = htmlentities($this->objectValue);
		$type        = $this->objectProperty['sob_all_type'];
		setnuVariable($this->formsessionID, nuDateAddDays(Today(),2), $fieldName, $this->objectValue);
		if ($type    == 'button'){
    		$htmlString   = $this->buildHTMLForButton($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue);
    	}
		if ($type    == 'display'){
    		$htmlString   = $this->buildHTMLForDisplay($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue);
    	}
		if ($type    == 'dropdown'){
    		$htmlString   = $this->buildHTMLForDropdown($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX);
    	}
		if ($type    == 'graph'){
    		$htmlString   = $this->buildHTMLForGraph($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue);
    	}
		if ($type    == 'image'){
    		$htmlString   = $this->buildHTMLForImage($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue);
    	}
		if ($type    == 'inarray'){
    		$htmlString   = $this->buildHTMLForInarray($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX);
    	}
		if ($type    == 'listbox'){
    		$htmlString   = $this->buildHTMLForListbox($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue);
    	}
		if ($type    == 'lookup'){
    		$htmlString   = $this->buildHTMLForLookup($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX);
    	}
		if ($type    == 'text'){
    		$htmlString   = $this->buildHTMLForText($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX);
    	}
		if ($type    == 'textarea'){
    		$htmlString   = $this->buildHTMLForTextarea($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX);
    	}
		if ($type    == 'words'){
    		$htmlString   = $this->buildHTMLForWords($CRLF, $TAB);
    	}
		return $htmlString;

	}

	private function buildHTMLForText($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

	    if ($this->displayThisObject){
	    	
	    	if($this->objectProperty['sob_text_password'] == '1'){
		    	$inputType            = 'password';
	    	}else{
		    	$inputType            = 'text';
		    	$textFormat           = $this->objectProperty['sob_text_format'];
	    	}
	    }else{
	    	$inputType            = 'hidden';
	    	$fieldTitle           = '';
		}

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' bgcolor='$this->bgcolor' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}else{
			$rowPrefix            = $this->subformRowNumber;
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}

		$style =          $this->addStyle('width', $this->objectProperty['sob_text_length']*16);
		$style = $style . $this->addStyle('text-align', align($this->objectProperty['sob_text_align']));

		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<input ";
		$s     = $s . $this->addProperty('type'           , $inputType);
		$s     = $s . $this->addProperty('accept'         , $textFormat);
		$s     = $s . $this->addProperty('name'           , $this->subformPrefix.$fieldName);
		$s     = $s . $this->addProperty('id'             , $this->subformPrefix.$fieldName);

		$s     = $s . $this->addProperty('value'          , $fieldValue);
		$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);

		if($inputType == 'text'){
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , $this->objectProperty['sob_all_class']);
			}
			$s = $s . $this->addProperty('style'          , $style);
//			$s = $s . $this->addProperty('onchange'       , $untick."uDBsmall(this,'text');".$this->objectProperty['sob_all_on_change']);
			$s = $s . $this->addProperty('onchange'       , $untick."nuFormat(this);".$this->objectProperty['sob_all_on_change']);
			$s = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_keypress']);
			$s = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_doubleclick']);
		}
		if($this->objectProperty['sob_text_read_only']=='1'){
			$s = $s . " readonly='readonly' ";
		}

		$s     = $s . "/>$CRLF";
		$format=textFormatsArray();

		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}

	private function buildHTMLForDisplay($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue){

	    if ($this->displayThisObject){
	    	$inputType            = 'text';
	    }else{
	    	$inputType            = 'hidden';
	    	$fieldTitle           = '';
		}

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' bgcolor='$this->bgcolor' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}

		$style =          $this->addStyle('width', $this->objectProperty['sob_display_length']*16);
		$style = $style . $this->addStyle('text-align', align($this->objectProperty['sob_display_align']));

		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<input ";
		$s     = $s . $this->addProperty('type'           , $inputType);
		$s     = $s . $this->addProperty('name'           , $fieldName);
		$s     = $s . $this->addProperty('id'             , $fieldName);
		$s     = $s . $this->addProperty('value'          , $fieldValue);
		$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);

		if($inputType == 'text'){
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , $this->objectProperty['sob_all_class']);
			}
			$s = $s . $this->addProperty('style'          , $style);
		}

		$s     = $s . " readonly='readonly'  tabindex='-1'/>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}

	private function buildHTMLForButton($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue){

	    if (!$this->displayThisObject){
	    	return '';
		}

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected'></td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}

		$style =      $this->addStyle('width', $this->objectProperty['sob_button_length']*16);
		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<input ";
		$s     = $s . $this->addProperty('type'           , 'button');
		$s     = $s . $this->addProperty('name'           , $this->objectProperty['sob_all_name']);
		$s     = $s . $this->addProperty('id'             , $this->objectProperty['sob_all_name']);
		$s     = $s . $this->addProperty('value'          , $this->objectProperty['sob_all_title']);
		$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
		if($this->objectProperty['sob_button_zzsys_form_id']==''){
      		$s = $s . $this->addProperty('onclick'        , $this->objectProperty['sob_all_on_double_click']);
		}else{
			$f = $this->objectProperty['sob_button_zzsys_form_id'];
			$b = $this->objectProperty['sob_button_browse_filter'];
      		$s = $s . $this->addProperty('onclick'        , "document.forms[0].action ='browsesmall.php?x=1&f=$f&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&p=1';document.forms[0].submit()");
//      		$s = $s . $this->addProperty('onclick'        , "openBrowse('$f', '$b')");
		}
		if($this->objectProperty['sob_all_class']==''){
		    $s = $s . $this->addProperty('class'          , 'button');
		}else{
		    $s = $s . $this->addProperty('class'          , $this->objectProperty['sob_all_class']);
		}
		$s     = $s . $this->addProperty('style'          , $style);
		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}

	private function buildHTMLForDropdown($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' bgcolor='$this->bgcolor' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}

	    if (!$this->displayThisObject){
	    	$s = "$TAB  <input type='hidden' name='fieldName' value='$fieldValue'/>$CRLF";
		}else{
			$style =      $this->addStyle('width', $this->objectProperty['sob_dropdown_length']*16);
			$s     =      $titleTableDetail . $td1;
			$s     = $s . '<select ';
			$s     = $s . $this->addProperty('name'           , $fieldName);
			$s     = $s . $this->addProperty('id'             , $fieldName);
			$s     = $s . $this->addProperty('value'          , $fieldValue);
			$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , $this->objectProperty['sob_all_class']);
			}
			$s     = $s . $this->addProperty('style'          , $style);
			$s     = $s . $this->addProperty('onchange'       , $untick."uDB(this);".$this->objectProperty['sob_all_on_change']);
			$s     = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s     = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s     = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
			$s     = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_double_click']);
			if($this->objectProperty['sob_text_read_only']=='1'){
				$s = $s . " readonly='readonly' ";
			}

			$s     = $s . ">$CRLF";


			//---replace any hashes with variables
	    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_dropdown_sql'], $this->recordID);
	    	//---run sql and use the first row and colomn as the value for this field
		    $t        = nuRunQuery($sql);
		    	$s    = $s . "$TAB        <option value=''></option>$CRLF";
		    while($r  = db_fetch_row($t)){
				if($r[0] == $fieldValue){
			    	$s    = $s . "$TAB        <option selected value='$r[0]'>$r[1]</option>$CRLF";
				}else{
			    	$s    = $s . "$TAB        <option value='$r[0]'>$r[1]</option>$CRLF";
				}
			}



		}
		$s     = $s . "$TAB</select>$CRLF$td2";

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForGraph($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue){

	    if (!$this->displayThisObject){
	    	return '';
		}
    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}else{
			$rowPrefix            = $this->subformRowNumber;
		}

		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<img alt='' ";
		$s     = $s . $this->addProperty('src'         , '../' . $_SESSION['customDirectory'] . '/custom/' . $this->objectProperty['sob_graph_file_name']);
		$s     = $s . $this->addProperty('id'          , $rowPrefix.$fieldName);
		$s     = $s . $this->addProperty('onclick'     , $this->objectProperty['sob_all_on_double_click']);
		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}



	private function buildHTMLForImage($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue){

	    if (!$this->displayThisObject){
	    	return '';
		}
    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}else{
			$rowPrefix            = $this->subformRowNumber;
		}

		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<img alt='' ";
		$s     = $s . $this->addProperty('src'         , $this->objectProperty['sob_image_file_name']);
		$s     = $s . $this->addProperty('id'          , $rowPrefix.$fieldName);
		$s     = $s . $this->addProperty('onclick'     , $this->objectProperty['sob_all_on_double_click']);
		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForInarray($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

	    if ($this->displayThisObject){
	    	$inputType            = 'text';
	    }else{
	    	$inputType            = 'hidden';
	    	$fieldTitle           = '';
		}

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' bgcolor='$this->bgcolor' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}

		$style =          $this->addStyle('width', $this->objectProperty['sob_inarray_length']*16);
		$style = $style . $this->addStyle('text-align', $this->objectProperty['sob_inarray_align']);

		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<input ";
		$s     = $s . $this->addProperty('type'           , $inputType);
		$s     = $s . $this->addProperty('name'           , $fieldName);
		$s     = $s . $this->addProperty('id'             , $fieldName);
		$s     = $s . $this->addProperty('value'          , $fieldValue);
		$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);

		if($inputType == 'text'){
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , $this->objectProperty['sob_all_class']);
			}
			$s = $s . $this->addProperty('style'          , $style);
			$s = $s . $this->addProperty('onchange'       , $untick."uDB(this);".$fieldName.'_array(this);'.$this->objectProperty['sob_all_onchange']);
			$s = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_keypress']);
			$s = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_doubleclick']);
		}
		if($this->objectProperty['sob_text_read_only']=='1'){
			$s = $s . " readonly='readonly' ";
		}

		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;



		//---replace any hashes with variables
    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_inarray_sql'], $this->recordID);
    	//---run sql and use the first row and colomn as the value for this field
	    $t        = nuRunQuery($sql);

	    $fun      = "function $fieldName"."_array(pThis){ $CRLF";
	    $fun      = $fun . "   var ar    = new Array();$CRLF";
	    $fun      = $fun . "   var found = new Boolean(false);$CRLF";
	    $counter  = 0;
	    while($r  = db_fetch_row($t)){
	    	if($counter == 0){
				$first  = $r[0];
			}
			$last = $r[0];
		    $fun  = $fun . "   ar[$counter] = \"$r[0]\";$CRLF";
		    $counter = $counter + 1;
		}

		$dq = '"';
	    $fun      = $fun . "   $CRLF";
	    $fun      = $fun . "   for (i=0 ; i < ar.length ; i++){ $CRLF";
	    $fun      = $fun . "      if(ar[i] == pThis.value){ $CRLF";
	    $fun      = $fun . "         found=true;$CRLF";
	    $fun      = $fun . "      }$CRLF";
	    $fun      = $fun . "   }$CRLF";
	    $fun      = $fun . "   if(found==false){ $CRLF";
	    $fun      = $fun . "      alert('Must be between $dq$first$dq and $dq$last$dq')$CRLF";
	    $fun      = $fun . "      pThis.value = '';$CRLF";
	    $fun      = $fun . "   }$CRLF";
	    $fun      = $fun . "}$CRLF";

    	$this->parentForm->appendJSfunction($fun);

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForLookup($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' bgcolor='$this->bgcolor' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}
		$s                        = $titleTableDetail . $td1;
    	$s                        = $s . "$TAB  <input name='$fieldName' id='$fieldName' type='hidden' value='".htmlentities($this->lookupArray['id'])."'/>$CRLF";

	    if ($this->displayThisObject){

			$ob   = $this->objectProperty['zzsys_object_id'];

			$s     = $s . "$TAB  <input ";
			$s     = $s . $this->addProperty('name'           , 'code'.$fieldName);
			$s     = $s . $this->addProperty('id'             , 'code'.$fieldName);
			$s     = $s . $this->addProperty('value'          , htmlentities($this->lookupArray['code'] . '  -  ' . $this->lookupArray['description']));
			if($this->objectProperty['sob_text_read_only']=='1'){
				$s = $s . " readonly='readonly' ";
			}
			$s     = $s . "/>$CRLF";
			$s     = $s . "$TAB  <input type='button' value ='...' onclick='lookupSmall(this, \"$ob\")'";
			$s     = $s . "/>$CRLF";

			$s = $s . $td2;

		}

		return $tr1.$s.$tr2;
	}

	private function buildHTMLForTextarea($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

	    if ($this->displayThisObject){
	    	$inputType            = 'password';
	    }else{
	    	$inputType            = 'hidden';
	    	$fieldTitle           = '';
		}

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' bgcolor='$this->bgcolor' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}

		$style =          $this->addStyle('width', $this->objectProperty['sob_textarea_length']*16);

		$s     =      $titleTableDetail . $td1;
		$s     = $s . "<textarea ";
		$s     = $s . $this->addProperty('name'           , $fieldName);
		$s     = $s . $this->addProperty('id'             , $fieldName);
		$s     = $s . $this->addProperty('cols'           , $this->objectProperty['sob_textarea_length']);
		$rows  = round($this->objectProperty['sob_textarea_height'])-1;
		$s     = $s . $this->addProperty('rows'           , $rows);
		$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , $this->objectProperty['sob_all_class']);
			}
		$s     = $s . $this->addProperty('style'          , $style);
		$s     = $s . $this->addProperty('onchange'       , $untick."uDB(this);".$this->objectProperty['sob_all_on_change']);
		$s     = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
		$s     = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
		$s     = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
		$s     = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_double_click']);

		$s     = $s . ">$fieldValue</textarea>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForWords($CRLF, $TAB){

    	$fieldValue           = $this->objectProperty['sob_all_title'];

    	if($this->parentType      == 'form'){ //--not a subform
	    	$s                    = "$TAB<tr class='selected'>$CRLF";
	    	$s                    = $s . "$TAB  <td class='selected'>$CRLF$TAB    ";
	    	$s                    = $s . "$TAB  </td>$CRLF";
	    	$s                    = $s . "$TAB  <td class='selected' style='text-align:left'>";
	    	$s                    = $s . "$TAB  <b>$fieldValue</b>$CRLF$TAB    ";
	    	$s                    = $s . "$TAB  </td>$CRLF";
	    	$s                    = $s . "$TAB</tr>$CRLF";
	    	return $s;
		}else{
	    	return "<font style='color:black'>".$this->objectProperty['sob_all_name'].'</font>';
		}
	}

}


?>
