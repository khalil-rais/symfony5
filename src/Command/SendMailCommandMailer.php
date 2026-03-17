<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:send-mail-mailer', description: 'Send a test email via Symfony Mailer (uses MAILER_DSN)')]
final class SendMailCommandMailer extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from(new Address('hello@example.com', 'Mailer Test'))
            ->to(new Address('khalil.raies@check24.de'))
            ->subject('You are awesome!')
            ->text('Congrats for sending test email with Symfony Mailer!')
        ;

        $this->mailer->send($email);

        $output->writeln('<info>Email sent successfully via Symfony Mailer.</info>');

        return Command::SUCCESS;
    }
}
