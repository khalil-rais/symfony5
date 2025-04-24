<?php

namespace App\Factory;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<Question>
 *
 * @method        Question|Proxy                              create(array|callable $attributes = [])
 * @method static Question|Proxy                              createOne(array $attributes = [])
 * @method static Question|Proxy                              find(object|array|mixed $criteria)
 * @method static Question|Proxy                              findOrCreate(array $attributes)
 * @method static Question|Proxy                              first(string $sortedField = 'id')
 * @method static Question|Proxy                              last(string $sortedField = 'id')
 * @method static Question|Proxy                              random(array $attributes = [])
 * @method static Question|Proxy                              randomOrCreate(array $attributes = [])
 * @method static QuestionRepository|ProxyRepositoryDecorator repository()
 * @method static Question[]|Proxy[]                          all()
 * @method static Question[]|Proxy[]                          createMany(int $number, array|callable $attributes = [])
 * @method static Question[]|Proxy[]                          createSequence(iterable|callable $sequence)
 * @method static Question[]|Proxy[]                          findBy(array $attributes)
 * @method static Question[]|Proxy[]                          randomRange(int $min, int $max, array $attributes = [])
 * @method static Question[]|Proxy[]                          randomSet(int $number, array $attributes = [])
 */
final class QuestionFactory extends PersistentProxyObjectFactory
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
        return Question::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {

        return [
            'name' => self::faker()->realText(50),
            'slug' => self::faker()->slug(),
            'question' => self::faker()->paragraphs(
                self::faker()->numberBetween(1, 4),
                true
            ),
            'askedAt' => self::faker()->boolean(70) ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-100 days', '-1 minute')) : null,
            'votes' => rand(-20, 50),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Question $question): void {})
        ;
    }
}
