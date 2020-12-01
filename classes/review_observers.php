<?php

namespace block_course_reviews_v2;

class review_observers {
    /**
     * Иницирует запросы на обновление записей в таблице feedback_completed
     * @param \core\event\base $event экземпляр события review_updated
     * @return void
     */
    public static function review_updated($event) {
        $formdata = $event->other['formdata'];

        $reviewids = $formdata['reviewids'];
        if (isset($formdata['isvisible']))
            $isvisible = $formdata['isvisible'];
        else $isvisible = array();

        $notvisible = array();

        foreach ($reviewids as $reviewid) {
            if (!in_array($reviewid, $isvisible))
                array_push($notvisible, $reviewid);
        }

        if (count($isvisible))
            db_request::update_user_reviews_isvisible_value($isvisible, 1);
        if (count($notvisible))
            db_request::update_user_reviews_isvisible_value($notvisible, 0);
    }
}