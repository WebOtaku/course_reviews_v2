<?php

use block_course_reviews_v2\html_list_view;

class block_course_reviews_v2 extends block_base {
    public function init() {
        $this->title = get_string('course_reviews_v2', 'block_course_reviews_v2');
    }

    // Задаёт содержимое для блоков
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if ($this->check_capability()) {
            $links = [
                '/blocks/course_reviews_v2/table_view.php',
                '/blocks/course_reviews_v2/table_view.php'
            ];

            $linksparams = [
                array('courseid' => $COURSE->id),
                array('courseid' => $COURSE->id, 'is_scos' => true)
            ];

            $langfile = 'block_course_reviews_v2';
            $keyslangfile = ['reviews_table', 'scos_reviews_table'];

            $this->content->text .= html_list_view::get_html_list_links($links, $linksparams, $langfile, $keyslangfile);
        }


        return $this->content;
    }

    /**
     * Проверяет есть ли право "block/course_reviews:addinstance" у текущего пользователя
     * @return bool да/нет
     * */
    private function check_capability() {
        global $DB, $COURSE;

        if (!$course = $DB->get_record('course', array('id' => $COURSE->id))) {
            print_error('invalidcourse', 'block_course_reviews_v2', $COURSE->id);
        }

        $context = context_course::instance($COURSE->id);

        return has_capability('block/course_reviews_v2:addinstance', $context);
    }

    // Позволяет ограничить отображение блока конкретными форматами страниц
    // /course/view.php => course-view
    // site-index - главная страница
    public function applicable_formats() {
        return array('course-view' => true);
    }

    // Позволяет добавлять несколько таких блоков в один курс
    public function instance_allow_multiple() {
        return false;
    }
}