<?php

namespace Bitrix\AI\Facade;

use Bitrix\AI\Config;
use Bitrix\AI\Engine;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserTable;
use CUser;

class User
{
	private const PREFIX_OPTION_CODE_LAST_ENGINE = 'last_engine_in_';
	private const PREFIX_OPTION_CODE_LAST_ROLE = 'last_role_in_';

	private const DEFAULT_LANG = 'en';

	/**
	 * Returns main module's USER instance.
	 *
	 * @return CUser
	 */
	public static function getInstance(): ?CUser
	{
		return $GLOBALS['USER'] ?? null;
	}

	/**
	 * Returns current user id.
	 *
	 * @return int
	 */
	public static function getCurrentUserId(): int
	{
		$userInstance = self::getInstance();
		if (empty($userInstance))
		{
			return 0;
		}

		return $userInstance->getId();
	}

	/**
	 * Returns user data by userId.
	 *
	 * @return array
	 */
	public static function getUserDataById(int $userId): array
	{
		$user = UserTable::query()
			->setSelect(['NAME','LAST_NAME','PERSONAL_GENDER'])
			->where('ID', $userId)
			->setLimit(1)
			->fetch()
		;

		return $user ?: [];
	}

	/**
	 * Returns true if current user is admin.
	 *
	 * @return bool
	 */
	public static function isAdmin(): bool
	{
		$user = self::getInstance();
		if (empty($user))
		{
			return false;
		}

		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			return $user->canDoOperation('bitrix24_config');
		}
		else
		{
			return $user->isAdmin();
		}
	}

	/**
	 * Returns User's Language.
	 *
	 * @return string
	 */
	public static function getUserLanguage(): string
	{
		return LANGUAGE_ID ?? static::DEFAULT_LANG;
	}

	/**
	 * Sets specified engine as last used by current user.
	 *
	 * @param Engine $engine Engine instance.
	 * @return void
	 */
	public static function setLastUsedEngineCode(Engine $engine): void
	{
		$prefix = self::PREFIX_OPTION_CODE_LAST_ENGINE;
		$moduleId = $engine->getIEngine()->getContext()->getModuleId();
		Config::setPersonalValue(
			$prefix . $engine->getCategory() . self::preparePostfix($moduleId),
			$engine->getCode()
		);
	}

	/**
	 * Returns last used engine code in category.
	 *
	 * @param string $category Engine category code.
	 * @return string
	 */
	public static function getLastUsedEngineCode(string $category, string $moduleId): string
	{
		$prefix = self::PREFIX_OPTION_CODE_LAST_ENGINE;
		return (string)Config::getPersonalValue($prefix . $category . self::preparePostfix($moduleId));
	}

	/**
	 * Drop last used engine by current user - will use default
	 *
	 * @param string $category Engine category code.
	 * @return void
	 */
	public static function clearLastUsedEngineCode(string $category): void
	{
		$prefix = self::PREFIX_OPTION_CODE_LAST_ENGINE;
		Config::setPersonalValue($prefix . $category, '');
	}

	/**
	 * Drop last used engine for all user - will use default
	 *
	 * @param string $category Engine category code.
	 * @return void
	 */
	public static function clearLastUsedEngineCodeForAll(string $category, string $moduleId = 'default'): void
	{
		$prefix = self::PREFIX_OPTION_CODE_LAST_ENGINE;
		Config::setPersonalValueForAll($prefix . $category . self::preparePostfix($moduleId), '');
	}

	/**
	 * Returns last used role code in category.
	 *
	 * @param string $category Engine category code.
	 * @return string
	 */
	public static function getLastUsedRoleCode(string $category, string $moduleId): string
	{
		$prefix = self::PREFIX_OPTION_CODE_LAST_ROLE;
		return (string)Config::getPersonalValue($prefix . $category . self::preparePostfix($moduleId));
	}

	/**
	 * Sets specified role as last used by current user.
	 *
	 * @param Engine $engine Engine instance.
	 * @return void
	 */
	public static function setLastUsedRoleCode(Engine $engine): void
	{
		if ($engine->getPayload()->getRole() === null)
		{
			return;
		}

		$prefix = self::PREFIX_OPTION_CODE_LAST_ROLE;
		$moduleId = $engine->getIEngine()->getContext()->getModuleId();
		Config::setPersonalValue(
			$prefix . $engine->getCategory() . self::preparePostfix($moduleId),
			$engine->getPayload()->getRole()?->getCode()
		);
	}

	/**
	 *
	 * @param string $moduleId
	 *
	 * @return string
	 */
	private static function preparePostfix(string $moduleId): string
	{
		return ($moduleId === 'im') ? '_' . $moduleId : '_default';
	}
}
