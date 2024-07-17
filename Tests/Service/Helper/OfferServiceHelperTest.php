<?php

namespace AppBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\Seller;
use TradusBundle\Repository\CategoryRepository;
use TradusBundle\Repository\MakeRepository;
use TradusBundle\Repository\OfferRepository;
use TradusBundle\Repository\SellerRepository;
use TradusBundle\Service\Helper\OfferServiceHelper;
use TradusBundle\Tests\Entity\OfferTest;

/**
 * Class SellerServiceHelperTest.
 */
class OfferServiceHelperTest extends KernelTestCase
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var OfferServiceHelper
     */
    protected $offerServiceHelper;

    /**
     * @var OfferTest
     */
    protected $offerTest;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        $kernel = self::bootKernel();

        $this->offerTest = new OfferTest();

        $offerByAdIdMap = [
            [OfferTest::OFFER_DATA[Offer::FIELD_AD_ID], $this->offerTest->getMockOffer()],
            ['NON_EXISTING_AD_ID', null],
        ];
        $offerBySlugLocaleMap = [
            [OfferTest::OFFER_DATA[Offer::FIELD_SLUG], 'en', $this->offerTest->getMockOffer()],
            [OfferTest::OFFER_DATA[Offer::FIELD_SLUG], 'nl', $this->offerTest->getMockOffer()],
            [OfferTest::OFFER_DATA[Offer::FIELD_SLUG], 'de', null],
            ['NON_EXESTING_SLUG', 'en', null],
        ];
        $offerByIdMap = [
            [OfferTest::OFFER_DATA[Offer::FIELD_OFFER_ID], $this->offerTest->getMockOffer()],
            [6666666, null],
        ];
        $offerByV1IdMap = [
            [OfferTest::OFFER_DATA[Offer::FIELD_V1_OFFER_ID], $this->offerTest->getMockOffer()],
            [6666666, null],
        ];

        $offerRepository = $this->getMockBuilder(OfferRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $offerRepository->expects($this->any())->method('getOfferByAdId')
            ->willReturnMap($offerByAdIdMap);

        $offerRepository->expects($this->any())->method('findOfferByAdId')
            ->willReturnMap($offerByAdIdMap);

        $offerRepository->expects($this->any())->method('getOfferBySlug')
            ->willReturnMap($offerBySlugLocaleMap);

        $offerRepository->expects($this->any())->method('getOfferById')
            ->willReturnMap($offerByIdMap);

        $offerRepository->expects($this->any())->method('findOfferByV1Id')
            ->willReturnMap($offerByV1IdMap);

        // TODO: PUT OFFER AND SELLER REPOSITORY IN ABSTRACT, SO IT CAN BE REUSED
        $sellerRepository = $this->getMockBuilder(SellerRepository::class)
            //->setMethodsExcept(['generateSlug'])
            ->disableOriginalConstructor()
            ->getMock();

        $sellerRepository->expects($this->any())->method('find')
            ->willReturnMap([
                [OfferTest::SELLER_DATA[Seller::FIELD_SELLER_ID], null, null, $this->offerTest->getMockSeller()],
                [5, null, null, ($this->offerTest->getMockSeller())->setSellerType(Seller::SELLER_TYPE_PACKAGE_GOLD)],
            ]);

        $makeRepository = $this->getMockBuilder(MakeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $makeRepository->expects($this->any())->method('find')
            ->willReturnMap([
                [OfferTest::OFFER_DATA[Offer::FIELD_MAKE], null, null, $this->offerTest->getMockMake()],
                [123, null, null, $this->offerTest->getMockMake('Other')],
            ]);
        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->any())->method('find')
            ->willReturnMap([
                [OfferTest::OFFER_DATA[Offer::FIELD_CATEGORY], null, null, $this->offerTest->getMockCategory()],
            ]);

        /* @var EntityManager $entityManager */
        $this->entityManager = $this->createMock('\Doctrine\ORM\EntityManager');

        $this->entityManager->expects($this->any())->method('getRepository')
            ->willReturnMap([
                ['TradusBundle:Offer', $offerRepository],
                ['TradusBundle:Seller', $sellerRepository],
                ['TradusBundle:Make', $makeRepository],
                ['TradusBundle:Category', $categoryRepository],
            ]);

        $this->offerServiceHelper = $this->getMockBuilder(OfferServiceHelper::class)
            ->setMethods(['indexOffer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->offerServiceHelper->entityManager = $this->entityManager;
        $this->offerServiceHelper->repository = $offerRepository;

        $this->validator = static::$kernel->getContainer()->get('validator');
    }

    public function testSaveTitle()
    {
        $offer = $this->offerTest->getMockOffer();
        /* @var Make $make */
        $make = $offer->getMake();
        $makeName = $make->getName();

        $params = $this->getParamsPopulateOffer();
        $params[OfferInterface::FIELD_MODEL] = OfferTest::OFFER_DATA[Offer::FIELD_MODEL];

        $offer = $this->offerServiceHelper->populateOffer($params);
        $this->assertGreaterThanOrEqual(1, $offer->getDescriptions()->count());
        /* @var \TradusBundle\Entity\OfferDescription $offerDescription */
        $offerDescription = $offer->getDescriptions()->current();
        $this->assertEquals('UNIT_TEST_MAKE UNIT_TEST_MODEL', $offerDescription->getTitle());

        // Now with double make name
        $params[OfferInterface::FIELD_MODEL] = $makeName.' '.OfferTest::OFFER_DATA[Offer::FIELD_MODEL];
        $offer = $this->offerServiceHelper->populateOffer($params);
        /* @var \TradusBundle\Entity\OfferDescription $offerDescription */
        $offerDescription = $offer->getDescriptions()->current();
        $this->assertEquals('UNIT_TEST_MAKE UNIT_TEST_MODEL', $offerDescription->getTitle());

        // Now with double make name (lowercase parts)
        $params[OfferInterface::FIELD_MODEL] = 'unit_TEST_MAKE'.' '.OfferTest::OFFER_DATA[Offer::FIELD_MODEL];
        $offer = $this->offerServiceHelper->populateOffer($params);
        /* @var \TradusBundle\Entity\OfferDescription $offerDescription */
        $offerDescription = $offer->getDescriptions()->current();
        $this->assertEquals('unit_TEST_MAKE UNIT_TEST_MODEL', $offerDescription->getTitle());

        // WIth Other in the make
        $params[OfferInterface::FIELD_MODEL] = OfferTest::OFFER_DATA[Offer::FIELD_MODEL];
        $params[OfferInterface::FIELD_MAKE] = 123;
        $offer = $this->offerServiceHelper->populateOffer($params);
        /* @var \TradusBundle\Entity\OfferDescription $offerDescription */
        $offerDescription = $offer->getDescriptions()->current();
        $this->assertEquals('UNIT_TEST_MODEL', $offerDescription->getTitle());
    }

    public function testFindOfferByAdId()
    {
        $offer = $this->offerServiceHelper->findOfferByAdId(OfferTest::OFFER_DATA[Offer::FIELD_AD_ID]);
        $this->assertNotEquals(null, $offer);
    }

    public function testFindOfferByAdIdException()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->findOfferByAdId('NON_EXISTING_AD_ID');
    }

    public function testFindOfferBySlug()
    {
        $offer = $this->offerServiceHelper->findOfferBySlug(OfferTest::OFFER_DATA[Offer::FIELD_SLUG], 'en');
        $this->assertNotEquals(null, $offer);
        $offer = $this->offerServiceHelper->findOfferBySlug(OfferTest::OFFER_DATA[Offer::FIELD_SLUG], 'nl');
        $this->assertNotEquals(null, $offer);
        // Test fallback to locale en
        $offer = $this->offerServiceHelper->findOfferBySlug(OfferTest::OFFER_DATA[Offer::FIELD_SLUG], 'de');
        $this->assertNotEquals(null, $offer);
    }

    public function testFindOfferBySlugException()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->findOfferBySlug('NON_EXESTING_SLUG', 'en');
    }

    public function testFindOfferById()
    {
        $offer = $this->offerServiceHelper->findOfferById(OfferTest::OFFER_DATA[Offer::FIELD_OFFER_ID]);
        $this->assertNotEquals(null, $offer);
    }

    public function testFindOfferByIdException()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->findOfferById(6666666);
    }

    /*
     public function testFindOfferByV1Id() {
        // TODO: IS NOT WORKING DON"T KNOW WHY
        $offer = $this->offerServiceHelper->findOfferByV1Id(OfferTest::OFFER_DATA[Offer::FIELD_V1_OFFER_ID]);
        $this->assertNotEquals(null, $offer);
    }
    */

    public function testFindOfferByV1IdException()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->findOfferByV1Id(6666666);
    }

    private function getParamsPopulateOffer()
    {
        $params = [
            OfferInterface::FIELD_V1_OFFER_ID => OfferTest::OFFER_DATA[Offer::FIELD_V1_OFFER_ID],
            OfferInterface::FIELD_MODEL => OfferTest::OFFER_DATA[Offer::FIELD_MODEL],
            OfferInterface::FIELD_SELLER => OfferTest::SELLER_DATA[Seller::FIELD_SELLER_ID],
            OfferInterface::FIELD_MAKE => OfferTest::OFFER_DATA[Offer::FIELD_MAKE],
            OfferInterface::FIELD_CATEGORY => OfferTest::OFFER_DATA[Offer::FIELD_CATEGORY],
            OfferInterface::FIELD_AD_ID => 'UNIQUE_AD_ID_FOR_PARAMS_TEST',
            OfferInterface::FIELD_PRICE => OfferTest::OFFER_DATA[Offer::FIELD_PRICE],
            OfferInterface::FIELD_CURRENCY => OfferTest::OFFER_DATA[Offer::FIELD_CURRENCY],
        ];

        return $params;
    }

    /**
     * TODO: IN PROGRESS, NOT FULLY DONE.
     */
    public function testPopulateOffer_newOffer()
    {
        $params = $this->getParamsPopulateOffer();

        $offer = $this->offerServiceHelper->populateOffer($params);

        $this->assertEquals(OfferTest::OFFER_DATA[Offer::FIELD_V1_OFFER_ID], $offer->getV1Id());
        $this->assertEquals(OfferInterface::STATUS_ONLINE, $offer->getStatus());
        $this->assertEquals(OfferTest::OFFER_DATA[Offer::FIELD_MODEL], $offer->getModel());
        $this->assertEquals('UNIQUE_AD_ID_FOR_PARAMS_TEST', $offer->getAdId());
        $this->assertEquals($this->offerTest->getMockMake(), $offer->getMake());
        $this->assertEquals($this->offerTest->getMockCategory(), $offer->getCategory());
        $this->assertEquals($this->offerTest->getMockSeller(), $offer->getSeller());
        $this->assertEquals(OfferTest::OFFER_DATA[Offer::FIELD_PRICE], $offer->getPrice());
        $this->assertEquals(OfferTest::OFFER_DATA[Offer::FIELD_CURRENCY], $offer->getCurrency());
        $this->assertGreaterThanOrEqual(1, $offer->getDescriptions()->count());
        /* @var \TradusBundle\Entity\OfferDescription $offerDescription */
        $offerDescription = $offer->getDescriptions()->current();
        $this->assertEquals('UNIT_TEST_MAKE UNIT_TEST_MODEL', $offerDescription->getTitle());
    }

    public function testPopulateOffer_newOfferNoSellerGivenException()
    {
        $params = $this->getParamsPopulateOffer();
        unset($params[OfferInterface::FIELD_SELLER]);
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->populateOffer($params);
    }

    public function testPopulateOffer_newOfferNoSellerFoundException()
    {
        $params = $this->getParamsPopulateOffer();
        $params[OfferInterface::FIELD_SELLER] = 666666;
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->populateOffer($params);
    }

    public function testPopulateOffer_newOfferAdIdExistsException()
    {
        $params = $this->getParamsPopulateOffer();
        $params[OfferInterface::FIELD_AD_ID] = OfferTest::OFFER_DATA[Offer::FIELD_AD_ID];
        $this->expectException('Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException');
        $offer = $this->offerServiceHelper->populateOffer($params);
    }

    public function testPopulateOffer_newOfferCategoryNotFoundException()
    {
        $params = $this->getParamsPopulateOffer();
        $params[OfferInterface::FIELD_CATEGORY] = 666666;
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $offer = $this->offerServiceHelper->populateOffer($params);
    }

    public function testPopulateOffer_newOfferCategoryNotValidPriceException()
    {
        $params = $this->getParamsPopulateOffer();
        $params[OfferInterface::FIELD_PRICE] = 'abs';
        $this->expectException('Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException');
        $offer = $this->offerServiceHelper->populateOffer($params);
        //$this->assertEquals(0.0, $offer->getPrice());
    }

    public function testPopulateOffer_newOfferWithPackageGoldSeller()
    {
        $params = $this->getParamsPopulateOffer();
        $params[OfferInterface::FIELD_SELLER] = 5;
        $offer = $this->offerServiceHelper->populateOffer($params);

        $expectedSortIndex = new \DateTime();
        $expectedSortIndex->modify('+12 hour');
        $this->assertEquals($expectedSortIndex->format('Y-m-d H'), $offer->getSortIndex()->format('Y-m-d H'));
        $this->assertEquals(Seller::SELLER_TYPE_PACKAGE_GOLD, ($offer->getSeller())->getSellerType());
    }
}
