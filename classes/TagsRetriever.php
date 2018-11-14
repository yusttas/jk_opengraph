<?php

class TagsRetriever
{

    private $page;
    private $id_lang;

    public function __construct(OpenGraphPage $page, $id_lang)
    {

        $this->page = $page;

        if (!is_int($id_lang)) {
            throw new Exception('Language id must be integer!');
        }

        $this->id_lang = $id_lang;
    }

    public function getOpengraphTags()
    {

        $fb_app_id = Configuration::get('jk_og_fb_app_id');
        $site_name = Configuration::get('jk_og_site_name');

        if (empty($site_name)) {
            $site_name = Configuration::get('PS_SHOP_NAME');
        }

        switch ($this->page->type) {
            case 1: //use metatags
                $og_tags = $this->getMetaTags();
                break;
            case 2: //use individual custom tags
                $og_tags = $this->getCustomTags();
                break;
            case 3: //use index custom tags
                $og_tags = $this->getIndexTags();
                break;
        }

        $tags = array(
            'fb_app_id' => $fb_app_id,
            'type' => $this->getType(),
            'site_name' => $site_name,
            'title' => $og_tags['title'],
            'description' => $og_tags['description'],
            'image' => $this->getImageUrl(),
        );

        return $tags;
    }

    private function getMetaTags()
    {

        switch ($this->page->name) {
            default:
                $meta_tags = Meta::getMetaTags($this->id_lang, $this->page->name);
                break;
        }

        $og_tags = array(
            'title' => $meta_tags['meta_title'],
            'description' => $meta_tags['meta_description'],
        );

        return $og_tags;
    }

    private function getCustomTags()
    {

        $og_tags = array(
            'title' => $this->page->title[(int) $this->id_lang],
            'description' => $this->page->description[(int) $this->id_lang],
        );

        return $og_tags;
    }

    private function getIndexTags()
    {

        $index = new OpenGraphPage(1);

        $og_tags = array(
            'title' => $index->title[(int) $this->id_lang],
            'description' => $index->description[(int) $this->id_lang],
        );

        return $og_tags;
    }

    private function getType()
    {

        if ($this->page->name == 'product') {
            $type = 'product';
        } else {
            $type = 'website';
        }

        return $type;
    }

    public function getImageUrl()
    {

        $index = new OpenGraphPage(1);

        if ($this->page->image != '') {
            $url = Media::getMediaPath(_PS_MODULE_DIR_ . 'jk_opengraph/views/img/' . $this->page->image); //individual image
        } elseif ($this->page->type == 3 && $index->image != '') {
            $url = Media::getMediaPath(_PS_MODULE_DIR_ . 'jk_opengraph/views/img/' . $index->image); //index image
        } else {
            $url = _PS_IMG_ . Configuration::get('PS_LOGO'); // shop logo
        }

        return $url;
    }

}
