<?php

namespace Bitrix\Intranet\Infrastructure;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Integration\Main\Culture;

class UserNameFormatter
{
	public function __construct(
		private readonly User $user,
		private readonly bool $useLogin = false,
		private readonly bool $useHtmlSpec = true,
	)
	{
	}

	public function formatByCulture(): string
	{
		$currentSite = Culture::getCurrentSite();
		$templateName = '#NAME# #LAST_NAME#';
		if (is_array($currentSite) && isset($currentSite['FORMAT_NAME']))
		{
			$templateName = $currentSite['FORMAT_NAME'];
		}

		return $this->formatByTemplate($templateName);
	}

	public function formatByTemplate(string $template): string
	{
		return \CUser::FormatName(
			NAME_TEMPLATE: $template,
			arUser: [
				'NAME' => $this->user->getName(),
				'LAST_NAME' => $this->user->getLastName(),
				'SECOND_NAME' => $this->user->getSecondName(),
				'LOGIN' => $this->user->getLogin(),
			],
			bUseLogin: $this->useLogin,
			bHTMLSpec: $this->useHtmlSpec,
		);
	}
}