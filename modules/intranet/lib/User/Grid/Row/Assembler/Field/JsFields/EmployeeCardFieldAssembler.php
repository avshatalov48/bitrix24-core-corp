<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers\UserPhoto;

class EmployeeCardFieldAssembler extends JsExtensionFieldAssembler
{
	use UserPhoto;

	protected function getExtensionClassName(): string
	{
		return 'EmployeeField';
	}

	protected function getRenderParams($rawValue): array
	{
		$userEntity = $this->getUserEntityById($rawValue['ID']);

		return [
			'photoUrl' => $this->getUserPhotoUrl($rawValue),
			'profileLink' => str_replace(['#ID#'], $rawValue['ID'], '/company/personal/user/#ID#/'),
			'fullName' => \CUser::FormatName(\CSite::GetNameFormat(), $rawValue, true, true),
			'position' => $rawValue['WORK_POSITION'],
			'role' => $userEntity->getRole()->value,
			'inviteStatus' => $userEntity->getInviteStatus()->value,
		];
	}

	protected function prepareColumnForExport($data): string
	{
		return \CUser::FormatName(\CSite::GetNameFormat(), $data, true, true);
	}
}