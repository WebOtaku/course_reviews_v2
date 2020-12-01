<?php
namespace block_course_reviews_v2;

class db_request {
    public static function get_feedback_by_idnumber($courseid, $idnumber)  {
        global $DB;

        // SQL запрос на получение информации о пользователях из отзывов по текущему курсу
        $sql_request = '
            SELECT fb.id AS fbid
            FROM mdl_course_modules AS cm
            JOIN mdl_feedback AS fb ON fb.id = cm.instance
            WHERE cm.deletioninprogress = 0 AND cm.course = :courseid AND cm.idnumber = :idnumber AND cm.module = :moduleid
        ';

        $query_params = array();
        $query_params['courseid'] = $courseid;
        $query_params['idnumber'] = $idnumber;
        $query_params['moduleid'] = constants::$FEEDBACK_MODULE_ID;

        $query_result = $DB->get_record_sql($sql_request, $query_params);

        return $query_result;
    }

    /**
     * Запрашивает из таблициы feedback_completed пользователей
     * которые оставили отзывы по текущему курсу. Если не передавать
     * id курса, то вернёт всех пользователей, которые оставляли отзывы.
     * Структура возвращаемого результата:
     *     array(
     *         [fbcid] => array {
     *             [fbcid]        => <integer>, - fbc.id (feedback_completed (fbc))
     *             [fbid]         => <integer>, - fb.id (feedback (fb))
     *             [userid]       => <integer>, - u.id (user (u))
     *             [firstname]    => <string>,  - u.firstname
     *             [lastname]     => <string>,  - u.lastname
     *             [timemodified] => <integer>  - fbc.timemodified
     *         }
     *     ).
     * @param int $courseid - id курса.
     * @param int $is_scos - возвращать только содержимое отзывов с СЦОС.
     * @param int $isvisible - возвращать только отзывы доступные для отображения (courseid = 1).
     * @param int[] $pagination - массив параметров для пагинации при запросе (LIMIT offset, limit)
     * @param string[] $orderby - массив параметров для сортировки при запросе (ORDER BY field order)
     * @return array массив ассоциативных записей, согласно структуре возвращаемого результата.
     * */
    public static function get_user_reviews_users_by_courseid(
        $fbid = -1, $is_scos = -1, $isvisible = -1,
        $pagination = array('offset' => 0, 'limit' => 0),
        $orderby = array('field' => '', 'order' => 'DESC')
    ) {
        global $DB;

        // SQL запрос на получение информации о пользователях из отзывов по текущему курсу
        $sql_request = '
            SELECT fbc.id AS fbcid, fb.id AS fbid, u.id AS userid, u.firstname, u.lastname, fbc.timemodified
            FROM {feedback_completed} AS fbc
            JOIN {feedback} AS fb ON fb.id = fbc.feedback
            JOIN {user} AS u ON u.id = fbc.userid
            WHERE';

        $query_params = array();

        if ($is_scos !== -1 && $is_scos)
            $sql_request .= ' u.id = '.constants::$GUEST_USER_ID;
        else if ($is_scos !== -1 && !$is_scos)
            $sql_request .= ' u.id <> '.constants::$ADMIN_USER_ID.' AND u.id <> '.constants::$GUEST_USER_ID;
        else $sql_request .= ' u.id <> '.constants::$ADMIN_USER_ID;

        if ($fbid > 0) {
            $sql_request .= ' AND fb.id = :fbid';
            $query_params['fbid'] = $fbid;
        }

        if ($isvisible !== -1) {
            $sql_request .= ' AND fbc.courseid = :isvisible';
            $query_params['isvisible'] = ($isvisible)? 1 : 0;
        }

        if ($orderby['field'] !== '' && ($orderby['order'] == 'ASC' || $orderby['order'] == 'DESC')) {
            $sql_request .= ' ORDER BY ' . $orderby['field'] . ' ' . $orderby['order'];
        }

        if ($pagination['limit'] > 0 && $pagination['offset'] >= 0) {
            $sql_request .= ' LIMIT ';
            $sql_request .= $pagination['offset'];
            $sql_request .= ',' . $pagination['limit'];
        }

        if (count($query_params))
            $query_result = $DB->get_records_sql($sql_request, $query_params);
        else $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
    }
    /**
     * Запрашивает из таблициы feedback_item (fbi) и feedback_value (fbv)
     * содержимое (fbi.name, fbv.value) отзывов по текущему курсу.
     * Если не передавать id курса, то вернёт значения по всем отзывам, что есть.
     * Структура возвращаемого результата:
     *     array(
     *         [fbvid] => array {
     *             [fbvid]    => <integer>, - fbv.id
     *             [fbcid]    => <integer>, - fbc.id (feedback_completed (fbc))
     *             [fbid]     => <integer>, - fb.id (feedback (fb))
     *             [userid]   => <integer>, - fbc.userid
     *             [fbiname]  => <string>,  - fbi.name
     *             [fbvvalue] => <var>      - fbv.value
     *         }
     *     )
     * @param int $courseid - id курса
     * @param int $is_scos - возвращать только содержимое отзывов с СЦОС
     * @return array массив ассоциативных записей, согласно структуре возвращаемого результата
     * */
    public static function get_user_reviews_values_by_courseid($fbid = -1, $is_scos = -1) {
        global $DB;

        // SQL запрос на получение содержимого отзывов по текущему курсу
        $sql_request = '
            SELECT fbv.id AS fbvid, fbc.id AS fbcid, fb.id AS fbid, fbc.userid, fbi.typ AS fbityp, fbi.name AS fbiname, fbv.value AS fbvvalue
            FROM {feedback_completed} AS fbc
            JOIN {feedback} AS fb ON fb.id = fbc.feedback
            JOIN {feedback_item} AS fbi ON fbi.feedback = fb.id AND (fbi.typ = "'.constants::$RATING_FIELD_TYPE.'" OR fbi.typ = "'.constants::$TEXT_FIELD_TYPE.'")
            JOIN {feedback_value} AS fbv ON fbv.item = fbi.id AND fbv.completed = fbc.id
            WHERE';

        $query_params = array();

        if ($is_scos !== -1 && $is_scos)
            $sql_request .= ' fbc.userid = '.constants::$GUEST_USER_ID;
        else if ($is_scos !== -1 && !$is_scos)
            $sql_request .= ' fbc.userid <> '.constants::$ADMIN_USER_ID.' AND fbc.userid <> '.constants::$GUEST_USER_ID;
        else $sql_request .= ' fbc.userid <> '.constants::$ADMIN_USER_ID;

        if ($fbid > 0) {
            $sql_request .= ' AND fb.id = :fbid';
            $query_params['fbid'] = $fbid;
        }

        if (count($query_params))
            $query_result = $DB->get_records_sql($sql_request, $query_params);
        else $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
    }

    /**
     * Запрашивает из таблициы feedback_item (fbi) и feedback_value (fbv)
     * содержимое (fbi.name, fbv.value) отзывов для указанных id отзывов.
     * Если не передавать id отзывов, то вернёт значения по всем отзывам, что есть.
     * Структура возвращаемого результата:
     *     array(
     *         [fbvid] => array {
     *             [fbvid]    => <integer>, - fbv.id
     *             [fbcid]    => <integer>, - fbc.id (feedback_completed (fbc))
     *             [fbid]     => <integer>, - fb.id (feedback (fb))
     *             [userid]   => <integer>, - fbc.userid
     *             [fbityp]   => <string>,  - fbi.typ
     *             [fbiname]  => <string>,  - fbi.name
     *             [fbvvalue] => <var>      - fbv.value
     *         }
     *     )
     * @param array $fbcids - массив id отзывов
     * @param int $is_scos - возвращать только содержимое отзывов с СЦОС
     * @return array массив ассоциативных записей, согласно структуре возвращаемого результата
     * */
    public static function get_user_reviews_values_by_fbcids($fbcids = array(), $is_scos = -1) {
        global $DB;

        // SQL запрос на получение содержимого отзывов по текущему курсу
        $sql_request = '
            SELECT fbv.id AS fbvid, fbc.id AS fbcid, fb.id AS fbid, fbc.userid, fbi.typ AS fbityp, fbi.name AS fbiname, fbv.value AS fbvvalue
            FROM {feedback_completed} AS fbc
            JOIN {feedback} AS fb ON fb.id = fbc.feedback
            JOIN {feedback_item} AS fbi ON fbi.feedback = fb.id AND (fbi.typ = "'.constants::$RATING_FIELD_TYPE.'" OR fbi.typ = "'.constants::$TEXT_FIELD_TYPE.'")
            JOIN {feedback_value} AS fbv ON fbv.item = fbi.id AND fbv.completed = fbc.id
            WHERE';

        $query_params = array();

        if ($is_scos !== -1 && $is_scos)
            $sql_request .= ' fbc.userid = '.constants::$GUEST_USER_ID;
        else if ($is_scos !== -1 && !$is_scos)
            $sql_request .= ' fbc.userid <> '.constants::$ADMIN_USER_ID.' AND fbc.userid <> '.constants::$GUEST_USER_ID;
        else $sql_request .= ' fbc.userid <> '.constants::$ADMIN_USER_ID;

        if (count($fbcids)) {
            $i = 0;
            $sql_request .= ' AND (';
            foreach ($fbcids as $fbcid) {
                if ($i)
                    $sql_request .= ' OR ';

                $sql_request .= 'fbc.id = ' . $fbcid;

                $i++;
            }
            $sql_request .= ')';
        }

        if (count($query_params))
            $query_result = $DB->get_records_sql($sql_request, $query_params);
        else $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
    }

    /**
     * Устанавливает для всех записей из $reviewids значение $isvisble
     * в таблице feedback_completed
     * @param array $reviewids - массив id-и в таблице feedback_completed
     * @param int   $isvisible - значение courseid в таблице feedback_completed
     * @return void
     * */
    public static function update_user_reviews_isvisible_value($reviewids = array(), $isvisible = 0) {
        global $DB;
        $sql_request = '
            UPDATE {feedback_completed}
            SET courseid = :isvisible
            WHERE ';

        for ($i = 0; $i < count($reviewids); $i++) {
            $sql_request .= 'id=' . $reviewids[$i];
            if ($i != count($reviewids) - 1) $sql_request .= ' OR ';
        }

        $DB->execute($sql_request, array('isvisible' => $isvisible));
    }

    /**
     * Запрашивает информацию об элементе курса "Отзывы на курс" (feedback)
     * @param int $courseid - id курса.
     * @return array массив вида:
     * array(
     *     [fbid] => object {
     *         [fbid] => <integer>, - fb.id
     *         [courseid] => <string>, - fb.course
     *         [fbname] => <string>, - fb.name
     *     }
     * )
     * */
    public static function get_course_reviews_info($fbid = -1) {
        global $DB;

        // SQL запрос на получение информации об элементе курса "Отзывы на курс" (feedback)
        $sql_request = '
            SELECT fb.id AS fbid, fb.course AS courseid, fb.name AS fbname
            FROM {feedback} AS fb
        ';

        if ($fbid > 0) {
            $sql_request .= 'WHERE fb.id = :fbid';
            $query_result = $DB->get_records_sql($sql_request, [ 'fbid' => $fbid ]);
        }
        else $query_result = $DB->get_records_sql($sql_request);

        return $query_result;
    }

    /**
     * Определяет и вовзращает наибольший id в таблице feedback_completed
     * для пользователя с id = 1 (СЦОС)
     * @param $fbid int - id модуля feedback в таблице feedback
     * @return int наибольший id в таблице feedback_completed
     * */
    public static function get_max_scos_course_review_timemodified($fbid) {
        global $DB;

        // SQL запрос на получение наибольшего значения поля timemodified
        // для userid = 1 в таблице feedback_completed
        $sql_request = '
            SELECT MAX(timemodified) AS maxtm
            FROM {feedback_completed}
            WHERE userid = 1 AND feedback = :fbid
        ';

        return $DB->get_record_sql($sql_request, array('fbid' => $fbid));
    }

    /**
     * Определяет и вовзращает наибольший id в таблице feedback_completed
     * @return int наибольший id в таблице feedback_completed
     * */
    public static function get_max_course_review_id() {
        global $DB;
        // SQL запрос на получение наибольшего значения поля id
        // в таблице feedback_completed
        $sql_request = '
            SELECT MAX(id) AS maxid
            FROM {feedback_completed}
        ';

        return $DB->get_record_sql($sql_request);
    }

    /**
     * Определяет и вовзращает наибольший id в таблице feedback_value
     * @return int наибольший id в таблице feedback_value
     * */
    public static function get_max_course_review_value_id() {
        global $DB;
        // SQL запрос на получение наибольшего значения поля id
        // в таблице feedback_value
        $sql_request = '
            SELECT MAX(id) AS maxid
            FROM {feedback_value}
        ';

        return $DB->get_record_sql($sql_request);
    }

    /**
     * Запрашивает элементы из таблицы feedback_item связанные с указанным feedback (fbid)
     * @param $fbid int - id модуля feedback в таблице feedback
     * @return array массив вида:
     * array(
     *     [id] => object {
     *         [id] => <integer>, - fbi.id
     *         [name] => <string>, - fbi.name
     *         [fbityp] => <string>, - fbi.typ
     *     }
     * )
     * */
    public static function get_review_items($fbid) {
        global $DB;
        // SQL запрос на получение элементов обратной связи (напр: "Отзывы на курс")
        // из таблицы feedback_item
        $sql_request = '
            SELECT id, name, typ AS fbityp
            FROM {feedback_item}
            WHERE feedback = :fbid
        ';

        return $DB->get_records_sql($sql_request, array('fbid' => $fbid));
    }

    /**
     * Вставляет отзывы пришедшии с СЦОС в таблицы связанные с плагином feedback
     * @param array $raw_scos_course_reviews - ассоциативный массив объектов хранящих содержимое отзывов c СЦОС.
     * @param int   $courseid - id курса.
     * @return void
     * */
    public static function insert_scos_course_reviews($raw_scos_course_reviews, $fbid = -1) {
        global $DB;

        $filtered_raw_scos_course_reviews = array();

        $fbs = self::get_course_reviews_info($fbid);
        $fb = array_shift($fbs);

        $fbitems = self::get_review_items($fbid);

        if (utilities::check_fb_validity($fb)) {

            $max_fbcid = self::get_max_course_review_id()->maxid;
            $cur_fbcid = $max_fbcid;

            $maxtm = self::get_max_scos_course_review_timemodified($fbid)->maxtm;
            $maxtm = ($maxtm)? $maxtm : 0;

            $filtered_raw_scos_course_reviews = utilities::filter_raw_scos_course_reviews_by_rated_at($raw_scos_course_reviews, $maxtm);

            $max_fbvid = self::get_max_course_review_value_id()->maxid;
            $cur_fbvid = $max_fbvid;

            $insert_reviews_info_request = '
                INSERT INTO {feedback_completed}(id, feedback, userid, timemodified, anonymous_response)
                VALUES ';

            $insert_reviews_values = '
                INSERT INTO {feedback_value}(id, item, completed, value)
                VALUES ';

            $i = 0;
            foreach ($filtered_raw_scos_course_reviews as $raw_scos_course_review) {
                $cur_fbcid++;

                $timestamp = date_create($raw_scos_course_review->rated_at);
                $timestamp = date_timestamp_get($timestamp);

                $insert_reviews_info_request .=
                    '('.$cur_fbcid.','.$fbid.','.constants::$GUEST_USER_ID.','.$timestamp.','.constants::$ANONYMOUS_RESPONSE.')';

                    $j = 0;
                    foreach ($fbitems as $fbitem) {
                        $cur_fbvid++;
                        $insert_reviews_values .= '(' . $cur_fbvid . ',' . $fbitem->id . ',' . $cur_fbcid ;

                        if ($fbitem->fbityp == constants::$RATING_FIELD_TYPE)
                            $insert_reviews_values .= ',' . $raw_scos_course_review->rating;
                        else if ($fbitem->fbityp == constants::$TEXT_FIELD_TYPE)
                            $insert_reviews_values .= ',"' . $raw_scos_course_review->text . '"';

                        $insert_reviews_values .= ')';

                        if ($j < count($fbitems) - 1) $insert_reviews_values .= ',';
                        $j++;
                    }

                if ($i < count($filtered_raw_scos_course_reviews) - 1) {
                    $insert_reviews_info_request .= ',';
                    $insert_reviews_values .= ',';
                }

                $i++;
            }
        } else {
            $insert_reviews_info_request = '';
            $insert_reviews_values = '';
        }

        if (count($filtered_raw_scos_course_reviews)
            && $insert_reviews_info_request != ''
            && $insert_reviews_values != ''
        ) {
            $DB->execute($insert_reviews_info_request);
            $DB->execute($insert_reviews_values);
        }
    }

    /**
     * Запрашивает из таблициы feedback_completed отзывы с СЦОС по текущему курсу.
     * Если не передавать id курса, то вернёт все отзывы.
     * Структура возвращаемого результата:
     *     array(
     *         [index] => object {
     *             [user]     => <string>
     *             [rating]   => <integer>,
     *             [text]     => <string>,
     *             [rated_at] => <unix_timestamp>
     *         }
     *     ).
     * @param int $courseid - id курса.
     * @param int $isvisible - возвращать только отзывы доступные для отображения (courseid = 1).
     * @param int[] $pagination - массив параметров для пагинации при запросе (LIMIT offset, limit).
     * @param string[] $orderby - массив параметров для сортировки при запросе (ORDER BY field order).
     * @return array массив объектов записей, согласно структуре возвращаемого результата.
     * */
    public static function get_scos_course_reviews(
        $fbid, $isvisible = -1,
        $pagination = array('offset' => 0, 'limit' => 0),
        $orderby = array('field' => '', 'order' => 'DESC')
    ) {
        $fbcs = db_request::get_user_reviews_users_by_courseid($fbid, true, $isvisible, $pagination, $orderby);

        $fbvalues = array();
        $raw_fbvalues = array();

        if (count($fbcs)) {
            $raw_fbvalues = db_request::get_user_reviews_values_by_fbcids(array_keys($fbcs), true);
            $fbvalues = utilities::prepare_fbvalues($raw_fbvalues);
        }

        $fbvalues_types = utilities::get_fbvalues_types($raw_fbvalues);

        $course_feedback = array();

        $i = 0;
        foreach ($fbcs as $fbc) {
            $course_feedback[$i] = new \stdClass();
            $course_feedback[$i]->user = '';

            if ($fbiname = array_search(constants::$RATING_FIELD_TYPE, $fbvalues_types))
                $course_feedback[$i]->rating = $fbvalues[$fbc->fbcid][array_search(constants::$RATING_FIELD_TYPE, $fbvalues_types)];

            if ($fbiname = array_search(constants::$TEXT_FIELD_TYPE, $fbvalues_types))
                $course_feedback[$i]->text = $fbvalues[$fbc->fbcid][array_search(constants::$TEXT_FIELD_TYPE, $fbvalues_types)];

            $course_feedback[$i]->rated_at = $fbc->timemodified;
            $i++;
        }

        return $course_feedback;
    }

    /**
     * Запрашивает из таблициы feedback_completed отзывы с СЦОС по текущему курсу.
     * Если не передавать id курса, то вернёт все отзывы.
     *
     * @param int $courseid - id курса.
     * @param int $is_scos - возвращать только содержимое отзывов с СЦОС.
     * @return int кол-во отзывов на данный курс в зависимости от параметра $is_scos.
     * */
    public static function get_num_scos_course_reviews($fbid, $is_scos = -1) {
        return count(self::get_user_reviews_users_by_courseid($fbid, $is_scos));
    }
}
?>