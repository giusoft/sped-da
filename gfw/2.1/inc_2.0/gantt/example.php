<?php

/*¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
gantt php class example and configuration file
this example shows a full example with all resources
and dependencies
version 0.1
Copyright (C) 2005 Alexandre Miguel de Andrade Souza

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either
version 2 of the License.
Please see the accompanying file COPYING for licensing details!

If you need a commercial license of this class to your project, please contact
alexandremasbr@gmail.com
¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*/
include 'gantt.class.php';

//THIS START STANDARD DEFINITIONS TO CLASS, YOU DONT NEED TO CHANGE THIS SETTINGS, ONLY IF YOU WANT
//generic  definitions to graphic, you dont need to change this. Only if you want
$definitions['title_y'] = 10; // absolute vertical position in pixels -> title string
$definitions['planned']['y'] = 6; // relative vertical position in pixels -> planned/baseline
$definitions['planned']['height']= 8; // height in pixels -> planned/baseline
$definitions['planned_adjusted']['y'] = 25; // relative vertical position in pixels -> adjusted planning
$definitions['planned_adjusted']['height']= 8; // height in pixels -> adjusted planning
$definitions['real']['y']=26; // relative vertical position in pixels -> real/realized time 
$definitions['real']['height']=5; // height in pixels -> real/realized time 
$definitions['progress']['y']=11; // relative vertical position in pixels -> progress
$definitions['progress']['height']=2; // height in pixels -> progress 
$definitions['img_bg_color'] = array(222, 229, 255); //color of background
$definitions['title_color'] = array(255, 255, 255); //color of title
$definitions['text']['color'] = array(0, 0, 0); //color of title
$definitions['title_bg_color'] = array(113, 155, 240); //color of background of title
$definitions['milestone']['title_bg_color'] = array(204, 204, 230); //color of background of title of milestone
$definitions['today']['color']=array(254, 54, 50); //color of today line
$definitions['status_report']['color']=array(113, 155, 240); //color of last status report line
$definitions['real']['hachured_color']=array(0,204, 0);// color of hachured of real. to not have hachured, set to same color of real
$definitions['workday_color'] = array(255, 255, 255); //white -> default color of the grid to workdays
$definitions['grid_color'] = array(218, 218, 218); //default color of weekend days in the grid
$definitions['groups']['color'] = array(0, 0, 0);// set color of groups
$definitions['groups']['bg_color'] = array(180,180, 180);// set color of background to groups title
$definitions['planned']['color']=array(255, 143, 4);// set color of initial planning/baseline
$definitions['planned_adjusted']['color']=array(0, 0, 204); // set color of adjusted planning
$definitions['real']['color']=array(255, 255,255);//set color of work done
$definitions['progress']['color']=array(0,255,0); // set color of progress/percentage completed
$definitions['milestones']['color'] = array(254, 54, 50); //set the color to milestone icon

//if you want a ttf font set this values
// just donwload a ttf font and set the path 
// find ttf fonts at http://www.webpagepublicity.com/free-fonts.html -> more than 6500 free fonts
//$definitions['text']['ttfont']['file'] = './Arial.ttf'; // set path and filename of ttf font -> coment to use gd fonts
//$definitions['text']['ttfont']['size'] = '11'; // used only with ttf
//define font colors
//$definitions['title']['ttfont']['file'] = './ActionIs.ttf'; // set path and filename of ttf font -> coment to use gd fonts
//$definitions['title']['ttfont']['size'] = '11'; // used only with ttf

// these are default value if not set a ttf font
$definitions['text_font'] = 3; //define the font to text -> 1 to 4 (gd fonts)
$definitions['title_font'] = 3;  //define the font to title -> 1 to 4 (gd fonts)
$definitions['text']['ttfont']['file']='arial.ttf';
$definitions['text']['ttfont']['size']='9';
$definitions['title']['ttfont']['file']='arialbd.ttf';
$definitions['title']['ttfont']['size']='12';
//define font colors
$definitions["group"]['text_color'] = array(255,255,255);
$definitions["legend"]['text_color'] = array(100,100,100);
$definitions["milestone"]['text_color'] = array(204,04,104);
$definitions["phase"]['text_color'] = array(80,80,80);


// set to 1 to a continuous line
$definitions['status_report']['pixels'] = 15; //set the number of pixels to line interval
$definitions['today']['pixels'] = 10; //set the number of pixels to line interval



// set colors to dependency lines -> both  dependency planned(baseline) and dependency (adjusted planning)
$definitions['dependency_color'][END_TO_START]=array(0, 0, 0);//black
$definitions['dependency_color'][START_TO_START]=array(0, 0, 0);//black
$definitions['dependency_color'][END_TO_END]=array(0, 0, 0);//black
$definitions['dependency_color'][START_TO_END]=array(0, 0, 0);//black

//set the alpha (tranparency) to colors of bars/icons/lines
$definitions['planned']['alpha'] = 40; //transparency -> 0-100
$definitions['planned_adjusted']['alpha'] = 40; //transparency -> 0-100
$definitions['real']['alpha'] = 0; //transparency -> 0-100
$definitions['progress']['alpha'] = 0; //transparency -> 0-100
$definitions['groups']['alpha'] = 40; //transparency -> 0-100
$definitions['today']['alpha']= 80; //transparency -> 0-100
$definitions['status_report']['alpha']= 10; //transparency -> 0-100
$definitions['dependency']['alpha']= 80; //transparency -> 0-100
$definitions['milestones']['alpha']= 40; //transparency -> 0-100


// set the legends strings
$definitions['planned']['legend'] = 'Planej. inicial';
$definitions['planned_adjusted']['legend'] = 'Planej. ajustado';
$definitions['real']['legend'] = 'Realizado';
$definitions['progress']['legend'] = 'Em andamento';
$definitions['milestone']['legend'] = 'Marco';
$definitions['today']['legend'] = 'Hoje';
$definitions['status_report']['legend'] = 'Última atualização de situação';

//set the size of each day in the grid for each scale
$definitions['limit']['cell']['m'] = '4'; // size of cells (each day)
$definitions['limit']['cell']['w'] = '8'; // size of cells (each day)
$definitions['limit']['cell']['d'] = '20';// size of cells (each day)

//set the initial positions of the grid (x,y)
$definitions['grid']['x'] = 180; // initial position of the grix (x)
$definitions['grid']['y'] = 40; // initial position of the grix (y)

//set the height of each row of phases/phases -> groups and milestone rows will have half of this height
$definitions['row']['height'] = 40; // height of each row

$definitions['legend']['y'] = 85; // initial position of legent (height of image - y)
$definitions['legend']['x'] = 150; // distance between two cols of the legend
$definitions['legend']['y_'] = 35; //distance between the image bottom and legend botton
$definitions['legend']['ydiff'] = 20; //diference between lines of legend

//other settings
$definitions['progress']['bar_type']='planned'; //  if you want set progress bar on planned bar (the x point), if not set, default is on planned_adjusted bar -> you need to adjust $definitions['progress']['y'] to progress y stay over planned bar or whatever you want; 
$definitions["not_show_groups"] = false; // if set to true not show groups, but still need to set phases to a group
///
//////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////
// THIS IS THE BEGINNING OF YOUR CHART SETTINGS 
//global definitions to graphic
// change to you project data/needs
$definitions['title_string'] = "project x "; //project title
$definitions['locale'] = "pt_BR";//change to language you need -> en = english, pt_BR = Brazilian Portuguese etc 
//define the scale of the chart
$definitions['limit']['detail'] = 'w'; //w week, m month , d day

//define data information about the graphic. this limits will be adjusted in month and week scales to fit to
//start of month of start date and end of month in end date, when the scale is month
// and to start of week of start date and end of week in the end date, when the scale is week
$definitions['limit']['start'] = mktime(0,0,0,12,1,2004); //these settings will define the size of
$definitions['limit']['end'] = mktime(23,59,59,2,28,2005); //graphic and time limits

// define the data to draw a line as "today" 
$definitions['today']['data']= mktime(0,0,0,1,19,2005); //time();//draw a line in this date

// define the data to draw a line as "last status report" 
$definitions['status_report']['data']= mktime(0,0,0,1,12,2005); //time();//draw a line in this date
//
//////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////
// use loops to define these variables with database data

// you need to set groups to graphic be created
$definitions['groups']['group'][0]['name'] = "Group One";
$definitions['groups']['group'][0]['start'] = mktime(0,0,0,12,2,2004);
$definitions['groups']['group'][0]['end'] = mktime(0,0,0,2,27,2005);

// increase the number to add another group
$definitions['groups']['group'][1]['name'] = "Group Two";
$definitions['groups']['group'][1]['start'] = mktime(0,0,0,12,28,2004);
$definitions['groups']['group'][1]['end'] = mktime(0,0,0,2,27,2005);

// you need to set a group to every phase(=phase) to show it rigth
// 'group'][0] -> 0 is the number of the group to associate phases
// ['phase'][0] = 0; 0 and 0 > the same value -> is the number of the phase to associate to group
$definitions['groups']['group'][0]['phase'][0] = 0;
$definitions['groups']['group'][1]['phase'][1] = 1;

//you have to set planned phase name even when show only planned adjusted
$definitions['planned']['phase'][0]['name'] = 'task b';

//define the start and end of each phase. Set only what you want/need to show. Not defined values will not draws bars
$definitions['planned']['phase'][0]['start'] = mktime(0,0,0,12,2,2004);
$definitions['planned']['phase'][0]['end'] = mktime(0,0,0,1,14,2005);
$definitions['planned_adjusted']['phase'][0]['start'] = mktime(0,0,0,12,2,2004);
$definitions['planned_adjusted']['phase'][0]['end'] = mktime(0,0,0,1,18,2005);
$definitions['real']['phase'][0]['start'] = mktime(0,0,0,12,28,2004);
$definitions['real']['phase'][0]['end'] = mktime(0,0,0,1,14,2005);

//define a percentage/progress to phase. Set only if you want.
$definitions['progress']['phase'][0]['progress']=70;

//Example of a second phase. 
$definitions['planned']['phase'][1]['name'] = 'task xyz';
$definitions['planned']['phase'][1]['start'] = mktime(0,0,0,1,14,2005);
$definitions['planned']['phase'][1]['end'] = mktime(0,0,0,2,23,2005);
$definitions['planned_adjusted']['phase'][1]['start'] = mktime(0,0,0,1,1,2005);
$definitions['planned_adjusted']['phase'][1]['end'] = mktime(0,0,0,1,12,2005);
//$definitions['real']['phase'][1]['start'] = mktime(0,0,0,1,23,2005);
//$definitions['real']['phase'][1]['end'] = mktime(0,0,0,2,27,2005);
$definitions['progress']['phase'][1]['progress']=30;

//////////////////////////////////////////////////////////////////////////
//dependencies to planned array -> type can be END_TO_START, START_TO_START, END_TO_END and START_TO_END

$definitions['dependency_planned'][0]['type']= END_TO_START;
$definitions['dependency_planned'][0]['phase_from']=0;
$definitions['dependency_planned'][0]['phase_to']=1;

//Examples of another types of dependencies
/*
$definitions['dependency_planned'][1]['type']= START_TO_START;
$definitions['dependency_planned'][1]['phase_from']=0;
$definitions['dependency_planned'][1]['phase_to']=1;

$definitions['dependency_planned'][2]['type']= END_TO_END;
$definitions['dependency_planned'][2]['phase_from']=0;
$definitions['dependency_planned'][2]['phase_to']=1;

$definitions['dependency_planned'][3]['type']= START_TO_END;
$definitions['dependency_planned'][3]['phase_from']=0;
$definitions['dependency_planned'][3]['phase_to']=1;
*/

//////////////////////////////////////////////////////////////////////////
//dependencies to adjusted planned array -> type can be END_TO_START, START_TO_START, END_TO_END and START_TO_END

/*
$definitions['dependency'][0]['type']= END_TO_START;
$definitions['dependency'][0]['phase_from']=0;
$definitions['dependency'][0]['phase_to']=1;
 // another examples of dependencies
/*
 $definitions['dependency'][1]['type']= START_TO_START;
$definitions['dependency'][1]['phase_from']=0;
$definitions['dependency'][1]['phase_to']=1;
*/
$definitions['dependency'][2]['type']= END_TO_END;
$definitions['dependency'][2]['phase_from']=0;
$definitions['dependency'][2]['phase_to']=1;
/*
$definitions['dependency'][3]['type']= START_TO_END;
$definitions['dependency'][3]['phase_from']=0;
$definitions['dependency'][3]['phase_to']=1;
*/

///////////////////////////////////////////////////////////////////////////
// milestones are products or objectives of project. Set if you want. In this case, you need to set 
// a data, a title and a group to each milestone
$definitions['milestones']['milestone'][0]['data']= mktime(0,0,0,2,25,2005);
$definitions['milestones']['milestone'][0]['title']='MILESTONE ONE';
//define a group to milestone
$definitions['groups']['group'][0]['milestone'][0]=0; //need to set a group to show

////
/////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////
// THE END -> generate the graphic
// TO SET THE KIND OF GRAFIC GENERATED

$definitions['image']['type']= 'png'; // can be png, jpg, gif  -> if not set default is png
//$definitions['image']['filename'] = "file.ext"'; // can be set if you prefer save image as a file
$definitions['image']['jpg_quality'] = 100; // quality value for jpeg imagens -> if not set default is 100

 new gantt($definitions);



?>

