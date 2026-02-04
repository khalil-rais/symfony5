<?php

namespace App\Message\Command;

class LogEmoji
{
    private $emojiIndex;

    /*
        Add a public function __construct().
        In order to tell us which emoji to log,
        the outside system will send us an integer index of the emoji they want,
        our app will have a list of emojis.
        So, add an $emojiIndex argument
     */
    public function __construct(int $emojiIndex){
        $this->emojiIndex = $emojiIndex;
    }

    /*
        and then press Alt+Enter and select "Initialize Fields" to create that property and set it.
        To make this property readable by the handler,
        go to the Code -> Generate menu - or Command + N on a Mac,
        select getters and generate getEmojiIndex().
        Brilliant! A perfectly boring, um, normal, message class.
     */
    public function getEmojiIndex(): int
    {
        return $this->emojiIndex;
    }
}