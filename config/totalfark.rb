$config = {
  # output status to stdout
  :verbose    => true,

  # web site settings
  :user       => 'INSERT_TOTALFARK_USERNAME',
  :pass       => 'INSERT_TOTALFARK_PASSWORD',
  :user_agent => 'INSERT_USER_AGENT_HERE',
  :url        => 'http://www.totalfark.com/',

  # command to run (built from options above)
  :command    => 'wget -O- --http-user=__USER__ --http-passwd=__PASS__ -U \'__USER_AGENT__\' \'__URL__\'',
  
  # local cache settings
  :use_local_cache? => false,
  :local_cache      => 'totalfark.html',
  
  # database settings
  :use_db?    => true,
  :db         => {
    :user       => 'pabs',
    :pass       => 'PASSWORD',
    :host       => 'localhost',
    :db         => 'pablotron',
    :table      => 'localfark',
  },

  # rss settings
  :use_rss?   => true,
  :rss_file   => 'totalfark-%s.rss' % [Time.now.strftime('%Y%m%d-%H')],
}
