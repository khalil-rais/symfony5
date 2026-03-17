<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Sends an email the same way as app:send-mail-mailer (Symfony Mailer + Mime Email).
 */
class SendMailController extends AbstractController
{
    /**
     * @Route("/send-mail/debug", name="app_send_mail_debug", methods={"GET"})
     */
    public function debug(Request $request, TransportInterface $transport): Response
    {
        $dsn = $_ENV['APP_MAILER_DSN'] ?? getenv('APP_MAILER_DSN') ?: '(not set)';
        $dsnMasked = \is_string($dsn) && $dsn !== '(not set)'
            ? preg_replace('#://([^:]+):([^@]+)@#', '://$1:****@', $dsn)
            : $dsn;

        $connectionData = [
            'APP_MAILER_DSN (env)' => $dsnMasked,
            'Transport class'   => \get_class($transport),
            'APP_ENV'           => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '(not set)',
            'Is null transport' => str_contains(\get_class($transport), 'NullTransport'),
        ];

        $html = '<!DOCTYPE html><html><head><title>Mailer connection (web)</title></head><body>';
        $html .= '<h1>Mailer connection data (web context)</h1><pre>' . htmlspecialchars(json_encode($connectionData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)) . '</pre>';
        $html .= '<p><a href="' . $this->generateUrl('app_send_mail') . '">Send test email</a></p>';
        $html .= '</body></html>';

        return new Response($html);
    }

    /**
     * @Route("/send-mail", name="app_send_mail", methods={"GET"})
     */
    public function __invoke(Request $request, MailerInterface $mailer, TransportInterface $transport): Response
    {
        $isNullTransport = str_contains(\get_class($transport), 'NullTransport');

        if ($isNullTransport) {
            $this->addFlash('warning', 'No email was sent: the app is using the null mailer transport. Set APP_MAILER_DSN in .env or .env.local and run: php bin/console cache:clear');
            return $this->redirectToRoute('app_login');
        }

        $email = (new Email())
            ->from(new Address('hello@example.com', 'Mailer Test'))
            ->to(new Address('khalil.raies@check24.de'))
            ->subject('You are awesome!')
            ->text('Congrats for sending test email with Symfony Mailer!')
        ;

        $mailer->send($email);

        $this->addFlash('success', 'Email sent successfully via Symfony Mailer.');

        return $this->redirectToRoute('app_login');
    }
}
