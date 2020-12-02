<?php
namespace block_course_reviews_v2;

/*require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/blocks/scos/locallib.php');*/

class scos_api_interact {
    /**
     * Запрашивает с помощью API СЦОС отзывы по текущему курсу.
     * Структура возвращаемого результата:
     *     array(
     *         [index] => object {
     *             [rating]    => <integer>,
     *             [text]      => <string>,
     *             [is_expert] => <integer>
     *             [rated_at]  => <date YY-MM-DD>
     *         }
     *     ).
     * @param int $fbid - id модуля "Обратная связь" (feedback) в таблице feedback.
     * @return array массив объектов, согласно структуре возвращаемого результата.
     * */
    public static function get_course_feedback($fbid) {
        $fbusers = db_request::get_user_reviews_users_by_courseid($fbid, false);
        $raw_fbvalues = db_request::get_user_reviews_values_by_courseid($fbid, false);
        $fbvalues = utility::prepare_fbvalues($raw_fbvalues);
        $course_feedback = [];

        $i = 0;
        foreach ($fbusers as $user) {
            $course_feedback[$i] = new \stdClass();
            $course_feedback[$i]->rating = $fbvalues[$user->fbcid]['Оцените курс по шкале от 1 до 100'];
            $course_feedback[$i]->text = $fbvalues[$user->fbcid]['Комментарий к оценке'];
            $course_feedback[$i]->is_expert = 1;
            $course_feedback[$i]->rated_at = date('Y-m-d', $user->timemodified);
            $i++;
        }

        return $course_feedback;
    }

    /**
     * Запрашивает с помощью API СЦОС отзывы по текущему курсу.
     * Структура возвращаемого результата:
     *     array(
     *         [index] => object {
     *             [rating]    => <integer>,
     *             [text]      => <string>,
     *             [is_expert] => <integer>
     *             [rated_at]  => <date YY-MM-DD>
     *         }
     *     ).
     * @param int $courseid - id курса
     * @return array массив объектов, согласно структуре возвращаемого результата.
     * */
    /*public static function get_course_feedback($courseid) {
        $course_feedback = block_scos_get_feedback($courseid);
        return $course_feedback;
    }*/
}
?>