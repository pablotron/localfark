#!/usr/bin/env ruby

#########################################################################
# localfark.rb - scrape totalfark.com and save it to a RSS file and/or  #
# a MySQL database                                                      #
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

$LOCALFARK_VERSION = '0.2.0'

require 'parsedate'

class String
  def escape_html
    str = self.dup
    str.gsub!(/&/, '&amp;') if str =~ /&/
    str.gsub!(/</, '&lt;') if str =~ /</
    str.gsub!(/>/, '&gt;') if str =~ />/
    str
  end
end

class FarkLink
  attr_accessor :url, :status

  def initialize(url, src, type, desc, forum, hour, status)
    @url = url
    @src = src
    @type = type
    @desc = desc
    @forum = forum
    @hour = hour
    @status = status
  end

  def time
    '%s %02d:00:00' % [$date, @hour]
  end

  def to_sql(table)
    "INSERT INTO #{table}(Url,Source,Type,Description,Forum,Time,Status) " <<
    "VALUES " <<
    "('#{Mysql::escape_string(@url)}', '#{Mysql::escape_string(@src)}', " <<
    "'#{Mysql::escape_string(@type)}', '#{Mysql::escape_string(@desc)}', " <<
    "'#{Mysql::escape_string(@forum)}', '#{Mysql::escape_string(time)}', " <<
    "'#{@status}')"
  end

  def to_rss
    ['  <item>',
     "    <title>#{@type}: #{@desc}</title>",
     "    <date>#{time}</date>",
     "    <link>#{@url}</link>", 
     '    <description>',
     "      &lt;p&gt;#{@desc.escape_html}&lt;/p&gt;",
     "      &lt;p&gt;&lt;i&gt;&lt;a href='#{@forum}'&gt;Comments&lt;/a&gt;",
     "      &lt;br /&gt;",
     "      &lt;p&gt;&lt;i&gt;Status: #{@status}&lt;br /&gt;",
     "      Source: #{@src.escape_html}&lt;/i&gt;&lt;/p&gt;",
     '    </description>',
     '  </item>',
     '',
    ].join("\n")
  end
end
    
def has_url?(db, table, url)
  r = db.query "SELECT Url from #{table} WHERE Url = " <<
               "'#{Mysql::escape_string(url)}'"
  r && r.num_rows > 0
end

#######################################################################
#######################################################################

# load config file
CONFIG_PATH = ENV['HOME'] << '/.localfark.rb'
unless test ?e, CONFIG_PATH
  $stderr.puts 'Missing "$HOME/.localfark.rb".  Please read README.'
  exit -1
end
load CONFIG_PATH, true

puts "LocalFark #{$LOCALFARK_VERSION} started." if $config[:verbose]

# load command
cmd = $config[:command].dup
{ 'USER'        => $config[:user], 
  'PASS'        => $config[:pass],
  'USER_AGENT'  => $config[:user_agent],
  'URL'         => $config[:url],
}.each { |key, val| cmd.gsub!(/__#{key}__/, val) if cmd =~ /__#{key}__/ }
  

# connect to database
if $config[:use_db?]
  require 'mysql'
  puts 'Connecting to database' if $config[:verbose]
  DB = $config[:db]
  db = Mysql::connect(DB[:host], DB[:user], DB[:pass], DB[:db])
end

# load/leech totalfark html
tf = ''
if $config[:use_local_cache?]
  tf = File::open($config[:local_cache]).readlines.join ''
else
  puts "Grabbing \"#{$config[:url]}\"." if $config[:verbose]
  IO::popen(cmd, 'r') { |io| tf = io.readlines.join '' }
end

# get date
date_ary = nil
dates = tf.scan(/<td class="dateheader" align=left width="33%">([^:<]+):<\/td>/)
dates.each { |date| puts date }
date_ary = ParseDate::parsedate(dates[0][0])
$date = '%04d-%02d-%02d' % date_ary
puts "Grabbed date: #$date" if $config[:verbose]

# scan links; warning PURE EVIL REGEX AHEAD ;)
links = []
puts 'Scanning links...' if $config[:verbose]
hour = '00'
tf.scan(/(\d+:\d+ hours)|<td class="nilink" align=right width="120"><a onMouseOver="window.status='([^']+?)' ; return true;" onMouseOut="window.status=''; return true;" href="[^"]+" target=_blank>(.+?)<\/a><\/td>\s<td class="nilink" align=center width=38><IMG SRC="[^"]+" WIDTH=54 HEIGHT=11 ALT="\[([^\]]+)]"><\/td>\s<td class="nilink" align=left>(<font color="#\d{6}">)?([^<]+?)(<\/font>)?<\/td>\s<td class="nilink" align=center width=64><a href="([^"]+?)">/m) { |hours, url, src, type, font, desc, term_font, forum|
  if hours =~ /(\d+):\d+ hours/
    hour = $1
  else
    status = if font
        if font =~ /"#800000"/
          'rejected'
        elsif font =~ /"#008000"/
          'accepted'
        else
          'none'
        end
      else
        'none'
      end
    src.gsub!(/http:\/\/img.fark.com\//, '') if src =~ /http:\/\/img.fark.com\//
    url.gsub!(/%3f/, '?') if url =~ /%3f/
    url.gsub!(/%26/, '&') if url =~ /%26/

    links << FarkLink.new(url, src, type, desc, forum, hour, status)
  end
}

# save to database
if $config[:use_db?]
  puts 'Inserting links into database...' % links.size if $config[:verbose]
  inserted = 0
  links.each { |link|
    if has_url?(db, DB[:table], link.url)
      db.query "UPDATE %s SET Status = '%s' WHERE Url = '%s'" %
               [DB[:table], 
                Mysql::escape_string(link.status),
                Mysql::escape_string(link.url)]
    else 
      db.query link.to_sql(DB[:table]) 
      inserted += 1
    end
  }
  puts 'Inserted %d of %d links into database.' % [inserted, links.size] if $config[:verbose]
end

# save to rss file
if $config[:use_rss?]
  puts 'Saving RSS file "%s".' % $config[:rss_file] if $config[:verbose]
  File::open($config[:rss_file], 'w') { |file|
    file.puts "<?xml version='1.0' encoding='iso-8859-1'?>",
              "<rss version='0.92'>",
              '  <channel>',
              '    <title>LocalFark</title>',
              '    <site>http://www.totalfark.com/</site>',
              '    <description>',
              '      RSS-ified screen scrape of Totalfark.',
              '    </description>',
              '  </channel>',
              ' '
    links.each { |link| file.puts link.to_rss }
    file.puts '</rss>'
  }
end
