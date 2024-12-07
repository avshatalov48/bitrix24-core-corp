<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\ChatFactory;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\Settings\Controls\Field;
use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Controls\Selector;
use Bitrix\Intranet\Settings\Controls\Switcher;
use Bitrix\Main\Config\Option;
use Bitrix\Intranet\Settings\Search\SearchEngine;
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
			\COption::SetOptionString("main", "rating_text_like_y", $this->data["rating_text_like_y"]);
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

			if (
				class_exists('\Bitrix\Im\V2\Chat\GeneralChannel')
				&& method_exists('\Bitrix\Im\V2\Chat\ChatFactory', 'getGeneralChannel')
				&& ChatFactory::getInstance()->getGeneralChannel() !== null

			)
			{
				if (isset($this->data['allow_post_general_channel']) && $this->data['allow_post_general_channel'] === 'N')
				{
					$this->data["general_channel_can_post"] = Chat::MANAGE_RIGHTS_NONE;
					unset($this->data['allow_post_general_channel']);
				}

				if (isset($this->data["general_channel_can_post"]))
				{
					$generalChannel = ChatFactory::getInstance()->getGeneralChannel();
					if ($generalChannel)
					{
						$generalChannel->setCanPost($this->data["general_channel_can_post"]);
						if ($this->data["general_channel_can_post"] === Chat::MANAGE_RIGHTS_MANAGERS)
						{
							if (isset($this->data["imchannel_toall_rights"]))
							{
								$managerIds = array_map(function ($userCode) {
									$matches = [];
									if (preg_match('/^U(\d+)$/', $userCode, $matches))
									{
										return $matches[1];
									}
								}, $this->data["imchannel_toall_rights"]);
								$generalChannel->setManagers($managerIds);
							}
						}
						$generalChannel->save();
					}
				}
			}
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];

		$data['allow_livefeed_toall'] = new Switcher(
			'settings-communication-field-allow_livefeed_toall',
			'allow_livefeed_toall',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_POST_FEED'),
			Option::get('socialnetwork', 'allow_livefeed_toall', 'Y'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_POST_FEED_ON')
			]
		);

		$defaultRightsSerialized = 'a:1:{i:0;s:2:"AU";}';
		$val = \COption::GetOptionString("socialnetwork", "livefeed_toall_rights", $defaultRightsSerialized);
		$toAllRights = unserialize($val, ["allowed_classes" => false]);
		if (!$toAllRights)
		{
			$toAllRights = unserialize($defaultRightsSerialized, ["allowed_classes" => false]);
		}
		$data['arToAllRights'] = static::processOldAccessCodes($toAllRights);

		$data['sectionFeed'] = new Section(
			'settings-communication-section-feed',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_NEWS_FEED'),
			'ui-icon-set --feed-bold'
		);
		$data['sectionChats'] = new Section(
			'settings-communication-section-chats',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CHATS'),
			'ui-icon-set --chats-1',
			false
		);
		$data['sectionChannels'] = new Section(
			'settings-communication-section-channels',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CHANNELS') ?? '',
			'ui-icon-set --chats-1',
			false
		);
		$data['sectionDisk'] = new Section(
			'settings-communication-section-disk',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_DISK'),
			'ui-icon-set --disk',
			false
		);

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

			$data['allow_post_general_chat'] = new Switcher(
				'settings-communication-field-allow_post_general_chat',
				'allow_post_general_chat',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHAT'),
				($generalChatRights['generalChatCanPost'] ?? null) === 'NONE' ? 'N' : 'Y',
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_POST_GEN_CHAT_ON_MSGVER_1')
				],
				helpDesk: 'redirect=detail&code=18213254'
			);


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

			$data['general_chat_can_post'] = new Selector(
				'settings-communication-field-general_chat_can_post',
				'general_chat_can_post',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHAT_LIST'),
				$values,
				$generalChatRights['generalChatCanPost'] ?? null
			);


			if (
				class_exists('\Bitrix\Im\V2\Chat\GeneralChannel')
				&& method_exists('\Bitrix\Im\V2\Chat\ChatFactory', 'getGeneralChannel')
				&& ChatFactory::getInstance()->getGeneralChannel() !== null
			)
			{
				$data['availableGeneralChannel'] = 'Y';

				$generalChannel = ChatFactory::getInstance()->getGeneralChannel();
				if (isset($generalChannel))
				{
					$generalChannelRights = $generalChannel->getRightsForIntranetConfig();
					$data = array_merge($data, $generalChannelRights);
				}

				$data['allow_post_general_channel'] = new Switcher(
					'settings-communication-field-allow_post_general_channel',
					'allow_post_general_channel',
					Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHANNEL') ?? '',
					($generalChannelRights['generalChannelCanPost'] ?? null) === 'NONE' ? 'N' : 'Y',
					[
						'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_POST_GEN_CHANNEL') ?? ''
					],
					helpDesk: 'redirect=detail&code=20963046#1'
				);

				$values = [];
				foreach ($generalChannelRights['generalChannelCanPostList'] ?? [] as $value => $name)
				{
					if ($value === 'NONE')
					{
						continue;
					}
					$values[] = [
						'value' => $value,
						'name' => $name,
						'selected' => $value === ($generalChannelRights['generalChannelCanPost'] ?? null)
					];
				}

				$data['general_channel_can_post'] = new Selector(
					'settings-communication-field-general_channel_can_post',
					'general_channel_can_post',
					Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHANNEL_LIST') ?? '',
					$values,
					$generalChannelRights['generalChannelCanPost'] ?? null
				);
			}
		}

		$data['default_livefeed_toall'] = new Switcher(
			'settings-communication-field-default_livefeed_toall',
			'default_livefeed_toall',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_PUBLISH_TO_ALL_DEFAULT'),
			Option::get('socialnetwork', 'default_livefeed_toall', 'Y'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_PUBLISH_TO_ALL_DEFAULT_ON')
			]
		);

		if (Loader::includeModule("im"))
		{

			$data['general_chat_message_leave'] = new Switcher(
				'settings-communication-field-general_chat_message_leave',
				'general_chat_message_leave',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_LEAVE_MESSAGE'),
				Option::get("im", "general_chat_message_leave") ? 'Y' : 'N',
			);
		}

		$data['url_preview_enable'] = new Switcher(
			'settings-communication-field-url_preview_enable',
			'url_preview_enable',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_URL_PREVIEW'),
			Option::get('main', 'url_preview_enable', 'N'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_URL_PREVIEW_ON')
			]
		);

		if (Loader::includeModule("tasks") && Loader::includeModule("im"))
		{
			$data['create_overdue_chats'] = new Switcher(
				'settings-communication-field-create_overdue_chats',
				'create_overdue_chats',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_CREATE_OVERDUE_CHATS'),
				Option::get('tasks', 'create_overdue_chats', 'N'),
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_OVERDUE_CHATS_ON_MSGVER_1')
				],
				helpDesk: 'redirect=detail&code=18213270'
			);
		}

		if ($this->isDiskConverted)
		{
			$data['disk_allow_edit_object_in_uf'] = new Switcher(
				'settings-communication-field-disk_allow_edit_object_in_uf',
				'disk_allow_edit_object_in_uf',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_EDIT_DOC'),
				Option::get('disk', 'disk_allow_edit_object_in_uf', 'Y'),
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_EDIT_DOC_ON')
				]
			);

			$data['disk_allow_autoconnect_shared_objects'] = new Switcher(
				'settings-communication-field-disk_allow_autoconnect_shared_objects',
				'disk_allow_autoconnect_shared_objects',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_AUTO_CONNECT_DISK'),
				Option::get('disk', 'disk_allow_autoconnect_shared_objects', 'N'),
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_AUTO_CONNECT_DISK_ON_MSGVER_1')
				],
				helpDesk: 'redirect=detail&code=18213280'
			);
		}
		else
		{
			$data['webdav_allow_ext_doc_services_global'] = \COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N");
			$data['webdav_allow_ext_doc_services_local'] = \COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N");
			$data['webdav_allow_autoconnect_share_group_folder'] = \COption::GetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y");
		}

		$data['disk_allow_use_external_link'] = new Switcher(
			'settings-communication-field-disk_allow_use_external_link',
			'disk_allow_use_external_link',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_PUBLIC_LINK'),
			Option::get('disk', 'disk_allow_use_external_link', 'Y'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_PUBLIC_LINK_ON_MSGVER_1')
			],
			isEnable: !$this->isBitrix24
				|| $this->isBitrix24
				&& Feature::isFeatureEnabled("disk_switch_external_link"),
			helpDesk: 'redirect=detail&code=5390599',
		);

		$data['disk_allow_use_extended_fulltext'] = new Switcher(
			'settings-communication-field-disk_allow_use_extended_fulltext',
			'disk_allow_use_extended_fulltext',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_SEARCH_DOC'),
			Option::get('disk', 'disk_allow_use_extended_fulltext', 'N'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_SEARCH_DOC_ON_MSGVER_1')
			],
			isEnable: !$this->isBitrix24
			|| $this->isBitrix24
			&& Feature::isFeatureEnabled("disk_allow_use_extended_fulltext"),
			helpDesk:'redirect=detail&code=18213348',
		);

		$data['disk_object_lock_enabled'] = new Switcher(
			'settings-communication-field-disk_object_lock_enabled',
			'disk_object_lock_enabled',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_BLOCK_DOC'),
			Option::get('disk', 'disk_object_lock_enabled', 'N'),
			[
				'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_ALLOW_BLOCK_DOC_ON_MSGVER_1')
			],
			isEnable: !$this->isBitrix24
			|| $this->isBitrix24
			&& Feature::isFeatureEnabled("disk_object_lock_enabled"),
			helpDesk:'redirect=detail&code=20962214',
		);

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

			$hints['hintTitle'] = Loc::getMessage('SETTINGS_LIMIT_MAX_TIME_IN_DOCUMENT_HISTORY_TITLE');
			$data['DISK_LIMIT_PER_FILE'] = new Selector(
				'settings-communication-field-disk_limit_per_file',
				'disk_version_limit_per_file',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAX_FILE_LIMIT'),
				$fileLimitedValues,
				$diskLimitCurrent,
				hints: $hints,
				isEnable: !$this->isBitrix24
					|| $this->isBitrix24
					&& Feature::isFeatureEnabled("disk_version_limit_per_file"),
			);


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
			$data["DISK_VIEWER_SERVICE"] = new Selector(
				'settings-communication-field-default_viewer_service',
				'default_viewer_service',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_FILE_VIEWER'),
				$optionList,
				$currentValue
			);
		}

		$data['ratingTextLikeY'] = new Field(
			'settings-communication-field-ratingTextLikeY',
			'rating_text_like_y',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_LIKE_INPUT'),
			'text',
			value: Option::get('main', 'rating_text_like_y', '')
		);

		if ($this->isBitrix24 && Loader::includeModule("im"))
		{
			$data['general_chat_message_admin_rights'] = new Switcher(
				'settings-communication-field-general_chat_message_admin_rights',
				'general_chat_message_admin_rights',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_ADMIN_MESSAGE'),
				Option::get('im', 'general_chat_message_admin_rights', true) ? 'Y' : 'N',
			);
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
			$departmentRepository = ServiceContainer::getInstance()->departmentRepository();
			$department = $departmentRepository->getRootDepartment();
			$rootDepartmentId = $department->getId();
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

	public function find(string $query): array
	{
		$index = [];
		if ($this->isBitrix24)
		{
			$index['general_chat_message_admin_rights'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_ADMIN_MESSAGE');
		}

		$searchEngine = SearchEngine::initWithDefaultFormatter($index + [
			//sections
			'settings-communication-section-feed' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_NEWS_FEED'),
			'settings-communication-section-chats' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CHATS'),
				'settings-communication-section-channels' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_CHANNELS') ?? '',
			'settings-communication-section-disk' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_DISK'),
			//fields
			'allow_livefeed_toall' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_POST_FEED'),
			'default_livefeed_toall' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_PUBLISH_TO_ALL_DEFAULT'),
			'rating_text_like_y' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_LIKE_INPUT'),
			'allow_post_general_chat' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHAT'),
			'allow_post_general_channel' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_POST_GEN_CHANNEL') ?? '',
			'general_chat_message_leave' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_LEAVE_MESSAGE'),
			'url_preview_enable' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_URL_PREVIEW'),
			'create_overdue_chats' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_CREATE_OVERDUE_CHATS'),
			'default_viewer_service' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_FILE_VIEWER'),
			'disk_version_limit_per_file' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAX_FILE_LIMIT'),
			'disk_allow_edit_object_in_uf' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_EDIT_DOC'),
			'disk_allow_autoconnect_shared_objects' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_AUTO_CONNECT_DISK'),
			'disk_allow_use_external_link' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_PUBLIC_LINK'),
			'disk_object_lock_enabled' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_BLOCK_DOC'),
			'disk_allow_use_extended_fulltext' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALLOW_SEARCH_DOC'),
		]);

		return $searchEngine->find($query);
	}
}