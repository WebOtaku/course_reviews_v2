<?php

namespace block_course_reviews_v2;

use moodle_url;
use html_writer;

class view_template {
    /**
     * Выводить заголовок страницы.
     * @param string $page_title - заголовок страницы.
     * @param string $page_url - url страницы.
     * */
    public static function print_page_header($page_title, $page_url, $is_scos = 0, $idnumber = 0) {
        global $PAGE, $OUTPUT;
        $PAGE->set_title($page_title);
        $PAGE->set_heading($page_title);
        $PAGE->navbar->add($page_title, $page_url);
        echo $OUTPUT->header();

        $url = new moodle_url($page_url, array('is_scos' => !$is_scos, 'idnumber' => $idnumber));

        if (!$is_scos)
            $html_link = html_writer::link($url, get_string('scos_course_reviews_v2', 'block_course_reviews_v2'));
        else
            $html_link = html_writer::link($url, get_string('course_reviews_v2', 'block_course_reviews_v2'));

        echo $OUTPUT->heading($page_title . ' ('. $html_link .')');
    }

    /**
     * Выводить "подвал" страницы.
     * */
    public static function print_page_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /**
     * Выводить сообщение. Использовать если нужно вывести сообщение заместого
     * иного содержимого текущей страницы (напр: сообщение об ошибке).
     * @param string $page_title - заголовок страницы.
     * @param string $page_url - url страницы.
     * @param string $message - выводимое сообщение.
     * */
    public static function print_message($page_title, $page_url, $message = '', $is_scos = 0, $idnumber = 0) {
        self::print_page_header($page_title, $page_url, $is_scos, $idnumber);
        echo '<h2>'.$message.'</h2>';
        self::print_page_footer();
    }
}