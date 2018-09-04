<?php

namespace Mapbender\DataSourceBundle\Tests;

use Eslider\Driver\HKVStorage;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Component\StyleMapManager;
use Mapbender\SearchBundle\Entity\Field;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;
use Mapbender\SearchBundle\Entity\Style;
use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class AllManagerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class AllManagerTest extends SymfonyTest2
{

    /** @var QueryManager */
    protected $queryManager;

    /** @var StyleManager */
    protected $styleManager;

    /** @var StyleMapManager */
    protected $styleMapManager;

    /** @var array styleData */
    protected $styleData;

    /** @var FeatureTypeService */
    protected $featureManager;

    /** @var array */
    protected $featureTypeDeclaration;

    /** @var array */
    protected $queryArgs;



    protected function setUp()
    {
        $this->styleManager    = $this->getStyleManager();
        $this->queryManager    = $this->getQueryManager();
        $this->styleMapManager = $this->getStyleMapManager();
        $this->featureManager  = $this->getFeatureTypeService();
        $this->styleManager->createDB();
        $this->queryManager->createDB();
        $this->styleMapManager->createDB();

        $this->styleData = array("name"            => "DefaultStyle",
                                 "borderSize"      => 5,
                                 "borderColor"     => "0c0c0c",
                                 "borderAlpha"     => 255,
                                 "backgroundSize"  => 41,
                                 "backgroundAlpha" => 255,
                                 "backgroundColor" => "fc0c0c",
                                 "graphicWidth"    => "100px",
                                 "graphicHeight"   => "100px",
                                 "graphicOpacity"  => "50%",
                                 "graphicXOffset"  => "40px",
                                 "graphicYOffset"  => "20px",
                                 "graphicName"     => "DefaultGraphic",
                                 "externalGraphic" => "$(default)",
                                 "fillOpacity"     => "50%",
                                 "fillColor"       => "#411232",
                                 "strokeColor"     => "#dcc23a",
                                 "strokeOpacity"   => "30%",
                                 "strokeWidth"     => "3px"
        );

        $this->featureTypeDeclaration = $this->featureManager->getFeatureTypeDeclarations();
        $lastFeatureType              = end($this->featureTypeDeclaration);
        $lastFeatureTypeId            = $lastFeatureType["id"];

        $queryConditionArgs = array("name"      => "Big money",
                                    "fieldName" => "money",
                                    "operator"  => ">",
                                    "value"     => "1000");

        $queryConditions = new QueryCondition($queryConditionArgs);
        $fieldArgs       = array("name" => "Fieldname", "description" => "Some field description");
        $field           = new Field($fieldArgs);

        $this->queryArgs = array("name"        => "QueryName",
                                 "conditions"  => $queryConditions,
                                 "fields"      => array($field),
                                 "featureType" => $lastFeatureTypeId,
                                 "sql"         => "SELECT * from QUERIES",

        );

    }

    public function testCreateStyle()
    {
        self::assertEquals($this->styleData["graphicWidth"], $this->getMockupStyle()->getGraphicWidth());
    }


    public function testCreateStyleMap()
    {
        /**@var StyleMap $styleMap */
        /**@var Style $style */

        list($style, $styleMap) = $this->getMockUpStyleMap();
        $styles = $styleMap->getStyles();
        self::assertEquals($styles[ $style->getId() ], $style->getId());
    }


    public function testCreateQuery()
    {
        $query = $this->getQuery();
        var_dump($query);
        self::assertNotNull($query);
    }

    public function testSaveQuery()
    {
        $query = $this->getQuery();

        $this->queryManager->save($query);
        $query1 = $this->queryManager->getById($query->getId());
        self::assertEquals(HKVStorage::encodeValue($query), HKVStorage::encodeValue($query1));
        $query1->setName("New Name");
        $this->queryManager->save($query1);
        $query2 = $this->queryManager->getById($query1->getId());
        self::assertEquals("New Name", $query2->getName());

    }


    public function testSaveStyleWorkflow()
    {
        /**@var StyleMap $styleMap */
        /**@var Style $style */

        list($style, $styleMap) = $this->getMockUpStyleMap();

        $this->styleMapManager->save($styleMap);

        try {
            $this->styleMapManager->addStyle( $styleMap->getId(), $style->getId());
        } catch (Exception $exception) {
            self::assertNotNull($exception);
        }

        $style=$this->styleManager->save($style);
        $wasSaved = $this->styleMapManager->addStyle( $styleMap->getId(), $style->getId());
        self::assertTrue($wasSaved);

        $wasRemoved = $this->styleMapManager->removeStyle( $styleMap->getId(), $style->getId());
        self::assertTrue($wasRemoved);

        try {
            $this->styleMapManager->removeStyle( $styleMap->getId(), $style->getId());
        } catch (Exception $exception) {
            self::assertNotNull($exception);
        }

    }



    /**
     * Help methods
     */

    /**
     * @return Style
     */
    public function getMockupStyle()
    {
        return $this->styleManager->create($this->styleData);
    }


    /**
     * @return StyleManager
     */
    public function getStyleManager()
    {
         return  self::$container->get("mapbender.style.manager");
    }

    /**
     * @return QueryManager
     */
    private function getQueryManager()
    {

        return self::$container->get("mapbender.query.manager");

    }

    /**
     * @return StyleMapManager
     */
    private function getStyleMapManager()
    {
        return self::$container->get("mapbender.stylemap.manager");
    }

    /**
     * @return FeatureTypeService
     */
    private function getFeatureTypeService()
    {
        $serviceId = self::$container->getParameter('mapbender.search.featuretype.service.id');
        /** @var FeatureTypeService $service */
        $service = self::$container->get($serviceId);
        return $service;
    }

    /**
     * @return array
     */
    protected function getMockUpStyleMap()
    {
        $style    = $this->getMockupStyle();
        $styleMap = $this->styleMapManager->create(array("styles" => array($style->getId() => $style->getId())));
        return array($style, $styleMap);
    }

    /**
     * @return Query
     */
    protected function getQuery()
    {
        $mockUpStyleMap              = $this->getMockUpStyleMap();
        $this->queryArgs["styleMap"] = $this->styleMapManager->create($mockUpStyleMap)->getId();
        $query                       = $this->queryManager->create($this->queryArgs);
        return $query;
    }


}