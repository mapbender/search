<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 *
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 *
 * @method StyleMap getById(integer $id, $userId=null)
 * @method StyleMap[] getAll($userId)
 * @method StyleMap save(StyleMap $entity, $userId)
 * @method StyleMap createFiltered(array $data)
 */
class StyleMapManager extends BaseManager
{
    /** @var StyleManager */
    protected $styleManager;

    /**
     * StyleManager constructor.
     *
     * @param StyleManager $styleManager
     * @param string $sqlitePath
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function __construct(StyleManager $styleManager, $sqlitePath)
    {
        parent::__construct($sqlitePath);
        $this->styleManager = $styleManager;
    }

    /**
     * @param array $args
     * @return StyleMap
     */
    public function create($args)
    {
        return new StyleMap($args);
    }

    /**
     * @param string $styleMapId
     * @param string $styleId
     * @param string $userId
     * @return bool
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function addStyle($styleMapId, $styleId, $userId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception('Der Style kann nicht hinzugefügt werden. Er existiert nicht mehr.');
            }

            $styleMap->addStyle($styleId);
            $style->addStyleMap($styleMapId);

            $this->styleManager->save($style, $userId);
            return $this->save($styleMap, $userId) ? true : false;
        }

        return false;
    }


    /**
     * @param string $styleMapId
     * @param string $styleId
     * @param string $userId
     * @return bool
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function removeStyle($styleMapId, $styleId, $userId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception('Der Style kann nicht gelöscht werden. Er gehört nicht zu der Stylemap.');
            }

            $style->removeStyleMapById($styleMapId);
            $styleMap->removeStyleById($styleId);

            $this->styleManager->save($style, $userId);
            return $this->save($styleMap, $userId) ? true : false;
        }

        return false;
    }
}
