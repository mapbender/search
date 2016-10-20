<?php

namespace Mapbender\SearchBundle\Controller;

use FOM\CoreBundle\Component\ExportResponse;
use FOM\ManagerBundle\Configuration\Route;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Entity\ExportRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class ExportController
 *
 * @package Mapbender\SearchBundle\Controller
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @Route("/export/")
 */
class ExportController extends MapbenderController
{


    /**@var FeatureTypeService */
    protected $featureService;

    /**@var QueryManager */
    protected $queryManager;


    /**
     *This method has desired services injected.
     *
     * @return string[]
     */
    protected function mappings()
    {
        return array(
            "featureService"  => static::MAPBENDER_FEATURE_SERVICE,
            "queryManager"    => static::MAPBENDER_QUERY_MANAGER,
            "securityContext" => static::MAPBENDER_SECURITY_CONTEXT
        );
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