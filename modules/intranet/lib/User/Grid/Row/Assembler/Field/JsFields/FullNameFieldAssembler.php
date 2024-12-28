<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Main\Grid\Settings;

class FullNameFieldAssembler extends JsExtensionFieldAssembler
{
	protected function getRenderParams($rawValue): array
	{
		$userEntity = $this->getUserEntityById($rawValue['ID']);

		return [
			'profileLink' => str_replace(['#ID#'], $rawValue['ID'], '/company/personal/user/#ID#/'),
			'fullName' => \CUser::FormatName(\CSite::GetNameFormat(), $rawValue, true, true),
			'position' => $rawValue['WORK_POSITION'],
			'role' => $userEntity->getRole()->value,
			'inviteStatus' => $userEntity->getInviteStatus()->value,
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