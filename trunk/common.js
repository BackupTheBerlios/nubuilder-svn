/*
** File:           common.js
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

//-------------------------------DEBUG WINDOW-------------   
// Debugging console window variable
var nu_debugwin;

// Display and add text to a debugging console window
function nuDebug(text) {

	// Open the console window if it isn't currently
	if ((undefined == nu_debugwin) || nu_debugwin.closed) {
	
		// Open a new popup window
		nu_debugwin = window.open('about:blank', '_blank', 'location=no, menubar=no, status=no, toolbar=no, resizable=yes');
	
		// Set up the base HTML document
		nu_debugwin.document.write('<html><head><title>Debug Window</title></head><body style="font-family: monospace;"><h2>Debugging Output</h2><br/>\n');
	}
	
	// Write the debugging text to the page
	nu_debugwin.document.write(text + '<br/>\n');
}
   
//-------------------------------END DEBUG WINDOW-------------   
   

//--creates an array of row prefixes of a subform
function nuSubformRowArray(pSubformName){

   TheRows        = Number(document.getElementById('rows'+pSubformName).value);
   TheArray       = Array();
   ThePrefix      = String();
   RowNo          = String();

   for(i = 0;i < TheRows; ++i){

      RowNo       = '0000' + String(i);
      RowNo       = RowNo.substring(RowNo.length - 4);
      TheArray[i] = pSubformName.concat(RowNo);


   }
   return TheArray;
}




function nuJax(pURL) {
//-- pass url to run

   var d                                                  = new Date();
   theID                                                  = 'a' + String(d.getTime()) + 'a';
   newObj                                                 = document.createElement('div');
   newObj.setAttribute('id', 'div_'+theID);
   newObj.innerHTML                                       = "<iframe src='' id='" + theID + "' />";
   document.body.appendChild(newObj);
   document.getElementById(theID).style.position          = 'absolute';
   document.getElementById(theID).style.height            = '0';
   document.getElementById(theID).style.width             = '0';
   document.getElementById(theID).style.backgroundColor   = 'red';
   document.getElementById(theID).style.overflow          = 'hidden';
   document.getElementById(theID).style.top               = '100';
   document.getElementById(theID).style.left              = '100';
   document.getElementById(theID).style.visibility        = 'visible';
   document.getElementById(theID).src                     = pURL;

}


function jsDateToSqlDate(pDate){
//-- parameter passed is a js date object
//-- returns a string formatted like "yyyy-mm-dd hh:mm:ss"
   var d        = pDate;
   var sqlDate  = String();

   sqlDate      =           d.getFullYear()           + '-';
   sqlDate      = sqlDate + twoChar(d.getMonth() + 1) + '-';
   sqlDate      = sqlDate + twoChar(d.getDate())      + ' ';
   sqlDate      = sqlDate + twoChar(d.getHours())     + ':';
   sqlDate      = sqlDate + twoChar(d.getMinutes())   + ':';
   sqlDate      = sqlDate + twoChar(d.getSeconds());

   return sqlDate;
}


function twoChar(pString){

   var vString = String(pString);

   if(vString.length == 1){
      vString   = '0' + vString + '';
   }
   return vString
}





function nuColumnTotal(pthis, pTotalField){
	var SF           = String('');
	var SFname       = String('');
	var FDname       = String('');
	var ROWno        = String('');
	var TESTname     = String('');
	var theTotal     = Number(0);
	var SFlist       = Number(document.getElementById('theform').TheSubforms.value);
	for(i=0;i<SFlist;i++){
		if(SF==''){
			SFname              = document.getElementById('theform')['SubformNumber'+i].value;
			if(SFname == pthis.name.substr(0,SFname.length)){
				FDname      = pthis.name.substr(SFname.length+4);
				TESTname    = SFname + '0000' + FDname;
				if(TESTname.length == pthis.name.length){
					SF  = SFname;
				}
			}
		}
		
	}
	if(SF==''){
		theTotal                            = Number(document.getElementById('theform')[SFname+ROWno+FDname].value)
	}else{
		for(i=0;i<Number(document.getElementById('theform')['rows'+SFname].value);i++){
			ROWno                       = rowString(i);
			if(document.getElementById('theform')['deletebox'+SFname].value == '1'){
				if(!document.getElementById('theform')['row'+SFname+ROWno].checked){
					theTotal    = theTotal + Number(document.getElementById('theform')[SFname+ROWno+FDname].value)
				}
			}
		}
	}
	document.getElementById('theform')[pTotalField].value = theTotal;
	nuFormat(document.getElementById('theform')[pTotalField]);
}


function rowString(pRow){
	var zeros  = '0000';
	return zeros.substr(0,4-String(pRow).length)+String(pRow);
}


function nuMask(pthis, plist){
	var str = '';
	var len = 0;
	var gap = 0;
	var sep = '';
	var max = 0;

	for (var i = 0; i < plist.split(",").length; i++){
		max = max + Number(plist.split(",")[i]);
		i++;
		max = max + Number(plist.split(",")[i].length);
	}

	for (var i = 0; i < plist.split(",").length; i++){
		gap = Number(plist.split(",")[i]);
		i++;
		sep = plist.split(",")[i];
		str = str + pthis.value.substr(len,gap);
		if(pthis.value.substr(len,gap).length == gap){
			str = str + sep;
		}
		len = len + gap + plist.split(",")[i].length;
	}
	pthis.value = str.substr(0,max);

	return pthis.value.length != max;

}



function uDBsmall(pThis,pType){ //---format

	if(pThis.accept!='' && pType == 'text'){;
		nuFormat(pThis);
	}
}


function uDB(pThis,pType){ //---format and save to database

	if(parent.frames[0].document.getElementById('____' + pThis.id)){  //--check if there is such a fieldname
		parent.frames[0].document.getElementById('____' + pThis.id).value = ''; //--set value to blank so that it gets updated
	}
	var framenumber       = parent.frames[0].document.theform.framenumber.value *1 + 1;
	if (framenumber > 9){
		parent.frames[0].document.theform.framenumber.value = 0;
	}else{
		parent.frames[0].document.theform.framenumber.value = framenumber;
	}

	if(pThis.accept!='' && pType == 'text'){;
		nuFormat(pThis);
	}
	if(arguments.length==1){ 
		pType = '';
	}
	parent.frames[0].document.theform.beenedited.value = '1'
	var url      = 'formsetvalues.php?x=1&type='+pType+'&dir=' + customDirectory() + '&id=' + form_session_id()  + '&ses=' + session_id() + '&name=' + pThis.id;
//nuDebug(url);	
	parent.frames[framenumber].document.location = url;
}


function openCalendar(pTarget, pDay, pFormat){
	var url = 'calendar.php?target=' + pTarget + '&pDay=' + pDay + '&theFormat=' + pFormat+'&dir='+customDirectory();
	foc=window.open(url,'theCal','width=300,height=300');
	foc.focus();

}


function openFileUpload(pFileName){

//--pFileName is the name of the text object on the opener document that will be updated with the new name of the file that has been uploaded

	var url = 'fileuploader.php?dir=' + customDirectory() + '&ses=' + session_id() + '&field=' + pFileName;
	foc=window.open(url,'theldr','width=380,height=100');
	foc.focus();

}


function openImageUpload(){

	var url = 'imageuploader.php?dir=' + sd() + '&iid=' + document.getElementById('recordID').value;
	foc=window.open(url,'theldr','width=380,height=100');
	foc.focus();

}


function openDownload(pFileName){

   w = window.open(web_root_path()+document.getElementById(pFileName).value,'test',maximumScreen());
   w.focus();
   
}



function backToIndex(){

	if(window.parent.name == 'index'){
		return;
	}
	if(window.parent.opener.name == 'index'){
		parent.opener.focus();
		return;
	}
	if(window.parent.opener.opener.name == 'index'){
		parent.opener.opener.focus();
		return;
	}
	if(window.parent.opener.opener.opener.name == 'index'){
		parent.opener.opener.opener.focus();
		return;
	}
	if(window.parent.opener.opener.opener.opener.name == 'index'){
		parent.opener.opener.opener.opener.focus();
		return;
	}
	if(window.parent.opener.opener.opener.opener.opener.name == 'index'){
		parent.opener.opener.opener.opener.opener.focus();
		return;
	}
	if(window.parent.opener.opener.opener.opener.opener.opener.name == 'index'){
		parent.opener.opener.opener.opener.opener.opener.focus();
		return;
	}

}


function maximumScreen(){
	return "top=0,left=0,width=1010,height=745,titlebar=no,resizable=yes,status=0,scrollbars=yes";
}


function openBrowseSmall(pFormID, pFilter){

	var vURL              = "browsesmall.php?x=1&p=1&f="+pFormID+"&s="+pFilter;
	e                     = window.open(vURL,'B'+pFormID,maximumScreen());
	e.focus();
	return true;
}


function openFormSmall(pFormID, pRecordID){

	var vURL              = "formsmall.php?x=1&f="+pFormID+"&r="+pRecordID+"&t=test";
	e                     = window.open(vURL,'F'+pFormID,maximumScreen());
	e.focus();
	return true;

}


function openBrowse(pFormID, pFilter, pPrefix, pSession, pFormSession){

//---pSession = formsessionID if browsing a lookup or session_id if editing a record

	var vURL              = "browse.php?x=1&p=1&f=" + pFormID + "&s=" + pFilter + "&prefix=" + pPrefix + '&dir=' + customDirectory() + '&ses='+ pSession + '&form_ses='+ pFormSession;
	e                     = window.open(vURL,'B'+pFormID,maximumScreen());
	e.focus();
	return true;
}


function openForm(pFormID, pRecordID){

	var vURL              = "nubuilder.php?x=1&f=" + pFormID + "&r=" + pRecordID + '&dir=' + customDirectory() + '&ses=' + session_id();
	e                     = window.open(vURL,'F'+pFormID,maximumScreen());
	e.focus();
	return true;

}

function openEdit(pFieldID, pLang){

	var vURL              = "nuedit.php?x=1&f=" + pFieldID + "&l=" + pLang;
	e                     = window.open(vURL,'E'+pFieldID,maximumScreen());
	e.focus();
	return true;

}

function openHelp(pFormID){

	var vURL              = "formhelp.php?x=1&f=" + pFormID + '&dir=' + customDirectory();
	e                     = window.open(vURL,'F'+pFormID,maximumScreen());
	e.focus();
	return true;

}


function openWYSIWYG(pFieldID){

	var vURL              = "nuwysiwyg.php?x=1&f=" + pFieldID;
	e                     = window.open(vURL,'E'+pFieldID,maximumScreen());
	e.focus();
	return true;

}

function right(pString, pLength){
   return pString.substr(pString.length - pLength);
}



function validateLU(pRecordID, pPrefix, pNewID, pFormSession){

	var framenumber       = parent.frames[0].document.theform.framenumber.value *1 + 1;
	if (framenumber > 9){
		parent.frames[0].document.theform.framenumber.value = 0;
	}else{
		parent.frames[0].document.theform.framenumber.value = framenumber;
	}
	parent.frames[framenumber].document.location = "formlookup.php?x=1&r="+pRecordID+"&p="+pPrefix+"&o=code&n="+pNewID + '&dir=' + customDirectory() + '&form_ses=' + form_session_id() + '&ses=' + session_id();

}





function getRecordFromList(pRecordID, pPrefix, pNewID){

	var framenumber       = window.opener.parent.frames[0].document.theform.framenumber.value *1 + 1;
	if (framenumber > 9){
		window.opener.parent.frames[0].document.theform.framenumber.value = 0;
	}else{
		window.opener.parent.frames[0].document.theform.framenumber.value = framenumber;
	}

	window.opener.parent.frames[framenumber].document.location = "formlookup.php?x=1&r="+pRecordID+"&p="+pPrefix+"&o=id&n="+pNewID + '&dir=' + customDirectory() + '&form_ses=' + form_session_id() + '&ses=' + session_id();
	window.close();
	window.opener.parent.focus();

}


function getRecordFromList__old(pRecordID, pPrefix, pNewID){

	var framenumber       = window.opener.parent.frames[0].theform.framenumber.value *1 + 1;
	if (framenumber > 9){
		window.opener.parent.frames[0].theform.framenumber.value = 0;
	}else{
		window.opener.parent.frames[0].theform.framenumber.value = framenumber;
	}

	window.opener.parent.frames[framenumber].location = "formlookup.php?x=1&r="+pRecordID+"&p="+pPrefix+"&o=id&n="+pNewID + '&dir=' + customDirectory() + '&ses=' + session_id();;
	window.close();
	window.opener.parent.focus();

}






function SelectAll(pListBox){
  var TheListBox = document.getElementById(pListBox);
  for(i=0;i<TheListBox.length;i++){
  	TheListBox[i].selected=true;
  }
}


function nuFormat(pThis){
	var fType             = new String(aType[pThis.accept]);
	var fFormat           = new String(aFormat[pThis.accept]);
	var fDecimal          = new String(aDecimal[pThis.accept]);
	var fSeparator        = new String(aSeparator[pThis.accept]);
	var formattedValue    = pThis.value;
	if(fType         == 'number'){
		formattedValue    = nuFormatNumber(pThis.value, fFormat, fDecimal, fSeparator);
	}
	if(fType         == 'date'){
		formattedValue    = nuFormatDate(pThis.value, fFormat);
	}
	pThis.value = formattedValue;
}


function nuFormatNumber(pValue, pDecimalPlaces, pDecimal, pSeparator){

	if(pValue==''){return '';}
	var splitWhole        = new Array();
	var splitPart         = new Array();
	var halve             = new Array();
	var divYear           = new Array();
	var c                 = 0;
	var whole             = new String();
	var part              = new String();
	var nn                = '';
	var addOne            = false;
	var newValue          = new String(pValue);
	if(pSeparator!=''){

		while(newValue.indexOf(pSeparator)!=-1){
			newValue          = newValue.replace(pSeparator,'');     //---remove separators
		}
	}
	newValue              = newValue.replace(pDecimal,'.');    //---make sure decimal is '.'
	var halve             = newValue.split('.');                //---split whole and part
	splitWhole            = halve[0].split('');
	if(halve.length==2){
		splitPart         = halve[1].split('');
	}

	//---format whole portion of number
	for (i=splitWhole.length ; i>0 ; i--){
	   if(c==3||c==6||c==9||c==12||c==15){
	      if(splitWhole[i-1]!='-'){                           //---if not a minus number
	         whole = pSeparator+whole;
	      }
	   }
	   c   = c + 1;
	   whole = splitWhole[i-1]+whole;
	}



	//---format part portion of number
	for (i=0 ; i < splitPart.length ; i++){
		nn = splitPart[i];
		if(i < pDecimalPlaces){
			part = part+''+nn;
		}
	}

	while(part.length < pDecimalPlaces){
		part = part+''+'0';
	}

	if(pDecimalPlaces==0){
		return whole;
	}else{
		return whole + pDecimal + part;
	}

}



function nuFormatDate(pValue, pFormat){


	if(String(pValue).length == 0){
		return '';
	}

	var split             = new Array();
	var dd                = new String();
	var mm                = new String();
	var yy                = new String();
	var fdd               = new String();
	var fmm               = new String();
	var fyy               = new String();
	var strDay            = new String();
	var strMth            = new String();
	var strYr             = new String();
	var d                 = new Date();
	var US                = new Boolean();
	US                    = pFormat.substr(0,1)=='m';

	var strTwoChr     = new Array();
	strTwoChr[1]      = '01';
	strTwoChr[2]      = '02';
	strTwoChr[3]      = '03';
	strTwoChr[4]      = '04';
	strTwoChr[5]      = '05';
	strTwoChr[6]      = '06';
	strTwoChr[7]      = '07';
	strTwoChr[8]      = '08';
	strTwoChr[9]      = '09';
	strTwoChr[10]     = '10';
	strTwoChr[11]     = '11';
	strTwoChr[12]     = '12';
	strTwoChr[13]     = '13';
	strTwoChr[14]     = '14';
	strTwoChr[15]     = '15';
	strTwoChr[16]     = '16';
	strTwoChr[17]     = '17';
	strTwoChr[18]     = '18';
	strTwoChr[19]     = '19';
	strTwoChr[20]     = '20';
	strTwoChr[21]     = '21';
	strTwoChr[22]     = '22';
	strTwoChr[23]     = '23';
	strTwoChr[24]     = '24';
	strTwoChr[25]     = '25';
	strTwoChr[26]     = '26';
	strTwoChr[27]     = '27';
	strTwoChr[28]     = '28';
	strTwoChr[29]     = '29';
	strTwoChr[30]     = '30';
	strTwoChr[31]     = '31';

	var strMonthArray     = new Array();
	strMonthArray[1]      = 'Jan';
	strMonthArray[2]      = 'Feb';
	strMonthArray[3]      = 'Mar';
	strMonthArray[4]      = 'Apr';
	strMonthArray[5]      = 'May';
	strMonthArray[6]      = 'Jun';
	strMonthArray[7]      = 'Jul';
	strMonthArray[8]      = 'Aug';
	strMonthArray[9]      = 'Sep';
	strMonthArray[10]     = 'Oct';
	strMonthArray[11]     = 'Nov';
	strMonthArray[12]     = 'Dec';

	var numMonthArray     = new Array();
	numMonthArray['jan']  = 1;
	numMonthArray['feb']  = 2;
	numMonthArray['mar']  = 3;
	numMonthArray['apr']  = 4;
	numMonthArray['may']  = 5;
	numMonthArray['jun']  = 6;
	numMonthArray['jul']  = 7;
	numMonthArray['aug']  = 8;
	numMonthArray['sep']  = 9;
	numMonthArray['oct']  = 10;
	numMonthArray['nov']  = 11;
	numMonthArray['dec']  = 12;

	//---split date by '/' or '-' or '.'
	if(pValue.indexOf('/')!=-1){split = pValue.split('/');}
	if(pValue.indexOf('-')!=-1){split = pValue.split('-');}
	if(pValue.indexOf('.')!=-1){split = pValue.split('.');}

	if(split.length < 2){
		alert('Invalid Date..');
		return '';
	}
	if(String(split[0]).length == 0 || String(split[1]).length == 0){
		alert('Invalid Date..');
		return '';
	}
	//---add year if needed
	if(split.length == 2){
		split[2]    = d.getFullYear();
	}
	splitFormat   = pFormat.split('-');

	if(US){ //---is USA date
		dd   = split[1];
		mm   = split[0];
		yy   = split[2];
		fdd  = splitFormat[1];
		fmm  = splitFormat[0];
		fyy  = splitFormat[2];
	}else{
		dd   = split[0];
		mm   = split[1];
		yy   = split[2];
		fdd  = splitFormat[0];
		fmm  = splitFormat[1];
		fyy  = splitFormat[2];
	}
	if(String(mm).length  == 3){                            //---if month is 3 characters long
		mm        = numMonthArray[mm.toLowerCase()]         //---swap to month number
	}
	if(String(yy).length  != 4){                            //---if year is 4 characters long
		yy        = 2000+Number(yy)                         //---swap to year number
	}

	if(Number(dd) > 31){
		alert('Invalid Date..');
		return '';
	}
	if(Number(dd) > 30 && (Number(mm)==2||Number(mm)==4||Number(mm)==6||Number(mm)==9||Number(mm)==11)){
		alert('Invalid Date..');
		return '';
	}
// 14/01/09 - Edited by Jeff, Nick, Michael, and everyone else --> 29/1/0x returns an invalid date
	if(Number(dd) == 29 && Number(mm)==2){
		divYear = String(Number(yy)/4).split('.');
		if(Number(yy)/4!=Number(divYear[0])){
			alert('Invalid Date..');
			return '';
		}
		divYear = String(Number(yy)/100).split('.');
		if(Number(yy)/100==Number(divYear[0]) && Number(yy) != 1600  && Number(yy) != 2000  && Number(yy) != 2400 ){
			alert('Invalid Date..');
			return '';
		}

	}
	d.setDate(1);
	d.setFullYear(Number(yy));
	d.setMonth(Number(mm)-1); //--month numbers start at 0 (11 = december)
	d.setDate(Number(dd));

	if(d.getDate()!=Number(dd) || d.getMonth()!=Number(mm)-1 || d.getFullYear()!=Number(yy)){
		alert('Invalid Date..');
		return '';
	}

	strDay           = strTwoChr[Number(dd)];       //---convert to 2 characters
	if(fmm.length    == 3){
		strMth       = strMonthArray[Number(mm)];   //---convert to 3 characters
	}else{
		strMth       = strTwoChr[Number(mm)];       //---convert to 2 characters
	}
	if(fyy.length    == 4){
		strYr        = yy;                  //---convert to 4 characters
	}else{
		strYr        = String(yy).substr(2);        //---convert to 2 characters
	}

	if(US){ //---is USA date
		return strMth+'-'+strDay+'-'+strYr;
	}else{
		return strDay+'-'+strMth+'-'+strYr;
	}

}



//=========cookie functions===========================

function nuCreateCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function nuReadCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function nuEraseCookie(name) {
	nuCreateCookie(name,"",-1);
}

//=========end of cookie functions====================





