<?php

/**
 * Summary of Computer
 * This class is a patch for GLPI Computer default class
 * It is replacing the hooks that were previously used in Computer class
 * pre_show_item and post_show_item
 */
class Computer {
    /**
     * Summary of showForm
     * Hook for Computer::showForm()
     * It will be executed into the Computer class context
     * @param mixed $ID 
     * @param mixed $options 
     * @return mixed
     */
    function showForm( $ID, $options ){
        if( $this->getFromDB($ID) && $this->can($ID,'w') ) {
            PluginLockLock::pre_show_item_lock( $this ) ;
        }
        $ret = $this->pluginLock_showForm_original( $ID, $options ) ;
        PluginLockLock::post_show_item_lock( null ) ;
        return $ret ;
    }
}