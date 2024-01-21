<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\ChatFactory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Bitrix24\Feature;
use Bitrix\Bitrix24\License\Order;

class CommunicationSettings extends AbstractSettings
{
	public const TYPE = 'communication';
	private bool $isBitrix24;
	private bool $isDiskConverted;
	private array $diskLimitPerFile;

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->isBitrix24 = IsModuleInstalled("bitrix24");
		$this->isDiskConverted = \COption::GetOptionString('disk', 'successfully_converted', false) === 'Y';
		$this->diskLimitPerFile = [
			0 => Loc::getMessage('SETTINGS_UNLIMITED'),
			3 => 3,
			10 => 10,
			25 => 25,
			50 => 50,
			100 => 100,
			500 => 500
		];
		Loader::includeModule('im');
	}

	public function save(): Result
	{
		if (isset($this->data["allow_livefeed_toall"]) && $this->data["allow_livefeed_toall"] <> 'N')
		{
			\COption::SetOptionString("socialnetwork", "allow_livefeed_toall", "Y");
		}
		else
		{
			\COption::SetOptionString("socialnetwork", "allow_livefeed_toall", "N");
		}

		if (
			is_array($this->data["livefeed_toall_rights"])
			&& count($this->data["livefeed_toall_rights"]) > 0
		)
		{
			$valuesToSave = [];
			foreach ($this->data["livefeed_toall_rights"] as $value)
			{
				$valuesToSave[] = ($value == 'UA' ? 'AU' : $value);
			}

			$val = serialize($valuesToSave);
		}
		else
		{
			$val = serialize(["AU"]);
		}
		\COption::SetOptionString("socialnetwork", "livefeed_toall_rights", $val);

		if (isset($this->data["default_livefeed_toall"]) && $this->data["default_livefeed_toall"] <> 'N')
		{
			\COption::SetOptionString("socialnetwork", "default_livefeed_toall", "Y");
		}
		else
		{
			\COption::SetOptionString("socialnetwork", "default_livefeed_toall", "N");
		}

		if (isset($this->data["general_chat_message_leave"]) && $this->data["general_chat_message_leave"] <> 'N')
		{
			\COption::SetOptionString("im", "general_chat_message_leave", true);
		}
		else
		{
			\COption::SetOptionString("im", "general_chat_message_leave", false);
		}

		if (isset($this->data["url_preview_enable"]) && $this->data["url_preview_enable"] <> 'N')
		{
			\COption::SetOptionString("main", "url_preview_enable", "Y");
		}
		else
		{
			\COption::SetOptionString("main", "url_preview_enable", "N");
		}

		if (isset($this->data["create_overdue_chats"]) && $this->data["create_overdue_chats"] <> 'N')
		{
			\COption::SetOptionString("tasks", "create_overdue_chats", "Y");
		}
		else
		{
			\COption::SetOptionString("tasks", "create_overdue_chats", "N");
		}

		if ($this->isDiskConverted)
		{
			if (
				isset($this->data["disk_allow_edit_object_in_uf"])
				&& $this->data["disk_allow_edit_object_in_uf"] <> 'N'
			)
			{
				\COption::SetOptionString("disk", "disk_allow_edit_object_in_uf", "Y");
			}
			else
			{
				\COption::SetOptionString("disk", "disk_allow_edit_object_in_uf", "N");
			}

			if (
				isset($this->data["disk_allow_autoconnect_shared_objects"])
				&& $this->data["disk_allow_autoconnect_shared_objects"] <> 'N'
			)
			{
				\COption::SetOptionString("disk", "disk_allow_autoconnect_shared_objects", "Y");
			}
			else
			{
				\COption::SetOptionString("disk", "disk_allow_autoconnect_shared_objects", "N");
			}
		}
		else
		{
			if (
				isset($this->data["webdav_allow_ext_doc_services_global"])
				&& $this->data["webdav_allow_ext_doc_services_global"] <> 'N'
			)
			{
				\COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "Y");
			}
			else
			{
				\COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N");
			}

			if (
				isset($this->data["webdav_allow_ext_doc_services_local"])
				&& $this->data["webdav_allow_ext_doc_services_local"] <> 'N'
			)
			{
				\COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "Y");
			}
			else
			{
				\COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N");
			}

			if (
				isset($this->data["webdav_allow_autoconnect_share_group_folder"])
				&& $this->data["webdav_allow_autoconnect_share_group_folder"] <> 'N'
			)
			{
				\COption::SetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y");
			}
			else
			{
				\COption::SetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "N");
			}
		}

		if (!$this->isBitrix24 || Feature::isFeatureEnabled("disk_switch_external_link"))
		{
			if (
				isset($this->data["disk_allow_use_external_link"])
				&& $this->data["disk_allow_use_external_link"] <> 'N'
			)
			{
				\COption::SetOptionString("disk", "disk_allow_use_external_link", "Y");
			}
			else
			{
				\COption::SetOptionString("disk", "disk_allow_use_external_link", "N");
			}
		}

		if (!$this->isBitrix24 || Feature::isFeatureEnabled("disk_object_lock_enabled"))
		{
			if (isset($this->data["disk_object_lock_enabled"]) && $this->data["disk_object_lock_enabled"] <> 'N')
			{
				\COption::SetOptionString("disk", "disk_object_lock_enabled", "Y");
			}
			else
			{
				\COption::SetOptionString("disk", "disk_object_lock_enabled", "N");
			}
		}

		if (
			!$this->isBitrix24
			|| $this->isBitrix24
			&& Feature::isFeatureEnabled("disk_allow_use_extended_fulltext")
		)
		{
			if (isset($this->data["disk_allow_use_extended_fulltext"])
				&& $this->data["disk_allow_use_extended_fulltext"] <> 'N')
			{
				\COption::SetOptionString("disk", "disk_allow_use_extended_fulltext", "Y");
			}
			else
			{
				\COption::SetOptionString("disk", "disk_allow_use_extended_fulltext", "N");
			}
		}

		if (
			!$this->isBitrix24
			|| $this->isBitrix24
			&& Feature::isFeatureEnabled("disk_version_limit_per_file"))
		{
			if (
				$this->data["disk_version_limit_per_file"] <> ''
				&& in_array($this->data["disk_version_limit_per_file"], array_keys($this->diskLimitPerFile))
			)
			{
				\COption::SetOptionString("disk", "disk_version_limit_per_file", $this->data["disk_version_limit_per_file"]);
			}
		}

		$allowedCodes = [];
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		foreach ($documentHandlersManager->getHandlersForView() as $handler)
		{
			$allowedCodes[] = $handler::getCode();
		}
		if (
			isset($this->data["default_viewer_service"])
			&& $this->data["default_viewer_service"] <> ''
			&& in_array($this->data["default_viewer_service"], $allowedCodes)
		)
		{
			\COption::SetOptionString("disk", "default_viewer_service", $this->data["default_viewer_service"]);
		}

		if (isset($this->data["rating_text_like_y"]) && $this->data["rating_text_like_y"] !== '')
		{
			\COption::SetOptionString("main", "rating_text_like_y", htmlspecialcharsbx($this->data["rating_text_like_y"]));
		}

		if ($this->isBitrix24)
		{
			if (isset($this->data["general_chat_message_leave"]) && $this->data["general_chat_message_admin_rights"] <> 'N')
			{
				\COption::SetOptionString("im", "general_chat_message_admin_rights", true);
			}
			else
			{
				\COption::SetOptionString("im", "general_chat_message_admin_rights", false);
			}
		}


		//im chat
		if (Loader::includeModule('im'))
		{
			if (isset($this->data['allow_post_general_chat']) && $this->data['allow_post_general_chat'] === 'N')
			{
				$this->data["general_chat_can_post"] = Chat::MANAGE_RIGHTS_NONE;
				unset($this->data['allow_post_general_chat']);
			}
			if (\COption::GetOptionString("im", "im_general_chat_new_rights") !== 'Y')
			{
				if (isset($this->data["allow_general_chat_toall"]))
				{
					$valuesToSave = [];
					if (is_array($this->data["imchat_toall_rights"]))
					{
						foreach ($this->data["imchat_toall_rights"] as $key => $value)
						{
							$valuesToSave[] = ($value === 'UA' ? 'AU' : $value);
						}
					}

					if (in_array('AU', $valuesToSave) || empty($valuesToSave))
					{
						\CIMChat::SetAccessToGeneralChat(true);
					}
					else
					{
						\CIMChat::SetAccessToGeneralChat(false, $valuesToSave);
					}
				}
				else
				{
					\CIMChat::SetAccessToGeneralChat(false);
				}
			}
			else
			{
				if (isset($this->data["general_chat_can_post"]))
				{
					$generalChat = ChatFactory::getInstance()->getGeneralChat();
					if ($generalChat)
					{
						$generalChat->setCanPost($this->data["general_chat_can_post"]);
						if ($this->data["general_chat_can_post"] === Chat::MANAGE_RIGHTS_MANAGERS)
						{
							if (isset($this->data["imchat_toall_rights"]))
							{
								$managerIds = array_map(function ($userCode) {
									$matches = [];
									if (preg_match('/^U(\d+)$/', $userCode, $matches))
									{
										return $matches[1];
									}
								}, $this->data["imchat_toall_rights"]);
								$generalChat->setManagers($managerIds);
							}
						}
						$generalChat->save();
					}
				}
			}
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];
		$data['allow_livefeed_toall'] = \COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y");

		$defaultRightsSerialized = 'a:1:{i:0;s:2:"AU";}';
		$val = \COption::GetOptionString("socialnetwork", "livefeed_toall_rights", $defaultRightsSerialized);
		$toAllRights = unserialize($val, ["allowed_classes" => false]);
		if (!$toAllRights)
		{
			$toAllRights = unserialize($defaultRightsSerialized, ["allowed_classes" => false]);
		}
		$data['arToAllRights'] = static::processOldAccessCodes($toAllRights);

		if (Loader::includeModule("im"))
		{
			if (!method_exists('\Bitrix\Im\V2\Chat\GeneralChat', 'getRightsForIntranetConfig'))
			{
				$chatToAllRights = [];
				$imAllowRights = \COption::GetOptionString("im", "allow_send_to_general_chat_rights");
				if (!empty($imAllowRights))
				{
					$chatToAllRights = explode(",", $imAllowRights);
				}
				$data['arChatToAllRights'] = static::processOldAccessCodes($chatToAllRights);
			}
			else
			{
				$generalChat = ChatFactory::getInstance()->getGeneralChat();
				if ($generalChat)
				{
					$generalChatRights = $generalChat->getRightsForIntranetConfig();
					$data = array_merge($data, $generalChatRights);
				}
			}

			$data['allow_post_general_chat'] = ($generalChatRights['generalChatCanPost'] ?? null) === 'NONE'
				? 'N'
				: 'Y';
			$values = [];
			foreach ($generalChatRights['generalChatCanPostList'] ?? [] as $value => $name)
			{
				if ($value === 'NONE')
				{
					continue;
				}
				$values[] = [
					'value' => $value,
					'name' => $name,
					'selected' => $value === ($generalChatRights['generalChatCanPost'] ?? null)
				];
			}
			$data['general_chat_can_post'] = [
				'name' => 'general_chat_can_post',
				'values' => $values,
				'current' => $generalChatRights['generalChatCanPost'] ?? null,
			];
		}

		$data['default_livefeed_toall'] = \COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y");

		if (Loader::includeModule("im"))
		{
			$data['general_chat_message_leave'] = \COption::GetOptionString("im", "general_chat_message_leave") ? 'Y' : 'N';
		}
		$data['url_preview_enable'] = \COption::GetOptionString("main", "url_preview_enable", "N");
		if (Loader::includeModule("tasks") && Loader::includeModule("im"))
		{
			$data['create_overdue_chats'] = \COption::GetOptionString("tasks", "create_overdue_chats", "N");
		}
		if ($this->isDiskConverted)
		{
			$data['disk_allow_edit_object_in_uf'] = \COption::GetOptionString("disk", "disk_allow_edit_object_in_uf", "Y");
			$data['disk_allow_autoconnect_shared_objects'] = \COption::GetOptionString("disk", "disk_allow_autoconnect_shared_objects", "N");
		}
		else
		{
			$data['webdav_allow_ext_doc_services_global'] = \COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N");
			$data['webdav_allow_ext_doc_services_local'] = \COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N");
			$data['webdav_allow_autoconnect_share_group_folder'] = \COption::GetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y");
		}

		$data['disk_allow_use_external_link'] = [
			'name' => 'disk_allow_use_external_link',
			'value' => \COption::GetOptionString("disk", "disk_allow_use_external_link", "Y"),
			'is_enable' => !$this->isBitrix24
				|| $this->isBitrix24
				&& Feature::isFeatureEnabled("disk_switch_external_link"),
		];

		$data['disk_allow_use_extended_fulltext'] = [
			'name' => 'disk_allow_use_extended_fulltext',
			'value' => \COption::GetOptionString("disk", "disk_allow_use_extended_fulltext", "N"),
			'is_enable' => !$this->isBitrix24
				|| $this->isBitrix24
				&& Feature::isFeatureEnabled("disk_allow_use_extended_fulltext"),
		];

		$data['disk_object_lock_enabled'] = [
			'name' => 'disk_object_lock_enabled',
			'value' => \COption::GetOptionString("disk", "disk_object_lock_enabled", "N"),
			'is_enable' => !$this->isBitrix24
				|| $this->isBitrix24
				&& Feature::isFeatureEnabled("disk_object_lock_enabled"),
		];

		if (Loader::includeModule("disk"))
		{
			$diskLimitCurrent = \COption::GetOptionInt("disk", 'disk_version_limit_per_file', 0);
			$fileLimitedValues = [];
			foreach ($this->diskLimitPerFile as $value => $name)
			{
				$fileLimitedValues[] = [
					'value' => $value,
					'name' => $name,
					'selected' => $value === $diskLimitCurrent
				];
			}

			$hints = [];
			$diskFileHistoryTtl = $this->isBitrix24 ? (string)Feature::getVariable('disk_file_history_ttl') : null;
			if ($this->isBitrix24)
			{
				$hintMessage = Loc::getMessage('SETTINGS_LIMIT_MAX_TIME_IN_DOCUMENT_HISTORY', [
					'#NUM#' => Feature::getVariable('disk_file_history_ttl')
				]);
				foreach ($this->diskLimitPerFile as $value => $name)
				{
					$hints[$value] = $hintMessage;
				}
			}
			$data['DISK_LIMIT_PER_FILE'] = [
				'name' => 'disk_version_limit_per_file',
				'values' => $fileLimitedValues,
				'current' => $diskLimitCurrent,
				'diskFileHistoryTtl' => $diskFileHistoryTtl,
				'hintTitle' => Loc::getMessage('SETTINGS_LIMIT_MAX_TIME_IN_DOCUMENT_HISTORY_TITLE'),
				'hints' => $hints,
				'is_enable' => !$this->isBitrix24
					|| $this->isBitrix24
					&& Feature::isFeatureEnabled("disk_version_limit_per_file"),
			];


			$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
			$optionList = [];
			$currentValue = Configuration::getDefaultViewerServiceCode();
			foreach ($documentHandlersManager->getHandlersForView() as $handler)
			{
				$optionList[] = [
					'value' => $handler::getCode(),
					'name' => $handler::getName(),
					'selected' => $handler::getCode() === $currentValue
				];
			}
			unset($handler);
			$data["DISK_VIEWER_SERVICE"] = [
				'name' => 'default_viewer_service',
				'values' => $optionList,
				'current' => $currentValue,
				'hints' => [],
			];
		}

		$data['ratingTextLikeY'] = [
			'name' => 'rating_text_like_y',
			'value' => \COption::GetOptionString("main", "rating_text_like_y", "")
		];

		if ($this->isBitrix24 && Loader::includeModule("im"))
		{
			$data['general_chat_message_admin_rights'] = \COption::GetOptionString("im", "general_chat_message_admin_rights", true) ? 'Y' : 'N';
		}

		return new static($data);
	}

	public static function processOldAccessCodes($rightsList): array
	{
		static $rootDepartmentId = null;

		if (!is_array($rightsList))
		{
			return [];
		}

		if ($rootDepartmentId === null)
		{
			$rootDepartmentId = \COption::GetOptionString("main", "wizard_departament", false, SITE_DIR, true);
			if (
				empty($rootDepartmentId)
				&& Loader::includeModule('iblock')
			)
			{
				$iblockId = \COption::GetOptionInt('intranet', 'iblock_structure', false);
				if ($iblockId > 0)
				{
					$res = \CIBlockSection::getList(
						array(
							'LEFT_MARGIN' => 'ASC',
						),
						array(
							'IBLOCK_ID' => $iblockId,
							'ACTIVE' => 'Y',
						),
						false,
						array('ID')
					);
					if (
						!empty($res)
						&& ($rootSection = $res->fetch())
					)
					{
						$rootDepartmentId = $rootSection['ID'];
					}
				}
			}
		}

		foreach($rightsList as $key => $value)
		{
			if ($value == 'AU')
			{
				unset($rightsList[$key]);
				$rightsList[] = 'UA';
			}
			elseif (preg_match('/^IU(\d+)$/i', $value, $matches))
			{
				unset($rightsList[$key]);
				$rightsList[] = 'U'.$matches[1];
			}
			elseif (
				!empty($rootDepartmentId)
				&& ($value == 'DR'.$rootDepartmentId)
			)
			{
				unset($rightsList[$key]);
				$rightsList[] = 'UA';
			}
		}

		return array_unique($rightsList);
	}
}