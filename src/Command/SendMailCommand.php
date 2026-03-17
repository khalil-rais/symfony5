<?php
# src/Command/SendMailCommand.php
# php bin/console app:send-mail

namespace App\Command;

use Mailtrap\Api\AbstractApi;
use Mailtrap\Config;
use Mailtrap\EmailHeader\CategoryHeader;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:send-mail')]
final class SendMailCommand extends Command
{
    private string $API_KEY ;
    private const IS_SANDBOX = true;
    private const INBOX_ID = 4465901;

    public function __construct(){
        parent::__construct();
        $this->API_KEY = (string) ($_ENV['APP_MAILER_DSN_KEY'] ?? getenv('APP_MAILER_DSN_KEY') ?: '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = new Config($this->API_KEY);
        if (self::IS_SANDBOX) {
            $config->setHost(AbstractApi::SENDMAIL_SANDBOX_HOST);
        }

        $mailtrap = new MailtrapClient($config);

        $email = (new Email())
            ->from(new Address('hello@example.com', 'Mailtrap Test'))
            ->to(new Address('khalil.raies@check24.de'))
            ->subject('You are awesome!')
            ->text('Congrats for sending test email with Mailtrap!')
        ;
        $email->getHeaders()->add(new CategoryHeader('Integration Test'));

        if (self::IS_SANDBOX) {
            $response = $mailtrap->sandbox()->emails()->send($email, self::INBOX_ID);
        } else {
            $response = $mailtrap->sending()->emails()->send($email);
        }

        $output->writeln('Response: ' . json_encode(ResponseHelper::toArray($response), \JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}