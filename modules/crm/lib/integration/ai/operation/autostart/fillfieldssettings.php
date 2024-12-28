<?php

namespace Bitrix\Crm\Integration\AI\Operation\Autostart;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use CCrmActivityDirection;
use CCrmOwnerType;

final class FillFieldsSettings implements \JsonSerializable, AutoStartInterface
{
	private const DEFAULT_CALL_DIRECTION = CCrmActivityDirection::Incoming;

	public function __construct(
		private readonly array $autostartOperationTypes,
		private readonly bool $autostartTranscriptionOnlyOnFirstCallWithRecording,
		private array $autostartCallDirections
	)
	{
	}

	public function shouldAutostart(
		int $operationType,
		int $callDirection,
		bool $checkAutomaticProcessingParams = true
	): bool
	{
		if (
			$checkAutomaticProcessingParams
			&& !(AIManager::isAiCallAutomaticProcessingAllowed() && AIManager::isBaasServiceHasPackage())
		)
		{
			return false;
		}

		$isAllowedOperationType = in_array($operationType, $this->autostartOperationTypes, true);
		$isAllowedDirection = in_array($callDirection, $this->autostartCallDirections, true);

		if ($operationType !== TranscribeCallRecording::TYPE_ID) // only autostart of first step should be configured
		{
			return true;
		}

		return $isAllowedOperationType && $isAllowedDirection;
	}

	public function isAutostartTranscriptionOnlyOnFirstCallWithRecording(): bool
	{
		return $this->autostartTranscriptionOnlyOnFirstCallWithRecording
			&& in_array(self::DEFAULT_CALL_DIRECTION, $this->autostartCallDirections, true)
		;
	}

	public function jsonSerialize(): array
	{
		return [
			'autostartOperationTypes' => $this->autostartOperationTypes,
			'autostartTranscriptionOnlyOnFirstCallWithRecording' => $this->autostartTranscriptionOnlyOnFirstCallWithRecording,
			'autostartCallDirections' => $this->autostartCallDirections,
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
				static fn(int $x) => in_array($x, $validTypes, true)
			);
		}

		$autostartTranscriptionOnlyOnFirstCallWithRecording =
			$json['autostartTranscriptionOnlyOnFirstCallWithRecording'] ?? null
		;

		// Incoming call by default (for old saved records)
		$autostartCallDirections = $json['autostartCallDirections'] ?? null;
		if (is_array($autostartCallDirections))
		{
			$validDirections = [CCrmActivityDirection::Incoming, CCrmActivityDirection::Outgoing];
			$autostartCallDirections = array_filter(
				array_map('intval', $json['autostartCallDirections']),
				static fn(int $x) => in_array($x, $validDirections, true)
			);
		}

		if (
			is_array($types)
			&& is_bool($autostartTranscriptionOnlyOnFirstCallWithRecording)
			&& is_array($autostartCallDirections)
		)
		{
			return new self(
				$types,
				$autostartTranscriptionOnlyOnFirstCallWithRecording,
				$autostartCallDirections
			);
		}

		return null;
	}

	public static function getDefault(): self
	{
		return new self(
			[
				SummarizeCallTranscription::TYPE_ID,
				FillItemFieldsFromCallTranscription::TYPE_ID,
			],
			false,
			[self::DEFAULT_CALL_DIRECTION]
		);
	}

	public static function get(int $entityTypeId, ?int $categoryId = null): self
	{
		$settingsRaw = Option::get('crm', self::getOptionName($entityTypeId, $categoryId));
		if ($settingsRaw === '')
		{
			return self::getDefault();
		}

		try
		{
			$settingsJson = Json::decode($settingsRaw);
		}
		catch (ArgumentException)
		{
			$settingsJson = [];
		}

		$settings = self::fromJson($settingsJson);
		$settings = $settings instanceof self ? $settings : self::getDefault();
		if (!isset($settings->autostartCallDirections))
		{
			$settings->autostartCallDirections = [self::DEFAULT_CALL_DIRECTION];
		}

		return $settings;
	}

	public static function save(self $settings, int $entityTypeId, ?int $categoryId = null): Result
	{
		$result = new Result();

		if (!CCrmOwnerType::IsDefined($entityTypeId))
		{
			return $result->addError(new Error('Unknown entityTypeId', ErrorCode::INVALID_ARG_VALUE));
		}

		Option::set(
			'crm',
			self::getOptionName($entityTypeId, $categoryId),
			Json::encode($settings->jsonSerialize()),
		);

		return $result;
	}

	private static function getOptionName(int $entityTypeId, ?int $categoryId): string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory?->isCategoriesSupported() && $categoryId === null)
		{
			$categoryId = $factory?->createDefaultCategoryIfNotExist()->getId();
		}

		$typeKey = "{$entityTypeId}";
		if ($categoryId !== null)
		{
			$typeKey .= "_{$categoryId}";
		}

		return "ai_autostart_settings_{$typeKey}";
	}

	public static function checkSavePermissions(int $entityTypeId, ?int $categoryId = null, ?int $userId = null): bool
	{
		return self::checkReadPermissions($entityTypeId, $categoryId, $userId);
	}

	public static function checkReadPermissions(int $entityTypeId, ?int $categoryId = null, ?int $userId = null): bool
	{
		return Container::getInstance()->getUserPermissions($userId)->checkUpdatePermissions($entityTypeId, 0, $categoryId);
	}
}
