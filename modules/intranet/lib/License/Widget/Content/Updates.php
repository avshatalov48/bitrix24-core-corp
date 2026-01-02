<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Main\Localization\Loc;

class Updates extends BaseContent
{
	public function getName(): string
	{
		return 'updates';
	}

	public function getConfiguration(): array
	{
		return [
			'link' => $this->getLink(),
			'title' => $this->getTitle(),
		];
	}

	private function getLink(): string
	{
		return SITE_DIR . 'bitrix/admin/update_system.php';
	}

	private function getTitle(): string
	{
		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_UPDATES_TEXT') ?? '';
	}
}
