<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailerDebugCommand extends Command
{
    protected static $defaultName = 'app:mailer:debug';

    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        parent::__construct();
        $this->transport = $transport;
    }

    protected function configure(): void
    {
        $this->setDescription('Shows which mailer DSN and transport are in use (to debug missing emails)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dsn = $_ENV['APP_MAILER_DSN'] ?? getenv('APP_MAILER_DSN') ?: '(not set)';
        $transportClass = \get_class($this->transport);

        $io->title('Mailer configuration');
        $io->table(
            ['Setting', 'Value'],
            [
                ['APP_MAILER_DSN (from env)', $dsn],
                ['Transport class', $transportClass],
            ]
        );

        if (str_contains($transportClass, 'NullTransport') || $dsn === '(not set)' || $dsn === 'null://null') {
            $io->warning([
                'Emails are NOT being sent.',
                'Either APP_MAILER_DSN is not set / is null, or the null transport is in use (emails are discarded).',
                'Set APP_MAILER_DSN in .env or .env.local to a real DSN (e.g. Mailtrap, SMTP) and clear cache: php bin/console cache:clear',
            ]);
        } else {
            $io->success('A real transport is configured. If you still do not receive emails, check the DSN (e.g. Mailtrap inbox, not your normal mailbox).');
        }

        return Command::SUCCESS;
    }
}
