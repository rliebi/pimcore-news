<?php

namespace NewsBundle\Document\Areabrick\News;

use FormBuilderBundle\Manager\TemplateManager;
use NewsBundle\Configuration\Configuration;
use NewsBundle\Event\NewsBrickEvent;
use NewsBundle\Manager\EntryTypeManager;
use NewsBundle\NewsEvents;
use NewsBundle\Registry\PresetRegistry;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxConfiguration;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Area\Info;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pimcore\Translation\Translator;


class News extends AbstractTemplateAreabrick implements EditableDialogBoxInterface
{
    /**
     * @var Document\PageSnippet
     */
    protected $document;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var EntryTypeManager
     */
    protected $entryTypeManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var PresetRegistry
     */
    protected $presetRegistry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    protected TemplateManager $templateManager;

    /**
     * News constructor.
     *
     * @param Configuration $configuration
     * @param EntryTypeManager $entryTypeManager
     * @param Translator $translator
     * @param PresetRegistry $presetRegistry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        Configuration $configuration,
        EntryTypeManager $entryTypeManager,
        Translator $translator,
        PresetRegistry $presetRegistry,
        TemplateManager $templateManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configuration = $configuration;
        $this->entryTypeManager = $entryTypeManager;
        $this->translator = $translator;
        $this->presetRegistry = $presetRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->templateManager = $templateManager;
    }

    /**
     * @param Info $info
     *
     * @return null|\Symfony\Component\HttpFoundation\Response|void
     * @throws \Exception
     */
    public function action(Info $info)
    {
        $this->document = $info->getDocument();
        $isEditMode = $info->getEditable()->getEditmode();
        $view = $info->getEditable();
        $isPresetMode = false;
        $latest = $this->getDocumentEditable($info->getDocument(), 'checkbox', 'latest');
//        $showPagination = false && $this->getDocumentEditable($info->getDocument(), 'checkbox', 'show_pagination')->getData();
        $showPagination = false;
        $layout = $this->getDocumentEditable($info->getDocument(), 'select', 'layout')->getData();
        $entryType = $this->getDocumentEditable($info->getDocument(), 'select', 'entryType');

        //check if preset has been selected at first


            $querySettings = [];
//            $querySettings['category'] = $fieldConfiguration['category']['value'];
//            $querySettings['includeSubCategories'] = $fieldConfiguration['include_subcategories']['value'];
//            $querySettings['singleObjects'] = $fieldConfiguration['single_objects']['value'];
//            $querySettings['entryType'] = $fieldConfiguration['entry_types']['value'];
//            $querySettings['offset'] = $fieldConfiguration['offset']['value'];
//
//            //set limit
//            $limit = $fieldConfiguration['max_items']['value'];
//
//            //set pagination
//            $calculatedItemsPerPage = $fieldConfiguration['paginate']['items_per_page']['value'];
//
//            if ($calculatedItemsPerPage > $limit) {
//                $calculatedItemsPerPage = $limit;
//            }
//
//            $querySettings['itemsPerPage'] = $calculatedItemsPerPage;
//
//            //set paged
//            $querySettings['page'] = (int)$info->getRequest()->query->get('page');
//
//            //only latest
            if ($latest->getData() === true) {
                $querySettings['onlyLatest'] = true;
            }
//
//            //set sort
//            $querySettings['sort']['field'] = $fieldConfiguration['sort_by']['value'];
//            $querySettings['sort']['dir'] = $fieldConfiguration['order_by']['value'];
//
//            //set time range
//            $querySettings['timeRange'] = $fieldConfiguration['time_range']['value'];
//
//            $mainClasses = [];
//            $mainClasses[] = 'area';
            $mainClasses[] = 'news-'.$layout;

            if ($layout !== 'all') {
                $mainClasses[] = 'entry-type-'.str_replace([
                        '_',
                        ' ',
                    ], ['-'], strtolower($layout));
            }
//
//            $event = new NewsBrickEvent($info, $querySettings);
//            $this->eventDispatcher->dispatch($event, NewsEvents::NEWS_BRICK_QUERY_BUILD);
//
//            $querySettings = $event->getQuerySettings();
//            $additionalViewParams = $event->getAdditionalViewParams();
//
            $newsObjects = DataObject\NewsEntry::getEntriesPaging($querySettings);
//
            $subParams = [
                'main_classes' => implode(' ', $mainClasses),
                'is_preset_mode' => false,
//                'category' => $ca,
                'show_pagination' => $showPagination,
//                'entry_type' => $fieldConfiguration['entry_types']['value'],
                'layout_name' => $layout,
                'paginator' => $newsObjects,
//                'additional_view_params' => $additionalViewParams,
                'query_settings' => $querySettings,
            ];
//
//
//        $systemParams = [
//            'is_preset_mode' => $isPresetMode,
//            //system/editmode related
//            'config' => $fieldConfiguration,
//        ];
//
//        $params = array_merge($systemParams, $subParams);
////        dump($view); die();
////        $view->setConfig($fieldConfiguration);
//        $view->setValues($subParams);
//        $view->setValue('is_preset_mode', $isPresetMode);
        foreach ($subParams as $key => $value) {
//            $view->{$key} = $value;
            $info->setParam($key, $value);
        }
    }

    /**
     * @return array
     */
    private function getPresetsStore()
    {
        $data = [
            'store' => [
                ['none', $this->translator->trans('news.no_preset', [], 'admin')],
            ],
            'info' => [],
        ];

        $services = $this->presetRegistry->getList();

        foreach ($services as $alias => $service) {
            $name = $this->translator->trans($service->getName(), [], 'admin');
            $description = !empty($service->getDescription())
                ? $this->translator->trans($service->getDescription(), [], 'admin')
                : null;

            $data['store'][] = [$alias, $name];
            $data['info'][] = ['name' => $alias, 'description' => $description];
        }

        return $data;
    }

    /**
     * @return array
     */
    private function getLayoutStore()
    {
        $listConfig = $this->configuration->getConfig('list');

        $store = [];
        foreach ($listConfig['layouts']['items'] as $index => $item) {
            $store[] = [$index, $this->translator->trans($item['name'], [], 'admin')];
        }

        return $store;
    }

    /**
     * @return array
     */
    private function getSortByStore()
    {
        $listConfig = $this->configuration->getConfig('list');

        $store = [];
        foreach ($listConfig['sort_by_store'] as $key => $value) {
            $store[] = [$key, $this->translator->trans($value, [], 'admin')];
        }

        return $store;
    }

    /**
     * @return array
     */
    private function getOrderByStore()
    {
        return [
            ['desc', $this->translator->trans('news.order_by.descending', [], 'admin')],
            ['asc', $this->translator->trans('news.order_by.ascending', [], 'admin')],
        ];
    }

    /**
     * @return array
     */
    private function getTimeRangeStore()
    {
        return [
            ['all', $this->translator->trans('news.time_range.all_entries', [], 'admin')],
            ['current', $this->translator->trans('news.time_range.current_entries', [], 'admin')],
            ['past', $this->translator->trans('news.time_range.past_entries', [], 'admin')],
        ];
    }

    /**
     * @return array
     */
    private function getEntryTypeStore()
    {
        $store = [
            ['all', $this->translator->trans('news.entry_type.all', [], 'admin')],
        ];

        foreach ($this->entryTypeManager->getTypesFromConfig() as $typeKey => $typeData) {
            $store[] = [$typeKey, $this->translator->trans($typeData['name'], [], 'admin')];
        }

        return $store;
    }

    /**
     * @param $fieldConfiguration
     *
     * @return bool
     */
    private function isPresetMode($fieldConfiguration)
    {
        return $fieldConfiguration['presets']['value'] !== 'none'
            && $this->presetRegistry->has($fieldConfiguration['presets']['value']);
    }

    /**
     * @return string
     */
    public function getTemplateSuffix()
    {
        return static::TEMPLATE_SUFFIX_TWIG;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'News';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagOpen(Info $info)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagClose(Info $info)
    {
        return '';
    }

    public function getEditableDialogBoxConfiguration(
        Document\Editable $area,
        ?Info $info
    ): EditableDialogBoxConfiguration {
        $tabbedItems = [];
        $tabbedItems = $this->getSingleElement($tabbedItems);
        $tabbedItems = $this->getCategoryTab($tabbedItems);
        $tabbedItems = $this->getPaginationTab($tabbedItems);
        $tabbedItems = $this->getSortingTab($tabbedItems);

        $editableDialog = new EditableDialogBoxConfiguration();
        $editableDialog->setItems([
            'type' => 'tabpanel',
            'items' => $tabbedItems,
        ]);
        $editableDialog->setReloadOnClose(true);
        $editableDialog->setWidth(600);
        $editableDialog->setHeight(450);
        return $editableDialog;
    }

    public function getSingleElement(array $tabbedItems): array
    {
        $listConfig = $this->configuration->getConfig('list');
        $tabbedItems[] = [
            'type' => 'panel',
            'title' => $this->translator->trans('news.entries', [], 'admin'),
            'items' => [
                [
                    'type' => 'checkbox',
                    'name' => 'latest',
                    'label' => $this->translator->trans('news.show_only_top_entries', [], 'admin'),
                    'config' => [
                        'defaultValue' => null,
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => 'layout',
                    'label' => $this->translator->trans('news.show_only_top_entries', [], 'admin'),
                    'config' => [
                        'store' => $this->getLayoutStore(),
                        'defaultValue' => $listConfig['layouts']['default'],
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => 'entryType',
                    'label' => $this->translator->trans('news.entry_type', [], 'admin'),
                    'config' => ['store' => $this->getEntryTypeStore(), 'defaultValue' => 'all'],
                ],
            ],
        ];

        return $tabbedItems;
    }

    public function getCategoryTab(array $tabbedItems): array
    {
        $tabbedItems[] = [
            'type' => 'panel',
            'title' => $this->translator->trans('news.categories', [], 'admin'),
            'items' => [
                [
                    'type' => 'relations',
                    'name' => 'category',
                    'label' => $this->translator->trans('news.categories', [], 'admin'),
                    'config' => [
                        'types' => ['object'],
                        'subtypes' => ['object' => ['object']],
                        'classes' => ['NewsCategory'],
                        'width' => '95%',
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'include_subcategories',
                    'label' => $this->translator->trans('news.include_subcategories', [], 'admin'),
                    'config' => [
                        'defaultValue' => null,
                    ],
                ],
            ],
        ];

        return $tabbedItems;
    }

    public function getPaginationTab(array $tabbedItems): array
    {
        $listConfig = $this->configuration->getConfig('list');
        $tabbedItems[] = [
            'type' => 'panel',
//            'title' => $this->translator->trans('news.list', [], 'admin'),
            'title' => 'news.list',
            'items' => [
                [
                    'type' => 'text',
                    'label' => 'test'
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'show_pagination',
//                    'label' => $this->translator->trans('news.show_pagination', [], 'admin'),
                    'label' => 'news.show_pagination',
                    'config' => [
                        'disabled' => true,
                        'defaultValue' => null,
                    ],
                ],
                [
                    'type' => 'numeric',
                    'name' => 'max_items',
                    'label' => $this->translator->trans('news.maximum_number_of_entries', [], 'admin'),
                    'config' => [
                        'defaultValue' => $listConfig['max_items'],

                    ],
                ],
                [
                    'type' => 'numeric',
                    'name' => 'offset',
                    'label' => $this->translator->trans('news.offset', [], 'admin'),
                ],

            ],
        ];

        return $tabbedItems;
    }

    public function getSortingTab(array $tabbedItems): array
    {
        $listConfig = $this->configuration->getConfig('list');
        $tabbedItems[] = [
            'type' => 'panel',
            'title' => $this->translator->trans('news.sorting', [], 'admin'),
            'items' => [
                [
                    'type' => 'select',
                    'name' => 'order_by',
                    'label' => $this->translator->trans('news.order_by', [], 'admin'),
                    'config' => [
                        'store' => $this->getOrderByStore(),
                        'defaultValue' => $listConfig['order_by'],
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => 'sort_by',
                    'label' => $this->translator->trans('news.sort_by', [], 'admin'),
                    'config' => [
                        'store' => $this->getSortByStore(),
                        'defaultValue' => $listConfig['sort_by'],
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => 'time_range',
                    'label' => $this->translator->trans('news.time_range', [], 'admin'),
                    'config' => [
                        'store' => $this->getTimeRangeStore(),
                        'defaultValue' => $listConfig['time_range'],
                    ],
                ],
            ],
        ];

        return $tabbedItems;
    }

    public function getIcon(): string
    {
        return '/bundles/news/img/entry.svg';
    }
}
