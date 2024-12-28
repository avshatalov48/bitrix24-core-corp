<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class ActivityDateFieldAssembler extends JsExtensionFieldAssembler
{
	private string $dateFormat;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);

		$culture = Context::getCurrent()->getCulture();

		$this->dateFormat =
			$culture->get('SHORT_DATE_FORMAT')
			. ', ' .
			$culture->get('SHORT_TIME_FORMAT')
		;
	}

	protected function getRenderParams($rawValue): array
	{
		return [
			'action' => $rawValue['CONFIRM_CODE'] !== '' && $rawValue['ACTIVE'] === 'N'
				? 'accept'
				: 'invite',
			'userId' => $rawValue['ID'],
			'gridId' => $this->getSettings()->getID(),
			'enabled' => $this->getSettings()->isInvitationAvailable(),
			'email' => $rawValue['EMAIL'],
			'phoneNumber' => $rawValue['PERSONAL_MOBILE'],
			'isExtranet' => empty($rawValue['UF_DEPARTMENT']),
			'isCloud' => Loader::includeModule('bitrix24')
		];
	}

	protected function getExtensionClassName(): string
	{
		return 'ActivityField';
	}

	protected function prepareColumnForExport($data): string
	{
		return $data['LAST_ACTIVITY_DATE'] ? FormatDateFromDB($data['LAST_ACTIVITY_DATE'], $this->dateFormat, true) : '';
	}

	protected function prepareColumn($value): mixed
	{
		if (!empty($value['CONFIRM_CODE']))
		{
			return parent::prepareColumn($value);
		}

		if ($value['LAST_ACTIVITY_DATE'])
		{
			return FormatDateFromDB($value['LAST_ACTIVITY_DATE'], $this->dateFormat, true);
		}

		return '';
	}
}