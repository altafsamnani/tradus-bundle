<?php

namespace AppBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Repository\SellerRepository;
use TradusBundle\Service\Helper\SellerServiceHelper;
use TradusBundle\Tests\Entity\SellerTest;

/**
 * Class SellerServiceHelperTest.
 */
class SellerServiceHelperTest extends KernelTestCase
{
    /**
     * Default constants.
     */
    const EMAIL = 'donald@duck.com';
    const COUNTRY = 'NL';
    const CITY = 'Tollembeek';
    const SLUG = 'test-slug';
    const COMPANY_NAME = 'Tradus';
    const ADDRESS = 'De Entree 230, 1101 EE Amsterdam';

    /**
     * Patch constants.
     */
    const PATCH_EMAIL = 'duck@donald.com';
    const PATCH_COUNTRY = 'DE';
    const PATCH_CITY = 'Amsterdam';
    const PATCH_SLUG = 'olx';
    const PATCH_COMPANY_NAME = 'OLX';
    const PATCH_ADDRESS = 'Prins Hendriklaan 56, 1075 BE Amsterdam';

    /**
     * @var SellerServiceHelper
     */
    protected $sellerServiceHelper;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $usedDatabaseForTest;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        $kernel = self::bootKernel();

        $sellerTest = new SellerTest();

        // Fetch database variable if set we will use the database for testing if not we mock it.
        $database = getenv('DATABASE_URL');
        if (! empty($database) && true == false) {
            $this->usedDatabaseForTest = true;
            $this->entityManager = $kernel->getContainer()
                ->get('doctrine')
                ->getManager();

            $this->sellerServiceHelper = new SellerServiceHelper($this->entityManager);
        } else {
            $sellerMock = $sellerTest->getMockSeller();

            $sellerFindMap = [
                [1, null, null, $this->mockStoredSeller()],
                [2, null, null, null],
            ];

            $sellerSlugMap = [
                [self::SLUG, $this->mockStoredSeller()],
                ['test-not-found', null],
            ];

            $sellerFindOneByMap = [
                [['email' => $sellerMock->getEmail()], $sellerMock],
                [['email' => self::EMAIL], null],
            ];

            $this->usedDatabaseForTest = false;

            // Mock the repository so it returns the mock of the seller.
            $sellerRepository = $this->getMockBuilder(SellerRepository::class)
                ->setMethodsExcept(['generateSlug'])
                ->disableOriginalConstructor()
                ->getMock();

            $sellerRepository->expects($this->any())
                ->method('getSellerBySlug')
                ->willReturnMap($sellerSlugMap);

            $sellerRepository
                ->method('find')
                // --------------------------------------------------------------------------
                // REMINDER Return value map needs all requirements including optional ones.|
                // --------------------------------------------------------------------------
                ->willReturnMap($sellerFindMap);

            $sellerRepository->expects($this->any())
                ->method('findOneBy')
                ->willReturnMap($sellerFindOneByMap);

            // Mock the EntityManager to return the mock of the repository.

            /* @var EntityManager $entityManager */
            $this->entityManager = $this->createMock('\Doctrine\ORM\EntityManager');

            $this->entityManager->expects($this->any())
                ->method('getRepository')
                ->willReturn($sellerRepository);

            $this->validator = static::$kernel->getContainer()->get('validator');

            //$this->sellerServiceHelper = new SellerServiceHelper($this->entityManager);
            $this->sellerServiceHelper = static::$kernel->getContainer()->get('seller.helper');
            $this->sellerServiceHelper->entityManager = $this->entityManager;
            $this->sellerServiceHelper->repository = $sellerRepository;
        }
    }

    /**
     * Test find seller by id.
     */
    public function testSellerService()
    {

        /** @var Seller $stored_seller */
        $stored_seller = $this->sellerServiceHelper->storeSeller([
            SellerInterface::FIELD_EMAIL => self::EMAIL,
            SellerInterface::FIELD_COUNTRY => self::COUNTRY,
            SellerInterface::FIELD_CITY => self::CITY,
            SellerInterface::FIELD_SLUG => self::SLUG,
            SellerInterface::FIELD_STATUS => Seller::STATUS_ONLINE,
            SellerInterface::FIELD_TYPE => Seller::SELLER_TYPE_FREE,
            SellerInterface::FIELD_COMPANY_NAME => self::COMPANY_NAME,
            SellerInterface::FIELD_ADDRESS => self::ADDRESS,
        ]);

        // Fetch id for testing the find by id.
        $id = ($temp_id = $stored_seller->getId()) === null ? 1 : $temp_id;

        /** @var Seller $seller_found_by_id */
        $seller_found_by_id = $this->sellerServiceHelper->findSellerById($id);

        // Check if the found seller object matches the defaults.
        self::sellerObjectMatchesDefault($seller_found_by_id);

        /* @var Seller $seller_found_by_mail */
        //$seller_found_by_mail = $this->sellerServiceHelper->findSellerByEmail(self::EMAIL);

        // Check if the found seller object matches the defaults.
        //self::sellerObjectMatchesDefault($seller_found_by_mail);

        try {
            /** @var Seller $seller_found_by_mail */
            $seller_found_by_slug = $this->sellerServiceHelper->findSellerBySlug(self::SLUG);

            // Check if the found seller object matches the defaults.
            self::sellerObjectMatchesDefault($seller_found_by_slug);
        } catch (ORMException $exception) {
            fwrite(STDERR, print_r($exception->getMessage(), true));
        }

        /** @var Seller $patched_seller */
        $patched_seller = $this->sellerServiceHelper->patchSeller([
            SellerInterface::FIELD_SELLER_ID => $id,
            SellerInterface::FIELD_EMAIL => self::PATCH_EMAIL,
            SellerInterface::FIELD_COUNTRY => self::PATCH_COUNTRY,
            SellerInterface::FIELD_CITY => self::PATCH_CITY,
            SellerInterface::FIELD_STATUS => Seller::STATUS_OFFLINE,
            SellerInterface::FIELD_TYPE => Seller::SELLER_TYPE_PREMIUM,
            SellerInterface::FIELD_COMPANY_NAME => self::PATCH_COMPANY_NAME,
            SellerInterface::FIELD_ADDRESS => self::PATCH_ADDRESS,
        ]);

        // Check if the patch has worked and we have indeed patches all the fields.
        self::sellerObjectMatchesPatchesValues($patched_seller);

        // Try to restore the seller which should re-enable the seller.
        $restored_seller = $this->sellerServiceHelper->restoreSeller($id);

        // Check if the restore has worked its magic.
        $this->assertEquals(SellerInterface::STATUS_ONLINE, $restored_seller->getStatus());

        // Delete the seller.
        $deleted_seller = $this->sellerServiceHelper->deleteSeller($id);

        // Check if the status is correct.
        $this->assertEquals($deleted_seller->getStatus(), SellerInterface::STATUS_DELETED);

        // Physically remove the created test seller from the database if database
        // is in use.
        if ($this->usedDatabaseForTest) {
            try {
                $this->entityManager->remove($deleted_seller);
                $this->entityManager->flush();
            } catch (ORMException $exception) {
                fwrite(STDERR, print_r($exception->getMessage(), true));
            }
        }
    }

    /**
     * Tests seller creation with an invalid email.
     */
    public function testUnprocessableEntityHttpException()
    {
        $params = [
            SellerInterface::FIELD_EMAIL => 'donald_not_valid_duck',
            SellerInterface::FIELD_CITY => self::CITY,
            SellerInterface::FIELD_COUNTRY => self::COUNTRY,
            SellerInterface::FIELD_COMPANY_NAME => self::COMPANY_NAME,
            SellerInterface::FIELD_ADDRESS => self::ADDRESS,
            SellerInterface::FIELD_TYPE => SellerInterface::SELLER_TYPE_PREMIUM,
        ];
        $this->expectException('Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException');

        $this->sellerServiceHelper->populateSeller($params);
    }

    /**
     * Tests seller creation with an invalid email.
     */
    public function testDifferentSellerTypes()
    {
        $params = [
            SellerInterface::FIELD_EMAIL => self::EMAIL,
            SellerInterface::FIELD_CITY => self::CITY,
            SellerInterface::FIELD_COUNTRY => self::COUNTRY,
            SellerInterface::FIELD_COMPANY_NAME => self::COMPANY_NAME,
            SellerInterface::FIELD_ADDRESS => self::ADDRESS,
        ];

        $params[SellerInterface::FIELD_TYPE] = SellerInterface::SELLER_TYPE_PACKAGE_GOLD;
        $seller = $this->sellerServiceHelper->populateSeller($params);
        $this->assertEquals(Seller::SELLER_TYPE_PACKAGE_GOLD, $seller->getSellerType());

        $params[SellerInterface::FIELD_TYPE] = SellerInterface::SELLER_TYPE_PACKAGE_SILVER;
        $seller = $this->sellerServiceHelper->populateSeller($params);
        $this->assertEquals(Seller::SELLER_TYPE_PACKAGE_SILVER, $seller->getSellerType());

        $params[SellerInterface::FIELD_TYPE] = SellerInterface::SELLER_TYPE_PACKAGE_BRONZE;
        $seller = $this->sellerServiceHelper->populateSeller($params);
        $this->assertEquals(Seller::SELLER_TYPE_PACKAGE_BRONZE, $seller->getSellerType());

        $params[SellerInterface::FIELD_TYPE] = SellerInterface::SELLER_TYPE_PACKAGE_FREE;
        $seller = $this->sellerServiceHelper->populateSeller($params);
        $this->assertEquals(Seller::SELLER_TYPE_PACKAGE_FREE, $seller->getSellerType());

        $this->expectException('Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException');
        $params[SellerInterface::FIELD_TYPE] = 66666; // SHOULD NOT EXIST
        $this->sellerServiceHelper->populateSeller($params);
    }

    /**
     * Tests patching a non-existent seller.
     */
    public function testPatchSellerNotFoundException()
    {
        $params = [
            SellerInterface::FIELD_EMAIL => self::EMAIL,
            SellerInterface::FIELD_CITY => self::CITY,
            SellerInterface::FIELD_COUNTRY => self::COUNTRY,
            SellerInterface::FIELD_COMPANY_NAME => self::COMPANY_NAME,
            SellerInterface::FIELD_ADDRESS => self::ADDRESS,
            SellerInterface::FIELD_TYPE => SellerInterface::SELLER_TYPE_PREMIUM,
            SellerInterface::FIELD_SELLER_ID => 2,
        ];
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $this->sellerServiceHelper->patchSeller($params);
    }

    /**
     * Tests the generation of slug with existing and non-existing slug.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testSellerRepositoryGenerateSlug()
    {
        /** @var SellerRepository $repository */
        $repository = $this->sellerServiceHelper->entityManager->getRepository('TradusBundle:Seller');
        $this->assertEquals($repository->generateSlug('test-not-found'), 'test-not-found');
        $this->assertNotEquals($repository->generateSlug(self::SLUG), self::SLUG);
    }

    /**
     * Function for validating if the seller object is indeed the seller object we stored.
     *
     * @param Seller $seller
     *   The seller object we would like to validate.
     */
    public function sellerObjectMatchesDefault(Seller $seller)
    {
        $this->assertEquals(self::EMAIL, $seller->getEmail());
        $this->assertEquals(self::COUNTRY, $seller->getCountry());
        $this->assertEquals(self::CITY, $seller->getCity());
        $this->assertEquals(self::SLUG, $seller->getSlug());
        $this->assertEquals(Seller::STATUS_ONLINE, $seller->getStatus());
        $this->assertEquals(Seller::SELLER_TYPE_FREE, $seller->getSellerType());
        $this->assertEquals(self::COMPANY_NAME, $seller->getCompanyName());
        $this->assertEquals(self::ADDRESS, $seller->getAddress());
    }

    /**
     * Function for validating if the seller object is indeed the seller object we patched.
     *
     * @param Seller $seller
     *   The seller object we would like to validate.
     */
    public function sellerObjectMatchesPatchesValues(Seller $seller)
    {
        $this->assertEquals(self::PATCH_EMAIL, $seller->getEmail());
        $this->assertEquals(self::PATCH_COUNTRY, $seller->getCountry());
        $this->assertEquals(self::PATCH_CITY, $seller->getCity());
        $this->assertEquals(self::PATCH_SLUG, $seller->getSlug());
        $this->assertEquals(Seller::STATUS_OFFLINE, $seller->getStatus());
        $this->assertEquals(Seller::SELLER_TYPE_PREMIUM, $seller->getSellerType());
        $this->assertEquals(self::PATCH_COMPANY_NAME, $seller->getCompanyName());
        $this->assertEquals(self::PATCH_ADDRESS, $seller->getAddress());
    }

    /**
     * Function for validating the parameters are in the correct v1 format.
     *
     * @param array $parameters
     *   The original parameters.
     * @param array $v1_parameters
     *   The parameters that need to be validated.
     */
    public function sellerParametersMatchV1Format(array $parameters, array $v1_parameters)
    {
        if (isset($parameters[SellerInterface::FIELD_COMPANY_NAME])) {
            $this->assertArrayHasKey(SellerInterface::FIELD_V1_COMPANY_NAME, $v1_parameters);
        }
        if (isset($parameters[SellerInterface::FIELD_SELLER_ID])) {
            $this->assertArrayHasKey(SellerInterface::FIELD_V1_SELLER_ID, $v1_parameters);
        }
        if (isset($parameters[SellerInterface::FIELD_STATUS])) {
            $this->assertArrayHasKey(SellerInterface::FIELD_V1_STATUS, $v1_parameters);
            $this->assertTrue(in_array($v1_parameters[SellerInterface::FIELD_V1_STATUS], SellerInterface::V1_STATUSES));
        }
        if (isset($parameters[SellerInterface::FIELD_TYPE])) {
            $this->assertArrayHasKey(SellerInterface::FIELD_V1_TYPE, $v1_parameters);
        }
    }

    /**
     * Function for validating that the returned statuses are correct.
     *
     * @param array $statuses
     *   The statuses array that needs to be compared.
     */
    public function sellerStatusesAreValid(array $statuses)
    {
        $valid_statuses = [
            SellerInterface::STATUS_ONLINE,
            SellerInterface::STATUS_OFFLINE,
            SellerInterface::STATUS_DELETED,
        ];
        $this->assertEquals($valid_statuses, $statuses);
    }

    /**
     * Function for mocking the stored seller.
     *
     * @return Seller
     *   Returns the Seller with default data.
     */
    private function mockStoredSeller()
    {
        $sellerTest = new SellerTest();
        $stored_seller = $sellerTest->getMockSeller();
        $stored_seller->setEmail(self::EMAIL);
        $stored_seller->setCountry(self::COUNTRY);
        $stored_seller->setCity(self::CITY);
        $stored_seller->setSlug(self::SLUG);
        $stored_seller->setStatus(SellerInterface::STATUS_ONLINE);
        $stored_seller->setSellerType(SellerInterface::SELLER_TYPE_FREE);
        $stored_seller->setCompanyName(self::COMPANY_NAME);
        $stored_seller->setAddress(self::ADDRESS);

        return $stored_seller;
    }
}
