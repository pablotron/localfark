<?php

$DB_OPTS = array(
  'user'  => 'pabs',
  'pass'  => 'PASSWORD',
  'host'  => 'localhost',
  'db'    => 'DB_NAME',
  'table' => 'localfark',
);

function draw_nav() {
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

  echo "</div>\n";
}

function draw_select($title, $options) {
  echo "<select name='$title'>\n";

  foreach ($options as $key => $val) 
    echo "  <option value='$key'" . 
         (($GLOBALS[$title] == $key) ? ' SELECTED' : '') . 
         ">$val</option\n";
  echo '</select>';
}

# set default values
if (!isset($sort))
  $sort = 'Time';
if (!isset($max))
  $max = 100;
if (!isset($order))
  $order = 'DESC';
if (!isset($ofs))
  $ofs = 0;

# connect to db
$db = mysql_connect($DB_OPTS['host'], $DB_OPTS['user'], $DB_OPTS['pass']) or
  die(__LINE__ . ": Couldn't connect to db: " . mysql_error() . ".\n");
mysql_select_db($DB_OPTS['db'], $db) or
  die(__LINE__ . ": Couldn't select db: " . mysql_error() . ".\n");

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
      <h1 class='title'>LocalFark</h1>
      <div class='subtitle'>Impatience is a virtue.</div>
    </div>

    <form action='.' method='get'>
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

<?php
    # sanitize values
    $order = preg_match('/^(as|des)c$/', $order) ? $order : 'DESC';
    $sort = preg_match('/^\w+$/', $sort) ? $sort : 'Time';
    $ofs = preg_match('/^\d+$/', $ofs) ? $ofs : 100;
    $max = preg_match('/^\d+$/', $max) ? $max : 100;

    # build query
    $table = $DB_OPTS['table'];
    $query = "SELECT Type,Time,Source,Description,Url,Status FROM $table";
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
           "  <td class='url'><a href='" . $o->Url . "'>Go</a>\n" .
           "</tr>\n";
    }
?>
    </table>

  <?php draw_nav(); ?>

  </body>
</html>
