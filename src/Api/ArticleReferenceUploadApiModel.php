<?php

namespace App\Api;

use Symfony\Component\Validator\Constraints as Assert;

/*
    2- In the first part of the if, just like a normal API endpoint,
    we need to decode the JSON request content into something useful.
    To do that, let's use the serializer!
    Search for "deser", there it is.
    Earlier, we used deserialize() to turn the JSON into an ArticleReference object.
    That worked because the keys in that JSON matched the property names in that class.
    But in this case, look at the fields: filename and data.
    We do have an originalFilename field,
    and we could rename the filename key to that but we definitely do not have
    and do not want a data property on ArticleReference that's equal to a base64 encoded version of our file.
    That makes no sense.
    This is a classic case where the data of an endpoint doesn't match the structure of our entity.
    And that's cool!
    Instead of using the entity, we can create a new model class.
    Inside src/, let's create a new Api/ directory - just for organization - and inside, a new class: how about ArticleReferenceUploadApiModel.
    The whole point of this class is to help us deal with the data for this endpoint.
    So, its properties should match the data.
    Add public $filename and public $data.
 */
class ArticleReferenceUploadApiModel
{
    /*
        3- Yes! Gasp! They're public!
        Because this class will only be used for this one, narrow, purpose,
        it's ok to make life a bit easier with public properties.
        If this makes you want to scream and tackle me,
        I get it! Just make them private and add the getter & setter methods.
        That will work perfectly.
        While we're here, don't forget about validation: add @Assert\NotBlank above both of these.
     */
    /**
     * @Assert\NotBlank()
     */
    public $filename;

    /**
     * @Assert\NotBlank()
     */
    public $data;

}