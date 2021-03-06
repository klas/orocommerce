<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class UniqueSlugResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var AclHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $aclHelper;

    /**
     * @var UniqueSlugResolver
     */
    protected $uniqueSlugResolver;

    protected function setUp()
    {
        $this->repository = $this->createMock(SlugRepository::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->uniqueSlugResolver = new UniqueSlugResolver($this->repository, $this->aclHelper);
    }

    public function testResolveNewSlug()
    {
        $slug = '/test';
        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->repository->expects($this->once())
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->with($slug, $entity)
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }

    public function testResolveExistingSlug()
    {
        $slug = '/test';
        $existingSlug = '/test-1';
        $expectedSlug = '/test-2';

        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(new Slug());
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->repository->expects($this->once())
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->with($slug, $entity)
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('findAllDirectUrlsByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }

    public function testResolveExistingSlugWithinBatch()
    {
        $slug = '/test';
        /** @var Localization $frLocalization */
        $frLocalization = $this->createMock(Localization::class);

        $slugUrl = new SlugUrl($slug);
        $slugUrlFr = new SlugUrl($slug, $frLocalization);

        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity1 **/
        $entity1 = $this->createMock(SluggableInterface::class);
        $entity1->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity2 **/
        $entity2 = $this->createMock(SluggableInterface::class);
        $entity2->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->repository->expects($this->any())
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->any())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->repository->expects($this->any())
            ->method('findAllDirectUrlsByPattern')
            ->willReturn([]);

        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrl, $entity1));
        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrlFr, $entity1));
        $this->assertEquals('/test-1', $this->uniqueSlugResolver->resolve($slugUrl, $entity2));
        $this->assertEquals('/test-1', $this->uniqueSlugResolver->resolve($slugUrlFr, $entity2));
    }

    public function testResolveExistingIncrementedSlug()
    {
        $slug = '/test-1';
        $existingSlug = '/test-1';
        $expectedSlug = '/test-2';

        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->exactly(2))
            ->method('getOneOrNullResult')
            ->willReturnOnConsecutiveCalls(
                [$this->getEntity(Slug::class, ['id' => 123])],
                [$this->getEntity(Slug::class, ['id' => 42])]
            );
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->repository->expects($this->exactly(2))
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->withConsecutive(
                [$slug, $entity],
                ['/test', $entity]
            )
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->exactly(2))
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('findAllDirectUrlsByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }
}
