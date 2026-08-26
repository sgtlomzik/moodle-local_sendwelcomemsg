<?php
namespace local_sendwelcomemsg;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function user_created(\core\event\user_created $event) {
        global $DB;
        $userid = $event->objectid;
        $record = (object)[
            'userid' => $userid,
            'timecreated' => time()
        ];
        $DB->insert_record('local_sendwelcomemsg_queue', $record);
    }
}
