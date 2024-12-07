<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Settings;

/**
 * @method UserSettings getSettings()
 */
class FullNameFieldAssembler extends JsExtensionFieldAssembler
{
	private array $integratorsId;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);
		$this->integratorsId = \Bitrix\Main\Loader::includeModule('bitrix24') ? \Bitrix\Bitrix24\Integrator::getIntegratorsId() : [];
	}

	protected function getRenderParams($rawValue): array
	{
		return [
			'profileLink' => str_replace(['#ID#'], $rawValue['ID'], '/company/personal/user/#ID#/'),
			'fullName' => \CUser::FormatName(\CSite::GetNameFormat(), $rawValue, true, true),
			'isAdmin' => $this->getSettings()->isUserAdmin($rawValue['ID']),
			'position' => $rawValue['WORK_POSITION'],
			'isInvited' => !empty($rawValue['CONFIRM_CODE']),
			'isConfirmed' => $rawValue['ACTIVE'] === 'Y',
			'isExtranet' => (empty($rawValue['UF_DEPARTMENT']) && \Bitrix\Intranet\Util::isExtranetUser($rawValue['ID'])),
			'isIntegrator' => in_array($rawValue['ID'], $this->integratorsId),
		];
	}

	protected function getExtensionClassName(): string
	{
		return 'FullNameField';
	}

	protected function prepareColumnForExport($data): string
	{
		return \CUser::FormatName(\CSite::GetNameFormat(), $data, true, true);
	}
}