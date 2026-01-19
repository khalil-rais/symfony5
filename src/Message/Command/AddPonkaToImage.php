<?php

namespace App\Message\Command;
/*
    We already organized our new event class into an Event subdirectory.
    Cool! Let's do the same thing for our commands.
    Create a new Command/ sub-directory,
    move the two command classes inside
    then add \Command to the end of the namespace on both classes.
    Now that we've changed those namespaces,
    we need to update a few things.
 */
class AddPonkaToImage
{
    private $imagePostId;

    public function __construct(int $imagePostId)
    {
        $this->imagePostId = $imagePostId;
    }

    public function getImagePostId(): int
    {
        return $this->imagePostId;
    }

}