<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFields;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFieldsValidator;
use Symfony\Component\Validator\Constraint;

class ZipCodeFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ZipCodeFields
     */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new ZipCodeFields();
    }

    protected function tearDown()
    {
        unset($this->constraint);
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals(ZipCodeFieldsValidator::ALIAS, $this->constraint->validatedBy());
    }
}
