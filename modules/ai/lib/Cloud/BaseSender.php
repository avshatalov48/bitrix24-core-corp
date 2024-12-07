<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\Main\Service\MicroService;

abstract class BaseSender extends MicroService\BaseSender
{
	private string $serviceUrl;

	public function __construct(string $serviceUrl)
	{
		$this->serviceUrl = $this->refineServiceUrl($serviceUrl);

		parent::__construct();
	}

	protected function refineServiceUrl(string $serviceUrl): string
	{
		if (!str_starts_with($serviceUrl, 'http://') && !str_starts_with($serviceUrl, 'https://'))
		{
			return "https://{$serviceUrl}";
		}

		return $serviceUrl;
	}

	protected function getServiceUrl(): string
	{
		return $this->serviceUrl;
	}
}