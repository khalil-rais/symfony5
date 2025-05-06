<?php

namespace App\Factory;

use App\Entity\QuestionTag;
use App\Repository\QuestionTagRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<QuestionTag>
 *
 * @method        QuestionTag|Proxy                              create(array|callable $attributes = [])
 * @method static QuestionTag|Proxy                              createOne(array $attributes = [])
 * @method static QuestionTag|Proxy                              find(object|array|mixed $criteria)
 * @method static QuestionTag|Proxy                              findOrCreate(array $attributes)
 * @method static QuestionTag|Proxy                              first(string $sortedField = 'id')
 * @method static QuestionTag|Proxy                              last(string $sortedField = 'id')
 * @method static QuestionTag|Proxy                              random(array $attributes = [])
 * @method static QuestionTag|Proxy                              randomOrCreate(array $attributes = [])
 * @method static QuestionTagRepository|ProxyRepositoryDecorator repository()
 * @method static QuestionTag[]|Proxy[]                          all()
 * @method static QuestionTag[]|Proxy[]                          createMany(int $number, array|callable $attributes = [])
 * @method static QuestionTag[]|Proxy[]                          createSequence(iterable|callable $sequence)
 * @method static QuestionTag[]|Proxy[]                          findBy(array $attributes)
 * @method static QuestionTag[]|Proxy[]                          randomRange(int $min, int $max, array $attributes = [])
 * @method static QuestionTag[]|Proxy[]                          randomSet(int $number, array $attributes = [])
 */
final class QuestionTagFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return QuestionTag::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'question' => QuestionFactory::new(),
            'tag' => TagFactory::new(),
            'taggedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(QuestionTag $questionTag): void {})
        ;
    }
}
