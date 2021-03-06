<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException as MessageQueueInvalidArgumentException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException as OptionsResolverInvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Schedule cache recalculation for content node
 */
class ContentNodeCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var ScopeMatcher */
    private $scopeMatcher;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param ScopeMatcher $scopeMatcher
     * @param ManagerRegistry $registry
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ScopeMatcher $scopeMatcher,
        ManagerRegistry $registry
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->scopeMatcher = $scopeMatcher;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = $this->getMessageData($message);
        $contentNodeId = $messageData['contentNodeId'];

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('%s:%s', Topics::CALCULATE_CONTENT_NODE_CACHE, $contentNodeId),
            function (JobRunner $jobRunner) use ($contentNodeId) {
                $contentNode = $this->getContentNode($contentNodeId);
                if ($contentNode) {
                    $this->scheduleCacheRecalculationForContentNodeTree($jobRunner, $contentNode);
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param JobRunner $jobRunner
     * @param ContentNode $contentNode
     */
    private function scheduleCacheRecalculationForContentNodeTree(JobRunner $jobRunner, ContentNode $contentNode)
    {
        $scopes = $this->scopeMatcher->getUsedScopes($contentNode->getWebCatalog());

        foreach ($scopes as $scope) {
            $jobRunner->createDelayed(
                sprintf(
                    '%s:%s:%s',
                    Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                    $scope->getId(),
                    $contentNode->getId()
                ),
                function (JobRunner $jobRunner, Job $child) use ($contentNode, $scope) {
                    $this->producer->send(
                        Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                        [
                            'contentNode' => $contentNode->getId(),
                            'scope'       => $scope->getId(),
                            'jobId'       => $child->getId(),
                        ]
                    );
                }
            );
        }
    }

    /**
     * @param int $contentNodeId
     *
     * @return ContentNode
     */
    private function getContentNode($contentNodeId)
    {
        $repository = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        return $repository->findOneBy(['id' => $contentNodeId]);
    }

    /**
     * @param MessageInterface $message
     *
     * @return array
     */
    private function getMessageData(MessageInterface $message): array
    {
        $body = JSON::decode($message->getBody());

        try {
            return $this->getOptionsResolver()->resolve((array)$body);
        } catch (OptionsResolverInvalidArgumentException $e) {
            throw new MessageQueueInvalidArgumentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['contentNodeId']);
        $resolver->setAllowedTypes('contentNodeId', ['int']);

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_CONTENT_NODE_CACHE];
    }
}
