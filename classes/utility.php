<?php

namespace block_course_reviews_v2;

class utility {
    /**
     * Представляет содержимое отзывов в удобном для вывовода виде.
     * @param array $raw_fbvalues - ассоциативный массив объектов хранящих содержимое отзывов (ответы).
     * @return array массив вида:
     * array (
     *     [fbcid] => array(
     *         [fbiname] => <var>, - fbv.value
     *     )
     * )
     * */
    public static function prepare_fbvalues($raw_fbvalues) {
        $fbvalues = array();

        foreach ($raw_fbvalues as $fbvalue)
            $fbvalues[$fbvalue->fbcid][$fbvalue->fbiname] = $fbvalue->fbvvalue;

        return $fbvalues;
    }

    /**
     * Возвращает типы полей в отзыве.
     * @param array $raw_fbvalues - ассоциативный массив объектов хранящих содержимое отзывов (ответы).
     * @return array массив вида:
     * array(
     *     [fbiname] => <string>, - fbi.typ
     * )
     * */
    public static function get_fbvalues_types($raw_fbvalues) {
        $fbvalues_types = array();

        foreach ($raw_fbvalues as $fbvalue)
            $fbvalues_types[$fbvalue->fbcid][$fbvalue->fbiname] = $fbvalue->fbityp;

        return array_shift($fbvalues_types);
    }

    /**
     * Фильтрует отзывы пришедший с СЦОС по времени, оставляя только те,
     * что были рамещены позже указанной временной метки.
     * @param array $raw_scos_course_reviews - ассоциативный массив объектов хранящих содержимое отзывов c СЦОС.
     * @param array $unix_time - время в формате UNIX метки.
     * @return array отфильтрованный по времени $raw_scos_course_reviews.
     * */
    public static function filter_raw_scos_course_reviews_by_rated_at($raw_scos_course_reviews, $unix_time) {
        $filtered_raw_scos_course_reviews = array();

        foreach ($raw_scos_course_reviews as $raw_scos_course_review) {
            $timestamp = date_create($raw_scos_course_review->rated_at);
            $timestamp = date_timestamp_get($timestamp);

            if ($timestamp > $unix_time) {
                $filtered_raw_scos_course_reviews[] = $raw_scos_course_review;
            }
        }

        return $filtered_raw_scos_course_reviews;
    }

    /**
     * Проверяет чтобы каждому типу данных из массива $valid_field_types соотвествовало поле
     * из массива $fbitems.
     * @param array $fbitems - ассоциативный массив элементов (полей) используемых в отзыве.
     * @param array $valid_field_types - валидные типы данных для полей используемых в отзыве.
     * @return bool указанные поля имеют требуемые типы - да/нет.
     * */
    public static function check_fbitems_has_valid_types($fbitems, $valid_field_types) {
        $is_valid = false;

        foreach ($fbitems as $fbitem) {
            $index = array_search($fbitem->fbityp, $valid_field_types);
            if ($index >= 0 && $index !== false) {
                $is_valid = true;
                array_splice($valid_field_types, $index, 1);
            }
            else {
                $is_valid = false;
                break;
            }
        }

        if (count($valid_field_types)) $is_valid = false;

        return $is_valid;
    }

    /**
     * Проверяет поля указанного экземпляра модуля feedback на сответствие требованиям:
     * - кол-во полей == constant::$NUM_REVIEW_FIELDS
     * - поля имеют требуемый тип (constant::$VALID_FIELDS_TYPES)
     * @param object $fb  - ассоциативный массив элементов (полей) используемых в отзыве.
     * @return bool указанные поля имеют требуемые типы - да/нет.
     * */
    public static function check_fb_validity($fb) {
        $fbitems = db_request::get_review_items($fb->fbid);

        return count($fbitems) == constant::$NUM_REVIEW_FIELDS ||
            utility::check_fbitems_has_valid_types($fbitems, constant::$VALID_FIELDS_TYPES);
    }

    /**
     * Проверяет строку на то является ли она целым числом или нет.
     * @param string $str  - строка, которая проверяется на целое число.
     * @param array $range  - массив содержащий диапазон, в который должно попадать целое число.
     * @return bool $str является целым числом - да/нет.
     * */
    public static function str_is_int($str, $range = array(0, 0)) {
        $int_num = intval($str);

        if ($range[0] || $range[1])
            $is_int = $int_num && (($int_num >= $range[0] && $int_num <= $range[1]) || ($int_num >= $range[1] && $int_num <= $range[0]));
        else $is_int = $int_num;

        return !!$is_int;
    }

    /*public static function add_cur_time_to_date($date) {
        $timestamp = new DateTime($date);
        $timenow = new DateTime();
        $hnow = $timenow->format("H");
        $mnow = $timenow->format("i");
        $snow = $timenow->format("s");
        $addinterval = new DateInterval('PT'.$hnow.'H'.$mnow.'M'.$snow.'S');
        $timestamp->add($addinterval);
        $timestamp = $timestamp->getTimestamp();

        return $timestamp;
    }*/
}