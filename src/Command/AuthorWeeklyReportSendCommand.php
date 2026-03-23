<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
