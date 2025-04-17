<?php

namespace App\Service;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Contracts\Cache\CacheInterface;

class MarkdownHelper
{
    private $markdownParser;
    private $cache;
    private $isDebug;
    public function __construct(MarkdownParserInterface $markdownParser, CacheInterface $cache, bool $isDebug){
        $this->markdownParser = $markdownParser;
        $this->cache = $cache;
        $this->isDebug = $isDebug;
        dump($isDebug);
    }
    public function parse(string $question_text): string{
        if($this->isDebug){
            return $this->markdownParser->transformMarkdown($question_text);
        }
        return $this->cache->get('markdown_'.md5($question_text), function () use($question_text) {
            return $this->markdownParser->transformMarkdown($question_text);
        });
    }
}