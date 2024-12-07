<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud\EngineProperty;

use Bitrix\AI\Config;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;

/**
 * Class Model
 * Describes the model property of the cloud engine.
 */
final class Model
{
	public const OPTION_NAME = 'cloud_models';
	public const DEFAULT_MODULE_ID = 'default';

	public function __construct(
		private readonly string $engineClass,
		private readonly string $contextModuleId,
	)
	{
	}

	private function getStoredValues(): array
	{
		$value = Config::getValue(self::OPTION_NAME) ?: null;
		if ($value === null)
		{
			return [];
		}

		try
		{
			$value = Json::decode($value);
			if (!\is_array($value))
			{
				return [];
			}

			return $value;
		}
		catch (ArgumentException)
		{
			return [];
		}
	}

	/**
	 * Returns the model name if it is set by remote aiproxy service.
	 * @return string|null
	 */
	public function getValue(): ?string
	{
		$storedValues = $this->getStoredValues();
		$optionValue = $storedValues[$this->engineClass] ?? null;
		if (empty($optionValue))
		{
			return null;
		}

		return $optionValue[$this->contextModuleId] ?? $optionValue[self::DEFAULT_MODULE_ID] ?? null;
	}
}