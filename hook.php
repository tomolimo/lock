<?php
/*
 *
 *  */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: to provide Lock/unlock to GLPI items
// ----------------------------------------------------------------------


// Install process for plugin : need to return true if succeeded
function plugin_lock_install() {
   global $DB;

   if (!TableExists("glpi_plugin_lock_locks")) {
      $query = "CREATE TABLE `glpi_plugin_lock_locks` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`items_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'RELATION to various table, according to itemtype (ID)',
		`itemtype` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',
		`users_id` INT(11) NOT NULL,
		`lockdate` TIMESTAMP NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE INDEX `item` (`itemtype`, `items_id`)
		)
		COLLATE='utf8_unicode_ci'
		ENGINE=InnoDB;";

      $DB->query($query) or die("error creating glpi_plugin_lock_locks " . $DB->error());

   }

   if(!TableExists("glpi_plugin_lock_configs")) {
      $query = "CREATE TABLE `glpi_plugin_lock_configs` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`read_only_profile_id` INT(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
		)
		COLLATE='utf8_unicode_ci'
		ENGINE=InnoDB;";

      $DB->query($query) or die("error creating glpi_plugin_lock_configs " . $DB->error());

   }

   // checks if the special LOCK profile exists in glpi_profiles table
   // if not then add it
   $query = "SELECT id FROM glpi_profiles WHERE name like 'Plugin Lock % Profile'";
   $result = $DB->query($query);
   if ($DB->numrows($result) == 0) {
       // profile is not existing must create an empty one that will be manually populated when needed
       $prof = new Profile ;
       $prof->add( array( 'name' => 'Plugin Lock Read-Only Profile',
                          'interface' => 'central', 
                          'is_default' => 0,
                          'comment' => "This profile is used to manage Lock/unlock of items and to give access to the Unlock form: do not use it for something else!\nDo not forget to set rights for this profile otherwise nothing will be viewable!"
                          )
                  ) ;
       $profile_id = $prof->getID() ; 
       if( $profile_id  > -1 ) {
           // will set profile rights
           $profRights = new ProfileRight ;

           $profRights->updateProfileRights( $profile_id, array( 'computer' => 1,
                                                                'monitor' => 1,
                                                                'ticket' => 1024,
                                                                'task' => 8193,
                                                                'ticketcost' => 1,
                                                                'followup' => 8193

                                                                )
                                            ) ;
       }

   } else {
      $row = $DB->fetch_assoc($result);
      $profile_id = $row['id'];
   }

   $query = "REPLACE INTO `glpi_plugin_lock_configs` (`id`, `read_only_profile_id`) VALUES (1, " . $profile_id . ")";
   $DB->query($query) or die("error inserting 'Plugin Lock Read-Only Profile' id into 'glpi_plugin_lock_configs' table!" . $DB->error());


   // To be called for each task the plugin manage
   // task in class
   CronTask::Register('PluginLockLock', 'unlock', DAY_TIMESTAMP, array('param' => 4, 'state' => CronTask::STATE_DISABLE, 'mode' => CronTask::MODE_EXTERNAL));
   return true;
}


// Uninstall process for plugin : need to return true if succeeded
function plugin_lock_uninstall() {
   global $DB;

   CronTask::Unregister('PluginLockLock');

   // Current version tables
   if (TableExists("glpi_plugin_lock_locks")) {
      $query = "DROP TABLE `glpi_plugin_lock_locks`";
      $DB->query($query) or die("error deleting glpi_plugin_lock_locks");
   }
   if (TableExists("glpi_plugin_lock_config")) {
       $query = "DROP TABLE `glpi_plugin_lock_config`";
       $DB->query($query) or die("error deleting glpi_plugin_lock_config");
   }
   if (TableExists("glpi_plugin_lock_configs")) {
       $query = "DROP TABLE `glpi_plugin_lock_configs`";
       $DB->query($query) or die("error deleting glpi_plugin_lock_configs");
   }

   return true;
}


function plugin_lock_postinit() {
   global $DB;

   if (isset($_SESSION['glpiname']) && !isset($_SESSION['glpi_plugin_lock_read_only_profile'])) {
      //   		echo("plugin_lock_postinit");
      $query = "SELECT * FROM glpi_plugin_lock_configs";
      $ret = $DB->query($query);
      if ($ret && $DB->numrows($ret) == 1) {
         $row = $DB->fetch_assoc($ret);

         $profile = new Profile();
         if ($profile->getFromDB($row['read_only_profile_id'])) {
            $profile->cleanProfile();
            $_SESSION['glpi_plugin_lock_read_only_profile'] = $profile->fields;

            $_SESSION['glpi_plugin_lock_read_only_profile']['entities'] = array(0 => array('id' => 0,
               'name' => '',
               'is_recursive' => 1));
         }
      }
   }

}

?>