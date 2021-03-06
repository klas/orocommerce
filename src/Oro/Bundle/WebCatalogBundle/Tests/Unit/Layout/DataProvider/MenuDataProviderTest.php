<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\MenuDataProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $webCatalogProvider;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contentNodeTreeResolverFacade;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localizationHelper;

    /**
     * @var MenuDataProvider
     */
    protected $menuDataProvider;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheProvider;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->webCatalogProvider = $this->getMockBuilder(WebCatalogProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->contentNodeTreeResolverFacade = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->menuDataProvider = new MenuDataProvider(
            $this->registry,
            $this->webCatalogProvider,
            $this->contentNodeTreeResolverFacade,
            $this->localizationHelper,
            $this->requestStack,
            $this->websiteManager
        );

        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->menuDataProvider->setCache($this->cacheProvider);
    }

    /**
     * @dataProvider getItemsDataProvider
     *
     * @param ResolvedContentNode $resolvedRootNode
     * @param array               $expectedData
     */
    public function testGetItems(ResolvedContentNode $resolvedRootNode, array $expectedData)
    {
        $webCatalogId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        $rootNode = new ContentNode();
        $scope = new Scope();

        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes = new ParameterBag(['_web_content_scope' => $scope]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->webCatalogProvider->expects($this->any())
            ->method('getNavigationRoot')
            ->willReturn(null);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($webCatalog);

        $nodeRepository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeRepository->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($nodeRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->contentNodeTreeResolverFacade->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($rootNode, $scope)
            ->willReturn($resolvedRootNode);

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->will($this->returnCallback(function (ArrayCollection $collection) {
                return $collection->first()->getString();
            }));

        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cacheProvider->expects($this->any())
            ->method('fetch')
            ->willReturn(false);

        $actual = $this->menuDataProvider->getItems();
        $this->assertEquals($expectedData, $actual);
    }

    /**
     * @dataProvider getItemsDataProvider
     *
     * @param ResolvedContentNode $resolvedRootNode
     * @param array               $expectedData
     */
    public function testGetItemsWithNavigationRoot(ResolvedContentNode $resolvedRootNode, array $expectedData)
    {
        $rootNode = new ContentNode();
        $scope = new Scope();

        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes = new ParameterBag(['_web_content_scope' => $scope]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->webCatalogProvider->expects($this->any())
            ->method('getNavigationRoot')
            ->willReturn($rootNode);

        $this->webCatalogProvider->expects($this->never())
            ->method('getWebCatalog');

        $nodeRepository = $this->createMock(ContentNodeRepository::class);
        $nodeRepository->expects($this->never())
            ->method('getRootNodeByWebCatalog');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getRepository');

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->contentNodeTreeResolverFacade->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($rootNode, $scope)
            ->willReturn($resolvedRootNode);

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->will($this->returnCallback(function (ArrayCollection $collection) {
                return $collection->first()->getString();
            }));

        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cacheProvider->expects($this->any())
            ->method('fetch')
            ->willReturn(false);

        $actual = $this->menuDataProvider->getItems();
        $this->assertEquals($expectedData, $actual);
    }

    public function testGetItemsCached()
    {
        $scope = $this->getEntity(Scope::class, ['id' => 1]);

        $expectedData = [
            MenuDataProvider::IDENTIFIER => 'root__node2',
            MenuDataProvider::LABEL => 'node2',
            MenuDataProvider::URL => '/node2',
            MenuDataProvider::CHILDREN => []
        ];

        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes = new ParameterBag(['_web_content_scope' => $scope]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $rootNode = $this->getEntity(ContentNode::class, ['id' => 77]);
        $this->webCatalogProvider->expects($this->any())
            ->method('getNavigationRoot')
            ->with($website)
            ->willReturn($rootNode);

        $this->cacheProvider->expects($this->at(0))
            ->method('fetch')
            ->with('cacheVal_menu_items_1_77_42')
            ->willReturn([MenuDataProvider::CHILDREN => $expectedData]);

        $actual = $this->menuDataProvider->getItems();
        $this->assertEquals($expectedData, $actual);
    }

    /**
     * @return array
     */
    public function getItemsDataProvider()
    {
        return [
            'root without children' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/'),
                'expectedData' => []
            ],
            'root with children' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', [
                    $this->getResolvedContentNode(1, 'root__node2', 'node2', '/node2')
                ]),
                'expectedData' => [
                    [
                        MenuDataProvider::IDENTIFIER => 'root__node2',
                        MenuDataProvider::LABEL => 'node2',
                        MenuDataProvider::URL => '/node2',
                        MenuDataProvider::CHILDREN => []
                    ]
                ]
            ],
        ];
    }

    /**
     * @param string                $id
     * @param string                $identifier
     * @param string                $title
     * @param string                $url
     * @param ResolvedContentNode[] $children
     *
     * @return ResolvedContentNode
     */
    private function getResolvedContentNode($id, $identifier, $title, $url, array $children = [])
    {
        $nodeVariant = new ResolvedContentVariant();
        $nodeVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString($url));

        $nodeTitleCollection =  new ArrayCollection([(new LocalizedFallbackValue())
            ->setString($title)]);

        $resolvedRootNode = new ResolvedContentNode(
            $id,
            $identifier,
            $nodeTitleCollection,
            $nodeVariant
        );

        foreach ($children as $child) {
            $resolvedRootNode->addChildNode($child);
        }

        return $resolvedRootNode;
    }
}
