<?php

namespace Bitrix\Disk\Integration;

use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Main\ModuleManager;

final class MessengerCall
{
	public static function isAvailableDocuments(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24') || OnlyOfficeHandler::isEnabled();
	}

	public static function isEnabledResumes(): bool
	{
		return OnlyOfficeHandler::isEnabled() && Bitrix24Manager::isFeatureEnabled('disk_im_call_resume');
	}

	public static function isEnabledDocuments(): bool
	{
		return OnlyOfficeHandler::isEnabled() && Bitrix24Manager::isFeatureEnabled('disk_onlyoffice_edit');
	}

	public static function getInfoHelperCodeForDocuments(): string
	{
		return 'limit_video_calls_documents';
	}

	public static function getInfoHelperCodeForResume(): string
	{
		return 'limit_office_call_meeting_resume';
	}
}