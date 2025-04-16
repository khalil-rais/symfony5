<?php

namespace App\Service;

class MarkdownHelper
{
    public function parse(string $question_text): string{
        return $cache->get('markdown_'.md5($question_text), function () use($question_text, $markdownParser) {
            return $markdownParser->transformMarkdown($question_text);
        });
    }
}