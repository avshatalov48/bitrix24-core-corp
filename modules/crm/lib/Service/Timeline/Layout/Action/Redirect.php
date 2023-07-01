<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Main\Web\Uri;

class Redirect extends Action
{
	protected Uri $url;

	public function __construct(Uri $url)
	{
		$this->url = $url;
	}

	public function getUrl(): Uri
	{
		return $this->url;
	}

	public function toArray(): array
	{
		return [
			'type' => 'redirect',
			'value' => $this->getUrl(),
			'actionParams' => $this->getActionParams(),
			'animation' => $this->getAnimation(),
			'analytics' => $this->getAnalytics(),
		];
	}
}
