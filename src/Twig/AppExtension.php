<?php

namespace App\Twig;

use App\Service\MarkdownHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\UploaderHelper;

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

    /*
        Inside: we need to get the UploaderHelper service so we can call getPublicPath() on it.
        Normally we do this by adding it as an argument to the constructor.
        But, in a few places in Symfony, for performance purposes, we should do something slightly different:
        we use what's called a "service subscriber",
        because it allows us to fetch the services lazily.
        If this is a new concept for you,
        go check out our Symfony Fundamentals course - it's a really cool feature.
        The short explanation is that this class has a getSubscribedServices() method
        where we can choose which services we need.
        These are then included in the $container object
        and we can fetch them out by saying $this->container->get().
        Add UploaderHelper::class to the array.
     */
    public static function getSubscribedServices()
    {
        return [
            MarkdownHelper::class,
            UploaderHelper::class,
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
    /*
        Then, above, we can return $this->container->get(UploaderHelper::class)->getPublicPath($path).
    */
    public function getUploadedAssetPath(string $path): string
    {
        return $this->container
            ->get(UploaderHelper::class)
            ->getPublicPath($path);
    }
    /*
        Let's give it a try! Refresh! We got it!
        That took some work, but I promise you'll be super happy you did this.
        Next: let's also update the image path in the show page,
        and learn a bit about what the asset() function does internally
        and how we can do the same thing automatically in UploaderHelper.
    */
}
