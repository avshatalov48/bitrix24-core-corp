<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

abstract class ErrorCode
{
	public const GENERAL_ERROR = 'GENERAL_ERROR';
	public const ACCESS_DENIED = 'ACCESS_DENIED';
	public const MODULE_NOT_INSTALLED = 'MODULE_NOT_INSTALLED';
	public const NOT_FOUND = 'NOT_FOUND';
	public const FILE_NOT_FOUND = 'FILE_NOT_FOUND';
	public const ENTITY_NOT_SUPPORTED = 'ENTITY_TYPE_NOT_SUPPORTED';
	public const OWNER_NOT_FOUND = 'OWNER_NOT_FOUND';
	public const REQUIRED_ARG_MISSING = 'REQUIRED_ARG_MISSING';
	public const INVALID_ARG_VALUE = 'INVALID_ARG_VALUE';
	public const ADDING_DISABLED = 'ADDING_DISABLED';
	public const REMOVING_DISABLED = 'REMOVING_DISABLED';
	public const MULTIPLE_BINDINGS = 'MULTIPLE_BINDINGS';
	public const RESTRICTED_BY_TARIFF = 'RESTRICTED_BY_TARIFF';

	protected static function loadMessages(): void
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public static function getGeneralError(): Error
	{
		static::loadMessages();

		return new Error(
			Loc::getMessage('CRM_COMMON_ERROR_GENERAL'),
			static::GENERAL_ERROR,
		);
	}

	public static function getAccessDeniedError(): Error
	{
		static::loadMessages();

		return new Error(
			Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
			static::ACCESS_DENIED
		);
	}

	public static function getModuleNotInstalledError(string $moduleName): Error
	{
		static::loadMessages();

		return new Error(
			Loc::getMessage('CRM_COMMON_ERROR_MODULE_NOT_INSTALLED', [
				'#MODULE#' => $moduleName,
			]),
			static::MODULE_NOT_INSTALLED,
		);
	}

	public static function getNotFoundError(): Error
	{
		static::loadMessages();

		return new Error(
			Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
			static::NOT_FOUND
		);
	}

	public static function getEntityTypeNotSupportedError(?int $entityTypeId = null): Error
	{
		static::loadMessages();
		$entityTypeName = is_null($entityTypeId) ? '' : \CCrmOwnerType::GetDescription($entityTypeId);

		return new Error(
			Loc::getMessage('CRM_COMMON_ERROR_ENTITY_TYPE_NOT_SUPPORT', [
				'#ENTITY#' => $entityTypeName,
			]),
			static::ENTITY_NOT_SUPPORTED,
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
			],
		);
	}

	public static function getOwnerNotFoundError(): Error
	{
		static::loadMessages();

		return new Error(
			'Owner was not found',
			static::OWNER_NOT_FOUND
		);
	}

	public static function getRequiredArgumentMissingError(string $argumentName): Error
	{
		static::loadMessages();

		return new Error(
			"Argument '{$argumentName}' is required",
			static::REQUIRED_ARG_MISSING,
			[
				'ARGUMENT_NAME' => $argumentName,
			]
		);
	}

	public static function getMultipleBindingsError(): Error
	{
		static::loadMessages();

		return new Error(
			'Entity has multiple bindings',
			static::MULTIPLE_BINDINGS
		);
	}

	public static function groupErrorsByMessage(ErrorCollection $errorCollection): ErrorCollection
	{
		$result = new ErrorCollection();
		$errors = [];

		/** @var Error $error */
		foreach ($errorCollection->toArray() as $error)
		{
			$errors[$error->getMessage()] ??= 0;
			$errors[$error->getMessage()]++;
		}

		foreach ($errors as $message => $count)
		{
			$error = new Error($message, customData: [
				'errorCount' => $count,
			]);

			$result->setError($error);
		}

		return $result;
	}
}
