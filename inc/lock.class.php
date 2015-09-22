<?php
/*
 *  */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: To provide Lock/Unlock to GLPI items
// ----------------------------------------------------------------------

// Class of the defined type
class PluginLockLock extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_plugin_lock_locks';
   public $type = 'PluginLockLock';

   // Should return the localized name of the type
   static function getTypeName() {
      return 'Lock';
   }

   /**
    * Give localized information about 1 task
    *
    * @param $name of the task
    *
    * @return array of strings
    */
   static function cronInfo($name) {
      //      global $LANG;

      switch ($name) {
         case 'unlock' :
            return array('description' => "Unlock forgotten locked objects!", //$LANG['plugin_lock']['cron']['description'],
               'parameter' => "Timeout to force unlocks (hours)" //$LANG['plugin_lock']['cron']['timeout']
            );
      }
      return array();
   }

   /**
    * Execute 1 task manage by the plugin
    *
    * @param $task CronTask class for log / stat
    *
    * @return integer
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   static function cronUnlock($task) {
      global $DB;
      // here we have to delete old locks
      //   	$ret = print_r($task, true);
      $actionCode = 0; // by default
      //$task->log("Lock log message from unlock cron");
      $task->setVolume(0); // start with zero
      $query = "SELECT * FROM `glpi_plugin_lock_locks` WHERE `lockdate` < '" . date("Y-m-d H:i:s", time() - ($task->fields['param'] * 60 * 60)) . "'";
      $ret = $DB->query($query);
      if ($ret && $DB->numrows($ret) > 0) {
         while ($row = $DB->fetch_assoc($ret)) {
            $query_del = "DELETE FROM `glpi_plugin_lock_locks` WHERE id=" . $row['id'];
            $ret_del = $DB->query($query_del);
            if ($ret_del && $DB->affected_rows() > 0) {
               $task->addVolume(1);
               $task->log("Unlock object type: " . $row['itemtype'] . " id: " . $row['items_id'] . " user: " . $row['users_id'] . " lockdate: " . $row['lockdate']);
               Log::history($row['items_id'], $row['itemtype'], array(0, '', 'Forced unlock from cronUnlock')); // Ticket ; $row['itemtype'] ;

            } else
               return -1; // error can't delete record, then exit with error
            //$DB->free_result($ret_del);
         }
         $actionCode = 1;
         $DB->free_result($ret);
      }
      return $actionCode;

   }

   static function displayLockMessage( $item, $row ) {
       global $CFG_GLPI;
                  echo "<div class='box' style='margin-bottom:20px;'>";
                  echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'>";
                  echo "</div></div></div>";
                  echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter'>";
                  echo "<h3><span class='red'>" . $item->getType() . " has been locked by '" . $row['name'] . "' since '" . $row['lockdate'] . "'!</span></h3>";
                  echo "<h3><span class='red'>To request unlock, click -> <a href=\"mailto:" . $row['email'] . "?subject=Please unlock item: " . $item->getType() . " " . $item->getID() . "&body=Hello,%0A%0ACould you go to this item and unlock it for me?%0A%0A" . $CFG_GLPI['url_base'] . "/?redirect=" . $item->getType() . "_" . $item->getID() . "%0A%0AThank you,%0A%0ARegards,%0A%0A" . $_SESSION['glpifirstname'] . "\">" . $row['name'] . "</a></span></h3>";
                  echo "</div></div></div>";
                  echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'>";
                  echo "</div></div></div>";
                  echo "</div>";

                  // changes profile to prevent write to item
                  $_SESSION['glpi_plugin_lock_former_profile'] = $_SESSION['glpiactiveprofile'];
                  $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_read_only_profile'];
	}
   
   /**
    * sets a lock on $item
    * when in post-only profile or when in creation mode, there is no lock
    * when in ajax calls for showing a tab, then just check if $item is locked and sets profile
    * when in a showForm call, then runs the workflow
    *
    * @param $item Object to lock
    *
    */
   static function pre_show_item_lock($item) {
      global $DB, $CFG_GLPI, $LANG;
      //echo ("pre_show_item_lock: ".$item->getID()) ;

      if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk' || $item->getID() < 0) {
          return; // when in post-only profile or when in creation mode, then there is no locking mechanism.
      }

      if ((isset($_REQUEST['_glpi_tab']) && $_REQUEST['_glpi_tab'] != $item->getType().'$main')
          || isset($_REQUEST['glpi_tab']) ) {
         // in this case we just do a verification to know if we authorize write
         // we check if linked item is locked or not and we prevent write only if locking user <> from current user
         //echo "Linked Item type=".$item->getType()." Items_id=".$item->getID() ;
         $query = "SELECT glpi_plugin_lock_locks.*, glpi_users.name, glpi_useremails.email 
					FROM `glpi_plugin_lock_locks` 
                     left join glpi_users on glpi_users.id = glpi_plugin_lock_locks.users_id
                     left join glpi_useremails on (glpi_users.id = glpi_useremails.users_id and glpi_useremails.is_default = 1)
					WHERE glpi_plugin_lock_locks.`items_id` = " . $item->getID() . " AND glpi_plugin_lock_locks.`itemtype` = '" . $item->getType() . "' AND glpi_plugin_lock_locks.`users_id` <> " . Session::getLoginUserID();
         $ret = $DB->query($query);
         if ($ret && $DB->numrows($ret) == 1) {
			$row = $DB->fetch_assoc($ret);
			if( ! isset($_REQUEST['glpi_tab']) ) 
                self::displayLockMessage( $item, $row ) ;
            // // changes profile to prevent write to item
            // $_SESSION['glpi_plugin_lock_former_profile'] = $_SESSION['glpiactiveprofile'];
            // $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_read_only_profile'];
         }
      } elseif ($item->getID() && in_array($item->getType(), array('Ticket', 'Computer', 'Reminder'))) {
         // tries to lock item
         $ret = $DB->query("
            SELECT *
            FROM `glpi_plugin_lock_locks`
            WHERE `items_id` = " . $item->getID() . " AND `itemtype` LIKE '".$item->getType()."'
         ");
         if ($ret && $DB->numrows($ret) == 0) {
            $DB->query("
               INSERT INTO `glpi_plugin_lock_locks` (
                  `items_id`,
                  `itemtype`,
                  `users_id`,
                  `lockdate`
               ) VALUES (
                  " . $item->getID() . ",
                  '" . $item->getType() . "',
                  " . Session::getLoginUserID() . ",
                  '" . date("Y-m-d H:i:s") . "'
               )
            ");
         }
         $last_id = $DB->insert_id();
         if (!$last_id) {
            // means the lock can't been set on this item means the item is already locked by someone else
            // then gets the row to show the user name and date of lock.
            $query = "SELECT glpi_plugin_lock_locks.*, glpi_users.name, glpi_useremails.email
                     FROM `glpi_plugin_lock_locks`
                     left join glpi_users on glpi_users.id = glpi_plugin_lock_locks.users_id
                     left join glpi_useremails on (glpi_users.id = glpi_useremails.users_id and glpi_useremails.is_default = 1)
                     WHERE `items_id` = " . $item->getID() . " AND `itemtype` = '" . $item->getType() . "'";
            $ret = $DB->query($query);
            if ($ret && $DB->numrows($ret) == 1) {
               $row = $DB->fetch_assoc($ret);

               // alerts user that the item is locked
               //   	  		displayLockMessage("<h3><span class='red'>".$item->getType()." has been locked by '".$row['name']."' since '".$row['lockdate']."'!</span></h3>") ;
               if (Session::getLoginUserID() <> $row['users_id']) {
					self::displayLockMessage( $item, $row ) ;
                  // echo "<div class='box' style='margin-bottom:20px;'>";
                  // echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'>";
                  // echo "</div></div></div>";
                  // echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter'>";
                  // echo "<h3><span class='red'>" . $item->getType() . " has been locked by '" . $row['name'] . "' since '" . $row['lockdate'] . "'!</span></h3>";
                  // echo "<h3><span class='red'>To request unlock, click -> <a href=\"mailto:" . $row['email'] . "?subject=Please unlock item: " . $item->getType() . " " . $item->getID() . "&body=Hello,%0A%0ACould you go to this item and unlock it for me?%0A%0A" . $CFG_GLPI['url_base'] . "/?redirect=" . $item->getType() . "_" . $item->getID() . "%0A%0AThank you,%0A%0ARegards,%0A%0A" . $_SESSION['glpifirstname'] . "\">" . $row['name'] . "</a></span></h3>";
                  // echo "</div></div></div>";
                  // echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'>";
                  // echo "</div></div></div>";
                  // echo "</div>";

                  // // changes profile to prevent write to item
                  // $_SESSION['glpi_plugin_lock_former_profile'] = $_SESSION['glpiactiveprofile'];
                  // $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_read_only_profile'];
               } else {
                  echo("<script type='text/javascript'>
									function UnlockIt(item, type, num){
										if( confirm('Unlock this item: '+type+' #'+num+'?') ){
										  	var oReq = new XMLHttpRequest();
						  					oReq.open('GET', '../plugins/lock/unlock.php?id='+ item , false);  // synchronous request
						  					oReq.send();
											if( oReq.status == 200 ) {
												//alert('Item unlocked!');
												if( confirm('Item unlocked!\\r\\n\\r\\nReload page?') ) window.location.reload(true);
												}
											else
												alert('Item NOT unlocked! Contact your GLPI admin!');
										}
									}
								</script>");
                  echo "<div class='box' style='margin-bottom:20px;'>";
                  echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'>";
                  echo "</div></div></div>";
                  echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter'>";
                  echo "<h3><span class='red'>" . $item->getType() . " has been open by you in another page since '" . $row['lockdate'] . "'!</span></h3>";
                  echo "<h3><span class='red'>Can't edit sorry!</span></h3>";
                  echo "<h3><span class='red'>To unlock, click -> <a href=\"javascript:UnlockIt(" . $row['id'] . ",'" . $item->getType() . "'," . $item->getID() . ")\">unlock " . $item->getType() . " #" . $item->getID() . "</a></span></h3>";
                  echo "</div></div></div>";
                  echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'>";
                  echo "</div></div></div>";
                  echo "</div>";

                  //die("");
                  // changes profile to prevent write to item
                  $_SESSION['glpi_plugin_lock_former_profile'] = $_SESSION['glpiactiveprofile'];
                  $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_read_only_profile'];

               }
            }
         } else {
            // install inline javascript to auto unlock item when user leaves the page  // &date=".rand()."
            echo("<script type='text/javascript'>
							function closeIt(){
							  	var oReq = new XMLHttpRequest();
			  					oReq.open('GET', '../plugins/lock/unlock.php?id=" . $last_id . "', false);  // synchronous request
			  					oReq.send();
			  					//alert(oReq.responseText);
							}
						window.onbeforeunload = closeIt;
						</script>");
         }
      }
   }

   /**
    * restores the current profile with the saved rights if previously saved
    * @param $item Object is not used, just checks profiles
    */
   static function post_show_item_lock($item) {
      if (isset($_SESSION['glpi_plugin_lock_former_profile'])) {
         //echo("Post show item." ) ;
         $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_former_profile'];
         unset($_SESSION['glpi_plugin_lock_former_profile']);
      }
   }

}
