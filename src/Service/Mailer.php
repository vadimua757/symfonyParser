<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment as Twig_Environment;
use Twig\Error\LoaderError as Twig_Error_Loader;

class Mailer
{
    public const FROM_ADDRESS = 'admin@admin.ua';

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var Twig_Environment
     */
    private $twig;

    public function __construct(
        Swift_Mailer $mailer,
        Twig_Environment $twig

    )  {
        $this->mailer = $mailer;
        $this->twig = $twig;

    }

    /**
     * @param User $user
     *
     * @throws Twig_Error_Loader
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendConfirmationMessage(User $user)
    {
        $messageBody = $this->twig->render('security/confirmation.html.twig', [
            'user' => $user
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Вы успешно прошли регистрацию!')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo($user->getEmail())
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);
    }
}