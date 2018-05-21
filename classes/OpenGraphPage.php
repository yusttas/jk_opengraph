<?php

class OpenGraphPage extends ObjectModel
{

    public $id_page;
    public $id_lang;
    public $name;
    public $title;
    public $description;
    public $image;
    /** @var int 1-metatags, 2-custom tags, 3-index page tags*/
    public $type;

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

    public function getOpengraphTags($id_lang)
    {

        $fb_app_id = Configuration::get('jk_og_fb_app_id');
        $site_name = Configuration::get('jk_og_site_name');

        if (empty($site_name)) {
            $site_name = Configuration::get('PS_SHOP_NAME');
        }

        switch ($this->type) {
            case 1: //use metatags
                $og_tags = $this->getMetaTags($id_lang);
                break;
            case 2: //use individual custom tags
                $og_tags = $this->getCustomTags($id_lang);
                break;
            case 3: //use index custom tags
                $og_tags = $this->getIndexTags($id_lang, true);
                break;
        }

        $tags = array(
            'fb_app_id' => $fb_app_id,
            'type' => 'website',
            'site_name' => $site_name,
            'title' => $og_tags['title'],
            'description' => $og_tags['description'],
            'image' => $this->getImageUrl(),
        );

        return $tags;
    }

    private function getMetaTags($id_lang)
    {

        switch ($this->name) {
            case 'index':
                $meta_tags = Meta::getHomeMetas($id_lang, $this->name);
                break;
            case 'cms':
                $id_cms = (int) Tools::getValue('id_cms');
                $meta_tags = MetaCore::getCmsMetas($id_cms, $id_lang, $this->name);
                break;
            case 'category':
                $id_category = (int) Tools::getValue('id_category');
                $meta_tags = Meta::getCategoryMetas($id_category, $id_lang, $this->name);
                break;
            default:
                $meta_tags = Meta::getHomeMetas($id_lang, $this->name);
                break;
        }

        $og_tags = array(
            'title' => $meta_tags['meta_title'],
            'description' => $meta_tags['meta_description'],
        );

        return $og_tags;
    }

    private function getCustomTags($id_lang)
    {

        $og_tags = array(
            'title' => $this->title[(int) $id_lang],
            'description' => $this->description[(int) $id_lang],
        );

        return $og_tags;
    }

    private function getIndexTags($id_lang)
    {

        $index = new self(1);

        $og_tags = array(
            'title' => $index->title[(int) $id_lang],
            'description' => $index->description[(int) $id_lang],
        );

        return $og_tags;
    }

    public function getImageUrl()
    {

        $index = new self(1);

        if ($this->image != '') {
            $url = Media::getMediaPath(_PS_MODULE_DIR_ . 'jk_opengraph/views/img/' . $this->image); //individual image
        } elseif ($this->type == 3 && $index->image != '') {
            $url = Media::getMediaPath(_PS_MODULE_DIR_ . 'jk_opengraph/views/img/' . $index->image); //index image
        } else {
            $url = _PS_IMG_ . Configuration::get('PS_LOGO'); // shop logo
        }

        return $url;
    }
}
