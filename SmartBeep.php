#!/usr/bin/php -q
<?php
/*

    SmartBeep(TM) PageIT Plus for PHP
    Copyright 2000 Jeremy Brand  <jeremy@nirvani.net>
    http://www.jeremybrand.com/Jeremy/Brand/Jeremy_Brand.html

    SmartBeep(TM) PageIT Plus for PHP.
    Release 1.0.1
    http://www.nirvani.net/software/

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    ChangeLog:
      1.0.0 -> 1.0.1 (2000-12-31)
        - fixed a missing \r\n at the end of the HTTP request.
          (This was not following standard HTTP protocol)

   
    ################      N O T E S      ##############################
    This program was inspired by the want for me to get emails on my 
    SmartBeep (http://www.smartbeep.com) pager instead of using their silly
    web form each time.

    I have put this script into my .qmail file (I'm sure there is a .forward
    equivilant).  See below for my example.

    -- snip -- 
    |./SmartBeep_PageIT_Plus.php 
    -- snip --

 */
      /********** CONFIG *********************/
      $pager_number = '5555555555';  // This is your pager number.  Don't
                                     // use any dashes or spaces or it won't
                                     // work.
      $message_length = 160;  // My Smartbeep service only allows 160 chars.
      $host = 'www.smartbeep.com';
      $port = 80;
      $action = '/cgi-bin/sendpage.cgi';
      $referer = 'http://www.smartbeep.com/smartbeep/send_page/sendpage_content.htm';
      $button_name = 'Send Page';

      /********** CONFIG *********************/

      function trim_message($message)
      {
        $in_headers = TRUE;
        $array = explode("\n",$message);

        while(list($key, $val) = each($array))
        { 
          $line = trim($val);
          if($in_headers)
          {
            if (ereg('^Subject:(.*)', $line, $hits))
              $buf .= 'S:' . $hits[1] . "\n";
            else if (ereg('^From:(.*)', $line, $hits))
              $buf .= 'F:' . substr($hits[1],0,15) . "\n";
            else if ($val == '')
              $in_headers = FALSE;
          }
          else
            $buf .= $line;
        }
        return $buf;
      }

      function urlize_array($values_array)
      {
        if (is_array($values_array))
          while(list($key,$val) = each($values_array))
            $string .= urlencode($key) . "=" . urlencode($val) . "&";
          $string = substr($string, 0, -1);
        return $string;
      }

      function post_method($host, $port=80, $action, $data_to_send, $referer='')
      {
        $reply = '';
        $data_length = strlen($data_to_send);
        $fd = fsockopen($host, $port, $errno, $errstr, 30);
        if ($fd)
        {
          if (trim($referer) != '')
            $ref = "Referer: $referer\r\n";

          $out = ''
               . "POST $action HTTP/1.1\r\n"
               . "Host: $host\r\n"
               . "User-Agent: SmartBeep(TM) PageIT Plus for PHP (http://www.nirvani.net/software/)\r\n"
               . $ref
               . "Content-type: application/x-www-form-urlencoded\r\n"
               . 'Content-length: '.$data_length."\r\n"
               . "Connection: close\r\n"
               . "\r\n"
               . $data_to_send
               . "\r\n"
               . "\r\n";

          fwrite($fd, $out, strlen($out));
          unset($out);
          while(!feof($fd))
          {
            $reply .= fread($fd, 128);
          }
          fclose($fd);
          unset($fd);
        }
        return $reply;
      }


      $fd = fopen('/dev/stdin', 'r');
      if ($fd)
      {
        while(!feof($fd))
          $buf .= fgets($fd, 2048);
        fclose($fd);
      }
      else
        exit();

      $buf = substr(trim_message($buf), 0, $message_length);

      $data_to_send = array('MapId' => $pager_number,
                            'MapMsg' => $buf,
                            'sendid' => $button_name);

      post_method($host, $port, $action, urlize_array($data_to_send), $referer) . "\n";

?>
