<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;

/*
Let's bootstrap the command the lazy way.
Find your terminal and run:
php bin/console make:command
Call it app:author-weekly-report:send.
Perfect! Back in the editor,
head to the src/Command directory to find
our shiny new console command.

Let's start customizing this:
we don't need any arguments or options
and I'll change the description:
“Send weekly reports to authors.”

 */

#[AsCommand(
    name: 'app:author-weekly-report:send',
    description: 'Send weekly reports to authors.',
)]
class AuthorWeeklyReportSendCommand extends Command
{
    private $userRepository;
    /*
        Back in the command, let's autowire the repository by adding a constructor.
        This is one of the rare cases
        where we have a parent class
        and the parent class has a constructor.
        I'll go to the Code -> Generate menu - or Command + N on a Mac - and select "Override methods"
        to override the constructor.
        Notice that this added a $name argument -
        that's an argument in the parent constructor -
        and it called the parent constructor.
        That's important: the parent class needs to set some stuff up.
        But, we don't need to pass the command name:
        Symfony already gets that from a static property on our class.
        Instead, make the first argument: UserRepository $userRepository.
        Hit Alt + Enter and select "Initialize fields"
        to create that property and set it. Perfect.
    */

    private $articleRepository;

    public function __construct(UserRepository $userRepository, ArticleRepository $articleRepository)
    {
        parent::__construct(null);

        $this->userRepository = $userRepository;
        /*
            Boom! Back in the command, autowire the repository via the second constructor argument:
            ArticleRepository $articleRepository.
            Hit Alt + Enter to initialize that field.
         */
        $this->articleRepository = $articleRepository;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /*
            Next, in execute(), clear everything out except for the $io variable,
            which is a nice little object that helps us print things and interact with the user in a pretty way.
            Start with $authors = $this->userRepository->findAllSubscribedToNewsletter().
            Well, this really returns all users not just authors -
            but we'll filter them out in a minute.
            To be extra fancy, let's add a progress bar!
            Start one with $io->progressStart().
            Then, foreach over $authors as $author,
            and advance the progress inside.
            Oh, and of course, for progressStart(),
            I need to tell it how many data points we're going to advance.
            Use count($authors).
            Leave the inside of the foreach empty for now, and after, say $io->progressFinish().
            Finally, for a big happy message, add $io->success()
            “Weekly reports were sent to authors!”
            Brilliant! We're not doing anything yet
            but let's try it!
            Copy the command name, find your terminal, and do it!
            php bin/console app:author-weekly-report:send
            Super fast!
         */
        $io = new SymfonyStyle($input, $output);
        $authors = $this->userRepository
            ->findAllSubscribedToNewsletter();
        $io->progressStart(count($authors));
        foreach ($authors as $author) {
            /*
                Inside the foreach, the next step is to find all the articles this user published -
                if any - from the past week.
             */
            $io->progressAdvance();
            /*
                Down in execute, we can say
                $articles = $this->articleRepository->findAllPublishedLastWeekByAuthor()
                and pass that $author.
             */
            $articles = $this->articleRepository
                ->findAllPublishedLastWeekByAuthor($author);
            // Skip authors who do not have published articles for the last week
            if (count($articles) === 0) {
                /*
                    Phew! Because we're actually querying for all users, not everyone will be an author...
                    and even less will have authored some articles in the past 7 days.
                    Let's skip those to avoid sending empty emails:
                    if count($articles) is zero, then continue.
                    By the way, in a real app, where you would have hundreds, thousands or even more users,
                    querying for all that have subscribed is not going to work.
                    Instead, I would make my query smarter by only returning users
                    that are authors or even query for a limited number of authors,
                    keep track of which you've sent to already,
                    then run the command over and over again until everyone has gotten their update.
                    These aren't even the only options.
                    The point is: I'm being a little loose with how much data I'm querying for:
                    be careful in a real app.
                    Ok, I think we're good! I mean, we're not actually emailing yet,
                    but let's make sure it runs.
                    Find your terminal and run the command again:
                    php bin/console app:author-weekly-report:send
                    All smooth. Next... let's actually send an email!
                    And then, fix the duplication we're going to have between our two email templates.
                 */
                continue;
            }
        }
        $io->progressFinish();

        $io->success('Weekly reports were sent to authors!');

        return Command::SUCCESS;
    }
}
