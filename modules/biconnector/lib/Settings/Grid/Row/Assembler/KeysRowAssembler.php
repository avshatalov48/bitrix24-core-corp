<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler;

use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\ActiveFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\DateFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\KeyFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\NameFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\UserCardFieldAssembler;
use Bitrix\Main\Grid\Row\RowAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\Dto;

class KeysRowAssembler extends RowAssembler
{
	protected const FIELD_PREFIX = 'BICONNECTOR_KEY_CREATED_USER_';

	protected function prepareFieldAssemblers(): array
	{
		return [
			new ActiveFieldAssembler([
				'ACTIVE',
			]),
			new KeyFieldAssembler([
				'ACCESS_KEY',
			]),
			new NameFieldAssembler([
				'CONNECTION',
				'BICONNECTOR_KEY_APPLICATION_APP_NAME',
			]),
			new UserCardFieldAssembler(
				new Dto\UserCard(
					self::FIELD_PREFIX . 'NAME',
					self::FIELD_PREFIX . 'LAST_NAME',
					self::FIELD_PREFIX . 'SECOND_NAME',
					self::FIELD_PREFIX . 'EMAIL',
					self::FIELD_PREFIX . 'LOGIN',
					self::FIELD_PREFIX . 'PERSONAL_PHOTO',
				),
				['CREATED_BY']
			),
			new DateFieldAssembler([
				'DATE_CREATE',
				'LAST_ACTIVITY_DATE',
				'TIMESTAMP_X',
			]),
		];
	}
}
