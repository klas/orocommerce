<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

// @codingStandardsIgnoreStart
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
// @codingStandardsIgnoreEnd

class FedexShippingMethod implements
    ShippingMethodInterface,
    ShippingMethodIconAwareInterface,
    PricesAwareShippingMethodInterface,
    ShippingTrackingAwareInterface
{
    const OPTION_SURCHARGE = 'surcharge';

    const TRACKING_URL = 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=';

    /**
     * @var FedexRateServiceRequestSettingsFactoryInterface
     */
    private $rateServiceRequestSettingsFactory;

    /**
     * @var FedexRequestByRateServiceSettingsFactoryInterface
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|null
     */
    private $iconPath;

    /**
     * @var ShippingMethodTypeInterface[]
     */
    private $types;

    /**
     * @var Collection|ShippingService[]
     */
    private $shippingServices;

    /**
     * @var FedexIntegrationSettings
     */
    private $settings;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param FedexRateServiceRequestSettingsFactoryInterface   $rateServiceRequestSettingsFactory
     * @param FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface         $rateServiceClient
     * @param string                                            $identifier
     * @param string                                            $label
     * @param string|null                                       $iconPath
     * @param bool                                              $enabled
     * @param FedexIntegrationSettings                          $settings,
     * @param ShippingMethodTypeInterface[]                     $types
     * @param Collection|ShippingService[]                      $shippingServices
     */
    public function __construct(
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient,
        string $identifier,
        string $label,
        $iconPath,
        bool $enabled,
        FedexIntegrationSettings $settings,
        array $types,
        Collection $shippingServices
    ) {
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
        $this->identifier = $identifier;
        $this->label = $label;
        $this->iconPath = $iconPath;
        $this->enabled = $enabled;
        $this->settings = $settings;
        $this->types = $types;
        $this->shippingServices = $shippingServices;
    }

    /**
     * {@inheritDoc}
     */
    public function isGrouped()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon()
    {
        return $this->iconPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * {@inheritDoc}
     */
    public function getType($identifier)
    {
        foreach ($this->getTypes() as $methodType) {
            if ($methodType->getIdentifier() === (string) $identifier) {
                return $methodType;
            }
        }

        return null;
    }

    /**
     * @param string $code
     *
     * @return ShippingService|null
     */
    public function getShippingService(string $code)
    {
        foreach ($this->shippingServices as $service) {
            if ($code === $service->getCode()) {
                return $service;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return FedexShippingMethodOptionsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder()
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        $methodSurcharge = $this->getSurchargeFromOptions($methodOptions);
        $result = [];
        foreach ($optionsByTypes as $typeId => $option) {
            $shippingService = $this->getShippingService($typeId);
            if (!$shippingService) {
                continue;
            }

            $request = $this->rateServiceRequestFactory->create(
                $this->rateServiceRequestSettingsFactory->create($this->settings, $context, $shippingService)
            );
            if (!$request) {
                continue;
            }

            $price = $this->rateServiceClient->send($request, $this->settings)->getPrice();
            if (!$price) {
                continue;
            }

            $result[$typeId] = Price::create(
                (float) $price->getValue() + $methodSurcharge + $this->getSurchargeFromOptions($option),
                $price->getCurrency()
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingLink($number)
    {
        foreach ($this->getTrackingRegexList() as $regex) {
            if (preg_match($regex, $number)) {
                return self::TRACKING_URL . $number;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    private function getTrackingRegexList(): array
    {
        return [
            '/(\b96\d{20}\b)|(\b\d{15}\b)|(\b\d{12}\b)/',
            '/\b((98\d\d\d\d\d?\d\d\d\d|98\d\d) ?\d\d\d\d ?\d\d\d\d( ?\d\d\d)?)\b/',
            '/^[0-9]{15}$/',
        ];
    }

    /**
     * @param array $option
     *
     * @return float
     */
    private function getSurchargeFromOptions(array $option): float
    {
        return (float) $option[static::OPTION_SURCHARGE];
    }
}
