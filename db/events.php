<?php
$observers = array(
    array(
        'eventname'   => '\block_course_reviews_v2\event\review_updated',
        'callback'    => '\block_course_reviews_v2\review_observer::review_updated'
    )
);
