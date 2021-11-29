<?php

namespace NewsBundle\Twig\Extension;

use NewsBundle\Generator\LinkGeneratorInterface;
use NewsBundle\Model\EntryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class NewsExtension extends AbstractExtension
{
    /**
     * @var LinkGeneratorInterface
     */
    private $linkGenerator;

    /**
     * @var array
     */
    private $imageThumbnails;

    /**
     * NewsExtension constructor.
     *
     * @param LinkGeneratorInterface $linkGenerator
     * @param array                  $imageThumbnails
     */
    public function __construct(LinkGeneratorInterface $linkGenerator, array $imageThumbnails)
    {
        $this->linkGenerator = $linkGenerator;
        $this->imageThumbnails = $imageThumbnails;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'news_entry_permalink',
                [$this, 'generatePermalink']
            ),
            new TwigFunction(
                'news_entry_backlink',
                [$this, 'generateBackLink']
            ),
            new TwigFunction(
                'news_thumbnail',
                [$this, 'getNewsThumbnail']
            ),
        ];
    }

    /**
     * @param EntryInterface $entry
     *
     * @return string
     */
    public function generatePermalink(EntryInterface $entry)
    {
        return $this->linkGenerator->generateDetailLink($entry);
    }

    /**
     * @param EntryInterface $entry
     *
     * @return string
     */
    public function generateBackLink(EntryInterface $entry)
    {
        return $this->linkGenerator->generateBackLink($entry);
    }

    /**
     * Get a thumbnail name from config
     *
     * @param string $thumbnail
     *
     * @return string
     */
    public function getNewsThumbnail(string $thumbnail)
    {
        return in_array($thumbnail, $this->imageThumbnails) ? $this->imageThumbnails[$thumbnail] : '';
    }
}
