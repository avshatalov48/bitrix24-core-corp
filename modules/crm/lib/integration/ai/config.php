<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\AI\Facade;
use Bitrix\Crm\Service\Container;
use CUserOptions;

final class Config
{
	public const MODULE_ID = 'crm';
	public const CODE_PREFIX = 'ai_config';
	public const LANGUAGE_CODE = 'languageId';

	public static function getAll(int $userId, int $entityTypeId, ?int $categoryId): array
	{
		$config = CUserOptions::GetOption(
			self::MODULE_ID,
			self::getOptionName($entityTypeId, $categoryId),
			[],
			$userId
		);

		return is_array($config) ? $config : [];
	}

	public static function getLanguageId(int $userId, int $entityTypeId, ?int $categoryId): string
	{
		$config = self::getAll($userId, $entityTypeId, $categoryId);
		$languageId = $config[self::LANGUAGE_CODE] ?? '';
		if (
			empty($languageId)
			|| !self::isValidLanguageId($languageId)
		)
		{
			$languageId = self::getDefaultLanguageId();
		}

		return $languageId;
	}

	public static function getDefaultLanguageId(): string
	{
		if (AIManager::isAvailable())
		{
			return Facade\User::getUserLanguage();
		}

		return '';
	}

	private static function getOptionName(int $entityTypeId, ?int $categoryId): string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($categoryId === null && $factory?->isCategoriesSupported())
		{
			$categoryId = $factory?->createDefaultCategoryIfNotExist()->getId();
		}

		$typeKey = (string)($entityTypeId);
		if ($categoryId !== null)
		{
			$typeKey .= "_{$categoryId}";
		}

		return self::CODE_PREFIX . "_{$typeKey}";
	}

	private static function isValidLanguageId(string $languageId): bool
	{
		return array_key_exists($languageId, AIManager::getAvailableLanguageList());
	}
}
