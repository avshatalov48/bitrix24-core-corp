<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\ViewersProvider;

class Viewer extends Controller
{
	public function configureActions(): array
	{
		return [
			'getViewers' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getViewersAction(
		array $params = [],
		PageNavigation $pageNavigation = null,
	): ?array
	{
		$entityId = (int) ($params['entityId'] ?? 0);
		$entityType = (string) ($params['entityType'] ?? '');

		if ($entityId && $entityType)
		{
			$provider = new ViewersProvider($pageNavigation);

			return $provider->getViewersData($entityType, $entityId);
		}

		return [];
	}
}
