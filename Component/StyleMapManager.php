<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 *
 * @method StyleMap getById(integer $id)
 * @method StyleMap[] getAll()
 * @method StyleMap save(StyleMap $entity)
 */
class StyleMapManager extends BaseManager
{
    /** @var StyleManager */
    protected $styleManager;

    /**
     * StyleManager constructor.
     *
     * @param ContainerInterface|null $container
     * @param StyleManager $styleManager
     * @param string $sqlitePath
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function __construct(ContainerInterface $container, StyleManager $styleManager, $sqlitePath)
    {
        parent::__construct($container, $sqlitePath);
        $this->styleManager = $styleManager;
    }

    /**
     * @param array $args
     * @return StyleMap
     */
    public function create($args)
    {
        $styleMap = new StyleMap($args);
        if (!isset($args['id'])) {
            $styleMap->setId($this->generateUUID());
        }
        $styleMap->setUserId($this->getUserId());
        return $styleMap;
    }

    /**
     * @param string $styleMapId
     * @param string $styleId
     * @return bool
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function addStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception('Der Style kann nicht hinzugefügt werden. Er existiert nicht mehr.');
            }

            $styleMap->addStyle($styleId);
            $style->addStyleMap($styleMapId);

            $this->styleManager->save($style);
            return $this->save($styleMap) ? true : false;
        }

        return false;
    }


    /**
     * @param string $styleMapId
     * @param string $styleId
     * @return bool
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function removeStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception('Der Style kann nicht gelöscht werden. Er gehört nicht zu der Stylemap.');
            }

            $style->removeStyleMapById($styleMapId);
            $styleMap->removeStyleById($styleId);

            $this->styleManager->save($style);
            return $this->save($styleMap) ? true : false;
        }

        return false;
    }
}
