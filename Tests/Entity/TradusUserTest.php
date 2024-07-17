<?php

namespace TradusBundle\Tests\Entity;

use TradusBundle\Entity\TradusUser;
use TradusBundle\Tests\AbstractEntityTest;

/**
 * Class TradusUserTest.
 */
class TradusUserTest extends AbstractEntityTest
{
    const TEST_CLASS = 'TradusBundle\Entity\TradusUser';

    const TEST_USERNAME = 'pieter';
    const TEST_EMAIL = 'pieter@olx.com';

    /* @var TradusUser */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->user = new TradusUser();
    }

    /**
     * Test the offer constraints.
     */
    public function testOfferDescriptionsConstraints()
    {
        /**
         * Check Required fields.
         */
        $violationList = $this->validator->validate($this->user);

        /* make sure that we have more then one required field, we expect more then 1 */
        $this->assertGreaterThan(1, count($violationList), 'Expected more then 1 required field to test.');

        $violationList = $this->validator->validate($this->user);
        /**
         * email.
         */
        $violation = $this->findViolationByColumn('email', $violationList);
        $this->assertEquals('email can not be empty', $violation->getMessage());

        // Not so valid email
        $this->user->setEmail('lalala@lala');
        $violationList = $this->validator->validate($this->user);
        $violation = $this->findViolationByColumn('email', $violationList);
        $this->assertEquals('The email \'"lalala@lala"\' is not a valid email.', $violation->getMessage());

        // Valid email
        $this->user->setEmail(self::TEST_EMAIL);
        $violationList = $this->validator->validate($this->user);
        $violation = $this->findViolationByColumn('email', $violationList);
        $this->assertEquals(null, $violation);
        $this->assertEquals(self::TEST_EMAIL, $this->user->getEmail());

        /**
         * Password.
         */
        $violation = $this->findViolationByColumn('password', $violationList);
        $this->assertEquals('password can not be empty', $violation->getMessage());

        $this->user->setPassword('rawPasswordString');
        $violationList = $this->validator->validate($this->user);
        $violation = $this->findViolationByColumn('password', $violationList);
        $this->assertEquals(null, $violation);
        $this->assertNotEquals('rawPasswordString', $this->user->getPassword());
        $this->assertEquals(true, $this->user->passwordValidate('rawPasswordString'));

        // Expecting bcrypt to be used
        $passwordInformation = $this->user->passwordInformation();
        $this->assertEquals('bcrypt', $passwordInformation['algoName']);

        /*
         * Status
         */
        // Invalid
        $this->user->setStatus(1209823948);
        $violationList = $this->validator->validate($this->user);
        $violation = $this->findViolationByColumn('status', $violationList);
        $this->assertEquals('The value you selected is not a valid choice.', $violation->getMessage());

        // Valid
        $this->user->setStatus(TradusUser::STATUS_ACTIVE);
        $violationList = $this->validator->validate($this->user);
        $violation = $this->findViolationByColumn('status', $violationList);
        $this->assertEquals(null, $violation);

        // Constraints are done
        $this->assertEquals(0, count($violationList), 'Expected not more constraints to test.');
    }
}
