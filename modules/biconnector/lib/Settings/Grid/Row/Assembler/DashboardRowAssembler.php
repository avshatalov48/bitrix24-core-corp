<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler;

use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\DashboardUrlFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\DateFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\Dto;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\NameFieldAssembler;
use Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\UserCardFieldAssembler;
use Bitrix\Main\Grid\Row\RowAssembler;

class DashboardRowAssembler extends RowAssembler
{
	protected const FIELD_PREFIX_CREATOR = 'BICONNECTOR_DASHBOARD_CREATED_USER_';
	protected const FIELD_PREFIX_WATCHER = 'BICONNECTOR_DASHBOARD_LAST_VIEW_USER_';

	protected function prepareFieldAssemblers(): array
	{
		return [
			new NameFieldAssembler([
				'NAME',
			]),
			new DashboardUrlFieldAssembler([
				'URL',
			]),
			new UserCardFieldAssembler(
				new Dto\UserCard(
					self::FIELD_PREFIX_CREATOR . 'NAME',
					self::FIELD_PREFIX_CREATOR . 'LAST_NAME',
					self::FIELD_PREFIX_CREATOR . 'SECOND_NAME',
					self::FIELD_PREFIX_CREATOR . 'EMAIL',
					self::FIELD_PREFIX_CREATOR . 'LOGIN',
					self::FIELD_PREFIX_CREATOR . 'PERSONAL_PHOTO',
				),
				['CREATED_BY']
			),
			new UserCardFieldAssembler(
				new Dto\UserCard(
					self::FIELD_PREFIX_WATCHER . 'NAME',
					self::FIELD_PREFIX_WATCHER . 'LAST_NAME',
					self::FIELD_PREFIX_WATCHER . 'SECOND_NAME',
					self::FIELD_PREFIX_WATCHER . 'EMAIL',
					self::FIELD_PREFIX_WATCHER . 'LOGIN',
					self::FIELD_PREFIX_WATCHER . 'PERSONAL_PHOTO',
				),
				['LAST_VIEW_BY']
			),
			new DateFieldAssembler([
				'DATE_LAST_VIEW',
				'DATE_CREATE',
				'TIMESTAMP_X',
			]),
		];
	}
}
