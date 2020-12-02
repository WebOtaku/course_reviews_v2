<?php

global $CFG, $PAGE, $OUTPUT, $DB;

use block_course_reviews_v2\db_request;
use block_course_reviews_v2\event\review_updated;
use block_course_reviews_v2\scos_api_interact;
use block_course_reviews_v2\utility;
use block_course_reviews_v2\constant;
use block_course_reviews_v2\html_form_view;
use block_course_reviews_v2\view_template;

require_once('../../config.php');;
require_once "$CFG->libdir/tablelib.php";
require_once "reviews_table.php";

$courseid = required_param('courseid', PARAM_INT);
$is_scos = optional_param('is_scos', 0, PARAM_BOOL);
$download = optional_param('download', '', PARAM_ALPHA);
$idnumber = (string) optional_param('idnumber', '', PARAM_INT);

$idnumber = (utility::str_is_int($idnumber,
    array(constant::$IDNUMBER_RANGE[0], constant::$IDNUMBER_RANGE[1])))? $idnumber : 0;

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_course_reviews_v2', $courseid);
}

$context = context_course::instance($courseid);

require_login($course);

$page_url_params = array('courseid' => $courseid);

if ($is_scos)
    $page_url_params['is_scos'] = $is_scos;

$page_url = new moodle_url('/blocks/course_reviews_v2/table_view.php', $page_url_params);

if ($is_scos)
    $page_title = get_string('scos_course_reviews_v2', 'block_course_reviews_v2');
else
    $page_title = get_string('course_reviews_v2', 'block_course_reviews_v2');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url($page_url);

// Проверка прав пользователя
if (has_capability('block/course_reviews_v2:addinstance', $context)) {

    if (!$idnumber) {
        view_template::print_page_header($page_title, $page_url, $is_scos, $idnumber);
        echo html_form_view::get_html_form_feedback_table_params($page_url, $courseid);
        view_template::print_page_footer();
    }
    else {
        if ($fb = db_request::get_feedback_by_idnumber($courseid, $idnumber)) {
            if(utility::check_fb_validity($fb)) {

                // TODO: переместить вставку отзывов с CЦОС в указанное место
                $raw_scos_course_reviews = scos_api_interact::get_course_feedback($fb->fbid);
                db_request::insert_scos_course_reviews($raw_scos_course_reviews, $fb->fbid);

                if ($is_scos) {
                    // TODO: место для вставки отзывов с СЦОС
                    $fbvalues = db_request::get_user_reviews_values_by_courseid($fb->fbid, true);
                } else $fbvalues = db_request::get_user_reviews_values_by_courseid($fb->fbid, false);


                // Проверка на наличие выводимых значений
                if (count($fbvalues)) {
                    /*print_object(db_request::get_scos_course_reviews(
                        $fb->fbid, true,
                        array('offset' => 0, 'limit' => 8),
                        array('field' => 'timemodified', 'order' => 'DESC')));*/
                    
                    if ($is_scos) {
                        $optional_columns = array('timemodified');
                        $optional_headers = array(
                            get_string('timemodified', 'block_course_reviews_v2')
                        );
                    } else {
                        $optional_columns = array('userid', 'firstname', 'lastname', 'timemodified');
                        $optional_headers = array(
                            get_string('userid', 'block_course_reviews_v2'),
                            get_string('firstname', 'block_course_reviews_v2'),
                            get_string('lastname', 'block_course_reviews_v2'),
                            get_string('timemodified', 'block_course_reviews_v2')
                        );
                    }

                    $table = new reviews_table('uniqueid', $fbvalues, $courseid, $idnumber, $optional_columns, $optional_headers);

                    $table->is_downloading($download, $page_title, $page_title);

                    if (!$table->is_downloading()) {
                        // Отображать только в случае если не было запроса на скачивание таблицы
                        view_template::print_page_header($page_title, $page_url, $is_scos, $idnumber);
                        echo html_form_view::get_html_form_feedback_table_params($page_url, $courseid, $idnumber);
                    }

                    // Обработка нажатия кнопки "Сохранить" (Save)
                    if (count($_POST)) {
                        $params = array(
                            'context' => $context,
                            'other' => array(
                                'formdata' => $_POST
                            )
                        );

                        $event = review_updated::create($params);
                        $event->trigger();
                    }

                    if ($is_scos) {
                        // запрос для таблицы с отзывами с СЦОС
                        $fields = '
                            fbc.id AS fbcid, fbc.timemodified, fbc.courseid AS isvisible
                        ';
                        $from = '
                            {feedback_completed} AS fbc
                            JOIN {feedback} AS fb ON fb.id = fbc.feedback
                        ';
                        $where = 'fb.id = :fbid AND fbc.userid = 1';
                    } else {
                        // запрос для таблицы с отзывами
                        $fields = '
                            fbc.id AS fbcid, fbc.userid, u.firstname, u.lastname, 
                            fbc.timemodified, fbc.courseid AS isvisible
                        ';
                        $from = '
                            {feedback_completed} AS fbc
                            JOIN {feedback} AS fb ON fb.id = fbc.feedback
                            JOIN {user} AS u ON u.id = fbc.userid
                        ';
                        $where = 'fb.id = :fbid AND u.id <> 2 AND u.id <> 1';
                    }

                    $params = array('fbid' => $fb->fbid);

                    $table->set_sql($fields, $from, $where, $params);
                    $table->define_baseurl($page_url);

                    $table->out(8, true);

                    if (!$table->is_downloading()) {
                        view_template::print_page_footer();
                    }
                } else view_template::print_message($page_title, $page_url,
                    get_string('emptytablemessage', 'block_course_reviews_v2'), $is_scos, $idnumber);

            } else redirect(new moodle_url($page_url, array('idnumber' => 0)));

        } else redirect(new moodle_url($page_url, array('idnumber' => 0)));
    }
} else view_template::print_message($page_title, $page_url,
    get_string('nopermissionmessage', 'block_course_reviews_v2'), $is_scos, $idnumber);
