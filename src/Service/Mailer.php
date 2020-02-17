<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment as Twig_Environment;
use Twig\Error\LoaderError as Twig_Error_Loader;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Mailer
{
    public const FROM_ADDRESS = 'noreply@wotconsole.info';
    public const ADMIN_ADDRESS = 'vadimua757@gmail.com';

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
     * @throws RuntimeError
     * @throws SyntaxError
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

    /**
     *
     * @param $msg
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Twig_Error_Loader
     */
    public function sendParsedMessage($msg)
    {
        $messageBody = $this->twig->render('security/parsed.html.twig', [
            'msg' => $msg
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Товары обновлены')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo(self::ADMIN_ADDRESS)
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);
    }

    /**
     *
     * @param $user
     * @param $product
     * @param $price_old
     * @param $price_new
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Twig_Error_Loader
     */
    public function sendChangePriceMessage($product, $price_old, $price_new, $user)
    {
        $messageBody = $this->twig->render('security/price.html.twig', [
            'product' => $product,
            'price_old' => $price_old,
            'price_new' => $price_new
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Изменилась цена')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo($user)
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);
    }

    /**
     *
     * @param $url
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Twig_Error_Loader
     */

    public function sendNewSite($url)
    {
        $messageBody = $this->twig->render('security/site.html.twig', [
            'url' => $url,
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Попытка добавить товар с сайта, которого нет в настройках')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo(self::ADMIN_ADDRESS)
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);
    }
}