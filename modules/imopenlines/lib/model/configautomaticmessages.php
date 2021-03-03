<?php
namespace Bitrix\Imopenlines\Model;

use Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class ConfigAutomaticMessagesTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_config_automatic_messages';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new IntegerField(
				'CONFIG_ID',
				[
					'required' => true,
				]
			),
			new IntegerField(
				'TIME_TASK',
				[
					'required' => true,
				]
			),
			new TextField('MESSAGE'),
			new StringField(
				'TEXT_BUTTON_CLOSE',
				[
					'validation' => [__CLASS__, 'validateTextButton']
				]
			),
			new StringField(
				'LONG_TEXT_BUTTON_CLOSE',
				[
					'validation' => [__CLASS__, 'validateLongTextButton']
				]
			),
			new TextField('AUTOMATIC_TEXT_CLOSE'),
			new StringField(
				'TEXT_BUTTON_CONTINUE',
				[
					'validation' => [__CLASS__, 'validateTextButton']
				]
			),
			new StringField(
				'LONG_TEXT_BUTTON_CONTINUE',
				[
					'validation' => [__CLASS__, 'validateLongTextButton']
				]
			),
			new TextField('AUTOMATIC_TEXT_CONTINUE'),
			new StringField(
				'TEXT_BUTTON_NEW',
				[
					'validation' => [__CLASS__, 'validateTextButton']
				]
			),
			new StringField(
				'LONG_TEXT_BUTTON_NEW',
				[
					'validation' => [__CLASS__, 'validateLongTextButton']
				]
			),
			new TextField('AUTOMATIC_TEXT_NEW'),
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function validateTextButton()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function validateLongTextButton()
	{
		return [
			new LengthValidator(null, 255),
		];
	}
}