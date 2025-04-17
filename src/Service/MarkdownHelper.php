<?php

namespace App\Service;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class MarkdownHelper
{
    private $markdownParser;
    private $cache;
    private $isDebug;
    private $logger;

    public function __construct(MarkdownParserInterface $markdownParser, CacheInterface $cache, bool $isDebug, LoggerInterface $markdownLogger){
        $this->markdownParser = $markdownParser;
        $this->cache = $cache;
        $this->isDebug = $isDebug;
        $this->logger = $markdownLogger;
    }
    public function parse(string $question_text): string{
        if(stripos($question_text, 'cat') !== false){
            $this->logger->info('Meow!');
        }

        if($this->isDebug){
            return $this->markdownParser->transformMarkdown($question_text);
        }
        return $this->cache->get('markdown_'.md5($question_text), function () use($question_text) {
            return $this->markdownParser->transformMarkdown($question_text);
        });
    }
}