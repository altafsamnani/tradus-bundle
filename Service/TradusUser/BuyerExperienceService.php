<?php

namespace TradusBundle\Service\TradusUser;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\BuyerExperience;
use TradusBundle\Entity\BuyerGoal;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Repository\BuyerExperienceRepository;
use TradusBundle\Repository\BuyerGoalRepository;
use TradusBundle\Repository\CategoryRepository;

/**
 * Class TradusUserService.
 */
class BuyerExperienceService
{
    use EntityValidationTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Translator */
    protected $translator;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $this->entityManager = $entityManager;
    }

    /**
     * Gel all necessary data for the buyer experience survey.
     *
     * @param $userId
     * @param $locale
     * @param $sitecodeId
     * @return array
     */
    public function getUserExperience($userId, $locale, $sitecodeId)
    {
        $buyerGoals = [];
        $buyerTypes = [];
        $buyerCategories = [];
        $categories = [];
        $goals = [];
        $step = 1;
        /** @var BuyerExperienceRepository $buyerExperienceRepository */
        $buyerExperienceRepository = $this->entityManager->getRepository(BuyerExperience::class);
        /** @var BuyerGoalRepository $buyerGoalRepository */
        $buyerGoalRepository = $this->entityManager->getRepository(BuyerGoal::class);
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->entityManager->getRepository(Category::class);

        /**
         * Check to see if this buyer already has some info saved.
         * @var BuyerExperience
         */
        $buyerExperience = $buyerExperienceRepository->findOneBy(['userId' => $userId, 'sitecodeId' => $sitecodeId]);
        if ($buyerExperience && $buyerExperience->getTypes()) {
            $buyerTypes = $buyerExperience->getTypes();
        }
        if ($buyerExperience && $buyerExperience->getCategories()) {
            $buyerCategories = $buyerExperience->getCategories();
        }
        if ($buyerExperience && $buyerExperience->getGoals()) {
            $buyerGoals = $buyerExperience->getGoals();
        }

        /** Get the list of L1 and L2 and see if any of them is checked by the buyer */
        $categoriesCache = $categoryRepository->getCategoriesFromCache($locale);
        foreach ($categoriesCache as $categoryId => $category) {
            if (in_array($categoryId, $buyerTypes)) {
                if ($step == 1) {
                    $step = 2;
                }
            }

            $children = [];
            foreach ($category['children'] as $childId => $child) {
                $selected = false;
                if (in_array($childId, $buyerCategories)) {
                    $selected = true;
                    $step = 3;
                }
                $children[] = [
                    'id' => $child['id'],
                    'name' => $child['label'],
                    'svg_icon' => $child['svg_icon'],
                    'selected' => $selected,
                ];
            }

            $selected = false;
            if (in_array($categoryId, $buyerTypes)) {
                $selected = true;
            }
            $categories[] = [
                'id' => $category['id'],
                'name' => $category['label'],
                'svg_icon' => $category['svg_icon'],
                'selected' => $selected,
                'children' => $children,
            ];
        }

        /** Get the list of goals and see if any of it is checked by the buyer */
        $result = $buyerGoalRepository->findBy(['deletedAt' => null, 'sitecodeId' => $sitecodeId]);
        /** @var BuyerGoal $goal */
        foreach ($result as $goal) {
            $selected = false;
            if (in_array($goal->getId(), $buyerGoals)) {
                $step = -1;
                $selected = true;
            }
            $goals[] = [
                    'id' => $goal->getId(),
                    'name' => $this->translator->trans($goal->getGoal()),
                    'selected' => $selected,
            ];
        }

        return [
          'categories' => $categories,
          'goals' => $goals,
          'step' => $step,
        ];
    }

    /**
     * Saves a buyer experience or updates it if existing.
     *
     * @param int $userId
     * @param int $sitecodeId
     * @param array $types
     * @param array $categories
     * @param array $goals
     * @return BuyerExperience
     */
    public function saveBuyerExperience(int $userId, int $sitecodeId, array $types, array $categories, array $goals)
    {
        /** @var BuyerExperienceRepository $buyerExperienceRepository */
        $buyerExperienceRepository = $this->entityManager->getRepository(BuyerExperience::class);
        $buyerExperience = $buyerExperienceRepository->getBuyerExperience($userId, $sitecodeId);
        if (! $buyerExperience) {
            $buyerExperience = new BuyerExperience();
            $buyerExperience->setUserId($userId);
            $buyerExperience->setSitecodeId($sitecodeId);
        }
        if ($buyerExperience) {
            $buyerExperience->setUpdatedAt(new DateTime());
        }

        if (! empty($types)) {
            $buyerExperience->setTypes(json_encode($types));
        }

        if (! empty($categories)) {
            $buyerExperience->setCategories(json_encode($categories));
        }

        if (! empty($goals)) {
            $buyerExperience->setGoals(json_encode($goals));
        }

        $this->entityManager->persist($buyerExperience);
        $this->entityManager->flush();

        return $buyerExperience;
    }

    /**
     * To transform BuyerExperience data.
     *
     * @param BuyerExperience $buyerExperience
     * @return array
     */
    public function transform(BuyerExperience $buyerExperience): array
    {
        return [
            'types' => $buyerExperience->getTypes(),
            'categories' => $buyerExperience->getCategories(),
            'goals' => $buyerExperience->getGoals(),
            'user_id' => $buyerExperience->getUserId(),
            'sitecode_id' => $buyerExperience->getSitecodeId(),
        ];
    }
}
