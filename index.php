<?php

ini_set ('display_errors', 0);
error_reporting (0);

//ini_set ('display_errors', 1);
//error_reporting (E_ALL);

require 'includes/geo/Ip2Country.php';

include ('includes/parseUserAgentString/parseUserAgentString.php');
include ('includes/jaybizzle/vendor/autoload.php');
use Jaybizzle\CrawlerDetect\CrawlerDetect;

define ('LOG_FILE', 'log.txt');
define ('LOG_BAK_FILE', 'log.bak');
define ('MAX_LOG_ITEMS', 20000);
define ('MAX_VIEW_ITEMS', 200);
define ('MIN_TIME_DIFFERENCE', 10 * 60);
define ('SCRIPT_ROOT', __DIR__ != $_SERVER ['DOCUMENT_ROOT'] ? substr (__DIR__, strlen ($_SERVER ['DOCUMENT_ROOT'])) . '/' : '');
define ('BOT_ICONS', 'images/bots');
define ('DEVICE_ICONS', 'images/devices');
define ('BROWSER_ICONS', 'images/browsers');
define ('OS_ICONS', 'images/os');

$admin_ip = array ('192.168.1.10');

?><html>
<head>
<link href="flags.css" rel="stylesheet">
<link href="includes/jvectormap/jquery-jvectormap-2.0.3.css" rel="stylesheet">

<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js'></script>

<title>ARMAL - Automatic Real-time Monitor And Log</title>
<link href="favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<meta charset="utf-8" />
<meta name="Keywords" content="web, http, spider, crawler, monitor, log, time, IP address, country, user agent" >
<meta name="Description" content="Automatic real-time monitor and log - see who visits your website, each visit is logged: time, IP address, country and user agent" >
<meta name="Robots" content="index, follow" >
<meta http-equiv="content-language" content="en">

<style>
#page {
  width: 600px;
  margin: 0 auto;
  font-family: Arial;
  font-size: 12px;
}
#page p {
  text-align: justify;
  font-size: 14px;
}
#header {
  border: 1px solid #eee;
  border-radius: 5px;
  margin: 10px 0 30px 0;
  font-family: Arial;
  text-align: center;
  background: #def;
}
#header a, #footer a {
  text-decoration: none;
}
#footer {
  border: 1px solid #eee;
  border-radius: 5px;
  margin: 30px 0 10px 0;
  padding: 10px 0;
  font-family: Arial;
  font-size: 12px;
  text-align: center;
  background: #f8f8f8;
}
#header h1 {
  color: #008;
}
#header h2 {
  color: #00c;
}
.log {
  width: 99%;
  margin: 0 auto;
}
.log table {
  text-align: center;
  width: 100%;
}
.log th, .log td {
  font-family: monospace, Courier, 'Courier New', monospace;
  font-size: 12px;
  padding: 1px 20px 1px 0;
  text-decoration: none;
}
.log th a, .log td a {
  font-family: monospace, Courier, 'Courier New', monospace;
  font-size: 12px;
  padding: 1px 20px 1px 0;
  text-decoration: none;
}
.log th {
  padding: 1px 20px 10px 0;
}
tr.odd {
  background: #f4f4f4;
}
td.time {
  width: 50px;
  white-space: pre;
}
td.country {
  width: 30px;
  white-space: pre;
}
th.ip-address, td.ip-address {
  white-space: pre;
  text-align: left;
}
td.ip-version {
  white-space: pre;
  font-size: 14px;
  font-weight: bold;
  color: #00f;
}
th.bot, th.visitor, td.bot, td.visitor {
  text-align: left;
  white-space: pre;
  overflow: hidden;
}
th.referer, td.referer {
  max-width: 200px;
  text-align: left;
  white-space: pre;
  overflow: hidden;
}
td.referer.html a {
  color: #f00;
}
span.flag-icon {
  margin: 0 6px 0 0;
}
div.statistics {
  margin: 20px 0;
}
.statistics table {
  text-align: left;
  width: 100%;
}
.statistics td {
  width: 3%;
  padding: 2px 0;
}
.statistics td.percentage {
  width: 7%;
}
.statistics td.bar {
  width: 70%;
  min-width: 200px;
}
.bar div {
  background: #0c0;
  height: 18px;
}
@media (min-width: 768px) {
  #page {
    width: 768px;
  }
}
@media (min-width: 960px) {
  #page {
    width: 960px;
  }
}
</style>
<script type="text/javascript">
  window.onload = function(){
     window.scrollTo(0,document.body.scrollHeight);
     setInterval(function(){
         window.location.reload (1);
     }, 600000);
  };
</script>
</head>
<body>
  <div id='page'>
    <div id='header'>
      <a href="<?php echo  SCRIPT_ROOT; ?>"><h1>ARMAL</h1></a>
      <h2>Automatic Real-time Monitor And Log</h2>
    </div>
<?php


function main () {
  global $admin_ip;

  if (!file_exists (LOG_FILE)) {
    file_put_contents (LOG_FILE, serialize (array ()));
    $log = array ();
  } else {
    $log = unserialize (file_get_contents (LOG_FILE));
    if ($log === false) {
      @unlink (LOG_FILE);
      $log = array ();
    }
  }

  $ip_address = get_client_ip_address ();

  $show_referer = false;
  if (in_array ($ip_address, $admin_ip)) $show_referer = true;

  date_default_timezone_set ("UTC");

  $found = false;
  foreach ($log as $index => $log_item) {
    if ($log_item ['ip-address'] == $ip_address && (time () - $log_item ['time'] < MIN_TIME_DIFFERENCE || $index == 0)) {
      $found = true;
      break;
    }
  }
  if (!$found && !in_array ($ip_address, $admin_ip)) {
    array_unshift ($log, array (
                'time' => time (),
                'ip-address' => $ip_address,
                'country' => ip_to_country ($ip_address),
                'referer' => isset ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                'user-agent' => $_SERVER['HTTP_USER_AGENT'],
              ));

    $log = array_slice ($log, 0, MAX_LOG_ITEMS);

    @unlink (LOG_BAK_FILE);
    @rename (LOG_FILE, LOG_BAK_FILE);
    $serialized_log = serialize ($log);
    file_put_contents (LOG_FILE, $serialized_log);
    if (filesize (LOG_FILE) < 6 || unserialize (file_get_contents (LOG_FILE)) === false) {
      rename (LOG_BAK_FILE, LOG_FILE);
    }
  }


  $parser = new parseUserAgentStringClass();
  $parser->includeAndroidName = true;
  $parser->includeWindowsName = true;
  $parser->includeMacOSName = true;
  $parser->treatClonesAsTheRealThing = true;
  $parser->treatProjectSpartanInternetExplorerLikeLegacyInternetExplorer = false;

  $CrawlerDetect = new CrawlerDetect;


  echo "<div class='log'><table cellpadding='0' cellspacing='0' >\n";
  echo "<tr>";
  echo '<th class="time">UTC TIME</th>';
  echo '<th class="ip-version">IP</th>';
  echo '<th class="ip-address">ADDRESS</th>';
  echo '<th class="country">COUNTRY</th>';
  echo '<th class="bot">BOT</th>';
  echo '<th class="visitor">VISITOR</th>';
  if ($show_referer) {
    echo '<th class="referer">REFERER</th>';
  }
  echo "</tr>";
  foreach (array_slice ($log, 0, MAX_VIEW_ITEMS) as $index => $log_item) {

    $encoded_referer = htmlspecialchars ($log_item ['referer']);
    $html_referer = strpos ($log_item ['referer'], '<') !== false;

    $bot_data = '&nbsp;&nbsp;';
    $bot_title = '';
    $bot = false;
    if ($CrawlerDetect->isCrawler (strip_tags ($log_item ['user-agent']))) {
      $bot_title = $CrawlerDetect->getMatches ();
      $bot_icon = BOT_ICONS.'/bot_'.str_replace (' ', '-', strtolower ($bot_title)).'.png';
      if (file_exists (dirname (__FILE__).'/'.$bot_icon)) $bot_data = '<img src="'.$bot_icon.'" />'; else $bot_data = '<img src="'.BOT_ICONS.'/unknown.png" />';
      $bot = true;
    }
    if (!$bot) {
      if (stripos ($log_item ['user-agent'], 'mosad') !== false) {
        $bot = true;
        $bot_data = '<img src="'.BOT_ICONS.'/unknown.png" />';
        $bot_title = $log_item ['user-agent'];
      }
    }

    $parser->parseUserAgentString (strip_tags ($log_item ['user-agent']));
    $browser_data = '?';
    if (!$bot && !$html_referer) {
      if ($log_item ['user-agent'] != '' && $parser->knownbrowser && $parser->browsername != '') {
//        $device_icon = DEVICE_ICONS.'/'.$parser->type.'.png';
//        $device_data = '';
//        if (file_exists (dirname (__FILE__).'/'.$device_icon)) $device_data = '<img src="'.$device_icon.'" />'; else $device_data = ''.$parser->type;
//        $browser_data =  $device_data .' ' . $parser->browsername . ' ' . $parser->browserversion . ', ' . $parser->osname ;

        if (stripos ($parser->browsername, 'chrome') !== false) {
          $browser_icon = 'chrome.png';
        }
        elseif (stripos ($parser->browsername, 'internet explorer') !== false) {
          $browser_icon = 'internet-explorer.png';
        }
        elseif (stripos ($parser->browsername, 'safari') !== false) {
          $browser_icon = 'safari.png';
        }
        elseif (stripos ($parser->browsername, 'firefox') !== false) {
          $browser_icon = 'firefox.png';
        } else $browser_icon = '';
        if ($browser_icon != '') $browser_icon = '<img src="'.BROWSER_ICONS.'/'.$browser_icon.'" title = "'. $parser->browsername . ' ' . $parser->browserversion.'" style="margin-right: 10px;" />'; else $browser_icon = $parser->browsername .' ';

        if (stripos ($parser->osname, 'windows 10') !== false) {
          $os_icon = 'windows10.png';
        }
        elseif (stripos ($parser->osname, 'windows 8.1') !== false) {
          $os_icon = 'windows81.png';
        }
        elseif (stripos ($parser->osname, 'windows 7') !== false) {
          $os_icon = 'windows7.png';
        }
        elseif (stripos ($parser->osname, 'windows vista') !== false) {
          $os_icon = 'windowsvista.png';
        }
        elseif (stripos ($parser->osname, 'windows xp') !== false) {
          $os_icon = 'windowsxp.png';
        }
        elseif (stripos ($parser->osname, 'android') !== false) {
          $os_icon = 'android.png';
        }
        elseif (stripos ($parser->osname, 'linux') !== false) {
          $os_icon = 'linux.png';
        }
        elseif (stripos ($parser->osname, 'ios') !== false) {
          if (stripos ($log_item ['user-agent'], 'android') !== false) {
            $os_icon = 'android.png';
            $parser->osname = 'Android';
          } else $os_icon = 'ios.png';
        }
        elseif (stripos ($parser->osname, 'mac osx') !== false) {
          $os_icon = 'macosx.png';
        } else $os_icon = '';
        if ($os_icon != '') $os_icon = '<img src="'.OS_ICONS.'/'.$os_icon.'" title = "'. $parser->osname .'" style="margin-right: 10px;" />'; else $os_icon = $parser->osname;

        $browser_data =  $browser_icon . $os_icon;
      }
    }
    elseif ($html_referer) {
      $browser_data = '?';
    } else $browser_data = $bot_title;

    echo "<tr class='", $index % 2 == 0 ? "even" : "odd", "'>";
    echo '<td class="time">', date ('Y-m-d H:i:s', $log_item ['time']), '</td>';
    echo '<td class="ip-version" title="', $log_item ['ip-address'], '">', strpos ($log_item ['ip-address'], ':') !== false ? '<span style="color: #0b0;">6</span>' : '4', '</td>';
    echo '<td class="ip-address">', $log_item ['ip-address'], '</td>';
    echo '<td class="country"><span class="flag-icon flag-icon-', strtolower ($log_item ['country']), '" style="width: 21px; height: 16px; margin-bottom: 1px;"></span><span style="vertical-align: middle;">', $log_item ['country'], '</span></td>';
    echo '<td class="bot" title="', strip_tags ($log_item ['user-agent']), '">', $bot_data, '</td>';
    echo '<td class="visitor">', $browser_data, '</td>';
    if ($show_referer) {
      if ($html_referer) {
        echo '<td class="referer html" title="', $encoded_referer, '">', $log_item ['referer'], '</td>';
      } else
        echo '<td class="referer" title="', $log_item ['referer'], '"><a href="', $log_item ['referer'], '">', $log_item ['referer'], '</a></td>';
    }
    echo "</tr>\n";
  }
  echo "</table></div>\n";
}

function statistics () {
  if (file_exists (LOG_FILE)) {
   $log = unserialize (file_get_contents (LOG_FILE));
  } else $log = array ();

  $countries = array ();
  foreach ($log as $index => $log_item) {
    $country = $log_item ['country'];
    if (isset ($countries [$country])) $countries [$country] ++; else $countries [$country] = 1;
  }
  arsort ($countries);

  echo '<div id="world-map" style="width: 100%; height: 400px"></div>';

  echo '
<script src="includes/jvectormap/jquery-jvectormap-2.0.3.min.js"></script>
<script src="includes/jvectormap/jquery-jvectormap-world-mill.js"></script>
';
  echo "<script type='text/javascript'>
  var visits = {\n";

  foreach ($countries as $country => $count) {
    if ($country == '') $country = 'ZZ';
    echo "    ", $country, ": ", number_format (100 * $count / count ($log), 2),",\n";
  }

  echo "};

  function initialize_map () {
    $(function (){
      $('#world-map').vectorMap({
        map: 'world_mill',
        backgroundColor: '#ddd',
        series: {
          regions: [{
            values: visits,
            scale: ['#C8EEFF', '#0071A4'],
//            scale: ['#c0c0c0', '#202080'],
            normalizeFunction: 'polynomial'
          }]
        },
        onRegionTipShow: function(e, el, code){
          var flag_class = 'flag-icon flag-icon-'+ code.toLowerCase();
          var country_data = '';
          if (typeof visits [code] != 'undefined') {
            country_data = ' ('+ visits [code] + ' % visits)';
          }
          el.html ('<span class=\'' + flag_class + '\' style=\'width: 20px; height: 15px;\'></span>' + el.html() + country_data);
        }
      });
    });
  }

  jQuery(document).ready(function($) {
    initialize_map ();
  });

</script>";

  echo '<div class="statistics"><table cellpadding="0" cellspacing="0" >';
  $percentage0 = array_shift (array_values ($countries)) / count ($log);
  foreach ($countries as $country => $count) {
    echo '<tr><td><span class="flag-icon flag-icon-', strtolower ($country), '" style="width: 28px; height: 21px;"></span></td>';
    $percentage = $count / count ($log);
    echo '<td>', $country, '</td><td class="percentage">', number_format (100 * $percentage, 2), ' %</td><td class="bar"><div style="width: ', 100 * $percentage / $percentage0, '%;"></div></td></tr>';
    echo "</tr>\n";
  }
  echo '</table></div>';
}

if (isset ($_GET ['page'])) {
  switch ($_GET ['page']) {
    case 'about':
      echo '<p>This is a simple script to analyze web traffic. It analyzes and logs real-time web visitors: UTC time of visit, country, IP address and user agent of the web client.';
      break;
    default:
      main ();
      break;
    case 'statistics':
      statistics ();
      break;
  }
} else main ();

?>
    <div id="footer">
      Armal - Automatic Real-time Monitor And Log :: <a href="?page=about">About</a>
    </div>
  </div>
</body>
</html>

