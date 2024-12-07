<?php

namespace Bitrix\Intranet\User\Grid\Column\Provider;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Column\DataProvider;
use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Localization\Loc;

/**
 * @method UserSettings getSettings()
 */
class UserDataProvider extends DataProvider
{
	private const EXCLUDE_UF_FIELDS = [
		'EMAIL',
		'UF_DEPARTMENT',
		'PERSONAL_MOBILE',
		'LAST_ACTIVITY_DATE',
		'FULL_NAME',
	];

	private function getDefaultFields(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_ID'),
				'sort' => 'ID',
			],
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_NAME'),
				'sort' => 'NAME',
			],
			[
				'id' => 'LAST_NAME',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_LAST_NAME'),
				'sort' => 'LAST_NAME',
			],
			[
				'id' => 'SECOND_NAME',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_SECOND_NAME'),
				'sort' => 'SECOND_NAME',
			],
			[
				'id' => 'LOGIN',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_LOGIN'),
				'sort' => 'LOGIN',
			],
			[
				'id' => 'DATE_REGISTER',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_DATE_REGISTER'),
				'sort' => 'DATE_REGISTER',
			],
			[
				'id' => 'PERSONAL_WWW',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_WWW'),
				'sort' => 'PERSONAL_WWW',
			],
			[
				'id' => 'PERSONAL_MOBILE',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_MOBILE'),
				'sort' => 'PERSONAL_MOBILE',
			],
			[
				'id' => 'PERSONAL_CITY',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_CITY'),
				'sort' => 'PERSONAL_CITY',
			],
			[
				'id' => 'PERSONAL_STREET',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_STREET'),
				'sort' => 'PERSONAL_STREET',
			],
			[
				'id' => 'PERSONAL_STATE',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_STATE'),
				'sort' => 'PERSONAL_STATE',
			],
			[
				'id' => 'PERSONAL_ZIP',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_ZIP'),
				'sort' => 'PERSONAL_ZIP',
			],
			[
				'id' => 'PERSONAL_MAILBOX',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_MAILBOX'),
				'sort' => 'PERSONAL_MAILBOX',
			],
			[
				'id' => 'PERSONAL_COUNTRY',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_COUNTRY'),
				'sort' => 'PERSONAL_COUNTRY',
			],
			[
				'id' => 'WORK_CITY',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_CITY'),
				'sort' => 'WORK_CITY',
			],
			[
				'id' => 'WORK_STREET',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_STREET'),
				'sort' => 'WORK_STREET',
			],
			[
				'id' => 'WORK_STATE',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_STATE'),
				'sort' => 'WORK_STATE',
			],
			[
				'id' => 'WORK_ZIP',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_ZIP'),
				'sort' => 'WORK_ZIP',
			],
			[
				'id' => 'WORK_MAILBOX',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_MAILBOX'),
				'sort' => 'WORK_MAILBOX',
			],
			[
				'id' => 'WORK_COUNTRY',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_COUNTRY'),
				'sort' => 'WORK_COUNTRY',
			],
			[
				'id' => 'WORK_PHONE',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_PHONE'),
				'sort' => 'WORK_PHONE',
			],
			[
				'id' => 'WORK_POSITION',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_POSITION'),
				'sort' => 'WORK_POSITION',
			],
			[
				'id' => 'WORK_COMPANY',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_COMPANY'),
				'sort' => 'WORK_COMPANY',
			],
			[
				'id' => 'WORK_DEPARTMENT',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_WORK_DEPARTMENT'),
				'sort' => 'WORK_DEPARTMENT',
			],
			[
				'id' => 'PERSONAL_GENDER',
				'name' => Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_GENDER'),
				'sort' => 'PERSONAL_GENDER',
			],
		];
	}

	private function getAvailableUserFields(): array
	{
		$fields = $this->getDefaultFields();
		$gridUFManager = new \Bitrix\Main\Grid\Uf\User();
		$gridUFManager->addUFHeaders($fields);

		$result = [];
		foreach ($fields as $id => $field)
		{
			$fieldId = $field['id'] ?? $id;
			if (
				!in_array($fieldId, $this::EXCLUDE_UF_FIELDS)
				&& in_array($fieldId, $this->getSettings()->getViewFields())
			)
			{
				$result[] = $this->createColumn($fieldId, $field);
			}
		}

		return $result;
	}

	public function prepareColumns(): array
	{
		$result = [];

		$result[] =
			$this->createColumn('EMPLOYEE_CARD')
				->setSelect(['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'TITLE', 'PERSONAL_PHOTO', 'WORK_POSITION', 'PERSONAL_GENDER', 'CONFIRM_CODE', 'ACTIVE', 'UF_DEPARTMENT'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_EMPLOYEE_CARD'))
				->setDefault(true)
				->setSort('LAST_NAME')
		;

		$result[] =
			$this->createColumn('UF_DEPARTMENT')
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_UF_DEPARTMENT'))
				->setDefault(true)
				->setWidth(180)
		;

		$result[] =
			$this->createColumn('EMAIL')
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_EMAIL'))
				->setDefault(true)
				->setSort('EMAIL')
		;

		$result[] =
			$this->createColumn('PERSONAL_MOBILE')
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_MOBILE'))
				->setDefault(true)
				->setSort('PERSONAL_MOBILE')
		;

		$result[] =
			$this->createColumn('LAST_ACTIVITY_DATE')
				->setSelect(['LAST_ACTIVITY_DATE', 'ACTIVE', 'CONFIRM_CODE', 'ID', 'UF_DEPARTMENT'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_LAST_ACTIVITY_DATE'))
				->setDefault(true)
				->setSort('LAST_ACTIVITY_DATE')
		;

		$result[] =
			$this->createColumn('MOBILE_APPS')
				->setSelect(['ID'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_MOBILE_APPS'))
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('DESKTOP_APPS')
				->setSelect(['ID'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_DESKTOP_APPS'))
				->setDefault(true)
		;

		$result[] =
			$this->createColumn('ID')
				->setType(Type::NUMBER)
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_ID'))
				->setFirstOrder('asc')
				->setDefault(false)
				->setSort('ID')
		;

		$result[] =
			$this->createColumn('FULL_NAME')
				->setSelect(['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'TITLE', 'WORK_POSITION', 'CONFIRM_CODE', 'ACTIVE', 'UF_DEPARTMENT'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_FULL_NAME'))
				->setDefault(false)
				->setSort('LAST_NAME')
		;

		$result[] =
			$this->createColumn('PERSONAL_PHOTO')
				->setSelect(['PERSONAL_PHOTO', 'PERSONAL_GENDER', 'CONFIRM_CODE', 'ACTIVE'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_PHOTO'))
				->setDefault(false)
		;

		$result[] =
			$this->createColumn('PERSONAL_BIRTHDAY')
				->setSelect(['PERSONAL_BIRTHDAY', 'PERSONAL_GENDER'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_PERSONAL_BIRTHDAY'))
				->setDefault(false)
				->setSort('PERSONAL_BIRTHDAY')
		;

		$result[] =
			$this->createColumn('TAGS')
				->setSelect(['ID'])
				->setName(Loc::getMessage('INTRANET_USER_LIST_COLUMN_TAGS'))
				->setDefault(false)
		;

		return array_merge($result, $this->getAvailableUserFields());
	}
}