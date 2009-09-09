<?php
/*
** File:           sqlbackup.php
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

$sd                               = $_GET['sd'];
header("Content-disposition: attachment;filename=$sd.sql");
include('../' . $_GET['sd'] . '/database.php');
include('common.php');


//--
//		$return[0]=$DBHost;
//		$return[1]=$DBName;
//		$return[2]=$DBUserID;
//		$return[3]=$DBPassWord;
//		return $return;
//

$dbInfo = nuRunQuery('');

mysqlbackup($dbInfo[0],$dbInfo[1],$dbInfo[2],$dbInfo[3], '', false) ;

function mysqlbackup($host,$dbname, $uid, $pwd, $output, $structure_only) {

    //this function creates a text file (or output to a HTTP connection), that when parsed through MYSQL's telnet client, will re-create the entire database 

    //Parameters: 
    //    $host: usually "localhost" but depends on where the MySQL database engine is mounted 
    //    $dbname : The MySQL database name 
    //    $uid : the database's username (not your account's), leave blank if none is required 
    //    $pwd : the database's password 
    //    $output : this is the complete filespec for the output text file, or if you want the result SQL to be sent back to the browser, leave blank. 
    //    $structure_only : set this to true if you want just the schema of the database (not the actual data) to be output. 

    // ************** 
    // IMPORTANT: If you use this function, for personal or commercial use, AND you feel an overwhelming sense of gratitude that someone actually took the time and wrote it, 
    // immediately go to your paypal account and send me $10 with a small comment of how and how much it helped! Set the payment recipient to woodystanford@yahoo.com . 
    // ************** 

    if (strval($output)!="") $fptr=fopen($output,"w"); else $fptr=false; 

    //connect to MySQL database 
    $con=mysql_connect($host,$uid, $pwd); 
    $db=mysql_select_db($dbname,$con); 

    //open back-up file ( or no file for browser output) 

    //set up database 
    out($fptr, "create database $dbname;\n\n"); 

    //enumerate tables 
    $res=mysql_list_tables($dbname); 
    $nt=mysql_num_rows($res); 

    for ($a=0;$a<$nt;$a++) 
    { 
        $row=mysql_fetch_row($res); 
        $tablename=$row[0]; 

        //start building the table creation query 
        $sql="create table $tablename\n(\n"; 

        $res2=nuRunQuery("SELECT * FROM $tablename "); 
        $nf=mysql_num_fields($res2); 
        $nr=mysql_num_rows($res2); 

        $fl=""; 

        //parse the field info first 
        for ($b=0;$b<$nf;$b++) 
        { 
            $fn=mysql_field_name($res2,$b); 
            $ft=mysql_fieldtype($res2,$b); 
            $fs=mysql_field_len($res2,$b); 
            $ff=mysql_field_flags($res2,$b); 

            $sql.="    $fn "; 

            $is_numeric=false; 
            switch(strtolower($ft)) 
            { 
                case "int": 
                    $sql.="int"; 
                    $is_numeric=true; 
                    break; 

                case "blob": 
                    $sql.="text"; 
                    $is_numeric=false; 
                    break; 

                case "real": 
                    $sql.="real"; 
                    $is_numeric=true; 
                    break; 

                case "string": 
                    $sql.="char($fs)"; 
                    $is_numeric=false; 
                    break; 

                case "unknown": 
                    switch(intval($fs)) 
                    { 
                        case 4:    //little weakness here...there is no way (thru the PHP/MySQL interface) to tell the difference between a tinyint and a year field type 
                            $sql.="tinyint"; 
                            $is_numeric=true; 
                            break; 

                        default:    //we could get a little more optimzation here! (i.e. check for medium ints, etc.) 
                            $sql.="int"; 
                            $is_numeric=true; 
                            break;   
                    } 
                    break; 

                case "timestamp": 
                    $sql.="timestamp";   
                    $is_numeric=true; 
                    break; 

                case "date": 
                    $sql.="date";   
                    $is_numeric=false; 
                    break; 

                case "datetime": 
                    $sql.="datetime";   
                    $is_numeric=false; 
                    break; 

                case "time": 
                    $sql.="time";   
                    $is_numeric=false; 
                    break; 

                default: //future support for field types that are not recognized (hopefully this will work without need for future modification) 
                    $sql.=$ft; 
                    $is_numeric=true; //I'm assuming new field types will follow SQL numeric syntax..this is where this support will breakdown 
                    break; 
            } 

            //VERY, VERY IMPORTANT!!! Don't forget to append the flags onto the end of the field creator 

            if (strpos($ff,"unsigned")!=false) 
            { 
                //timestamps are a little screwy so we test for them 
                if ($ft!="timestamp") $sql.=" unsigned"; 
            } 

            if (strpos($ff,"zerofill")!=false) 
            { 
                //timestamps are a little screwy so we test for them 
                if ($ft!="timestamp") $sql.=" zerofill"; 
            } 

            if (strpos($ff,"auto_increment")!=false) $sql.=" auto_increment"; 
            if (strpos($ff,"not_null")!=false) $sql.=" not null"; 
            if (strpos($ff,"primary_key")!=false) $sql.=" primary key"; 

            //End of field flags 

            if ($b<$nf-1) 
            { 
                $sql.=",\n"; 
                $fl.=$fn.", "; 
            } 
            else 
            { 
                $sql.="\n);\n\n"; 
                $fl.=$fn; 
            } 

            //we need some of the info generated in this loop later in the algorythm...save what we need to arrays 
            $fna[$b]=$fn; 
            $ina[$b]=$is_numeric; 
              
        } 

        out($fptr,$sql); 

        if ($structure_only!=true) 
        { 
            //parse out the table's data and generate the SQL INSERT statements in order to replicate the data itself... 
            for ($c=0;$c<$nr;$c++) 
            { 
                $sql="insert into $tablename ($fl) values ("; 

                $row=mysql_fetch_row($res2); 

                for ($d=0;$d<$nf;$d++) 
                { 
                    $data=strval($row[$d]); 
                  
                    if ($ina[$d]==true) 
                        $sql.= intval($data); 
                    else 
                        $sql.="\"".mysql_escape_string($data)."\""; 

                    if ($d<($nf-1)) $sql.=", "; 
      
                } 

                $sql.=");\n"; 

                out($fptr,$sql); 

            } 

            out($fptr,"\n\n"); 

        } 

        mysql_free_result($res2);      

    } 
      
    if ($fptr!=false) fclose($fptr); 
    return 0; 

} 

function out($fptr,$s) 
{ 
    if ($fptr==false) echo("$s"); else fputs($fptr,$s); 
} 

?>
