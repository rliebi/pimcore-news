<?php

namespace NewsBundle\EventListener;

use NewsBundle\Generator\HeadMetaGeneratorInterface;
use NewsBundle\Model\EntryInterface;
use Pimcore\Model\DataObject\NewsEntry;
use Pimcore\Twig\Extension\Templating\HeadMeta;
use Pimcore\Twig\Extension\Templating\HeadTitle;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MetaDataListener implements EventSubscriberInterface
{
    /**
     * @var HeadMeta
     */
    protected $headMeta;

    /**
     * @var HeadTitle
     */
    protected $headTitle;

    /**
     * @var HeadMetaGeneratorInterface
     */
    protected $headMetaGenerator;

    /**
     * @param HeadMeta                   $headMeta
     * @param HeadTitle                  $headTitle
     * @param HeadMetaGeneratorInterface $headMetaGenerator
     */
    public function __construct(
        HeadMeta $headMeta,
        HeadTitle $headTitle,
        HeadMetaGeneratorInterface $headMetaGenerator
    ) {
        $this->headMeta = $headMeta;
        $this->headTitle = $headTitle;
        $this->headMetaGenerator = $headMetaGenerator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if ($request->attributes->get('pimcore_request_source') !== 'staticroute') {
            return;
        }

        $entryId = $request->get('entry');

        if (empty($entryId)) {
            return;
        }

        /** @var EntryInterface $entry */
        $entry = NewsEntry::getByLocalizedfields('detailUrl', $entryId, $request->getLocale(), ['limit' => 1]);

        if (!$entry instanceof NewsEntry) {
            return;
        }

        foreach ($this->headMetaGenerator->generateMeta($entry) as $property => $content) {
            if (!empty($content)) {
                $this->headMeta->appendProperty($property, $content);
            }
        }

        $this->headMeta->setDescription($this->headMetaGenerator->generateDescription($entry));

        $title = $this->headMetaGenerator->generateTitle($entry);

        switch ($this->headMetaGenerator->getTitlePosition()) {
            case Container::SET:
                $this->headTitle->set($title);
                break;
            case Container::PREPEND:
                $this->headTitle->prepend($title);
                break;
            case Container::APPEND:
            default:
                $this->headTitle->append($title);
                break;
        }
    }
}
