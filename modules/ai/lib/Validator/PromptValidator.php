<?php declare(strict_types=1);

namespace Bitrix\AI\Validator;

use Bitrix\AI\Exception\ValidateException;
use Bitrix\AI\Model\PromptTable;
use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Repository\ShareRepository;
use Bitrix\AI\SharePrompt\Service\CategoryService;
use Bitrix\AI\SharePrompt\Service\OwnerService;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\Main\Localization\Loc;

class PromptValidator
{
	public const MIN_LEN_PROMPT = '5';
	public const MAX_LEN_PROMPT = '2500';

	public function __construct(
		protected PromptService $promptService,
		protected ShareRepository $shareRepository,
		protected ShareService $shareService,
		protected OwnerService $ownerService,
		protected CategoryService $categoryService
	)
	{
	}

	/**
	 * Check has promptId in share
	 *
	 * @param int $promptId
	 * @param string $fieldName
	 * @return array
	 * @throws ValidateException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function hasPromptIdInShare(int $promptId, string $fieldName): array
	{
		$promptIds = $this->shareService->findByPromptId($promptId);
		if (empty($promptIds))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_HAS_NOT_PROMPT_IN_SHARE'));
		}

		return $promptIds;
	}

	/**
	 * Returns promptId by promptCode. If not found promptId throws ValidateException
	 *
	 * @param string $promptCode
	 * @param string $fieldName
	 * @return int
	 * @throws ValidateException
	 */
	public function getPromptByCode(string $promptCode, string $fieldName): int
	{
		$promptId = $this->promptService->getPromptIdByCode($promptCode);
		if (is_null($promptId) || $promptId === 0)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODE'));
		}

		return $promptId;
	}

	/**
	 * Return main prompt data by code
	 *
	 * @param string $promptCode
	 * @param string $fieldName
	 * @return array{int, bool, string}
	 * @throws ValidateException
	 */
	public function getPromptMainDataByCode(string $promptCode, string $fieldName): array
	{
		list($promptId, $isSystem, $promptText) = $this->promptService->getMainPromptDataWithTextByCode($promptCode);
		if (is_null($promptId) || $promptId === 0 || is_null($isSystem) || is_null($promptText))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODE'));
		}

		return [$promptId, $isSystem, $promptText];
	}

	/**
	 * Returns prompt not system by code
	 *
	 * @param string $promptCode
	 * @param string $fieldName
	 * @return int
	 * @throws ValidateException
	 */
	public function getPromptIdNotSystemByCode(string $promptCode, string $fieldName): int
	{
		list($promptId, $isSystem) = $this->promptService->getMainPromptDataByCode($promptCode);
		if (is_null($promptId) || $promptId === 0)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODE'));
		}

		if ($isSystem)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IS_SYSTEM_PROMPT'));
		}

		return $promptId;
	}

	/**
	 * @param string $promptCode
	 * @param string $fieldName
	 * @return int[]
	 * @throws ValidateException
	 */
	public function getPromptIdAndAuthorNotSystemByCode(string $promptCode, string $fieldName): array
	{
		list($promptId, $isSystem, $authorId) = $this->promptService->getMainPromptDataWithAuthorByCode($promptCode);
		if (is_null($promptId) || $promptId === 0)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODE'));
		}

		if ($isSystem)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IS_SYSTEM_PROMPT'));
		}

		return [$promptId, $authorId];
	}

	/**
	 * @param array $promptCodes
	 * @param string $fieldName
	 * @return int[]
	 * @throws ValidateException
	 */
	public function getPromptByCodesNotSystems(array $promptCodes, string $fieldName): array
	{
		$promptsData = $this->promptService->getMainPromptDataByCodes($promptCodes);
		if (empty($promptsData))
		{
			throw new ValidateException(
				$fieldName,
				Loc::getMessage(Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODES'))
			);
		}

		$promptsIds = [];
		foreach ($promptsData as $promptData)
		{
			list($promptId, $isSystem) = $promptData;
			if (is_null($promptId) || $promptId === 0)
			{
				throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODE'));
			}

			if ($isSystem)
			{
				throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IS_SYSTEM_PROMPT'));
			}

			$promptsIds[] = $promptId;
		}

		if (count($promptCodes) !== count($promptsIds))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_BY_CODES'));
		}

		return $promptsIds;
	}

	/**
	 * @param int $promptId
	 * @param string $fieldName
	 * @param int $userId
	 * @return bool
	 * @throws ValidateException
	 */
	public function accessOnPrompt(int $promptId, string $fieldName, int $userId): bool
	{
		$this->checkUserId($userId);

		list($hasAccess, $inOwnerId) = $this->shareService->accessOnPrompt($promptId, $userId);
		if (!$hasAccess)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NOT_ACCESS_ON_PROMPT'));
		}

		return !empty($inOwnerId);
	}

	public function accessOnPrompts(array $promptIds, string $fieldName, int $userId): array
	{
		$this->checkUserId($userId);

		list($hasAccess, $ownerIdsForSharingPrompts) = $this->shareService->accessOnPrompts($promptIds, $userId);
		if (!$hasAccess)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NOT_ACCESS_ON_PROMPT_LIST'));
		}

		return $ownerIdsForSharingPrompts;
	}

	protected function checkUserId(int $userId)
	{
		if (empty($userId))
		{
			throw new ValidateException('currentUser', Loc::getMessage('AI_VALIDATOR_NO_AUTHORIZED_USER'));
		}
	}

	/**
	 * @param int $promptId
	 * @param string $fieldName
	 * @param int $userId
	 * @return void
	 * @throws ValidateException
	 */
	public function inAccessibleIgnoreDelete(int $promptId, string $fieldName, int $userId): void
	{
		$promptId = $this->promptService->getPromptIdInAccessibleList($userId, $promptId);
		if (is_null($promptId) || $promptId === 0)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NOT_ACCESSIBLE'));
		}
	}

	/**
	 * @param int $promptId
	 * @param string $fieldName
	 * @param int $userId
	 * @return void
	 * @throws ValidateException
	 */
	public function inAccessibleList(int $promptId, string $fieldName, int $userId): void
	{
		$promptId = $this->promptService->getPromptIdInAccessibleList($userId, $promptId, true);
		if (is_null($promptId) || $promptId === 0)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NOT_ACCESSIBLE'));
		}
	}

	/**
	 * @param string $type
	 * @param string $fieldName
	 * @return void
	 * @throws ValidateException
	 */
	public function hasPromptType(string $type, string $fieldName): void
	{
		if (!in_array($type, [PromptTable::TYPE_DEFAULT, PromptTable::TYPE_SIMPLE_TEMPLATE]))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_HAS_NOT_PROMPT_TYPE'));
		}
	}

	/**
	 * @param string $text
	 * @param string $fieldName
	 * @return void
	 * @throws ValidateException
	 */
	public function isNotMinPromptLength(string $text, string $fieldName): void
	{
		if ((mb_strlen($text)) < static::MIN_LEN_PROMPT)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_LENGTH_IS_SMALL'));
		}
	}

	/**
	 * @param string $text
	 * @param string $fieldName
	 * @return void
	 * @throws ValidateException
	 */
	public function isNotMaxPromptLength(string $text, string $fieldName): void
	{
		if ((mb_strlen($text)) > static::MAX_LEN_PROMPT)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_LENGTH_IS_MAX'));
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function hasInFavoriteList(int $promptId, string $fieldName, int $userId): void
	{
		if (empty($this->ownerService->getFavoriteIdByUserIdAndPromptId($userId, $promptId)))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IS_NOT_IN_FAVORITE'));
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function hasNotInFavoriteList(int $promptId, string $fieldName, int $userId): void
	{
		if ($this->ownerService->isFavoritePrompt($userId, $promptId))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IN_FAVORITE'));
		}
	}

	public function getCategoryData(string $value, string $fieldName): Category
	{
		$category = Category::tryFrom($value);
		if (is_null($category))
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_NO_FOUND_SECTION')
			);
		}

		return $category;
	}

	/**
	 * @param array $values
	 * @param string $fieldName
	 * @return Category[]
	 * @throws ValidateException
	 */
	public function getAvailableCategoriesForSave(array $values, string $fieldName): array
	{
		$categories = $this->categoryService->getAvailableCategoriesForSave($values);

		$notFoundsCategories = [];
		foreach ($values as $categoryName)
		{
			if (is_string($categoryName) && !array_key_exists($categoryName, $categories))
			{
				$notFoundsCategories[] = $categoryName;
			}
		}

		if (!empty($notFoundsCategories))
		{
			throw new ValidateException(
				$fieldName,
				Loc::getMessage('AI_VALIDATOR_IS_NOT_AVAILABLE_CATEGORIES_FOR_SAVE') .
				implode(', ', $notFoundsCategories)
			);
		}

		return $categories;
	}
}
