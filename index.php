<?php
    //
    // vnStat PHP frontend (c)2006-2010 Bjorge Dijkstra (bjd@jooz.net)
    //
    // This program is free software; you can redistribute it and/or modify
    // it under the terms of the GNU General Public License as published by
    // the Free Software Foundation; either version 2 of the License, or
    // (at your option) any later version.
    //
    // This program is distributed in the hope that it will be useful,
    // but WITHOUT ANY WARRANTY; without even the implied warranty of
    // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    // GNU General Public License for more details.
    //
    // You should have received a copy of the GNU General Public License
    // along with this program; if not, write to the Free Software
    // Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    //
    //
    // see file COPYING or at http://www.gnu.org/licenses/gpl.html
    // for more information.
    //
    require 'config.php';
    require 'localize.php';
    require 'vnstat.php';

    validate_input();

    require "./themes/$style/theme.php";

    function write_side_bar()
    {
        global $iface, $page, $graph, $script, $style;
        global $iface_list, $iface_title;
        global $page_list, $page_title;

        $p = "&amp;graph=$graph&amp;style=$style";

        print "<nav class=\"nav\">\n";
        foreach ($iface_list as $if)
        {
            $active = ($iface == $if);
            $name = isset($iface_title[$if]) ? $iface_title[$if] : $if;

            print "<div class=\"nav-group".($active ? " active" : "")."\">\n";
            print "<a class=\"nav-iface\" href=\"$script?if=$if$p\">";
            print "<span class=\"nav-iface-name\">".htmlspecialchars($name)."</span>";
            print "<span class=\"nav-iface-id\">$if</span>";
            print "</a>\n";

            print "<ul class=\"nav-pages\">\n";
            foreach ($page_list as $pg)
            {
                $on = ($active && $page == $pg) ? " class=\"on\"" : "";
                print "<li><a$on href=\"$script?if=$if$p&amp;page=$pg\">".$page_title[$pg]."</a></li>\n";
            }
            print "</ul>\n";
            print "</div>\n";
        }
        print "</nav>\n";
    }


    //
    // theme switcher: one link per available theme (a theme is any
    // themes/<name>/ directory that provides both style.css and theme.php).
    // Switching reloads the page with the new style so the server-rendered
    // graph is recoloured too.
    //
    function write_theme_switcher()
    {
        global $iface, $page, $graph, $style, $script;

        $themes = array();
        foreach (glob('themes/*', GLOB_ONLYDIR) as $dir) {
            if (file_exists("$dir/style.css") && file_exists("$dir/theme.php")) {
                $themes[] = basename($dir);
            }
        }
        sort($themes);
        if (count($themes) < 2) {
            return;
        }

        $labels = array('light' => 'Light', 'dark' => 'Dark', 'red' => 'Rose');

        print "<div class=\"theme-switch\">\n";
        print "<span class=\"theme-switch-label\">".T('Theme')."</span>\n";
        print "<div class=\"theme-options\">\n";
        foreach ($themes as $t) {
            $on = ($t == $style) ? " on" : "";
            $label = T(isset($labels[$t]) ? $labels[$t] : ucfirst($t));
            $href = "$script?if=$iface&amp;page=$page&amp;graph=$graph&amp;style=$t";
            print "<a class=\"theme-option$on\" href=\"$href\">";
            print "<span class=\"theme-swatch theme-swatch-".htmlspecialchars($t)."\"></span>";
            print htmlspecialchars($label)."</a>\n";
        }
        print "</div>\n</div>\n";
    }


    function kbytes_to_string($kb)
    {

        global $byte_notation;

        $units = array('TiB','GiB','MiB','KiB');
        $scale = 1024*1024*1024;
        $ui = 0;

        $custom_size = isset($byte_notation) && in_array($byte_notation, $units);

        while ((($kb < $scale) && ($scale > 1)) || $custom_size)
        {
            $ui++;
            $scale = $scale / 1024;

            if ($custom_size && $units[$ui] == $byte_notation) {
                break;
            }
        }

        return sprintf("%0.2f %s", ($kb/$scale),$units[$ui]);
    }

    //
    // format a byte value as a value/unit pair so the two can be styled
    // independently (large figure + small unit)
    //
    function kbytes_to_parts($kb)
    {
        $s = kbytes_to_string($kb);
        $parts = explode(' ', $s, 2);
        return array($parts[0], isset($parts[1]) ? $parts[1] : '');
    }

    function write_summary()
    {
        global $summary,$top,$day,$hour,$month;

        $trx = $summary['totalrx']*1024+$summary['totalrxk'];
        $ttx = $summary['totaltx']*1024+$summary['totaltxk'];

        $cards = array();

        if (count($day) > 0 && count($hour) > 0 && count($month) > 0) {
            $cards[] = array('label' => T('This hour'),  'rx' => $hour[0]['rx'],  'tx' => $hour[0]['tx']);
            $cards[] = array('label' => T('This day'),   'rx' => $day[0]['rx'],   'tx' => $day[0]['tx']);
            $cards[] = array('label' => T('This month'), 'rx' => $month[0]['rx'], 'tx' => $month[0]['tx']);
            $cards[] = array('label' => T('All time'),   'rx' => $trx,            'tx' => $ttx);
        }

        if (count($cards) > 0) {
            print "<div class=\"kpi-grid\">\n";
            foreach ($cards as $c)
            {
                list($tval, $tunit) = kbytes_to_parts($c['rx'] + $c['tx']);
                $rx = kbytes_to_string($c['rx']);
                $tx = kbytes_to_string($c['tx']);
                print "<div class=\"kpi\">\n";
                print "  <div class=\"kpi-label\">".$c['label']."</div>\n";
                print "  <div class=\"kpi-total\"><span class=\"num\">$tval</span> <span class=\"unit\">$tunit</span></div>\n";
                print "  <div class=\"kpi-io\">\n";
                print "    <span class=\"io\"><span class=\"dot in\"></span>".T('In')." <b>$rx</b></span>\n";
                print "    <span class=\"io\"><span class=\"dot out\"></span>".T('Out')." <b>$tx</b></span>\n";
                print "  </div>\n";
                print "</div>\n";
            }
            print "</div>\n";
        }

        write_data_table(T('Top 10 days'), $top);
    }


    function write_data_table($caption, $tab)
    {
        print "<section class=\"card\">\n";
        print "<h2 class=\"card-title\">".$caption."</h2>\n";
        print "<div class=\"table-wrap\">\n";
        print "<table class=\"data\">\n";
        print "<thead><tr>";
        print "<th class=\"label\">&nbsp;</th>";
        print "<th class=\"num\"><span class=\"dot in\"></span>".T('In')."</th>";
        print "<th class=\"num\"><span class=\"dot out\"></span>".T('Out')."</th>";
        print "<th class=\"num total\">".T('Total')."</th>";
        print "</tr></thead>\n<tbody>\n";

        $rows = 0;
        for ($i=0; $i<count($tab); $i++)
        {
            if ($tab[$i]['act'] == 1)
            {
                $t = $tab[$i]['label'];
                $rx = kbytes_to_string($tab[$i]['rx']);
                $tx = kbytes_to_string($tab[$i]['tx']);
                $total = kbytes_to_string($tab[$i]['rx']+$tab[$i]['tx']);
                print "<tr>";
                print "<td class=\"label\">$t</td>";
                print "<td class=\"num\">$rx</td>";
                print "<td class=\"num\">$tx</td>";
                print "<td class=\"num total\">$total</td>";
                print "</tr>\n";
                $rows++;
             }
        }
        if ($rows == 0) {
            print "<tr><td class=\"empty\" colspan=\"4\">".T('no data available')."</td></tr>\n";
        }
        print "</tbody></table>\n";
        print "</div>\n</section>\n";
    }

    get_vnstat_data();

    $page_heading = array(
        's' => T('Summary'),
        'h' => T('Last 24 hours'),
        'd' => T('Last 30 days'),
        'm' => T('Last 12 months'),
    );
    $iface_name = isset($iface_title[$iface]) ? $iface_title[$iface] : $iface;

    //
    // html start
    //
    header('Content-type: text/html; charset=utf-8');
    // Security hardening headers. The page uses no JavaScript and only
    // same-origin CSS/images, so a strict policy does not break anything.
    header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; base-uri 'none'; form-action 'self'; frame-ancestors 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>vnStat &middot; <?php echo htmlspecialchars($iface_name); ?></title>
<?php
    // Cache-bust stylesheets with their file modification time so browsers
    // always re-fetch after a theme is updated (avoids serving a stale
    // cached style.css from a previous version).
    $base_v  = @filemtime('themes/base.css');
    $theme_v = @filemtime("themes/$style/style.css");
?>
  <link rel="stylesheet" type="text/css" href="themes/base.css?v=<?php echo $base_v; ?>"/>
  <link rel="stylesheet" type="text/css" href="themes/<?php echo $style ?>/style.css?v=<?php echo $theme_v; ?>"/>
</head>
<body>

<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <span class="brand-mark">vnStat</span>
      <span class="brand-sub">network traffic</span>
    </div>
    <?php write_side_bar(); ?>
    <?php write_theme_switcher(); ?>
  </aside>

  <main class="content">
    <header class="topbar">
      <h1 class="topbar-title">
        <span class="topbar-iface"><?php echo htmlspecialchars($iface_name); ?></span>
        <span class="topbar-id"><?php echo htmlspecialchars($iface); ?></span>
      </h1>
      <div class="topbar-sub"><?php echo $page_heading[$page]; ?></div>
    </header>

    <div class="main">
    <?php
    $graph_params = "if=$iface&amp;page=$page&amp;style=$style";
    if ($page != 's') {
        $src = ($graph_format == 'svg') ? "graph_svg.php?$graph_params" : "graph.php?$graph_params";
        print "<section class=\"card graph-card\">\n";
        print "  <div class=\"graph\"><img src=\"$src\" alt=\"".T('Traffic data for')." $iface\"/></div>\n";
        print "</section>\n";
    }

    if ($page == 's')
    {
        write_summary();
    }
    else if ($page == 'h')
    {
        write_data_table(T('Last 24 hours'), $hour);
    }
    else if ($page == 'd')
    {
        write_data_table(T('Last 30 days'), $day);
    }
    else if ($page == 'm')
    {
        write_data_table(T('Last 12 months'), $month);
    }
    ?>
    </div>

    <footer class="footer">
      <a href="http://www.sqweek.com/">vnStat PHP frontend</a> 2.0.0
      &middot; &copy;2006-2011 Bjorge Dijkstra
    </footer>
  </main>
</div>

</body></html>
