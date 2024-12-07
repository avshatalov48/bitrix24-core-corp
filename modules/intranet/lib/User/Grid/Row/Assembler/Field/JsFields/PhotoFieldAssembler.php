<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers\UserPhoto;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;

class PhotoFieldAssembler extends JsExtensionFieldAssembler
{
	use UserPhoto;

	protected function getExtensionClassName(): string
	{
		return 'PhotoField';
	}

	protected function getRenderParams($rawValue): array
	{
		return [
			'photoUrl' => $this->getUserPhotoUrl($rawValue),
			'isInvited' => !empty($rawValue['CONFIRM_CODE']),
			'isConfirmed' => $rawValue['ACTIVE'] === 'Y',
		];
	}

	protected function prepareColumnForExport($data): string
	{
		return '';
	}
}