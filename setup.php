<?php
/*
 *  */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: provides Lock/Unlock to GLPI items
// ----------------------------------------------------------------------


function plugin_init_lock() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['lock'] = true;

   Plugin::registerClass('PluginLockLock', array('classname' => 'PluginLockLock'));

    //old way of doing things :)
   $PLUGIN_HOOKS['pre_show_item']['lock'] = array(
        'Ticket' => array('PluginLockLock', 'pre_show_item_lock'),
        'Computer' => array('PluginLockLock', 'pre_show_item_lock'),
        'Reminder' => array('PluginLockLock', 'pre_show_item_lock')
        );
   $PLUGIN_HOOKS['post_show_item']['lock'] = array(
        'Ticket' => array('PluginLockLock', 'post_show_item_lock'),
        'Computer' => array('PluginLockLock', 'post_show_item_lock'),
        'Reminder' => array('PluginLockLock', 'post_show_item_lock')
        );

   $PLUGIN_HOOKS['post_init']['lock'] = 'plugin_lock_postinit';
}

// Get the name and the version of the plugin - Needed
function plugin_version_lock() {
   return array(
      'name' => "Lock",
      'version' => "3.1.1",
      'license' => "GPLv2+",
      'author' => "Olivier Moron",
      'minGlpiVersion' => "0.83+"
   );
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_lock_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '0.83', 'lt')) {
      echo "This plugin requires GLPI >= 0.83";
      return false;
   }
   $plug = new Plugin ;
   if (!$plug->isActivated('mhooks') || version_compare( $plug->fields['version'], '1.1.0', '<') ) { 
       echo "'mhooks 1.1.0' plugin is needed to run 'lock' plugin, please add it to your GLPI plugin configuration.";
       return false;
   } 

   return true;
}

// Check configuration process for plugin : need to return true if succeeded
// Can display a message only if failure and $verbose is true
function plugin_lock_check_config($verbose = false) {
   global $LANG;

   $plug = new Plugin ;
   if ($plug->isActivated('mhooks') && version_compare( $plug->fields['version'], '1.1.0', '>=')) { // Your configuration check
       return true;
   } 

   if ($verbose) {
       echo "'mhooks' plugin is needed to run 'lock' plugin, please add it to your GLPI plugin configuration.";
   }

   return false;
}


?>