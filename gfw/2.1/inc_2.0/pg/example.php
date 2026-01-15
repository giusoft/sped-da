<html>
<head>
 <title>PowerGraphic - Examples</title>
 <style type="text/css">
 body { margin: 70px; font-family: verdana, arial; font-weight: bold; font-size: 15px; }
 </style>
</head>
<body>

<div style="position: absolute; right: 0px; top: -10px; right: 20px; z-index: -1; text-align: right; font-weight: normal;">
  <div style="font-size: 60px;">
    <span style="letter-spacing: -5px; font-family: arial black; color: #ffbbbb;">Power</span><span style="color: #bbbbff;">Graphic</span>
  </div>
  <div style="font-style: italic; font-size: 15px;">Powered by Carlos Reche</div>
</div>

<div style="margin: 0px 0px 30px 0px; font-size: 17px;">
  Features:
  <div style="margin: 0px 0px 0px 10px; font-size: 13px; font-weight: normal;">
    - 6 types of graphics <br />
    - 3 different skins <br />
    - Data crossing from 2 graphics <br />
  </div>
</div>


<?php

require_once "class.graphic.php";
$PG = new PowerGraphic;



$PG->title     = 'Sales';
$PG->axis_x    = 'Month';
$PG->axis_y    = 'US$';
$PG->graphic_1 = 'Year 2004';
$PG->graphic_2 = 'Year 2003';
$PG->type      = 1;
$PG->skin      = 1;
$PG->credits   = 0;

// Set values
$PG->x[0] = 'jan';
$PG->y[0] = 35000;


$PG->x[1] = 'feb';
$PG->y[1] = 38500;

$PG->x[2] = 'mar';
$PG->y[2] = 40800;

$PG->x[3] = 'apr';
$PG->y[3] = 45200;

$PG->x[4] = 'may';
$PG->y[4] = 46800;

$PG->x[5] = 'jun';
$PG->y[5] = 55000;

echo '<span style="font-size: 17px;">&#8226; 6 types of graphic:</span> <br /><br />';
echo '1. Vertical bars: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';


// Changing the type

$PG->type = 2;
echo '2. Horizontal bars: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

$PG->type = 3;
echo '3. Dots: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

$PG->type = 4;
echo '4. Lines: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

$PG->type = 5;
echo '5. Pie: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

$PG->type = 6;
echo '6. Donut: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

?>


<hr style="margin: 20px 0px 20px 0px; color: #aaaaff;" />


<?php

// Clear parameters
$PG->reset_values();


echo '<span style="font-size: 17px;">&#8226; 3 different skins:</span> <br /><br />';

$PG->title     = 'Tax Rates';
$PG->axis_x    = 'Months';
$PG->axis_y    = 'Tax (%)';
$PG->graphic_1 = 'Year 2004';
$PG->graphic_2 = 'Year 2003';
$PG->skin      = 1;
$PG->type      = 2;
$PG->credits   = 0;

// Set values
$PG->x[0] = 'jan';
$PG->y[0] = 6.0;
$PG->z[0] = 5.2;

$PG->x[1] = 'feb';
$PG->y[1] = 6.0;
$PG->z[1] = 5.3;

$PG->x[2] = 'mar';
$PG->y[2] = 6.3;
$PG->z[2] = 7.8;

$PG->x[3] = 'apr';
$PG->y[3] = 5.0;
$PG->z[3] = 6.2;

$PG->x[4] = 'may';
$PG->y[4] = 6.1;
$PG->z[4] = 5.7;

$PG->x[5] = 'jun';
$PG->y[5] = 7.0;
$PG->z[5] = 6.0;



echo '1. Office: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';
$PG->type = 4;
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

$PG->skin = 2;
$PG->type = 2;
echo '2. Matrix: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';
$PG->type = 4;
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';


$PG->skin = 3;
$PG->type = 2;
echo '3. Spring: <br /><br />';
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';
$PG->type = 4;
echo '<img src="class.graphic.php?' . $PG->create_query_string() . '" border="1" alt="" /> <br /><br />';

?>


</body>
</html>





