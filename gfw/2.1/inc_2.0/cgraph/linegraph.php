<?php
  include "../class.linegraph.php";

  $graph = new Graph();
  $graph->LoadGraph(realpath("./linegraph.def"));
  $graph->DrawGraph();
?>
