<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class AutostartSettings implements \JsonSerializable
{
	public function __construct(
		private array $autostartOperationTypes,
		private bool $autostartTranscriptionOnlyOnFirstCallWithRecording,
	)
	{
	}

	public function shouldAutostart(int $operationType, bool $checkAutomaticProcessingParams = true): bool
	{
		if ($checkAutomaticProcessingParams && !AIManager::isAiCallAutomaticProcessingAllowed())
		{
			return false;
		}

		return in_array($operationType, $this->autostartOperationTypes, true);
	}

	public function isAutostartTranscriptionOnlyOnFirstCallWithRecording(): bool
	{
		return $this->autostartTranscriptionOnlyOnFirstCallWithRecording;
	}

	public function jsonSerialize(): array
	{
		return [
			'autostartOperationTypes' => $this->autostartOperationTypes,
			'autostartTranscriptionOnlyOnFirstCallWithRecording' => $this->autostartTranscriptionOnlyOnFirstCallWithRecording,
		];
	}

	public static function fromJson(array $json): ?self
	{
		$types = $json['autostartOperationTypes'] ?? null;
		if (is_array($types))
		{
			$validTypes = AIManager::getAllOperationTypes();
			$types = array_filter(
				array_map('intval', $json['autostartOperationTypes']),
				fn(int $x) => in_array($x, $validTypes, true)
			);
		}

		$autostartTranscriptionOnlyOnFirstCallWithRecording =
			$json['autostartTranscriptionOnlyOnFirstCallWithRecording'] ?? null
		;

		if (is_array($types) && is_bool($autostartTranscriptionOnlyOnFirstCallWithRecording))
		{
			return new self($types, $autostartTranscriptionOnlyOnFirstCallWithRecording);
		}

		return null;
	}

	public static function getDefault(): self
	{
		return new self(
			AIManager::getAllOperationTypes(),
			true,
		);
	}

	public static function get(int $entityTypeId, ?int $categoryId = null): self
	{
		$settings = unserialize(
			Option::get('crm', self::getOptionName($entityTypeId, $categoryId)),
			['allowed_classes' => [self::class]],
		);

		return $settings instanceof self ? $settings : self::getDefault();
	}

	public static function save(self $settings, int $entityTypeId, ?int $categoryId = null): Result
	{
		$result = new Result();

		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return $result->addError(new Error('Unknown entityTypeId', ErrorCode::INVALID_ARG_VALUE));
		}

		Option::set(
			'crm',
			self::getOptionName($entityTypeId, $categoryId),
			serialize($settings),
		);

		return $result;
	}

	private static function getOptionName(int $entityTypeId, ?int $categoryId): string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory?->isCategoriesSupported() && $categoryId === null)
		{
			$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
		}

		$typeKey = "{$entityTypeId}";
		if ($categoryId !== null)
		{
			$typeKey .= "_{$categoryId}";
		}

		return "ai_autostart_settings_{$typeKey}";
	}
}
