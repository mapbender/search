<?php
namespace Mapbender\SearchBundle\Controller;

use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\DataSourceBundle\Utils\HTTPStatusConstants;
use Mapbender\SearchBundle\Component\StyleManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StyleController
 *
 * @package Mapbender\SearchBundle\Element
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleController
{
    /** @var SecurityContext */
    private $securityContext;

    /** @var StyleManager */
    private $styleManager;

    /** @var User */
    private $user;

    /**
     * StyleController constructor.
     *
     * @param Request $requestService
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container       = $container;
        $this->securityContext = $container->get("security.context");
        $this->styleManager    = $container->get("mapbender.style.manager");
        $this->user            = $this->securityContext->getUser();
    }


    /**
     * @param string  $key
     * @param Request $request
     */
    public function handle($key, Request $request)
    {
        switch ($key) {
            case 'style/get':
                return $this->get($request->get("id"));
            case 'style/update':
                return $this->update($request);
            case 'style/remove':
                return $this->remove($request->get("id"));
        }
    }


    /**
     * @param       $message
     * @param int   $status
     * @param array $headers
     * @return JsonResponse
     */
    private function getErrorMessage($message, $status = HTTPStatusConstants::_BAD_REQUEST, $headers = array())
    {
        $errors = array('errors' => array('message' => $message));
        return new JsonResponse($errors, $status, $headers);
    }

    /**
     * @param int   $id
     * @param int   $status
     * @param array $headers
     * @return JsonResponse
     */
    private function getEmptyMessage($id, $status = HTTPStatusConstants::_NOT_FOUND, $headers = array())
    {
        $errors = array('errors' => array('message' => "Could not alter/find stylemap for id " . $id));
        return new JsonResponse($errors, $status, $headers);
    }


    /**
     * @Route("{id}",requirements={"id" = "\w+"})
     * @param int $int
     * @Method("GET")
     * @return JsonResponse
     */
    public function get($id)
    {
        if ($this->securityContext->isUserAllowedToView($this->user)) {
            $styleMap = $this->styleManager->getById($id);

            if ($styleMap != null) {
                return $this->getSuccessMessage($styleMap);
            } else {
                return $this->getEmptyMessage($id);
            }
        }

        return $this->getErrorMessage("Get: Current user is not authorized to access style with id " . $id, HTTPStatusConstants::_UNAUTHORIZED);

    }

    /**
     * Updates or creates new StyleMap
     * @Route("{id}/update")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update($request)
    {
        $id = $request->get("id");

        if ($this->securityContext->isUserAllowedToEdit($this->user)) {
            $styleMap = $this->styleManager->update($request->request->all());
            return $this->getSuccessMessage($styleMap);
        }
        return $this->getErrorMessage("Update : Current user is not authorized to access style with id " . $id, HTTPStatusConstants::_UNAUTHORIZED);

    }


    /**
     * @Route("{id}/remove",requirements={"id" = "\w+"})
     * @Method("GET")
     * @return JsonResponse
     */
    public function remove($id)
    {
        if ($this->securityContext->isUserAllowedToDelete($this->user)) {
            $isRemoved = $this->styleManager->remove($id);
            return $isRemoved ? $this->getSuccessMessage($isRemoved) : $this->getEmptyMessage($id);
        }
        return $this->getErrorMessage("Remove: Current user is not authorized to access style with id " . $id, HTTPStatusConstants::_UNAUTHORIZED);
    }

    /**
     * @param mixed $payload
     * @param int   $status
     * @param array $headers
     * @return JsonResponse
     */
    private function getSuccessMessage($payload, $status = HTTPStatusConstants::_OK, $headers = array())
    {
        return new JsonResponse($payload, $status, $headers);
    }


}