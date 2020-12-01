<?php
namespace block_course_reviews_v2\event;

class review_updated extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u'; // обязтелен (выобор операции из набора crud)
        $this->data['edulevel'] = self::LEVEL_OTHER; // обязателен (уровни прописаны в документации мудл API событий)
    }
}