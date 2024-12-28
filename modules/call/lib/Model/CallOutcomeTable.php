<?php

namespace Bitrix\Call\Model;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Im\Model\CallTable;
use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\Outcome\OutcomeCollection;

\Bitrix\Main\Loader::includeModule('im');

/**
 * Class CallOutcomeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallOutcome_Query query()
 * @method static EO_CallOutcome_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallOutcome_Result getById($id)
 * @method static EO_CallOutcome_Result getList(array $parameters = [])
 * @method static EO_CallOutcome_Entity getEntity()
 * @method static \Bitrix\Call\Integration\AI\Outcome createObject($setDefaultValues = true)
 * @method static \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection createCollection()
 * @method static \Bitrix\Call\Integration\AI\Outcome wakeUpObject($row)
 * @method static \Bitrix\Call\Integration\AI\Outcome\OutcomeCollection wakeUpCollection($rows)
 */
class CallOutcomeTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_call_outcome';
	}

	public static function getObjectClass(): string
	{
		return Outcome::class;
	}

	public static function getCollectionClass(): string
	{
		return OutcomeCollection::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('CALL_ID'))
				->configureRequired(),

			(new IntegerField('TRACK_ID'))
				->configureNullable(),

			(new EnumField('TYPE'))
				->configureValues([
					SenseType::TRANSCRIBE->value,
					SenseType::SUMMARY->value,
					SenseType::OVERVIEW->value,
					SenseType::INSIGHTS->value,
				])
				->configureNullable(),

			(new DatetimeField('DATE_CREATE'))
				->configureDefaultValue(function (){return new DateTime;}),

			(new StringField('LANGUAGE_ID'))
				->configureSize(5),

			(new TextField('CONTENT'))
				->configureLong()
				->configureNullable(),

			(new Reference('TRACK', CallTrackTable::class, Join::on('this.TRACK_ID', 'ref.ID'))),
			(new Reference('CALL', CallTable::class, Join::on('this.CALL_ID', 'ref.ID'))),
		];
	}
}


