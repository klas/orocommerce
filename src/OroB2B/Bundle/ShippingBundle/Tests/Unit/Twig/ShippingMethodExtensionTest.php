<?php

namespace OroB2B\src\OroB2B\Bundle\ShippingBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Twig\ShippingMethodExtension;

class ShippingMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  ShippingMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var ShippingMethodExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->shippingMethodLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ShippingMethodExtension(
            $this->shippingMethodLabelFormatter
        );
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction(
                    'get_shipping_method_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodLabel']
                ),
                new \Twig_SimpleFunction(
                    'get_shipping_method_type_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodTypeLabel']
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingMethodExtension::SHIPPING_METHOD_EXTENSION_NAME, $this->extension->getName());
    }
}
