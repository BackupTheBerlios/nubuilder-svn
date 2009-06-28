<?php
/*
** File:           nureportbuilder.php
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

$dir                             = $_GET['dir'];
$reportID                        = $_GET['r'];

include("../$dir/database.php");
include('common.php');


	$t           = nuRunQuery("SELECT sat_report_display_code FROM zzsys_activity WHERE zzsys_activity_id = '$reportID'");
	$r           = db_fetch_row($t);
	if($r[0]==''){
		eval('class Reporting{var $nuBuilder = "1";var $Controls = array();var $Sections = array();var $Groups = array();var $Width = "900";var $Height = "";function Reporting(){}}');
	}else{
		eval($r[0]);
	}
	
?>

<html>

<style TYPE='text/css'>
.moveup            {position:relative;background-color:gray;border:solid;border-width:1;color:white;border-color:black}
.delSection        {position:relative;background-color:gray;border:solid;border-width:1;color:white;border-color:black}
.theObject         {cursor:default;overflow:hidden;position:absolute;}
.theHeader         {position:absolute;font-size:14;color:white;background-color:black;height:10;}
.thePropertyLeft   {position:absolute;font-size:14;color:white;height:10;left:10}
.thePropertyRight  {position:absolute;font-size:14;color:white;height:10;left:200;width:400}
.thesortorderRight {position:absolute;font-size:14;color:white;height:10;left:330;width:400}
.thesortorderLeft {position:absolute;font-size:14;color:white;height:10;left:20;width:300}
</style>


<script language='javascript'>

   var theaction           = '';
   var offsetX             = 0;
   var offsetY             = 0;
   var sizeX               = 0;
   var sizeY               = 0;
   var idName              = '';
   var mouseIsDown         = false;
   var shiftIsDown         = false;
   var customStyleType	   = new Array();
   

   
//-------------------------------DEBUG WINDOW-------------   
// Debugging console window variable
var g_dbgwnd;

// Display and add text to a debugging console window
function dbgwnd(text) {

	// Open the console window if it isn't currently
	if ((undefined == g_dbgwnd) || g_dbgwnd.closed) {
	
		// Open a new popup window
		g_dbgwnd = window.open('about:blank', '_blank', 'location=no, menubar=no, status=no, toolbar=no, resizable=yes');
	
		// Set up the base HTML document
		g_dbgwnd.document.write('<html><head><title>Debug Window</title></head><body style="font-family: monospace;"><h2>Debugging Output</h2><br/>\n');
	}
	
	// Write the debugging text to the page
	g_dbgwnd.document.write(text + '<br/>\n');
}
   
//-------------------------------END DEBUG WINDOW-------------   
   
//used to send messages to a textarea on the page. the textarea must exist for this to work
//add to  file: <textarea id='debugconsole' cols='120' rows='6'></textarea>
function debugCon(message){
	//document.getElementById('debugconsole').value += message;
}
   
function dumpCustomStyleArray(){
	for(key in customStyleType){
		debugCon("\nk: "+key+" v: " +customStyleType[key]);
	}
}
   
function nuDebug(){
	eval("alert("+prompt()+")");
}

function buildClass(){
	//dumpCustomStyleArray(); //dump all the custom values to the debug box
	var r = "";
	var s = "";
	var c = "";
	var g = "\n";
	r =     "class Reporting{\n\n";
	r = r + "   var $nuBuilder          = '1';\n";
	r = r + "   var $Controls           = array();\n";
	r = r + "   var $Sections           = array();\n";
	r = r + "   var $Groups             = array();\n";
	r = r + "   var $Version            = '2';\n";
	r = r + "   var $Width              = '" + document.getElementById('rproperty00').value + "';\n";
	r = r + "   var $Height             = '" + document.getElementById('rproperty01').value + "';\n";
	r = r + "   var $PaperType          = '" + document.getElementById('rproperty02').value + "';\n";
	r = r + "   var $Orientation        = '" + document.getElementById('rproperty03').value + "';\n\n";
	r = r + "\n   function Reporting(){";

	obNumber = -1;
	for(i = 0 ; i < reportSection.length ; i++){
		curSection = getSectionNumber(reportSection[i]);
		curDiv  = document.getElementById(reportSection[i]);
		s = s + "      $this->Sections[" + curSection + "]->Name                = '" + reportSection[i] +"';\n";
		s = s + "      $this->Sections[" + curSection + "]->ControlType         = '"   + curSection +"';\n";
		s = s + "      $this->Sections[" + curSection + "]->Height              = '"   + getStyle(reportSection[i], 'height') + "';\n";
		s = s + "      $this->Sections[" + curSection + "]->BackColor           = '"   + reformatHexOrRGBToHex(getStyle(reportSection[i], 'backgroundColor')) +"';\n";
		s = s + "      $this->Sections[" + curSection + "]->Tag                 = '"   + getStyle(reportSection[i],'tag') +"';\n";
		s = s + "      $this->Sections[" + curSection + "]->SectionNumber       = '"   + curSection +"';\n\n";
		for(ch = 0 ; ch < curDiv.childNodes.length ; ch++){
			if(curDiv.childNodes[ch].nodeType!=3){
				obID = curDiv.childNodes[ch].id;
				if(obID.substr(0,5)!='text_' && obID != null && obID.length > 0){
					obNumber      = obNumber + 1;
					theObjectType = getStyle(obID, 'type');
					graphValue    = getStyle(obID, 'graph');
					//alert(document.getElementById(obID).value + " gval:" + graphValue); //html standards don't allow for custom element values. IE lets it happen, firefox doesn't.
						//if(graphValue == null){
						//	debugCon("graphValue is null " + document.getElementById(obID).value);
						//}else{
						//debugCon("graphValue is " + graphValue);
						//}
					graphCode     = graphValue.substr(6);
					graphType     = graphValue.substr(0,5);
					c = c + "\n";
					c = c + "      $this->Controls[" + obNumber + "]->Name                = '"   + getStyle(obID, 'name')               + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Section             = '"   + curSection                           + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->ControlType         = '"   + getStyle(obID, 'type')               + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Tag                 = '"   + getStyle(obID, 'tag')                + "';\n";
                    fixValue                                                              = getStyle(obID,'value');
					//fixValue = document.getElementById(obID).value;
					debugCon(fixValue);
                    fixValue                                                              = fixValue.replace(/'/,'');
					c = c + "      $this->Controls[" + obNumber + "]->ControlSource       = '"   + fixValue                             + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Caption             = '"   + fixValue                             + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Value               = '"   + fixValue                             + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Top                 = '"   + getStyle(obID, 'top')                + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Left                = '"   + getStyle(obID, 'left')               + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Width               = '"   + getStyle(obID, 'width')              + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->Height              = '"   + getStyle(obID, 'height')             + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->ForeColor           = '"   + getStyle(obID, 'color')              + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->FontSize            = '"   + parseInt(getStyle(obID, 'fontSize')) + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->FontWeight          = '"   + getStyle(obID, 'fontWeight')         + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->FontName            = '"   + getStyle(obID, 'fontFamily')         + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->BackColor           = '"   + getStyle(obID, 'backgroundColor')    + "';\n";
					if(getStyle(obID, 'name') == document.getElementById('currentID').value){
						c = c + "      $this->Controls[" + obNumber + "]->BorderWidth         = '"   + reformatWidth(getStyle(obID, 'widthwas'))    + "';\n";
						c = c + "      $this->Controls[" + obNumber + "]->BorderColor         = '"   + reformatHexOrRGBToHex(getStyle(obID, 'colorwas'))                   + "';\n";
					}else{
						c = c + "      $this->Controls[" + obNumber + "]->BorderWidth         = '"   + reformatWidth(getStyle(obID, 'borderWidth')) + "';\n";
						c = c + "      $this->Controls[" + obNumber + "]->BorderColor         = '"   + reformatHexOrRGBToHex(getStyle(obID, 'borderLeftColor'))                + "';\n";
					}
					c = c + "      $this->Controls[" + obNumber + "]->Graph               = '"   + graphCode                            + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->CanGrow             = '"   + getStyle(obID, 'cangrow')            + "';\n";
					c = c + "      $this->Controls[" + obNumber + "]->TextAlign           = '"   + getStyle(obID, 'textAlign')          + "';\n";
					if(theObjectType == 'Graph'){
						c = c + "      $this->Controls[" + obNumber + "]->Format              = '"   + graphType                            + "';\n";
					}else{
						c = c + "      $this->Controls[" + obNumber + "]->Format              = '"   + getStyle(obID, 'format')             + "';\n";
					}
				}
			}
		}
	}
	for(gg = 0 ; gg < 8 ; gg++){
		if(document.getElementById('property0'+gg).value!=''){
			g = g + "      $this->Groups["+gg+"]->Field                 = '"+document.getElementById('property0'+gg).value+"';\n";
			if(document.getElementById('sort0'+gg).value == ''){
				g = g + "      $this->Groups["+gg+"]->SortOrder             = '';\n";
			}else{
				g = g + "      $this->Groups["+gg+"]->SortOrder             = 'DESC';\n";
			}
		}
	}
	s = r + c + s + g + "\n   }\n}";
	
	
	document.getElementById('classcode').value = s;
    copyClass();
}
//added by nick
//ensures that width is written as "NUMBERpx"
function reformatWidth(pxString){
	return parseInt(pxString) + "px";
}

function reformatHexOrRGBToHex(colorstr){
	//alert(colorstr);
	var result = /^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/.exec(colorstr);
	if(result){
		colorstr = '#'+parseInt(result[1]).toString(16)+parseInt(result[2]).toString(16)+parseInt(result[3]).toString(16);
	}
	if(colorstr == 'rgb(0, 0, 0)'){
		colorstr = '#'+'00'+'00'+'00';
	}
	return colorstr
}

//added by nick
function selectSectionObjects(){
	clearObjectList();
	var r = "";
	var s = "";
	var c = "";
	var g = "\n";
	var theSectionID = 	document.getElementById('currentSectionID').value;
	obNumber = -1;
	for(i = 0 ; i < reportSection.length ; i++){
		curSection = getSectionNumber(reportSection[i]);
		curDiv  = document.getElementById(reportSection[i]);
		if(reportSection[i] == theSectionID){
			for(ch = 0 ; ch < curDiv.childNodes.length ; ch++){
				if(curDiv.childNodes[ch].nodeType!=3){
					obID = curDiv.childNodes[ch].id;
					//debugCon("adding>" + obID + "<to object list. has values such as: " + curDiv.childNodes[ch].value);
					if(obID.substr(0,5)!='text_'){
						if(obID != null && obID.length > 0){
							obNumber = obNumber + 1;
							var tempID = getStyle(obID, 'name');
							addToObjectList(tempID);
							//addToObjectList(obID);
						}
					}
				}
			}
		}
	}
	// s = r + c + s + g;
}

function getSectionNumber(pID){

	theSort = pID.substr(0,pID.length-7);
	theSpot = pID.substr(pID.length-7);

	if(pID == 'Detail')       {return 0;}
	if(pID == 'Report_Header'){return 1;}
	if(pID == 'Report_Footer'){return 2;}
	if(pID == 'Page_Header')  {return 3;}
	if(pID == 'Page_Footer')  {return 4;}
	return sortRow(theSort, theSpot);
}

function sortRow(ptheSort, ptheSpot){

	for(sl = 0 ; sl < 8 ; sl++){
		if(document.getElementById('property0'+sl).value == ptheSort){
			if(ptheSpot == '_Header'){
				return 5 + (sl*2);
			}else{
				return 6 + (sl*2);
			}
		}
	}
}



function buildSectionArray(){

	theSections                        = Array();
	theHidden                          = Array();
	theParent                          = document.getElementById('Detail').parentNode;
	for(rs = 0 ; rs < reportSection.length ; rs++){
		theDiv                         = document.getElementById(reportSection[rs]);
		theSections[reportSection[rs]] = theParent.removeChild(theDiv);
	}
	for(rs = 0 ; rs < hiddenSection.length ; rs++){
		theDiv                         = document.getElementById(hiddenSection[rs]);
		theHidden[rs]                  = theParent.removeChild(theDiv);
	}

	reportSectionRequery();
	replacePropertyValueSelect('object', '01', '', true, reportSection, 'moveToSection("", this.value)');

	for(rs = 0 ; rs < reportSection.length ; rs++){
		theParent.appendChild(theSections[reportSection[rs]]);
	}
	for(rs = 0 ; rs < theHidden.length ; rs++){
		theParent.appendChild(theHidden[rs]);
	}

}


//--rebuild 'reportSection' array and section list for object properties
function reportSectionRequery(){
	
	reportSection.splice(0,40)
	reportSection[0] = 'Report_Header';
	reportSection[1] = 'Page_Header';
	arrayNo = 1;
	for(sl = 0 ; sl < 8 ; sl++){
		if(document.getElementById('property0'+sl).value!=''){
			arrayNo = arrayNo + 1;
			reportSection[arrayNo] = document.getElementById('property0'+sl).value+'_Header'
		}
	}

	arrayNo = arrayNo + 1;
	reportSection[arrayNo] = 'Detail';

	for(sl = 7 ; sl > -1 ; sl--){
		if(document.getElementById('property0'+sl).value!=''){
			arrayNo = arrayNo + 1;
			reportSection[arrayNo] = document.getElementById('property0'+sl).value+'_Footer'
		}
	}

	arrayNo = arrayNo + 1;
	reportSection[arrayNo]             = 'Page_Footer';
	arrayNo = arrayNo + 1;
	reportSection[arrayNo]             = 'Report_Footer';
	
}





function nodrag(){
	mouseIsDown            = false;
}


function alterSections(pThis){
	if(pThis.value  == pThis.tag){return;}
	if(pThis.value  == ''){
		document.getElementById('deleteheaderID').value = pThis.tag + '_Header';
		if(!deleteSection()){
			pThis.value = pThis.tag;
		}
		return;
	}
	
	if(pThis.tag  == ''){ //--new section
		addSection(pThis);
		buildSectionArray()
		return;
	}
	if(isDiv(pThis.tag + '_Header')){//-- a current section id
		if(!isDiv(pThis.value + '_Header')){//-- not a current section id


			oTextNode = document.createTextNode(pThis.value+ '_Header');
			document.getElementById('text_'+pThis.tag+ '_Header').childNodes(0).replaceNode(oTextNode);
			oTextNode = document.createTextNode(pThis.value+ '_Footer');
			document.getElementById('text_'+pThis.tag+ '_Footer').childNodes(0).replaceNode(oTextNode);

			theh = document.getElementById(pThis.tag+ '_Header')
			thef = document.getElementById(pThis.tag+ '_Footer')
			ttheh = document.getElementById('text_'+pThis.tag+ '_Header')
			tthef = document.getElementById('text_'+pThis.tag+ '_Footer')

			theh.id = pThis.value+ '_Header'
			thef.id = pThis.value+ '_Footer'
			ttheh.id = 'text_'+pThis.value+ '_Header'
			tthef.id = 'text_'+pThis.value+ '_Footer'
		}
		reportSectionRequery();
		
	}else{
		if(isDiv(pThis.value + '_Header')){//-- not a current section id
			alert('There is currently already a sort for : '+pThis.value);
		}else{
		}
	}
}

function deleteSort(pThis){
	theSec = pThis.tag;
	moveSectionToBottom(theSec);
	//theSec = document.getElementById(pThis.parentNode.firstChild.id).value;

	if(confirm("Delete '"+theSec+ "_Header' and '"+theSec+ "_Footer' sections?")){
		hd = document.getElementById(theSec+ '_Header').parentNode;
		hd.removeChild(document.getElementById(theSec+ '_Header'));
		hd.removeChild(document.getElementById(theSec+ '_Footer'));
	}
}

function moveSectionToBottom(tagName){
	
}

function shiftKey(pEvent){
	if(e.keyCode == 16){
		shiftIsDown = pEvent == 1;
		//shiftIsDown = false;
	}
}


function hideGroupBox(pThis){
	pThis.parentNode.style.visibility = 'hidden';
}


function cloneObject(){

   clonedID         = document.getElementById('currentID').value;
   if(clonedID==''){return;}
   clonedObj        = document.getElementById(clonedID);
   newID            = newObjectID();
   newObj           = document.createElement("input");
   newObj.setAttribute('id', newID);
   newObj.setAttribute('tag', clonedObj.tag);
   newObj.setAttribute('className', clonedObj.className);
   newObj.setAttribute('value', clonedObj.value);
   newObj.onkeydown = oKeyDown;
   newObj.onkeyup   = oKeyUp;
   newObj.onmousedown = function(event){mouseDown(event,this)}; //nick
   newObj.onmouseup = function(event){mouseUp(event,this)}; //nick
   newObj.onclick = function(){loadObjectProperties(this)}; //nick
   //eval("function MD_"+newID+"(){mouseDown(document.getElementById('"+newID+"'));}");
   //eval("newObj.onmousedown = MD_"+newID);

   //eval("function MU_"+newID+"(){mouseUp(document.getElementById('"+newID+"'));}");
   //eval("newObj.onmouseup = MU_"+newID);

   //eval("function OC_"+newID+"(){loadObjectProperties(document.getElementById('"+newID+"'));}");
   eval("function OCL_"+newID+"(){objValueChange('"+newID+"');loadObjectProperties(document.getElementById('"+newID+"'));}");
   //eval("newObj.onclick = OC_"+newID);
   eval("newObj.onchange = OCL_"+newID);

   document.getElementById(clonedObj.parentNode.id).appendChild(newObj);
   setStyle(newID, 'top', parseInt(getStyle(clonedID, 'top'))+4)
   setStyle(newID, 'left', parseInt(getStyle(clonedID, 'left'))+4)
   setStyle(newID, 'width', getStyle(clonedID, 'width'))
   setStyle(newID, 'height', getStyle(clonedID, 'height'))
   setStyle(newID, 'position', getStyle(clonedID, 'position'))
   setStyle(newID, 'borderStyle', getStyle(clonedID, 'borderStyle'))
   setStyle(newID, 'borderWidth', getStyle(clonedID, 'widthwas'))
   setStyle(newID, 'borderColor', getStyle(clonedID, 'colorwas'))
   setStyle(newID, 'widthwas', getStyle(clonedID, 'widthwas'))
   setStyle(newID, 'colorwas', getStyle(clonedID, 'colorwas'))
   setStyle(newID, 'name', newID)
   setStyle(newID, 'type', getStyle(clonedID, 'type'))
   setStyle(newID, 'value', getStyle(clonedID, 'value'))
   setStyle(newID, 'tag', getStyle(clonedID, 'tag'))
   setStyle(newID, 'color', getStyle(clonedID, 'color'))
   setStyle(newID, 'fontSize', getStyle(clonedID, 'fontSize'))
   setStyle(newID, 'fontWeight', getStyle(clonedID, 'fontWeight'))
   setStyle(newID, 'fontFamily', getStyle(clonedID, 'fontFamily'))
   setStyle(newID, 'backgroundColor', getStyle(clonedID, 'backgroundColor'))
   setStyle(newID, 'graph', getStyle(clonedID, 'graph'))
   setStyle(newID, 'cangrow', getStyle(clonedID, 'cangrow'))
   setStyle(newID, 'textAlign', getStyle(clonedID, 'textAlign'))
   setStyle(newID, 'format', getStyle(clonedID, 'format'))
   loadObjectProperties(newObj)
	
}

function newObject(pID){

   newID            = newObjectID();
   newObj           = document.createElement("input");
   newObj.setAttribute('id', newID);
   newObj.setAttribute('tag', 'Field');
   newObj.setAttribute('className', 'theObject');
   newObj.setAttribute('value', '');
   newObj.onkeydown = oKeyDown;
   newObj.onkeyup   = oKeyUp;
   newObj.onmousedown = function(event){mouseDown(event,this)}; //nick
   newObj.onmouseup = function(event){mouseUp(event,this)}; //nick
   newObj.onclick = function(){loadObjectProperties(this)}; //nick
   //eval("function MD_"+newID+"(event){mouseDown(event,document.getElementById('"+newID+"'));}");
   //eval("newObj.onmousedown = MD_"+newID+"(event)");

   //eval("function MU_"+newID+"(event){mouseUp(event,document.getElementById('"+newID+"'));}");
   //eval("newObj.onmouseup = MU_"+newID+"(event)");

   //eval("function OC_"+newID+"(){loadObjectProperties(document.getElementById('"+newID+"'));}");
   eval("function OCL_"+newID+"(){objValueChange('"+newID+"');loadObjectProperties(document.getElementById('"+newID+"'));}");
   //eval("newObj.onclick = OC_"+newID);
   eval("newObj.onchange = OCL_"+newID);


   document.getElementById('Detail').appendChild(newObj);
   setStyle(newID, 'top', '0')
   setStyle(newID, 'left', '0')
   setStyle(newID, 'width', '150')
   setStyle(newID, 'height', '20')
   setStyle(newID, 'position', 'absolute')
   setStyle(newID, 'borderStyle', 'solid')
   setStyle(newID, 'borderWidth', '1')
   setStyle(newID, 'borderColor', 'black')
   setStyle(newID, 'widthwas', '1')
   setStyle(newID, 'colorwas', 'black')
   setStyle(newID, 'name', newID)
   setStyle(newID, 'type', 'Field')
   setStyle(newID, 'value', '')
   setStyle(newID, 'tag', pID)
   setStyle(newID, 'color', 'black')
   setStyle(newID, 'fontSize', '10')
   setStyle(newID, 'fontFamily', 'Arial')
   setStyle(newID, 'backgroundColor', 'white')
   setStyle(newID, 'borderWidth', '1')
   setStyle(newID, 'borderColor', 'black')
   setStyle(newID, 'graph', '')
   setStyle(newID, 'cangrow', 'false')
   setStyle(newID, 'textAlign', 'left')
   setStyle(newID, 'format', '')

}

function deleteObject(){
	if(document.getElementById('currentID').value==''){return;}
	if(confirm("Delete this object?")){
		theObjectID = document.getElementById('currentID').value;
		thePar = document.getElementById(theObjectID).parentNode;
		thePar.removeChild(document.getElementById(theObjectID));
	}
	document.getElementById('currentID').value='';
}


function deleteSection(){
	
	hID = document.getElementById('deleteheaderID').value;
	if(hID==''){return;}
	if(hID=='Detail'){return;}
	if(hID=='Report_Header'){return;}
	if(hID=='Page_Header'){return;}
	if(confirm("Delete this section group?")){
		thePrefix = hID.substr(0,hID.length-7);
		moveDown  = false;
		for(i = 1 ; i < 8 ; i++){
			if(document.getElementById('property0'+(i-1)).value==thePrefix){
				moveDown = true;
			}
			if(moveDown){
				document.getElementById('property0'+(i-1)).value = document.getElementById('property0'+i).value;
			}
		}
		document.getElementById('property07').value = '';
		buildSectionArray();
		return true;
	}
	return false;
}



function oKeyDown(){
	shiftIsDown=true;
}

function oKeyUp(){
	shiftIsDown=false;
}

function showDialogBox(pBox){
	document.getElementById('objectProperties').style.visibility = 'hidden';
	document.getElementById('sectionProperties').style.visibility = 'hidden';
	document.getElementById('groupProperties').style.visibility = 'hidden';
	document.getElementById('reportProperties').style.visibility = 'hidden';
	document.getElementById('sortorderProperties').style.visibility = 'hidden';
	document.getElementById('classProperties').style.visibility = 'hidden';
	document.getElementById('listObjects').style.visibility = 'hidden';
	if(pBox==1){document.getElementById('objectProperties').style.visibility = 'visible';}
	if(pBox==2){document.getElementById('sectionProperties').style.visibility = 'visible';}
	if(pBox==3){document.getElementById('reportProperties').style.visibility = 'visible';}
	if(pBox==4){document.getElementById('sortorderProperties').style.visibility = 'visible';}
	if(pBox==5){document.getElementById('classProperties').style.visibility = 'visible';}
	if(pBox==6){document.getElementById('groupProperties').style.visibility = 'visible';}
	if(pBox==7){document.getElementById('listObjects').style.visibility = 'visible';}
}

//added by nick 14-04-09
function mouseDownId(e,idText){
	mouseDown(e,document.getElementById(idText));
}

function mouseDown(e,pThis){
   //make up for IE's event incosistencies
   if(!e){
		e=window.event;
   }
   mouseIsDown             = true;
   shiftIsDown             = false;
   var cObject             = document.getElementById(pThis.id);
   var oParent             = cObject.parentNode;
   idName                  = pThis.id;
   if(pThis.id == ''){return;}
   //debugCon("|down: " + idName + " |");//write the event to the bottom of the page
   //debugCon("|par: " + pThis.parentNode.id + " |");
   //debugCon("|left: " + pThis.style.left + " top: " + pThis.style.top + " position: " + pThis.style.position + "|");
   //debugCon("|par left: " + pThis.parentNode.style.left + " par top: " + pThis.parentNode.style.top + " position: " + pThis.parentNode.style.position + "|");
   if(!cObject.style.left){cObject.style.left = '0';}
   if(!cObject.style.top) {cObject.style.top  = '0';}

   offsetX                 = e.clientX - parseInt(cObject.style.left);
   offsetY                 = e.clientY - parseInt(cObject.style.top);

   sizeX                   = e.clientX - parseInt(cObject.style.width)  - parseInt(cObject.style.left);
   sizeY                   = e.clientY - parseInt(cObject.style.height) - parseInt(cObject.style.top);

   if(shiftIsDown){//--sizeX >-11 && sizeY > -11){
      theaction = 'size';
   }else{
      theaction = 'drag';
   }
}

function emptyID(){
   idName = '';
}

function changeSection(pSection, pObject){
   document.getElementById(pSection).appendChild(document.getElementById(pObject));
}

function mouseMove(e){
   //make up for IE's event incosistencies
   if(!e){
		e=window.event;
   }
   if(idName == ''){return;}
   if(!mouseIsDown){return;}
   pThis = document.getElementById(idName);

   if(parseInt(pThis.style.left) < 0){pThis.style.left = 0;return;}
   if(parseInt(pThis.style.top)  < 0){pThis.style.top  = 0;return;}


   if(shiftIsDown){
      //pThis.style.width   = e.clientX - parseInt(pThis.style.left) - sizeX;
      //pThis.style.height  = e.clientY - parseInt(pThis.style.top)  - sizeY;
   }else{
      pThis.style.left    = e.clientX - offsetX;
      pThis.style.top     = e.clientY - offsetY;
   }
   
}

//added by nick 14-04-09
function mouseUpId(e,idText){
	mouseUp(e,document.getElementById(idText));
}

function mouseUp(e,pThis){
   //make up for IE's event incosistencies
   if(!e){
		e=window.event;
   }
   //debugCon("|up: " + idName + " |"); //write the event to the bottom of the page
   mouseIsDown              = false;
   idName                   = '';
   if(parseInt(pThis.style.left) < 0){pThis.style.left = 0;}
   if(parseInt(pThis.style.top)  < 0){pThis.style.top  = 0;}
}

//added by nick, onload of each object this is called, with all non html standardised properties being sent here and added to a hashmap
function setupCustomStyle(pID,name,type,graph,tag,value,cangrow,format){
	setStyle(pID,'name',name);
	setStyle(pID,'type',type);
	setStyle(pID,'graph',graph);
	setStyle(pID,'tag',tag);
	setStyle(pID,'value',value);
	setStyle(pID,'cangrow',cangrow);
	setStyle(pID,'format',format);
	setStyle(pID,'colorwas','#000000');
}

function getStyle(pID, pStyle){
	if(pStyle == 'name'){
		return customStyleType[pID + "_name"];
	}
	if(pStyle == 'type'){
		return customStyleType[pID + "_type"];
	}
	if(pStyle == 'graph'){
		return customStyleType[pID + "_graph"];
	}
	if(pStyle == 'tag'){
		return customStyleType[pID + "_tag"];
	}
	if(pStyle == 'value'){
		return customStyleType[pID + "_value"];
	}
	if(pStyle == 'cangrow'){
		return customStyleType[pID + "_cangrow"];
	}
	if(pStyle == 'format'){
		return customStyleType[pID + "_format"];
	}
	if(pStyle == 'colorwas'){
		return customStyleType[pID + "_colorwas"];
	}
	debugCon("|getstyle: "+ pID + " " + pStyle +"|");
	return document.getElementById(pID).style[pStyle];
}

function setStyle(pID, pStyle, pValue){
	if(pStyle == 'name'){
		customStyleType[pID + "_name"] = pValue;
	}
	if(pStyle == 'type'){
		customStyleType[pID + "_type"] = pValue;
	}
	if(pStyle == 'graph'){
		customStyleType[pID + "_graph"] = pValue;
	}
	if(pStyle == 'tag'){
		customStyleType[pID + "_tag"] = pValue;
	}
	if(pStyle == 'value'){
		customStyleType[pID + "_value"] = pValue;
	}
	if(pStyle == 'cangrow'){
		customStyleType[pID + "_cangrow"] = pValue;
	}
	if(pStyle == 'format'){
		customStyleType[pID + "_format"] = pValue;
	}
	if(pStyle == 'colorwas'){
		customStyleType[pID + "_colorwas"] = pValue;
	}
	debugCon("|setstyle: "+ pID + " " + pStyle + " " + pValue + "|");
	document.getElementById(pID).style[pStyle] = pValue;
	
	if(pStyle!='height'){return;}
	if(parseInt(document.getElementById(pID).style.height) == 0){
		document.getElementById(pID).style.borderColor = 'lightgrey';
	}else{
		document.getElementById(pID).style.borderColor = 'black';
	}
	
}

function setBGStyle(pID, pValue){
	
	if(pValue=='ffffff' || pValue=='#ffffff' || pValue=='white'){
		document.getElementById(pID).style['backgroundColor'] = '#ebebeb';
	}else{
		document.getElementById(pID).style['backgroundColor'] = pValue;
	}
	if(pStyle!='height'){return;}
	if(parseInt(document.getElementById(pID).style.height) == 0){
		document.getElementById(pID).style.borderColor = 'lightgrey';
	}else{
		document.getElementById(pID).style.borderColor = 'black';
	}
	
}

function setValue(pID, pValue){
	setStyle(pID, 'value', pValue);
	document.getElementById(pID).value = pValue;
}

function setTag(pID, pTag){
	
	setStyle(pID, 'tag', pTag);
	itWas = document.getElementById(pID).tag;
	document.getElementById(pID).tag = pTag;
	setStyle(pID, 'type', pTag);
	if(pTag == 'PageBreak'){
		setStyle(pID, 'borderColor', 'black');
		setStyle(pID, 'borderWidth', '4');
		setStyle(pID, 'height', '4');
		setStyle(pID, 'width', '44');
		setStyle(pID, 'left', '0');
	}
	if(pTag == 'Graph'){
		setStyle(pID, 'backgroundColor', 'black');
		setStyle(pID, 'color', 'white');
		setStyle(pID, 'fontSize', '12');
	}
	if(itWas == 'PageBreak'){
		setStyle(pID, 'borderColor', 'black');
		setStyle(pID, 'borderWidth', '1');
		setStyle(pID, 'height', '20');
		setStyle(pID, 'width', '44');
	}
	loadObjectProperties(document.getElementById(pID));
}

function objValueChange(pID){
	document.getElementById('objectProperty02').value = document.getElementById(pID).value;
	setStyle(pID, 'value', document.getElementById(pID).value);
}


function loadReportProperties(){
	document.getElementById('reportProperties').style.visibility = 'visible'
}

function isElement(pID){
	var el = document.getElementsByTagName('*');
	for(i = 0 ; i < el.length ; i++){
		if(el(i).id == pID){
			return true;
		}
	}
	return false;
}

function usedID(pID){

	var inp = document.getElementsByTagName('input');

	for(i = 0 ; i < inp.length ; i++){

		if(inp[i].id == pID){
			return true;
		}
	}
	return false;
}

function newObjectID(){

	for(idNumber=0 ; idNumber < 1000 ; idNumber++){
		theID = String(idNumber);
		if(theID.length==1){suffix = '00' + theID;}
		if(theID.length==2){suffix = '0'  + theID;}
		if(theID.length==3){suffix =        theID;}
		if(!usedID('object'+suffix)){return 'object'+suffix;}
	}
}




function loadObjectProperties(pThis){
	debugCon("|load obj properties|");
	pID   = pThis.id;
	if(document.getElementById('currentID').value!=''){
		restoreBorder(document.getElementById('currentID').value);
	}
	document.getElementById('currentID').value = pID;
	setStyle(pID, 'widthwas', getStyle(pID, 'borderLeftWidth'))
	setStyle(pID, 'colorwas', getStyle(pID, 'borderLeftColor'))
	setStyle(pID, 'borderWidth', '2');
	setStyle(pID, 'borderColor', 'red');
	setStyle(pID, 'borderStyle', 'dotted double');
	replacePropertyDescription('object','00', 'Control Type');
	replacePropertyDescription('object','01', 'Section');
	replacePropertyDescription('object','02', 'Value')
	replacePropertyDescription('object','03', 'Top');
	replacePropertyDescription('object','04', 'Left');
	replacePropertyDescription('object','05', 'Width');
	replacePropertyDescription('object','06', 'Height');
	replacePropertyDescription('object','07', 'Border Width');
	replacePropertyDescription('object','08', 'Border Color');
	if(pThis.tag == 'Graph'){
		replacePropertyDescription('object','02', 'Parameters')
		replacePropertyDescription('object','09', 'Graphic');
		replacePropertyDescription('object','10', '');
		replacePropertyDescription('object','11', '');
		replacePropertyDescription('object','12', '');
		replacePropertyDescription('object','13', '');
		replacePropertyDescription('object','14', '');
		replacePropertyDescription('object','15', '');
		replacePropertyDescription('object','16', '');
	}else{
		replacePropertyDescription('object','09', 'Color');
		replacePropertyDescription('object','10', 'Font Size');
		replacePropertyDescription('object','11', 'Font Weight');
		replacePropertyDescription('object','12', 'Font Family');
		replacePropertyDescription('object','13', 'Background Color');
		replacePropertyDescription('object','14', 'Text Align');
		if(pThis.tag == 'Field'){replacePropertyDescription('object','15', 'Can Grow')};
		if(pThis.tag == 'Field'){replacePropertyDescription('object','16', 'Format')};
	}

	replacePropertyValueSelect('object', '00', pThis.tag,                    true, theControl,    'setTag("'+pID+'", this.value)');
	replacePropertyValueSelect('object', '01', pThis.parentNode.id,          true, reportSection, 'moveToSection("'+pID+'", this.value)');
	replacePropertyValueInput('object', '02',  pThis.value,                  true,                'setValue("'+pID+'", this.value)')
	replacePropertyValueInput('object', '03',  getStyle(pID, 'top'),         true,                'setStyle("'+pID+'", "top", this.value)');
	replacePropertyValueInput('object', '04',  getStyle(pID, 'left'),        true,                'setStyle("'+pID+'", "left", this.value)');
	replacePropertyValueInput('object', '05',  getStyle(pID, 'width'),       true,                'setStyle("'+pID+'", "width", this.value)');
	replacePropertyValueInput('object', '06',  getStyle(pID, 'height'),      true,                'setStyle("'+pID+'", "height", this.value)');
	replacePropertyValueInput('object', '07',  getStyle(pID, 'widthwas'),    true,                'setStyle("'+pID+'", "widthwas", this.value)');
	replacePropertyValueInput('object', '08',  getStyle(pID, 'colorwas'),    true,                'setStyle("'+pID+'", "colorwas", this.value)');
	if(pThis.tag == 'Graph'){
		replacePropertyValueSelect('object', '09', getStyle(pID, 'graph'), true, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '10', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '11', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '12', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '13', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '14', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '15', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '16',  getStyle(pID, 'cangrow'),    false, theAnswer,'');	
	}else{
		replacePropertyValueInput('object', '09',  getStyle(pID, 'color'),           true,            'setStyle("'+pID+'", "color", this.value)');
		replacePropertyValueInput('object', '10',  getStyle(pID, 'fontSize'),        true,            'setStyle("'+pID+'", "fontSize", this.value)');
		replacePropertyValueSelect('object', '11', getStyle(pID, 'fontWeight'),      true, theWeight, 'setStyle("'+pID+'", "fontWeight", this.value)');
		replacePropertyValueSelect('object', '12', getStyle(pID, 'fontFamily'),      true, theFamily, 'setStyle("'+pID+'", "fontFamily", this.value)');
		replacePropertyValueInput('object',  '13', getStyle(pID, 'backgroundColor'), true,            'setStyle("'+pID+'", "backgroundColor", this.value)');
		replacePropertyValueSelect('object', '14', getStyle(pID, 'textAlign'),       true, theAlign,  'setStyle("'+pID+'", "textAlign", this.value)');
		if(pThis.tag == 'Field'){
			replacePropertyValueSelect('object', '15',  getStyle(pID, 'cangrow'),    true, theAnswer, 'setStyle("'+pID+'", "cangrow", this.value)');	
			replacePropertyValueSelect('object', '16',  theFormat[getStyle(pID, 'format')],     true, theFormat, 'setStyle("'+pID+'", "format", formatNumber(this.value))');	
		}else{
			replacePropertyValueSelect('object', '15',  getStyle(pID, 'cangrow'),    false, theAnswer,'');	
			replacePropertyValueSelect('object', '16',  getStyle(pID, 'cangrow'),    false, theAnswer,'');	
		}
	}
}

function isDiv(pID){
	
	var divs = document.getElementsByTagName('div');
	for (var i = 0; i < divs.length; i++) {
		if(divs[i].id == pID){
			return true; 
		}
	}
	return false;

}

function getSectionByTag(pSectionNumber){
	
	var divs = document.getElementsByTagName('div');
	for (var i = 0; i < divs.length; i++) {
		if(divs[i].tag == pSectionNumber){
			return divs[i].id; 
		}
	}

}

function loadSectionProperties(pThis){

	pID                  = pThis.id;
	document.getElementById('currentSectionID').value = pID;
	thePrefix            = pID.substr(0,pID.length-7);
	if(pID == 'Detail'){
		notDetail        = false;
		headerID         = 'Detail';
		footerID         = 'Detail';
		document.getElementById('deleteheaderID').value = headerID;
		document.getElementById('deletefooterID').value = footerID;
	}else{
		notDetail        = true;
		headerID         = thePrefix+'_Header';
		footerID         = thePrefix+'_Footer';
		document.getElementById('deleteheaderID').value = headerID;
		document.getElementById('deletefooterID').value = footerID;
	}
	head                 = document.getElementById(headerID);
	foot                 = document.getElementById(footerID);
	replacePropertyDescription('section','00', 'Section Name');
	replacePropertyDescription('section','01', 'Height');
	replacePropertyDescription('section','02', 'Background Color');

	replacePropertyDescription('section','03', '');
	replacePropertyDescription('section','04', '');
	replacePropertyDescription('section','05', '');
	replacePropertyDescription('section','06', '');
	replacePropertyDescription('section','07', '');

	replacePropertyDescription('section','08', 'Section Name');
	replacePropertyDescription('section','09', 'Height');
	replacePropertyDescription('section','10', 'Background Color');
	
	pSec = head;
	pID  = head.id;
	replacePropertyValueInput('section','00', pSec.id, true, '');
	replacePropertyValueInput('section','01', getStyle(pID, 'height'), true, 'setStyle("'+pID+'", "height", this.value)');
	replacePropertyValueInput('section','02', getStyle(pID, 'backgroundColor'), true, 'setBGStyle("'+pID+'", this.value)');

	replacePropertyValueInput('section','03', '', false, '');
	replacePropertyValueInput('section','04', '', false, '');
	replacePropertyValueInput('section','05', '', false, '');
	replacePropertyValueInput('section','06', '', false, '');
	replacePropertyValueInput('section','07', '', false, '');

	pSec = foot;
	pID  = foot.id;
	replacePropertyValueInput('section','08', pSec.id, notDetail, '');
	replacePropertyValueInput('section','09', getStyle(pID, 'height'), notDetail, 'setStyle("'+pID+'", "height", this.value)');
	replacePropertyValueInput('section','10', getStyle(pID, 'backgroundColor'), notDetail, 'setStyle("'+pID+'", "backgroundColor", this.value)');

}

function replacePropertyValueInput(pBox, pRow, pValue, pVisible, pOnChange){

	var theValue  = document.getElementById(pBox+'Value'+pRow);
	if(theValue.firstChild){
		theValue.removeChild(document.getElementById(pBox+'Property'+pRow));
	}
	if(pVisible){
		newChild      = theValue.appendChild(document.createElement('input'));
		newChild.setAttribute('id',pBox+'Property'+pRow);
		newChild.setAttribute('value',pValue);
		newChild.onfocus = emptyID;
		if(pOnChange!=''){
			eval("function "+pBox+'Property'+pRow+"(){"+pOnChange+";}")
			eval("newChild.onchange = "+pBox+"Property"+pRow)
		}
	}
	
}


function replacePropertyValueSelect(pBoxType, pRow, pValue, pVisible, pArray, pOnChange){

	var theValue  = document.getElementById(pBoxType+'Value'+pRow);
	while(theValue.firstChild){
		theValue.removeChild(document.getElementById(pBoxType+'Property'+pRow));
	}
	if(pVisible){
		newChild      = theValue.appendChild(document.createElement('select'));
		newChild.setAttribute('id',pBoxType+'Property'+pRow);
		for(i=0;i<pArray.length;i++){
			newOption = newChild.appendChild(document.createElement('option'));
			newOption.setAttribute('value',pArray[i]);
			newOption.appendChild(document.createTextNode(pArray[i]));
			if(pArray[i]==pValue){
				newOption.setAttribute('selected','selected');
			}
		}
		if(pOnChange!=''){
			eval("function "+pBoxType+'Property'+pRow+"(){"+pOnChange+";}")
			eval("newChild.onchange = "+pBoxType+"Property"+pRow)
		}
	}
	
}



function replacePropertyDescription(pBox,pRow, pDescription){

	var theTitle  = document.getElementById(pBox+'Title'+pRow);
	theTitle.removeChild(document.getElementById(pBox+'TitleWords'+pRow));
	newChild    = theTitle.appendChild(document.createElement('p'));
	newChild.setAttribute('id',pBox+'TitleWords'+pRow);
	newChild.appendChild(document.createTextNode(pDescription));
	
}

function moveToSection(pID, pSection){
	document.getElementById(pSection).appendChild(document.getElementById(pID))
}

function hideSectionBox(pThis){
	
	pThis.parentNode.style.visibility = 'hidden';
	for(i=0 ; i<12 ; i++){
		suf = i;
		if(i<10){suf='0'+i;}
		theValue  = document.getElementById('sectionValue'+suf);
		if(theValue.firstChild){
			document.getElementById('sectionProperty'+suf).style.visibility = 'hidden';
		}
	}
}

function isLastEmpty(pThis){
	
	emptyID();
	pThis.tag = pThis.value;
	if(pThis.id.substr(9)==0){return true;}
	if(document.getElementById('property0'+(Number(pThis.id.substr(9))-1)).value==''){
		document.forms[0]['moveup'+pThis.id.substr(9)].focus();
	}
	return true;
}

function moveSectionUp(pThis){
	
	thisRow    = 'property0'+pThis.name.substr(6);
	aboveRow   = 'property0'+(Number(pThis.name.substr(6)) - 1);
	if(document.getElementById(thisRow).value==''){return;}
	goUp       = document.getElementById(thisRow).value;	
	goDown     = document.getElementById(aboveRow).value;	

	document.getElementById(thisRow).value  = goDown;	
	document.getElementById(aboveRow).value = goUp;	
	buildSectionArray();
	
}


function addSection(pThis){
	
	for(u = 0 ; u < Number(pThis.id.substr(8)) ; u++){
		if(document.getElementById('property0'+u).value.toUpperCase()==pThis.value.toUpperCase()){
			alert('cannot have same Sort Field as '+Number(u+1));
			pThis.tag = '';
			pThis.value = '';
			return false;
		}
	}

	headerWas  = hiddenSection[0];
	footerWas  = hiddenSection[1];
	hiddenSection.splice(0,2);


	oTextNode = document.createTextNode(pThis.value+ '_Header');
	//document.getElementById('text_'+headerWas).childNodes[0].replaceNode(oTextNode);
	document.getElementById('text_'+headerWas).replaceChild(oTextNode, document.getElementById('text_'+headerWas).childNodes[0]);
	
	oTextNode = document.createTextNode(pThis.value+ '_Footer');
	//document.getElementById('text_'+footerWas).childNodes[0].replaceNode(oTextNode);
	document.getElementById('text_'+footerWas).replaceChild(oTextNode, document.getElementById('text_'+footerWas).childNodes[0]);
	
	theh = document.getElementById(headerWas)
	thef = document.getElementById(footerWas)
	ttheh = document.getElementById('text_'+headerWas)
	tthef = document.getElementById('text_'+footerWas)

	theh.id = pThis.value+ '_Header';
	thef.id = pThis.value+ '_Footer';
	ttheh.id = 'text_'+pThis.value+ '_Header'
	tthef.id = 'text_'+pThis.value+ '_Footer'
	setStyle(pThis.value+ '_Header', 'visibility', 'visible');
	setStyle(pThis.value+ '_Footer', 'visibility', 'visible');
	reportSectionRequery();

}

function makeReportSection(pID){
   newID            = pID;
   newObj           = document.createElement("div");
   newObj.setAttribute('id', newID);
   newObj.setAttribute('tag', 'new');
   newObj.setAttribute('className', 'theObject');
   newObj.setAttribute('value', '');
   newObj.onkeydown = oKeyDown;
   newObj.onkeyup   = oKeyUp;
   eval("function lpbi"+newID+"(){loadSectionPropertiesById('"+newID+"');}");
   eval("newObj.onclick = lpbi"+newID);
   eval("newObj.onchange = lpbi"+newID);

   document.getElementById('Detail').parentNode.insertBefore(newObj,document.getElementById('Report_Header'));

   setStyle(newID, 'position', 'relative');
   setStyle(newID, 'overflow', 'hidden');
   setStyle(newID, 'fontSize', '14');
   setStyle(newID, 'borderWidth', '1');
   setStyle(newID, 'borderStyle', 'solid');
   setStyle(newID, 'borderColor', 'lightgrey');
   setStyle(newID, 'borderLeftWidth', '15');
   setStyle(newID, 'backgroundColor', '#ffffff');
   setStyle(newID, 'height', '0');
   setStyle(newID, 'width', document.getElementById('rproperty00').value);


   tObj           = document.createElement("div");
   tObj.setAttribute('id', 'text_'+newID);

   parnt = document.getElementById('Detail').parentNode;
   parnt.appendChild(newObj);
   
   oTextNode = document.createTextNode(newID);
   tObj.appendChild(oTextNode);
   
}


function copyClass(){
	window.opener.document.getElementById('sat_report_display_code').value=document.getElementById('classcode').value;
	window.opener.document.getElementById('beenedited').value = '1';
	window.opener.focus();
	alert('Copied Successfully to Activity Table');
	self.close();
}


function resize(){
	return 0.0679;
}

function restoreBorder(pID){

	setStyle(pID, 'borderStyle', 'solid')
	setStyle(pID, 'borderWidth', getStyle(pID, 'widthwas'))
	setStyle(pID, 'borderColor', getStyle(pID, 'colorwas'))
}


function pageWidth(pThis){
	for(i=0 ; i < reportSection.length ; i++){
		document.getElementById(reportSection[i]).style.width = pThis.value;
	}
}

function formatNumber(pFormat){
	for(f = 0 ; f < theFormat.length ; f++){
		if(pFormat == theFormat[f]){
			return f;
		}
	}
	return '';
}

//added by nick
function toGroupEditor(){
	document.getElementById('groupProperties').style.visibility = 'hidden';
	document.getElementById('groupProperties').style.visibility = 'visible';
	document.getElementById('listObjects').style.visibility = 'hidden';
	oList    = document.getElementById('list_ids');
	for(ele = 0 ; ele < oList.length ; ele++){
		if(oList.options[ele].selected){
			theObj = oList.options[ele].value;
			addToList2(theObj);
		}
	}
	sortListAlphabetically('object_ids');
}

//added by nick
function notInList(thisID){
	curList    = document.getElementById('object_ids');
	for(i = 0 ; i < curList.length ; i++){
		curObj = curList.options[i].value;
		if(thisID === curObj){
			return false;
		}
	}
	return true;
}

function addToList(){
	o                   = document.getElementById('object_ids');
	c                   = document.getElementById('currentID');
	if(notInList(c.value)){
		objDescription      = c.value + ' - (' + document.getElementById(c.value).value+')';
		o.options[o.length] = new Option(objDescription, document.getElementById('currentID').value);
	}
}

//added by nick
function addToList2(objID){
	if(notInList(objID)){
		o                   = document.getElementById('object_ids');
		objDescription      = objID + ' - (' + document.getElementById(objID).value+')';
		o.options[o.length] = new Option(objDescription, objID);
	}
}

//added by nick
function addToObjectList(theID){
	//debugCon("|addtoobjlist: " + theID + "|");
   o                   = document.getElementById('list_ids');
   objDescription      = theID + ' - ' + document.getElementById(theID).value +' - '+ document.getElementById(theID).type ;
   o.options[o.length] = new Option(objDescription, theID);
}

function removeFromList(){

   o                          = document.getElementById('object_ids');
   o.options[o.selectedIndex] = null;

}
function selectAllInSectionSelect(){
	var listOfObjects = document.getElementById('list_ids');
	for(i = 0; i < listOfObjects.length; i++){
		listOfObjects.options[i].selected = true;
	}
}

function deselectSelectedInSectionSelect(){
	var listOfObjects = document.getElementById('list_ids');
	for(i = 0; i < listOfObjects.length; i++){
		listOfObjects.options[i].selected = false;
	}
}

function deleteSelected(){
	if(confirm("Delete these objects?")){
		var listOfObjects = document.getElementById('list_ids');
		for(i = listOfObjects.length-1; i >= 0; i--){
			if(listOfObjects.options[i].selected){
				theObjectID = listOfObjects.options[i].value;
				listOfObjects.options[i] = null;
				thePar = document.getElementById(theObjectID).parentNode;
				thePar.removeChild(document.getElementById(theObjectID));
			}
		}
	}
	document.getElementById('currentID').value='';
}

function clearList(){
	o                         = document.getElementById('object_ids');
	o.options.length = 0;
}

//added by nick
function removeFromObjectList(){
   o                          = document.getElementById('list_ids');
   o.options[o.selectedIndex] = null;
}

//added by nick
function clearObjectList(){
	o                         = document.getElementById('list_ids');
//	for(i = 0; i < o.options.length; i++){
//		restoreBorder(o.options[i].value);
//	}
	o.options.length = 0;
}

function replaceGroupValue(pArray){
	//alert("something happening here!");
	var theValue       = document.getElementById('groupValue03');
	if(theValue.firstChild){
		theValue.removeChild(document.getElementById('object_value'));
	}
	if(arguments.length == 0){//--no array to use
    newChild         = theValue.appendChild(document.createElement('input'));
	  newChild.setAttribute('id','object_value');
    newChild.setAttribute('value','');
    newChild.onfocus = emptyID;
  }else{
  	newChild         = theValue.appendChild(document.createElement('select'));
	  newChild.setAttribute('id','object_value');
		for(i=0;i<pArray.length;i++){
			newOption      = newChild.appendChild(document.createElement('option'));
			newOption.setAttribute('value',pArray[i]);
			newOption.appendChild(document.createTextNode(pArray[i]));
		}
	}
	setStyle('object_value', 'width', '200')
  eval("newChild.onchange = update_group");

	
}


function replace_object_value(pValue){

	replaceGroupValue();
	if(pValue == 0){replaceGroupValue(reportSection);}
	if(pValue == 10){replaceGroupValue(theFamily);}
	if(pValue == 12){replaceGroupValue(theAlign);}
	if(pValue == 13){replaceGroupValue(theAnswer);}
	if(pValue == 14){replaceGroupValue(theFormat);}

}


function update_group(){
   //alert("something else happening here!");
   oList    = document.getElementById('object_ids');
   newValue = document.getElementById('object_value').value;
   pValue   = document.getElementById('object_property').value;
   for(i = 0 ; i < oList.length ; i++){

      theID = oList.options[i].value;
	  if(oList.options[i].selected){
			if(pValue=='0'){  moveToSection(theID, newValue);}                    //--Section
			if(pValue=='1'){  setStyle(theID, "top", newValue);}                  //--Top                   
			if(pValue=='2'){  setStyle(theID, "left", newValue);}                 //--Left                   
			if(pValue=='3'){  setStyle(theID, "width", newValue);}                //--Width                   
			if(pValue=='4'){  setStyle(theID, "height", newValue);}               //--Height                   
			if(pValue=='5'){  setStyle(theID, "borderWidth", newValue);}          //--Border Width                   
			if(pValue=='6'){  setStyle(theID, "borderColor", newValue);}          //--Border Color                   
			if(pValue=='7'){  setStyle(theID, "color", newValue);}                //--Color                   
			if(pValue=='8'){  setStyle(theID, "fontSize", newValue);}             //--Font Size                   
			if(pValue=='9'){  setStyle(theID, "fontWeight", newValue);}           //--Font Weight                   
			if(pValue=='10'){ setStyle(theID, "fontFamily", newValue);}           //--Font Family
			if(pValue=='11'){ setStyle(theID, "backgroundColor", newValue);}      //--Background Color                   
			if(pValue=='12'){ setStyle(theID, "textAlign", newValue);}            //--Text Align
			if(pValue=='13'){ setStyle(theID, "cangrow", newValue);}              //--Can Grow
			if(pValue=='14'){ setStyle(theID, "format", formatNumber(newValue));} //--Format
	  }
   }
	if((pValue!=0)&&(pValue!=12)&&(pValue!=13)&&(pValue!=14)){
		replace_object_value(pValue); //clear the text input box so that the next input will work (eg: doing "blue" then "blue" will work)
	}
}

function group_left(pTimes){

   oList    = document.getElementById('object_ids');
   vBy = document.getElementById('pixel_move').value;

   for(i = 0 ; i < oList.length ; i++){

      theobj = document.getElementById(oList.options[i].value);
// BEGIN -- 2009.01.23 -- Michael

// REQUESTED BY -- Simon
// The following code meant you could only move the group if you selected them in the dialog box.
// Now they only need to be present in the list to be affected.
		theobj.style.left = parseInt(theobj.style.left) + (pTimes * vBy);
/*
		if(oList.options[i].selected){
			theobj.style.left = parseInt(theobj.style.left) + (pTimes * vBy);
		}
*/
// END -- 2009.01.23 -- Michael
   }

}

function group_top(pTimes){

   oList    = document.getElementById('object_ids');
   vBy = document.getElementById('pixel_move').value;

   for(i = 0 ; i < oList.length ; i++){

      theobj = document.getElementById(oList.options[i].value);
// BEGIN -- 2009.01.23 -- Michael

// REQUESTED BY -- Simon
// The following code meant you could only move the group if you selected them in the dialog box.
// Now they only need to be present in the list to be affected.
		theobj.style.top = parseInt(theobj.style.top) + (pTimes * vBy);
/*
		if(oList.options[i].selected){
			theobj.style.top = parseInt(theobj.style.top) + (pTimes * vBy);
		}
*/
// END -- 2009.01.23 -- Michael
   }

}

//added by nick
function strcmp(str1, str2){
    return ( (str1 == str2) ? 0 : ((str1>str2)?1:-1) );
}

//added by nick
function poscmp(x1,y1,x2,y2){
	if(y1 > y2){
		return 1;
	}else if(y1 < y2){
		return -1;
	}else{
		if(x1 > x2){
			return 1;
		}else if(x1 < x2){
			return -1;
		}else{
			return 0;
		}
	}
}

//added by nick
function sortListAlphabetically(selectID){
	//this sorting algorithm is slow on large data sets ( O(n^2) ),
	//someone please optimise in the future. Perhaps replace with an implementation of quicksort or mergesort.
	sList = document.getElementById(selectID);
	//alert(sList.options.length);
	var firstop = 0;
	var secondop = 0;
	for (firstop=0; firstop < sList.options.length-1; firstop++) {
		for (secondop=0; secondop < sList.options.length-1-firstop; secondop++){
			if (strcmp(sList.options[secondop].value, sList.options[secondop+1].value) > 0) {  /* compare the two neighbors */
				swapOptions(secondop,secondop+1,sList);
			}
		}
	}
}

//added by nick
function sortListByPosition(selectID){
	//alert("compucon is tired and cannot categorise objects by their positions. come back when compucon is not tired. KTHX");
	sList = document.getElementById(selectID);
	var firstop = 0;
	var secondop = 0;
	for (firstop=0; firstop < sList.options.length-1; firstop++) {
		for (secondop=0; secondop < sList.options.length-1-firstop; secondop++){
			var opone = document.getElementById(sList.options[secondop].value).parentNode.offsetTop;
			var optwo = document.getElementById(sList.options[secondop+1].value).parentNode.offsetTop;
			var ax = parseInt(getStyle(sList.options[secondop].value, 'left'));
			var ay = parseInt(getStyle(sList.options[secondop].value, 'top'));
			var bx = parseInt(getStyle(sList.options[secondop+1].value, 'left'));
			var by = parseInt(getStyle(sList.options[secondop+1].value, 'top'));
			if (poscmp(ax, ay + opone, bx, by + optwo) > 0) {  /* compare the two neighbors */
				swapOptions(secondop,secondop+1,sList);
			}
		}
	}
}

//added by nick
function swapOptions(a,b,list){
	var tempvalue = list.options[a].value;
	var temptext = list.options[a].text;
	var tempselected = list.options[a].selected;
	//alert(a + " " + b + " " + tempvalue + " " + temptext + " " + tempselected);
	list.options[a].value = list.options[b].value;
	list.options[a].text = list.options[b].text;
	list.options[a].selected = list.options[b].selected;
	list.options[b].value = tempvalue;
	list.options[b].text = temptext;
	list.options[b].selected = tempselected;
}

//added by nick
function highlightSelected(fromListID){
	hList = document.getElementById(fromListID);
	for(i = 0; i < hList.options.length; i++){
		if(hList.options[i].selected){
			var targID = hList.options[i].value;
			loadObjectProperties2(targID);
				
			return;
		}else{
				restoreBorder(hList.options[i].value);
		}
	}
}

function autoHighlight(selectList){
	var multiple = 0;
	var targetID;
	for(i = 0; i < selectList.options.length; i++){
		if(selectList.options[i].selected){
			multiple ++;
			targetID = selectList.options[i].value;
		}
		
		if(multiple > 1){
			break;
		}
	}
	
	if(multiple == 1){
		loadObjectProperties2(targetID);
	}
}


//added by nick
function loadObjectProperties2(pThisID){

	pID   = pThisID;
	pThis = document.getElementById(pThisID);
	if(document.getElementById('currentID').value!=''){
		restoreBorder(document.getElementById('currentID').value);
	}
	document.getElementById('currentID').value = pID;
	setStyle(pID, 'widthwas', getStyle(pID, 'borderLeftWidth'))
	setStyle(pID, 'colorwas', getStyle(pID, 'borderLeftColor'))
	setStyle(pID, 'borderWidth', '2');
	setStyle(pID, 'borderColor', 'red');
	setStyle(pID, 'borderStyle', 'dotted double');
	replacePropertyDescription('object','00', 'Control Type');
	replacePropertyDescription('object','01', 'Section');
	replacePropertyDescription('object','02', 'Value')
	replacePropertyDescription('object','03', 'Top');
	replacePropertyDescription('object','04', 'Left');
	replacePropertyDescription('object','05', 'Width');
	replacePropertyDescription('object','06', 'Height');
	replacePropertyDescription('object','07', 'Border Width');
	replacePropertyDescription('object','08', 'Border Color');
	if(pThis.tag == 'Graph'){
		replacePropertyDescription('object','02', 'Parameters')
		replacePropertyDescription('object','09', 'Graphic');
		replacePropertyDescription('object','10', '');
		replacePropertyDescription('object','11', '');
		replacePropertyDescription('object','12', '');
		replacePropertyDescription('object','13', '');
		replacePropertyDescription('object','14', '');
		replacePropertyDescription('object','15', '');
		replacePropertyDescription('object','16', '');
	}else{
		replacePropertyDescription('object','09', 'Color');
		replacePropertyDescription('object','10', 'Font Size');
		replacePropertyDescription('object','11', 'Font Weight');
		replacePropertyDescription('object','12', 'Font Family');
		replacePropertyDescription('object','13', 'Background Color');
		replacePropertyDescription('object','14', 'Text Align');
		if(pThis.tag == 'Field'){replacePropertyDescription('object','15', 'Can Grow')};
		if(pThis.tag == 'Field'){replacePropertyDescription('object','16', 'Format')};
	}

	replacePropertyValueSelect('object', '00', pThis.tag,                    true, theControl,    'setTag("'+pID+'", this.value)');
	replacePropertyValueSelect('object', '01', pThis.parentNode.id,          true, reportSection, 'moveToSection("'+pID+'", this.value)');
	replacePropertyValueInput('object', '02',  pThis.value,                  true,                'setValue("'+pID+'", this.value)')
	replacePropertyValueInput('object', '03',  getStyle(pID, 'top'),         true,                'setStyle("'+pID+'", "top", this.value)');
	replacePropertyValueInput('object', '04',  getStyle(pID, 'left'),        true,                'setStyle("'+pID+'", "left", this.value)');
	replacePropertyValueInput('object', '05',  getStyle(pID, 'width'),       true,                'setStyle("'+pID+'", "width", this.value)');
	replacePropertyValueInput('object', '06',  getStyle(pID, 'height'),      true,                'setStyle("'+pID+'", "height", this.value)');
	replacePropertyValueInput('object', '07',  getStyle(pID, 'widthwas'),    true,                'setStyle("'+pID+'", "widthwas", this.value)');
	replacePropertyValueInput('object', '08',  getStyle(pID, 'colorwas'),    true,                'setStyle("'+pID+'", "colorwas", this.value)');
	if(pThis.tag == 'Graph'){
		replacePropertyValueSelect('object', '09', getStyle(pID, 'graph'), true, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '10', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '11', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '12', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '13', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '14', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '15', getStyle(pID, 'graph'), false, theGraph, 'setStyle("'+pID+'", "graph", this.value)');
		replacePropertyValueSelect('object', '16',  getStyle(pID, 'cangrow'),    false, theAnswer,'');	
	}else{
		replacePropertyValueInput('object', '09',  getStyle(pID, 'color'),           true,            'setStyle("'+pID+'", "color", this.value)');
		replacePropertyValueInput('object', '10',  getStyle(pID, 'fontSize'),        true,            'setStyle("'+pID+'", "fontSize", this.value)');
		replacePropertyValueSelect('object', '11', getStyle(pID, 'fontWeight'),      true, theWeight, 'setStyle("'+pID+'", "fontWeight", this.value)');
		replacePropertyValueSelect('object', '12', getStyle(pID, 'fontFamily'),      true, theFamily, 'setStyle("'+pID+'", "fontFamily", this.value)');
		replacePropertyValueInput('object',  '13', getStyle(pID, 'backgroundColor'), true,            'setStyle("'+pID+'", "backgroundColor", this.value)');
		replacePropertyValueSelect('object', '14', getStyle(pID, 'textAlign'),       true, theAlign,  'setStyle("'+pID+'", "textAlign", this.value)');
		if(pThis.tag == 'Field'){
//alert(getStyle(pID, 'format')+'  -   '+formatNumber(getStyle(pID, 'format')));
			replacePropertyValueSelect('object', '15',  getStyle(pID, 'cangrow'),    true, theAnswer, 'setStyle("'+pID+'", "cangrow", this.value)');	
			replacePropertyValueSelect('object', '16',  theFormat[getStyle(pID, 'format')],     true, theFormat, 'setStyle("'+pID+'", "format", formatNumber(this.value))');	
		}else{
			replacePropertyValueSelect('object', '15',  getStyle(pID, 'cangrow'),    false, theAnswer,'');	
			replacePropertyValueSelect('object', '16',  getStyle(pID, 'cangrow'),    false, theAnswer,'');	
		}
	}
}

</script>

<body id='theBody'  onmouseup='mouseIsDown=false;idName=""' onmousemove='mouseMove(event)' onload='getStyle("objectProperties", "top");replace_object_value(0)' bgcolor='lightgrey' style='font-family:arial'>
<form name='theForm'>
<input type='hidden' name='currentID' id='currentID' value=''/>
<input type='hidden' name='currentSectionID' id='currentSectionID' value=''/>
<input type='hidden' name='deleteheaderID' id='deleteheaderID' value=''/>
<input type='hidden' name='deletefooterID' id='deletefooterID' value=''/>
<?php
//-----------------------------php code-------------------------------------

	$GLOBALS['controlType'] = array('100' => 'Label', '109' => 'Field', '103' => 'Graph', '118' => 'PageBreak','Label' => 'Label', 'Field' => 'Field', 'Graph' => 'Graph', 'PageBreak' => 'PageBreak');
	print "<div id='newO' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:20;height:14;width:90;color:white;text-align:center' onclick='newObject(100)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nNew Object</div>";
	print "<div id='clone' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:120;height:14;width:90;color:white;text-align:center' onclick='cloneObject()' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nClone Object</div>";
	print "<div id='listO' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:220;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(7)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nSection Select</div>";
	print "<div id='showG' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:320;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(6)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nGroup Change</div>";
	print "<div id='newS'  style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:420;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(4)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nSort Order</div>";
	print "<div id='showO' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:520;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(1)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nObject Properties</div>";
	print "<div id='showS' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:620;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(2)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nSection Properties</div>";
	print "<div id='showR' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:720;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(3)' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nReport Properties</div>";
//	print "<div id='showC' style='position:absolute;font-size:10;background-color:gray;top:0;left:820;height:14;width:90;color:white;text-align:center' onclick='showDialogBox(5);buildClass();' onmouseover='this.style.color=\"orange\"' onmouseout='this.style.color=\"white\"' >\nReport Class</div>";
	print "<div id='showC' style='position:absolute;z-index:1;font-size:10;background-color:gray;top:0;left:820;height:14;width:90;color:white;text-align:center' onclick='buildClass();' onmouseover='this.style.color=\"orange\"' title='Copy Changes to PHP Display Code' onmouseout='this.style.color=\"white\"' >\nCopy Changes</div>";

//-----object box
	print "<div id='objectProperties' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color: #999999;top:200;left:200;height:520;width:450' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>\n";
	print "<div id='objectTitleClose' class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"white\"' onclick='this.parentNode.style.visibility = \"hidden\"'><b>X</b></div>\n";
	print "<div id='objectTitleBar'   class='theHeader'           style='top:0;left:16;width:3000;height:18'>Object Properties</div>\n";
	print "<div id='objectTitleDelete' class='theHeader'           style='color:yellow;top:0;left:328;' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"yellow\"' onclick='deleteObject()'>Delete This Object</div>\n";

	for($i = 0 ; $i < 17 ; $i++){
		$suf = substr("0$i", -2);
		$theTop = 25*(1+$i);
		print "   <div id='objectTitle$suf'    class='thePropertyLeft'     style='top:$theTop;'><p id='objectTitleWords$suf'></p></div>\n";
		print "   <div id='objectValue$suf'    class='thePropertyRight'    style='top:$theTop;'><input id='objectProperty$suf' value='' style='width:200' onchange='updateValue(this.id)'/></div>\n";




	}
	
	print "</div>\n";

//-----section box
	print "<div id='sectionProperties' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color: gray;top:200;left:200;height:350;width:450' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>\n";
	print "<div id='sectionTitleClose' class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"white\"' onclick='hideSectionBox(this)'><b>X</b></div>\n";
	print "<div id='sectionTitleBar'   class='theHeader'           style='top:0;left:16;width:3000;height:18'>Section Properties</div>\n";
	print "<div id='sectionTitleDelete' class='theHeader'          style='color:yellow;top:0;left:275;' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"yellow\"' onclick='deleteSection()'>Delete This Section Group</div>\n";

	for($i = 0 ; $i < 12 ; $i++){
		$suf = substr("0$i", -2);
		$theTop = 25*(1+$i);
		print "   <div id='sectionTitle$suf'    class='thePropertyLeft'     style='top:$theTop;'><p id='sectionTitleWords$suf'></p></div>\n";
		print "   <div id='sectionValue$suf'    class='thePropertyRight'    style='top:$theTop;'><input id='sectionProperty$suf' value='' style='visibility:hidden;width:200'/></div>\n";
	}
	
	print "</div>\n";


	$theLayout             = new Reporting();


//-----class box
	print "<div id='classProperties' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color:#666666;top:200;left:200;height:500;width:600' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>\n";
	print "<div id='classTitleClose'   class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"white\"' onclick='this.parentNode.style.visibility = \"hidden\"'><b>X</b></div>\n";
	print "<div id='classTitleBar'     class='theHeader'           style='top:0;left:16;width:3000;height:18'>Report Class</div>\n";
	print "   <div id='classTitleCopy' class='theHeader'           style='color:yellow;top:0;left:490;' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"yellow\"' onclick='copyClass()'>Copy To Report</div>\n";
	print "   <div id='classTitle00'   class='thePropertyLeft'     style='top:25;'><p id='classTitleWords00'><textarea rows='28' cols='70' name='classcode' id='classcode' onfocus='emptyID()' ></textarea></p></div>\n";
	print "</div>\n";


//-----report box
	print "<div id='reportProperties' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color:#666666;top:200;left:200;height:150;width:300' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>\n";
	print "<div id='reportTitleClose' class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"white\"' onclick='this.parentNode.style.visibility = \"hidden\"'><b>X</b></div>\n";
	print "<div id='reportTitleBar'   class='theHeader'           style='top:0;left:16;width:3000;height:18'>Report Properties</div>\n";

	print "   <div id='reportTitle00'    class='thePropertyLeft'     style='top:25;'><p id='reportTitleWords00'>Width</p></div>\n";
	print "   <div id='reportValue00'    class='thePropertyRight'    style='top:25;'><input id='rproperty00' value='$theLayout->Width' style='width:50' onfocus='emptyID()' onchange='pageWidth(this)'/></div>\n";
	
	print "   <div id='reportTitle01'    class='thePropertyLeft'     style='top:50;'><p id='reportTitleWords01'>Height</p></div>\n";
	print "   <div id='reportValue01'    class='thePropertyRight'    style='top:50;'><input id='rproperty01' value='$theLayout->Height' style='width:50' onfocus='emptyID()' /></div>\n";
	
	print "   <div id='reportTitle02'    class='thePropertyLeft'     style='top:75;'><p id='reportTitleWords02'>Paper Type</p></div>\n";
	print "   <div id='reportValue02'    class='thePropertyRight'    style='top:75;'><input id='rproperty02' value='$theLayout->PaperType' style='width:90' onfocus='emptyID()' onchange='pageWidth(this)'/></div>\n";
	
	print "   <div id='reportTitle03'    class='thePropertyLeft'     style='top:100;'><p id='reportTitleWords03'>Orientation</p></div>\n";
	print "   <div id='reportValue03'    class='thePropertyRight'    style='top:100;'><input id='rproperty03' value='$theLayout->Orientation' onchange='var bob = \"lLpP\"; if(bob.indexOf(this.value) == -1){alert(\"Must be P or L\");this.value= \"\";}' style='width:15' onfocus='emptyID()' /></div>\n";
	
	print "</div>\n";




//-----sortorder box
	print "<div id='sortorderProperties' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color:#666666;top:200;left:200;height:250;width:450' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>\n";
	print "<div id='sortorderTitleClose' class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color=\"red\"' onmouseout='this.style.color=\"white\"' onclick='this.parentNode.style.visibility = \"hidden\"'><b>X</b></div>\n";
	print "<div id='sortorderTitleBar'   class='theHeader'           style='top:0;left:16;width:3000;height:18'>Sort Order</div>\n";
	print "   <div id='sortorderValue00'    class='thesortorderLeft'    style='top:25;'><input name='property00' id='property00' value='" . $theLayout->Groups[0]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/></div>\n";
	if($theLayout->Groups[0]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect00'   class='thesortorderRight'   style='top:25;'><select  id='sort00' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue01'    class='thesortorderLeft'    style='top:50;'><input name='property01' id='property01' value='" . $theLayout->Groups[1]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup1' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[1]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect01'   class='thesortorderRight'   style='top:50;'><select  id='sort01' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue02'    class='thesortorderLeft'    style='top:75;'><input name='property02' id='property02' value='" . $theLayout->Groups[2]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup2' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[2]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect02'   class='thesortorderRight'   style='top:75;'><select  id='sort02' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue03'    class='thesortorderLeft'    style='top:100;'><input name='property03' id='property03' value='" . $theLayout->Groups[3]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup3' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[3]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect03'   class='thesortorderRight'   style='top:100;'><select  id='sort03' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue04'    class='thesortorderLeft'    style='top:125;'><input name='property04' id='property04' value='" . $theLayout->Groups[4]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup4' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[4]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect04'   class='thesortorderRight'   style='top:125;'><select  id='sort04' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue05'    class='thesortorderLeft'    style='top:150;'><input name='property05' id='property05' value='" . $theLayout->Groups[5]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup5' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[5]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect05'   class='thesortorderRight'   style='top:150;'><select  id='sort05' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue06'    class='thesortorderLeft'    style='top:175;'><input name='property06' id='property06' value='" . $theLayout->Groups[6]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup6' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[6]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect06'   class='thesortorderRight'   style='top:175;'><select  id='sort06' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	print "   <div id='sortorderValue07'    class='thesortorderLeft'    style='top:200;'><input name='property07' id='property07' value='" . $theLayout->Groups[7]->Field . "' style='width:150' onfocus='isLastEmpty(this);' onblur='alterSections(this)'/> <input name='moveup7' type='button' class='moveup' value='Move Up' onmousedown='nodrag()' onclick='moveSectionUp(this)' /></div>\n";
	if($theLayout->Groups[7]->SortOrder==''){$asc='selected';$desc='';}else{$asc='';$desc='selected';}
	print "   <div id='sortorderSelect07'   class='thesortorderRight'   style='top:200;'><select  id='sort07' class='objects' style='width:100'><option $asc value=''>Ascending</option><option $desc value='DESC'>Descending</option></select></div>\n";
	
	print "</div>\n";



?>

<div id='listObjects' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color: gray;top:100;left:100;height:450;width:450' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>
	<div id='listTitleClose' class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color="red"' onmouseout='this.style.color="white"' onclick='hideGroupBox(this)'><b>X</b></div>
	<div id='listTitleBar'   class='theHeader'           style='top:0;left:16;width:3000;height:18'>List the Objects in Selected Section</div>


	<div id='listTitle00'    class='thePropertyLeft'     style='top:40;'>Object List</div>
	
	
	<div id='listValue00'    class='thePropertyRight'    style='top:60;left:20;'>
		<select name="list_ids" id="list_ids" class="objects"  multiple size='14' onscroll="mouseUpId(event,'listObjects')" onchange="autoHighlight(this)" ondblclick="removeFromObjectList()" style="width:400;postition:absolute">
		</select>
	</div>

	<div id='listValue01'    class='thePropertyRight'    style='text-align:center;width:200;height:20;top:350;left:10;'>
		<input type='button' id='highlight_list' value='Highlight Selected Object' onclick='highlightSelected("list_ids")' style='width:200'/><BR/>
	</div>
	
	<div id='listValue02'    class='thePropertyRight'    style='text-align:center;width:200;height:20;top:350;left:210;'>
		<input type='button' id='to_group' value='Send to Group Editor' onclick='toGroupEditor()' style='width:200'/><BR/>
	</div>

	<div id='listValue03'    class='thePropertyRight'    style='text-align:center;width:200;height:20;top:380;left:10;'>
		<input type='button' id='highlight_list' value='Select All' onclick='selectAllInSectionSelect()' style='width:200'/><BR/>
	</div>
	
	<div id='listValue04'    class='thePropertyRight'    style='text-align:center;width:200;height:20;top:380;left:210;'>
		<input type='button' id='to_group' value='Deselect Selected' onclick='deselectSelectedInSectionSelect()' style='width:200'/><BR/>
	</div>
	
	<div id='listValue05'    class='thePropertyRight'    style='text-align:center;width:200;height:20;top:410;left:10;'>
		<input type='button' id='highlight_list' value='Delete Selected' onclick='deleteSelected()' style='width:200'/><BR/>
	</div>
</div>
	
<div id='groupProperties' style='visibility:hidden;overflow:hidden;position:absolute;z-index:1;font-size:14;border-width:1;border-style:solid;border-color:black;background-color: gray;top:200;left:200;height:450;width:450' onmousedown='mouseDown(event,this)' onmouseup='mouseUp(event,this)' onkeydown='shiftIsDown=true' onkeyup='shiftIsDown=false'>
   <div id='groupTitleClose' class='theHeader'           style='cursor:default;overflow:hidden;position:absolute;font-size:14;color:white;background-color:black;top:0;left:0;height:18;width:20' onmouseover='this.style.color="red"' onmouseout='this.style.color="white"' onclick='hideGroupBox(this)'><b>X</b></div>
   <div id='groupTitleBar'   class='theHeader'           style='top:0;left:16;width:3000;height:18'>Change A Group of Objects Properties</div>
   <div id='groupTitle00'    class='thePropertyLeft'     style='top:25;'>Object Property</div>

      <div id='groupValue00'    class='thePropertyRight'    style='top:25;'>

         <select name="object_property" id="object_property" onchange='replace_object_value(this.value)' class="objects" style="width:200">
         <option value='0'>Section</option'>
         <option value='1'>Top</option'>
         <option value='2'>Left</option'>
         <option value='3'>Width</option'>
         <option value='4'>Height</option'>
         <option value='5'>Border Width</option'>
         <option value='6'>Border Color</option'>
         <option value='7'>Color</option'>
         <option value='8'>Font Size</option'>
         <option value='9'>Font Weight</option'>
         <option value='10'>Font Family</option'>
         <option value='11'>Background Color</option'>
         <option value='12'>Text Align</option'>
         <option value='13'>Can Grow</option'>
         <option value='14'>Format</option'>
         </select>

      </div>

      <div id='groupTitle01'    class='thePropertyLeft'     style='top:60;'>Object List
      	 <br/><br/><br/>
         <br/><input accesskey='a' id='object_add'    type='button' value='Add object' onclick="addToList()" style='width:150'/>
         <br/><input accesskey='e' id='object_remove' type='button' value='rEmove object' onclick="removeFromList()" style='width:150'/>
         <br/><input id='object_clear' type='button' value='clear list' onclick="clearList()" style='width:150'/>
         <br/><input id='object_sort' type='button' value='sort by position' onclick="sortListByPosition('object_ids')" style='width:150'/>
      	
      </div>


      <div id='groupValue01'    class='thePropertyRight'    style='top:60;'>


         <select name="object_ids" id="object_ids" class="objects"  multiple size='14' onscroll="mouseUpId(event,'groupProperties')" onchange="autoHighlight(this)" ondblclick="removeFromList()" style="width:160;width:200">
         </select>

      </div>

      <div id='groupTitle03'    class='thePropertyLeft'     style='top:300;'>New Value</div>
      <div id='groupValue03'    class='thePropertyRight'    style='top:300;'>


         <input id='object_value' value='' style='width:200'/>

      </div>

      <div id='groupTitle03'    class='thePropertyLeft'     style='top:350;'><br/>move 
      	

         <select name="pixel_move" accesskey='p' id="pixel_move" class="objects" style="width:60">
         <option value='1'>1</option'>
         <option value='5'>5</option'>
         <option value='10'>10</option'>
         <option value='50'>50</option'>
         <option value='100'>100</option'>
         </select> Pixels




      	
      </div>
      <div id='groupValue03'    class='thePropertyRight'    style='text-align:center;width:200;top:350;'>


         <input type='button' accesskey='u' id='move_up' value='Up' onclick='group_top(-1)' style='width:50'/><BR/>
         <input type='button' accesskey='l' id='move_left' value='Left' onclick='group_left(-1)' style='width:50'/>
         <input type='button' accesskey='r'  id='move_right' value='Right' onclick='group_left(1)' style='width:50'/><BR/>
         <input type='button' accesskey='o'  id='move_down' value='dOwn' onclick='group_top(1)' style='width:50'/>

      </div>

</div>




<?php




	$sectionArray          = array(1,3,5,7,9,11,13,15,17,0,18,16,14,12,10,8,6,4,2);
	$sectionHeaderArray    = array(1,3,5,7,9,11,13,15,17);
	$sectionFooterArray    = array(2,4,6,8,10,12,14,16,18);
	
	for($i = 0 ; $i < count($theLayout->Groups)+2 ; $i++){
		$HTML              = $HTML . sectionNumber($sectionHeaderArray[$i], $theLayout, 'visible');
	}
	$HTML  	               = $HTML . sectionNumber(0, $theLayout, 'visible');
	for($i = count($theLayout->Groups)+1 ; -1 < $i ; $i--){
		$HTML              = $HTML . sectionNumber($sectionFooterArray[$i], $theLayout, 'visible');
	}

	for($i = 0 ; $i < 20 ; $i++){
		$HTML              = $HTML . sectionNumber('hidden'.$i, $theLayout, 'hidden');
	}


	print "$HTML</form></body></html>";



	print "\n<script>\n\n";
	print "\nvar reportGroup = new Array()\n";
	for($i = 0 ; $i < count($theLayout->Groups) ; $i++){
		print "reportGroup[$i] = '" . $theLayout->Groups[$i]->Field. "'\n";
	}

	print "\nvar reportSection = new Array()\n";
	for($i = 0 ; $i < count($GLOBALS['sectionNames']) ; $i++){
		print   "reportSection[$i] = '" . $GLOBALS['sectionNames'][$i] . "'\n";
	}

	print "\nvar hiddenSection = new Array()\n";
	for($i = 0 ; $i < 20 ; $i++){
		print   "hiddenSection[$i] = 'hidden$i'\n";
	}

	$textFormat = textFormatsArray();
	print "\nvar theFormat = new Array()\n";
	for($i = 0 ; $i < count($textFormat) ; $i++){
		print   "theFormat[$i] = '" . $textFormat[$i]->sample. "'\n";
	}
	print   "theFormat[20] = ''\n";

	print "\nvar sortOrder = new Array()\n";
	print "sortOrder[0] = 'Ascending'\n";
	print "sortOrder[1] = 'Descending'\n";

	print "\nvar theAnswer = new Array()\n";
	print "theAnswer[0] = 'False'\n";
	print "theAnswer[1] = 'True'\n";

	print "\nvar theAlign = new Array()\n";
	print "theAlign[0] = 'left'\n";
	print "theAlign[1] = 'right'\n";
	print "theAlign[2] = 'center'\n";

	print "\nvar theFamily = new Array()\n";
	print "theFamily[0] = 'Arial'\n";
	print "theFamily[1] = 'Courier'\n";
	print "theFamily[2] = 'Georgia'\n";
	print "theFamily[3] = 'Impact'\n";
	print "theFamily[4] = 'Tahoma'\n";
	print "theFamily[5] = 'Times'\n";
	print "theFamily[6] = 'Verdana'\n";
	print "theFamily[7] = 'Symbol'\n";
	print "theFamily[8] = 'Webdings'\n";
	print "theFamily[9] = 'Wingdings'\n";


	print "\nvar theWeight = new Array()\n";
	print "theWeight[0] = 'normal'\n";
	print "theWeight[1] = 'bold'\n";
	print "theWeight[2] = 'lighter'\n";

	print "\nvar theControl = new Array()\n";
	print "theControl[0] = 'Field'\n";
	print "theControl[1] = 'Label'\n";
	print "theControl[2] = 'Graph'\n";
	print "theControl[3] = 'PageBreak'\n";

	print "\nvar theGraph = new Array()\n";
	$tg         = nuRunQuery("SELECT sag_graph_name FROM zzsys_activity_graph WHERE sag_zzsys_activity_id = '$reportID'");
	$tcount     = 1;
	print "theGraph[0] = ''\n";
	while($rg   = db_fetch_row($tg)){
		print "theGraph[$tcount] = 'graph-$rg[0]'\n";
		$tcount = $tcount + 1;
	}
	
	//added by nick 15-01-09
	$tg         = nuRunQuery("SELECT sim_code FROM zzsys_image ");
	while($rg   = db_fetch_row($tg)){
		print "theGraph[$tcount] = 'image-$rg[0]'\n";
		$tcount = $tcount + 1;
	}

	print "\n</script>\n";


function sectionNumber($pSection, $pLayout, $pVisible){

	$arrayNumberOfSection             = -1;
	for($i = 0 ; $i < count($pLayout->Sections) ; $i++){
		if($pLayout->Sections[$i]->SectionNumber == $pSection){
			$arrayNumberOfSection     = $i;
		}
	}
	if($arrayNumberOfSection == -1){//--not created by Access Report
		$theSection                   = new buildSection($pSection, $pLayout, '#ffffff', '0', '2999', $pVisible);
	}else{
		$S                            = $pLayout->Sections[$arrayNumberOfSection];
		$theSection                   = new buildSection($pSection, $pLayout, $S->BackColor, $S->Height, '2000', $pVisible);
	}
	return $theSection->HTML;
}

function resize(){
	return 0.0679;
}

function sectionDescription($pSection, $pLayout){
	if($pSection == '0'){return 'Detail';}
	if($pSection == '1'){return 'Report_Header';}
	if($pSection == '2'){return 'Report_Footer';}
	if($pSection == '3'){return 'Page_Header';}
	if($pSection == '4'){return 'Page_Footer';}
	if(round($pSection/2) == $pSection/2){ //--this is an even number (section footer)
		return $pLayout->Groups[($pSection-6)/2]->Field . '_Footer' ;
	}else{ //--(section header)
		return $pLayout->Groups[($pSection-5)/2]->Field . '_Header' ;
	}
}

class buildSection{

    public  $name                = '';
    public  $tag                 = '';
    public  $back_ground_color   = '';
    public  $height              = '';
    public  $section             = '';
    public  $layout              = '';
    public  $visible             = '';
    public  $HTML                = '';

    function __construct($pSection, $pLayout, $pBGColor, $pHeight, $pWidth, $pVisible){

		if($pVisible=='hidden'){
			$this->name          = $pSection;
		}else{
			$this->name          = sectionDescription($pSection, $pLayout);
		}
		$this->tag               = $pSection;
		$this->back_ground_color = $pBGColor;
		if(isNB()){ //-- is nuBuilder
			$this->height            = $pHeight;
		}else{
			$this->height            = $pHeight * resize();
		}
		if($this->height > 0 and $this->height < 1){
			$this->height        = 1;
		}
		$this->section           = $pSection;
		$this->layout            = $pLayout;
		$this->visible           = $pVisible;
		$this->HTML              = $this->buildHTML();
    }

    private function buildHTML(){
    	if($this->visible == 'visible'){
			$GLOBALS['sectionNames'][]= $this->name;
    	}
		if($this->height == 0){
			$border              = 'lightgrey';
		}else{
			$border              = 'black';
		}
		$s  =      "\n\n<div id='$this->name' tag='$this->tag' style='visibility:$this->visible;position:relative;overflow:hidden;font-size:14;";
		$s  = $s . "border-width:1;border-style:solid;border-color:$border;border-left-width:15;";
		if($this->back_ground_color=='' or $this->back_ground_color=='white' or $this->back_ground_color=='ffffff' or $this->back_ground_color=='#ffffff'){
			$s  = $s . "background-color: #ebebeb;";
		}else{
			$s  = $s . "background-color: $this->back_ground_color;";
		}
		$s  = $s . "height          : $this->height;";
		$s  = $s . "width           : " . $this->layout->Width . ";'";
		$s  = $s . "onclick         = 'loadSectionProperties(this); selectSectionObjects();' ";
		$s  = $s . "onchange        = 'loadSectionProperties(this); selectSectionObjects();' ";
		$s  = $s . "><div id='text_$this->name'>$this->name</div>\n\n";

		for($i = 0 ; $i < count($this->layout->Controls) ; $i++){
			if($this->layout->Controls[$i]->Section == $this->section){
				$theObject       = new buildObject($this->layout->Controls[$i]);
				$s               = $s . $theObject->HTML;
			}

		}

		$s  = $s . "</div>\n\n";
		$s  = $s . "<script language='javascript'>setStyle('$this->name','tag','$this->tag');</script>";
		return $s;
	
    }


}


class buildObject{

    public  $obj                 = null;
    public  $tag                 = '';
    public  $graph               = '';
    public  $value               = '';
	public  $name                = '';
	public  $top                 = '';
	public  $left                = '';
	public  $width               = '';
	public  $height              = '';
	public  $color               = '';
	public  $back_ground_color   = '';
	public  $border_width        = '';
	public  $border_color        = '';
	public  $border_style        = '';
    public  $font_weight         = '';
    public  $font_family         = '';
    public  $text_align          = '';
    public  $can_grow            = '';
    public  $HTML                = '';
    public  $format              = '';

    function __construct($pObject){

		$this->obj               = $pObject;
		$this->tag               = $GLOBALS['controlType'][$this->obj->ControlType];
		
		$this->font_weight       = $this->obj->FontWeight;
		$this->font_family       = $this->obj->FontName;
		$alignment               = array('left', 'left', 'center', 'right');
		$this->text_align        = $alignment[$this->obj->TextAlign];
		if($this->text_align==''){
			$this->text_align = $this->obj->TextAlign;
		}
		$this->value             = $this->obj->Value;
		if($this->tag == 'Label'){$this->value                 = $this->obj->Caption;}
		if($this->tag == 'Field'){$this->value                 = $this->obj->ControlSource;}
		if($this->tag == 'Graph'){$this->obj->BackColor        = 'black';}
		if($this->tag == 'Graph'){$this->obj->ForeColor        = 'white';}
		if($this->tag == 'Graph'){$this->obj->FontSize         = '10';}
		if($this->tag == 'PageBreak'){$this->obj->BorderColor  = 'black';}
		if($this->tag == 'PageBreak'){$this->obj->borderWidth  = '4';}
		if($this->tag == 'PageBreak'){$this->obj->height       = 4  * resize();}
		if($this->tag == 'PageBreak'){$this->obj->width        = 44 * resize();}
		if($this->tag == 'PageBreak'){$this->obj->left         = 0  * resize();}
		$this->name              = $this->obj->Name;

		if(isNB()){ //-- is nuBuilder
			if($this->tag == 'Graph'){$this->value             = $this->obj->Value;}
			if($this->tag == 'Graph'){$this->graph             = $this->obj->Graph;}
		    $this->font_size         = $this->obj->FontSize;
			$this->top               = $this->obj->Top;
			$this->left              = $this->obj->Left;
			$this->width             = $this->obj->Width;
			$this->height            = $this->obj->Height;
		}else{
			if($this->tag == 'Graph'){$this->value             = $this->obj->Tag;}
			if($this->tag == 'Graph'){$this->graph             = $this->obj->Name;}
			if($this->font_family   == 'Arial'){
			    $this->font_size     = floor($this->obj->FontSize  * 1.3);
			}else{
			    $this->font_size     = floor($this->obj->FontSize  * 1.5);
			}
			$this->top               = $this->obj->Top      * resize();
			$this->left              = $this->obj->Left     * resize();
			$this->width             = $this->obj->Width    * resize();
			$this->height            = $this->obj->Height   * resize();
		}

		$this->color             = $this->obj->ForeColor;
		$this->back_ground_color = $this->obj->BackColor;
		$this->border_width      = $this->obj->BorderWidth;
		$this->border_color      = $this->obj->BorderColor;
		$this->border_style      = $this->obj->BorderStyle;
		$this->can_grow          = $this->obj->CanGrow;
		$textFormat              = textFormatsArray();
		$this->format            = $this->obj->Format;
		$this->HTML              = $this->buildHTML();
    }

    private function buildHTML(){

		$dq = '"';
	
		$s  =      "<input \n"; 
		$s  = $s . "onkeydown     = 'oKeyDown()'\n"; 
		$s  = $s . "onkeyup       = 'oKeyUp()'\n"; 
		$s  = $s . "onmousedown   = 'mouseDown(event,this)'\n"; 
		$s  = $s . "onmouseup     = 'mouseUp(event,this)' \n";
		$s  = $s . "onchange      = 'objValueChange(this.id)' \n";
		$s  = $s . "onfocus       = 'loadObjectProperties(this)' \n";
		$s  = $s . "class         = 'theObject' \n";
		$s  = $s . "id            = '$this->name' \n";
		$s  = $s . "tag           = '$this->tag' \n";
		$s  = $s . "value         = '$this->value'\n";
		$s  = $s . "style         = 'position           : absolute;\n";
		$s  = $s . "                 border-style       : solid;\n";
		$s  = $s . "                 name               : $this->name;\n"; //this
		$s  = $s . "                 type               : $this->tag;\n"; //this
		$s  = $s . "                 id                 : $this->name;\n";
		$s  = $s . "                 tag                : $this->tag;\n"; //this
		$s  = $s . "                 value              : $this->value;\n"; //this
		$s  = $s . "                 top                : $this->top;\n";
		$s  = $s . "                 left               : $this->left;\n";
		$s  = $s . "                 width              : $this->width;\n";
		$s  = $s . "                 height             : $this->height;\n";
		$s  = $s . "                 color              : $this->color;\n";
		$s  = $s . "                 font-size          : $this->font_size;\n";
		$s  = $s . "                 font-weight        : $this->font_weight;\n";
		$s  = $s . "                 font-family        : $this->font_family;\n";
		$s  = $s . "                 background-color   : $this->back_ground_color;\n";
		$s  = $s . "                 border-width       : $this->border_width;\n";
		$s  = $s . "                 border-color       : $this->border_color;\n";
		$s  = $s . "                 widthwas           : 0;\n";
		$s  = $s . "                 colorwas           : 000000;\n";
		$graphString = $this->graph;
		if($this->tag == 'Graph'){
			if($this->format == ''){$this->format = 'graph';}
			$graphString = $this->format."-".$this->graph;
		}
		$s  = $s . "                 graph              : $graphString;\n"; //this
		$s  = $s . "                 cangrow            : $this->can_grow;\n"; //this
		$s  = $s . "                 format             : $this->format;\n"; //this
		$s  = $s . "                 text-align         : $this->text_align;'\n";

		$s  = $s . "/>";
		//added in by nick. trying to get custom items to store in a hashmap. the items labelled "//this" above are custom things that need to be stored in the hash
		//at a later date,  any code that touches custom style elements should be replaced with code that deals with the hash
		$s  = $s . "<script language='javascript'>setupCustomStyle('$this->name','$this->name','$this->tag','$graphString','$this->tag','$this->value','$this->can_grow','$this->format');</script>\n\n\n";
		return $s;
    }


}

function isNB(){
	$theLayout = new Reporting();
	return $theLayout->nuBuilder == '1';
}

?>
