--
-- Table structure for table 'localfark'
--

CREATE TABLE localfark (
  ID int(11) NOT NULL auto_increment,
  Url text NOT NULL,
  Source varchar(255) NOT NULL default '',
  Type varchar(255) NOT NULL default '',
  Description text NOT NULL,
  Forum varchar(255) NOT NULL default '',
  Time datetime NOT NULL default '0000-00-00 00:00:00',
  Status enum('none','accepted','rejected') NOT NULL default 'none',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

