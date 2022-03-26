<?php

namespace Bitrix\Disk\Document\OnlyOffice\Cloud;

use Bitrix\Main\Service\MicroService;

abstract class BaseSender extends MicroService\BaseSender
{
	/** @var string */
	private $serviceUrl;

	public function __construct(string $serviceUrl)
	{
		$this->serviceUrl = $this->refineServiceUrl($serviceUrl);

		parent::__construct();
	}

	protected function refineServiceUrl(string $serviceUrl): string
	{
		if (strpos($serviceUrl, 'http://') !== 0 && strpos($serviceUrl, 'https://') !== 0)
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