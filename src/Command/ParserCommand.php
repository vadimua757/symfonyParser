<?php
namespace App\Command;

use App\Repository\ProductRepository;
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = new Kernel('prod', false);
        $request = Request::create('/products/batch_update', 'GET');
        try {
            $response = $kernel->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
        } catch (Exception $e) {
        }

        $output->writeln([
            $e->getMessage()
        ]);
    }
}