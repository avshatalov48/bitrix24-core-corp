<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\UserField\Internal\TemporaryStorage;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\UserPermissions;
use Bitrix\Rpa\Integration\Bizproc\Automation;

class StageTable extends ORM\Data\DataManager
{
	protected static $temporaryStorage;

	public static function getTableName(): string
	{
		return 'b_rpa_stage';
	}

	public static function getMap(): array
	{
		Base::loadBaseLanguageMessages();

		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\StringField('NAME'))
				->configureRequired()
				->configureTitle(Loc::getMessage('RPA_COMMON_TITLE')),
			(new ORM\Fields\StringField('CODE')),
			(new ORM\Fields\StringField('COLOR'))
				->configureSize(6)
				->configureTitle(Loc::getMessage('RPA_COMMON_COLOR')),
			(new ORM\Fields\IntegerField('SORT'))
				->configureDefaultValue(500),
			(new ORM\Fields\EnumField('SEMANTIC'))
				->configureValues(static::getFinalSemantics()),
			(new ORM\Fields\IntegerField('TYPE_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('RPA_COMMON_PROCESS')),
			new ORM\Fields\Relations\Reference(
				'TYPE',
				TypeTable::class,
				['=this.TYPE_ID' => 'ref.ID'],
				['join_type' => 'INNER']
			),
		];
	}

	public static function getObjectClass(): string
	{
		return Stage::class;
	}

	public static function onBeforeAdd(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();

		$fields = $event->getParameter('fields');
		if(isset($fields['SEMANTIC']) && $fields['SEMANTIC'] === Stage::SEMANTIC_SUCCESS)
		{
			$typeId = (int)$fields['TYPE_ID'];
			if($typeId > 0)
			{
				$type = Driver::getInstance()->getType($typeId);
				if($type)
				{
					$successStage = $type->getSuccessStage();
					if($successStage)
					{
						$result->addError(new ORM\EntityError(Loc::getMessage('RPA_STAGE_TABLE_ADD_SEMANTIC_SUCCESS')));
					}
				}
			}
		}

		return $result;
	}

	// do not let change typeId
	public static function onBeforeUpdate(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();
		$id = $event->getParameter('id');
		$fields = $event->getParameter('fields');
		if(isset($fields['TYPE_ID']))
		{
			$data = static::getById($id)->fetch();
			if((int) $data['TYPE_ID'] !== (int) $fields['TYPE_ID'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('RPA_STAGE_TABLE_UPDATE_TYPE_ID')));
			}
		}
		if(isset($fields['SEMANTIC']) && $fields['SEMANTIC'] === Stage::SEMANTIC_SUCCESS)
		{
			$data = static::getById($id)->fetch();
			if($data['SEMANTIC'] !== $fields['SEMANTIC'])
			{
				$result->addError(new ORM\EntityError(Loc::getMessage('RPA_STAGE_TABLE_UPDATE_SEMANTIC_SUCCESS')));
			}
		}

		return $result;
	}

	public static function onBeforeDelete(Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();

		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;
		$stage = static::getById($id)->fetchObject();

		if (!$stage)
		{
			return $result;
		}

		static::getTemporaryStorage()->saveData($id, $stage->collectValues());
		$type = Driver::getInstance()->getType($stage->getTypeId());
		if(!$type)
		{
			return $result;
		}
		$itemsCount = $type->getItemsCount([
			'=STAGE_ID' => $stage->getId(),
		]);

		if($itemsCount > 0)
		{
			$result->addError(new ORM\EntityError(Loc::getMessage('RPA_STAGE_TABLE_DELETE_ERROR_ITEMS')));
		}
		if ($stage->isSuccess() && $itemsCount > 0)
		{
			$result->addError(new ORM\EntityError(Loc::getMessage('RPA_STAGE_TABLE_DELETE_ERROR_SUCCESS')));
		}

		return $result;
	}

	public static function onAfterDelete(Event $event): ORM\EventResult
	{
		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;

		StageToStageTable::deleteByStageId($id);
		FieldTable::deleteByStageId($id);
		PermissionTable::deleteByEntity(UserPermissions::ENTITY_STAGE, $id);
		$data = static::getTemporaryStorage()->getData($id);
		if($data && isset($data['TYPE_ID']) && $data['TYPE_ID'] > 0)
		{
			Automation\Factory::onAfterStageDelete((int) $data['TYPE_ID'], $id);
		}

		return new ORM\EventResult();
	}

	public static function getFinalSemantics(): array
	{
		return [
			Stage::SEMANTIC_SUCCESS,
			Stage::SEMANTIC_FAIL,
		];
	}

	protected static function getTemporaryStorage(): TemporaryStorage
	{
		if(!static::$temporaryStorage)
		{
			static::$temporaryStorage = new TemporaryStorage();
		}

		return static::$temporaryStorage;
	}
}