<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Web\Uri;

class Base extends Controller
{
	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}

	protected function trackedToItem($trackedItem): array
	{
		$trackedObject = $trackedItem['trackedObject'];

		$downloadLink = $trackedObject['links']['download'];
		if ($downloadLink instanceof Uri)
		{
			$downloadLink = $downloadLink->getUri();
		}

		$previewLink = $trackedObject['links']['preview'];
		if ($previewLink instanceof Uri)
		{
			$previewLink = $previewLink->getUri();
		}

		return array_merge(
			[
				'trackedId' => (int)$trackedObject['id'],
			],
			$trackedObject['file'],
			[
				'links' => [
					'download' => is_string($downloadLink) ? $downloadLink : null,
					'preview' => is_string($previewLink) ? $previewLink : null,
				]
			],
		);
	}
}