<?php

#########################################################################
# index.php - localfark web interface                                   #
#                                                                       #
# Copyright (C) 2003 Paul Duncan, and various contributors.             #
#                                                                       #
# Permission is hereby granted, free of charge, to any person           #
# obtaining a copy of this software and associated documentation files  #
# (the "Software"), to deal in the Software without restriction,        #
# including without limitation the rights to use, copy, modify, merge,  #
# publish, distribute, sublicense, and/or sell copies of the Software,  #
# and to permit persons to whom the Software is furnished to do so,     #
# subject to the following conditions:                                  #
#                                                                       #
# The above copyright notice and this permission notice shall be        #
# included in all copies of the Software, its documentation and         #
# marketing & publicity materials, and acknowledgment shall be given    #
# in the documentation, materials and software packages that this       #
# Software was used.                                                    #
#                                                                       #
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,       #
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF    #
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND                 #
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY      #
# CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,  #
# TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE     #
# SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.                #
#                                                                       #
#########################################################################

function draw_nav($draw_page_index = false) {
  global $sort, $max, $order, $ofs, $num_rows;
  $base = "?sort=$sort&amp;max=$max&amp;order=$order&amp;ofs=";

  echo "<div class='nav'>\n";
  if ($ofs > 0)
   echo "<a href='$base" . ($ofs - $max) . "'>Previous</a> |\n";
  else
    echo "<u>Previous</u> |\n";

  if ($ofs + $max < $num_rows)
    echo "<a href='$base" . ($ofs + $max) . "'>Next</a>\n";
  else
    echo "<u>Next</u>\n";

  if ($draw_page_index) {
    echo "<div class='page-index'>Page: \n";
    $page = 1;
    for ($i = 0; $i < $num_rows; $i += $max) {
      if ($ofs >= $i && $ofs < $i + $max)
        echo "<u>$page</u> \n";
      else
        echo "<a href='$base" . $i . "'>$page</a> \n";
      $page++;
    }
    echo "</div>";
  }

  echo "</div>\n";
}

function draw_select($title, $options) {
  echo "<select name='$title'>\n";

  foreach ($options as $key => $val) 
    echo "  <option value='$key'" . 
         (($GLOBALS[$title] == $key) ? ' SELECTED' : '') . 
         ">$val</option>\n";
  echo "</select>\n\n";
}

function db_connect($opts) {
  $db = mysql_connect($opts['host'], $opts['user'], $opts['pass']) or
    die(__LINE__ . ": Couldn't connect to db: " . mysql_error() . ".\n");
  mysql_select_db($opts['db'], $db) or
    die(__LINE__ . ": Couldn't select db: " . mysql_error() . ".\n");

  return $db;
}

#######################################################################
#######################################################################

# load config file
require 'config.php';

# set default values
$keys = array('sort', 'max', 'order', 'ofs');
foreach ($keys as $i => $val)
  if (!$GLOBALS[$val])
    $GLOBALS[$val] = $LOCALFARK_CONFIG[$val];

$db = db_connect($LOCALFARK_CONFIG['db']);

echo "<?xml version='1.0' encoding='iso-8859-1'?>\n"; 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
  "http://www.w3.org/TR/xhtml10/DTD/xhtml10.dtd">

<html>
  <head>
    <title>LocalFark</title>
    <link title='default' href='style.css'
      rel='stylesheet' type='text/css' />
  </head>

  <body>
    <div class='titlebar'>
      <a class='title' href='.'>LocalFark</a>
      <div class='subtitle'>Impatience is a virtue.</div>
    </div>

    <div class='sortbar'>
      <form id='sortform' action='.' method='get'>
        Display
        <input type='entry' size='3' name='max' value='<? echo $max; ?>' />
        results, sorted by 
        <?php
        $order_vals = array('DESC'  => 'Descending',
                            ' '     => 'Ascending');
        draw_select('order', &$order_vals);

        $sort_vals = array('Time'         => 'Time',
                           'Id'           => 'ID',
                           'Type'         => 'Type',
                           'Source'       => 'Source',
                           'Description'  => 'Description');
        draw_select('sort', &$sort_vals);
        ?>.
        Filter on
        <input type='entry' name='filter' value='<?php echo $filter ?>' />
        <input type='submit' name='submit' value='Refresh' />
      </form>
    </div>

<?php
    # sanitize values
    $order = preg_match('/^(as|des)c$/', $order) ? $order : 'DESC';
    $sort = preg_match('/^\w+$/', $sort) ? $sort : 'Time';
    $ofs = preg_match('/^\d+$/', $ofs) ? $ofs : 100;
    $max = preg_match('/^\d+$/', $max) ? $max : 100;

    # build query
    $table = $LOCALFARK_CONFIG['db']['table'];
    $query = "SELECT Type,Time,Source,Description,Url,Status,Forum FROM $table";
    $limit = "ORDER BY $sort $order LIMIT $ofs, $max";
    if ($filter) {
      $filter = mysql_escape_string($filter);
      $query .= " WHERE Description LIKE '%$filter%' ";
    }

    # get num rows
    $r = mysql_query($query, $db) or
      die(__LINE__ . ": Couldn't query db: " . mysql_error($db) . "\n");
    $num_rows = mysql_num_rows($r);

    # query db
    $r = mysql_query("$query $limit", $db) or
      die(__LINE__ . ": Couldn't query db: " . mysql_error($db) . "\n");

    draw_nav();
?>
    <table>
    <tr class='header'>
      <td class='src'>Source</td>
      <td class='type'>Type</td>
      <td class='time'>Time</td>
      <td class='desc'>Description</td>
      <td>&nbsp;</td>
    </tr>
<?php
    # iterate over and print each result
    while ($o = mysql_fetch_object($r)) {
      $even = ($i++ % 2) ? 'odd' : 'even';
      $time = preg_replace('/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/', 
                           '\2/\3 \4:\5', $o->Time);
      $status = $o->Status;

      echo "<tr class='$even-row'>\n" .
           "  <td class='src'>" . $o->Source . "</td>\n" .
           "  <td class='type'><img src='images/" . strtolower($o->Type) .
           ".gif' width='54' height='11' alt='[" . $o->Type . "]' /></td>\n" . 
           "  <td class='time'>" . $time . "</td>\n" .
           "  <td class='desc-$status'>" . $o->Description . "</td>\n" .
           "  <td class='url'><a href='" . $o->Url . "'>Go</a>\n";
      if ($LOCALFARK_CONFIG['show_comments_link'])
        echo "<a class='url' href='" . $o->Forum . "'>Comments</a>\n";
      echo "</td>\n</tr>\n";
    }
?>
    </table>

  <?php draw_nav(true); ?>

  </body>
</html>
