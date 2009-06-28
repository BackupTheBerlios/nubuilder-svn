/*
** File:           nuCalendar.js
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

var cellCol = "#ffffff"; 		//cell colour
var cellColHov = "#aaaaaa"; 	//cell colour on mouse hover
var cellColNON = "#cccccc"; 	//cell color for non-button cells
var cellColMod = "#000000";		//colour for the modal div
var currentDate; 				//today's date
var displayDate = new Date(); 	//date object used to display a month in the calendar.
								//Calling a calendar refresh will redraw the month of the year stored in display date (make sure that the Day of the month is 1 before a refresh)
var idprefix = "cal_";
	
//makes the button with specified coordinates, width, and text. when the button is clicked, calendarBuild() is called, and a calendar will pop up.
function calendarButton(xpos,ypos,width,text,targetid){
	var thisHTML = "<input type=\"button\" value=\""+text+"\" style=\"width:"+width+";\" name=\"calendar\" id=\"calendarButton\"  onclick=\"calendarBuild(\'"+targetid+"\')\"/>";
	
	var id = addAppendChild(document.body,'div',idprefix+'button_' + targetid);
	id.style.position = 'absolute';
	id.style.top = ypos;
	id.style.left = xpos;
	id.style.visibility = 'visible';
	id.innerHTML = thisHTML;
}
		
//builds calendar and its elements
function calendarBuild(targetid){
	displayDate = new Date();  //-- added by sc 13-3-09
	displayDate.setDate(1);    //-- added by sc 13-3-09
	currentDate = new Date();
	document.body.onkeydown = function(){return false;}
	var pagew = 1042;
	var pageh = getWidthOrHeight(0);
	
	//turn on modal div
	toggleModalMode();
	
	//append the calendar div on top
	var cid = addAppendChild(document.body,'div',idprefix+'div');
	cid.style.border = '2px solid black';
	cid.style.position = "absolute";
	cid.style.top = "20%";
	cid.style.left = ((pagew/2) - 100) + "";
	cid.style.width = "200";
	cid.style.height = "240px";
	cid.style.visibility = "visible";
	cid.style.backgroundColor = cellColNON;
	cid.style.color = "#000000";
				
	//append the calendar table onto the calendarDiv
	var caltable = document.createElement('table');
	caltable.setAttribute('id', idprefix+'table');
	cid.appendChild(caltable);
	var tid = document.getElementById(idprefix+'table');
	tid.align = "center";
	tid.style.color = "#000000";
	tid.value = targetid;
	
	//makes the title row containing S M T W T F S
	var currentrow = addRow(caltable,idprefix+'titlerow', 0);
	addCell(currentrow, 0, "S", "Su", 20, "black", cellColNON, 1, 0, 0, 0);
	addCell(currentrow, 1, "M", "Mo", 20, "black", cellColNON, 1, 0, 0, 0);
	addCell(currentrow, 2, "T", "Tu", 20, "black", cellColNON, 1, 0, 0, 0);
	addCell(currentrow, 3, "W", "We", 20, "black", cellColNON, 1, 0, 0, 0);
	addCell(currentrow, 4, "T", "Th", 20, "black", cellColNON, 1, 0, 0, 0);
	addCell(currentrow, 5, "F", "Fr", 20, "black", cellColNON, 1, 0, 0, 0);
	addCell(currentrow, 6, "S", "Sa", 20, "black", cellColNON, 1, 0, 0, 0);

	//makes the rows and cells containing days
	var row = 0;
	var col = 0;			
	for(row = 0; row < 6; row ++){
		currentrow = addRow(caltable, idprefix+'row_' + row, row + 1);
		for(col = 0; col < 7; col ++){
			addCell(currentrow,col,"31", idprefix+row+","+col, 20, "black", cellCol, 1, 1, 1, 1);
		}
	}
			
	//makes the navigation buttons to skip forward/back a month/year
	currentrow = addRow(caltable, idprefix+'row_7', 7);
	addCell(currentrow, 0, "mth--", idprefix+"6,0", 20, "black", cellCol, 2, 2, 1, 1);
	addCell(currentrow, 1, "mth", idprefix+"6,1", 20, "black", cellColNON, 3, 0, 0, 0);
	addCell(currentrow, 2, "mth++", idprefix+"6,2", 20, "black", cellCol, 2, 3, 1, 1);
	currentrow = addRow(caltable, idprefix+'row_8', 8);
	addCell(currentrow, 0, "yr--", idprefix+"7,0", 20, "black", cellCol, 2, 4, 1, 1);
	addCell(currentrow, 1, "yr", idprefix+"7,1", 20, "black", cellColNON, 3, 0, 0, 0);
	addCell(currentrow, 2, "yr++", idprefix+"7,2", 20, "black", cellCol, 2, 5, 1, 1);
	currentrow = addRow(caltable, idprefix+'row_9', 9);
	addCell(currentrow, 0, "cancel", idprefix+"8,0",20, "black", cellCol, 7, 6, 1, 1);
			
	//refresh the table to display correct information
	refreshCal();
}
		
//adds a cell to the specified row with with a specified index, width, id, string, colour, span, and flags for what interaction functions the cell will use
function addCell(row,position,displaystr,idstr,width,color,bgcolor,span,mouseclick,mouseover,mouseout){
	var celid = idstr + "_cell";
	var celref = row.insertCell(position);
	celref.setAttribute('id',celid);
	document.getElementById(celid).style.width = width;
	document.getElementById(celid).align = "center";
	document.getElementById(celid).colSpan = span;
	document.getElementById(celid).innerHTML = displaystr;
	//alert(displaystr);			
	var thisHTML = "<div id=\""+idstr+"\" style=\"color:#000000;background-color:"+bgcolor+";width:"+(width*span)+";height:"+width+";left:0;top:0;\"";
	if(mouseclick == 1){thisHTML += "onclick=\"clickedDay(this)\"";}
	if(mouseclick == 2){thisHTML += "onclick=\"clickedPrevMonth(this)\"";}
	if(mouseclick == 3){thisHTML += "onclick=\"clickedNextMonth(this)\"";}
	if(mouseclick == 4){thisHTML += "onclick=\"clickedPrevYear(this)\"";}
	if(mouseclick == 5){thisHTML += "onclick=\"clickedNextYear(this)\"";}
	if(mouseclick == 6){thisHTML += "onclick=\"calendarDestroy()\"";}
	if(mouseover == 1){thisHTML += "onmouseover=\"calMIN(this)\"";}
	if(mouseout == 1){thisHTML += "onmouseout=\"calMOUT(this)\"";}
	thisHTML += ">"+displaystr+"</div>";
	document.getElementById(celid).innerHTML = thisHTML;
}
		
//adds a row to the specified table with an id and index
function addRow(table,idstr,position){
	var rowref = table.insertRow(position);
	rowref.setAttribute('id',idstr);
	return document.getElementById(idstr);
}
		
//appends a child obect onto specified node, with an element type, and id
function addAppendChild(destination,typestr,idstr){
	var newObj = document.createElement(typestr);
	newObj.setAttribute('id',idstr);
	destination.appendChild(newObj);
	return document.getElementById(idstr);
}
		
//remove all calendar elements from the screen. child nodes also get destroyed at the same time as their parent nodes
function calendarDestroy(){
	document.body.removeChild(document.getElementById(idprefix+"div"));
	toggleModalMode();
	document.body.onkeydown = "";
}
		
function calMIN(pid){	//Called when the mouse moves over an element
	document.getElementById(pid.id).style.backgroundColor=cellColHov;
	return;
}
			
function calMOUT(pid){	//Called when the mouse moves off an element
	document.getElementById(pid.id).style.backgroundColor=cellCol;
	return;
}
		
//refreshes the calendar to display correct information. called when the user clicks a navigation button
function refreshCal(){
	//fill the calendar with "."
	var x = 0;
	var y = 0;
	for(x = 0; x < 6; x += 1){
		for(y = 0; y < 7; y += 1){
			var currentid = idprefix + x+","+y;
			document.getElementById(currentid).innerHTML=".";
		}
	}
	//fill the relevant elements in the calendar with the correct day numbers
	//in the case of a day in the calendar being today, colour the text red
	var max = getNumberOfDays(displayDate.getMonth(),displayDate.getFullYear()) + 1;
	var count = displayDate.getDay();
	var no = 1;
	while(no < max && count < 42){
		var idcur = idprefix+Math.floor(count/7)+","+count%7;
		if((displayDate.getFullYear()==currentDate.getFullYear())&&(displayDate.getMonth()==currentDate.getMonth())&&(no==currentDate.getDate())){
			document.getElementById(idcur).style.color="#ff0000";	//mark today as red
		}else{
			document.getElementById(idcur).style.color="#000000";	//otherwise normal black
		}
		document.getElementById(idcur).innerHTML=no; 				//the element now contains a day
		no = no + 1;
		count = count + 1;
	}
	document.getElementById(idprefix+"6,0").innerHTML=monthStr(displayDate.getMonth()-1);	//fill the buttons at the bottom of the calendar with the relevant information
	document.getElementById(idprefix+"6,1").innerHTML=monthStr(displayDate.getMonth());	
	document.getElementById(idprefix+"6,2").innerHTML=monthStr(displayDate.getMonth()+1);	
	document.getElementById(idprefix+"7,0").innerHTML=displayDate.getFullYear()-1;		
	document.getElementById(idprefix+"7,1").innerHTML=displayDate.getFullYear();			
	document.getElementById(idprefix+"7,2").innerHTML=displayDate.getFullYear()+1;		
}
		
//executes when a day-cell is clicked.
function clickedDay(pthis){
	if(document.getElementById(pthis.id).innerHTML=="."){ return;}
		//code to send
	var returnYear = displayDate.getFullYear();
	var returnMonth = displayDate.getMonth();
	var returnDay = document.getElementById(pthis.id).innerHTML;
		//goes here
	var targetid = document.getElementById(idprefix + 'table').value;
	var formatted = formattedDateStr(aFormat[parseInt(document.getElementById(targetid).accept)], returnYear, returnMonth, returnDay);
	document.getElementById(targetid).value = formatted;
//	document.getElementById(targetid).value = formatted;
	//alert("You have selected: " + formatted);
	uDB(document.getElementById(targetid),'text')
	calendarDestroy(); //day has been selected, so get rid of the calendar and let them continue
}
		
//navigates to the next month
function clickedNextMonth(pthis){
	displayDate.setFullYear(displayDate.getFullYear(),displayDate.getMonth()+1,1);
	refreshCal();
}
		
//navigates to the next year
function clickedNextYear(pthis){
	displayDate.setFullYear(displayDate.getFullYear()+1,displayDate.getMonth(),1);
	refreshCal();
}

//navigates to the previous month
function clickedPrevMonth(pthis){
	displayDate.setFullYear(displayDate.getFullYear(),displayDate.getMonth()-1,1);
	refreshCal();
}
		
//navigates to the previous year
function clickedPrevYear(pthis){
	displayDate.setFullYear(displayDate.getFullYear()-1,displayDate.getMonth(),1);
	refreshCal();
}
		
//returns a string corresponding to the month
function monthStr(m){
	m = m%12;
	switch(m){
		case 0: return "Jan"; //J
		case 1: return "Feb"; //F
		case 2: return "Mar"; //M
		case 3: return "Apr"; //A
		case 4: return "May"; //M
		case 5: return "Jun"; //J
		case 6: return "Jul"; //J
		case 7: return "Aug"; //A
		case 8: return "Sep"; //S
		case 9: return "Oct"; //O
		case 10: return "Nov"; //N
		default: return "Dec"; //D
	}
}
		
//returns the number of days in the specified month of the year
function getNumberOfDays(m,y){
	if((y%4 == 0)&&(m==1)){
		return 29; //february on a leap year
	}
	switch(m){
		case 0: return 31; //J
		case 1: return 28; //F
		case 2: return 31; //M
		case 3: return 30; //A
		case 4: return 31; //M
		case 5: return 30; //J
		case 6: return 31; //J
		case 7: return 31; //A
		case 8: return 30; //S
		case 9: return 31; //O
		case 10: return 30; //N
		default: return 31; //D
	}
}
		
//returns a date as a string in the desird format
function formattedDateStr(format, year, month, day){
	var returnStr = "";
	day += "";
	if(day.length < 2){
		day = "0" + day;
	}
	year += "";
	var yearshort = year.charAt(2) + year.charAt(3);
	if(format == 'dd-mmm-yyyy'){
		returnStr += day + "-" + monthStr(month) + "-" + year;
	}else if(format == 'dd-mm-yyyy'){
		returnStr += day + "-" + (month+1) + "-" + year;
	}else if(format == 'mmm-dd-yyyy'){
		returnStr += monthStr(month)+ "-"+ day + "-" + year;
	}else if(format == 'mm-dd-yyyy'){
		returnStr += (month+1) + "-" + day + "-" + year;
	}else if(format == 'dd-mmm-yy'){
		returnStr += day + "-" + monthStr(month) + "-" + yearshort;
	}else if(format == 'dd-mm-yy'){
		returnStr += day + "-" + (month+1) + "-" + yearshort;
	}else if(format == 'mmm-dd-yy'){
		returnStr += monthStr(month)+ "-"+ day + "-" + yearshort;
	}else if(format == 'mm-dd-yy'){
		returnStr += (month) + "-" + day + "-" + yearshort;
	}
	return returnStr;
}
		
//returns the page width or height. width if choice != 0, height otherwise
function getWidthOrHeight(option){
	var myW;
	var myH;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myW = window.innerWidth;
		myH = window.innerHeight;
	}else{
		//IE 6+ in 'standards compliant mode'
		myW = document.documentElement.clientWidth;
		myH = document.documentElement.clientHeight;
	}
	if(option){
		return myW;
	}else{
		return myH;
	}
}
		
//call this function to make/destroy a fullscreen div that blocks the user's mouseclicks from things beneath
function toggleModalMode(){
	try{
		document.body.removeChild(document.getElementById("modal_div"));
	}catch(e){
		var mid = addAppendChild(document.body,'div', 'modal_div');
		mid.style.position = "absolute";
		mid.style.top = "0";
		mid.style.left = "0";
		mid.style.width = "110%";
		mid.style.height = "100%";
		mid.style.visibility = "visible";
		mid.style.filter = "Alpha(Opacity=20)";
		mid.style.opacity = 0.2;
		mid.style.backgroundColor = "#000000";
	}
}
