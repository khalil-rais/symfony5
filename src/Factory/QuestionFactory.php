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
            'name' => 'Missing Dress',
            'slug' => 'missing-dress-' . rand(0, 1000),
            'question' => <<<EOF
Hi! So... I'm having a *weird* day. Yesterday, I cast a spell 
to make my dishes wash themselves. But while I was casting it, 
I slipped a little and I think `I also hit my dress with the spell`.

When I woke up this morning, I caught a quick glimpse of my dresses 
opening the front door and walking out! I've been out all afternoon 
(with no dresses mind you) searching for them.

Does anyone have a spell to call your dresses back?
EOF,
            'votes' => rand(-20, 50),
            'askedAt' => rand(1, 10) > 2 ? new \DateTimeImmutable(sprintf('-%d days', rand(1, 100))) : null,
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
