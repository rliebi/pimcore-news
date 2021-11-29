<?php

namespace NewsBundle\Generator;

use NewsBundle\Model\EntryInterface;
use Pimcore\Model\Asset;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;
use Pimcore\Tool;

class HeadMetaGenerator implements HeadMetaGeneratorInterface
{
    /**
     * @var LinkGeneratorInterface
     */
    protected $linkGenerator;

    /**
     * HeadMetaGenerator constructor.
     *
     * @param LinkGeneratorInterface $linkGenerator
     */
    public function __construct(LinkGeneratorInterface $linkGenerator)
    {
        $this->linkGenerator = $linkGenerator;
    }

    public function getTitlePosition()
    {
        return Container::PREPEND;
    }

    /**
     * @param EntryInterface $entry
     *
     * @return string
     */
    public function generateTitle(EntryInterface $entry)
    {
        $mT = $entry->getMetaTitle();
        $title = !empty($mT) ? $mT : $entry->getName();

        return $title;
    }

    /**
     * @param EntryInterface $entry
     *
     * @return string
     */
    public function generateDescription(EntryInterface $entry)
    {
        $mD = $entry->getMetaDescription();
        $description = !empty($mD) ? $mD : ($entry->getLead() ? $entry->getLead() : $entry->getDescription());
        $description = trim(substr($description, 0, 160));

        return $description;
    }

    /**
     * @param EntryInterface $entry
     *
     * @return array
     */
    public function generateMeta(EntryInterface $entry): array
    {
        $title = $this->generateTitle($entry);
        $description = $this->generateDescription($entry);

        $href = $this->linkGenerator->generateDetailLink($entry);

        $ogTitle = $title;
        $ogDescription = $description;
        $ogUrl = $href;
        $ogType = 'article';

        $ogImage = null;

        if ($entry->getImage() instanceof Asset\Image) {
            $ogImage = Tool::getHostUrl() . $entry->getImage()->getThumbnail('contentImage');
        }

        $params = [
            'og:title'       => $ogTitle,
            'og:description' => $ogDescription,
            'og:url'         => $ogUrl,
            'og:image'       => $ogImage,
            'og:type'        => $ogType
        ];

        return $params;
    }
}
