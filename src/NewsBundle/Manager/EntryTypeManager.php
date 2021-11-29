<?php

namespace NewsBundle\Manager;

use NewsBundle\Configuration\Configuration;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Tool;
use Pimcore\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class EntryTypeManager
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $routeData = [];

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * EntryTypeManager constructor.
     *
     * @param Configuration       $configuration
     * @param Translator $translator
     * @param SiteResolver        $siteResolver
     * @param EditmodeResolver    $editmodeResolver
     * @param DocumentResolver    $documentResolver
     */
    public function __construct(
        Configuration $configuration,
        Translator $translator,
        SiteResolver $siteResolver,
        EditmodeResolver $editmodeResolver,
        DocumentResolver $documentResolver
    ) {
        $this->configuration = $configuration;
        $this->translator = $translator;
        $this->siteResolver = $siteResolver;
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;

    }

    /**
     * @param null $object
     *
     * @return array|mixed|null
     */
    public function getTypes($object = null)
    {
        $entryTypes = $this->getTypesFromConfig();

        $validLayouts = null;
        $masterLayoutAvailable = false;
        if (!is_null($object)) {
            $validLayouts = DataObject\Service::getValidLayouts($object);
            if (array_key_exists(0, $validLayouts)) {
                $masterLayoutAvailable = true;
            }
        }

        foreach ($entryTypes as $typeId => &$type) {
            if ($type['custom_layout_id'] === 0) {
                $type['custom_layout_id'] = null;
            }

            $customLayoutId = $type['custom_layout_id'];
            //if string (name) is given, get layout via listing
            if (is_string($customLayoutId)) {
                $list = new ClassDefinition\CustomLayout\Listing();
                $list->setLimit(1);
                $list->setCondition('name = ?', $type['custom_layout_id']);
                $list = $list->load();
                if (isset($list[0]) && $list[0] instanceof DataObject\ClassDefinition\CustomLayout) {
                    $customLayoutId = (int)$list[0]->getId();
                } else {
                    $customLayoutId = 0; //reset layout to default -> custom layout is not available!
                }
            }

            //remove types if valid layout is set and user is not allowed to use it!
            if (!is_null($customLayoutId)) {
                // custom layout found: check if user has rights to use it! if not: remove from selection!
                if (!is_null($validLayouts) && $masterLayoutAvailable === false && !isset($validLayouts[$customLayoutId])) {
                    unset($entryTypes[$typeId]);
                } else {
                    $type['custom_layout_id'] = $customLayoutId;
                }
            } else {
                $type['custom_layout_id'] = 0;
            }
        }

        return $entryTypes;
    }

    /**
     * Get Default Entry Type
     *
     * @return mixed
     */
    public function getDefaultType()
    {
        $entryTypeConfig = $this->configuration->getConfig('entry_types');
        return $entryTypeConfig['default'];
    }

    /**
     * @param $typeName
     *
     * @return array|mixed
     */
    public function getTypeInfo($typeName)
    {
        $info = [];
        $types = $this->getTypes();

        if (isset($types[$typeName])) {
            $info = $types[$typeName];
            //translate name.
            $info['name'] = $this->translator->trans($types[$typeName]['name'], [], 'admin');
        }

        return $info;
    }

    /**
     * @return array|mixed|null
     */
    public function getTypesFromConfig()
    {
        $entryTypeConfig = $this->configuration->getConfig('entry_types');

        $types = $entryTypeConfig['items'];

        //cannot be empty - at least "news" is required.
        if (empty($types)) {
            $types = [
                'news' => [
                    'name'           => 'news.entry_type.news',
                    'route'          => '',
                    'customLayoutId' => 0
                ]
            ];
        }

        return $types;
    }

    /**
     * @param $entryType
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function getRouteInfo($entryType)
    {
        //use cache.
        if (isset($this->routeData[$entryType])) {
            return $this->routeData[$entryType];
        }

        $routeData = ['name' => 'news_detail', 'urlParams' => []];
        $types = $this->getTypesFromConfig();

        if (isset($types[$entryType]) && !empty($types[$entryType]['route'])) {
            $routeData['name'] = $types[$entryType]['route'];
        }

        $site = null;
        if (!$this->editmodeResolver->isEditmode()) {
            if ($this->siteResolver->isSiteRequest()) {
                $site = $this->siteResolver->getSite();
            }
        } else {
            $currentDocument = $this->documentResolver->getDocument();
            $site = Tool\Frontend::getSiteForDocument($currentDocument);
        }

        $routeData['site'] = null;
        if ($site instanceof Site) {
            $routeData['site'] = $site->getId();
        }

        $route = Staticroute::getByName($routeData['name'], $routeData['site']);

        if (empty($route)) {
            throw new \Exception(sprintf('"%s" route is not available. please add it to your static routes', $routeData['name']));
        }
        $variables = explode(',', $route->getVariables());

        //remove default one
        $defaults = ['news'];
        $variables = array_diff($variables, $defaults);

        $routeData['urlParams'] = array_merge($routeData['urlParams'], $variables);
        $this->routeData[$entryType] = $routeData;

        return $routeData;
    }
}
