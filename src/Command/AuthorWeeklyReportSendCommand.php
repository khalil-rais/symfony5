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
    public function __construct(UserRepository $userRepository)
    {
        parent::__construct(null);

        $this->userRepository = $userRepository;
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
            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->success('Weekly reports were sent to authors!');

        return Command::SUCCESS;
    }
}
