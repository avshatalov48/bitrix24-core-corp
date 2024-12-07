<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service;

use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Prompt\Item;
use Bitrix\AI\SharePrompt\Events\Enums\ShareType;
use Bitrix\AI\SharePrompt\Dto\CreateDto;
use Bitrix\AI\SharePrompt\Repository\ShareRepository;
use Bitrix\AI\SharePrompt\Service\Dto\FavoritePromptsDto;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Routing\Exceptions\ParameterNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\EntitySelector;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Config\Option;

class ShareService
{
	use TranslateTrait;

	protected const ENTITY_ID_DEFAULT = 'user';
	protected const ENTITY_ID_PROJECT = 'project';

	public function __construct(
		protected PromptService $promptService,
		protected ShareRepository $shareRepository,
		protected OwnerService $ownerService,
		protected OwnerOptionService $ownerOptionService,
		protected PromptDisplayRuleService $promptDisplayRuleService
	)
	{
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function create(CreateDto $requestDTO): AddResult
	{
		return $this->shareRepository->create($requestDTO);
	}

	public function prepareAccessCodes(array $accessCodes, int $userIdCreator): array
	{
		if (!empty($accessCodes))
		{
			return EntitySelector\Converter::convertToFinderCodes($accessCodes);
		}

		return EntitySelector\Converter::convertToFinderCodes([
			[$this->getEntityIdDefault(), $userIdCreator]
		]);
	}

	/**
	 * @param array $accessCodesRaw
	 * @return int[]
	 * @throws ParameterNotFoundException
	 */
	public function getUsersIdsFromListRawCodes(array $accessCodesRaw): array
	{
		if (empty($accessCodesRaw))
		{
			return [];
		}

		$entityIdDefault = $this->getEntityIdDefault();
		$result = [];
		foreach ($accessCodesRaw as [$entityId, $id])
		{
			if ($entityId === $entityIdDefault && !empty($id))
			{
				$result[] = (int)$id;
			}
		}

		return $result;
	}

	public function getShareType(array $accessCodes, int $userIdCreator): ShareType
	{
		if (empty($accessCodes))
		{
			return ShareType::SHARED_NO;
		}

		$userCodeData = EntitySelector\Converter::convertToFinderCodes([
			[$this->getEntityIdDefault(), $userIdCreator]
		]);

		if (empty($userCodeData))
		{
			return ShareType::SHARED_NO;
		}

		reset($userCodeData);
		$userAccessCode = current($userCodeData);
		if (empty($userAccessCode))
		{
			return ShareType::SHARED_NO;
		}

		foreach ($accessCodes as $accessCode)
		{
			if ($accessCode !== $userAccessCode)
			{
				return ShareType::SHARED_YES;
			}
		}

		return ShareType::SHARED_NO;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getAccessCodesForPrompt(int $promptId): array
	{
		$accessCodes = $this->shareRepository->getAccessCodesForPrompt($promptId);
		if (empty($accessCodes))
		{
			return [];
		}

		$result = [];
		foreach ($accessCodes as $accessCode)
		{
			$result[] = $accessCode['ACCESS_CODE'];
		}

		return $result;
	}

	public function getProjectAccessCodes(array $projectIds): array
	{
		if (empty($projectIds))
		{
			return [];
		}

		$projectEntityId = $this->getEntityIdProject();

		return EntitySelector\Converter::convertToFinderCodes(
			array_map(
				fn(int $projectId) => [$projectEntityId, (int)$projectId],
				$projectIds
			)
		);
	}

	private function getEntityIdDefault(): string
	{
		return $this->getEntityIdInAccessList(static::ENTITY_ID_DEFAULT);
	}

	private function getEntityIdProject(): string
	{
		return $this->getEntityIdInAccessList(static::ENTITY_ID_PROJECT);
	}

	/**
	 * @param string $entity
	 * @return string
	 * @throws ParameterNotFoundException
	 */
	private function getEntityIdInAccessList(string $entity)
	{
		if (!in_array($entity, array_keys(EntitySelector\Converter::getCompatEntities())))
		{
			throw new ParameterNotFoundException(
				$entity . Loc::getMessage('AI_SERVICE_CODE_NO_FOUND_MSGVER_1')
			);
		}

		return $entity;
	}

	/**
	 * @param int $promptId
	 * @param int $userId
	 * @return array{bool, int|null}
	 */
	public function accessOnPrompt(int $promptId, int $userId): array
	{
		$result = $this->shareRepository->getInfoAccessPrompt([$promptId], $userId);
		if (empty($result))
		{
			return [false, null];
		}

		$idInOwnerTable = null;
		foreach ($result as $row)
		{
			if (!empty($row['OWNER_ID']))
			{
				$idInOwnerTable = (int)$row['OWNER_ID'];
				break;
			}
		}

		return [true, $idInOwnerTable];
	}

	public function accessOnPrompts(array $promptIds, int $userId): array
	{
		$result = $this->shareRepository->getInfoAccessPrompt($promptIds, $userId);
		if (empty($result))
		{
			return [false, []];
		}

		$ownerIdsForSharingPrompts = [];
		foreach ($result as $row)
		{
			if (!empty($row['PROMPT_ID']) && array_key_exists('OWNER_ID', $row))
			{
				if (empty($ownerIdsForSharingPrompts[$row['PROMPT_ID']]))
				{
					$ownerIdsForSharingPrompts[$row['PROMPT_ID']] = [];
				}
				$ownerIdsForSharingPrompts[$row['PROMPT_ID']][] = $row['OWNER_ID'];
			}
		}

		return [count($promptIds) === count($ownerIdsForSharingPrompts), $ownerIdsForSharingPrompts];
	}

	public function deleteSharingForChange(int $promptId): void
	{
		$this->shareRepository->deletePromptId($promptId);
	}

	public function getAccessiblePrompts(int $userId, string $lang, string $category): FavoritePromptsDto
	{
		$promptListAll = $this->promptService->getAccessiblePromptList($userId, $lang, $category);
		$favoritePrompts = $this->getFavoritePrompts($promptListAll);

		[$sortingInFavoriteListTable, $hasRowOption] = $this->ownerOptionService->getSortingInFavoriteList($userId);
		[$needUpdateSortingInFavoriteList, $sortingList] = $this->ownerOptionService->getUpdatingDataForFavoritesSortingList(
			array_unique($sortingInFavoriteListTable), array_keys($favoritePrompts)
		);

		[$userPrompts, $systemPrompts] = $this->sortPrompts($promptListAll);

		$systemPrompts = $this->prepareSystemItems($systemPrompts, $lang);

		return new FavoritePromptsDto(
			$userId,
			$needUpdateSortingInFavoriteList,
			$hasRowOption,
			$sortingList,
			$userPrompts,
			$this->sortBySection($systemPrompts),
			$this->sortFavoritePrompts($favoritePrompts, $sortingList)
		);
	}

	/**
	 * @param int $promptId
	 * @return int[]
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public function findByPromptId(int $promptId): array
	{
		$idsList = $this->shareRepository->findByPromptId($promptId);
		if (empty($idsList))
		{
			return [];
		}

		return array_map(fn($item) => (int)$item['ID'], $idsList);
	}

	public function addInFavoriteList(int $userId, int $promptId): void
	{
		[$sortingInFavoriteListTable, $hasRowOption] = $this->ownerOptionService->getSortingInFavoriteList($userId);
		$sortingInFavoriteListTable[] = $promptId;

		$this->ownerOptionService->updateFavoritesListSorting(
			$userId,
			array_unique($sortingInFavoriteListTable),
			$hasRowOption
		);
		$this->ownerService->addFavoriteForUser($userId, $promptId);
	}

	public function deleteInFavoriteList(int $userId, int $promptId): void
	{
		[$sortingInFavoriteListTable, $hasRowOption] = $this->ownerOptionService->getSortingInFavoriteList($userId);

		$key = array_search($promptId, $sortingInFavoriteListTable);
		if ($key !== false)
		{
			unset($sortingInFavoriteListTable[$key]);
			$this->ownerOptionService->updateFavoritesListSorting(
				$userId,
				$sortingInFavoriteListTable,
				$hasRowOption
			);
		}

		$this->ownerService->deleteFromFavoriteListWithCheck($userId, $promptId);
	}

	/**
	 * @param Item[] $promptList
	 * @return array
	 */
	private function sortPrompts(array $promptList): array
	{
		$userPrompts = [];
		$systemPrompts = [];

		$libraryEnable = Option::get('ai', 'ai_prompt_library_enable') === 'Y';
		foreach ($promptList as $prompt)
		{
			if (!$libraryEnable || $prompt->isSystem())
			{
				$systemPrompts[$prompt->getId()] = $prompt;
				continue;
			}

			$userPrompts[] = $prompt;
		}

		return [
			$userPrompts,
			$systemPrompts
		];
	}

	private static function sortBySection(array $objects): array
	{
		usort($objects, function (Item $a, Item $b) {
			if (empty($a->getSectionCode()) || empty($b->getSectionCode()))
			{
				return 0;
			}

			if ($a->getSectionCode() === $b->getSectionCode())
			{
				return $a->getSort() <=> $b->getSort();
			}

			return $a->getSectionCode() <=> $b->getSectionCode();
		});

		return $objects;
	}

	private function sortFavoritePrompts(array $prompts, array $sortingList): array
	{
		$result = [];

		foreach ($sortingList as $key)
		{
			if (array_key_exists($key, $prompts))
			{
				$result[$key] = $prompts[$key];
				unset($prompts[$key]);
			}
		}

		return array_merge($result, $prompts);
	}

	/**
	 * @param Item[] $promptInfo
	 * @return Item[]
	 */
	private function getFavoritePrompts(array $promptInfo): array
	{
		$result = [];
		foreach ($promptInfo as $prompt)
		{
			if ($prompt->isFavorite())
			{
				$result[$prompt->getId()] = $prompt;
			}
		}

		return $result;
	}

	/**
	 * @param Item[] $promptList
	 * @param string $lang
	 * @return Item[]
	 */
	private function prepareSystemItems(array $promptList, string $lang): array
	{
		if (empty($promptList))
		{
			return [];
		}

		$promptIds = array_keys($promptList);

		$forbiddenPromptIds = $this->promptDisplayRuleService->getForbiddenPrompts($promptIds, $lang);
		foreach ($forbiddenPromptIds as $forbiddenPromptId)
		{
			if (isset($promptList[$forbiddenPromptId]))
			{
				unset($promptList[$forbiddenPromptId]);
			}
		}

		$childPrompts = $this->promptService->getChildrenPromptListByIds(
			array_keys($promptList),
			$forbiddenPromptIds,
			$lang
		);

		$this->promptService->fillChildrenForItems($promptList, $childPrompts);

		return $promptList;
	}
}
