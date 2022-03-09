<?php


namespace Mapbender\SearchBundle\Component;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Mapbender\CoreBundle\Entity\Element;

class ConfigFilter
{
    /** @var array[] */
    protected $featureTypes;

    public function __construct($featureTypes)
    {
        $this->featureTypes = $featureTypes ?: array();
    }

    public function getSchemaConfigByName(Element $element, $schemaName)
    {
        $config = $element->getConfiguration() + array('schemas' => array());
        if (\is_numeric($schemaName)) {
            // Uh-oh. Schema "id" passed in.
            $names = \array_keys($config['schemas']);
            $schemaName = $names[$schemaName];
        }
        $defaults = array(
            'fields' => array(),
        );
        return $config['schemas'][$schemaName] + $defaults;
    }

    public function getFeatureTypeConfigForSchema(Element $element, $schemaName)
    {
        $schemaConfig = $this->getSchemaConfigByName($element, $schemaName);
        $ftConfig = $this->featureTypes[$schemaConfig['featureType']];
        if (empty($ftConfig['title'])) {
            $ftConfig['title'] = ucfirst($schemaConfig['featureType']);
        }
        return $ftConfig;
    }

    public function expandSqlOptions(Connection $connection, $fieldConfigs)
    {
        $configsOut = array();
        foreach ($fieldConfigs as $fieldConfig) {
            if (!empty($fieldConfig['sql'])) {
                $values = $connection->executeQuery($fieldConfig['sql'])->fetchAll(FetchMode::COLUMN);
                $fieldConfig['options'] = \array_combine($values, $values);
            }
            unset($fieldConfig["connection"]);
            unset($fieldConfig["sql"]);
            $configsOut[] = $fieldConfig;
        }
        return $configsOut;
    }
}
