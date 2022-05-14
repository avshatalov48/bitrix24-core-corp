<?php

namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields;

/**
 * Class TypeParticipantsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TypeParticipants_Query query()
 * @method static EO_TypeParticipants_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TypeParticipants_Result getById($id)
 * @method static EO_TypeParticipants_Result getList(array $parameters = [])
 * @method static EO_TypeParticipants_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_TypeParticipants_Collection wakeUpCollection($rows)
 */
class TypeParticipantsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_type_participants';
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$typeId = new Fields\IntegerField('TYPE_ID');

		$code = new Fields\StringField('CODE');
		$code->addValidator(new Fields\Validators\LengthValidator(1, 24));

		$type = new Fields\Relations\Reference(
			'TYPE',
			TypeTable::class,
			Join::on('this.TYPE_ID', 'ref.ID')
		);

		return [
			$id,
			$typeId,
			$code,
			$type,
		];
	}
}