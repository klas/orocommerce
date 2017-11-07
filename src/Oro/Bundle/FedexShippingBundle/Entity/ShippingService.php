<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_fedex_shipping_service")
 * @ORM\Entity
 */
class ShippingService
{
    /**
     * @var integer|null
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=200)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=200)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="limitation_expression_lbs", type="string", length=250)
     */
    private $limitationExpressionLbs;

    /**
     * @var string|null
     *
     * @ORM\Column(name="limitation_expression_kg", type="string", length=250)
     */
    private $limitationExpressionKg;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return self
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getLimitationExpressionLbs()
    {
        return $this->limitationExpressionLbs;
    }

    /**
     * @param string $limitationExpressionLbs
     *
     * @return self
     */
    public function setLimitationExpressionLbs(string $limitationExpressionLbs): self
    {
        $this->limitationExpressionLbs = $limitationExpressionLbs;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getLimitationExpressionKg()
    {
        return $this->limitationExpressionKg;
    }

    /**
     * @param string $limitationExpressionKg
     *
     * @return self
     */
    public function setLimitationExpressionKg(string $limitationExpressionKg): self
    {
        $this->limitationExpressionKg = $limitationExpressionKg;

        return $this;
    }
}
