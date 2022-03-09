<?php

namespace Mapbender\SearchBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\SearchBundle\Element\Type\SearchAdminType;

/**
 * Class Search

 * @package Mapbender\SearchBundle\Element
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Search extends AbstractElementService implements ConfigMigrationInterface
{
    /** @var SearchHttpHandler */
    protected $httpHandler;

    public function __construct(SearchHttpHandler $httpHandler)
    {
        $this->httpHandler = $httpHandler;
    }

    public static function getClassTitle()
    {
        return 'Search';
    }

    public static function getClassDescription()
    {
        return 'Object search element';
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '/components/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js',
                '@MapbenderSearchBundle/Resources/public/FormUtil.js',
                '@MapbenderSearchBundle/Resources/public/FeatureRenderer.js',
                '@MapbenderSearchBundle/Resources/public/feature-style-editor.js',
                '@MapbenderSearchBundle/Resources/public/TableRenderer.js',
                '@MapbenderSearchBundle/Resources/public/query-manager.js',
                '@MapbenderSearchBundle/Resources/public/mapbender.element.search.js',
            ),
            'css' => array(
                '@MapbenderSearchBundle/Resources/public/sass/element/search.scss',
                '/components/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css',
            ),
            'trans' => array(
                'mb.search.*'
            ),
        );
    }

    /** @inheritdoc */
    public static function getDefaultConfiguration()
    {
        return array(
            'schemas' => array(),
            'cluster_threshold' => 15000,
        );
    }

    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbSearch';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderSearchBundle:Element:search.html.twig');
        $view->attributes['class'] = 'mb-element-search';
        return $view;
    }

    /**
     * Get the element configuration form type.
     *
     * Override this method to provide a custom configuration form instead of
     * the default YAML form.
     *
     * @return string Administration type class name
     */
    public static function getType()
    {
        return SearchAdminType::class;
    }

    /**
     * @return null
     */
    public static function getFormTemplate()
    {
        return false;
    }

    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }

    public static function updateEntityConfig(Element $entity)
    {
        // Fold legacy list-of-somethings clustering config to a single scalar
        // threshold
        $config = $entity->getConfiguration();
        $defaultClusterThreshold = static::getDefaultConfiguration()['cluster_threshold'];
        if (!empty($config['clustering']) && \is_array($config['clustering'])) {
            $threshold = $defaultClusterThreshold;
            foreach ($config['clustering'] as $clusterConfig) {
                if (!empty($clusterConfig['disable']) && !empty($clusterConfig['scale'])) {
                    $threshold = min($threshold, \intval($clusterConfig['scale']));
                }
            }
            $config += array(
                'cluster_threshold' => $threshold,
            );
        }
        unset($config['clustering']);
        $entity->setConfiguration($config);
    }
}
