<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\Internal\Integration;
use Bitrix\Intranet\User;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Security extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return (new Integration\Security\Otp())->isAvailable();
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_SECURITY_TITLE'),
			'url' => $this->getUrl(),
		];
	}

	public function getName(): string
	{
		return 'security';
	}

	private function getUrl(): string
	{
		$isExtranet = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();
		$profileLink = $isExtranet ? SITE_DIR . 'contacts/personal' : SITE_DIR . 'company/personal';

		return \CComponentEngine::MakePathFromTemplate(
			$profileLink . '/user/#user_id#/common_security/?page=otpConnected',
			['user_id' => $this->user->getId()],
		);
	}
}
