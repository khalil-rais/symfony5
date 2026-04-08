<?php

namespace App\Twig;

use App\Service\MarkdownHelper;
use App\Service\UploaderHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private $container;
    private $publicDir;

    /*

     */
    public function __construct(ContainerInterface $container, string $publicDir)
    {
        $this->container = $container;
        /*
            Back in AppExtension,
            add the string $publicDir argument.
            I'll hit "Alt + Enter" and go to "Initialize fields" to create that property and set it.
         */
        $this->publicDir = $publicDir;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('uploaded_asset', [$this, 'getUploadedAssetPath']),
            /*
                Since there's no built-in way to do that,
                let's make our own Twig function where we can say encore_entry_css_source(),
                pass it email, and it will figure out all the CSS files it needs,
                load their contents, and return it as one big, giant, beautiful string.

                To create the function, our app already has a Twig extension called AppExtension.
                Inside, say new TwigFunction(), call it encore_entry_css_source
                and when this function is used, Twig should call a getEncoreEntryCssSource method.
             */
            new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource'])
        ];
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

    public function getUploadedAssetPath(string $path): string
    {
        return $this->container
            ->get(UploaderHelper::class)
            ->getPublicPath($path);
    }

    /*
        Copy that name and create it below: public function getEncoreEntryCssSource() with a string $entryName argument.
        This will return the string CSS source.
     */
    public function getEncoreEntryCssSource(string $entryName): string
    {
        /*
            Back up in getEncoreEntryCssSource(), we can say $files = $this->container->get(EntrypointLookupInterface::class) -
            that's how you access the service using a service subscriber -
            then ->getCssFiles($entryName).
         */
        /*
            Tip:
            To avoid missing CSS if you send your emails via Messenger
            (or if you send multiple emails during the same request),
            "reset" Encore's internal cache before calling getCssFiles():
            replace the first 3 lines with these
         */
        $entryPointLookupInterface = $this->container->get(EntrypointLookupInterface::class);
        $entryPointLookupInterface->reset();
        $files = $entryPointLookupInterface->getCssFiles($entryName);
        /*
            This will return an array with something like these two paths.
            Next, foreach over $files as $file and, above create a new $source variable set to an empty string.
            All we need to do now is look for each file inside the public/ directory and fetch its contents.
         */
        $source = '';
        foreach ($files as $file) {
            /*
                Down in the method, we can say
                $source .= file_get_contents($this->publicDir.$file) -
                each $file path should already have a / at the beginning.
                Finish the method with return $source.
             */
            $source .= file_get_contents($this->publicDir.'/'.$file);
        }
        return $source;
    }

    public static function getSubscribedServices()
    {
        return [
            MarkdownHelper::class,
            UploaderHelper::class,
            /*
                Inside, we need to look into the entrypoints.json file to find the CSS filenames needed for this $entryName.
                Fortunately, Symfony has a service that already does that.
                We can get it by using the EntrypointLookupInterface type-hint.
                For reasons I don't want to get into in this tutorial,
                instead of using normal constructor injection -
                where we add an argument type-hinted with EntrypointLookupInterface -
                we're using a "service subscriber".
                You can learn about this in, oddly-enough, our tutorial about Symfony & Doctrine.
                To fetch the service, go down to getSubscribedServices()
                and add EntrypointLookupInterface::class.
             */
            EntrypointLookupInterface::class,
        ];
    }
}
