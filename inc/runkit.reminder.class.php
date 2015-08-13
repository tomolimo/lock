<?php

/**
 * Summary of Reminder
 * This class is a patch for GLPI Reminder default class
 * It is replacing the hooks that were previously used in Reminder class
 * pre_show_item and post_show_item
 */
class Reminder {
    /**
     * Summary of showForm
     * Hook for Reminder::showForm()
     * It will be executed into the Reminder class context
     * @param mixed $ID 
     * @param mixed $options 
     * @return mixed
     */
    function showForm( $ID ){
        if( $this->getFromDB($ID) && $this->can($ID,'w') ) {
            PluginLockLock::pre_show_item_lock( $this ) ;
        }
        $ret = $this->pluginLock_showForm_original( $ID ) ;
        
        PluginLockLock::post_show_item_lock( null ) ;
        
        return $ret ;
    }
}