<?php

namespace TradusBundle\Tests\Service\TradusUser;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TradusBundle\Repository\TradusUserRepository;
use TradusBundle\Service\TradusUser\TradusUserService;
use TradusBundle\Tests\Entity\TradusUserTest;

class TradusUserServiceTest extends KernelTestCase
{
    /* @var TradusUserService $searchService */
    protected $tradusUserService;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->entityManager = $this->createMock('\Doctrine\ORM\EntityManager');

        $tradusUserTest = new TradusUserTest();

        $tradusUserRepository = $this->getMockBuilder(TradusUserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tradusUserRepository->expects($this->any())->method('findOneByEmail')
            ->willReturnMap([
                ['notfound@email.com', null],
                ['found@email.com', $tradusUserTest->getMockTradusUser([
                  'email' => 'found@email.com',
                  'username' => 'pieter',
                  'first_name' => 'pieter',
                  'last_name' => 'lastname',
                  'country' => 'nl',
                  'company' => 'olx',
                  'phone' => '06123456789',
                  'ip' => '127.0.0.1',
                ])],
            ]);

        $this->entityManager->expects($this->any())->method('getRepository')
            ->willReturnMap([
                ['TradusBundle:TradusUser', $tradusUserRepository],
            ]);

        $this->tradusUserService = new TradusUserService($this->entityManager);
    }

    public function testGenerateBuyer()
    {
        $content = [
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'phone'  => '112233445566',
            'country' => 'Netherlands',
            'from_email' => 'notfound@email.com',
            'company' => 'Tradus',
        ];

        $tradusUser = $this->tradusUserService->generateBuyer($content);
        $this->assertEquals('FirstName', $tradusUser->getFirstName());
        $this->assertEquals('LastName', $tradusUser->getLastName());
        $this->assertEquals('112233445566', $tradusUser->getPhone());
        $this->assertEquals('Netherlands', $tradusUser->getCountry());
        $this->assertEquals('notfound@email.com', $tradusUser->getEmail());
        $this->assertEquals('Tradus', $tradusUser->getCompany());

        // Exisiting user
        $content['from_email'] = 'found@email.com';
        $tradusUser = $this->tradusUserService->generateBuyer($content);
        // Existing data overwriten
        $this->assertEquals('FirstName', $tradusUser->getFirstName());
        $this->assertEquals('LastName', $tradusUser->getLastName());
        $this->assertEquals('112233445566', $tradusUser->getPhone());
        $this->assertEquals('Netherlands', $tradusUser->getCountry());
        $this->assertEquals('found@email.com', $tradusUser->getEmail());

        // Send Alert Emails?
        $content['send_alerts'] = boolval('on');
        $tradusUser = $this->tradusUserService->generateBuyer($content);
        $this->assertEquals(true, $tradusUser->canSendAlertEmails());
        $this->assertInstanceOf(\DateTime::class, $tradusUser->getAcceptedAlertsDate());

        $content['send_alerts'] = false;
        $tradusUser = $this->tradusUserService->generateBuyer($content);
        $this->assertEquals(false, $tradusUser->canSendAlertEmails());
        $this->assertEquals(null, $tradusUser->getAcceptedAlertsDate());
    }
}
