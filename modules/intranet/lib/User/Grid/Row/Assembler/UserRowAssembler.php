<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler;

use Bitrix\Intranet\User\Grid\Row\Assembler\Field;
use Bitrix\Main\Grid\Row\RowAssembler;
use Bitrix\Main\Grid\Settings;

class UserRowAssembler extends RowAssembler
{
	protected Settings $settings;

	public function __construct(array $visibleColumnIds, Settings $settings)
	{
		parent::__construct($visibleColumnIds);
		$this->settings = $settings;
	}

	protected function prepareFieldAssemblers(): array
	{
		return [
			new Field\UFFieldAssembler(
				array_keys($this->settings->getUserFields()),
				$this->settings
			),
			new Field\JsFields\PhotoFieldAssembler(['PERSONAL_PHOTO'], $this->settings),
			new Field\JsFields\FullNameFieldAssembler(['FULL_NAME'], $this->settings),
			new Field\JsFields\EmployeeCardFieldAssembler(['EMPLOYEE_CARD'], $this->settings),
			new Field\EmailFieldAssembler(['EMAIL'], $this->settings),
			new Field\BirthDayFieldAssembler(['PERSONAL_BIRTHDAY'], $this->settings),
			new Field\UrlFieldAssembler(['PERSONAL_WWW'], $this->settings),
			new Field\JsFields\DepartmentFieldAssembler(['UF_DEPARTMENT'], $this->settings),
			new Field\GenderFieldAssembler(['PERSONAL_GENDER']),
			new Field\TagsFieldAssembler(['TAGS'], $this->settings),
			new Field\JsFields\ActivityDateFieldAssembler(['LAST_ACTIVITY_DATE'], $this->settings),
			new Field\MobileAppsField(['MOBILE_APPS'], $this->settings),
			new Field\DesktopAppsField(['DESKTOP_APPS'], $this->settings),
			new Field\CountryFieldAssembler(['PERSONAL_COUNTRY', 'WORK_COUNTRY']),
			new Field\PhoneNumberField(['PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE']),
			new Field\StringFieldAssembler([
				'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_WWW', 'PERSONAL_CITY', 'WORK_STATE',
				'PERSONAL_STREET', 'PERSONAL_ZIP', 'PERSONAL_MAILBOX', 'PERSONAL_COUNTRY', 'WORK_CITY', 'WORK_STREET',
				'WORK_ZIP', 'WORK_MAILBOX', 'WORK_COUNTRY', 'WORK_POSITION', 'WORK_COMPANY', 'WORK_DEPARTMENT'
				]),
		];
	}
}