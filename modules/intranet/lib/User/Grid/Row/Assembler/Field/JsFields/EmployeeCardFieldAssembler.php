<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers\UserPhoto;
use Bitrix\Main\Grid\Settings;

class EmployeeCardFieldAssembler extends JsExtensionFieldAssembler
{
	use UserPhoto;

	private array $integratorsId;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);
		$this->integratorsId = \Bitrix\Main\Loader::includeModule('bitrix24') ? \Bitrix\Bitrix24\Integrator::getIntegratorsId() : [];
	}

	protected function getExtensionClassName(): string
	{
		return 'EmployeeField';
	}

	protected function getRenderParams($rawValue): array
	{
		return [
			'photoUrl' => $this->getUserPhotoUrl($rawValue),
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

	protected function prepareColumnForExport($data): string
	{
		return \CUser::FormatName(\CSite::GetNameFormat(), $data, true, true);
	}
}