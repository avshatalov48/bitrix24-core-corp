<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers\UserPhoto;

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
			'role' => $this->getUserEntityById($rawValue['ID'])->getRole()->value,
		];
	}

	protected function prepareColumnForExport($data): string
	{
		return '';
	}
}