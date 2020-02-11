<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Statistic;
use App\Repository\StatisticRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Doctrine\ORM\Query;


class StatisticController extends AbstractController
{
    /**
     * @var StatisticRepository
     */
    private $statisticRepository;

    /** @var StatisticRepository $statisticRepository */
    public function __construct(StatisticRepository $statisticRepository)
    {
        $this->statisticRepository = $statisticRepository;
    }
    /**
     * @Route("/statistic/{id}", name="statistic")
     * @param $id
     * @param Breadcrumbs $breadcrumbs
     * @return Response
     */
    public function index($id,Breadcrumbs $breadcrumbs)
    {
        $breadcrumbs->addRouteItem("Users", "users");
        $breadcrumbs->prependRouteItem("Home", "site");

        $statistics = $this->statisticRepository->findBy(['product' => $id]);

        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository('App\Entity\Product')->findOneBy(['id' => $id]);
        $result = $em->getRepository('App\Entity\Statistic')->findBy(['product'=>$product]);

        $data = [];
        foreach ($result as $key => $value){
            $data[] = [$value->getDate()->format('Y-m-d'), intval($value->getPrice())];
        }

        $series = [
            [
                "name" => $product->getName(),
                "data" => $data
            ]
        ];

        $ob = new Highchart();
        $ob->chart->renderTo('linechart');  // The #id of the div where to render the chart
        $ob->title->text('Chart Title');
        $ob->xAxis->title(array('text'  => "Date"));
        $ob->xAxis->type('category');
        $ob->yAxis->title(array('text'  => "Price"));
        $ob->plotOptions->series(
            array(
                'dataLabels' => array(
                    'enabled' => true,
                    ),
                'enableMouseTracking' => false
            )
        );
        $ob->series($series);
        
        return $this->render('statistic/index.html.twig', [
            'statistics' => $statistics,
            'chart' => $ob,
        ]);
    }

    /**
     * @Route("/statistic/{id}/update", name="statistic_update")
     * @param $product
     * @param $price
     * @return void
     * @throws Exception
     */
    public function update($product, $price)
    {
        $em = $this->getDoctrine()->getManager();
        $statistic = $em->getRepository('App\Entity\Statistic')->findOneBy(['product' => $product]);
        if (!$statistic || $statistic->getDate() !== new DateTime()){
            $statistic = new Statistic();
            $statistic->setProduct($product);
            $statistic->setPrice($price);
            $statistic->setDate(new DateTime());

            $em->persist($statistic);
            $em->flush();
        }
    }
}
