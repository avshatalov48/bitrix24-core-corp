<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\User\Widget\BaseContent;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CComponentEngine;

class Settings extends BaseContent
{
	public function getName(): string
	{
		return 'settings';
	}

	public function getConfiguration(): array
	{
		$isExtranetSite = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();
		$profileLink = $isExtranetSite ? SITE_DIR . 'contacts/personal' : SITE_DIR . 'company/personal';
		$path = CComponentEngine::MakePathFromTemplate(
			$profileLink . '/user/#user_id#/common_security/?page=auth',
			['user_id' => CurrentUser::get()->getId()],
		);

		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_SETTINGS_TITLE'),
			'path' => $path,
		];
	}
}
