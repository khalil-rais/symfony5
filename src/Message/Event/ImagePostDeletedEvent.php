<?php

namespace App\Message\Event;

/*
Commands, events & their handlers look identical.
In the src/Message directory, to start organizing things a bit better,
let's create an Event/ subdirectory.
Inside, add a new class: ImagePostDeletedEvent
 */
class ImagePostDeletedEvent
{
/*
Notice the name of this class: that's critical.
Everything so far has sounded like a command:
we're running around our code base shouting: AddPonkaToImage! And DeleteImagePost!
But with events, you're not using a strict command,
you're notifying the system of something that just happened:
we're going to fully delete the image post and then say:
“Hey! I just deleted an image post!
If you care, now is your chance to do something!
But I don't care if you do or not.”
The event itself could be handled by nobody
or it could have multiple handlers.
Inside the class, we'll store any data we think might be handy.
Add a constructor with a string $filename -
knowing the filename of the deleted ImagePost might be useful.
I'll hit Alt + Enter and go to "Initialize Fields"
to create that property and set it.
Then, at the bottom, I'll go to "Code -> Generate" - or Command + N on a Mac -
and select "Getters" to generate this one getter.
 */
    private $filename;
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }
    /*
        But wait... if that's true -
        if the Symfony serializer creates messages
        that can be consumed by external systems or by our same app -
        then why isn't it the default serializer in Messenger?
        An excellent question!
        The reason is that the Symfony serializer requires your classes to follow a few rules in order to be serialized and unserialized correctly,
        like each property needs a setter method or a constructor argument
        where the name matches the property name.
        If your class doesn't follow those rules,
        you can end up with a property
        that is set on the original object,
        but suddenly becomes null when it's read from the transport.
        No fun.
        In other words, the PHP serializer is easier and more dependable
        when everything is done by the same app.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}
/*
You may have noticed that,
other than its name,
this "event" class looks exactly like the command we just deleted!
 */