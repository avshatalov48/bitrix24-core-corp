<?php

namespace Bitrix\Crm\Agent\Recyclebin;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Web\Json;

class SpdUfCleanerAgent extends AgentBase
{
	use Singleton;

	private const OPTION_MODULE = 'crm';
	private const OPTION_ENTITY_TYPES = 'crm_spd_uf_entity_types';
	private const OPTION_SPD_FIELDS = 'crm_spd_uf_fields';
	private const OPTION_DEFAULT_TIME_LIMIT = 10;

	public static function doRun(): bool
	{
		return self::getInstance()->process();
	}

	public function process(): bool
	{
		$startedTime = time();
		$entityTypesToProcess = $this->getEntityTypesToProcess();
		if (empty($entityTypesToProcess))
		{
			$this->onFinishProcess();

			return false;
		}

		[$suspendedEntityTypeId, $entityTypeId] = $this->getNextEntityToProcess($entityTypesToProcess);

		$duplicatedSpdFields = $this->getDuplicatedSpdFields($entityTypeId, $suspendedEntityTypeId);
		foreach ($duplicatedSpdFields as $duplicatedSpdFieldId => $duplicatedSpdFieldName)
		{
			if (time() - $startedTime > $this->getTimeLimit())
			{
				break;
			}

			$this->deleteField($suspendedEntityTypeId, $duplicatedSpdFieldId, $duplicatedSpdFieldName);
			$this->removeFieldFromUnprocessed($duplicatedSpdFields, $duplicatedSpdFieldId);
			$this->saveFieldDeletionProgress($suspendedEntityTypeId, $duplicatedSpdFields);

		}

		if (empty($duplicatedSpdFields))
		{
			$this->onFinishFieldDeletion($suspendedEntityTypeId);

			unset($entityTypesToProcess[$entityTypeId]);
			$this->saveEntityTypesToProcess($entityTypesToProcess);
		}

		return true;
	}

	private function getEntityTypesToProcess(): array
	{
		$savedEntityTypeIds = Option::get($this::OPTION_MODULE, $this::OPTION_ENTITY_TYPES, null);
		if (is_null($savedEntityTypeIds))
		{
			$savedEntityTypeIds = $this->prepareEntityTypesToProcess();
			$this->saveEntityTypesToProcess($savedEntityTypeIds);
		} else
		{
			try
			{
				$savedEntityTypeIds = Json::decode($savedEntityTypeIds);
			} catch (ArgumentException)
			{
				$savedEntityTypeIds = [];
			}
		}

		return $savedEntityTypeIds;
	}

	private function prepareEntityTypesToProcess(): array
	{
		$allEntityTypeIds = [
			\CCrmOwnerType::Lead => \CCrmOwnerType::SuspendedLead,
			\CCrmOwnerType::Deal => \CCrmOwnerType::SuspendedDeal,
			\CCrmOwnerType::Contact => \CCrmOwnerType::SuspendedContact,
			\CCrmOwnerType::Company => \CCrmOwnerType::SuspendedCompany,
			\CCrmOwnerType::SmartInvoice => \CCrmOwnerType::SuspendedSmartInvoice,
		];

		$dynamicEntityTypes = TypeTable::query()
			->setSelect(['ENTITY_TYPE_ID'])
			->whereNotIn('ENTITY_TYPE_ID', \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds())
			->exec();
		while ($dynamicEntityType = $dynamicEntityTypes->fetch())
		{
			$dynamicEntityTypeId = $dynamicEntityType['ENTITY_TYPE_ID'];
			$allEntityTypeIds[$dynamicEntityTypeId] = \CCrmOwnerType::getSuspendedDynamicTypeId($dynamicEntityTypeId);
		}

		$result = [];
		foreach ($allEntityTypeIds as $entityTypeId => $suspendedEntityTypeId)
		{
			$suspendedUfEntity = \CCrmOwnerType::ResolveUserFieldEntityID($suspendedEntityTypeId);
			if (Application::getConnection()->query(new SqlExpression('SELECT ID FROM b_user_field WHERE ENTITY_ID=?s LIMIT 1', $suspendedUfEntity))->fetch())
			{
				$result[$entityTypeId] = $suspendedEntityTypeId;
			}
		}

		return $result;
	}

	private function getDuplicatedSpdFields(int $entityTypeId, int $suspendedEntityTypeId): array
	{
		$fields = Option::get($this::OPTION_MODULE, $this->getFieldOptionName($suspendedEntityTypeId), null);
		if (is_null($fields))
		{
			$fields = $this->prepareDuplicatedSpdFields($entityTypeId, $suspendedEntityTypeId);
			$this->saveFieldDeletionProgress($suspendedEntityTypeId, $fields);
		} else
		{
			try
			{
				$fields = Json::decode($fields);
			} catch (ArgumentException)
			{
				$fields = [];
			}
		}


		return $fields;
	}

	private function prepareDuplicatedSpdFields(int $entityTypeId, int $suspendedEntityTypeId): array
	{
		$connection = Application::getConnection();
		$suspendedUfEntity = \CCrmOwnerType::ResolveUserFieldEntityID($suspendedEntityTypeId);
		if (!$suspendedUfEntity) // smart process was deleted during agent execution
		{
			return [];
		}

		$ufLang = $this->getDefaultSiteLangId();

		$existedFieldsIntersection = \Bitrix\Crm\Synchronization\UserFieldSynchronizer::getIntersection($entityTypeId, $suspendedEntityTypeId, $ufLang);
		$fieldsUsedInRecycleBinSync = [];
		foreach ($existedFieldsIntersection as $intersection)
		{
			$fieldsUsedInRecycleBinSync[$intersection['DST_FIELD_NAME']] = $intersection;
		}

		$userFields = $connection->query(new SqlExpression("
			SELECT f.FIELD_NAME, f.ID, fl.EDIT_FORM_LABEL, fl.LIST_COLUMN_LABEL
				FROM b_user_field f
				LEFT JOIN b_user_field_lang fl ON (fl.USER_FIELD_ID=f.ID and fl.LANGUAGE_ID=?s)
			WHERE ENTITY_ID=?s",
			$ufLang,
			$suspendedUfEntity
		));

		$fieldsByLabel = [];
		while ($field = $userFields->fetch())
		{
			$fieldLabel = (string)($field['EDIT_FORM_LABEL'] ?: $field['LIST_COLUMN_LABEL']);
			if ($fieldLabel === '')
			{
				continue;
			}

			if (!isset($fieldsByLabel[$fieldLabel]))
			{
				$fieldsByLabel[$fieldLabel] = [];
			}
			$fieldsByLabel[$fieldLabel][$field['FIELD_NAME']] = $field;
		}

		$candidatesToDelete = [];
		foreach ($fieldsByLabel as $label => $fields)
		{
			if (count($fields) <= 1) // have not any duplicates
			{
				continue;
			}
			foreach ($fields as $fieldName => $field)
			{
				if (!isset($fieldsUsedInRecycleBinSync[$fieldName])) // if this uf not linked to base entity uf
				{
					$candidatesToDelete[$field['ID']] = $fieldName;
				}
			}
		}
		if (!empty($candidatesToDelete))
		{
			$this->log('Found ' . count($candidatesToDelete) . ' duplicated user fields for ' . \CCrmOwnerType::ResolveName($suspendedEntityTypeId));
		}

		return $candidatesToDelete;
	}

	private function getNextEntityToProcess(array $entityTypesToProcess): array
	{
		$entityTypeId = array_key_first($entityTypesToProcess);
		$suspendedEntityTypeId = $entityTypesToProcess[$entityTypeId];

		return [$suspendedEntityTypeId, $entityTypeId];
	}


	private function deleteField(int $suspendedEntityTypeId, int $fieldId, string $fieldName): void
	{
		$hasValues = $this->doesFieldHaveValues($suspendedEntityTypeId, $fieldName);

		if (!$hasValues)
		{
			$userTypeEntity = new \CUserTypeEntity();
			$userTypeEntity->Delete($fieldId);

			$this->log("Deleted UF $fieldName ($fieldId) from " . \CCrmOwnerType::ResolveName($suspendedEntityTypeId));
		} else
		{
			$this->log("UF $fieldName ($fieldId) has values and can not be deleted from " . \CCrmOwnerType::ResolveName($suspendedEntityTypeId));
		}
	}

	private function removeFieldFromUnprocessed(array &$duplicatedSpdFields, string $fieldId): void
	{
		unset($duplicatedSpdFields[$fieldId]);
	}

	private function saveEntityTypesToProcess(array $savedEntityTypeIds): void
	{
		Option::set($this::OPTION_MODULE, $this::OPTION_ENTITY_TYPES, Json::encode($savedEntityTypeIds));
	}

	private function saveFieldDeletionProgress(mixed $suspendedEntityTypeId, array $fields): void
	{
		Option::set($this::OPTION_MODULE, $this->getFieldOptionName($suspendedEntityTypeId), Json::encode($fields));
	}

	private function onFinishFieldDeletion(mixed $suspendedEntityTypeId): void
	{
		Option::delete($this::OPTION_MODULE, ['name' => $this->getFieldOptionName($suspendedEntityTypeId)]);
	}

	private function onFinishProcess(): void
	{
		Option::delete($this::OPTION_MODULE, ['name' => $this::OPTION_ENTITY_TYPES]);
	}

	private function getFieldOptionName(mixed $suspendedEntityTypeId): string
	{
		return $this::OPTION_SPD_FIELDS . '_' . $suspendedEntityTypeId;
	}

	public function doesFieldHaveValues(int $suspendedEntityTypeId, string $fieldName): bool
	{
		$connection = Application::getConnection();
		$suspendedUfEntity = \CCrmOwnerType::ResolveUserFieldEntityID($suspendedEntityTypeId);
		$suspendedUfTableName = 'b_uts_' . strtolower($suspendedUfEntity);

		try
		{
			$hasValues = (bool)$connection->query(new SqlExpression(
				"SELECT 1 FROM ?# WHERE ?# IS NOT NULL LIMIT 1",
				$suspendedUfTableName,
				$fieldName,
			))->fetch();
		}
		catch (\Bitrix\Main\DB\SqlQueryException $e)
		{
			if (mb_strpos($e->getMessage(), 'Unknown column') !== false)
			{
				$hasValues = false;
			}
			else
			{
				throw $e;
			}
		}

		return $hasValues;
	}

	private function log($message): void
	{
		$logHost = Application::getInstance()->getContext()->getServer()->getHttpHost();
		AddMessage2Log("crm.SpdUfCleanerAgent {$logHost} {$message}", 'crm', 0);
	}

	public function getTimeLimit(): int
	{
		return (int)Option::get($this::OPTION_MODULE, 'SpdUfCleanerAgentTimeLimit', self::OPTION_DEFAULT_TIME_LIMIT);
	}

	private function getDefaultSiteLangId(): string
	{
		/** @todo Use SiteTable::getDefaultLanguageId() */
		$iterator = \Bitrix\Main\SiteTable::getList([
			'select' => ['LID', 'LANGUAGE_ID'],
			'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
			'cache' => ['ttl' => 86400],
		]);
		if ($defaultSite = $iterator->fetch())
		{
			return $defaultSite['LANGUAGE_ID'];
		}

		return LANGUAGE_ID;
	}
}
