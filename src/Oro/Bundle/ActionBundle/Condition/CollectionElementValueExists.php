<?php

namespace Oro\Bundle\ActionBundle\Condition;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Component\ConfigExpression\Condition\AbstractComparison;
use Oro\Component\ConfigExpression\Exception;

class CollectionElementValueExists extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (2 !== count($options)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 2 or more elements, but %d given.', count($options))
            );
        }

        $this->right = [];

        for ($i = 0; $i < count($options); $i++) {
            $option = array_shift($options);

            if (!$option instanceof PropertyPathInterface) {
                throw new Exception\InvalidArgumentException(
                    sprintf('Option with index %s must be property path.', $i)
                );
            }

            $this->right[] = $option;
        }

        $this->left = array_shift($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($needle, $collection)
    {
        return in_array($needle, $collection, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'collection_element_value_exists';
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveValue($context, $value, $strict = true)
    {
        if (!is_array($value)) {
            return parent::resolveValue($context, $value, $strict);
        }

        return $this->resolvePath($context, array_shift($value), $value);
    }

    /**
     * @param mixed $context
     * @param PropertyPathInterface $propertyPath
     * @param array $propertyPaths
     * @return array|mixed
     */
    protected function resolvePath($context, PropertyPathInterface $propertyPath, array $propertyPaths)
    {
        $data = $this->resolveValue($context, $propertyPath);

        if ((is_array($data) || $data instanceof Collection) && count($propertyPaths)) {
            $propertyPath = array_shift($propertyPaths);
            $result = [];

            foreach ($data as $item) {
                $result[] = $this->resolvePath(new ActionContext(['data' => $item]), $propertyPath, $propertyPaths);
            }

            return $result;
        }

        return $data;
    }
}
