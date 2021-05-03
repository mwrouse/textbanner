<?php
if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class TextBanner
 *
 * @since 1.0.0
 */
class TextBanner extends Module
{
    const LINK = 'TEXTBANNER_LINK';
    const TEXT = 'TEXTBANNER_TEXT';
    const BGCOLOR = 'TEXTBANNER_BGCOLOR';
    const BGCOLOR_HOVER = 'TEXTBANNER_BGCOLORHOVER';
    const FGCOLOR = 'TEXTBANNER_FGCOLOR';
    const ENABLED = 'TEXTBANNER_ENABLED';

    /**
     * TEXTBANNER constructor.
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'textbanner';
        $this->tab = 'front_office_features';
        $this->version = '2.1.0';
        $this->author = 'Michael Rouse';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Text Banner');
        $this->description = $this->l('Displays a text/link banner at the top of the shop (no images).');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws HTMLPurifier_Exception
     * @since 1.0.0
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('displayBanner');
        $this->registerHook('displayHeader');
        $this->registerHook('actionObjectLanguageAddAfter');

        $this->installFixtures();

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function hookActionObjectLanguageAddAfter($params)
    {
        try {
            return $this->installFixture((int) $params['object']->id);
        } catch (Exception $e) {
            Logger::addLog("TEXTBANNER hook error: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Uninstall this module
     *
     * @return bool Indicates whether this module has been successfully uninstalled
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function uninstall()
    {
        foreach ([
            static::LINK,
            static::TEXT,
            static::BGCOLOR,
            static::BGCOLOR_HOVER,
            static::FGCOLOR,
            static::ENABLED,
                 ] as $key) {
            try {
                Configuration::deleteByName($key);
            } catch (PrestaShopException $e) {
                Logger::addLog("TEXTBANNER module error: {$e->getMessage()}");
            }
        }

        Tools::deleteDirectory($this->getImageDir());

        return parent::uninstall();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayBanner()
    {
        try {
            return $this->hookDisplayTop();
        } catch (Exception $e) {
            Logger::addLog("TEXTBANNER hook error: {$e->getMessage()}");

            return '';
        }
    }

      /**
     * @since 1.0.0
     */
    public function hookDisplayHeader()
    {
        $enabled = Configuration::get(static::ENABLED, $this->context->language->id);
        if ($enabled == 1) {
            $this->context->controller->addCSS($this->_path.'textbanner.css', 'all');

           return $this->getStyling();
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayTop()
    {
        try {
            if (!$this->isCached('textbanner.tpl', $this->getCacheId())) {

                $this->smarty->assign(
                    [
                        'banner_link' => Configuration::get(static::LINK, $this->context->language->id),
                        'banner_text' => Configuration::get(static::TEXT, $this->context->language->id),
                        'banner_bgcolor' => Configuration::get(static::BGCOLOR),
                        'banner_bgcolor_hover' => Configuration::get(static::BGCOLOR_HOVER),
                        'banner_fgcolor' => Configuration::get(static::FGCOLOR),
                        'banner_enabled' => Configuration::get(static::ENABLED, $this->context->language->id),
                    ]
                );
            }

            return $this->display(__FILE__, 'textbanner.tpl', $this->getCacheId());
        } catch (Exception $e) {
            Logger::addLog("TEXTBANNER hook error: {$e->getMessage()}");

            return '';
        }
    }


    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        try {
            return $this->postProcess().$this->renderForm();
        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();

            return '';
        }
    }


    /**
     * Returns styling for the banner if enabled
     */
    public function getStyling() {
        $bgColor = Configuration::get(static::BGCOLOR);
        $bgColorHover = Configuration::get(static::BGCOLOR_HOVER);
        $fgColor = Configuration::get(static::FGCOLOR);

        $css = '<!-- Text Banner Styling -->';
        $css .='<style>';
        if (!empty($bgColor)) {
            $css .= '#textbanner .textbanner-container { background-color:' . $bgColor . ';}';
        }
        if (!empty($bgColorHover)) {
            $css .= '#textbanner .textbanner-container::hover { background-color:' . $bgColorHover .';}';
        }
        if (!empty($fgColor)) {
            $css .= '#textbanner .textbanner-container { color:' . $fgColor . ';}';
        }
        $css .= '</style>';

        return $css;
    }

    /**
     * @return bool|string
     *
     * @since 1.0.0
     * @throws PrestaShopException
     * @throws HTMLPurifier_Exception
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $languages = Language::getLanguages(false);
            $values = [];
            $updateImagesValues = false;

            foreach ($languages as $lang) {

                $idLang = (int)$lang['id_lang'];

                $values[static::LINK][$idLang] = Tools::getValue(static::LINK . '_'. $idLang);

                $values[static::TEXT][$idLang] = Tools::getValue(static::TEXT . '_'. $idLang);
                $values[static::BGCOLOR] = Tools::getValue(static::BGCOLOR);
                $values[static::BGCOLOR_HOVER] = Tools::getValue(static::BGCOLOR_HOVER);
                $values[static::FGCOLOR] = Tools::getValue(static::FGCOLOR);
                $values[static::ENABLED][$idLang] = Tools::getValue(static::ENABLED);
            }


            Configuration::updateValue(static::LINK, $values[static::LINK]);

            Configuration::updateValue(static::TEXT, $values[static::TEXT]);

            Configuration::updateValue(static::BGCOLOR, $values[static::BGCOLOR]);
            Configuration::updateValue(static::BGCOLOR_HOVER, $values[static::BGCOLOR_HOVER]);
            Configuration::updateValue(static::FGCOLOR, $values[static::FGCOLOR]);
            Configuration::updateValue(static::ENABLED, $values[static::ENABLED]);

            $this->_clearCache('TEXTBANNER.tpl');

            return $this->displayConfirmation($this->l('The settings have been updated.'));
        }

        return '';
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show Banner'),
                        'name' => static::ENABLED,
                        'desc' => '',
                        'lang' => true,
                        'values' => [
                            [
                                'id' => static::ENABLED . '_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => static::ENABLED . '_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ]
                        ]
                    ],
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => $this->l('Banner Link'),
                        'name'  => static::LINK,
                        'desc'  => $this->l('Enter the link associated to your banner. When clicking on the banner, the link opens in the same window. If no link is entered, clicking will do nothing'),
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Banner Text'),
                        'name' => static::TEXT,
                        'desc' => $this->l('The contents of the banner')
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Background Color'),
                        'name' => static::BGCOLOR
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Background Hover Color'),
                        'name' => static::BGCOLOR_HOVER
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Text Color'),
                        'name' => static::FGCOLOR
                    ]

                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            try {
                $fields[static::LINK][$lang['id_lang']] = Tools::getValue(
                    static::LINK.'_'.$lang['id_lang'],
                    Configuration::get(static::LINK, $lang['id_lang'])
                );
                $fields[static::TEXT][$lang['id_lang']] = Tools::getValue(
                    static::TEXT.'_'.$lang['id_lang'],
                    Configuration::get(static::TEXT, $lang['id_lang'])
                );
                $fields[static::BGCOLOR] = Tools::getValue(
                    static::BGCOLOR,
                    Configuration::get(static::BGCOLOR)
                );
                $fields[static::BGCOLOR_HOVER] = Tools::getValue(
                    static::BGCOLOR_HOVER,
                    Configuration::get(static::BGCOLOR_HOVER)
                );
                $fields[static::FGCOLOR] = Tools::getValue(
                    static::FGCOLOR,
                    Configuration::get(static::FGCOLOR)
                );


                $fields[static::ENABLED] = Tools::getValue(
                    static::ENABLED,
                    Configuration::get(static::ENABLED, $lang['id_lang'])
                );
            } catch (Exception $e) {
                Logger::addLog("TEXTBANNER hook error: {$e->getMessage()}");
                $fields[static::LINK][$lang['id_lang']] = '';
                $fields[static::TEXT][$lang['id_lang']] = '';
                $fields[static::BGCOLOR] = '';
                $fields[static::BGCOLOR_HOVER] = '';
                $fields[static::FGCOLOR] = '';
                $fields[static::ENABLED] = 0;
            }
        }

        return $fields;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     * @throws HTMLPurifier_Exception
     */
    protected function installFixtures()
    {

        return true;
    }

    /**
     * @param int $idLang
     * @param string|null $image
     *
     * @since 1.0.0
     * @throws PrestaShopException
     * @throws HTMLPurifier_Exception
     */
    protected function installFixture($idLang)
    {
        $values = [];
        $values[static::LINK][(int) $idLang] = '';
        $values[static::TEXT][(int) $idLang] = '';
        $values[static::BGCOLOR]= '';
        $values[static::BGCOLOR_HOVER]= '';
        $values[static::FGCOLOR] = '';
        $values[static::ENABLED] = 0;

        Configuration::updateValue(static::LINK, $values[static::LINK]);
        Configuration::updateValues(static::TEXT, $values[static::TEXT]);
        Configuration::updateValues(static::BGCOLOR, $values[static::BGCOLOR]);
        Configuration::updateValues(static::BGCOLOR_HOVER, $values[static::BGCOLOR_HOVER]);
        Configuration::updateValues(static::FGCOLOR, $values[static::FGCOLOR]);
        Configuration::updateValues(static::ENABLED, $values[static::ENABLED]);
    }

}
