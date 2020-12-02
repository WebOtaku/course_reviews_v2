<?php

namespace block_course_reviews_v2;

use moodle_url;
use html_writer;

class html_form_view
{
    /**
     * Формирует и возвращает html форму для таблицы "Отзывы на курс".
     * Форма содержит поле для ввода idnumber.
     * @param string $submit_url - url по которому будет осуществляется отправка формы.
     * @param int $courseid - id курса.
     * @param int $value - дефолтное значение для поля ввода idnumber.
     * @return string html строка.
     * */
    public static function get_html_form_feedback_table_params($submit_url, $courseid, $value = 0) {
        global $PAGE;

        if (constant::$IDNUMBER_RANGE[0] <= constant::$IDNUMBER_RANGE[1]) {
            $min = constant::$IDNUMBER_RANGE[0];
            $max = constant::$IDNUMBER_RANGE[1];
        }
        else {
            $min = constant::$IDNUMBER_RANGE[1];
            $max = constant::$IDNUMBER_RANGE[0];
        }

        if (!$value) $value = $min;

        $html_str = '
            <form method="get" action="' . $submit_url . '" class="m-1">
                <div class="form-inline text-xs-right">
                    <label for="idnumber" class="mr-1">'. get_string('idnumberfieldlabel', 'block_course_reviews_v2') .' ('.$min.' - '.$max.')</label>
                    <input type="number" name="idnumber" id="idnumber" class="form-control" 
                           value="'.$value.'" min="'.$min.'" max="'.$max.'"/>
                    <button type="submit" class="btn btn-secondary">'.get_string('idnumbersubmitbutton', 'block_course_reviews_v2').'</button>
                    ' . html_writer::input_hidden_params(new moodle_url($PAGE->url, array('courseid' => $courseid))) . '
                </div>
            </form>
        ';

        return $html_str;
    }
}
?>