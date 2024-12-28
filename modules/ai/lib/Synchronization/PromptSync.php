<?php declare(strict_types=1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Entity\Prompt;
use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Model\EO_Role_Collection;
use Bitrix\AI\Model\PromptTable;
use Bitrix\AI\Model\PromptTranslateNameTable;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\SharePrompt\Service\PromptDisplayRuleService;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\Container;
use Bitrix\AI\Synchronization\Dto\RuleDto;
use Bitrix\AI\Synchronization\Enum\SyncMode;
use Bitrix\AI\Synchronization\Repository\PromptDisplayRuleRepository;
use Bitrix\Main\Entity\AddResult;
use Bitrix\AI\SharePrompt\Repository\PromptRepository;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\SystemException;

class PromptSync extends BaseSync
{
	use TranslateTrait;

	protected const ERROR_PARENT_NOT_FOUND = 1;

	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): PromptTable
	{
		return $this->dataManager ?? ($this->dataManager = new PromptTable());
	}

	/**
	 * return role query manager
	 */
	protected function getRoleQueryBuilder(): Query
	{
		return (new RoleTable())::query();
	}

	/**
	 * @inheritDoc
	 */
	public function sync(array $items, array $filter = [], SyncMode $mode = SyncMode::Partitional): void
	{
		if ($mode !== SyncMode::Partitional)
		{
			$oldIds = $this->getIdsByFilter($filter);
		}

		$currentIds = [];
		$rootSort = 0;
		foreach ($items as $rootCode => $rootAbility)
		{
			$rules = $this->getRules($rootAbility['rules'] ?? []);
			if ($this->hasRuleForHidden($rules))
			{
				continue;
			}

			$rootSort += 100;
			$rootAbility['sort'] = $rootSort;
			$rootAbility['is_system'] = 'Y';
			$result = $this->updatePromptByFields(
				$rootAbility,
				PromptRepository::SYSTEM_USER_ID,
				$rules
			);

			if (!$result->isSuccess())
			{
				$this->log('AI_DB_SYNC_ERROR: ' . implode('; ', $result->getErrorMessages()));
				continue;
			}

			$currentIds[] = $result->getId();

			if (empty($rootAbility['abilities']))
			{
				continue;
			}

			$childSort = 0;
			foreach ($rootAbility['abilities'] as $childAbility)
			{
				$rules = $this->getRules($childAbility['rules'] ?? []);
				if ($this->hasRuleForHidden($rules))
				{
					continue;
				}

				$childSort += 100;
				$childAbility['sort'] = $childSort;
				$childAbility['is_system'] = 'Y';
				$childAbility['parent_code'] = $rootCode;
				$childAbility['settings'] = $rootAbility['settings'] ?? [];
				$childAbility['category'] = $rootAbility['category'] ?? [];
				$result = $this->updatePromptByFields(
					$childAbility,
					PromptRepository::SYSTEM_USER_ID,
					$rules
				);

				if (!$result->isSuccess())
				{
					$this->log('AI_DB_SYNC_ERROR: ' . implode('; ', $result->getErrorMessages()));
					continue;
				}

				$currentIds[] = $result->getId();
			}
		}

		if ($mode === SyncMode::Partitional)
		{
			return;
		}

		$idsForDelete = array_diff($oldIds, $currentIds);
		if (empty($idsForDelete))
		{
			return;
		}

		foreach ($idsForDelete as $id)
		{
			$this->delete((string)$id);
		}
	}

	protected function addOrUpdate(array $fields, ?array $rules = null, int $editorId = 0): AddResult|UpdateResult
	{
		return $this->updatePromptByFields($fields, $editorId, $rules);
	}

	/**
	 * @param array $fields
	 * @param int $editorId
	 * @param ?RuleDto[] $rules
	 * @return AddResult|UpdateResult
	 */
	public function updatePromptByFields(
		array $fields,
		int $editorId = 0,
		?array $rules = null,
		bool $needChangeAuthor = false
	): \Bitrix\Main\ORM\Data\Result
	{
		$fields = array_change_key_case($fields, CASE_UPPER);
		if (is_null($rules))
		{
			$rules = $this->getRules($fields['RULES'] ?? []);
		}

		try
		{
			list($fields, $roles, $categories, $translateNames) = $this->getDataFromFields($fields);
		}
		catch (SystemException $exception)
		{
			$result = new \Bitrix\Main\ORM\Data\UpdateResult();
			$result->addError(
				new Error(
					$exception->getMessage(),
					$exception->getCode() == static::ERROR_PARENT_NOT_FOUND
						? 'PARENT_CODE'
						: $exception->getCode()
				)
			);

			return $result;
		}

		$fields['EDITOR_ID'] = $editorId;
		$fields['DATE_MODIFY'] = (new DateTime())->toUserTime();
		$promptInBD = $this->getPromptService()->getPromptByCodes($fields);
		if (!$promptInBD)
		{
			$fields['AUTHOR_ID'] = $editorId;
			$fields['DATE_CREATE'] = (new DateTime())->toUserTime();
			$result = $this->add($fields);
			if ($result->isSuccess())
			{
				$resultId = $result->getId();
				if (is_numeric($resultId))
				{
					$resultId = (int)$resultId;
					$this->updatePromptRoles($resultId, $roles);

					$this->getPromptService()
						->addCategoriesForPrompt($categories, $resultId)
					;

					$this->getPromptService()
						->addTranslateNames($translateNames, $resultId)
					;

					$this->getPromptDisplayRuleService()
						->updateRulesForPrompt($resultId, $rules)
					;
				}
			}

			return $result;
		}

		if (
			$promptInBD['HASH'] === ($fields['HASH'] ?? null)
			&& $promptInBD['EDITOR_ID'] == $editorId
			&& (empty($fields['SORT']) || $promptInBD['SORT'] == $fields['SORT'])
			&& !$needChangeAuthor
		)
		{
			// fake update
			return $this->getFakeUpdateResult((string)$promptInBD['ID']);
		}

		if ($needChangeAuthor)
		{
			$fields['AUTHOR_ID'] = $editorId;
		}

		$this->getPromptService()
			->addCategoriesForPrompt($categories, (int)$promptInBD['ID'], true)
		;

		$this->getPromptService()
			->addTranslateNames($translateNames, (int)$promptInBD['ID'], true)
		;

		$this->updatePromptRoles((int)$promptInBD['ID'], $roles);

		$this->getPromptDisplayRuleService()
			->updateRulesForPrompt((int)$promptInBD['ID'], $rules, true)
		;

		return $this->update($promptInBD['ID'], $fields);
	}

	protected function getDataFromFields(array $fields): array
	{
		if (array_key_exists('PARENT_CODE', $fields))
		{
			$parent = PromptTable::query()
				->setSelect(['ID'])
				->setFilter(['PARENT_ID' => null, '=CODE' => $fields['PARENT_CODE']])
				->setLimit(1)
				->fetch()
			;
			if (!$parent)
			{
				throw new SystemException('Parent prompt not found.', static::ERROR_PARENT_NOT_FOUND);
			}

			$fields['PARENT_ID'] = $parent['ID'];
		}

		$categories = [];
		if (array_key_exists('CATEGORY', $fields))
		{
			$categories = $fields['CATEGORY'] ?? [];
			if (!is_array($categories))
			{
				$categories = (array)$categories;
			}
			unset($fields['CATEGORY']);
		}

		if (array_key_exists('CACHE_CATEGORY', $fields) && !is_array($fields['CACHE_CATEGORY']))
		{
			$fields['CACHE_CATEGORY'] = (array)$fields['CACHE_CATEGORY'];
		}

		if (array_key_exists('SETTINGS', $fields) && empty($fields['SETTINGS']))
		{
			$fields['SETTINGS'] = [];
		}

		// prepare roles
		$roles = $fields['ROLES'] ?? [];
		unset($fields['ROLES']);
		if (!is_array($roles))
		{
			$roles = [];
		}

		// prepare rules
		if (array_key_exists('RULES', $fields))
		{
			unset($fields['RULES']);
		}

		$translateNames = [];
		$fields['DEFAULT_TITLE'] = '';
		if (array_key_exists('TRANSLATE', $fields))
		{
			if (is_array($fields['TRANSLATE']))
			{
				$translateNames = $fields['TRANSLATE'];
			}

			$defaultCode = '';
			if (!empty($fields['CODE']) && is_string($fields['CODE']))
			{
				$defaultCode = $fields['CODE'];
			}

			$defaultTitle = self::translate(
				$translateNames,
				PromptTranslateNameTable::DEFAULT_LANG,
				$defaultCode
			);

			$fields['DEFAULT_TITLE'] = $defaultTitle;
			unset($fields['TRANSLATE']);
		}

		return [$fields, $roles, $categories, $translateNames];
	}

	protected function getById(int|string $promptId): Prompt
	{
		return $this->getDataManager()::getById($promptId)->fetchObject();
	}

	protected function updatePromptRoles(int|string $promptId, array $roleCodes): void
	{
		$prompt = $this->getById($promptId);
		if (!$prompt)
		{
			return;
		}

		if (!$prompt->isRolesFilled())
		{
			$prompt->fillRoles();
		}

		if (!count($roleCodes))
		{
			$prompt->removeAllRoles();
			$this->savePrompt($prompt);
			return;
		}

		$roles = $this->getRolesByCodes($roleCodes);
		$promptRoles = $prompt->getRoles();
		$roleCodesExists = [];
		// add new prompt roles
		foreach ($roles as $role)
		{
			$roleCodesExists[] = $role->getCode();
			if (!$prompt->getRoles()->has($role))
			{
				$prompt->addToRoles($role);
			}
		}
		// remove old prompt roles
		foreach ($promptRoles as $role)
		{
			if (!in_array($role->getCode(), $roleCodesExists, true))
			{
				$prompt->removeFromRoles($role);
			}
		}

		$this->savePrompt($prompt);
	}

	protected function savePrompt(Prompt $prompt): Result
	{
		return $prompt->save();
	}


	/**
	 * Return role collection by role codes
	 *
	 * @param array $roleCodes
	 *
	 * @return EO_Role_Collection
	 */
	protected function getRolesByCodes(array $roleCodes): EO_Role_Collection
	{
		return $this->getRoleQueryBuilder()
			->setSelect(['ID', 'CODE'])
			->setFilter(['=CODE' => $roleCodes])
			->fetchCollection();
	}

	protected function getPromptService(): PromptService
	{
		return Container::init()->getItem(PromptService::class);
	}

	protected function getPromptDisplayRuleService(): PromptDisplayRuleService
	{
		return Container::init()->getItem(PromptDisplayRuleService::class);
	}

	protected function getDisplayRuleRepository(): PromptDisplayRuleRepository
	{
		return Container::init()->getItem(PromptDisplayRuleRepository::class);
	}
}
