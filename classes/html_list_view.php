<?php

namespace block_course_reviews_v2;

use html_writer;
use moodle_url;

class html_list_view
{
    /**
     * Формирует и возвращает маркированны html список (ul) с требуемыми сылками
     * @param string[] $links - массив ссылок
     * @param array[] $linksparams - массив массивов параметров для ссылок $links
     * @param string $langfile - название языкового файла
     * @param string[] $keyslangfile - ключи в языковом файле (названия ссылок)
     * @return string html строка
     * */
    public static function get_html_list_links($links = [], $linksparams = [], $langfile = '', $keyslangfile = []) {
        $html_str = '<ul>';
        foreach ($links as $key => $link) {
            $html_str .= '<li>';
            if (count($linksparams))
                $url = new moodle_url($link, $linksparams[$key]);
            else
                $url = new moodle_url($link);

            $html_str .= html_writer::link($url, get_string($keyslangfile[$key], $langfile));
            $html_str .= '</li>';
        }
        $html_str .= '</ul>';

        return $html_str;
    }
}
?>