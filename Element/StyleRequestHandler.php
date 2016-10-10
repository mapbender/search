<?php
namespace Mapbender\SearchBundle\Element;

use Mapbender\DataSourceBundle\Utils\HTTPStatusConstants;
use Mapbender\SearchBundle\Security\StyleManagerVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class StyleRequestHandler
 *
 * @package Mapbender\SearchBundle\Element
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleRequestHandler
{
    /**
     * StyleRequestHandler constructor.
     *
     * @param array           $configuration
     * @param Request         $requestService
     * @param KernelInterface $kernel
     */
    public function __construct($configuration, $requestService, $kernel)
    {
        $this->request         = json_decode($requestService->getContent(), true);
        $this->schemas         = $configuration["schemes"];
        $this->debugMode       = $configuration['debug'] || $kernel->getEnvironment() == "dev";
        $this->schemaName      = isset($request["schema"]) ? $request["schema"] : $requestService->get("schema");
        $this->defaultCriteria = array('returnType' => 'FeatureCollection',
                                       'maxResults' => 2500);

        $this->container = $kernel->getContainer();
        /**@var SecurityContextInterface $securityContext * */
        $this->securityContext = $this->container->get("security.context");
        $this->styleManager    = $this->container->get("mapbender.style.manager");

    }


    /**
     * @param string $key
     */
    public function handle($key)
    {

        if (!isset($this->request) || !isset($this->request['id'])) {
            $errorMessage = $key . ": id not defined!";
            return $this->getErrorMessage($errorMessage);
        }

        switch ($key) {
            case 'style/get':
                return $this->get($key);

            case 'style/update':
                return $this->update($key);

            case 'style/remove':
                return $this->remove($key);
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

    public function get($key)
    {

        $id = $this->request['id'];
        if ($this->isAllowedToGet()) {
            $styleMap = $this->styleManager->getById($id);

            if ($styleMap != null) {
                return $this->getSuccessMessage($styleMap);
            } else {
                return $this->getEmptyMessage($id);
            }
        }

        return $this->getErrorMessage($key . ": Current user is not authorized to access style with id " . $id, HTTPStatusConstants::_UNAUTHORIZED);

    }

    /**
     * Updates or creates new StyleMap
     *
     * @param $key
     * @return JsonResponse
     */
    public function update($key)
    {
        $id = $this->request['id'];

        if ($this->isAllowedToUpdate()) {
            $styleMap = $this->styleManager->update($this->request);
            return $this->getSuccessMessage($styleMap);
        }
        return $this->getErrorMessage($key . ": Current user is not authorized to access style with id " . $id, HTTPStatusConstants::_UNAUTHORIZED);

    }

    /**
     * @param $key
     * @return JsonResponse
     */
    public function remove($key)
    {
        $id = $this->request['id'];

        if ($this->isAllowedToRemove()) {
            $isRemoved = $this->styleManager->remove($id);
            return $isRemoved ? $this->getSuccessMessage($isRemoved) : $this->getEmptyMessage($id);
        }
        return $this->getErrorMessage($key . ": Current user is not authorized to access style with id " . $id, HTTPStatusConstants::_UNAUTHORIZED);
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


    /**
     * @return boolean
     */
    private function isAllowedToGet()
    {
        /**@var SecurityContextInterface $securityContext * */
        return $this->securityContext->isGranted(StyleManagerVoter::GET, $this->styleManager);

    }

    /**
     * @return boolean
     */
    private function isAllowedToUpdate()
    {
        /**@var SecurityContextInterface $securityContext * */
        return $this->securityContext->isGranted(StyleManagerVoter::CREATE, $this->styleManager);

    }

    /**
     * @return boolean
     */
    private function isAllowedToRemove()
    {
        /**@var SecurityContextInterface $securityContext * */
        return $this->securityContext->isGranted(StyleManagerVoter::REMOVE, $this->styleManager);

    }


}