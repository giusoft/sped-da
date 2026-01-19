<?php
  include "../class.3dbargraph.php";

  $graph = new Graph();
  $graph->LoadGraph(realpath("./3dbargraph.def"));
  $graph->DrawGraph();
?>
