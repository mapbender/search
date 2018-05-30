<?php

namespace Mapbender\SearchBundle\Controller;

use FOM\CoreBundle\Component\ExportResponse;
use FOM\ManagerBundle\Configuration\Route;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Entity\ExportRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExportController
 *
 * @package Mapbender\SearchBundle\Controller
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @Route("/export/")
 */
class ExportController extends Controller
{


    /**@var FeatureTypeService */
    protected $featureService;

    /**@var QueryManager */
    protected $queryManager;

    public function __construct(ContainerInterface $container)
    {
        $this->featureService = $container->get(MapbenderController::MAPBENDER_FEATURE_SERVICE);
        $this->queryManager = $container->get(MapbenderController::MAPBENDER_QUERY_MANAGER);
        $this->setContainer($container);
    }


    /**
     * @Method("POST")
     * @Route("export")
     * @param ExportRequest $request
     * @return ExportResponse
     */
    public function export($request)
    {

        $features = $this->queryManager->listQueriesByFeatureTypes($this->featureService, $request->getIds());

        return new ExportResponse($features, $request->getFilename(),
            $request->getType(), $request->getEncodingFrom(),
            $request->getEnclosure(), $request->getDelimiter(),
            $request->isEnableDownload());
    }

}