<?php

namespace Bitrix\Market\Detail\Types;

use Bitrix\Market\Application\Installed;
use Bitrix\Market\Application\MarketDetail;
use Bitrix\Market\Rest\Actions;

class AppType implements RestMethodInfo
{
	public function getMethodName(): string
	{
		return Actions::METHOD_MARKET_APP;
	}

	public function getParams(MarketDetail $appInfo): array
	{
		$appItem = Installed::getByCode($appInfo->getAppCode());

		$queryFields = [
			'code' => $appInfo->getAppCode(),
			'isInstalled' => ($appItem && $appItem['ACTIVE'] === 'Y') ? 'Y' : 'N',
		];

		if($appInfo->getVersion() > 0) {
			$queryFields['ver'] = $appInfo->getVersion();
		}

		if(!empty($appInfo->getCheckHash()) && !empty($appInfo->getInstallHash())) {
			$queryFields['check_hash'] = $appInfo->getCheckHash();
			$queryFields['install_hash'] = $appInfo->getInstallHash();
		}

		return $queryFields;
	}
}