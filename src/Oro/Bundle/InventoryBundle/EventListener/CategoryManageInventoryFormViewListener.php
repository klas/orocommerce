<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\Fallback\AbstractFallbackFieldsFormView;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CategoryManageInventoryFormViewListener extends AbstractFallbackFieldsFormView
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $category = $this->getEntityFromRequest(Category::class);
        if ($category === null) {
            return null;
        }

        $this->addBlockToEntityEdit(
            $event,
            'OroInventoryBundle:Category:editManageInventory.html.twig',
            'oro.catalog.sections.default_options'
        );
    }
}
