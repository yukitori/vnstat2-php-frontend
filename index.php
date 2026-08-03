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

    $theme_list = array('auto', 'light', 'dark');

    // config.php files predating the built-in bar chart don't set this
    if (!isset($graph_format))
    {
        $graph_format = 'html';
    }


    function e($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }


    //
    // link back to this script, with the current state as a starting point
    //
    function page_link($params = array())
    {
        global $script, $iface, $page, $graph, $style;

        $query = array_merge(array('if' => $iface, 'page' => $page,
                                   'graph' => $graph, 'style' => $style), $params);

        $parts = array();
        foreach ($query as $key => $value)
        {
            $parts[] = rawurlencode($key).'='.rawurlencode($value);
        }

        return e($script.'?'.implode('&', $parts));
    }


    function interface_name($if)
    {
        global $iface_title;

        if (isset($iface_title[$if]) && $iface_title[$if] != '')
        {
            return $iface_title[$if];
        }

        return $if;
    }


    //
    // 'Traffic data for' is used as a small label above the interface name,
    // so drop the trailing colon some of the translations carry
    //
    function head_kicker()
    {
        $text = T('Traffic data for');
        $label = preg_replace('/[:\x{ff1a}]\s*$/u', '', $text);

        return ($label === null) ? $text : $label;
    }


    //
    // name of the period a page shows
    //
    function view_title($pg)
    {
        switch ($pg)
        {
            case 'h': return T('Last 24 hours');
            case 'd': return T('Last 30 days');
            case 'm': return T('Last 12 months');
        }

        return T('Summary');
    }


    function write_side_bar()
    {
        global $iface, $page, $style, $theme_list;
        global $iface_list, $page_list, $page_title;

        print "<div class=\"brand\">\n";
        print "<div class=\"brand-name\">vnStat</div>\n";
        print "<div class=\"brand-sub\">".e(T('Traffic statistics'))."</div>\n";
        print "</div>\n";

        print "<ul class=\"iface\">\n";
        foreach ($iface_list as $if)
        {
            $active = ($if == $iface);
            $name = interface_name($if);

            print $active ? "<li class=\"iface active\">" : "<li class=\"iface\">";
            print "<a class=\"iface-link\" href=\"".page_link(array('if' => $if))."\">";
            print "<span class=\"iface-name\">".e($name)."</span>";
            if ($name != $if)
            {
                print "<span class=\"iface-id\">".e($if)."</span>";
            }
            print "</a>\n";

            if ($active)
            {
                print "<ul class=\"page\">\n";
                foreach ($page_list as $pg)
                {
                    $class = ($pg == $page) ? 'page-link current' : 'page-link';
                    print "<li class=\"page\"><a class=\"$class\" href=\"".page_link(array('if' => $if, 'page' => $pg))."\">";
                    print e($page_title[$pg])."</a></li>\n";
                }
                print "</ul>\n";
            }
            print "</li>\n";
        }
        print "</ul>\n";

        print "<div class=\"appearance\">\n";
        print "<span class=\"appearance-label\">".e(T('Appearance'))."</span>\n";
        print "<div class=\"switch\">\n";
        $theme_label = array('auto' => T('Auto'), 'light' => T('Light'), 'dark' => T('Dark'));
        foreach ($theme_list as $theme)
        {
            $class = ($theme == $style) ? 'switch-opt on' : 'switch-opt';
            $label = isset($theme_label[$theme]) ? $theme_label[$theme] : $theme;
            print "<a class=\"$class\" href=\"".page_link(array('style' => $theme))."\">".e($label)."</a>\n";
        }
        print "</div>\n";
        print "</div>\n";
    }


    //
    // split a value in KiB into a formatted number and its unit
    //
    function kbytes_split($kb)
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

        return array(sprintf("%0.2f", $kb/$scale), $units[$ui]);
    }


    function kbytes_to_string($kb)
    {
        $value = kbytes_split($kb);

        return $value[0].' '.$value[1];
    }


    function write_summary_cards()
    {
        global $summary,$day,$hour,$month;

        $cards = array();

        if (count($hour) > 0)
        {
            $cards[] = array('label' => T('This hour'), 'rx' => $hour[0]['rx'], 'tx' => $hour[0]['tx']);
        }
        if (count($day) > 0)
        {
            $cards[] = array('label' => T('This day'), 'rx' => $day[0]['rx'], 'tx' => $day[0]['tx']);
        }
        if (count($month) > 0)
        {
            $cards[] = array('label' => T('This month'), 'rx' => $month[0]['rx'], 'tx' => $month[0]['tx']);
        }
        if (isset($summary['totalrx']))
        {
            $cards[] = array('label' => T('All time'),
                             'rx' => $summary['totalrx']*1024+$summary['totalrxk'],
                             'tx' => $summary['totaltx']*1024+$summary['totaltxk']);
        }

        if (count($cards) == 0)
        {
            return;
        }

        print "<section class=\"summary\">\n<div class=\"cards\">\n";
        foreach ($cards as $card)
        {
            $total = kbytes_split($card['rx'] + $card['tx']);

            print "<div class=\"card\">\n";
            print "<div class=\"card-label\">".e($card['label'])."</div>\n";
            print "<div class=\"card-value num\">";
            print "<span class=\"card-num\">".e($total[0])."</span>";
            print "<span class=\"card-unit\">".e($total[1])."</span>";
            print "</div>\n";
            print "<div class=\"card-split\">\n";
            print "<div class=\"card-split-row\"><span class=\"dir\"><i class=\"dot rx\"></i>".e(T('In'))."</span>";
            print "<span class=\"val num\">".e(kbytes_to_string($card['rx']))."</span></div>\n";
            print "<div class=\"card-split-row\"><span class=\"dir\"><i class=\"dot tx\"></i>".e(T('Out'))."</span>";
            print "<span class=\"val num\">".e(kbytes_to_string($card['tx']))."</span></div>\n";
            print "</div>\n";
            print "</div>\n";
        }
        print "</div>\n</section>\n";
    }


    //
    // pick a round top value for the graph, together with the unit and the
    // divider needed to get there from KiB
    //
    function graph_scale($peak)
    {
        $units = array('KiB','MiB','GiB','TiB');

        if ($peak <= 0)
        {
            return array('max' => 1, 'unit' => $units[0], 'div' => 1);
        }

        $div = 1;
        $ui = 0;
        while ((($peak / $div) >= 1024) && ($ui < 3))
        {
            $div = $div * 1024;
            $ui++;
        }

        $scaled = $peak / $div;
        $magnitude = pow(10, floor(log10($scaled)));

        $step = 10;
        foreach (array(1, 2, 2.5, 5, 10) as $candidate)
        {
            if (($scaled / ($candidate * $magnitude)) <= 5)
            {
                $step = $candidate;
                break;
            }
        }

        $step = $step * $magnitude;

        return array('max' => ceil($scaled / $step) * $step, 'unit' => $units[$ui], 'div' => $div);
    }


    function compare_time($a, $b)
    {
        if ($a['time'] == $b['time'])
        {
            return 0;
        }

        return ($a['time'] < $b['time']) ? -1 : 1;
    }


    function write_graph($data)
    {
        $y_ticks = 5;

        $bars = $data;
        usort($bars, 'compare_time');

        $peak = 0;
        foreach ($bars as $bar)
        {
            $peak = max($peak, $bar['rx'], $bar['tx']);
        }

        $scale = graph_scale($peak);
        $top = $scale['max'] * $scale['div'];

        print "<div class=\"chart\">\n";
        for ($i = 0; $i <= $y_ticks; $i++)
        {
            $value = ($scale['max'] / $y_ticks) * $i;
            $label = (fmod($value, 1) == 0) ? (string)(int)$value : sprintf('%.1f', $value);
            printf("<div class=\"chart-grid\" style=\"top: %.4f%%\"><span class=\"num\">%s</span></div>\n",
                   (1 - $i / $y_ticks) * 100, e($label.' '.$scale['unit']));
        }

        print "<div class=\"chart-cols\">";
        foreach ($bars as $bar)
        {
            $title = $bar['label'].' · '.T('In').' '.kbytes_to_string($bar['rx']).
                     ' · '.T('Out').' '.kbytes_to_string($bar['tx']);

            printf("<div class=\"chart-col\" title=\"%s\">", e($title));
            printf("<i class=\"rx\" style=\"height: %.3f%%\"></i>", max(0.4, ($bar['rx'] / $top) * 100));
            printf("<i class=\"tx\" style=\"height: %.3f%%\"></i>", max(0.4, ($bar['tx'] / $top) * 100));
            print "</div>";
        }
        print "</div>\n";
        print "</div>\n";

        print "<div class=\"chart-axis\"></div>\n";
        print "<div class=\"chart-labels\">";
        foreach ($bars as $bar)
        {
            print "<span class=\"num\">".e($bar['img_label'])."</span>";
        }
        print "</div>\n";
    }


    function write_graph_section($data)
    {
        global $iface, $page, $style, $graph, $graph_format;

        print "<section class=\"graph\">\n";
        print "<div class=\"section-head\">\n";
        print "<h2 class=\"section-title\">".e(sprintf(T('%s traffic'), view_title($page)))."</h2>\n";

        if ($graph_format != 'svg' && $graph_format != 'png')
        {
            print "<div class=\"legend\">";
            print "<span><i class=\"swatch rx\"></i>".e(T('In'))."</span>";
            print "<span><i class=\"swatch tx\"></i>".e(T('Out'))."</span>";
            print "</div>\n";
        }
        print "</div>\n";

        $params = "if=".rawurlencode($iface)."&amp;page=".rawurlencode($page).
                  "&amp;style=".rawurlencode($style)."&amp;graph=".rawurlencode($graph);

        if ($graph_format == 'svg')
        {
            print "<div class=\"chart-image\"><object type=\"image/svg+xml\" width=\"692\" height=\"297\" data=\"graph_svg.php?$params\"></object></div>\n";
        }
        else if ($graph_format == 'png')
        {
            print "<div class=\"chart-image\"><img src=\"graph.php?$params\" alt=\"graph\"/></div>\n";
        }
        else
        {
            write_graph($data);
        }

        print "</section>\n";
    }


    function write_data_table($caption, $tab, $ranked = false)
    {
        print "<section class=\"table\">\n";
        print "<h2 class=\"section-title\">".e($caption)."</h2>\n";
        print $ranked ? "<table class=\"data top\">\n" : "<table class=\"data\">\n";
        print "<thead><tr>";
        if ($ranked)
        {
            print "<th class=\"rank\"></th>";
        }
        print "<th class=\"label\"></th>";
        print "<th class=\"numeric\">".e(T('In'))."</th>";
        print "<th class=\"numeric\">".e(T('Out'))."</th>";
        print "<th class=\"numeric\">".e(T('Total'))."</th>";
        print "</tr></thead>\n<tbody>\n";

        $rank = 0;
        for ($i=0; $i<count($tab); $i++)
        {
            if ($tab[$i]['act'] == 1)
            {
                $rank++;
                print "<tr>";
                if ($ranked)
                {
                    printf("<td class=\"rank num\">%02d</td>", $rank);
                }
                print "<td class=\"label\">".e($tab[$i]['label'])."</td>";
                print "<td class=\"numeric num\">".e(kbytes_to_string($tab[$i]['rx']))."</td>";
                print "<td class=\"numeric num\">".e(kbytes_to_string($tab[$i]['tx']))."</td>";
                print "<td class=\"numeric num\">".e(kbytes_to_string($tab[$i]['rx']+$tab[$i]['tx']))."</td>";
                print "</tr>\n";
             }
        }
        print "</tbody>\n</table>\n</section>\n";
    }

    get_vnstat_data();

    //
    // html start
    //
    header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>vnStat - PHP frontend</title>
  <link rel="stylesheet" type="text/css" href="fonts/fonts.css"/>
  <link rel="stylesheet" type="text/css" href="themes/<?php echo e($style) ?>/style.css"/>
</head>
<body>

<div id="wrap">
  <aside id="sidebar"><?php write_side_bar(); ?></aside>
  <main id="content">
    <header id="header">
      <div class="head-main">
        <div class="head-kicker"><?php print e(head_kicker()); ?></div>
        <h1><?php print e(interface_name($iface));
                  if (interface_name($iface) != $iface) { ?> <span class="iface-id">(<?php print e($iface); ?>)</span><?php } ?></h1>
      </div>
      <div class="head-meta">
        <div><?php print e(view_title($page)); ?></div>
<?php   if (isset($summary['updated'])) { ?>
        <div class="head-updated num"><?php print e(T('Updated').' '.date('Y-m-d H:i', $summary['updated'])); ?></div>
<?php   } ?>
      </div>
    </header>
<?php
    if ($page == 's')
    {
        write_summary_cards();
        write_data_table(T('Top 10 days'), $top, true);
    }
    else
    {
        $data = ($page == 'h') ? $hour : (($page == 'd') ? $day : $month);

        if ($graph != 'none')
        {
            write_graph_section($data);
        }
        write_data_table(view_title($page), $data);
    }
?>
    <footer id="footer">
      <span><a href="http://www.sqweek.com/">vnStat PHP frontend</a> 2.0.0</span>
      <span>&copy; 2006&#8211;2011 Bjorge Dijkstra (bjd _at_ jooz.net)</span>
    </footer>
  </main>
</div>

</body></html>
