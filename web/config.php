<?php

#########################################################################
# config.php - localfark web interface config file                      #
#########################################################################

$LOCALFARK_CONFIG = array(
  # default sort options
  'sort'  => 'Time',
  'max'   => 100,
  'order' => 'DESC',
  'ofs'   => 0,

  # database options
  'db'  => array(
    'user'  => 'user',
    'pass'  => 'pass',
    'host'  => 'localhost',
    'db'    => 'db',
    'table' => 'table',
  ),

  # show forum link?
  'show_comments_link' => false,
);

?>

