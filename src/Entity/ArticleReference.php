<?php

namespace App\Entity;

use App\Repository\ArticleReferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Service\UploaderHelper;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ArticleReferenceRepository::class)
 */
class ArticleReference
{
    /*
        The easiest way to fix this is to define a serialization group.
        In ArticleReference, above the id property,
        add @Groups and let's invent one called main.
        Put this above all the fields
        that we actually want to serialize,
        how about $id, $filename, $originalFilename and $mimeType.
        We're not actually using the JSON response yet so it doesn't matter -
        but we will use it in a few minutes.
     */
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("main")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Article", inversedBy="articleReferences")
     * @ORM\JoinColumn(nullable=false)
     */
    private $article;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("main")
     */
    private $filename;

    /*
        Oh, and when we've been serializing,
        we've been passing a groups option,
        which tells the serializer to put the properties from the "main" group into the JSON.
        We can do the same thing here:
        we don't want a clever user to be able to update the internal filename or the id:
        we need to restrict their power to changing the originalFilename.
        Above $originalFilename, turn the groups value into an array and give it a second group: input.
     */
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"main", "input"})
     */
    private $originalFilename;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("main")
     */
    private $mimeType;

    /*
        Oh, but before we do - go open that class.
        This is a very traditional entity:
        it has some properties and everything has a getter and a setter.
        That's great, but because every ArticleReference needs to have its Article property set
        and because an ArticleReference will never change articles,
        find the setArticle() method and obliterate it!
        Instead, add a public function __construct() with a required Article argument.
        Set that onto the article property.
        This is an optional step -
        but it's always nice to think critically about your entities:
        what methods do you not need?
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /*
        Oh, except, we don't have an easy way to do that yet!
        In our Article entity, we added a nice getImagePath() method
        that read the constant from UploaderHelper and added the filename.
        I like that.
        Let's copy that and go do the exact same thing in ArticleReference.
        At the bottom, paste and rename this to getFilePath().
        Let's add a return type too -
        I probably should have done that in Article.
        Then, re-type the r on UploaderHelper to get the use statement,
        change the constant to ARTICLE_REFERENCE
        and update the method call to getFilename().
     */
    public function getFilePath(): string
    {
        return UploaderHelper::ARTICLE_REFERENCE.'/'.$this->getFilename();
    }
}
/*
    Let's create the new entity:
    php bin/console make:entity

    Call it ArticleReference and give it an article property.
    This will be a relation back to the Article class.
    This will be a ManyToOne relation:
    each Article can have many ArticleReferences.
    Then, this will be not null in the database:
    every ArticleReference must be related to an Article.
    Say yes to map the other side of the relationship -
    it's convenient to be able to say $article->getArticleReferences().
    And no to orphan removal - we won't be using that feature.
    Nice! Ok, this needs a few more fields:
    filename a string that will hold the filename on the filesystem, originalFilename, a string
    that will hold the original filename
    that was on the user's system - more on that later - and mimeType -
    we'll use that to store what type of file it is -
    which will come in handy later.



     Class name of the entity to create or update (e.g. GrumpyGnome):
     > ArticleReference

     created: src/Entity/ArticleReference.php
     created: src/Repository/ArticleReferenceRepository.php

     Entity generated! Now let's add some fields!
     You can always add more fields later manually or by re-running this command.

     New property name (press <return> to stop adding fields):
     > article

     Field type (enter ? to see all types) [string]:
     > relation

     What class should this entity be related to?:
     > Article

    What type of relationship is this?
     ------------ --------------------------------------------------------------------------------
      Type         Description
     ------------ --------------------------------------------------------------------------------
      ManyToOne    Each ArticleReference relates to (has) one Article.
                   Each Article can relate to (can have) many ArticleReference objects.

      OneToMany    Each ArticleReference can relate to (can have) many Article objects.
                   Each Article relates to (has) one ArticleReference.

      ManyToMany   Each ArticleReference can relate to (can have) many Article objects.
                   Each Article can also relate to (can also have) many ArticleReference objects.

      OneToOne     Each ArticleReference relates to (has) exactly one Article.
                   Each Article also relates to (has) exactly one ArticleReference.
     ------------ --------------------------------------------------------------------------------

     Relation type? [ManyToOne, OneToMany, ManyToMany, OneToOne]:
     > ManyToOne

     Is the ArticleReference.article property allowed to be null (nullable)? (yes/no) [yes]:
     > no

     Do you want to add a new property to Article so that you can access/update ArticleReference objects from it - e.g. $article->getArticleReferences()? (yes/no) [yes]:
     > yes

     A new property will also be added to the Article class so that you can access the related ArticleReference objects from it.

     New field name inside Article [articleReferences]:
     >

     Do you want to activate orphanRemoval on your relationship?
     A ArticleReference is "orphaned" when it is removed from its related Article.
     e.g. $article->removeArticleReference($articleReference)

     NOTE: If a ArticleReference may *change* from one Article to another, answer "no".

     Do you want to automatically delete orphaned App\Entity\ArticleReference objects (orphanRemoval)? (yes/no) [no]:
     > no

     updated: src/Entity/ArticleReference.php
     updated: src/Entity/Article.php

     Add another property? Enter the property name (or press <return> to stop adding fields):
     > filename

     Field type (enter ? to see all types) [string]:
     > string

     Field length [255]:
     > 255

     Can this field be null in the database (nullable) (yes/no) [no]:
     > no

     updated: src/Entity/ArticleReference.php

     Add another property? Enter the property name (or press <return> to stop adding fields):
     > originalFilename

     Field type (enter ? to see all types) [string]:
     > string

     Field length [255]:
     > mimeType

     Field length [255]:
     > 255

     Can this field be null in the database (nullable) (yes/no) [no]:
     > no

     updated: src/Entity/ArticleReference.php

     Add another property? Enter the property name (or press <return> to stop adding fields):
     > mimeType

     Field type (enter ? to see all types) [string]:
     > string

     Field length [255]:
     > 255

     Can this field be null in the database (nullable) (yes/no) [no]:
     > no

     updated: src/Entity/ArticleReference.php

     Add another property? Enter the property name (or press <return> to stop adding fields):
     >

      Success!
    Next: When you're ready, create a migration with php bin/console make:migration
 */
