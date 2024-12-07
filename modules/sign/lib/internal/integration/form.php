<?php
namespace Bitrix\Sign\Internal\Integration;

use Bitrix\Main\Entity;

/**
 * Class FormTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Form_Query query()
 * @method static EO_Form_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Form_Result getById($id)
 * @method static EO_Form_Result getList(array $parameters = [])
 * @method static EO_Form_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Integration\EO_Form createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\Integration\EO_Form_Collection createCollection()
 * @method static \Bitrix\Sign\Internal\Integration\EO_Form wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\Integration\EO_Form_Collection wakeUpCollection($rows)
 */
class FormTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_integration_form';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			]),
			'BLANK_ID' => new Entity\IntegerField('BLANK_ID', [
				'title' => 'Blank ID',
				'required' => true
			]),
			'PART' => new Entity\IntegerField('PART', [
				'title' => 'Member part index',
				'required' => true
			]),
			'FORM_ID' => new Entity\IntegerField('FORM_ID', [
				'title' => 'Form ID',
				'required' => true
			]),
			'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID', [
				'title' => 'Created by user ID',
				'required' => true
			]),
			'MODIFIED_BY_ID' => new Entity\IntegerField('MODIFIED_BY_ID', [
				'title' => 'Modified by user ID',
				'required' => true
			]),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', [
				'title' => 'Created on',
				'required' => true
			]),
			'DATE_MODIFY' => new Entity\DatetimeField('DATE_MODIFY', [
				'title' => 'Modified on',
				'required' => true
			])
		];
	}

	/**
	 * Before delete handler.
	 * @param Entity\Event $event Event instance.
	 * @return Entity\EventResult
	 */
	public static function onBeforeDelete(Entity\Event $event): Entity\EventResult
	{
		$result = new Entity\EventResult();
		$primary = $event->getParameter('primary');

		if ($primary['ID'] ?? null)
		{
			// remove form from crm
			$res = self::getList([
				'select' => [
					 'FORM_ID'
				],
				'filter' => [
					 'ID' => $primary['ID']
				],
				'limit' => 1
			]);
			if ($row = $res->fetch())
			{
				\Bitrix\Sign\Integration\CRM\Form::remove($row['FORM_ID']);
			}
		}

		return $result;
	}
}
