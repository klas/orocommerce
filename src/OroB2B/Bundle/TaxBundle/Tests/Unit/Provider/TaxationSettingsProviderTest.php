<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\TaxBundle\Model\Address;
use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Factory\AddressModelFactory;
use OroB2B\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class TaxationSettingsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var TaxBaseExclusionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxBaseExclusionFactory;

    /**
     * @var AddressModelFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressModelFactory;

    /**
     * @var TaxationSettingsProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxBaseExclusionFactory = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressModelFactory = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Factory\AddressModelFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TaxationSettingsProvider(
            $this->configManager,
            $this->taxBaseExclusionFactory,
            $this->addressModelFactory
        );
    }

    protected function tearDown()
    {
        unset($this->configManager, $this->taxBaseExclusionFactory, $this->addressModelFactory, $this->provider);
    }

    public function testGetStartCalculationWith()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.start_calculation_with')
            ->willReturn(TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE);

        $this->assertEquals(
            TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
            $this->provider->getStartCalculationWith()
        );
    }

    /**
     * @dataProvider isStartCalculationWithUnitPriceProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsStartCalculationWithUnitPrice($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.start_calculation_with')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationWithUnitPrice());
    }

    /**
     * @return array
     */
    public function isStartCalculationWithUnitPriceProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
                'expected' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ROW_TOTAL,
                'expected' => false,
            ],
        ];
    }

    public function testGetStartCalculationOn()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.start_calculation_on')
            ->willReturn(TaxationSettingsProvider::START_CALCULATION_ON_INVOICE);

        $this->assertEquals(
            TaxationSettingsProvider::START_CALCULATION_ON_INVOICE,
            $this->provider->getStartCalculationOn()
        );
    }

    /**
     * @dataProvider isStartCalculationOnUnitPriceProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsStartCalculationOnUnitPrice($configValue, $expected)
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('orob2b_tax.start_calculation_on')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationOnInvoice());
        $this->assertEquals(!$expected, $this->provider->isStartCalculationOnItem());
    }

    /**
     * @return array
     */
    public function isStartCalculationOnUnitPriceProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ON_INVOICE,
                'expected' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ON_ITEM,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isStartCalculationWithRowTotalProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsStartCalculationWithRowTotal($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.start_calculation_with')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationWithRowTotal());
    }

    /**
     * @return array
     */
    public function isStartCalculationWithRowTotalProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
                'expected' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ROW_TOTAL,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider isProductPricesIncludeTaxProvider
     * @param bool $configValue
     * @param bool $expected
     */
    public function testIsProductPricesIncludeTax($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.product_prices_include_tax')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isProductPricesIncludeTax());
    }

    /**
     * @return array
     */
    public function isProductPricesIncludeTaxProvider()
    {
        return [
            [
                true,
                true,
            ],
            [
                false,
                false,
            ],
        ];
    }

    public function testGetDestination()
    {
        $value = 'value';

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.destination')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getDestination());
    }

    public function testGetDigitalProductsTaxCodesUs()
    {
        $value = ['AAAA', 'BBBB'];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.digital_products_us')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getDigitalProductsTaxCodesUs());
    }

    public function testGetDigitalProductsTaxCodesEu()
    {
        $value = ['AAAA', 'BBBB'];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.digital_products_eu')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getDigitalProductsTaxCodesEu());
    }

    /**
     * @dataProvider isBillingAddressDestinationProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsBillingAddressDestination($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.destination')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isBillingAddressDestination());
    }

    /**
     * @return array
     */
    public function isBillingAddressDestinationProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                'value' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                'value' => false,
            ],
        ];
    }

    /**
     * @dataProvider isShippingAddressDestinationProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsShippingAddressDestination($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.destination')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isShippingAddressDestination());
    }

    /**
     * @return array
     */
    public function isShippingAddressDestinationProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                'value' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                'value' => true,
            ],
        ];
    }

    public function testGetBaseByDefaultAddressType()
    {
        $value = TaxationSettingsProvider::USE_AS_BASE_DESTINATION;
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.use_as_base_by_default')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getBaseByDefaultAddressType());
    }

    /**
     * @dataProvider isOriginBaseByDefaultAddressTypeProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsOriginBaseByDefaultAddressType($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.use_as_base_by_default')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isOriginBaseByDefaultAddressType());
    }

    /**
     * @return array
     */
    public function isOriginBaseByDefaultAddressTypeProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                'value' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                'value' => true,
            ],
        ];
    }

    /**
     * @dataProvider isDestinationBaseByDefaultAddressTypeProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsDestinationBaseByDefaultAddressType($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.use_as_base_by_default')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isDestinationBaseByDefaultAddressType());
    }

    /**
     * @return array
     */
    public function isDestinationBaseByDefaultAddressTypeProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                'value' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                'value' => false,
            ],
        ];
    }

    public function testGetBaseAddressExclusions()
    {
        $exclusionData = [
            ['data'],
            ['data'],
        ];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.use_as_base_exclusions')
            ->willReturn($exclusionData);

        $this->taxBaseExclusionFactory
            ->expects($this->exactly(count($exclusionData)))
            ->method('create')
            ->willReturn(new TaxBaseExclusion());

        $this->assertCount(count($exclusionData), $this->provider->getBaseAddressExclusions());
    }

    public function testGetOrigin()
    {
        $addressData = ['address data'];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('orob2b_tax.origin_address')
            ->willReturn($addressData);

        $address = new Address();
        $this->addressModelFactory
            ->expects($this->once())
            ->method('create')
            ->with($addressData)
            ->willReturn($address);

        $this->assertEquals($address, $this->provider->getOrigin());
    }
}
