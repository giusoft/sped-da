<?php
  include "../class.piegraph.php";

  $graph = new Graph();
  $graph->LoadGraph(realpath("./piegraph.def"));
  $graph->DrawGraph();
?>
