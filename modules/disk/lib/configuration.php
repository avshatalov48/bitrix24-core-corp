<?php


namespace Bitrix\Disk;


use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UI\Viewer\Transformation\Document;
use Bitrix\Main\UI\Viewer\Transformation\Video;

final class Configuration
{
	public const DEFAULT_CACHE_TIME = 60;
	public const REVISION_API       = 8;

	public static function isEnabledDefaultEditInUf()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_allow_edit_object_in_uf', 'Y');
		}
		return $isAllow;
	}

	public static function isEnabledKeepVersion()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_keep_version', 'Y');
		}
		return $isAllow;
	}

	public static function isEnabledDocuments()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = ('Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'documents_enabled', 'N'));
		}

		return $isAllow;
	}

	public static function isEnabledStorageSizeRestriction()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_restriction_storage_size_enabled', 'N');
		}
		return $isAllow;
	}

	public static function getVersionLimitPerFile()
	{
		$value = (int)Option::get(Driver::INTERNAL_MODULE_ID, 'disk_version_limit_per_file', 0);

		return $value?: null;
	}

	public static function isPossibleToShowExternalLinkControl()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_allow_use_external_link', 'Y');
		}
		return $isAllow;
	}

	public static function isEnabledExternalLink()
	{
		return self::isEnabledManualExternalLink();
	}

	public static function isEnabledManualExternalLink()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow =
				static::isPossibleToShowExternalLinkControl() &&
				Bitrix24Manager::isFeatureEnabled('disk_manual_external_link')
			;
		}
		return $isAllow;
	}

	public static function isEnabledAutoExternalLink()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow =
				static::isPossibleToShowExternalLinkControl() &&
				Bitrix24Manager::isFeatureEnabled('disk_auto_external_link')
			;
		}
		return $isAllow;
	}

	public static function isEnabledBoardExternalLink(): bool
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow =
				static::isPossibleToShowExternalLinkControl() &&
				Bitrix24Manager::isFeatureEnabled('disk_board_external_link')
			;
		}
		return (bool)$isAllow;
	}

	public static function isEnabledObjectLock()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_object_lock_enabled', 'N');
		}
		return $isAllow;
	}

	public static function shouldAutoLockObjectOnEdit(): bool
	{
		static $isAllow = null;
		if ($isAllow === null)
		{
			$isAllow = 'Y' === Option::get(Driver::INTERNAL_MODULE_ID, 'disk_auto_lock_on_object_edit', 'N');
		}

		return $isAllow;
	}

	public static function shouldAutoUnlockObjectOnSave(): bool
	{
		static $isAllow = null;
		if ($isAllow === null)
		{
			$isAllow = 'Y' === Option::get(Driver::INTERNAL_MODULE_ID, 'disk_auto_release_lock_on_save', 'N');
		}

		return $isAllow;
	}

	public static function getMinutesToAutoReleaseObjectLock(): int
	{
		return (int)Option::get(Driver::INTERNAL_MODULE_ID, 'disk_time_auto_release_object_lock', 0);
	}

	public static function getDocumentServiceCodeForCurrentUser()
	{
		return UserConfiguration::getDocumentServiceCode();
	}

	public static function canCreateFileByCloud()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_allow_create_file_by_cloud', 'Y');
		}
		return $isAllow;
	}

	public static function canAutoConnectSharedObjects()
	{
		static $isAllow = null;
		if($isAllow === null)
		{
			$isAllow = 'Y' == Option::get(Driver::INTERNAL_MODULE_ID, 'disk_allow_autoconnect_shared_objects', 'N');
		}
		return $isAllow;
	}

	public static function isSuccessfullyConverted()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'successfully_converted',
			false
		) == 'Y';
	}

	public static function getRevisionApi()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'disk_revision_api',
			0
		);
	}

	/**
	 * Returns max file size in bytes.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getMaxFileSizeForIndex()
	{
		$maxIntranetFileSize = (int)Option::get("search", "max_file_size", 0);
		$maxDiskFileSize = (int)Option::get(Driver::INTERNAL_MODULE_ID, 'disk_max_file_size_for_index', 1024);

		return min($maxIntranetFileSize * 1024, $maxDiskFileSize * 1024 * 1024);
	}

	/**
	 * Returns max size of index information which we save in b_disk_object.SEARCH_INDEX.
	 *
	 * @return float|int
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getMaxIndexSize()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'disk_max_index_size',
			1
		) * 1024 * 1024;
	}

	/**
	 * Returns max size of index information which we save in b_disk_object_extended_index.SEARCH_INDEX.
	 *
	 * @return float|int
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getMaxExtendedIndexSize()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'disk_max_extended_index_size',
			1
		) * 1024 * 1024;
	}

	/**
	 * Returns max size of index information which we save in b_disk_object_head_index.SEARCH_INDEX.
	 *
	 * @return float|int
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getMaxHeadIndexSize()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'disk_max_extended_index_size',
			0.001
		) * 1024 * 1024;
	}

	public static function allowIndexFiles()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'disk_allow_index_files',
			'Y'
		) == 'Y';
	}

	public static function allowFullTextIndex()
	{
		return true;
	}

	public static function allowUseExtendedFullText()
	{
		return Option::get(
			Driver::INTERNAL_MODULE_ID,
			'disk_allow_use_extended_fulltext',
			'N'
		) == 'Y';
	}

	public static function getDefaultViewerServiceCode()
	{
		static $service = null;
		if ($service !== null)
		{
			return $service;
		}

		$service = Option::get(Driver::INTERNAL_MODULE_ID, 'default_viewer_service', BitrixHandler::getCode());

		return $service;
	}

	public static function setDefaultViewerService(string $code): void
	{
		Option::set(Driver::INTERNAL_MODULE_ID, 'default_viewer_service', $code);
	}

	/**
	 * @deprecated
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function allowDocumentTransformation()
	{
		return Option::get(
				Driver::INTERNAL_MODULE_ID,
				'disk_allow_document_transformation',
				'N'
			) == 'Y';
	}

	public static function getMaxSizeForDocumentTransformation()
	{
		$documentTransformation = new Document();

		return $documentTransformation->getInputMaxSize();
	}

	/**
	 * @deprecated
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function allowVideoTransformation()
	{
		return Option::get(
				Driver::INTERNAL_MODULE_ID,
				'disk_allow_video_transformation',
				'N'
			) == 'Y';
	}

	/**
	 * Returns maximum size (in bytes) of video files that could be transformed.
	 *
	 * @return int
	 */
	public static function getMaxSizeForVideoTransformation()
	{
		$videoTransformation = new Video();

		return $videoTransformation->getInputMaxSize();
	}

	public static function allowTransformFilesOnOpen()
	{
		static $allow = null;
		if ($allow !== null)
		{
			return $allow;
		}
		$allow = Option::get(
				Driver::INTERNAL_MODULE_ID,
				'disk_transform_files_on_open',
				'N'
			) == 'Y';

		return $allow;
	}

	public static function getFileVersionTtl(): int
	{
		$dayLimit = Bitrix24Manager::getFeatureVariable('disk_file_history_ttl');
		if ($dayLimit !== null)
		{
			return (int)$dayLimit;
		}

		return (int)Option::get(Driver::INTERNAL_MODULE_ID, 'disk_file_history_ttl', -1);
	}

	public static function getTrashCanTtl(): int
	{
		$ttl = Bitrix24Manager::getFeatureVariable('disk_trashcan_ttl');
		if ($ttl !== null)
		{
			return (int)$ttl;
		}

		return (int)Option::get(Driver::INTERNAL_MODULE_ID, 'disk_trashcan_ttl', -1);
	}
}

/**
 * Class UserConfiguration
 * Represents configuration for current user
 * @package Bitrix\Disk
 */
final class UserConfiguration
{
	public static function resetDocumentServiceCode(): void
	{
		$userSettings = \CUserOptions::getOption(
			Driver::INTERNAL_MODULE_ID,
			'doc_service',
			[
				'default' => '',
				'primary' => '',
				'was_reset_to_onlyoffice' => '',
				'was_reset' => '',
			]
		);

		$wasResetToOnlyOffice = $userSettings['was_reset_to_onlyoffice'] ?? '';

		$wasReset = null;
		if (isset($userSettings['was_reset']) && is_int($userSettings['was_reset']))
		{
			$wasReset = (int)$userSettings['was_reset'];
		}

		self::setDocumentServiceOptions('', '', $wasResetToOnlyOffice, $wasReset);
	}

	private static function setDocumentServiceOptions(string $default, string $primary, string $wasResetToOnlyOffice = 'N', int $wasReset = null): void
	{
		\CUserOptions::setOption(
			Driver::INTERNAL_MODULE_ID,
			'doc_service',
			[
				'default' => $default,
				'primary' => $primary,
				'was_reset_to_onlyoffice' => $wasResetToOnlyOffice,
				'was_reset' => $wasReset ?? '',
			]
		);
	}

	public static function resetDocumentServiceForAllUsers(): void
	{
		Option::set(Driver::INTERNAL_MODULE_ID, 'reset_user_edit_service', time());
	}

	public static function getDocumentServiceCode()
	{
		global $USER;
		static $service = null;

		if ($service !== null || !($USER instanceof \CUser) || !$USER->getId() )
		{
			return $service;
		}

		$userSettings = \CUserOptions::getOption(
			Driver::INTERNAL_MODULE_ID,
			'doc_service',
			[
				'default' => '',
				'primary' => '',
				'was_reset_to_onlyoffice' => '',
				'was_reset' => '',
			]
		);

		$defaultService = $userSettings['default'] ?? '';
		$primaryService = $userSettings['primary'] ?? '';
		$wasResetToOnlyOffice = $userSettings['was_reset_to_onlyoffice'] ?? '';
		$wasReset = $userSettings['was_reset'] ?? '';

		if (!$wasResetToOnlyOffice && Option::get(Driver::INTERNAL_MODULE_ID, 'reset_user_edit_service_to_onlyoffice', 'N') === 'Y')
		{
			self::setDocumentServiceOptions(OnlyOfficeHandler::getCode(), '', 'Y');
		}

		$timeWhenResetStart = Option::get(Driver::INTERNAL_MODULE_ID, 'reset_user_edit_service', 'N');
		if ($timeWhenResetStart !== 'N' && $timeWhenResetStart > $wasReset)
		{
			self::setDocumentServiceOptions('', '', 'N', $timeWhenResetStart);
		}

		if ($primaryService === OnlyOfficeHandler::getCode() && OnlyOfficeHandler::isEnabled())
		{
			$service = OnlyOfficeHandler::getCode();

			return $service;
		}
		if (!$primaryService && OnlyOfficeHandler::isEnabled())
		{
			$defaultHandlerForView = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
			if ($defaultHandlerForView instanceof OnlyOfficeHandler)
			{
				$service = OnlyOfficeHandler::getCode();

				return $service;
			}
		}
		if ($primaryService === OnlyOfficeHandler::getCode() && !OnlyOfficeHandler::isEnabled())
		{
			$primaryService = '';
		}

		$service = $primaryService?: $defaultService;

		return $service;
	}

	public static function isSetLocalDocumentService(): bool
	{
		return LocalDocumentController::isLocalService(self::getDocumentServiceCode());
	}
}
