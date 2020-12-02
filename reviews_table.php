<?php

use block_course_reviews_v2\utility;

class reviews_table extends table_sql
{
    protected $fbvalues;
    protected $courseid;
    protected $idnumber;
    public $columns = array('fbcid');
    public $headers = array();
    public $optional_columns;
    public $optional_headers;

    /**
     * Конструктор
     * @param int $uniqueid все таблицы должны иметь уникальный идентификатор, это используется
     * в качестве ключа при сохранении свойств таблицы, таких как порядок сортировки, в сеансе.
     * @return void
     */
    public function __construct($uniqueid, $raw_fbvalues = [], $courseid = 0, $idnumber = 0, $optional_columns = [], $optional_headers = [])
    {
        parent::__construct($uniqueid);
        $this->fbvalues = utility::prepare_fbvalues($raw_fbvalues);

        $this->headers[] = get_string('fbcid', 'block_course_reviews_v2');

        $this->optional_columns = $optional_columns;
        $this->optional_headers = $optional_headers;

        // Определяем какие колонки будут в таблице
        $this->set_optional_columns();
        $this->define_columns($this->columns);

        // Определям отображаемые заголовки для колонок
        $this->set_optional_headers();
        $this->define_headers($this->headers);

        $this->courseid = $courseid;
        $this->idnumber = $idnumber;
    }

    /**
     * Задаёт дополонительный колонки таблицы
     * @return void
     * */
    protected function set_optional_columns() {
        foreach ($this->optional_columns as $users_column) {
            $this->columns[] = $users_column;
        }

        $i = 1;
        foreach ($this->fbvalues[array_keys($this->fbvalues)[0]] as $fbname => $fbvalue) {
            $this->columns[] = 'fbiname'.$i;
            $this->column_nosort[] = 'fbiname'.$i;
            $i++;
        }

        $this->columns[] = 'isvisible';
    }

    /**
     * Задаёт дополонительные заголовки для колонок таблицы
     * @return void
     * */
    protected function set_optional_headers() {
        foreach ($this->optional_headers as $users_header) {
            $this->headers[] = $users_header;
        }

        foreach ($this->fbvalues[array_keys($this->fbvalues)[0]] as $fbname => $fbvalue)
            $this->headers[] = $fbname;
        $this->headers[] = get_string('isvisible', 'block_course_reviews_v2');
    }

    /**
     * Описывет вывод значений столбца fbcid в таблице
     * @param $values object объект с полями характеризующими текущую строку таблицы (при выводе этой таблицы).
     * @return string возвращает строку содержащую либо значение столбца fbcid (для печати)
     * либо поле ввода (input) с этим же значением.
     * */
    public function col_fbcid($values)
    {
        if ($this->is_downloading()) {
            return $values->fbcid;
        } else {
            return '<input style="
                width: 50px; 
                border: none;
                background-color: inherit; 
                outline: none;
            " 
            type="text" name="reviewids[]" readonly value="' . $values->fbcid . '"/>';
        }
    }

    /**
     * Описывет вывод значений столбца timemodified в таблице
     * @param $values object объект с полями характеризующими текущую строку таблицы (при выводе этой таблицы).
     * @return string возвращает строку содержащую значение столбца timemodified в человекочитаемом формате.
     * */
    public function col_timemodified($values)
    {
        return userdate($values->timemodified);
    }

    /**
     * Описывет вывод значений опционального столбца fbiname<число> в таблице. Оно предназначено для вывода содержимого отзывов.
     * Кол-во таких столбцов как fbiname<число> зависит от числа заполняемых полей в отзыве.
     * @param $values object объект с полями характеризующими текущую строку таблицы (при выводе этой таблицы).
     * @return string возвращает значение столбца fbvvalue связанное с конкретным отзывом.
     * */
    public function col_fbiname1($values)
    {
        $fbvalue = $this->fbvalues[$values->fbcid][array_keys($this->fbvalues[$values->fbcid])[0]];
        return $fbvalue;
    }

    /**
     * Описывет вывод значений опционального столбца fbiname<число> в таблице. Оно предназначено для вывода содержимого отзывов.
     * Кол-во таких столбцов как fbiname<число> зависит от числа заполняемых полей в отзыве.
     * @param $values object объект с полями характеризующими текущую строку таблицы (при выводе этой таблицы).
     * @return string возвращает значение столбца fbvvalue связанное с конкретным отзывом.
     * */
    public function col_fbiname2($values)
    {
        $fbvalue = $this->fbvalues[$values->fbcid][array_keys($this->fbvalues[$values->fbcid])[1]];
        return $fbvalue;
    }

    /**
     * Описывет вывод значений столбца isvisible в таблице. Оно предназначено для вывода чекбоксов показывающих
     * нужно ли отображать отзыв или нет.
     * @param $values object объект с полями характеризующими текущую строку таблицы (при выводе этой таблицы).
     * @return string возвращает строку содержащую либо значение столбца isvisible (для печати)
     * либо чекбокс (input) с этим же значением.
     * */
    public function col_isvisible($values)
    {
        if ($this->is_downloading()) {
            if ($values->isvisible == 1) return 'Да';
            else if ($values->isvisible == 0) return 'Нет';
        } else {
            $isvisible = false;

            if ($values->isvisible == 1) $isvisible = true;
            else if ($values->isvisible == 0) $isvisible = false;

            if ($isvisible)
                $html_checkbox = '<input type="checkbox" name="isvisible[]" value="' . $values->fbcid . '" checked/>';
            else
                $html_checkbox = '<input type="checkbox" name="isvisible[]" value="' . $values->fbcid . '"/>';

            return $html_checkbox;
        }
    }

    /**
     * Добавляет html перед таблицей. В данном случае это форма.
     * @return void
     * */
    public function wrap_html_start()
    {
        global $PAGE;
        if ($this->is_downloading()) {
            return;
        }

        $submit_url = new moodle_url('/blocks/course_reviews_v2/table_view.php');

        echo '<div id="tablecontainer">';
        echo '<form id="attemptsform" method="post" action="' . $submit_url . '">';
        echo html_writer::input_hidden_params(new moodle_url($PAGE->url,
            array('sesskey' => sesskey(), 'courseid' => $this->courseid, 'idnumber' => $this->idnumber)));
        echo '<div>';
    }

    /**
     * Добавляет html после таблицы. В данном случае это форма.
     * @return void
     * */
    public function wrap_html_finish()
    {
        global $PAGE;

        if ($this->is_downloading()) {
            return;
        }

        echo '<div style="
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            margin: 15px 0;
        " id="commands">';

        echo '<a id="checkattempts" href="#">' . get_string('selectall', 'quiz') . '</a> / ';
        echo '<a id="uncheckattempts" href="#">' . get_string('selectnone', 'quiz') . '</a> ';

        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                $('#checkattempts').click(function(e) {
                    $('#attemptsform').find('input:checkbox').prop('checked', true);
                    e.preventDefault();
                });
                $('#uncheckattempts').click(function(e) {
                    $('#attemptsform').find('input:checkbox').prop('checked', false);
                    e.preventDefault();
                });
            });"
        );

        echo '&nbsp;&nbsp;';
        $this->submit_buttons();
        echo '</div>';

        // Close the form.
        echo '</div>';
        echo '</form></div>';
    }

    /**
     * Описывает вывод кнопки "Сохранить"
     * @return void
     * */
    protected function submit_buttons()
    {
        global $PAGE;

        echo '<input type="submit" class="btn btn-secondary mr-1" id="updatereviewbutton" name="update" 
                     value="'.get_string('savebtn', 'block_course_reviews_v2').'"/>';
        $PAGE->requires->event_handler('#updatereviewbutton', 'click', 'M.util.show_confirm_dialog',
            array('message' => get_string('acceptsavemessage', 'block_course_reviews_v2')));
    }
}