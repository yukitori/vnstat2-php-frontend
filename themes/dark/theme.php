<?php
    //
    // Colours for the generated graphs (graph.php / graph_svg.php).
    // Values are (red, green, blue, alpha), alpha 0 = opaque, 127 = transparent.
    // Taken from the dark palette in themes/dark/style.css.
    //
    $colorscheme = array(
         'image_background'   => array(  41,  40,  38,   0 ),  // --surface
         'graph_background'   => array(  35,  34,  32,   0 ),  // --sunk
         'graph_background_2' => array(  50,  47,  45,   0 ),  // --line-soft
         'grid_stipple_1'     => array(  59,  57,  54,   0 ),  // --line
         'grid_stipple_2'     => array(  50,  47,  45,   0 ),  // --line-soft
         'border'             => array(  59,  57,  54,   0 ),  // --line
         'text'               => array( 233, 229, 222,   0 ),  // --text
         'rx'                 => array( 147, 166, 149,  36 ),  // --rx (72% opaque)
         'rx_border'          => array( 147, 166, 149,  36 ),
         'tx'                 => array( 196, 162, 148,  43 ),  // --tx (66% opaque)
         'tx_border'          => array( 196, 162, 148,  43 )
     );
?>
