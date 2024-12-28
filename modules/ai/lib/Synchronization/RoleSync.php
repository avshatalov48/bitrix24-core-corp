<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Container;
use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Role\RoleManager;
use Bitrix\AI\Model\RoleTranslateDescriptionTable;
use Bitrix\AI\Model\RoleTranslateNameTable;
use Bitrix\AI\ShareRole\Service\RoleDisplayRuleService;
use Bitrix\AI\ShareRole\Service\RoleService;
use Bitrix\AI\Synchronization\Dto\RuleDto;
use Bitrix\AI\Synchronization\Repository\RoleDisplayRuleRepository;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Type\DateTime;

class RoleSync extends BaseSync
{
	use TranslateTrait;

	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): RoleTable
	{
		return $this->dataManager ?? ($this->dataManager = new RoleTable());
	}

	protected function getDisplayRuleRepository(): RoleDisplayRuleRepository
	{
		return Container::init()->getItem(RoleDisplayRuleRepository::class);
	}

	protected function hasRuleForHidden(array $rules, array $item = []): bool
	{
		if (!empty($item['code']) && $item['code'] === RoleManager::getUniversalRoleCode())
		{
			return false;
		}

		return parent::hasRuleForHidden($rules);
	}

	protected function addOrUpdate(array $fields, ?array $rules = null, int $editorId = 0): AddResult|UpdateResult
	{
		return $this->updateRoleByFields($fields, $editorId, $rules);
	}

	/**
	 * @param array $fields
	 * @param int $editorId
	 * @param ?RuleDto[] $rules
	 * @return AddResult|UpdateResult
	 */
	public function updateRoleByFields(
		array  $fields,
		int    $editorId = 0,
		?array $rules = null
	): AddResult|UpdateResult
	{
		$fields = array_change_key_case($fields, CASE_UPPER);
		if (is_null($rules))
		{
			$rules = $this->getRules($fields['RULES'] ?? []);
		}

		[$fields, $translateNames, $translateDescriptions] = $this->getDataFromFields($fields);

		$fields['EDITOR_ID'] = $editorId;
		$fields['DATE_MODIFY'] = (new DateTime())->toUserTime();
		$roleService = $this->getRoleService();
		$roleDisplayRuleService = $this->getRoleDisplayRuleService();

		$roleInDB = $roleService->getRoleByCode($fields);
		if (!$roleInDB)
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
					$roleService->addCreationActions($resultId);
					$roleService->addTranslateNames($resultId, $translateNames);
					$roleService->addTranslateDescriptions($resultId, $translateDescriptions);
					$roleDisplayRuleService->updateRulesForRole($resultId, $rules);
				}
			}

			return $result;
		}

		if (
			$roleInDB['HASH'] === ($fields['HASH'] ?? null)
			&& $roleInDB['EDITOR_ID'] === $editorId
			&& (empty($fields['SORT']) || $roleInDB['SORT'] === $fields['SORT'])
		)
		{
			// fake update
			return $this->getFakeUpdateResult((string)$roleInDB['ID']);
		}

		$roleService->addTranslateNames((int)$roleInDB['ID'], $translateNames, true);
		$roleService->addTranslateDescriptions((int)$roleInDB['ID'], $translateDescriptions, true);
		$roleDisplayRuleService->updateRulesForRole((int)$roleInDB['ID'], $rules, true);
		unset($fields['DEFAULT_NAME'], $fields['DEFAULT_DESCRIPTION']);

		return $this->update($roleInDB['ID'], $fields);
	}

	protected function getDataFromFields(array $fields): array
	{
		// prepare rules
		if (array_key_exists('RULES', $fields))
		{
			unset($fields['RULES']);
		}

		[$fields, $translateNames] = $this->processTranslations(
			$fields,
			'NAME_TRANSLATES',
			'DEFAULT_NAME',
			RoleTranslateNameTable::DEFAULT_LANG
		);

		[$fields, $translateDescriptions] = $this->processTranslations(
			$fields,
			'DESCRIPTION_TRANSLATES',
			'DEFAULT_DESCRIPTION',
			RoleTranslateDescriptionTable::DEFAULT_LANG
		);

		return [$fields, $translateNames, $translateDescriptions];
	}

	private function processTranslations(array $fields, string $translateKey, string $defaultKey, string $defaultLang): array
	{
		$translateArray = [];
		$fields[$defaultKey] = '';

		if (array_key_exists($translateKey, $fields))
		{
			if (is_array($fields[$translateKey]))
			{
				$translateArray = $fields[$translateKey];
			}

			$defaultCode = $fields['CODE'];
			$fields[$defaultKey] = self::translate($translateArray, $defaultLang, $defaultCode);

			unset($fields[$translateKey]);
		}

		return [$fields, $translateArray];
	}

	protected function getRoleService(): RoleService
	{
		return Container::init()->getItem(RoleService::class);
	}

	protected function getRoleDisplayRuleService()
	{
		return Container::init()->getItem(RoleDisplayRuleService::class);
	}
}
