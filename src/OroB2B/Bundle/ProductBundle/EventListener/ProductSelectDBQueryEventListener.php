<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductSelectDBQueryEventListener
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $systemConfigurationPath;

    /**
     * @param ConfigManager $configManager
     * @param ProductVisibilityQueryBuilderModifier $modifier
     */
    public function __construct(ConfigManager $configManager, ProductVisibilityQueryBuilderModifier $modifier)
    {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @param string $systemConfigurationPath
     * @return $this
     */
    public function setSystemConfigurationPath($systemConfigurationPath)
    {
        $this->systemConfigurationPath = $systemConfigurationPath;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        if (!$this->isConditionsAcceptable($event)) {
            return;
        }

        $inventoryStatuses = $this->configManager->get($this->systemConfigurationPath);
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     * @return bool
     */
    protected function isConditionsAcceptable(ProductSelectDBQueryEvent $event)
    {
        if (!$this->systemConfigurationPath) {
            throw new \LogicException('SystemConfigurationPath not configured for ProductSelectDBQueryEventListener');
        }

        if (!$this->scope) {
            throw new \LogicException('Scope not configured for ProductSelectDBQueryEventListener');
        }

        $dataParameters = $event->getDataParameters();

        // @TODO Will be refactored in scope BB-1443
        $scope = null;
        if ($dataParameters instanceof ParameterBag) {
            $scope = $dataParameters->get('scope');
        } elseif (is_array($dataParameters) && isset($dataParameters['scope'])) {
            $scope = $dataParameters['scope'];
        }

        if ($scope !== $this->scope) {
            return false;
        }

        return true;
    }
}
