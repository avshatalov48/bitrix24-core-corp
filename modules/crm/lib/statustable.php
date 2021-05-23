<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\Attribute\Entity\FieldAttributeTable;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\Result;
use Bitrix\Main\UserField\Internal\TemporaryStorage;

Loc::loadMessages(__FILE__);

class StatusTable extends Entity\DataManager
{
	protected static $temporaryStorage;

	public static function getTableName(): string
	{
		return 'b_crm_status';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\StringField('ENTITY_ID'))
				->configureRequired()
				->configureSize(50),
			(new ORM\Fields\StringField('STATUS_ID'))
				->configureRequired()
				->configureSize(50),
			(new ORM\Fields\StringField('NAME'))
				->configureRequired()
				->configureSize(100),
			(new ORM\Fields\StringField('NAME_INIT'))
				->configureSize(100),
			(new ORM\Fields\IntegerField('SORT'))
				->configureRequired(),
			(new ORM\Fields\BooleanField('SYSTEM'))
				->configureRequired()
				->configureValues('N', 'Y')
				->configureDefaultValue('N'),
			(new ORM\Fields\StringField('COLOR'))
				->configureSize(10),
			(new ORM\Fields\EnumField('SEMANTICS'))
				->configureValues([
					PhaseSemantics::PROCESS,
					PhaseSemantics::FAILURE,
					PhaseSemantics::SUCCESS,
				]),
			(new ORM\Fields\IntegerField('CATEGORY_ID')),
		];
	}

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();
		$fields = $event->getParameter('fields');

		if(isset($fields['SEMANTICS']) && $fields['SEMANTICS'] === PhaseSemantics::SUCCESS)
		{
			$existingSuccessStatus = static::getList([
				'select' => ['ID'],
				'filter' => [
					'=ENTITY_ID' => $fields['ENTITY_ID'],
					'=SEMANTICS' => PhaseSemantics::SUCCESS,
				],
				'limit' => 1,
			])->fetch();
			if($existingSuccessStatus)
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_MORE_THAN_ONE_SUCCESS_ERROR')));
			}
		}

		return $result;
	}

	public static function onBeforeUpdate(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();
		$id = $event->getParameter('id');
		$fields = $event->getParameter('fields');
		$data = null;
		if(isset($fields['ENTITY_ID']))
		{
			$data = static::getById($id)->fetch();
			if((int) $data['ENTITY_ID'] !== (int) $fields['ENTITY_ID'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_FIELD_UPDATE_ERROR', [
					'#STATUS_FIELD#' => 'ENTITY_ID',
				])));
			}
		}
		if(isset($fields['STATUS_ID']))
		{
			if(!$data)
			{
				$data = static::getById($id)->fetch();
			}
			if($data['STATUS_ID'] !== $fields['STATUS_ID'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_FIELD_UPDATE_ERROR', [
					'#STATUS_FIELD#' => 'STATUS_ID',
				])));
			}
		}
		if(isset($fields['SEMANTICS']) && $fields['SEMANTICS'] === PhaseSemantics::SUCCESS)
		{
			if(!$data)
			{
				$data = static::getById($id)->fetch();
			}
			if($data['SEMANTICS'] !== $fields['SEMANTICS'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('CRM_STATUS_SUCCESS_SEMANTIC_UPDATE_ERROR')));
			}
		}

		return $result;
	}

	public static function onAfterUpdate(Event $event): EventResult
	{
		$result = new EventResult();

		$id = $event->getParameter('id');
		$data = static::getById($id)->fetch();

		$entityId = $data['ENTITY_ID'];
		$entity = \CCrmStatus::GetEntityTypes()[$entityId] ?? null;
		if (!$entity)
		{
			return $result;
		}

		\CCrmStatus::ClearCachedStatuses($entityId);

		$entityTypeId = $entity['ENTITY_TYPE_ID'] ?? null;
		if (!$entityTypeId)
		{
			return $result;
		}
		$entityScope = $entity['FIELD_ATTRIBUTE_SCOPE'] ?? '';

		FieldAttributeManager::processPhaseModification(
			$data['STATUS_ID'],
			$entityTypeId,
			$entityScope,
			\CCrmStatus::GetStatus('STATUS')
		);

		return $result;
	}

	public static function deleteByEntityId(string $entityId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_ID' => $entityId,
			],
		]);
		while($item = $list->fetch())
		{
			$deleteResult = static::delete($item['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	protected static function getTemporaryStorage(): TemporaryStorage
	{
		if(!static::$temporaryStorage)
		{
			static::$temporaryStorage = new TemporaryStorage();
		}

		return static::$temporaryStorage;
	}

	public static function onBeforeDelete(Event $event): EventResult
	{
		$data = static::getByPrimary($event->getParameter('id'))->fetch();
		static::getTemporaryStorage()->saveData($event->getParameter('id'), $data);

		return new EventResult();
	}

	public static function onAfterDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$data = static::getTemporaryStorage()->getData($event->getParameter('id'));

		$entityId = $data['ENTITY_ID'];
		$entity = \CCrmStatus::GetEntityTypes()[$entityId] ?? null;
		if (!$entity)
		{
			return $result;
		}
		$entityTypeId = $entity['ENTITY_TYPE_ID'] ?? null;
		if (!$entityTypeId)
		{
			return $result;
		}
		$entityScope = $entity['FIELD_ATTRIBUTE_SCOPE'] ?? '';

		FieldAttributeTable::deleteByPhase($data['STATUS_ID'], $entityTypeId, $entityScope);
		\CCrmStatus::ClearCachedStatuses($data['ENTITY_ID']);

		return $result;
	}
}
