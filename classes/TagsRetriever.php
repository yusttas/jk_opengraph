<?php

require_once _PS_MODULE_DIR_ . 'jk_opengraph/classes/OpenGraphPage.php';

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
            case OpenGraphPage::TYPE_META_TAGS:
                $og_tags = $this->getMetaTags();
                break;
            case OpenGraphPage::TYPE_CUSTOM_TAGS:
                $og_tags = $this->getCustomTags();
                break;
            case OpenGraphPage::TYPE_INDEX_TAGS:
                $og_tags = $this->getIndexTags();
                break;
        }

        $main_tags = array(
            'fb_app_id' => $fb_app_id,
            'site_name' => $site_name,
            'site_type' => 'website',
            'title' => $og_tags['title'],
            'description' => $og_tags['description'],
            'image' => $this->getImageUrl(),
        );

        $final_tags = $this->addAdditionalTags($main_tags);

        return $final_tags;
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

    private function addAdditionalTags($tags)
    {
        switch ($this->page->name) {
            case 'product':
                $tags['site_type'] = 'product';
                $id_product = (int) Tools::getValue('id_product');
                $product = new Product($id_product, true);
                if ($this->page->type == OpengraphPage::TYPE_META_TAGS) {
                    $id_cover = Product::getCover($id_product);
                    $id_cover['id_image'] ? $tags['image'] = Context::getContext()->link->getImageLink($id_product, $id_cover['id_image']) : '';
                }
                $tags['product'] = array(
                    'brand' => Manufacturer::getNameById((int)$product->id_manufacturer),
                    'availability' => $product->checkQty(StockAvailable::getQuantityAvailableByProduct($id_product, 0)) ? 'in stock' : 'out of stock',
                    'condition' => $product->condition,
                    'amount' => Product::getPriceStatic($id_product, true, null, 2),
                    'currency' => Context::getContext()->currency->iso_code,
                    'id' => $id_product,  
                );
                break;

            default:
                break;
        }

        return $tags;
    }

    public function getImageUrl()
    {
        $index = new OpenGraphPage(1);

        if ($this->page->type == OpengraphPage::TYPE_INDEX_TAGS && $index->image != '') {
            $url = Media::getMediaPath(_PS_MODULE_DIR_ . 'jk_opengraph/views/img/' . $index->image); //index image
        } elseif ($this->page->image != '') {
            $url = Media::getMediaPath(_PS_MODULE_DIR_ . 'jk_opengraph/views/img/' . $this->page->image); //individual image
        } else {
            $url = _PS_IMG_ . Configuration::get('PS_LOGO'); // shop logo
        }

        return $url;
    }
}
