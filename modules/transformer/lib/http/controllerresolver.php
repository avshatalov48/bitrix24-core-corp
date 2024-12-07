<?php

namespace Bitrix\Transformer\Http;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;
use Bitrix\Transformer\Http;
use Bitrix\Transformer\Integration\Baas\Feature;

/**
 * @internal
 */
final class ControllerResolver
{
	public function __construct(
		private readonly Feature $dedicatedControllerFeature,
		private readonly string $dedicatedControllerUrlConstName = 'TRANSFORMER_CONTROLLER_URL_BAAS_DEDICATED_DOCGEN',
		private readonly string $regularControllerUrlConstName = 'TRANSFORMER_CONTROLLER_URL'
	)
	{
	}

	public function resolveControllerUrl(string $commandName, ?string $queue = null): ?string
	{
		$dedicated = $this->resolveToDedicatedController($commandName, $queue);
		if ($dedicated)
		{
			return $dedicated;
		}

		return $this->resolveToRegularController();
	}

	private function resolveToDedicatedController(string $commandName, ?string $queue): ?string
	{
		if (!$this->dedicatedControllerFeature->isActive())
		{
			return null;
		}

		if (!$this->dedicatedControllerFeature->isApplicable(['commandName' => $commandName, 'queue' => $queue]))
		{
			return null;
		}

		return $this->getBaasDedicatedControllerUrl();
	}

	private function resolveToRegularController(): ?string
	{
		$constControllerUrl = $this->getControllerUrlFromConst($this->regularControllerUrlConstName);
		if ($constControllerUrl)
		{
			return $constControllerUrl;
		}

		$optionsControllerUrl = Option::get(
			Http::MODULE_ID,
			'transformer_controller_url',
			$this->getDefaultCloudControllerUrl(),
		);
		if (!empty($optionsControllerUrl))
		{
			$uri = new Uri($optionsControllerUrl);
			if ($this->validateUrl($uri))
			{
				return $uri->getLocator();
			}
		}

		return null;
	}

	private function getControllerUrlFromConst(string $constName): ?string
	{
		if (!defined($constName))
		{
			return null;
		}

		$constValue = constant($constName);

		if (is_string($constValue) && $this->validateUrl($constValue))
		{
			return $constValue;
		}

		return null;
	}

	private function validateUrl(Uri|string $url): bool
	{
		if (is_string($url))
		{
			$url = new Uri($url);
		}

		return !empty($url->getHost());
	}

	public function getDefaultCloudControllerUrl(): string
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return match ($region) {
			'ru' => 'https://transformer-ru-boxes.bitrix.info/bitrix/tools/transformercontroller/add_queue.php',
			default => Http::CLOUD_CONVERTER_URL,
		};
	}

	public function getBaasDedicatedControllerUrl(): ?string
	{
		return $this->getControllerUrlFromConst($this->dedicatedControllerUrlConstName);
	}

	/**
	 * @internal
	 */
	public function isDefaultCloudControllerUsed(): bool
	{
		return $this->getDefaultCloudControllerUrl() === $this->resolveToRegularController();
	}
}
