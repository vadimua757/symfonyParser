<?php
namespace App\Command;

use App\Repository\ProductRepository;
use App\Service\Mailer;
use App\Service\Updater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Controller\ProductsController;
use Exception;
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ParserCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:parse';

    protected function configure()
    {
        $this
            ->setName('app:parse');
    }

    private $em;
    private $mailer;

    public function __construct(EntityManagerInterface $em, Mailer $mailer) {
        parent::__construct();
        $this->em = $em;
        $this->mailer = $mailer;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = new Updater($this->em, $this->mailer);
        $result ='';
        try {
            $result = $updater->updateAll();
        } catch (Exception $e) {
            $output->writeln([
                $e->getMessage()
            ]);
        }
        $output->writeln($result);
        return 0;
    }
}