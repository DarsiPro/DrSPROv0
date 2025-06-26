<?php
/**
* @project    DarsiPro CMS
* @package    Foto Sections Model
* @url        https://darsi.pro
*/


namespace FotoModule\ORM;

class FotoCategoriesModel extends \OrmModel
{

    public $Table = 'foto_categories';

    public function getCatsByIds($cats) {

        $Register = \Register::getInstance();

        if (!is_array($cats))
            $cats = explode(',', $cats);

        $out = array();
        $need_cats = array();

        // Получаем категории, которые уже были загружены.
        $categories = array();
        if (isset($Register['foto_categories'])) $categories = $Register['foto_categories'];

        // Оценка необходимости в дополнительных запросах
        foreach ($cats as $n => $cat) {
            // Если категория еще не была загружена, то в массив кладем её id вместо \ORM обьекта.
            if (!isset($categories[$cat])) {
                $need_cats[] = $cat;
                $out[] = $cat;
                continue;
            }
            // Если уже загружена, то добавляем её обьект в массив
            $out[] = $categories[$cat];
        }

        // Если есть, что еще нужно загрузить
        if (!empty($need_cats))
            // Загружаем недостающие категории
            if (($need_cats = $this->getCollection(array('`id` IN ('.implode(',',$need_cats).')'))) && !empty($need_cats))
                // Укладываем их в возвращаемый массив, заместо поставленных там ID
                foreach($out as $n => $cat) {
                    // Находим "подставленное ID"
                    if (!is_object($cat) && is_numeric($cat))
                        // Ищем соответствующую по ID категорию
                        foreach($need_cats as $ncat)
                            if ($ncat->getId() == $cat) {
                                // Меняем "подставленное ID" на полноценный объект категории
                                $out[$n] = $ncat;
                                // Полученную категорию запоминаем в регистре, чтобы не проделывать тоже самое при следующей необходимости.
                                $categories[$cat] = $ncat;
                            }
                    // Выявляем категории, которые не удалось получить. (их, как правило, не существует)
                    if (!is_object($out[$n]))
                        $out[$n] = false;
                }

        // Помещаем загруженные категории в общеиспользуемый регистр.
        $Register['foto_categories'] = $categories;
        return $out;
    }

}