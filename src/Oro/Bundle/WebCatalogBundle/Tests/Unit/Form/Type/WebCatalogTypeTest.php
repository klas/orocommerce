<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WebCatalogTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider = $this->getMockBuilder(HtmlTagProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);
        $context = $this->createMock(ContextInterface::class);
        $richTextType = new OroRichTextType($configManager, $htmlTagProvider, $context);

        return [
            new PreloadedExtension(
                [
                    TextType::class => new TextType(),
                    OroRichTextType::class => $richTextType
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(WebCatalogType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
    }

    public function testGetBlockPrefix()
    {
        $type = new WebCatalogType();
        $this->assertEquals(WebCatalogType::NAME, $type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param WebCatalog $existingData
     * @param array $submittedData
     * @param WebCatalog $expectedData
     */
    public function testSubmit($existingData, $submittedData, $expectedData)
    {
        $form = $this->factory->create(WebCatalogType::class, $existingData);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new entity' => [
                new WebCatalog(),
                [
                    'name' => 'name',
                    'description' => 'description'
                ],
                (new WebCatalog())
                    ->setName('name')
                    ->setDescription('description')
            ],
            'existing entity' => [
                (new WebCatalog())
                    ->setName('name')
                    ->setDescription('description'),
                [
                    'name' => 'name UP',
                    'description' => 'description UP'
                ],
                (new WebCatalog())
                    ->setName('name UP')
                    ->setDescription('description UP')
            ],
        ];
    }
}
