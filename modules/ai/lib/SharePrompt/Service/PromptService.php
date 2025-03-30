<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service;

use Bitrix\AI\Facade\User;
use Bitrix\AI\Helper;
use Bitrix\AI\Prompt\Item;
use Bitrix\AI\Prompt\Manager;
use Bitrix\AI\SharePrompt\Dto\CreateDto;
use Bitrix\AI\SharePrompt\Repository\CategoryRepository;
use Bitrix\AI\SharePrompt\Repository\PromptRepository;
use Bitrix\AI\SharePrompt\Repository\TranslateNameRepository;
use Bitrix\AI\SharePrompt\Service\Dto\PromptForUpdateDto;
use Bitrix\AI\Synchronization\PromptSync;
use Bitrix\Main\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;

class PromptService
{
	public function __construct(
		protected PromptRepository $promptRepository,
		protected CategoryRepository $categoryRepository,
		protected TranslateNameRepository $translateNameRepository,
		protected PromptDisplayRuleService $promptDisplayRuleService
	)
	{
	}

	public function getPromptByIdForUpdate(int $promptId, int $userId, array $categoriesList): PromptForUpdateDto|array
	{
		$promptData = $this->promptRepository->getByIdForUpdate($promptId);
		if (empty($promptData))
		{
			return [];
		}

		return new PromptForUpdateDto($promptData, $userId, $categoriesList);
	}

	/**
	 * @throws \Exception
	 */
	public function changeActivatePrompt(int $promptId, bool $needActivate, int $userId): void
	{
		$this->promptRepository->changeActivatePrompt($promptId, $needActivate, $userId);
	}

	/**
	 * @param int[] $promptIds
	 * @param bool $needActivate
	 * @param int $userId
	 */
	public function changeActivatePrompts(array $promptIds, bool $needActivate, int $userId): Result
	{
		$this->promptRepository->changeActivatePrompts($promptIds, $needActivate, $userId);

		return new Result();
	}

	/**
	 * @param array $categories
	 * @param int $promptId
	 * @param bool $needDeleteOld
	 * @return void
	 */
	public function addCategoriesForPrompt(array $categories, int $promptId, bool $needDeleteOld = false): void
	{
		if ($needDeleteOld)
		{
			$this->categoryRepository->deleteByPromptId($promptId);
		}

		if (!empty($categories))
		{
			$categoriesForInsert = [];
			foreach ($categories as $category)
			{
				$category = trim($category);
				if (!empty($category))
				{
					$categoriesForInsert[] = $category;
				}
			}
		}

		if (!empty($categoriesForInsert))
		{
			$this->categoryRepository->addCategoriesForPrompt($promptId, array_unique($categoriesForInsert));
		}
	}

	public function addTranslateNames(array $names, int $promptId, bool $needDeleteOld = false): void
	{
		if ($needDeleteOld)
		{
			$this->translateNameRepository->deleteByPromptId($promptId);
		}

		if (!empty($names))
		{
			$this->translateNameRepository->addNamesForPrompt($promptId, $names);
		}
	}

	/**
	 * @param string $code
	 * @param string $lang
	 * @param string|null $roleCode
	 * @return array
	 */
	public function getSystemsPromptsByCategory(
		string $code,
		string $lang,
		?string $roleCode
	): array
	{
		$prompts = $this->promptRepository->getSystemPromptsByCategory($code, $lang, $roleCode);
		if (empty($prompts))
		{
			return [];
		}

		$collection = [];
		$promptIds = [];
		foreach ($prompts as $prompt)
		{
			if (empty($prompt['ID']))
			{
				continue;
			}

			$promptId = (int)$prompt['ID'];
			$promptIds[] = $promptId;
			$collection[$promptId] = Manager::getItemFromRawRow(
				$this->preparePrompt($prompt)
			);
		}

		if (empty($promptIds))
		{
			return $collection;
		}

		$forbiddenPromptIds = $this->promptDisplayRuleService->getForbiddenPrompts($promptIds, $lang);
		foreach ($forbiddenPromptIds as $forbiddenPromptId)
		{
			if (isset($collection[$forbiddenPromptId]))
			{
				unset($collection[$forbiddenPromptId]);
			}
		}

		$childPrompts = $this->getChildrenPromptListByIds(
			array_keys($collection),
			$forbiddenPromptIds,
			$lang
		);
		$this->fillChildrenForItems($collection, $childPrompts);

		return $collection;
	}

	/**
	 * @param CreateDto $createDTO
	 * @return AddResult|UpdateResult
	 */
	public function createPrompt(CreateDto $createDTO): UpdateResult|AddResult
	{
		$createDTO->promptCode = Helper::generateUUID();

		return $this->savePrompt($createDTO);
	}

	public function savePrompt(CreateDto $createDTO)
	{
		$promptData = [
			'code' => $createDTO->promptCode,
			'type' => $createDTO->promptType,
			'hash' => $createDTO->getHash(),
			'icon' => $createDTO->promptIcon,
			'prompt' => $createDTO->promptDescription,
			'translate' => [User::getUserLanguage() => $createDTO->promptTitle],
			'category' => array_keys($createDTO->categoriesForSaveData),
		];

		return (new PromptSync())->updatePromptByFields(
			$promptData,
			$createDTO->userCreatorId,
			[],
			$createDTO->needChangeAuthor
		);
	}

	public function getPromptByCodes(array $data): ?array
	{
		if (empty($data))
		{
			return null;
		}

		$codeApp = '';
		if (array_key_exists('APP_CODE', $data))
		{
			$codeApp = $data['APP_CODE'];
		}

		$code = '';
		if (array_key_exists('CODE', $data))
		{
			$code = $data['CODE'];
		}

		if (empty($codeApp) && empty($code))
		{
			return null;
		}

		$prompt = $this->promptRepository->getByCodes($codeApp, $code);

		if (!$prompt)
		{
			return null;
		}

		return $prompt;
	}

	public function getPromptIdsByCodes(array $codes): array
	{
		$promptsData = $this->promptRepository->getByIds($codes);
		if (empty($promptsData))
		{
			return [];
		}

		$result = [];
		foreach ($promptsData as $promptData)
		{
			$result[$promptData['CODE']] = $promptData['ID'];
		}

		return $result;
	}

	public function getPromptIdByCode(string $code): ?int
	{
		$promptData = $this->promptRepository->getByCode($code);
		if (empty($promptData['ID']))
		{
			return null;
		}

		return (int)$promptData['ID'];
	}

	/**
	 * @param string $code
	 * @return array{int|null, bool|null}
	 */
	public function getMainPromptDataByCode(string $code): array
	{
		$promptData = $this->promptRepository->getByCode($code);
		if (empty($promptData['ID']))
		{
			return [null, null];
		}

		return [(int)$promptData['ID'], $promptData['IS_SYSTEM'] === 'Y'];
	}

	public function getMainPromptDataByCodes(array $codes): array
	{
		$promptsData = $this->promptRepository->getByPromptCodes($codes);

		$result = [];
		foreach ($promptsData as $promptData)
		{
			if (empty($promptData['ID']))
			{
				$result[] = [null, null];
			}

			$result[] = [(int)$promptData['ID'], $promptData['IS_SYSTEM'] === 'Y'];
		}

		return $result;
	}

	/**
	 * @param string $code
	 * @return array{int, bool, string}
	 */
	public function getMainPromptDataWithTextByCode(string $code): array
	{
		$promptData = $this->promptRepository->getMainDataWithPromptTextByCode($code);
		if (empty($promptData['ID']) || empty($promptData['PROMPT']) || !is_string($promptData['PROMPT']))
		{
			return [null, null, null];
		}

		return [(int)$promptData['ID'], $promptData['IS_SYSTEM'] === 'Y', $promptData['PROMPT']];
	}

	/**
	 * @param string $code
	 * @return array|null[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getMainPromptDataWithAuthorByCode(string $code): array
	{
		$promptData = $this->promptRepository->getMainDataWithAuthorByCode($code);
		if (empty($promptData['ID']))
		{
			return [null, null, null];
		}

		return [(int)$promptData['ID'], $promptData['IS_SYSTEM'] === 'Y', (int)$promptData['AUTHOR_ID']];
	}

	/**
	 * @param int $userId
	 * @param string $category
	 * @return Item[]
	 */
	public function getAccessiblePromptList(int $userId, string $lang, string $category): array
	{
		$prompts = $this->promptRepository->getAccessiblePromptList($userId, $lang, $category);
		if (empty($prompts))
		{
			return [];
		}

		$collection = [];
		foreach ($prompts as $prompt)
		{
			$collection[] = Manager::getItemFromRawRow(
				$this->preparePrompt($prompt)
			);
		}

		return $collection;
	}

	protected function preparePrompt(array $prompt): array
	{
		$prompt['TITLE'] = '';
		if (!empty($prompt['TITLE_DEFAULT']))
		{
			$prompt['TITLE'] = $prompt['TITLE_DEFAULT'];
		}

		if (!empty($prompt['TITLE_FOR_USER']))
		{
			$prompt['TITLE'] = $prompt['TITLE_FOR_USER'];
		}

		$prompt['HAS_SYSTEM_CATEGORY'] = !empty($prompt['CODE_CATEGORY_SYSTEM']);

		return $prompt;
	}

	public function getChildrenPromptListByIds(array $promptIds, array $forbiddenPromptIds, string $lang): array
	{
		$promptsCollection = $this->promptRepository->getChildrenPromptListByIds($promptIds, $forbiddenPromptIds, $lang);
		if (empty($promptsCollection))
		{
			return [];
		}

		return $promptsCollection;
	}

	/**
	 * @param Item[] $promptList
	 * @param array $childPrompts
	 * @return void
	 */
	public function fillChildrenForItems(array $promptList, array $childPrompts): void
	{
		foreach ($childPrompts as $childPrompt)
		{
			if (empty($promptList[$childPrompt['PARENT_ID']]))
			{
				continue;
			}

			$promptList[$childPrompt['PARENT_ID']]
				->addChild(
					Manager::getItemFromRawRow(
						$this->preparePrompt($childPrompt)
					)
				)
			;
		}
	}

	public function getPromptIdInAccessibleList(int $userId, int $promptId, ?bool $ignoreDelete = null): ?int
	{
		$data = $this->promptRepository->getPromptIdInAccessibleList($userId, $promptId, $ignoreDelete);
		if (empty($data))
		{
			return null;
		}

		return (int)$data['ID'];
	}

	public function getAccessiblePrompt(int $userId, string $lang, string $promptCode): ?Item
	{
		if (empty($promptCode))
		{
			return null;
		}

		$prompts = $this->promptRepository->getAccessiblePromptList(
			userId: $userId,
			lang: $lang,
			promptCode: $promptCode
		);

		if (empty($prompts[0]))
		{
			return null;
		}

		return Manager::getItemFromRawRow(
			$this->preparePrompt($prompts[0])
		);
	}
}
