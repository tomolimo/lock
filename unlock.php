<?php
// here we are going to try to unlock the given object
// url should be of the form: 'http://.../.../unlock.php?item=ticket_xxxxxx'
// which means that object type is ticket and id of the objet is xxxxxx
// OR url should be of the form: 'http://.../.../unlock.php?id=yyyyyy'
// which means that lock id is yyyyyy

/**
 *  Database class for Mysql
 **/
class DBmysql {
   // fake class to permit to load db config
}

include('../../config/config_db.php');

if (isset($_GET["item"]) || isset($_GET["id"])) {
   // then we may have something to unlock

   if (isset($_GET["item"]))
      $Object = explode("_", $_GET["item"]);
   else $Object = $_GET["id"];

   header("Content-Type: text/html; charset=UTF-8");
   header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // HTTP/1.1
   header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe

   //echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
   //           \"http://www.w3.org/TR/html4/loose.dtd\">";
   //echo "\n<html><head>\n";
   //echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";

   //// Send extra expires header
   //echo "<meta http-equiv='Expires' content='Fri, Jun 12 1981 08:20:00 GMT'>\n";
   //echo "<meta http-equiv='Pragma' content='no-cache'>\n";
   //echo "<meta http-equiv='Cache-Control' content='no-cache'>\n";
   //echo "</head>\n";
   //echo "<body>\n";

   if (isset($_GET["item"])) {
      echo ("object type: " . $Object[0] . "\n");
      echo ("id: " . $Object[1] . "\n");
   } else echo ("id: " . $Object . "\n"); //<br>");

   //gets DB connection
   $unlockDB = new DB;

   $link = mysql_connect($unlockDB->dbhost, $unlockDB->dbuser, $unlockDB->dbpassword);

   if ($link) {
      mysql_select_db($unlockDB->dbdefault);

      if (isset($_GET["item"]))
         $query = "DELETE FROM `glpi_plugin_lock_locks` WHERE `itemtype` = '" . ucfirst($Object[0]) . "' AND `items_id` = '" . $Object[1] . "';";
      else
         $query = "DELETE FROM `glpi_plugin_lock_locks` WHERE `id` = '" . $Object . "';";

      //		echo $query."\n" ;

      $result = mysql_query($query, $link);

      echo "unlocked row: " . mysql_affected_rows($link) . "\n";

      mysql_close($link);
   } else
      echo("Can't connect to DB\n");

  // echo "</body></html>";
}

?>