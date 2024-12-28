<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

final class ErrorCode
{
	public const AI_NOT_AVAILABLE = 'AI_NOT_AVAILABLE';
	public const AI_DISABLED = 'AI_IS_DISABLED';
	public const LICENSE_NOT_ACCEPTED = 'LICENSE_NOT_ACCEPTED';
	public const NOT_FOUND = \Bitrix\Crm\Controller\ErrorCode::NOT_FOUND;
	public const FILE_NOT_FOUND = \Bitrix\Crm\Controller\ErrorCode::FILE_NOT_FOUND;
	public const AI_RESULT_NOT_FOUND = 'AI_RESULT_NOT_FOUND';
	public const FILE_NOT_SUPPORTED = 'FILE_NOT_SUPPORTED';
	public const AI_ENGINE_NOT_FOUND = 'AI_ENGINE_NOT_FOUND';
	public const AI_ENGINE_LIMIT_EXCEEDED = 'AI_ENGINE_LIMIT_EXCEEDED';
	public const REQUIRED_ARG_MISSING = \Bitrix\Crm\Controller\ErrorCode::REQUIRED_ARG_MISSING;
	public const INVALID_ARG_VALUE = \Bitrix\Crm\Controller\ErrorCode::INVALID_ARG_VALUE;
	public const JOB_ALREADY_EXISTS = 'JOB_ALREADY_EXISTS';
	public const JOB_MAX_RETRIES_EXCEEDED = 'JOB_MAX_RETRIES_EXCEEDED';
	public const JOB_EXECUTION_FAILED = 'JOB_EXECUTION_FAILED';
	public const JOB_IN_WRONG_STATUS = 'JOB_IN_WRONG_STATUS';
	public const OPERATION_IS_COMPLETE = 'OPERATION_IS_COMPLETE';
	public const NOT_SUITABLE_TARGET = 'NOT_SUITABLE_TARGET';
	public const PAYLOAD_NOT_FOUND = 'PAYLOAD_NOT_FOUND';
	public const OPERATION_TYPE_NOT_SUPPORTED = 'OPERATION_TYPE_NOT_SUPPORTED';
	public const OPERATION_IS_PENDING = 'OPERATION_IS_PENDING';
	public const PAYLOAD_IS_EMPTY_ERROR_CODE = 'PAYLOAD_IS_EMPTY';

	public static function getAINotAvailableError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_NOT_AVAILABLE'),
			self::AI_NOT_AVAILABLE
		);
	}

	public static function getAIDisabledError(array $customData = null): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_DISABLED'),
			self::AI_DISABLED,
			$customData
		);
	}

	public static function getLicenseNotAcceptedError(array $customData = null): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_NOT_AVAILABLE'),
			self::LICENSE_NOT_ACCEPTED
		);
	}

	public static function getNotFoundError(): Error
	{
		return \Bitrix\Crm\Controller\ErrorCode::getNotFoundError();
	}

	public static function getFileNotFoundError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_FILE_NOT_FOUND'),
			self::FILE_NOT_FOUND
		);
	}

	public static function getAIEngineNotFoundError(array $customData = null): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_FAILED'),
			self::AI_ENGINE_NOT_FOUND,
			$customData
		);
	}

	public static function getAIResultFoundError(array $customData = null): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_FAILED'),
			self::AI_RESULT_NOT_FOUND,
			$customData
		);
	}

	public static function getAILimitOfRequestsExceededError(array $customData = null): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_LIMIT_EXCEEDED'),
			self::AI_ENGINE_LIMIT_EXCEEDED,
			$customData
		);
	}

	public static function getJobAlreadyExistsError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_JOB_ALREADY_EXISTS'),
			self::JOB_ALREADY_EXISTS
		);
	}

	public static function getJobMaxRetriesExceededError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_FAILED'),
			self::JOB_MAX_RETRIES_EXCEEDED
		);
	}

	public static function getJobExecutionFailedError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_FAILED'),
			self::JOB_EXECUTION_FAILED
		);
	}

	public static function getNotSuitableTargetError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_FAILED'),
			self::NOT_SUITABLE_TARGET
		);
	}

	public static function getPayloadNotFoundError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_ENGINE_FAILED'),
			self::PAYLOAD_NOT_FOUND
		);
	}

	public static function getOperationIsCompleteError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_INTEGRATION_AI_ERROR_JOB_ALREADY_EXISTS'),
			self::OPERATION_IS_COMPLETE
		);
	}

	public static function getInvalidPayloadError(): Error
	{
		return new Error(
			'Payload cant be completely empty',
			self::PAYLOAD_IS_EMPTY_ERROR_CODE
		);
	}

	private function __construct()
	{
	}
}
