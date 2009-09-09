<?php
/*
** File:           form.php
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
$GLOBALS['nuRunQuery']           = 0;
$GLOBALS['formValues'][]         = 'xxx';
$dir                             = $_GET['dir'];
$ses                             = $_GET['ses'];
$f                               = $_GET['f'];
$r                               = $_GET['r'];
$c                               = $_GET['c'];
$delete                          = $_GET['delete'];

include("../$dir/database.php");
include('common.php');

if(passwordNeeded($f)){
	$session = nuSession($ses, 'F'.$f);
	if($session->foundOK == ''){
		print 'you have been logged out..';
		return;
	}
}

$setup                           = nuSetup();

$al                              = $session->sss_access_level;

if($f == 'index' AND $session->sss_access_level != 'globeadmin'){
	$inString                    = "'x'";
	$s                           = "SELECT zzsys_object_id FROM zzsys_object ";
	$s                           = $s . "INNER JOIN zzsys_access_level_object ON zzsys_object_id = sao_zzsys_object_id ";
	$s                           = $s . "INNER JOIN zzsys_access_level ON sao_zzsys_access_level_id = zzsys_access_level_id ";
	$s                           = $s . "WHERE sob_zzsys_form_id = 'index' ";
	$s                           = $s . "AND sal_name = '$session->sss_access_level' ";
	$ttt                         = nuRunQuery($s);

	while($rrr                   = db_fetch_row($ttt)){
		$inString                = "$inString, '$rrr[0]'";
	}
	$inString                    = " AND zzsys_object_id IN($inString) ";
}

$runActivity                     = false;

if($f                            == 'run'){//---run a report, procedure or export
	$ttt                         = nuRunQuery("SELECT sat_all_zzsys_form_id, sat_all_description FROM zzsys_activity WHERE zzsys_activity_id = '$r'");
	$rrr                         = db_fetch_row($ttt);
	$f                           = $rrr[0];
	$runActivity                 = true;
}
$tempObjectTable                 = TT();
nuRunQuery("CREATE TABLE $tempObjectTable SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$f'");
nuRunQuery("ALTER TABLE $tempObjectTable ADD INDEX (sob_all_column_number)");
nuRunQuery("ALTER TABLE $tempObjectTable ADD INDEX (sob_all_order_number)");
nuRunQuery("ALTER TABLE $tempObjectTable ADD INDEX (sob_all_tab_number)");
nuRunQuery("ALTER TABLE $tempObjectTable ADD INDEX (sob_all_tab_title)");



$nuForm                          = new Form();
$nuForm->loadAfterConstruct($f, $r, $c, $delete, $runActivity, $dir, $ses, $tempObjectTable, $session);


function addJSFunction($pCode){

	global $nuForm;
	$nuForm->appendJSFunction($pCode);

}



//---get form tab details FROM zzsys_object
$t                               = nuRunQuery("SELECT sob_all_tab_title FROM zzsys_object WHERE sob_zzsys_form_id = '".$nuForm->form->zzsys_form_id."' $inString GROUP BY sob_all_tab_title ORDER BY sob_all_tab_number");
while($r                         = db_fetch_row($t)){
	$nuForm->formTabs[]          = $r[0];
	$nuForm->formTabNames[$r[0]] = count($nuForm->formTabs);
}

$nuForm->access_level                         = $session->sss_access_level;
$nuForm->session_id                           = $session->sss_session_id;
$nuForm->zzsys_user_id                        = $session->sss_zzsys_user_id;
$nuForm->zzsys_user_group_name                = $session->sss_zzsys_user_group_name;
$nuForm->inString                             = $inString;

$nuForm->setSessionVariables();

$nuForm->execute();
nuRunQuery("DROP TABLE $nuForm->objectTableName");

class Form{

    public  $form                    = array();
    public  $setup                   = array();
    public  $formTabs                = array();
    public  $formTabNames            = array();
    public  $recordValues            = array();
    public  $arrayOfHashVariables    = array();       //---this array holds all the values from this record plus values that might be in a display or lookup.
    private $formObjects             = array();
    public  $textObjects             = array();
    public  $subformNames            = array();
    public  $subformTabs             = array();
    private $jsFunctions             = array();
    public  $inarrayFunctions        = array();
    public  $globalValue             = array();
    public  $customDirectory         = '';
    public  $session                 = '';            //---id that remains the same throughout login time
    public  $pageHeader              = '';
    public  $actionButtonsHTML       = '';            //---HTML that goes at the top of the form for buttons like Save,Clone etc.
    public  $CRLF                    = "\n";
    public  $TAB                     = '    ';
    public  $formID                  = '';            //---Primary Key of zzsys_form Table
    public  $recordID                = '';            //---Primary Key of displayed record
    private $subformID               = '';            //---Primary Key of current Subform record
    public  $formsessionID           = '';            //---Form Session ID (unique ID for this instance of this form)
    private $styleSheet              = '';
    private $startTime               = 0;
    private $tabHTML                 = '';
    private $logon                   = '';
    private $delete                  = '';
    public  $cloning                 = '';            //---Whether this will be a new record cloned from $formID's record
    public  $access_level            = '';
    public  $zzsys_user_id           = '';
    public  $zzsys_user_login_id     = '';
    public  $inString                = '';
    public  $objectTableName         = '';
    public  $runActivity             = false;

    function loadAfterConstruct($theFormID, $theRecordID, $clone, $delete, $runActivity, $dir, $ses, $tempObjectTable, $session){

		$this->access_level          = $session->sss_access_level;
		$this->session_id            = $session->sss_session_id;
		$this->zzsys_user_id         = $session->sss_zzsys_user_id;
		$this->zzsys_user_group_name = $session->sss_zzsys_user_group_name;


		$this->startTime             = time();
		$this->objectTableName       = $tempObjectTable;
		$this->customDirectory       = $dir;
		$this->session               = $ses;
		$this->formsessionID         = uniqid('1');
		nuRunQuery("DELETE FROM zzsys_variable WHERE sva_id = '$this->formsessionID'");
    	
		$this->formID                = $theFormID;                                //---Primary Key of zzsys_form Table
		$this->form                  = formFields($theFormID);
		$this->recordID              = $theRecordID;                              //---ID of displayed record (-1 means a new record)
                setnuVariable($this->formsessionID, nuDateAddDays(Today(),2), 'recordID', $this->recordID);

		$this->cloning               = $clone;                                    //---Whether this will be a new record cloned from $formID's record
		$this->delete                = $delete;
		$this->setup                 = nuSetup();
//----------create an array of hash variables that can be used in any "hashString" 
		if($this->form->sfo_report_selection != '1'){
			$T                              = nuRunQuery("SELECT ".$this->form->sfo_table.".* FROM ".$this->form->sfo_table." WHERE ".$this->form->sfo_primary_key." = '$this->recordID'");
			$this->recordValues             = db_fetch_array($T);
			$this->arrayOfHashVariables     = recordToHashArray($this->form->sfo_table, $this->form->sfo_primary_key, $this->recordID);//--values of this record
		}
		$this->arrayOfHashVariables['#recordID#']      = $theRecordID;  //--this record's id
		$this->arrayOfHashVariables['#id#']            = $theRecordID;  //--this record's id
		$this->arrayOfHashVariables['#clone#']         = $clone;        //--if it is a clone
		$this->arrayOfHashVariables['#formSessionID#'] = $this->formsessionID;        //--form session id
		$sVariables                                    = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
		$this->arrayOfHashVariables                    = joinHashArrays($this->arrayOfHashVariables, $sVariables);       //--join the arrays together
		$nuHashVariables                               = $this->arrayOfHashVariables;   //--added by sc 23-07-2009
//----------allow for custom code----------------------------------------------
        //--replace hash variables then run code
        $runCode                                   = replaceHashVariablesWithValues($this->arrayOfHashVariables, $this->form->sfo_custom_code_run_before_open);
        if($_GET['debug']!=''){
            tofile('sfo_custom_code_run_before_open hash variables : debug value:'.$_GET['debug']);
            tofile(print_r($this->arrayOfHashVariables,true));
            tofile($runCode);
        }
		eval($runCode);
        if($newRecordID!=''){
            $this->recordID = $newRecordID; 
        }
//-----------custom code end---------------------------------------------------
		$this->runActivity           = $runActivity;
        //----defaultJSfunctions needs $formTabs populated first
        $this->defaultJSfunctions();
        $this->createActionButtonsHTML();
    }

	private function pageHeader($tabList){

        $TAB                = $this->TAB;
        $CRLF               = $this->CRLF;
        $dbc                = "";

	    if($this->zzsys_user_id == 'globeadmin' and $this->form->sys_setup != '1'){
  				$dbc        = " ondblclick=\"openBrowse('object', '$this->formID', '', '$this->session', '')\"";
	    }

        $tabString          = "<div id='TopDiv$tabNumber' $dbc   class='unselected' style='visibility:visible;overflow:hidden;position:absolute;top:73;left:12;width:968;height:25'>$CRLF";
		//---create menu
        $left               = 0;
        for($i=0;$i<count($tabList);$i++){
        	
            $tabString .= $this->buildATab($this->setup->set_unselected_color, $this->setup->set_selected_color, $tabList[$i], $left, $i);
            $left       = $left + (strlen($tabList[$i])* 11) + 3;
        }
        //---end of create menu

        $tabString         .= "</div>$CRLF";//--close top div
        $this->pageHeader  = "$CRLF<!-- start menu-->$CRLF$tabString$CRLF<!-- end menu-->";

	}


    private function buildTab($tabNumber, $tabList){

        $TAB                = $this->TAB;
        $CRLF               = $this->CRLF;
        $subformString      = '';

        $tabString          = "$CRLF<!-- Tab for ".$tabList[$tabNumber]." -->$CRLF$CRLF";//--close main div

		//--create main div
        if($tabNumber       ==0){
            $v              = 'visible';
        }else{
            $v              = 'hidden';
        }

        $tabString         .= "<div id='MidDiv$tabNumber'    class='selected' style='visibility:hidden;overflow:hidden;position:absolute;top:93; left:12;  width:968;  height:505'><br/>$CRLF";

		$getColumns         = nuRunQuery("SELECT sob_all_column_number FROM $this->objectTableName WHERE sob_zzsys_form_id = '$this->formID' AND sob_all_tab_title = '".$tabList[$tabNumber]."' $this->inString GROUP BY sob_all_column_number ORDER BY sob_all_column_number");
		while($tc           = db_fetch_row($getColumns)){
			$tabColumns[]   = $tc[0];
		}

        $tabString         .= "$CRLF$TAB<table class='selected'>$CRLF$TAB$TAB<tr>$CRLF";
		for($c=0;$c<count($tabColumns);$c++){
	        $tabString     .= "$TAB$TAB$TAB<td class='selected'>$CRLF$TAB$TAB$TAB$TAB<table class='selected'>$CRLF$TAB$TAB$CRLF";

	        //---create column objects
	        $t              = nuRunQuery("SELECT * FROM $this->objectTableName WHERE sob_zzsys_form_id = '$this->formID' AND sob_all_column_number = '".$tabColumns[$c]."' AND  sob_all_tab_title = '".$tabList[$tabNumber]."' $this->inString ORDER BY sob_all_column_number, sob_all_order_number");
	        while($object   = db_fetch_object($t)){
	            $this->buildObject($object);
	        }

	        for($i=0;$i<=count($this->formObjects);$i++){
	        	if($this->formObjects[$i]->objectType == 'subform'){
		        	$subformString .= $this->formObjects[$i]->objectHtml;
	        	}else{
		        	$tabString     .= $this->formObjects[$i]->objectHtml;
	        	}
			}

	        $tabString     .="$CRLF$TAB$TAB$TAB$TAB</table>$CRLF$TAB$TAB$TAB</td>$CRLF";
			unset ($this->formObjects);
		}
        $tabString         .= "$TAB$TAB</tr>$CRLF$TAB</table>$CRLF";
        $tabString         .= $subformString; //---put subforms outside tables
        $tabString         .= "</div>$CRLF";//--close middle div

        return $tabString;  //--html that displays tabs

    }

    public function createActionButtonsHTML(){


    	$dq      = '"';
		if($this->form->zzsys_form_id == 'index'){
			$s   =      "$this->CRLF<div id='logo' style='cursor:pointer;overflow:hidden;position:absolute;top:0; left:0;  width:992;  height:70'  >$this->CRLF";
			$s   = $s . "<img src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_index_image_path . "' onclick='nuRefresh()'/></div>$this->CRLF";
			$vis = 'hidden';
		}else{
			$s   =      "$this->CRLF<div id='logo' style='cursor:pointer;overflow:hidden;position:absolute;top:0; left:10;  width:150;  height:70'  >$this->CRLF";
			if(passwordNeeded($this->formID)){ //-- show home button
				if($this->setup->set_home_mouse_up==''){
					$s   = $s . "<br/><input id='home_id' type='button' class='actionButton' value='Home' onclick='backToIndex()' accesskey='h' />&nbsp;$this->CRLF";
				}else{
                    $s   = $s . "<img  id='home_id' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_home_mouse_up . "' alt='Home' onmouseup=\"this.src=getImage('" . $this->setup->set_home_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_home_mouse_up . "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_home_mouse_down . "')\" onclick='backToIndex()'/>$this->CRLF";
				}
			}
			$s   = $s . "</div>$this->CRLF";
			$vis = 'visible';
		}
		$s       = $s . "$this->CRLF<div id='actionButtons' style='visibility:$vis;overflow:hidden;position:absolute;top:20; left:165;  width:660;  height:40'  >$this->CRLF";
		$s       = $s . "<table align='center'><tr align='center'><td align='center'>$this->CRLF";
		if($this->runActivity){
			$s   = $s . $this->createActivityButtonHTML();
		}else{
			if($this->form->zzsys_form_id != 'index'){
		        if($this->delete == '1'){
					$s   = $s . "Are You Sure? <select name='del_ok' id='del_ok'><option selected value='0'>No don't</option><option value='1'>Yes</option></select>$this->CRLF";
		        }else{
					if($this->form->sfo_save_button    == '1' and displayCondition($this->arrayOfHashVariables, $this->form->sfo_save_button_display_condition)){
						$title = iif($this->form->sfo_save_title == '','Save',$this->form->sfo_save_title);
						if($this->setup->set_form_save_mouse_up==''){
							$s   = $s . "<input type='button' id = 'nuActionSave' accesskey='s' class='actionButton' value='$title' onclick='SaveThis(0)'/>&nbsp;$this->CRLF";
						}else{
							$s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_form_save_mouse_up . "' alt='Save' onmouseup=\"this.src=getImage('" . $this->setup->set_form_save_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_form_save_mouse_up. "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_form_save_mouse_down . "')\" onclick='SaveThis(0)'/>$this->CRLF";
						}
					}
					if($this->form->sfo_close_button    == '1' and displayCondition($this->arrayOfHashVariables, $this->form->sfo_close_button_display_condition)){
						$title = iif($this->form->sfo_close_title == '','Save & Close',$this->form->sfo_close_title);
						if($this->setup->set_form_close_mouse_up==''){
							$s   = $s . "<input type='button' id = 'nuActionSaveNClose' accesskey='s' class='actionButton' value='$title' onclick='SaveThis(1)'/>&nbsp;$this->CRLF";
						}else{
							$s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_form_close_mouse_up . "' alt='Close' onmouseup=\"this.src=getImage('" . $this->setup->set_form_close_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_form_close_mouse_up. "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_form_close_mouse_down . "')\" onclick='SaveThis(1)'/>$this->CRLF";
						}
					}
					if($this->form->sfo_clone_button   == '1' and $this->recordID <> '-1' and $this->cloning <> '1' and displayCondition($this->arrayOfHashVariables, $this->form->sfo_clone_button_display_condition)){
						$title = iif($this->form->sfo_clone_title == '','Clone',$this->form->sfo_clone_title);
						if($this->setup->set_form_clone_mouse_up==''){
							$s   = $s . "<input type='button' id = 'nuActionClone' class='actionButton' value='$title' onclick='CloneThis()'/>&nbsp;$this->CRLF";
						}else{
							$s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_form_clone_mouse_up . "' alt='Clone' onmouseup=\"this.src=getImage('" . $this->setup->set_form_clone_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_form_clone_mouse_up. "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_form_clone_mouse_down . "')\" onclick='CloneThis()'/>$this->CRLF";
						}
					}
					$t       = nuRunQuery("SELECT * FROM zzsys_form_action WHERE sfa_zzsys_form_id = '".$this->form->zzsys_form_id."'");
					while($r = db_fetch_object($t)){
						if(displayCondition($this->arrayOfHashVariables, $r->sfa_button_display_condition)){
							if($r->sfa_button_mouse_up_image==''){
								$s   = $s . "<input type='button' id = 'nuCustomAction$r->sfa_button_title' class='actionButton' value='$r->sfa_button_title' onclick='$r->sfa_button_javascript'/>&nbsp;$this->CRLF";
							}else{
								$s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $r->sfa_button_mouse_up_image . "' alt='" . $r->sfa_button_title . "' onmouseup=\"this.src=getImage('" . $r->sfa_button_mouse_up_image . "')\" onmouseout=\"this.src=getImage('" . $r->sfa_button_mouse_up_image . "')\" onmousedown=\"this.src=getImage('" . $r->sfa_button_mouse_down_image . "')\" onclick='$r->sfa_button_javascript'/>$this->CRLF";
							}
						}
					}
		        }

				if($this->delete == '1' or ($this->form->sfo_delete_button  == '1' and $this->recordID <> '-1' and $this->cloning <> '1' and displayCondition($this->arrayOfHashVariables, $this->form->sfo_delete_button_display_condition))){
					$title = iif($this->form->sfo_delete_title == '','Delete',$this->form->sfo_delete_title);
					if($this->setup->set_form_delete_mouse_up==''){
						$s   = $s . "<input type='button' id = 'nuActionDelete' class='actionButton' value='Delete' onclick='DeleteThis()'/>&nbsp;$this->CRLF";
					}else{
                        $s   = $s . "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_form_delete_mouse_up . "' alt='Delete' onmouseup=\"this.src=getImage('" . $this->setup->set_form_delete_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_form_delete_mouse_up . "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_form_delete_mouse_down . "')\" onclick='DeleteThis()'/>$this->CRLF";
					}
				}
			}
		}
		$s       = $s . "</td></tr></table>$this->CRLF";
		$s       = $s . "</div>$this->CRLF";
		$s       = $s . "$this->CRLF<div id='pagetitle' style='padding:10;font-size:x-small;font-family:tahoma;text-align:center;overflow:hidden;position:absolute;top:-7; left:830;  width:150;  height:30'  >$this->CRLF";
		if($this->form->zzsys_form_id != 'index'){

			if($this->runActivity){
				$ta = nuRunQuery("SELECT sat_all_description FROM zzsys_activity WHERE zzsys_activity_ID = '$this->recordID'");
				$ra = db_fetch_row($ta);
				$s       = $s . "<i>".$ra[0]."</i></div>$this->CRLF";
			}else{
			    if($this->zzsys_user_id == 'globeadmin' and $this->form->sys_setup != '1'){
    				$s   = $s . "<i><span ondblclick=\"openForm('form', '$this->formID')\">".$this->form->sfo_title."</span></i></div>$this->CRLF";
			    }else{
    				$s   = $s . "<i>".$this->form->sfo_title."</i></div>$this->CRLF";
			    }
			}
			$s       = $s . "$this->CRLF<div id='loggedin' style='padding:10;font-size:x-small;font-family:tahoma;text-align:center;overflow:hidden;position:absolute;top:25; left:830;  width:150;  height:45'  >$this->CRLF";
			$s       = $s . $GLOBALS['zzsys_user_name']."<br/>".$this->setup->set_title."$this->CRLF";
		}
		$s       = $s . "</div>$this->CRLF";
		$this->actionButtonsHTML = $s;

	}




// begin added by SG
public function createActivityButtonHTML(){

	$t                       = nuRunQuery("SELECT * FROM zzsys_activity WHERE zzsys_activity_id = '$this->recordID'");
        $r                       = db_fetch_object($t);
        $dq                      = '"';
        $s                       = '';

       	//if report
	if($r->sat_all_type == 'report'){


		if ($r->sat_report_display_type == 0 || $r->sat_report_display_type == 2) { 
		
			//print	html	
			$action = "printIt($dq$r->sat_all_code$dq)";
			if($this->setup->set_print_mouse_up==''){
				$s .= $this->showActivityBtn('Print to Screen', $action);
                	}else{
				$s .= $this->showActivityImg($this->setup->set_print_mouse_up, $this->setup->set_print_mouse_down, 'Print', $action);
                	}
               
			//email	html
			if ("" != $this->setup->set_email_default_from) {
				$from = $this->setup->set_email_default_from;
			} else {
				$from = "";
			}
			$action = "emailIt($dq$r->sat_all_code$dq,$dq$dq,$dq$from$dq,$dq$dq,$dq$dq,$dq$dq,true,".$dq."HTML"."$dq)";
			if($this->setup->set_email_html_mouse_up==''){
				$s .= $this->showActivityBtn('Email', $action);
                	}else{
				$s .= $this->showActivityImg($this->setup->set_email_html_mouse_up, $this->setup->set_email_html_mouse_down, 'Email', $action);
                	}
		}
 
		if ($r->sat_report_display_type == 1 || $r->sat_report_display_type == 2) {

			//print pdf
			$action = "pdfIt($dq$r->sat_all_code$dq)";
	        	if($this->setup->set_pdf_mouse_up==''){
				$s .= $this->showActivityBtn('Print to PDF', $action);       	
                	}else{
				$s .= $this->showActivityImg($this->setup->set_pdf_mouse_up, $this->setup->set_pdf_mouse_down, 'PDF', $action);
                	}

			//PDF html	
			$action = "emailIt($dq$r->sat_all_code$dq,$dq$dq,$dq$from$dq,$dq$dq,$dq$dq,$dq$dq,true,".$dq."PDF"."$dq)";
			if($this->setup->set_email_pdf_mouse_up==''){
				$s .= $this->showActivityBtn('Email PDF', $action);
                	}else{
				$s .= $this->showActivityImg($this->setup->set_email_pdf_mouse_up, $this->setup->set_email_pdf_mouse_down, 'Email PDF', $action);
                	}
		}

        }//end if report

	//if procedure
        if($r->sat_all_type == 'procedure'){

		$action = "runIt($dq$r->sat_all_code$dq)";
        	if($this->setup->set_procedure_mouse_up==''){
			$s .= $this->showActivityBtn('Run', $action);       	
                }else{
			$s .= $this->showActivityImg($this->setup->set_procedure_mouse_up, $this->setup->set_procedure_mouse_down, 'Procedure', $action);
                }
        }

	//if export
        if($r->sat_all_type == 'export'){

		$action = "exportIt($dq$r->sat_all_code$dq)";
		if($this->setup->set_export_mouse_up==''){
			$s .= $this->showActivityBtn('Export', $action);       	
                }else{
			$s .= $this->showActivityImg($this->setup->set_export_mouse_up, $this->setup->set_export_mouse_down, 'Export', $action);
                }
	}
        
	//return result (string)
	return $s;
}

private function showActivityBtn($value, $action) {

	$dq     = '"';
	$result = "<input type='button' class='actionButton' value='$value' onclick='$action'/>&nbsp;$this->CRLF";
	return $result;

}

private function showActivityImg($up, $down, $alt, $action) {

	$result = "<img height='30' src='formimage.php?dir=$this->customDirectory&iid=" .$up."' alt='$alt' onmouseup=\"this.src=getImage('".$up."')\" onmouseout=\"this.src=getImage('".$up."')\" onmousedown=\"this.src=getImage('".$down."')\" onclick='$action'/>$this->CRLF";
	return $result;

}
// end added by SG 


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
        $s   = $s . "   return false;$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function zzsys_user_group_name(){ $C";
        $s   = $s . "   return '$this->zzsys_user_group_name';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function sd(){ $C";
        $s   = $s . "   return '$this->customDirectory';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function web_root_path(){ $C";
        $s   = $s . "   return '".$this->setup->set_web_root_path."';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);


        $s   =      "function getImage(pID){ $C";
        $s   = $s . "   return 'formimage.php?dir='+sd()+'&iid='+pID;$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);


	}

    private function defaultJSfunctions(){

        $C   = $this->CRLF;
		if($this->form->sfo_javascript != ''){
	        $this->appendJSfunction("$C//---- start of custom javascript ----$C$C".$this->form->sfo_javascript."$C$C//---- end of custom javascript ----");
		}
		$this->checkBlanks();

        $s   =      "function nuRefresh(){//---refresh index page$C";
        $s   = $s . "      window.onbeforeunload = null; $C";
        $s   = $s . "      window.onunload = null; $C";
        $s   = $s . "      history.go(); $C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function sfVisibile(pTabNo){//---show subforms$C";
        $s   = $s . "      var subformCount = document.getElementById('theform').TheSubforms.value; $C";
        $s   = $s . "      var subformName  = ''; $C";
        $s   = $s . "      for(ii=0;ii<subformCount;ii++){ $C";
        $s   = $s . "          if(document.getElementById('theform')['SubformNumber'+ii].accept == pTabNo){ $C";
        $s   = $s . "              subformName = document.getElementById('theform')['SubformNumber'+ii].value$C";
        $s   = $s . "              document.getElementById('sf_title'+subformName).style.visibility = 'visible';$C";
        $s   = $s . "              document.getElementById(subformName).style.visibility = 'visible';$C";
        $s   = $s . "          }$C";
        $s   = $s . "      }$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function SFrowColor(pthis, pSubform){ $C";
        $s   = $s . "      var lRow    = document.getElementById('lastRow_'  + pSubform).value;$C";
        $s   = $s . "      var rColor  = document.getElementById('rowColor_' + pSubform).value;$C";
        $s   = $s . "      if(lRow    != ''){ $C";
        $s   = $s . "         document.getElementById(lRow).style.backgroundColor  = document.getElementById('lastColor_'  + pSubform).value;$C";
        $s   = $s . "      }$C";
        $s   = $s . "      document.getElementById('lastColor_'  + pSubform).value = document.getElementById(pthis.id).style.backgroundColor;$C";
        $s   = $s . "      document.getElementById(pthis.id).style.backgroundColor = rColor;$C";
        $s   = $s . "      document.getElementById('lastRow_' + pSubform).value    = pthis.id;$C";
        $s   = $s . "}    $C";
        $this->appendJSfunction($s);

        $s   =      "function sfInvisibile(pTabNo){//---show subforms$C";
        $s   = $s . "      var subformCount = document.getElementById('theform').TheSubforms.value; $C";
        $s   = $s . "      var subformName  = ''; $C";
        $s   = $s . "      for(ii=0;ii<subformCount;ii++){ $C";
        $s   = $s . "          if(document.getElementById('theform')['SubformNumber'+ii].accept == pTabNo){ $C";
        $s   = $s . "              subformName = document.getElementById('theform')['SubformNumber'+ii].value$C";
        $s   = $s . "              document.getElementById('sf_title'+subformName).style.visibility = 'hidden';$C";
        $s   = $s . "              document.getElementById(subformName).style.visibility = 'hidden';$C";
        $s   = $s . "          }$C";
        $s   = $s . "      }$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);


        $s   =      "function getImage(pID){ $C";
        $s   = $s . "   return 'formimage.php?dir='+customDirectory()+'&iid='+pID; $C";
        $s   = $s . "}$C$C";
        $this->appendJSfunction($s);

   		if($this->form->sfo_access_without_login != '1'){
            $s   =      "self.setInterval('checknuC()', 1000); $C$C";
        }

		if($this->form->zzsys_form_id == 'index'){$s = '';}
        $s   = $s . "function checknuC(){ $C";
        $s   = $s . "   if(nuReadCookie('nuC') == null){ $C";
		if($this->form->zzsys_form_id == 'index'){
            $s   = $s . "      pop = window.open('formlogin.php', '_parent');$C";
        }else{
            $s   = $s . "      pop = window.open('', '_parent');$C";
            $s   = $s . "      pop.close();$C";
        }
        $s   = $s . "   }$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function MIN(pthis){//---mouse over menu$C";
        $s   = $s . "      document.getElementById(pthis.id).style.color='" . $this->setup->set_hover_color . "';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function MOUT(pthis){//---mouse out menu$C";
        $s   = $s . "   document.getElementById(pthis.id).style.color='';$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function getCal(theID){//---open calendar$C";

		$s	= $s . "   calendarBuild(theID); ";	

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
        $s   = $s . "   var url      = 'getlist.php?x=1&type=list&id='+document.getElementById('formsessionID').value+'&name='+pthis.id;$C";
        $s   = $s . "   var theframe = getframe();$C";
        $s   = $s . "   parent.frames[theframe].document.location = url;$C";
        $s   = $s . "}$C";
        $this->appendJSfunction($s);

        $s   =      "function SaveThis(pclose){//---save record$C";
        $s   = $s . "   document.getElementById('theform').close_after_save.value = pclose;$C";
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

        $s   = $s . "   var framenumber       = parent.frames[0].document.theform.framenumber.value *1 + 1;$C";
        $s   = $s . "   if (framenumber > 9){ $C";
        $s   = $s . "   	parent.frames[0].document.theform.framenumber.value = 0;$C";
        $s   = $s . "   }else{ $C";
        $s   = $s . "   parent.frames[0].document.theform.framenumber.value = framenumber;$C";
        $s   = $s . "   }$C";
        $dbg = '&debug=' . $_GET['debug']; //--debug parameter (passed manually)
        if($this->cloning == '1'){
	        $s   = $s . "   parent.frames[framenumber].document.location = 'formduplicate.php?x=1&r=-1&dir=$this->customDirectory&ses=$this->session&f=$this->formID&form_ses=$this->formsessionID$dbg';$C";
        }else{
 	        $s   = $s . "   parent.frames[framenumber].document.location = 'formduplicate.php?x=1&r=$this->recordID&dir=$this->customDirectory&ses=$this->session&f=$this->formID&form_ses=$this->formsessionID$dbg';$C";
        }

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
        $dbg = '&debug=' . $_GET['debug'];
        $s   = $s . "   parent.frames['main'].document.forms[0].action = 'form.php?x=1&c=1&r=$recordID&dir=$this->customDirectory&ses=$this->session&f=$this->formID$dbg';$C";
        $s   = $s . "   parent.frames['main'].document.forms[0].submit();$C";
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
        $s   = $s . "   showTab(0);$C";
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
	        $s   = $s . "   parent.frames['main'].document.forms[0].action = 'formdelete.php?x=1&r=$recordID&dir=$this->customDirectory&ses=$this->session&f=$this->formID';$C";
        }else{
	        $s   = $s . "   parent.frames['main'].document.forms[0].action = 'form.php?x=1&delete=1&r=$recordID&dir=$this->customDirectory&ses=$this->session&f=$this->formID';$C";
        }
        $s   = $s . "   parent.frames['main'].document.forms[0].submit();$C";
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

//Added by SG
   	$s   =      "function emailIt(pReportID,pTo,pFrom,pSubject,pMessage,pFilename,pResponse,pType){                         $C";
// BEGIN - 2009/05/29 - Michael
// If set_default_email_from does not exist in the zzsys_setup table
// or is blank, pFrom will still be null when it's passed through to
// emailFormBuild.
	$s   = $s . "   if (pFrom == null || pFrom == '')                                                                       $C";
	$s   = $s . "        pFrom = '{$this->setup->set_default_email_from}';							$C";
// END - 2009/05/29 - Michael
        $s   = $s . "   nuEraseCookie('emailREPORT');                                                                           $C";
	$s   = $s . "  	emailFormBuild(pReportID,pTo,pFrom,pSubject,pMessage,pFilename,pResponse,pType);  						$C";
	//	$s   = $s . "   if (pTo == '')       { pTo = prompt('Email to..','');}                                                  $C";
	//	$s   = $s . "   if (pFrom == '')     { pFrom = prompt('Email from..','');}                                              $C";
	//	$s   = $s . "   if (pSubject == '')  { pSubject = prompt('Subject..','');}                                              $C";
	//	$s   = $s . "   if (pMessage == '')  { pMessage = prompt('Message..','');}                                              $C";
	//	$s   = $s . "   if (pFilename == '') { pFilename = prompt('Filename..','');}                                            $C";
	//	$s   = $s . "   if (pTo == null || pFrom == null || pSubject == null || pMessage == null || pFilename == null) {        $C";
	//	$s   = $s . "           alert('not enough information provided to send email, please try again');                       $C";	
	//	$s   = $s . "   } else {                                                                                                $C";
	//	$s   = $s . "           if (pTo == '' || pTo == null) {                                                                 $C";
	//	$s   = $s . "                   alert('Please provide an email address');                                               $C";
	//	$s   = $s . "           } else {                                                                                        $C";
	//	$s   = $s . "   				emailFormBuild(pReportID,pTo,pFrom,pSubject,pMessage,pFilename,pResponse,pType);        $C";
	//	$s   = $s . "                   emailSendIt(pReportID,pTo,pFrom,pSubject,pMessage,pFilename,pResponse,pType);           $C";
	//	$s   = $s . "           }                                                                                               $C";
	//	$s   = $s . "   }                                                                                                       $C";
		$s   = $s . "}                                                                                                          $C";
        $this->appendJSfunction($s);

// BEGIN - 2009/05/29 - Michael
	$php_url = getPHPurl();
	$s = <<<EOJS
			function emailSendIt(pReportID, pTo, pFrom, pSubject, pMessage, pFilename, pAlert, pType, pResponse)
			{
					// Make sure the error message is hidden.
				document.getElementById("error_row").style.display = 'none';
					// Erase the cookie and make sure these is an email address.
				nuEraseCookie("emailREPORT");
				if (pTo == '' || pTo == null)
					alert("Please provide an email address");
				else
				{
						// Generate the URL to email the report.
						// NOTE: The subject, message and filename need to be escaped
						//       otherwise everything after the first '&' in those values
						//       will be dropped due to the way GET variables are interpreted.
					var report_url	= "{$php_url}";
					var url 	= report_url+"reportemail.php" +
									"?x=1" +
									"&dir={$this->customDirectory}" +
									"&ses={$this->session}" +
									"&form_ses={$this->formsessionID}" +
									"&r=" + pReportID +
									"&to=" + pTo +
									"&from=" + pFrom +
									"&subject=" + escape(pSubject) +
									"&message=" + escape(pMessage) +
									"&filename=" + escape(pFilename) +
									"&report_url=" + report_url +
									"&reporttype=" + pType +
									"&receipt=" + pResponse;
						// Run that URL.
					nuMailJax(url);
						// Check if we need to be alerted about whether the email was
						// successfully sent out.
					if (pAlert == true || pAlert == 'true')
						startEmailTimeOut();
				} // else
			} // func
EOJS;
	$this->appendJSfunction($s);
// END - 2009/05/29 - Michael

/*
        $s   =      "function emailSendIt(pReportID,pTo,pFrom,pSubject,pMessage,pFilename,pAlert,pType,pResponse){              $C";
        $s   = $s . "   nuEraseCookie('emailREPORT');                                                                           $C";
        $s   = $s . "   if (pTo == '' || pTo == null) {                                                                         $C";
        $s   = $s . "           alert('Please provide an email address');                                                       $C";
        $s   = $s . "   } else {                                                                                                $C";
        $s   = $s . "           var report_url='".getPHPurl()."';                                                               $C";
        $s   = $s . "           var url='".getPHPurl()."reportemail.php?x=1&dir=$this->customDirectory&ses=$this->session';     $C";
        $s   = $s . "           var url=url+'&form_ses=$this->formsessionID&r='+pReportID;                                      $C";
        $s   = $s . "           var url=url+'&to='+pTo+'&from='+pFrom+'&subject='+pSubject+'&message='+pMessage;                $C";
        $s   = $s . "           var url=url+'&filename='+pFilename+'&report_url='+report_url+'&reporttype='+pType;              $C";
        $s   = $s . "           nuMailJax(url);                                                                                     $C";
        $s   = $s . "           if (pAlert == true || pAlert == 'true'){                                                                         $C";
        $s   = $s . "                   startEmailTimeOut();                                                 $C";
		$s   = $s . "                   return;                                                 								$C";
        $s   = $s . "           }                                                                                               $C";
        $s   = $s . "   }                                                                                                       $C";
        $s   = $s . "}                                                                                                          $C";
        $this->appendJSfunction($s);
*/

// BEGIN - 2009/05/29 - Michael
	$s = <<<EOJS
			function emailSendResponse()
			{
					// Get the cookie and delete it.
				cookieValue = nuReadCookie("emailREPORT");
				nuEraseCookie("emailREPORT");
					// Check the cookie value.
				switch (cookieValue)
				{
					// The cookie has this value if everything was successful.
				case "{$this->formsessionID}":
					emailSuccess();
					return true;
					// The report could not be generated...
					// NOTE: This case won't run if there was an error in the report
					//       itself, only if the report URL could not be read from or
					//       the report document could not be saved to the filesystem.
				case "report_error":
					emailFailure("There was an error generating the report");
					return false;
					// The email was not sent successfully...
					// NOTE: This will mostly like only occur if the report could not
					//       be attached or there is no SMTP server listening on port 25.
					//       Postfix will just add the message to a queue before it trys
					//       to send the message, so invalid hostnames and users will
					//       not trigger this error.
				case "email_error":
					emailFailure("There was an error sending the email");
					return false;
					// This case will run if for some reason reportemail.php died
					// before sendResponse() was called. In other words, no cookie.
				default:
					emailFailure("There was an error sending the email");
					return false;
				} // switch
			} // function
EOJS;
        $this->appendJSfunction($s);
// END - 2009/05/29 - Michael

/*
        $s   =      "function emailSendResponse(){                                                                              $C";
        $s   = $s . "   if (nuReadCookie('emailREPORT') == '$this->formsessionID') {                                            $C";
        $s   = $s . "           //alert('Your email was successfully sent');                                                      $C";
        $s   = $s . "           nuEraseCookie('emailREPORT');                                                                   $C";
        $s   = $s . "           emailSuccess();      				                                                            $C";
        $s   = $s . "           return true;                                                                                    $C";
        $s   = $s . "   } else {                                                                                                $C";
        $s   = $s . "           //alert('possible error, you can try resending the email, or contact nuSoftware');                                             $C";
        $s   = $s . "           nuEraseCookie('emailREPORT');                                                                   $C";
// BEGIN - 2009/05/09 - Michael
				if (There was an error sending the email
        $s   = $s . "           emailFailure(errorString);		                                                                    $C";
// END - Michael
        $s   = $s . "           return false;                                                                                   $C";
        $s   = $s . "   }                                                                                                       $C";
        $s   = $s . "}                                                                                                          $C";
*/
//end added by SG

        $s   =      "function pdfIt(pReportID){ $C";
        $s   = $s . "   var url='" . $this->setup->set_php_url  . "runpdf.php?x=1&dir=$this->customDirectory&ses=$this->session&form_ses=$this->formsessionID&r='+pReportID; $C";
        $s   = $s . "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes'); $C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function printIt(pReportID){ $C";
        $s   = $s . "   var url='" . $this->setup->set_php_url  . "runreport.php?x=1&dir=$this->customDirectory&ses=$this->session&form_ses=$this->formsessionID&r='+pReportID; $C";
        $s   = $s . "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=yes,scrollbars=yes,resizable=yes'); $C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function exportIt(pReportID){ $C";
        $s   = $s . "   var url='" . $this->setup->set_php_url  . "runexport.php?x=1&dir=$this->customDirectory&ses=$this->session&form_ses=$this->formsessionID&r='+pReportID; $C";
        $s   = $s . "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes'); $C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function runIt(pReportID){ $C";
        $s   = $s . "   var url='" . $this->setup->set_php_url  . "runprocedure.php?x=1&dir=$this->customDirectory&ses=$this->session&form_ses=$this->formsessionID&r='+pReportID; $C";
        $s   = $s . "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes'); $C";
        $s   = $s . "};$C";
        $this->appendJSfunction($s);

        $s   =      "function CheckIfSaved(){ $C";
        if(!$this->runActivity){
        	if($this->formID == 'index'){
		        $s   = $s . "   event.returnValue = 'Are you sure?';$C";
        	}else{
		        $s   = $s . "   if(document.getElementById('beenedited').value=='1'){ $C";
		        $s   = $s . "       event.returnValue = 'This record has NOT been saved.. (Click CANCEL to save this record)';$C";
		        $s   = $s . "   };$C";
        	}
        }
        $s   = $s . "};$C";
        $this->appendJSfunction($s);
        
        $s   =      "function untick(pBox){ $C";
        $s   = $s . "   try{document.getElementById('row'+pBox).checked = false;}catch(err){}\n";
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
        		$a[]           = "   if(document.getElementById('$fieldName').value == ''){return $dq'$fieldTitle' cannot be left blank$dq;}$this->CRLF";
       		}
        	if($r['sob_all_type']  == 'subform'){
				$t1ID                     = $r['zzsys_object_id'];
		        $t1                       = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$t1ID'");
		        while($r1                 = db_fetch_array($t1)){

	        		if($r1['sob_'.$r1['sob_all_type'].'_no_blanks'] == '1'){
		        		$fieldName1   = $r1['sob_all_name'];
		        		$fieldTitle1  = $r1['sob_all_title'];
		        		$a[]          = "                                                                                                          $this->CRLF";
		        		$a[]          = "   for(i = 0 ; i < document.getElementById('rows$fieldName').value ; i++){                                $this->CRLF";
		        		$a[]          = "      rno           = '000'+i;                                                                            $this->CRLF";
		        		$a[]          = "      rno           = rno.substr(rno.length-4);                                                           $this->CRLF";
		        		$a[]          = "      checkBox      = 'row$fieldName'+rno;                                                                $this->CRLF";
		        		$a[]          = "      checkField    = '$fieldName'+rno+'$fieldName1';                                                     $this->CRLF";
		        		$a[]          = "      if(!document.getElementById(checkBox).checked  && document.getElementById(checkField).value == ''){ $this->CRLF";
		        		$a[]          = "         return $dq'$fieldTitle1' on line $dq+String(i-0+1)+$dq cannot be left blank$dq;                  $this->CRLF";
		        		$a[]          = "      }                                                                                                   $this->CRLF";
		        		$a[]          = "   }                                                                                                      $this->CRLF$this->CRLF";
	        		}
		        }
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

    private function appendshowTabfunction(){

        $C   = $this->CRLF;

        $s   =      "function showTab(ptabno){//---change visible tab$C";
        if(count($this->formTabs)> 1){ // only have tabs if there is more than one

	        $s   = $s . "   var menuitem$C";
	        $s   = $s . "   for (i=0;i<".count($this->formTabs).";i++){ $C";
	        $s   = $s . "      document.getElementById('MidDiv'+i).style.visibility = 'hidden';$C";
	        $s   = $s . "      document.getElementById('HiddenTabNo'+i).style.visibility = 'visible';$C";
	        $s   = $s . "      document.getElementById('TabNo'+i).style.visibility = 'hidden';$C";
	        $s   = $s . "   }$C";
	        $s   = $s . "   document.getElementById('MidDiv'+ptabno).style.visibility = 'visible';$C";
	        $s   = $s . "   document.getElementById('HiddenTabNo'+ptabno).style.visibility = 'hidden';$C";
	        $s   = $s . "   document.getElementById('TabNo'+ptabno).style.visibility = 'visible';$C";
        	
        }else{

	        $s   = $s . "   document.getElementById('MidDiv0').style.visibility = 'visible';$C";
        	
        }
        $s   = $s . "}$C";

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

//TESTING BY SG 17/Dec/08
print "<script type='text/javascript' src='nuCalendar.js' language='javascript'></script>$this->CRLF";
print "<script type='text/javascript' src='nuEmailForm.js' language='javascript'></script>$this->CRLF";


        $this->displayJavaScript();

		$stylesheetLocation = "../$this->customDirectory/style.css";
		print "</head>$this->CRLF";
		if($this->form->zzsys_form_id == 'index'){
			$dq                       = '"';
	        print "<body onload='nuCreateCookie(\"nuC\",1,1);LoadThis()' onbeforeunload='CheckIfSaved()' onunload=$dq nuEraseCookie('nuC');window.open('formlogout.php?x=1&dir=$this->customDirectory&f=" . $_GET['ses'] . "')$dq>$this->CRLF";
		}else{
	        print "<body onload='LoadThis()' onbeforeunload='CheckIfSaved()'>$this->CRLF";
		}
        print "<form name='theform' id='theform' action='' method='post'>$this->CRLF";
    }

    private function displayFooter(){

		$this->hiddenFields();
        print  "$this->TAB<input type='hidden' name='TheSubforms' value='".count($this->subformNames)."'/>$this->CRLF";
    	for($i = 0 ; $i < count($this->subformNames) ; $i++){
    		$sfno=$i+1;
	        print  "$this->TAB<input type='hidden' name='SubformNumber$i' value='".$this->subformNames[$i]."' accept='".$this->subformTabs[$i]."'/>$this->CRLF";
		}
		print $this->actionButtonsHTML;
		print $this->pageHeader;
		print $this->formValuesStaus();
        print  "$this->CRLF</form>$this->CRLF</body>$this->CRLF</html>$this->CRLF$this->CRLF";
        $build = time()- $this->startTime;
        print  "<!--built in $build seconds (" . $GLOBALS['nuRunQuery'] . " queries)-->";

    }

    
    
    private function formValuesStaus(){

	    $s = "\n\n";
	    
	    for($i = 0 ; $i < count($GLOBALS['formValues']) ; $i++){
		$s = $s . "<input type='hidden' name='____" . $GLOBALS['formValues'][$i] . "' id='____" . $GLOBALS['formValues'][$i] . "' value='1'>\n";
	    }
	    return $s;
    }
    private function displayBody(){
        print "<div id='BorderDivA'   class='nuborder' style='overflow:hidden;position:absolute;top:70;left:10;width:972;height:550' ></div>$CRLF";
        print "<div id='BorderDivB'   class='nuborder' style='overflow:hidden;position:absolute;top:604;left:12;width:968;height:15;text-align:right' >$CRLF";
		$t = nuRunQuery("SELECT * FROM zzsys_user WHERE zzsys_user_id = '$this->zzsys_user_id'");
		$r = db_fetch_object($t);
		if($this->form->sfo_help != ''){
			$help = "title='help' onclick='openHelp(" . '"' . $this->form->zzsys_form_id  . '")' . "'";
		}else{
			$help = "";
		}

		if($r->sus_login_name == ''){$r->sus_login_name = 'globeadmin';}
		print "<span class='unselected' style='font-size:10;font-weight:normal' $help >$r->sus_login_name&nbsp;</span>";
		
		//--login
        print "</div>$CRLF";
        print "<div id='BorderDivC'   class='nuborder' style='overflow:hidden;position:absolute;top:604;left:12;width:200;height:15;text-align:left' >$CRLF";
        print "<span class='unselected' style='font-size:10;font-weight:normal'>Powered by nuBuilder</span></div>$CRLF";

        print  $this->tabHTML;
    }

    private function hiddenFields(){
        print  "$this->CRLF";
        print  "$this->TAB<input type='hidden' name='EMAIL_ADDRESS'          id='EMAIL_ADDRESS'          value=''/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='EMAIL_MESSAGE'          id='EMAIL_MESSAGE'          value=''/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='session_id'             id='session_id'             value='$this->session'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='formsessionID'          id='formsessionID'          value='$this->formsessionID'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='framenumber'            id='framenumber'            value='0'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='beenedited'             id='beenedited'             value='0'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='recordID'               id='recordID'               value='$this->recordID'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='clone'                  id='clone'                  value='$this->cloning'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='close_after_save'       id='close_after_save'       value='0'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='number_of_tabs'         id='number_of_tabs'         value='".count($this->formTabs)."'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='refresh_after_save'     id='refresh_after_save'     value='".$this->form->sfo_refresh_after_save."'/>$this->CRLF";
        print  "$this->TAB<input type='hidden' name='customDirectory'        id='customDirectory'        value='$this->customDirectory'/>$this->CRLF";
    }

    private function buildFormTabs(){
        for($i=0;$i<count($this->formTabs);$i++){
            $this->tabHTML .= $this->buildTab($i, $this->formTabs);
        }
        $this->pageHeader($this->formTabs);

    }
    
    
    private function buildATab($pUnselectedColor, $pSelectedColor, $pTitle, $pLeft, $pTabNumber){

      $w       = strlen($pTitle)* 11;

        if(count($this->formTabs)> 1){ // only have tabs if there is more than one
		  $s =      $this->TAB."<div id='TabNo$pTabNumber' class='selected' style='top:4;left:$pLeft;width:$w;height:50;position:absolute;overflow:hidden;text-align:center'>\n";
		  $s = $s . $this->TAB."   <div style='background:blue;top:0;left:0;width:5;height:5;position:absolute;overflow:hidden;'>\n";
		  $s = $s . $this->TAB."      <div style='background:$pUnselectedColor;top:-23;left:-3;position:absolute;font-size:50px;font-family:arial;color:$pSelectedColor;overflow:hidden;'>&bull;\n";
		  $s = $s . $this->TAB."      </div>\n";
		  $s = $s . $this->TAB."   </div>\n";
		  $s = $s . $this->TAB."   <div style='background:blue;top:0;right:-1;width:7;height:7;position:absolute;overflow:hidden;'>\n";
		  $s = $s . $this->TAB."      <div style='cursor:pointer;background:$pUnselectedColor;top:-23;left:-8;position:absolute;font-size:50px;font-family:arial;color:$pSelectedColor;overflow:hidden;'>&bull;\n";
		  $s = $s . $this->TAB."      </div>\n";
		  $s = $s . $this->TAB."   </div>&nbsp;$pTitle&nbsp;\n";
		  $s = $s . $this->TAB."</div>\n";
		  $s = $s . $this->TAB."<div id='HiddenTabNo$pTabNumber' class='tab' onclick='showTab($pTabNumber)' onmouseover='MIN(this)' onmouseout='MOUT(this)'style='background:$pUnselectedColor;top:4;left:$pLeft;width:$w;height:20;position:absolute;overflow:hidden;text-align:center'>\n";
		  $s = $s . $this->TAB."&nbsp;$pTitle&nbsp;\n";
		  $s = $s . $this->TAB."</div>\n";

        }

      return $s;

    
    }

    private function buildObject($o){

        $formObject                    = new formObject($this, $this->recordID);
		$formObject->parentType        = 'form';
		$formObject->customDirectory   = $this->customDirectory;
		$formObject->session           = $this->session;
		$formObject->activity          = $this->runActivity;
		
		$formObject->formsessionID     = $this->formsessionID;
        $formObject->setObjectProperties($o);
        
        //---decide whether to show or hide this object
        $formObject->displayThisObject = displayCondition($this->arrayOfHashVariables, $o->sob_all_display_condition);

    	//--dont add read only fields to the list or displays (because they canted be edited by the user
     	if($formObject->displayThisObject){                                                                                                           //--isn't displayed
    		if($formObject->objectProperty['sob_' . $formObject->objectProperty['sob_all_type'] . '_read_only'] != '1'){                          //--isn't read only
    			if($formObject->objectProperty['sob_all_type'] == 'text' and $formObject->objectProperty['sob_text_password'] != '1'){//--isn't a password field
    				$GLOBALS['formValues'][]               = $formObject->objectProperty['sob_all_name'];
    			}
    			if($formObject->objectProperty['sob_all_type'] == 'textarea'){
    				$GLOBALS['formValues'][]               = $formObject->objectProperty['sob_all_name'];
    			}
    			if($formObject->objectProperty['sob_all_type'] == 'dropdown'){
    				$GLOBALS['formValues'][]               = $formObject->objectProperty['sob_all_name'];
    			}
    			if($formObject->objectProperty['sob_all_type'] == 'inarray'){
    				$GLOBALS['formValues'][]               = $formObject->objectProperty['sob_all_name'];
    			}
    		}
    	}

        //---get the value and format it
        $formObject->objectValue               = $formObject->setValue($this->recordID, $this->cloning, $this->recordValues[$formObject->objectProperty['sob_all_name']]);
        //---create the html string that will display it
		$formObject->objectHtml        = $formObject->buildObjectHTML($this->CRLF, $this->TAB.$this->TAB.$this->TAB.$this->TAB.$this->TAB,'');
		$this->formObjects[]           = $formObject;
		nuRunQuery("DROP TABLE IF EXISTS $formObject->TT");

    }

    public function execute(){

        $this->appendshowTabfunction();
        $this->buildFormTabs();
        $this->appendJSfunction(setFormatArray());
        $this->displayHeader();
        $this->displayBody();
        $this->displayFooter();

    }

}


class formObject{

	public  $objectProperty      = array();
	public  $lookupArray         = array();
	public  $setup               = array();
	public  $objectValue         = '';
	public  $objectHTML          = '';
	public  $subformObjects      = array();
	public  $recordValues        = array();
	public  $displayThisObject   = true;
	public  $parentType          = '';
	private $parentForm          = null;
	public  $formsessionID       = '';    //----id that is unique to this instance of form
	public  $recordID            = '';
	private $subformRowNumber    = 0;
	public  $subformPrefix       = '';
	public  $objectType          = '';
	public  $objectName          = '';
	public  $TT                  = '';
	public  $customDirectory     = '';
	public  $activity            = '';
	public  $session             = '';    //----id that remains the same throughout login time

    function __construct(Form $form, $pRecordID){

    	$this->parentForm        = $form;
    	$this->recordID          = $pRecordID;
    	$this->TT                = TT();           //---temp table name
    	$this->setup             = $form->setup;

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

		$type             = $this->objectProperty['sob_all_type'];
		$this->objectType = $this->objectProperty['sob_all_type'];
		if ($type    == 'display'){
			//---replace any hashes with variables
	    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_display_sql'], $this->recordID);
	    	//---run sql and use the first row and colomn as the value for this field
            if(trim($sql) == ''){return '';}
		    $t        = nuRunQuery($sql);
		    $r        = db_fetch_row($t);
	    	return formatTextValue($r[0], $this->objectProperty['sob_display_format']);
    	}
	if ($type    == 'dropdown'){
	    	if($this->activity OR $pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
				//---get default value from sob_text_default_value_sql
				if($this->objectProperty['sob_dropdown_default_value_sql']==''){
			    	return '';
				}
				//---replace any hashes with variables
		    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_dropdown_default_value_sql'], $this->recordID);
		    	//---run sql and use the first row and colomn as the default value for this field
			    $t        = nuRunQuery($sql);
			    $r        = db_fetch_row($t);
		    	return $r[0];
			}
	    	return $pValue; //---return value already in record
    	}
	if ($type    == 'lookup'){
	    	if($pValue == '' or $this->activity OR $pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
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

	    	//--get sql from lookup form
            if($this->objectProperty['sob_lookup_load_zzsysform_id'] == ''){
    	    	$lookupFormID                                          = $this->objectProperty['sob_lookup_zzsysform_id'];
            }else{
    	    	$lookupFormID                                          = $this->objectProperty['sob_lookup_load_zzsysform_id'];
            }
	    	$t                                                         = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$lookupFormID'");
	    	$r                                                         = db_fetch_object($t);
    		$browseTable                                               = $this->TT;
	    	$TT                                                        = $this->TT;
		    $this->parentForm->arrayOfHashVariables['#formSessionID#'] = $this->parentForm->formsessionID;
		    $this->parentForm->arrayOfHashVariables['#browseTable#']   = $this->TT;
    		$this->parentForm->arrayOfHashVariables['#TT#']            = $this->TT;

    		eval(replaceHashVariablesWithValues($this->parentForm->arrayOfHashVariables, $r->sfo_custom_code_run_before_browse)); //--replace hash variables then run code

    		$r->sfo_sql           = replaceHashVariablesWithValues($this->parentForm->arrayOfHashVariables, $r->sfo_sql); //--replace hash variables
	    	$SQL                  = new sqlString($r->sfo_sql);

    		if($SQL->getWhere()==''){
	    		$SQL->setWhere("WHERE ".$this->objectProperty['sob_lookup_id_field']." = '$pValue'");
		    }else{
			    $SQL->setWhere($SQL->getWhere() . " AND (".$this->objectProperty['sob_lookup_id_field']." = '$pValue')");
    		}
	    	$SQL->removeAllFields();
		    $SQL->addField($this->objectProperty['sob_lookup_id_field']);
    		$SQL->addField($this->objectProperty['sob_lookup_code_field']);
	    	if($this->objectProperty['sob_lookup_description_field']==''){
		    	$SQL->addField("''");
    		}else{
	    		$SQL->addField($this->objectProperty['sob_lookup_description_field']);
		    }

            if($_GET['debug']!=''){
                tofile('SQL lookup hash variables : debug value:'.$_GET['debug']);
                tofile(print_r($this->parentForm->arrayOfHashVariables,true));
                tofile($SQL->SQL);
            }

    		$T = nuRunQuery($SQL->SQL);


/*            $lookupform                         = formFields($this->objectProperty['sob_lookup_zzsysform_id']);

		    $fld1                               = $this->objectProperty['sob_lookup_id_field'];
		    $fld2                               = $this->objectProperty['sob_lookup_code_field'];
	    	if($this->objectProperty['sob_lookup_description_field']==''){
    		    $fld3                           = '"" AS desc ';
    		}else{
    		    $fld3                           = $this->objectProperty['sob_lookup_description_field'];
		    }
    		$T = nuRunQuery("SELECT $fld1, $fld2, $fld3 FROM $lookupform->sfo_table WHERE $lookupform->sfo_primary_key = '$pValue'");
*/
	    	$R = db_fetch_row($T);
		    $this->lookupArray['id']            = $R[0];
    		$this->lookupArray['code']          = $R[1];
	    	$this->lookupArray['description']   = $R[2];
//		    if($browseTable!=''){nuRunQuery("DROP TABLE IF EXISTS `$this->TT`");}
    		return $pValue; //---return value already in record
    	}
	if ($type    == 'inarray'){
		if($this->activity OR $pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
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
		if($this->activity OR $pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
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
	    	if($this->activity OR $pRecordID == '-1' OR ($pClone AND $this->objectProperty['sob_all_clone'] != '1')){
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

		$dq              = '"';
		$fieldName       = $PREFIX.$this->objectProperty['sob_all_name'];

//   		$fieldTitle      = $this->objectProperty['sob_all_title'];

   		$fieldTitle      = "<div id='$fieldName" . "_title'>" . $this->objectProperty['sob_all_title'] . '</div>';

        if(!$this->displayThisObject){ //--set it blank so no title will be displayed
            $fieldTitle  = '';
        }

		$fieldValue      = htmlentities($this->objectValue);
		$type            = $this->objectProperty['sob_all_type'];

		$id              = uniqid('',true);
		$pValue          = addEscapes($this->objectValue);
		$ses             = $_GET['ses'];

		$s               = "INSERT INTO zzsys_variable (zzsys_variable_id, sva_id, sva_expiry_date, sva_session_id, sva_name, sva_value, sys_added)  ";
		$s               = $s . "VALUES ('$id', '$this->formsessionID', '" . nuDateAddDays(Today(),2) . "', '$ses', '$fieldName', '$pValue', '" . date('Y-m-d H:i:s') . "')";
		if ($type  != 'words'){
			nuRunQuery($s);
		}

		if ($type    == 'button'){
    		$htmlString   = $this->buildHTMLForButton($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX);
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
		if ($type    == 'html'){
    		$htmlString   = $this->buildHTMLForHtml($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue);
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
		if ($type    == 'subform'){
    		$htmlString   = $this->buildHTMLForSubform($CRLF, $TAB, $fieldName, $fieldTitle, $this->objectProperty['sob_subform_blank_rows'], $this->objectProperty['zzsys_object_id']);
    	}
		nuRunQuery("DROP TABLE IF EXISTS `$this->TT`");
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
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}else{
			$rowPrefix            = $this->subformRowNumber;
		}
		if($PREFIX               != ''){
			$untick               = "untick('$PREFIX');";
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
		$s     = $s . $this->addProperty('style'          , $style);
		if($inputType == 'text'){
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'      , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'      , str_replace('.', '', $this->objectProperty['sob_all_class']));
			}
			if($this->objectProperty['sob_text_total']==''){
				$totalUp = '';
			}else{
				$totalUp = "nuColumnTotal(this, '" . $this->objectProperty['sob_text_total'] . "');";
			}
			$s = $s . $this->addProperty('onchange'       , $untick."uDB(this,'text');$totalUp".$this->objectProperty['sob_all_on_change']);
			$s = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);
			$mask = '';
			if($this->objectProperty['sob_text_mask']!=''){
				$mask = ";return nuMask(this,'" . $this->objectProperty['sob_text_mask'] . "')";
			}
			$s = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press'].$mask);
			$s = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_double_click']);
		}
		if($this->objectProperty['sob_text_read_only']=='1'){
			$s = $s . " readonly='readonly'  tabindex='-1' ";
		}

		$s     = $s . "/>$CRLF";
		$format=textFormatsArray();
	    if ($this->displayThisObject){

			if($format[$this->objectProperty['sob_text_format']]->type=='date' and $this->objectProperty['sob_text_read_only']!='1'){
				if($this->setup->set_calendar_mouse_up==''){
					$s   = $s . "<input type='button' class='calbutton' value='c' id='cal_$fieldName' onclick='getCal(\"$fieldName\")' tabindex='-1' style='font-size: xx-small'/>&nbsp;$this->CRLF";
				}else{
                     $s  = $s . "<img  tabindex='-1' class='calbutton' id='cal_$fieldName' src='formimage.php?dir=$this->customDirectory&iid=" . $this->setup->set_calendar_mouse_up . "' alt='Calendar' onmouseup=\"this.src=getImage('" . $this->setup->set_calendar_mouse_up . "')\" onmouseout=\"this.src=getImage('" . $this->setup->set_calendar_mouse_up . "')\" onmousedown=\"this.src=getImage('" . $this->setup->set_calendar_mouse_down . "')\" onclick='getCal(\"$fieldName\")'/>$this->CRLF";
				}
	
			}
	    }	
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
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
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
		$s     = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);

		if($inputType == 'text'){
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_all_class']));
			}
			$s = $s . $this->addProperty('style'          , $style);
		}

		$s     = $s . " readonly='readonly'  tabindex='-1'/>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}

	private function buildHTMLForButton($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

	    if (!$this->displayThisObject){
	    	return '';
		}

    	if($this->parentType      == 'form' and $this->objectProperty['sob_button_top']=='0'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected'></td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
	}else{
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
	}

		$style = '';
		$s     =      $titleTableDetail . $td1;
		if($this->objectProperty['sob_button_top']!='0'){
			$style = $style . $this->addStyle('left', $this->objectProperty['sob_button_left']);
			$style = $style . $this->addStyle('top', $this->objectProperty['sob_button_top']);
			$style = $style . $this->addStyle('position', 'absolute');
		}
		if($this->objectProperty['sob_button_image_on_mouse_up']==''){
			$style = $style . $this->addStyle('width',  $this->objectProperty['sob_button_length']*16);
			$s     = $s . "<input ";
			$s     = $s . $this->addProperty('type'       , 'button');
			$s     = $s . $this->addProperty('title'      , $this->objectProperty['sob_all_tool_tip']);
			$s     = $s . $this->addProperty('value'      , $this->objectProperty['sob_all_title']);
			$s     = $s . $this->addProperty('style'          , $style);
		}else{

			$s     = $s . "<img ";
			$s     = $s . $this->addProperty('src'        , "formimage.php?dir=$this->customDirectory&iid=" . $this->objectProperty['sob_button_image_on_mouse_up']);
			$s     = $s . $this->addProperty('alt'        , $this->objectProperty['sob_all_title']);
			$s     = $s . $this->addProperty('onmouseup'  , "this.src = getImage('" . $this->objectProperty['sob_button_image_on_mouse_up'] . "')");
			$s     = $s . $this->addProperty('onmouseout'  , "this.src = getImage('" . $this->objectProperty['sob_button_image_on_mouse_up'] . "')");
			$s     = $s . $this->addProperty('onmousedown'  , "this.src = getImage('" . $this->objectProperty['sob_button_image_on_mouse_down'] . "')");
			$s     = $s . $this->addProperty('style'          , $style);
		}
		//$fieldName
		$s     = $s . $this->addProperty('name'           , $this->subformPrefix.$fieldName);
		$s     = $s . $this->addProperty('id'             , $this->subformPrefix.$fieldName);
		$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
		if($this->objectProperty['sob_button_zzsys_form_id']!=''){
			$f = $this->objectProperty['sob_button_zzsys_form_id'];
			if($this->objectProperty['sob_button_skip_browse_record_id']==''){//--open form via browse
				$b = $this->objectProperty['sob_button_browse_filter'];
	      		$s = $s . $this->addProperty('onclick'        , "openBrowse('$f', '$b', '', '$this->session', '')");
			}else{//--open form with a certain ID
				$b = $this->objectProperty['sob_button_skip_browse_record_id'];
	      		$s = $s . $this->addProperty('onclick'        , "openForm('$f', '$b')");
			}
		}else{
      		$s = $s . $this->addProperty('onclick'        , $this->objectProperty['sob_all_on_double_click']);
		}
		if($this->objectProperty['sob_all_class']==''){
		    $s = $s . $this->addProperty('class'          , 'button');
		}else{
		    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_all_class']));
		}
		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;
		return $tr1.$s.$tr2;
	}

	private function buildHTMLForDropdown($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}

	    if (!$this->displayThisObject){
	    	$s     = "$TAB  $td1<input type='hidden' name='fieldName' value='$fieldValue'/>$CRLF";
			$s     = $s . "$TAB$CRLF$td2";
		}else{
			$style =      $this->addStyle('width', $this->objectProperty['sob_dropdown_length']*16);
			$s     =      $titleTableDetail . $td1;
			$s     = $s . '<select ';
			$s     = $s . $this->addProperty('name'           , $fieldName);
			$s     = $s . $this->addProperty('id'             , $fieldName);
			$s     = $s . $this->addProperty('value'          , $fieldValue);
			$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
			$s     = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_all_class']));
			}
			$s     = $s . $this->addProperty('style'          , $style);
			$s     = $s . $this->addProperty('onchange'       , $untick."uDB(this);".$this->objectProperty['sob_all_on_change']);
			$s     = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s     = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s     = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
			$s     = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_double_click']);
			if($this->objectProperty['sob_dropdown_read_only']=='1'){
				$s = $s . " disabled='disabled' ";
			}

			$s     = $s . ">$CRLF";


			//---replace any hashes with variables
			if($this->parentType == 'form'){ //--- sets #id# to the record ID of the main form not the subform
				$hashID    = $this->recordID;
			}else{
				$hashID    = $this->parentForm->recordID;
			}
	    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_dropdown_sql'], $hashID);
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
			$s     = $s . "$TAB</select>$CRLF$td2";

		}

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
		$sesQS = 'ses=' . $this->parentForm->session . '&id=' . $this->parentForm->recordID . '&f=' . $this->parentForm->formID;  //--add session, form and record ids to querystring
		$s     = $s . $this->addProperty('src'         , "graph_object.php?$sesQS&dir=$this->customDirectory&graphID=" . $this->objectProperty['zzsys_object_id']);
		$s     = $s . $this->addProperty('id'          , $rowPrefix.$fieldName);
		$s     = $s . $this->addProperty('onclick'     , $this->objectProperty['sob_all_on_double_click']);
		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}



	private function buildHTMLForHtml($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue){

	    if (!$this->displayThisObject){
	    	return '';
		}
    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'></td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}else{
			$rowPrefix            = $this->subformRowNumber;
		}

		$s     = $titleTableDetail . $td1;
        $html  = replaceHashVariablesWithValues($this->parentForm->arrayOfHashVariables, $this->objectProperty['sob_html_code']);

        if($_GET['debug']!=''){
            tofile('sob_html_code hash variables : debug value:'.$_GET['debug']);
            tofile(print_r($this->parentForm->arrayOfHashVariables,true));
            tofile($html);
        }

		$s     = $s . $CRLF . $CRLF . $html . $CRLF . $CRLF . $td2;

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
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
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
			    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_all_class']));
			}
			$s = $s . $this->addProperty('style'          , $style);
			$s = $s . $this->addProperty('onchange'       , $untick."uDB(this);" . $this->objectProperty['sob_all_name'] . '_array(this);'.$this->objectProperty['sob_all_onchange']);
			$s = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
			$s = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_doubleclick']);
			$s = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);
		}
		if($this->objectProperty['sob_text_read_only']=='1'){
			$s = $s . " readonly='readonly'  tabindex='-1' ";
		}

		$s     = $s . "/>$CRLF";
		$s     = $s . $td2;



		//---replace any hashes with variables
    	$sql      = replaceVariablesInString($this->TT,$this->objectProperty['sob_inarray_sql'], $this->recordID);
    	//---run sql and use the first row and colomn as the value for this field
	    $t        = nuRunQuery($sql);
		if(!in_array($this->objectProperty['sob_all_name'], $this->parentForm->inarrayFunctions)){

		    $fun      = "function " . $this->objectProperty['sob_all_name'] . "_array(pThis){ $CRLF";
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
			$this->parentForm->inarrayFunctions[] = $this->objectProperty['sob_all_name'];
	    	$this->parentForm->appendJSfunction($fun);

			
		}

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForListbox($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue){

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' align='center' style='align:center'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' align='left' style='align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
	    if (!$this->displayThisObject){
	    	$s     = $s . "$TAB  <input type='hidden' name='$fieldName' value='$fieldValue'/>$CRLF";
		}else{
			$style =      $this->addStyle('width', $this->objectProperty['sob_listbox_length']*16);
			$s     =$titleTableDetail . $td1;
			if($this->objectProperty['sob_listbox_button_style']==''){
		    	$s = $s . "$TAB  <input type='button' style='$style' class='button' name='sel_$fieldName' value='Select All' onclick=\"SelectAll('$fieldName')\" onblur=\"uDB(document.getElementById('theform').$fieldName,'list');\"/><br/>$CRLF";
			}else{
		    	$s = $s . "$TAB  <input type='button' class='" . $this->objectProperty['sob_listbox_button_style'] . "' name='sel_$fieldName' value='Select All' onclick=\"SelectAll('$fieldName')\" onblur=\"uDB(document.getElementById('theform').$fieldName,'list');\"/><br/>$CRLF";
			}
			$s     = $s . "<select multiple='multiple' ";
			$s     = $s . $this->addProperty('name'           , $fieldName.'[]');
			$s     = $s . $this->addProperty('id'             , $fieldName);
			$s     = $s . $this->addProperty('size'           , $this->objectProperty['sob_listbox_height']);
			$s     = $s . $this->addProperty('value'          , $fieldValue);
			$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
			if($this->objectProperty['sob_all_class']==''){
			    $s = $s . $this->addProperty('class'          , 'objects');
			}else{
			    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_all_class']));
			}
			$s     = $s . $this->addProperty('style'          , $style);
			$s     = $s . $this->addProperty('onchange'       , "uDB(this,'list');".$this->objectProperty['sob_all_on_change']);
			$s     = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s     = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s     = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
			$s     = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_double_click']);
			$s     = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);
			$s     = $s . ">$CRLF";


			//---replace any hashes with variables

	    	$sql            = replaceVariablesInString($this->TT,$this->objectProperty['sob_listbox_sql'], $this->recordID);
	    	//---run sql and use the first row and colomn as the value for this field
		    $t              = nuRunQuery($sql);
		    while($r        = db_fetch_row($t)){
		    	$s          = $s . "$TAB        <option value='$r[0]'>$r[1]</option>$CRLF";
			}

		}
		$s     = $s . "$TAB   </select>$CRLF$td2";

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForLookup($CRLF, $TAB, $fieldName, $fieldTitle, $fieldValue, $PREFIX){

    	if($this->parentType      == 'form'){ //--not a subform
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
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

			$style =          $this->addStyle('width', $this->objectProperty['sob_lookup_code_length']*16);

			$s     = $s . "$TAB  <input ";
			$s     = $s . $this->addProperty('name'           , 'code'.$fieldName);
			$s     = $s . $this->addProperty('id'             , 'code'.$fieldName);
			$s     = $s . $this->addProperty('value'          , htmlentities($this->lookupArray['code']));
			$s     = $s . $this->addProperty('accesskey'      , $this->objectProperty['sob_all_access_key']);
			if($this->objectProperty['sob_lookup_code_class'] == ''){
			    $s = $s . $this->addProperty('class'          , 'lookupcode');
			}else{

			    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_lookup_code_class']));
			}
			$s     = $s . $this->addProperty('style'          , $style);
			$s     = $s . $this->addProperty('onchange'       , $untick."validateLU('".$this->objectProperty['zzsys_object_id']."', '$PREFIX', this.value, '$this->formsessionID');".$this->objectProperty['sob_all_on_change']);
			$s     = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
			$s     = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
			$s     = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
			$s     = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);

			$f     = $this->objectProperty['sob_lookup_zzsysform_id'];
			$i     = $this->objectProperty['zzsys_object_id'];


			if($this->objectProperty['sob_lookup_read_only']=='1'){
				$s = $s . " readonly='readonly'  tabindex='-1' ";
			}else{
	      		$s = $s . $this->addProperty('ondblclick'        , "openBrowse('$i', '', '$PREFIX', '$this->session', '$this->formsessionID')");
			}
			$s     = $s . "/>$CRLF";
			if($this->objectProperty['sob_lookup_no_description'] != 1){
				$style  = $this->addStyle('width', $this->objectProperty['sob_lookup_description_length']*16);
				$ds     = $ds . $this->addProperty('name'           , 'description'.$fieldName);
				$ds     = $ds . $this->addProperty('id'             , 'description'.$fieldName);
				$ds     = $ds . $this->addProperty('value'          , htmlentities($this->lookupArray['description']));
				if($this->objectProperty['sob_lookup_description_class'] == ''){
				    $ds = $ds . $this->addProperty('class'          , 'lookupdesc');
				}else{
				    $ds = $ds . $this->addProperty('class'          , $this->objectProperty['sob_lookup_description_class']);
				}
				$ds     = $ds . $this->addProperty('style'          , $style);
				$s = $s . "$TAB    <input $ds readonly='readonly' tabindex='-1'/>$CRLF";
			}
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
	    	$titleTableDetail     = "$TAB  <td class='selected' style='text-align:right'>$fieldTitle</td>$CRLF";
	    	$tr1                  = "$TAB<tr class='selected'>$CRLF";
	    	$tr2                  = "$TAB</tr>$CRLF";
	    	$td1                  = "$TAB  <td class='selected' style='text-align:left'>$CRLF$TAB    ";
	    	$td2                  = "$TAB  </td>$CRLF";
		}
		if($PREFIX               != ''){
			$untick              = "untick('$PREFIX');";
		}

		$style =          $this->addStyle('width', $this->objectProperty['sob_textarea_length']*16);
		//17-06-09 -- Nick : added these in to prevent a horizontal scrollbar
		// vvvv				being present for textareas in both IE and FF
		$style = $style . $this->addStyle('overflow','scroll');
		$style = $style . $this->addStyle('overflow-y','scroll');
		$style = $style . $this->addStyle('overflow-x','hidden');
		$style = $style . $this->addStyle('overflow','-moz-scrollbars-vertical');
		// ^^^^
		//17-06-09
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
			    $s = $s . $this->addProperty('class'          , str_replace('.', '', $this->objectProperty['sob_all_class']));
			}
			if($this->objectProperty['sob_textarea_read_only'] == '1'){
				$s = $s . " readonly='readonly'  tabindex='-1' ";
			}
		$s     = $s . $this->addProperty('style'          , $style);
		$s     = $s . $this->addProperty('onchange'       , $untick."uDB(this);".$this->objectProperty['sob_all_on_change']);
		$s     = $s . $this->addProperty('onblur'         , $this->objectProperty['sob_all_on_blur']);
		$s     = $s . $this->addProperty('onfocus'        , $this->objectProperty['sob_all_on_focus']);
		$s     = $s . $this->addProperty('onkeypress'     , $this->objectProperty['sob_all_on_key_press']);
		$s     = $s . $this->addProperty('ondblclick'     , $this->objectProperty['sob_all_on_double_click']);
		$s     = $s . $this->addProperty('title'          , $this->objectProperty['sob_all_tool_tip']);

		$s     = $s . ">$fieldValue</textarea>$CRLF";
	    if (!$this->displayThisObject){
	    	$s = '';
	    }
		$s     = $s . $td2;

		return $tr1.$s.$tr2;
	}


	private function buildHTMLForWords($CRLF, $TAB){

    	$fieldValue           = $this->objectProperty['sob_all_title'];

	    if ($this->displayThisObject){
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


	private function buildHTMLForSubform($CRLF, $TAB, $fieldName, $fieldTitle, $blankRows, $objectID){

	    if (!$this->displayThisObject){
	    	return '';
		}else{
			$this->parentForm->subformNames[] = $this->objectProperty['sob_all_name'];
			$this->parentForm->subformTabs[]  = $this->parentForm->formTabNames[$this->objectProperty['sob_all_tab_title']];
		}
		$format=textFormatsArray();
		if($this->objectProperty['sob_all_on_change']!=''){
			$OnChange = 'onchange="' . $this->objectProperty["sob_all_on_change"] . '" ';
		}
		$this->CRLF                       = $CRLF;
		$this->TAB                        = $TAB;
		$hGap                             = 3;                                                   //gap between fields
		$vHeight                          = 23;                                                  //row height
		$vTitleHeight                     = ($this->objectProperty['sob_subform_title_height']*16);                                                  //row height
		$vTop                             = 10;                                                  //row height
		$sfDimensions                     = array();
		$fldDimensions                    = array();
		$t                                = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '".$this->objectProperty['zzsys_object_id']."' ORDER BY sob_all_column_number, sob_all_order_number");
		while($r                          = db_fetch_array($t)){
			$objectLength                 = $r['sob_'.$r['sob_all_type'].'_length'];                 // eg. text objects are 'sob_text_length'
			if ($r['sob_all_type']        == 'lookup'){
				$objectLength             = $r['sob_lookup_code_length'];                        // code length
				if($r['sob_lookup_no_description'] != '1'){
					$objectLength         = $objectLength + .5 + $r['sob_lookup_description_length']; // description length
				}
			}
			if ($r['sob_all_type']        == 'text'){
				if($format[$r['sob_text_format']]->type == 'date'){
					$objectLength                                   = $objectLength + 1.5;       // date button length
				}
	    	}
			$fldDimensions[$r['sob_all_name']]->type                = $r['sob_all_type'];
			$fldDimensions[$r['sob_all_name']]->columnAlign         = 'left';
			if($r['sob_all_type'] == 'text'){
				$fldDimensions[$r['sob_all_name']]->columnAlign     = $r['sob_text_align'];
			}
			if($r['sob_all_type'] == 'display'){
				$fldDimensions[$r['sob_all_name']]->columnAlign     = $r['sob_display_align'];
			}
			$fldDimensions[$r['sob_all_name']]->columnTitle         = $r['sob_all_title'];
			$fldDimensions[$r['sob_all_name']]->column              = $r['sob_all_column_number'];
			$fldDimensions[$r['sob_all_name']]->leftCoordinate      = round($sfDimensions[$r['sob_all_column_number']]->columnWidth);
			$fldDimensions[$r['sob_all_name']]->columnWidth         = ($objectLength * 16) + $hGap;
			$sfDimensions[$r['sob_all_column_number']]->columnWidth = round($sfDimensions[$r['sob_all_column_number']]->columnWidth) + $fldDimensions[$r['sob_all_name']]->columnWidth;
			$colHeight                                              = round($sfDimensions[$r['sob_all_column_number']]->columnHeight);
			if($colHeight == 0){
				$sfDimensions[$r['sob_all_column_number']]->columnHeight = $vHeight;
				$colHeight                                               = $sfDimensions[$r['sob_all_column_number']]->columnHeight;
			}
			if($r['sob_all_type'] == 'textarea' AND $colHeight < $r['sob_textarea_height'] * 16){
				$sfDimensions[$r['sob_all_column_number']]->columnHeight = $r['sob_textarea_height'] * 16;
			}
		}
		$longest = '';

		foreach($sfDimensions as $key => $value){                                               // get longest row
			if($value->columnWidth > $longest){$longest                 = $value->columnWidth;}
			$rowHeight = $rowHeight + $value->columnHeight;
		}
		$prntCheckBox                                                   = $this->objectProperty['sob_subform_delete_box'] == '1' and $this->objectProperty['sob_subform_read_only'] != '1';

		if($prntCheckBox){                               //add room for the delete tick box
			$longest                                                    = $longest + $vHeight;
		}else{
			$longest                                                    = $longest + 20;
		}

//add scroll bar
		$sfWidth    = $longest + 20;
		$sfLeft     = $this->objectProperty['sob_subform_left'];
//add height of subform title + column title(s) to overall height
		$sfHeight   = ($this->objectProperty['sob_subform_height']*16) -($this->objectProperty['sob_subform_title_height']*16) -100;
		$sfTop      = $this->objectProperty['sob_subform_top'];

		if($this->objectProperty['sob_subform_width']==0){
			$scsfWidth  = $sfWidth + 10;
		}else{
			$scsfWidth  = $this->objectProperty['sob_subform_width'];
		}
		
		$scsfHeight = $sfHeight - 20;
		$scsfHeight = $sfHeight - 16;//changed from -10 to -16

		$s          =      "<div name='sf_title$fieldName' id='sf_title$fieldName' class='selected' style='text-align:left;position:absolute;height:20;top:$sfTop;left:$sfLeft;width:$sfWidth;'>$fieldTitle</div>$CRLF";
		$sfTop      = $sfTop + 20;
		$s          = $s . "$TAB<div name='$fieldName' id='$fieldName' class='selected' style='position:absolute;overflow:auto;width:$scsfWidth;height:$scsfHeight;top:$sfTop;left:$sfLeft;'>$CRLF";
		$s          = $s . "$TAB   <div name='title$fieldName' id='title$fieldName' style='position:absolute;top:0;left:0;background:#6D7B8D'>$CRLF";

// build subform column titles
 		$columnTop            = 0;
 		$columnNumber         = '';
		foreach($fldDimensions as $key => $value){
			if($columnNumber != $value->column){
				if($columnNumber == ''){
					$columnNumber = $value->column;
				}
			}
			if($columnTop == 0){// only print headings for the first row (sob_subform_column_order)
				if($columnNumber == $value->column){
				$width    = $value->columnWidth;
        		$dbc      = '';

        	    if($this->parentForm->zzsys_user_id == 'globeadmin' and $this->form->sys_setup != '1'){
        			$dbc    = " ondblclick=\"openBrowse('object', '$objectID', '', '".$this->parentForm->session."', '')\"";
        	    }

				$s   = $s . "$TAB      <div $dbc class='unselected' style='vertical-align:top;font-size:x-small;font-family:tahoma;font-weight:bold;top:0;left:$value->leftCoordinate;width:$width;height:$vTitleHeight;overflow:hidden;position:absolute;text-align:".align($value->columnAlign).";'>$CRLF";
				$s  = $s  . "$TAB         $value->columnTitle$CRLF";
				$s  = $s  . "$TAB      </div>$CRLF";
						$nextLeft  = $value->leftCoordinate + $value->columnWidth;
				}
			}
		}

		$sfHeight = $sfHeight - $columnTop-$vHeight;                 //  adjusting for scrollng div
		$this->objectName  = $this->objectProperty['sob_all_name'];  //  set subform name
//add room for the delete tick box

		if($this->objectProperty['sob_subform_read_only'] != '1'){
			$s   = $s . "$TAB      <div class='unselected' style='top:0;left:$nextLeft;width:50;height:$vTitleHeight;overflow:hidden;position:absolute;align:left;'>$CRLF"; //align:left removed
			if($prntCheckBox){
				$s  = $s  . "$TAB         <font style='vertical-align:top;font-size:xx-small;font-family:tahoma;font-weight:bold;'>&nbsp;Delete&nbsp;</font>$CRLF";
			}
			$s  = $s  . "$TAB      </div>$CRLF";
		}//end of subform column titles

//start scrolling div
		$sfHeight     = $sfHeight - $vTitleHeight; // adjust a bit to see all of scroll bar
		$columnTop    = $columnTop + $vTitleHeight;
		//added by nick 10-06-09 $grey needs to be defined before it can be used
		//vvvvv
		$grey                 = iif($grey==$this->objectProperty['sob_subform_odd_background_color'],$this->objectProperty['sob_subform_even_background_color'],$this->objectProperty['sob_subform_odd_background_color']);
		//^^^^^
		$subformClass = str_replace('.', '', $this->objectProperty['sob_all_class']);
		$s            = $s . "$TAB         <div name='scroller$fieldName' class='$subformClass' id='scroller$fieldName' style='border-style:solid;border-width:2;border-color:white;position:absolute;overflow:scroll;width:$sfWidth;height:$sfHeight;top:$columnTop;left:0;background:$grey;'>$CRLF";

//put subform objects in an array
		$subformObjects           = array();
		$t                        = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '".$this->objectProperty['zzsys_object_id']."' ORDER BY sob_all_column_number, sob_all_order_number");
		while($r                  = db_fetch_object($t)){
			$subformObjects[]     = $r;
		}

//get SQL for subform
//		$subformSQL               = replaceVariablesInString($this->TT,$this->objectProperty['sob_subform_sql'], $this->recordID);


//get SQL for subform //-- added by sc 4-feb-2009
		if(is_array($this->parentForm->recordValues)){
			$hVariables           = arrayToHashArray($this->parentForm->recordValues);                                               //--session values (access level and user etc. )
		}
		$subformSQL               = replaceVariablesInString($this->TT,$this->objectProperty['sob_subform_sql'], $this->recordID);
		if(is_array($this->parentForm->recordValues)){
			$subformSQL           = replaceHashVariablesWithValues($hVariables, $subformSQL);
		}
//---------

		$subformTable             = nuRunQuery($subformSQL);
 		$columnTop                = (($vHeight)*-1)+5;
 		$nextTop                  = 0;
		$columnNumber             = '';

//loop through subform records
		if($this->parentForm->cloning == '1'){
			$primaryKey           = '';
		}else{
			$primaryKey           = $this->objectProperty['sob_subform_primary_key'];
		}
		while($subformRecord      = mysql_fetch_array($subformTable)){
			$this->recordID       = $subformRecord[$this->objectProperty['sob_subform_primary_key']];
			$this->nextRowNumber();
//loop through each object for this subform record
			$newRow               = true;
			$grey                 = iif($grey==$this->objectProperty['sob_subform_odd_background_color'],$this->objectProperty['sob_subform_even_background_color'],$this->objectProperty['sob_subform_odd_background_color']);
			$dq                   = '"';
			$s                    = $s . "$TAB               <div id='rowdiv_$this->subformPrefix' onfocus='SFrowColor(this, $dq$fieldName$dq)' style='background:$grey;height:$rowHeight'>$CRLF";
			$checkBoxDone         = false;
			for($i = 0 ; $i < count($subformObjects) ; $i++){
				$subformFieldDiv  = $fldDimensions[$subformObjects[$i]->sob_all_name];
				if($columnNumber != $subformFieldDiv->column OR $i == 0){
					$columnNumber = $subformFieldDiv->column;
					$columnTop    = $nextTop;
					$nextTop      = $columnTop + $sfDimensions[$columnNumber]->columnHeight;
				}
//add room for the delete tick box
				if($prntCheckBox and !$checkBoxDone){
					$checkBoxDone = true;

					$s            = $s . "$TAB               <div style='position:absolute;top:$columnTop;left:$nextLeft'>$CRLF";
					$s            = $s . "$TAB                  <input name='row$this->subformPrefix' id='row$this->subformPrefix' type='checkbox' $OnChange tabindex='-1'/>$CRLF";
					$s            = $s . "$TAB               </div>$CRLF";
				}
				$s                = $s . "$TAB               <div name='row$this->subformPrefix' id='row$this->subformPrefix' style='background:lightgray;position:absolute;top:$columnTop;left:$subformFieldDiv->leftCoordinate'>$CRLF";
				$fieldWidth       = $subformFieldDiv->columnWidth - $hGap;
				if($newRow){
					$s            = $s . "$TAB                  <input name='$this->subformPrefix$primaryKey' id='$this->subformPrefix$primaryKey' value='$this->recordID' type='hidden'/>$CRLF";
					$newRow       = false;
				}
				$s                = $s . "$TAB                  ".$this->buildObject($subformObjects[$i],$subformRecord)."$CRLF";
				$s                = $s . "$TAB               </div>$CRLF";
			}
			$rowNumber            = $rowNumber + 1;
			$newRow               = true;
			$s                    = $s . "$TAB               </div>$CRLF";
		}

		$sfRowTotal               = $rowNumber + $blankRows;
		$columnNumber             = '';

//loop through blank subform records
		for($blankRecord          = 0 ;  $blankRecord < $blankRows ; $blankRecord++){
			$this->recordID       = '-1';
			$this->nextRowNumber();
//loop through each object for this subform record
			$grey                 = iif($grey==$this->objectProperty['sob_subform_odd_background_color'],$this->objectProperty['sob_subform_even_background_color'],$this->objectProperty['sob_subform_odd_background_color']);
//			$grey                 = iif($grey=='#E0E0E0 ','#F0F0F0','#E0E0E0 ');
			$s                    = $s . "$TAB               <div style='background:$grey;height:$rowHeight'>$CRLF";
			$checkBoxDone = false;
			for($i = 0 ; $i < count($subformObjects) ; $i++){
				$subformFieldDiv  = $fldDimensions[$subformObjects[$i]->sob_all_name];
				if($columnNumber != $subformFieldDiv->column){
					$columnNumber = $subformFieldDiv->column;
					$columnTop    = $nextTop;
					$nextTop      = $columnTop + $sfDimensions[$columnNumber]->columnHeight;
				}
//add room for the delete tick box
				if($prntCheckBox and !$checkBoxDone){
					$checkBoxDone = true;
					$s            = $s . "$TAB               <div style='position:absolute;top:$columnTop;left:$nextLeft'>$CRLF";
					$s            = $s . "$TAB                  <input name='row$this->subformPrefix' id='row$this->subformPrefix' type='checkbox' $OnChange tabindex='-1' checked='checked'/>$CRLF";
					$s            = $s . "$TAB               </div>$CRLF";
				}
				$s                = $s . "$TAB               <div name='$this->subformPrefix' id='$this->subformPrefix' style='position:absolute;top:$columnTop;left:$subformFieldDiv->leftCoordinate'>$CRLF";
				$fieldWidth       = $subformFieldDiv->columnWidth - $hGap;
				$s                = $s . "$TAB                  ".$this->buildObject($subformObjects[$i],$subformRecord)."$CRLF";
				$s                = $s . "$TAB               </div>$CRLF";
			}
			$rowNumber            = $rowNumber + 1;
			$columnNumber         = '';
			$s                    = $s . "$TAB               </div>$CRLF";

		}

		$s                        = $s . "$TAB         </div>$CRLF";
		$s                        = $s . "$TAB      </div>$CRLF";
		$s                        = $s . "$TAB   </div>$CRLF";
		$s                        = $s . "$TAB   <div style='position:absolute;overflow:hidden;width:0;height:0;top:0;left:10;background:blue;'>$CRLF";
		$sfColumns                = count($fldDimensions);

		$s                        = $s . "$TAB      <input name='subformid$fieldName' id='subformid$fieldName' value='".$this->objectProperty['zzsys_object_id']."' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='rows$fieldName' id='rows$fieldName' value='$sfRowTotal' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='columns$fieldName' id='columns$fieldName' value='$sfColumns' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='table$fieldName' id='table$fieldName' value='".$this->objectProperty['sob_subform_table']."' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='foreignkey$fieldName' id='foreignkey$fieldName' value='".$this->objectProperty['sob_subform_foreign_key']."' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='primarykey$fieldName' id='primarykey$fieldName' value='".$this->objectProperty['sob_subform_primary_key']."' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='readonly$fieldName' id='readonly$fieldName' value='".$this->objectProperty['sob_subform_read_only']."' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='deletebox$fieldName' id='deletebox$fieldName' value='".$this->objectProperty['sob_subform_delete_box']."' type='hidden' />$CRLF";

		$s                        = $s . "$TAB      <input name='rowColor_$fieldName' id='rowColor_$fieldName' value='".$this->objectProperty['sob_subform_selected_row_color']."' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='lastRow_$fieldName' id='lastRow_$fieldName' value='' type='hidden' />$CRLF";
		$s                        = $s . "$TAB      <input name='lastColor_$fieldName' id='lastColor_$fieldName' value='' type='hidden' />$CRLF";



		$cNo                      = 0;
		foreach($fldDimensions as $key => $value){
			$s                    = $s . "$TAB      <input name='$fieldName$cNo' id='$fieldName$cNo' value='$key' type='hidden' />$CRLF";
			$cNo                  = $cNo + 1;
		}
		$s                        = $s . "$TAB</div>$CRLF";

		return $s;

	}

    private function buildObject($o,$r){

        $subformObject                    = new formObject($this->parentForm, $this->recordID);
		$subformObject->parentType        = 'subform';
		$subformObject->session           = $this->parentForm->session;
        $subformObject->formsessionID     = $this->parentForm->formsessionID;
        $subformObject->customDirectory   = $this->parentForm->customDirectory;

        $subformObject->setObjectProperties($o);
        //---decide whether to show or hide this object
        $subformObject->displayThisObject = displayCondition($this->parentForm->arrayOfHashVariables, $o->sob_all_display_condition);
        //---get the value and format it
        if($subformObject->objectProperty['sob_'.$subformObject->objectProperty['sob_all_type'].'_read_only'] != '1'){
    	    $GLOBALS['formValues'][]          = $this->subformPrefix.$subformObject->objectProperty['sob_all_name'];
        }
        $subformObject->objectValue       = $subformObject->setValue($this->recordID, $this->parentForm->cloning, $r[$subformObject->objectProperty['sob_all_name']]);
        //---create the html string that will display it
		$subformObject->objectHtml        = $subformObject->buildObjectHTML($this->CRLF, $this->TAB.$this->TAB,$this->subformPrefix);
		return $subformObject->objectHtml;

    }


}


?>
