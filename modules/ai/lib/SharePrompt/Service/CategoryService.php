<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service;

use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Repository\CategoryRepository;
use Bitrix\Main\Localization\Loc;

class CategoryService
{
	protected array $categoriesNameByCode = [];

	public function __construct(
		protected CategoryRepository $categoryRepository
	)
	{
	}

	/**
	 * @return Category[]
	 */
	public function getForbiddenCategoryList(): array
	{
		return [
			Category::READONLY_LIVEFEED,
			Category::CHAT,
			Category::SYSTEM,
			Category::LIST,
		];
	}

	/**
	 * @param string $category
	 * @return Category|null
	 */
	public function getAvailableCategoryForSave(string $category): ?Category
	{
		$categoryData = Category::tryFrom($category);
		if (empty($categoryData))
		{
			return null;
		}

		if (in_array($categoryData, $this->getForbiddenCategoryList()))
		{
			return null;
		}

		return $categoryData;
	}

	/**
	 * @param string[] $categories
	 * @return Category[]
	 */
	public function getAvailableCategoriesForSave(array $categories): array
	{
		if (empty($categories))
		{
			return [];
		}

		$result = [];
		foreach ($categories as $category)
		{
			$categoryData = $this->getAvailableCategoryForSave($category);
			if (!empty($categoryData))
			{
				$result[$category] = $categoryData;
			}
		}

		return $result;
	}

	/**
	 * @return list<array{name: string, code: string}>
	 */
	public function getCategoryListWithTranslations(): array
	{
		return [
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_LIVEFEED'),
				'code' => Category::LIVEFEED->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_LIVEFEED_COMMENTS'),
				'code' => Category::LIVEFEED_COMMENTS->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_TASKS'),
				'code' => Category::TASKS->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_TASKS_COMMENTS'),
				'code' => Category::TASKS_COMMENTS->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_MAIL'),
				'code' => Category::MAIL->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_MAIL_CRM'),
				'code' => Category::MAIL_CRM->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_LANDING'),
				'code' => Category::LANDING->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_CALENDAR'),
				'code' => Category::CALENDAR->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_CALENDAR_COMMENTS'),
				'code' => Category::CALENDAR_COMMENTS->value
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_CRM_ACTIVITY'),
				'code' => Category::CRM_ACTIVITY->value,
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_CRM_TIMELINE_COMMENT'),
				'code' => Category::CRM_TIMELINE_COMMENT->value,
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_CRM_COMMENT_FIELD'),
				'code' => Category::CRM_COMMENT_FIELD->value,
			],
			[
				'name' => Loc::getMessage('AI_SERVICE_CATEGORY_PRODUCT_DESCRIPTION'),
				'code' => Category::PRODUCT_DESCRIPTION->value,
			],
		];
	}

	/**
	 * @param string[] $codes
	 * @return array{name: string, code: string}
	 */
	public function getCategoriesWithNameByCodes(array $codes): array
	{
		$mapArray = $this->getCategoriesNameByCode();

		$result = [];
		foreach ($codes as $code)
		{
			if (!array_key_exists($code, $mapArray))
			{
				continue;
			}

			$result[] = [
				'name' => $mapArray[$code],
				'code' => $code,
			];
		}

		return $result;
	}

	public function getByPromptId(int $promptId): array
	{
		$categoriesData = $this->categoryRepository->getCodesByPromptId($promptId);
		if (empty($categoriesData))
		{
			return [];
		}

		$mapArray = $this->getCategoriesNameByCode();
		$result = [];
		foreach ($categoriesData as $categoryData)
		{
			if (empty($categoryData['CODE']))
			{
				continue;
			}

			$code = $categoryData['CODE'];
			if (empty($mapArray[$code]))
			{
				$this->log('Not found translate' . $code);
				continue;
			}

			$result[] = [
				'code' => $code,
				'name' => $mapArray[$code],
			];
		}

		return $result;
	}

	public function getAllBatches(): array
	{
		return [
			[
				Category::CRM_ACTIVITY->value,
				Category::CRM_COMMENT_FIELD->value,
				Category::CRM_TIMELINE_COMMENT->value,
			],
			[
				Category::LANDING,
				Category::LANDING_SETTING,
			],
		];
	}


	/**
	 * @return array<string, string>
	 */
	private function getCategoriesNameByCode(): array
	{
		if (empty($this->categoriesNameByCode))
		{
			foreach ($this->getCategoryListWithTranslations() as $data)
			{
				$this->categoriesNameByCode[$data['code']] = $data['name'];
			}
		}

		return $this->categoriesNameByCode;
	}

	private function log(string $text): void
	{
		AddMessage2Log('AI_CATEGORY_SERVICE_ERROR: ' . $text);
	}
}
