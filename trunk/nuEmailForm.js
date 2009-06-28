/*
** File:           nuEmailForm.js
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

//display the email form, where the user inputs the email information
//then the email is sent, with the report attached in a file
var nuEmailTimeOutId = 0;
function emailFormBuild(pReport, pTo, pFrom, pSubject, pMessage, pFilename, pResponse, pType){
	var pagew = 1042;
	var pageh = 1024;
	toggleModalMode(); //function in nuCalendar.js
	//set up the emailer div
	var cid = document.createElement('div');
	cid.id = 'emailformdiv';
	document.body.appendChild(cid);
	cid.style.border = '2px solid black';
	cid.style.position = "absolute";
	cid.style.top = "20%";
	cid.style.left = ((pagew/2) - 370) + "";
	cid.style.width = "640px";
	cid.style.height = "270px";
	cid.style.visibility = "visible";
	cid.style.backgroundColor = "#cccccc";
	cid.style.color = "#000000";
	cid.align = 'center';
	cid.innerHTML = '<font face="Arial">Enter Email Information';
	
	//set up the form
//	var fid = document.createElement('form');
//	fid.id = 'emailformform';
//	cid.appendChild(fid);
	//add a table to the form to align form elements
	var tab = document.createElement('table');
	tab.id = 'emailformtable';
	tab.style.color = '#000000';
	tab.style.backgroundColor = "#cccccc";
	cid.appendChild(tab);
	//details about the to email address
	if(typeof(pTo) == 'undefined' || pTo == ''){
		addRowToEmailForm(tab, 'mailto', 'To', 1,'',0,null,false);
	}else{
		addRowToEmailForm(tab, 'mailto', 'To', 5,pTo,0,null,false); //5
	}
// 2009/05/29 - Michael
	document.getElementById('mailto_input').focus();
	//details about the from email address
	if(typeof(pFrom) == 'undefined' || pFrom == ''){
		addRowToEmailForm(tab, 'mailfrom', 'From', 1,'',1,null,false);
	}else{
		addRowToEmailForm(tab, 'mailfrom', 'From', 4,pFrom,1,null,false); //4
	}
	//subject text
	if(typeof(pSubject) == 'undefined' || pSubject == ''){
		addRowToEmailForm(tab, 'subject', 'Subject', 1,'Report '+pReport,2,null,false);
	}else{
		addRowToEmailForm(tab, 'subject', 'Subject', 4,pSubject,2,null,false); //4
	}
	//message text
	if(typeof(pMessage) == 'undefined' || pMessage == ''){
		addRowToEmailForm(tab, 'message', 'Message', 2,'',3,null,false);
	}else{
		addRowToEmailForm(tab, 'message', 'Message', 5,pMessage,3,null,false); //5
	}
	//filename
	if(typeof(pFilename) == 'undefined' || pFilename == ''){
		addRowToEmailForm(tab, 'filename', 'Filename For Attached Report', 1,'Report_'+pReport+'.'+pType,4,null,false);
	}else{
		addRowToEmailForm(tab, 'filename', 'Filename For Attached Report', 4,pFilename,4,null,false); //4
	}
	addRowToEmailForm(tab, 'type', 'Filetype', 4, pType,5,null,true);	
	//display the report ID
	addRowToEmailForm(tab, 'report', 'Report ID', 4,pReport,6,null,true);
	//dropdown to select whether the browser will pop up saying if the email was sent
	var resp = new Array(2);
	resp[0] = 'No';
	resp[1] = 'Yes';
	addRowToEmailForm(tab, 'response', 'Send Read Receipt', 6,pResponse,7,resp,false);
	//add in the send/cancel email buttons
	addRowToEmailForm(tab, 'submit', '', 3,'',8,null,false);
	addRowToEmailForm(tab, 'loading', 'Please Wait...', 4,'The Email Is Sending.',8,null,true);
	addRowToEmailForm(tab, 'done', 'Email Successfully Sent',7,'',9,null,true);
	addRowToEmailForm(tab, 'error', 'Error!',4,'<THIS TEXT IS REPLACED DYNAMICALLY>',10,null,true);
}

//adds a field row to the form table
function addRowToEmailForm(tableref, id, label, inputType, defaultval, pos, selectoptions, hide){
	//set up the new row
	var row = tableref.insertRow(pos);
	row.id = id + '_row';
	
	//build the left side
	var cellL = row.insertCell(0);
	cellL.id = id + '_cellL';
	cellL.style.width = '150px';
	cellL.style.backgroundColor = "#cccccc";
	if(inputType == 3){
			
	}else{
		cellL.innerHTML = label;
	}
	
	//build the right side
	if(inputType == 1){ // 1 - input
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.style.margin = 0;
		var content = document.createElement('input');
		content.id = id + '_input';
		content.value = defaultval;
		content.style.width = '400px';
		cellR.appendChild(content);
	}else if(inputType == 2){ // 2 - textarea
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.style.margin = 0;
		var content = document.createElement('textarea');
		content.id = id + '_input';
		content.value = defaultval;
		content.rows = 3;
		content.style.width = '400px';
		cellR.appendChild(content);
	}else if(inputType == 3){ // 3 - submit button
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.align = 'right';
		cellR.style.margin = 0;
		var contentL = document.createElement('button');
		contentL.id = id + '_submit';
		contentL.innerHTML = 'Send Email';
		contentL.style.width = '100px';
		contentL.style.height = '20px';
		contentL.onclick = function(){emailFormOk()};
		cellR.appendChild(contentL);
		var content = document.createElement('button');
		content.id = id + '_cancel';
		content.innerHTML = 'Cancel';
		content.style.width = '100px';
		content.style.height = '20px';
		content.onclick = function(){emailFormCancel()};
		cellR.appendChild(content);
	}else if(inputType == 4){ // 4 - non-editable input
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.style.margin = 0;
		var content = document.createElement('input');
		content.id = id + '_input';
		content.value = defaultval;
		content.style.width = '400px';
		content.style.backgroundColor = "#cccccc";
		content.style.border = 0;
		cellR.style.margin = 0;
		content.readOnly = 'true';
		cellR.appendChild(content);
	}else if(inputType == 5){ // 5 - non-editable textbox
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.style.margin = 0;
		var content = document.createElement('textarea');
		content.id = id + '_input';
		content.value = defaultval;
		content.style.width = '400px';
		content.readOnly = 'true';
		cellR.appendChild(content);
	}else if(inputType == 6){ //dropdown - pass an array of options to the function too
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.style.margin = 0;
		var content = document.createElement('select');
		content.id = id + '_input';
		for(i = 0; i < selectoptions.length; i++){
			var newopt = document.createElement('option');
			newopt.value = selectoptions[i];
			newopt.text = selectoptions[i];
			try{	
				content.add(newopt,null); //standards
			}catch(ex){
				content.add(newopt); //IE workaround
			}
		}
		content.style.width = '100px';
		cellR.appendChild(content);
	}else if(inputType == 7){ // OK button dialogue for successful sending of emails
		var cellR = row.insertCell(1);
		cellR.id = id + '_cellR';
		cellR.style.backgroundColor = "#cccccc";
		cellR.style.margin = 0;
		var content = document.createElement('button');
		content.id = id + '_ok';
		content.innerHTML = 'OK';
		content.style.width = '100px';
		content.style.height = '20px';
		content.onclick = function(){emailFormCancel()};
		cellR.appendChild(content);
	}
	
	if(hide){
		row.style.display = 'none';
	}
}

//check the key form information
function emailFormCheck(){
	//check to addresses
	var addressTo = document.getElementById('mailto_input').value;
	if(typeof(addressTo) == 'undefined' || addressTo == ''){
		alert('please input an email address to send to');
		return false;
	}
	
	//appply regex to the to email address.

// 2009/06/02 - Michael - this regex now supports only one email address.
// TODO: This should support a comma seperated list. Maybe use: (...[ ,]*){1,}
	if(/^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}[, ]*){1,}$/.test(addressTo)){
	
	}else{
		alert('please input valid email address(es) to send to');
		return false;
	}
	
	//check from address
	var addressFrom = document.getElementById('mailfrom_input').value;
	if(typeof(addressFrom) == 'undefined' || addressFrom == ''){
		alert('please input an email address to send from');
		return false;
	}
	
	//apply regex to the from email address
// 2009/06/02 - Michael - this regex now supports only one email address.
	if(/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/.test(addressFrom)){
	
	}else{
		alert('please input a valid email address to send from');
//		document.getElementById('mailfrom_input').readOnly = 'false';
		return false;
	}
	
	//check filename
	var file = document.getElementById('filename_input').value;
	if(typeof(file) == 'undefined' || file == ''){
		alert('please input a filename');
		return false;
	}
	return true;
}

//send the email
//
function emailFormOk(){
	if(emailFormCheck()){ //first check the information
		//assemble the information for sending
		var rReportID = document.getElementById('report_input').value;
		//get the list of addresses to send to, and split them into an array
		var rAddressTo = document.getElementById('mailto_input').value;
		rAddressTo = rAddressTo.replace(new RegExp(' ','gi'),''); //replace whitespace with nothing
		//get the from address
		var rAddressFrom = document.getElementById('mailfrom_input').value;
		//subject line
		var rSubject = document.getElementById('subject_input').value;
		//split the message into separate lines,
		//add <br> tags to the end of each line,
		//then reform the message line
		var lines;
		var rMessage = '';
		var TA=document.getElementById('message_input').value;
		if(document.all) { // IE
			lines=TA.split("\r\n");
		}else{ //Mozilla
			lines=TA.split("\n");
		}
		if(!lines[0] == ''){
			for(var i=0; i<lines.length; i++) {
				rMessage += lines[i] + '<br>';
			}
		}
		var rFilename = stripFileExtension(document.getElementById('filename_input').value);
		var rResponse;
		var res = document.getElementById('response_input');
		for(i = 0; i < res.length; i++){
			if(res.options[i].selected){
				rResponse = res.options[i].value;
				break;
			}
		}
		if(rResponse == 'Yes'){
			rResponse = true;
		}else{
			rResponse = false;
		}
		var rType = document.getElementById('type_input').value;
		//alert( rReportID +'|'+ rAddressTo +'|'+ rAddressFrom +'|'+ rSubject +'|'+ rMessage +'|'+ rFilename + '|' + rResponse + '|' + rType);
		emailSendIt(rReportID,rAddressTo,rAddressFrom,rSubject,rMessage,rFilename,true,rType,rResponse);
		//emailSendIt(pReportID,pTo,pFrom,pSubject,pMessage,pFilename,pAlert,pType,pResponse)
		document.getElementById('submit_row').style.display = 'none';
		document.getElementById('loading_row').style.display = '';
		//emailFormDestroy();
	}
}

function nuMailJax(pURL) {
//-- pass url to run


	//alert(pURL);
   var d                                                  = new Date();
   theID                                                  = 'a' + String(d.getTime()) + 'a';
   newObj                                                 = document.createElement('div');
   newObj.setAttribute('id', 'div_'+theID);
   newObj.innerHTML                                       = "<iframe src='' id='" + theID + "' />";
   document.body.appendChild(newObj);
   document.getElementById(theID).style.position          = 'absolute';
   document.getElementById(theID).style.height            = '300';
   document.getElementById(theID).style.width             = '300';
   document.getElementById(theID).style.backgroundColor   = 'red';
   document.getElementById(theID).style.overflow          = 'hidden';
   document.getElementById(theID).style.top               = '100';
   document.getElementById(theID).style.left              = '100';
   document.getElementById(theID).style.visibility        = 'hidden';
   document.getElementById(theID).src                     = pURL;
}

function startEmailTimeOut(){
	nuEmailTimeOutId = setTimeout('emailSendResponse()',20000);
}

function stopEmailTimeOut(){
	clearTimeout(nuEmailTimeOutId);
}

function stripFileExtension(inputString){
	inputString = inputString.replace(new RegExp('.html','gi'),'');
	inputString = inputString.replace(new RegExp('.pdf','gi'),'');
	return inputString;
}

function emailSuccess(){
	stopEmailTimeOut();
	document.getElementById('submit_row').style.display = 'none';
	document.getElementById('error_row').style.display = 'none';
	document.getElementById('loading_row').style.display = 'none';
	document.getElementById('done_row').style.display = '';
}

// 2009/05/29 - Michael - Added errorString parameter.
function emailFailure(errorString){
	stopEmailTimeOut();
	document.getElementById('submit_row').style.display = '';
	document.getElementById('error_row').style.display = '';
// 2009/05/29 - Michael
	document.getElementById('error_input').value = errorString;
	document.getElementById('loading_row').style.display = 'none';
	document.getElementById('done_row').style.display = 'none';
}

//cancel sending the email
function emailFormCancel(){
	emailFormDestroy();
}

//destroy the form after use
function emailFormDestroy(){
	document.body.removeChild(document.getElementById('emailformdiv'));
	toggleModalMode(); //function in nuCalendar.js
}
