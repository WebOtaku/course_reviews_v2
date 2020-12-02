<?php

namespace block_course_reviews_v2;

class constant {
    // Требуемы типы полей
    public static $VALID_FIELDS_TYPES = array('numeric', 'textarea');
    // Тип поля для ввода оценки
    public static $RATING_FIELD_TYPE = 'numeric';
    // Тип поля для ввода комментария к оценке
    public static $TEXT_FIELD_TYPE = 'textarea';
    // Кол-во полей = кол-ву типов полей ($VALID_FIELDS_TYPES)
    public static $NUM_REVIEW_FIELDS = 2;
    // ИД пользователя с правами админа
    public static $ADMIN_USER_ID = 2;
    // ИД пользователя с правами гостя
    public static $GUEST_USER_ID = 1;
    // Значение показывающее, что отзыв анонимный
    public static $ANONYMOUS_RESPONSE = 1;
    // Диапазон в который должен попадать idnumber модуля
    public static $IDNUMBER_RANGE = array(1, 10000);
    // ИД модуля "Обратная связь" (feedback) в таблец modules
    public static $FEEDBACK_MODULE_ID = 7;
}