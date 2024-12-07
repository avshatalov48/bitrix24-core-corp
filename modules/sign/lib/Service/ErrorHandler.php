<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Storage;

/**
 * Localize error service error messages by code
 */
final class ErrorHandler
{
	/**
	 * Handle parsed error
	 *
	 * @param array $error Error from json data
	 *
	 * @return Error
	 */
	public function handleParsedError(array $error): Error
	{
		// there can be error logging
		if (!empty($error['code']))
		{
			return $this->getErrorByCode((string)$error['message'], (string)$error['code'], (array)($error['customData'] ?? []));
		}

		return new Error($this->getDefaultError());
	}

	/**
	 * Get error by code
	 *
	 * @param string $code Error code
	 * @param array $customData Additional error data
	 *
	 * @return Error
	 */
	private function getErrorByCode(string $message, string $code, array $customData): Error
	{
		$message = $this->getErrorMessageByCode( $message, $code, $customData);
		$customData = $this->getErrorCustomDataByCode( $message, $code, $customData);
		return new Error($message, $code, $customData);
	}

	/**
	 * Get error message by code
	 *
	 * @param string $message Error message
	 * @param string $code Error code
	 * @param array $customData Additional error data
	 *
	 * @return string
	 */
	private function getErrorMessageByCode(string $message, string $code, array $customData): string
	{
		return (string) match ($code)
		{
			'FILE_IS_PROTECTED' => Loc::getMessage('SIGN_SERVICE_ERROR_PROTECTED_PDF'),
			'FILE_TOO_BIG' => $this->getTooBigError($customData),
			'NO_FILE_TYPE', 'NOT_ALLOWED_EXTENSIONS', 'UNSUPPORTED_FILE_TYPE'  => Loc::getMessage('SIGN_SERVICE_ERROR_NO_FILE_TYPE'),
			'TO_MANY_FILES', 'MULTIPLE_FILES_ONLY_FOR_IMAGES' => $this->getTooManyFilesError($customData),
			'FILE_TOO_MANY_PAGES' => $this->getFileToManyPagesError($customData),
			'DELETE_DOCUMENTS_EXISTS' => Loc::getMessage('SIGN_SERVICE_ERROR_DELETE_DOCUMENTS_EXISTS'),
			'BLANK_DELETE_FAILED' => Loc::getMessage('SIGN_SERVICE_ERROR_BLANK_DELETE_FAILED'),
			'REQUIRED_FIELDS_VALIDATION', 'BLOCK_REQUIRED_FIELDS_VALIDATION', 'MEMBER_REQUIRED_FIELDS_VALIDATION',
				=> Loc::getMessage('SIGN_SERVICE_ERROR_REQUIRED_FIELDS_VALIDATION'),
			'EMPTY_MEMBER_CHANNEL' => Loc::getMessage('SIGN_SERVICE_ERROR_EMPTY_MEMBER_CHANNEL'),
			'DOCUMENT_TO_MANY_MEMBERS' => Loc::getMessage('SIGN_SERVICE_ERROR_DOCUMENT_TO_MANY_MEMBERS'),
			'PREVIOUS_MEMBER_NOT_DONE' => Loc::getMessage('SIGN_SERVICE_ERROR_PREVIOUS_MEMBER_NOT_DONE'),
			'FIELD_FILE_NOT_IMAGE' => Loc::getMessage('SIGN_SERVICE_ERROR_FIELD_FILE_NOT_IMAGE'),
			'MEMBER_INVALID_PHONE' => Loc::getMessage('SIGN_SERVICE_ERROR_MEMBER_INVALID_PHONE', ['#PHONE#' => $customData['phone'] ?? '']),
			'MEMBER_PHONE_UNSUPPORTED_COUNTRY_CODE' => Loc::getMessage('SIGN_SERVICE_ERROR_MEMBER_PHONE_UNSUPPORTED_COUNTRY_CODE', ['#PHONE#' => $customData['phone'] ?? '']),
			'DOCUMENT_SIGNING_EXPIRED' => Loc::getMessage('SIGN_SERVICE_ERROR_DOCUMENT_SIGNING_EXPIRED'),
			'SMS_LIMIT_EXCEEDED' => Loc::getMessage('SIGN_SERVICE_ERROR_SMS_LIMIT_EXCEEDED'),
			'MEMBERS_NOT_READY_FOR_RESEND' => Loc::getMessage('SIGN_SERVICE_ERROR_MEMBERS_NOT_READY_FOR_RESEND'),
			'INCORRECT_TAX_ID', 'B2E_COMPANY_NAME_NOT_FOUND' => Loc::getMessage('SIGN_SERVICE_ERROR_INCORRECT_TAX_ID'),
			'PROVIDER_ERROR' => $message, //bypass for rest
			default => $this->getDefaultError($code),
		};
	}

	private function getErrorCustomDataByCode(string $message, string $code, array $customData): array
	{
		return match ($code)
		{
			'MEMBER_INVALID_PHONE', 'MEMBER_PHONE_UNSUPPORTED_COUNTRY_CODE' => ['phone' => $customData['phone'] ?? ''],
			'PROVIDER_ERROR' => $customData, //bypass for rest
			default => [], // $customData
		};
	}

	/**
	 * Get localized error about file size
	 *
	 * @param array $customData Additional error data
	 *
	 * @return string
	 */
	private function getTooBigError(array $customData): string
	{
		$maxSizeKb = (int)($customData['maxSizeKb'] ?? 0);
		if ($maxSizeKb > 0)
		{
			$maxSizeMb = $maxSizeKb / 1000;
			return (string)Loc::getMessage('SIGN_SERVICE_ERROR_FILE_TOO_BIG_SIZE_MSG_1', [
				'#SIZE#' => $maxSizeMb,
			]);
		}
		return (string)Loc::getMessage('SIGN_SERVICE_ERROR_FILE_TOO_BIG');
	}

	/**
	 * Get localized error about files count
	 *
	 * @param array $customData Additional error data
	 *
	 * @return string
	 */
	private function getTooManyFilesError(array $customData): string
	{
		$count = (int)($customData['maxCount'] ?? 0);
		$count = $count > 0 ? $count : (new Storage())->getImagesCountLimitForBlankUpload();

		return (string)Loc::getMessage('SIGN_SERVICE_ERROR_TOO_MANY_FILES_COUNT', [
			'#COUNT#' => $count,
		]);
	}

	/**
	 * Get localized error about file pages count
	 *
	 * @param array $customData Additional error data
	 *
	 * @return string
	 */
	private function getFileToManyPagesError(array $customData): string
	{
		$count = (int)($customData['maxCount'] ?? 0);
		$count = $count > 0 ? $count : 100;

		return (string)Loc::getMessage('SIGN_SERVICE_ERROR_FILE_TO_MANY_PAGES_COUNT', [
			'#COUNT#' => $count,
		]);
	}

	/**
	 * Get default error message
	 *
	 * @return string
	 */
	public function getDefaultError(string $code = null): string
	{
		return $code !== null
			? (string)Loc::getMessage('SIGN_SERVICE_DEFAULT_ERROR_WITH_CODE', ['#CODE#' => $code])
			: (string)Loc::getMessage('SIGN_SERVICE_DEFAULT_ERROR')
		;
	}
}
