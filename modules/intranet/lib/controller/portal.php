<?php

namespace Bitrix\Intranet\Controller;


use Bitrix\Intranet\ActionFilter\UserType;
use Bitrix\Main\Config\Option;

class Portal extends \Bitrix\Main\Engine\Controller
{
	protected function getDefaultPreFilters(): array
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[
				new UserType(['employee', 'extranet']),
			]
		);
	}

	public function getLogoAction(): array
	{
		$settings = \Bitrix\Intranet\Portal::getInstance()->getSettings();

		// to bypass default value
		$result['title'] = Option::get('bitrix24', 'site_title', null, SITE_ID) ??
			Option::get('bitrix24', 'site_title', null) ?? '';

		$result['logo'] = $settings->getLogo();
		$result['logo24'] = $settings->getLogo24();

		if (empty($result['title']) && empty($result['logo']))
		{
			$result['defaultLogo'] = $settings->getDefaultLogo();
		}

		return $result;
	}
}