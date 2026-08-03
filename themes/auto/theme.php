<?php
    //
    // Only the browser knows which appearance the device is set to, and the
    // graph images of graph.php and graph_svg.php are drawn here, so they
    // are drawn in the colours of the light theme. The bar chart built into
    // index.php ($graph_format = 'html') does follow the device.
    //
    require dirname(__FILE__).'/../light/theme.php';
?>
