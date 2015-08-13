<?php
/*
 *  */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: providews Lock/Unlock to GLPI items
// ----------------------------------------------------------------------


function initRunkit( $runkitDef ) {
    foreach($runkitDef as $classname => $fcts) {
        $tkt = new $classname; // to force autoload of class
        // copy the original methods to backups
        foreach( $fcts as $fctname ) {
            $ret = runkit_method_copy( $classname, "pluginLock_".$fctname."_original", $classname, $fctname ) ;
        }
        // load new class definition
        $ret = runkit_import( strtolower( "inc/runkit.$classname.class.php" ), RUNKIT_IMPORT_CLASSES | RUNKIT_IMPORT_OVERRIDE ) ;

    }
} 

function plugin_init_lock() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['lock'] = true;

   Plugin::registerClass('PluginLockLock', array('classname' => 'PluginLockLock'));

   initRunkit(   array( 'Ticket' => array( 'showForm' ),
                        'Computer' => array( 'showForm' ) ,
                        'Reminder' => array( 'showForm' ) 
                      ) 
             ) ;


   // old way of doing things :)
   //$PLUGIN_HOOKS['pre_show_item']['lock'] = array('Ticket' => array('PluginLockLock', 'pre_show_item_lock'),
   //   'Computer' => array('PluginLockLock', 'pre_show_item_lock'));
   //$PLUGIN_HOOKS['post_show_item']['lock'] = array('Ticket' => array('PluginLockLock', 'post_show_item_lock'),
   //   'Computer' => array('PluginLockLock', 'post_show_item_lock'));

   $PLUGIN_HOOKS['post_init']['lock'] = 'plugin_lock_postinit';
}

// Get the name and the version of the plugin - Needed
function plugin_version_lock() {
   return array(
      'name' => "Lock",
      'version' => "3.0",
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
   if (!extension_loaded('runkit')) { // Your configuration check
       echo "PHP 'runkit' module is needed to run 'lock' plugin, please add it to your php config.";
       return false;
   }
   return true;
}

// Check configuration process for plugin : need to return true if succeeded
// Can display a message only if failure and $verbose is true
function plugin_lock_check_config($verbose = false) {
   global $LANG;

   if (extension_loaded('runkit')) { // Your configuration check
      return true;
   } 

   if ($verbose) {
      echo "PHP 'runkit' module is needed to run 'lock' plugin, please add it to your php config.";
   }

   return false;
}


?>