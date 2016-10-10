<?php

namespace Mapbender\SearchBundle\Controller;

use Mapbender\CoreBundle\Component\SecurityContext;

use Mapbender\DataSourceBundle\Utils\HTTPStatusConstants;

use Mapbender\SearchBundle\Security\StyleManagerVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class StyleController
 *
 * @package Mapbender\SearchBundle\Controller
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @Route("/style/")
 * TODO: This class is still WIP and non-functional !
 */
class StyleController extends Controller
{

    /**
     * @param int $id
     * @Router("/{id}")
     * @Method("GET")
     * @return JSONResponse
     */
    public function get($id)
    {
        /** @var SecurityContext $securityContext * */
        $securityContext = $this->container->get("security.context");
        $styleManager    = $this->container->get("mapbender.style.manager");
        $styleManager->setUserId($securityContext->getUser()->getId());
        $styleMap = array();
        if ($securityContext->isGranted(StyleManagerVoter::GET, $styleManager)) {
            $styleMap = $styleManager->getById($id);

            $status = $styleMap == null ? HTTPStatusConstants::_NOT_FOUND : HTTPStatusConstants::_OK;
            $header = array();
            if ($styleMap != null) {
                $isAllowedToGet = $styleMap->getId() == $userId ||
                    $styleMap->getId() == SecurityContext::USER_ANONYMOUS_ID;

                if (!$isAllowedToGet) {
                    $styleMap = array();
                    $status   = HTTPStatusConstants::_UNAUTHORIZED;

                } else {

                }
            }

        }

        $response = new JsonResponse($styleMap, $status, $header);
        return $response;

    }


    /**
     * @param array $args
     * @Router("{id}/update")
     * @Method("POST")
     * @return JSONResponse
     */
    public function update($args)
    {
        /** @var SecurityContext $securityContext * */
        $securityContext = $this->container->get("security.context");
        $styleManager    = $this->get("mapbender.style.manager");

        $hasId  = isset($args["id"]);
        $status = $hasId ? HTTPStatusConstants::_OK : HTTPStatusConstants::_BAD_REQUEST;

        $data     = array();
        $header   = array();
        $response = new JsonResponse($data, $status, $header);
        if ($hasId) {

        }
        return $response;
    }


    /**
     * @param int $id
     * @Router("{id}/remove")
     * @return JSONResponse
     */
    public function remove($id)
    {

        /** @var SecurityContext $securityContext * */
        $securityContext = $this->container->get("security.context");

        $status = 200;
        $data   = array();
        $header = array();

        $response = new JsonResponse($data, $status, $header);

        return $response;
    }


}