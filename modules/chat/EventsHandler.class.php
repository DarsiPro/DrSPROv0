<?php

namespace ChatModule;

class EventsHandler {
    static $support_events = array(
        "after_parse_global_markers"
    );

    function after_parse_global_markers($markers) {
        
        $markers['drs_chat'] = function() {
            return \ChatModule\ActionsHandler::form().\ChatModule\ActionsHandler::add_form();
        };
        
        return $markers;
    }
}