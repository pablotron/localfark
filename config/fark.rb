$config = {
  # output status to stdout
  :verbose    => true,

  # web site settings
  :user_agent => 'INSERT_USER_AGENT_HERE',
  :url        => 'http://www.fark.com/',

  # command to run (built from options above)
  :command    => 'wget -qO- -U \'__USER_AGENT__\' \'__URL__\'',
  
  # local cache settings
  :use_local_cache? => false,
  :local_cache      => 'fark.html',
  
  # database settings
  :use_db?    => true,
  :db         => {
    :user       => 'INSERT_DB_USER',
    :pass       => 'INSERT_DB_PASSWORD',
    :host       => 'localhost',
    :db         => 'INSERT_DB_NAME',
    :table      => 'localfark',
  },

  # rss settings
  :use_rss?   => true,
  :rss_file   => 'fark-%s.rss' % [Time.now.strftime('%Y%m%d-%H')],
}
