<?php

class OpenGraphPage extends ObjectModel
{
    public $id_page;
    public $id_lang;
    public $name;
    public $title;
    public $description;
    public $image;
    public $type;
    const TYPE_META_TAGS = 1;
    const TYPE_CUSTOM_TAGS = 2;
    const TYPE_INDEX_TAGS = 3;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'jk_opengraph_tags',
        'primary' => 'id_page',
        'multilang' => true,
        'fields' => array(
            'title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'),
            'description' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'image' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
            'type' => array('type' => self::TYPE_NOTHING),
        ),
    );

    /**
     * @param string $name Name of page
     * @return OpenGraphPage
     */
    public static function loadByName($name)
    {
        $sql = new DbQuery();
        $sql->select('id_page');
        $sql->from('jk_opengraph_tags');
        $sql->where("name='" . $name . "'");

        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new self($id);
        }
        return false;
    }
}
