<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'jk_opengraph/classes/TagsRetriever.php';
require_once _PS_MODULE_DIR_ . 'jk_opengraph/classes/OpenGraphPage.php';

class Jk_Opengraph extends Module
{
    protected $active_tab = false;
    protected $html;
    protected $pages = array(
        array(
            'id_page' => '1',
            'name' => 'index',
            'type' => '1',
        ),
        array(
            'id_page' => '2',
            'name' => 'category',
            'type' => '1',
        ),
        array(
            'id_page' => '3',
            'name' => 'new-products',
            'type' => '1',
        ),
        array(
            'id_page' => '4',
            'name' => 'best-sales',
            'type' => '1',
        ),
        array(
            'id_page' => '5',
            'name' => 'cms',
            'type' => '1',
        ),
        array(
            'id_page' => '6',
            'name' => 'product',
            'type' => '0',
        ),
    );

    protected $excluded = array(
        'xipblog_single',
    );

    public function __construct()
    {
        $this->name = 'jk_opengraph';
        $this->tab = 'front_office_features';
        $this->version = '1.3.2';
        $this->author = 'yusttas.github.io';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Open Graph');
        $this->description = $this->l(' Adds the open graph meta tags to your site. Open graph meta tags allow you to control what content shows up when a page is shared on Facebook.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Open Graph?');
    }

    public function install()
    {
        /* Check that the Multistore feature is enabled, and if so, set the current context to all shops
         * on this installation of PrestaShop. */

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() //Check that the module parent class is installed.
             || !$this->registerHook('header')
            || !$this->installTables()
            || !$this->installPages()
            || !$this->installConfig()
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
            || !$this->uninstallConfig()
            || !$this->uninstallTables()
        ) {
            return false;
        }

        return true;
    }

    public function installTables()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "jk_opengraph_tags` (
            `id_page` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(30) NOT NULL,
            `image` varchar(30) NOT NULL,
            `type` int(1) NOT NULL
            ) ENGINE=" . _MYSQL_ENGINE_ . "  DEFAULT CHARSET=utf8 ;";

        $sql2 = "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "jk_opengraph_tags_lang` (
            `id_page` int(11) NOT NULL,
            `id_lang` int(11) NOT NULL,
            `title` varchar(100) NOT NULL,
            `description` varchar(200) NOT NULL,
            PRIMARY KEY (`id_page`, `id_lang`)
            ) ENGINE=" . _MYSQL_ENGINE_ . "  DEFAULT CHARSET=utf8 ;";

        if (!Db::getInstance()->Execute($sql) || !Db::getInstance()->Execute($sql2)) {
            return false;
        }

        return true;
    }

    public function installPages()
    {
        $values = array();
        foreach ($this->pages as $page) {
            $values[] = '("' . $page['id_page'] . '", "' . $page['name'] . '", "' . $page['type'] . '")';
        }

        $insert = "INSERT INTO " . _DB_PREFIX_ . "jk_opengraph_tags (id_page, name, type) VALUES" . implode(',', $values);
        if (!Db::getInstance()->execute($insert)) {
            return false;
        }

        return true;
    }

    public function uninstallTables()
    {
        $sql1 = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "jk_opengraph_tags` ";
        $sql2 = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "jk_opengraph_tags_lang` ";

        if (!Db::getInstance()->Execute($sql1) || !Db::getInstance()->Execute($sql2)) {
            return false;
        }
        return true;
    }

    public function installConfig()
    {
        Configuration::updateValue('jk_og_site_name', '');
        Configuration::updateValue('jk_og_fb_app_id', '');

        return true;
    }

    public function uninstallConfig()
    {
        Configuration::deleteByName('jk_og_site_name');
        Configuration::deleteByName('jk_og_fb_app_id');

        return true;
    }

    public function hookDisplayHeader()
    {
        $name = $this->context->controller->php_self;

        if (in_array($name, $this->excluded)) {
            return false;
        }

        /** If module has no settings for this page, use metatags */
        if (!$page = OpenGraphPage::loadByName($name)) {
            $page = new OpenGraphPage();
            $page->name = $name;
            $page->type = 1;
        }

        if ($page->type == 0) {
            return false;
        }

        $tags_retriever = new TagsRetriever($page, $this->context->language->id);

        $tags = $tags_retriever->getOpengraphTags();

        $this->context->smarty->assign(
            array(
                'name' => $name,
                'excluded' => $this->excluded,
                'tags' => $tags,
            )
        );

        return $this->display(__FILE__, 'opengraph.tpl');
    }

    public function getContent()
    {
        $backoffice_css = __PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/backoffice.css';

        $tpl_vars = array(
            'tabs' => $this->getModuleTabs(),
            'active_tab' => $this->getActiveTab(),
            'backoffice_css' => $backoffice_css,

        );

        $this->context->smarty->assign($tpl_vars);
        return $this->html . $this->display(__FILE__, 'views/templates/admin/tabs.tpl');
    }

    protected function getModuleTabs()
    {
        $tabs = array();

        $tabs[] = array(
            'id' => 'general-settings',
            'title' => $this->l('General settings'),
            'content' => $this->getGeneralSettingsTemplate(),
        );

        foreach ($this->pages as $page) {
            $tabs[] = array(
                'id' => $page['name'],
                'title' => $this->l($page['name'] . ' tags'),
                'content' => $this->getOpenGraphTagsTemplate($page['id_page']),
            );
        }

        return $tabs;
    }

    protected function getActiveTab()
    {
        if (!$this->active_tab) {
            $this->active_tab = 'general-settings';
        }

        return $this->active_tab;
    }

    protected function getGeneralSettingsTemplate()
    {
        if (Tools::isSubmit('submit-general')) {

            $this->active_tab = 'general-settings';
            $errors = false;

            $fb_app_id = Tools::getValue('fb-app-id');
            $site_name = Tools::getValue('site-name');

            if (!is_numeric($fb_app_id) || $fb_app_id <= 0) {
                $this->html .= $this->displayError($this->l('Facebook App Id should only contain numbers'));
                $errors = true;
            }

            if (!$errors) {
                Configuration::updateValue('jk_og_fb_app_id', $fb_app_id);
                Configuration::updateValue('jk_og_site_name', $site_name);

                $this->html .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        $template = $this->renderGeneralSettingsForm();

        return $template;
    }

    protected function renderGeneralSettingsForm()
    {
        $fields_form = array();

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('General settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Facebook App ID:'),
                    'name' => 'fb-app-id',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Site name'),
                    'name' => 'site-name',
                    'desc' => $this->l('Leave empty to use default shop name: ') . $this->context->shop->name,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name' => 'submit-general',
            ),
        );

        $helper = $this->buildHelper();

        $helper->fields_value = array(
            'fb-app-id' => Configuration::get('jk_og_fb_app_id'),
            'site-name' => Configuration::get('jk_og_site_name'),
        );

        return $helper->generateForm($fields_form);
    }

    protected function getOpenGraphTagsTemplate($id_page)
    {
        $page = new OpenGraphPage($id_page);

        if (Tools::isSubmit('submit-' . $page->name)) {
            $this->active_tab = $page->name;
            $this->updateOpenGraphTags($page);
        }

        if (Tools::isSubmit('upload-' . $page->name)) {
            $this->active_tab = $page->name;
            $this->uploadImage($page);
        }

        $template = $this->renderOpenGraphTagsForm($page);

        return $template;
    }

    public function updateOpenGraphTags($page)
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $page->title[$lang['id_lang']] = Tools::getValue('og-title-' . $page->id . '_' . $lang['id_lang']);
            $page->description[$lang['id_lang']] = Tools::getValue('og-description-' . $page->id . '_' . $lang['id_lang']);
        }

        $page->type = Tools::getValue('og-type-' . $page->id);

        $page->save();

        $this->html .= $this->displayConfirmation($this->l('Tags updated!'));
    }

    public function renderOpenGraphTagsForm($page)
    {
        $type_values = array(
            array('id' => 'use-meta-tags-' . $page->id, 'value' => '1', 'label' => 'Use meta tags'),
            array('id' => 'use-custom-tags-' . $page->id, 'value' => '2', 'label' => 'Use custom tags'),
        );

        if ($page->name != 'index') {
            $type_values[] = array(
                'id' => 'use-index-tags-' . $page->id,
                'value' => '3',
                'label' => 'Use index tags',
            );
        }

        $type_values[] = array(
            'id' => 'turn-off-tags-' . $page->id,
            'value' => '0',
            'label' => 'Turn off tags',
        );

        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l($page->name . ' | Open graph tags'),
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->l('Settings'),
                    'name' => 'og-type-' . $page->id,
                    'values' => $type_values,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Open graph: title:'),
                    'name' => 'og-title-' . $page->id,
                    'lang' => true,
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Open graph: description'),
                    'name' => 'og-description-' . $page->id,
                    'lang' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name' => 'submit-' . $page->name,
            ),
        );

        $tags_retriever = new TagsRetriever($page, $this->context->language->id);
        $image = '<img src=' . Tools::safeOutput($tags_retriever->getImageUrl()) . ' style="max-width:300px">';

        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Open Graph image'),
            ),
            'input' => array(
                array(
                    'type' => 'file',
                    'label' => $this->l('Open Graph: image'),
                    'name' => 'image_' . $page->id,
                    'image' => $image,
                    'desc' => $this->l('Use images that are at least 1200 x 630 pixels for the best display on high resolution devices. At the minimum, you should use images that are 600 x 315 pixels to display link page posts with larger images. Images can be up to 8MB in size.'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name' => 'upload-' . $page->name,
            ),
        );

        $helper = $this->buildHelper();

        foreach (Language::getLanguages(false) as $lang) {

            $helper->fields_value['og-type-' . $page->id] = $page->type;
            $helper->fields_value['og-title-' . $page->id][(int) $lang['id_lang']] = $page->title[(int) $lang['id_lang']];
            $helper->fields_value['og-description-' . $page->id][(int) $lang['id_lang']] = $page->description[(int) $lang['id_lang']];
        }

        $tags_retriever = new TagsRetriever($page, $this->context->language->id);
        $helper->tpl_vars = array(
            'image' => $tags_retriever->getImageUrl(),
            'page' => $page->name,
        );

        return $helper->generateForm($fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name; //form action

        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0),
            );
        }

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;

        return $helper;
    }

    public function uploadImage($page)
    {
        $image_name = "og-" . $page->name;
        $image_maxsize = "8000000";

        if ($error = ImageManager::validateUpload($_FILES['image_' . $page->id], $image_maxsize)) {

            $this->html .= $this->displayError($error);
        } else {
            $image = $image_name . ".png";
            $this->deleteImageByName($image);
            if (!move_uploaded_file($_FILES['image_' . $page->id]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . $image)) {
                $this->html .= $this->displayError($this->trans('An error occurred while attempting to upload the file.', array(), 'Admin.Notifications.Error'));
            } else {
                $page->image = $image;
                $page->update();

                $this->html .= $this->displayConfirmation($this->l('Image updated'));
            }
        }
    }

    public function deleteImageByName($name)
    {
        $image = _PS_MODULE_DIR_ . $this->name . '/views/img/' . $name;
        if (@unlink($image)) {
            return true;
        }

        return false;
    }
}
