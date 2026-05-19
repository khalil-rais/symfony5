<?php

namespace App\Twig;

use App\Service\MarkdownHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('cached_markdown', [$this, 'processMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function processMarkdown($value)
    {
        return $this->container
            ->get(MarkdownHelper::class)
            ->parse($value);
    }

    public static function getSubscribedServices()
    {
        return [
            MarkdownHelper::class,
        ];
    }

    /*
        In AppExtension, copy getFilters(), paste and rename it to getFunctions().
        Return an array, and, inside, add a new TwigFunction() with uploaded_asset and [$this, 'getUploadedAssetPath'].
    */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('uploaded_asset', [$this,'getUploadedAssetPath'])
        ];
    }

    /*
        Copy that new method name, scroll down and add it:
        public function getUploadedAssetPath() with a string $path argument.
        It will also return a string.
    */
    public function getUploadedAssetPath(string $path): string
    {

    }
}
