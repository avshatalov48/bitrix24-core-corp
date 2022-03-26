<?php

namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields;

class EpicTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_epic';
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$groupId = new Fields\IntegerField('GROUP_ID');

		$name = new Fields\StringField('NAME');
		$name->addValidator(new Fields\Validators\LengthValidator(1, 255));

		$description = new Fields\TextField('DESCRIPTION');

		$createdBy = new Fields\IntegerField('CREATED_BY');

		$modifiedBy = new Fields\IntegerField('MODIFIED_BY');

		$color = new Fields\StringField('COLOR');
		$color->addValidator(new Fields\Validators\LengthValidator(0, 18));

		return [
			$id,
			$groupId,
			$name,
			$description,
			$createdBy,
			$modifiedBy,
			$color,
		];
	}
}