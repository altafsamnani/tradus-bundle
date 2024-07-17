<?php

namespace TradusBundle\Tests\Service\Alerts;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\VarDumper\VarDumper;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Repository\AlertsRepository;
use TradusBundle\Repository\SimilarOfferAlertRepository;
use TradusBundle\Service\Alerts\AlertService;
use TradusBundle\Service\Alerts\Rules\AlertRuleInterface;
use TradusBundle\Tests\Entity\OfferTest;

class AlertServiceTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected $offerTest;
    /**
     * @var TradusUser
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $GLOBALS['kernel'] = self::bootKernel();

        $this->offerTest = new OfferTest();

        $alertRepository = $this->getMockBuilder(AlertsRepository::class)->disableOriginalConstructor()->getMock();
        //$alertRepository->expects($this->any())->method('findOneByEmail')

        $similarOfferAlertRepository = $this->getMockBuilder(SimilarOfferAlertRepository::class)->disableOriginalConstructor()->getMock();

        /* @var EntityManager $entityManager */
        $this->entityManager = $this->createMock('\Doctrine\ORM\EntityManager');

        $this->entityManager->expects($this->any())->method('getRepository')
            ->willReturnMap([
                ['TradusBundle:Alerts', $alertRepository],
                ['TradusBundle:SimilarOfferAlert', $similarOfferAlertRepository],
            ]);

        $this->user = $this->getMockBuilder(TradusUser::class)->setMethods(['getPhone'])->getMock();
        $this->user->setEmail('pieter@tradus.com');
        $this->user->setStatus(TradusUser::STATUS_ACTIVE);
        $this->user->setId(20);
    }

    public function testCreateAlertMatchingOffer()
    {
        $alertService = new AlertService($this->entityManager);
        $alertService->setUser($this->user);
        $alert = $alertService->createAlertMatchingOffer($this->offerTest->getMockOffer());
        $this->assertEquals(AlertRuleInterface::RULE_TYPE_MATCHING_OFFER, $alert->getType());
        $rules = $alert->getRule();
        $this->assertEquals('1300', $rules['make']);
        $this->assertEquals('3', $rules['category']);
        $this->assertEquals('20', $alert->getUser()->getId());

        //VarDumper::dump($alert->getUser());die;
    }
}
