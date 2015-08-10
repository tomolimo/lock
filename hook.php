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
		`lockdate` DATETIME NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE INDEX `item` (`itemtype`, `items_id`)
		)
		COLLATE='utf8_unicode_ci'
		ENGINE=MyISAM;";

      $DB->query($query) or die("error creating glpi_plugin_lock_locks " . $DB->error());

   }

   if (!TableExists("glpi_plugin_lock_config")) {
      $query = "CREATE TABLE `glpi_plugin_lock_config` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`read_only_profile_id` INT(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
		)
		COLLATE='utf8_unicode_ci'
		ENGINE=MyISAM;";

      $DB->query($query) or die("error creating glpi_plugin_lock_config " . $DB->error());

   }

   // checks if the special LOCK profile exists in glpi_profiles table
   // if not then add it
   $query = "SELECT id FROM glpi_profiles WHERE name like 'Plugin Lock % Profile'";
   $result = $DB->query($query);
   if ($DB->numrows($result) == 0) {
      $DB->query("
         INSERT INTO `glpi_profiles` (`name`, `interface`, `is_default`, `computer`, `monitor`, `software`, `networking`, `printer`, `peripheral`, `cartridge`, `consumable`, `phone`, `notes`, `contact_enterprise`, `document`, `contract`, `infocom`, `knowbase`, `knowbase_admin`, `faq`, `reservation_helpdesk`, `reservation_central`, `reports`, `ocsng`, `view_ocsng`, `sync_ocsng`, `dropdown`, `entity_dropdown`, `device`, `typedoc`, `link`, `config`, `rule_ticket`, `entity_rule_ticket`, `rule_ocs`, `rule_ldap`, `rule_softwarecategories`, `search_config`, `search_config_global`, `check_update`, `profile`, `user`, `user_authtype`, `group`, `entity`, `transfer`, `logs`, `reminder_public`, `bookmark_public`, `backup`, `create_ticket`, `delete_ticket`, `add_followups`, `group_add_followups`, `global_add_followups`, `global_add_tasks`, `update_ticket`, `update_priority`, `own_ticket`, `steal_ticket`, `assign_ticket`, `show_all_ticket`, `show_assign_ticket`, `show_full_ticket`, `observe_ticket`, `update_followups`, `update_tasks`, `show_planning`, `show_group_planning`, `show_all_planning`, `statistic`, `password_update`, `helpdesk_hardware`, `helpdesk_item_type`, `ticket_status`, `show_group_ticket`, `show_group_hardware`, `rule_dictionnary_software`, `rule_dictionnary_dropdown`, `budget`, `import_externalauth_users`, `notification`, `rule_mailcollector`, `date_mod`, `comment`, `validate_ticket`, `create_validation`, `calendar`, `sla`, `rule_dictionnary_printer`, `clean_ocsng`, `update_own_followups`, `delete_followups`, `entity_helpdesk`, `show_my_problem`, `show_all_problem`, `edit_all_problem`, `problem_status`, `create_ticket_on_login`, `tickettemplate`, `ticketrecurrent`)
         VALUES ('Plugin Lock Read-Only Profile','central',0,'r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','0','r','0','r','r',NULL,'r',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'r','r',NULL,'0','0','0','0','0','0','0','0','0','0','0','1','1','1','1','0','0','0','0','0','1',NULL,0,'[]',NULL,'1','0',NULL,NULL,'r',NULL,NULL,NULL,'2013-04-16 17:26:27','This profile is used to manage Lock/unlock of items: do not use for something else!','0','0',NULL,NULL,NULL,'r','0','0',NULL,'0','0','0',NULL,0,NULL,NULL)
      ") or die("error creating 'Plugin Lock Read-Only Profile' into 'glpi_profiles' table!" . $DB->error());
      $profile_id = $DB->insert_id();
   } else {
      $row = $DB->fetch_assoc($result);
      $profile_id = $row['id'];
   }

   $query = "REPLACE INTO `glpi_plugin_lock_config` (`id`, `read_only_profile_id`) VALUES (1, " . $profile_id . ")";
   $DB->query($query) or die("error inserting 'Plugin Lock Read-Only Profile' id into 'glpi_plugin_lock_config' table!" . $DB->error());


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

   return true;
}


function plugin_lock_postinit() {
   global $DB;

   if (!isset($_SESSION['glpi_plugin_lock_read_only_profile'])) {
      //   		echo("plugin_lock_postinit");
      $query = "SELECT * FROM glpi_plugin_lock_config";
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