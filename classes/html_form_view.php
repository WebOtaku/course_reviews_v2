<?php

namespace block_course_reviews_v2;

use moodle_url;
use html_writer;

class html_form_view
{
    /**
     * Формирует и возвращает html форму для таблицы "Отзывы на курс"
     * @param string $submit_url
     * @param int $courseid
     * @return string html строка
     * */
    public static function get_html_form_feedback_table_params($submit_url, $courseid, $value = 0) {
        global $PAGE;

        if (constants::$IDNUMBER_RANGE[0] <= constants::$IDNUMBER_RANGE[1]) {
            $min = constants::$IDNUMBER_RANGE[0];
            $max = constants::$IDNUMBER_RANGE[1];
        }
        else {
            $min = constants::$IDNUMBER_RANGE[1];
            $max = constants::$IDNUMBER_RANGE[0];
        }

        if (!$value) $value = $min;

        $html_str = '
            <form method="get" action="' . $submit_url . '" class="m-1">
                <div class="form-inline text-xs-right">
                    <label for="idnumber" class="mr-1">ИД активности "Обратная связь" ('.$min.' - '.$max.')</label>
                    <input type="number" name="idnumber" id="idnumber" class="form-control" 
                           value="'.$value.'" min="'.$min.'" max="'.$max.'"/>
                    <button type="submit" class="btn btn-secondary">Отобразить</button>
                    ' . html_writer::input_hidden_params(new moodle_url($PAGE->url, array('courseid' => $courseid))) . '
                </div>
            </form>
        ';

        return $html_str;
    }
}
?>