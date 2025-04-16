<?php

namespace App\Service;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Contracts\Cache\CacheInterface;

class MarkdownHelper
{
    public function parse(string $question_text, MarkdownParserInterface $markdownParser, CacheInterface $cache): string{
        return $cache->get('markdown_'.md5($question_text), function () use($question_text, $markdownParser) {
            return $markdownParser->transformMarkdown($question_text);
        });
    }
}