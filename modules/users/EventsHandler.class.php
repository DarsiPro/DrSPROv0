<?php

namespace UsersModule;

class EventsHandler {
    
    static $support_events = array(
        "add_admin_hchart"
    );
    
    
    
    // !!! помоему add_admin_hchart используется только в админке, там своя функция с аналогичным именем, а здесь она не нужна и нужно ее удалить!
    
    
    function add_admin_hchart($hcharts) {
        
        
        
        $cnt_usrs = getDB()->select('users', DB_COUNT);
        $groups_info = array();
        $users_groups = \ACL::get_group_info();
        if (!empty($users_groups)) {
            foreach ($users_groups as $key => $group) {
                if ($key === 0)
                    continue;
                
                $groups_info[] = array(
                    "name" => $group['title'],
                    "y" => (int)(getDB()->select('users', DB_COUNT, array('cond' => array('status' => $key))))
                );
            }
        }
        
        
        $hcharts[] = array(
            "order" => 1000,
            "is_row" => false,
            "title" => false,
            "body" => include('admin/homehcharts.php')
        );
        return $hcharts;
    }
}