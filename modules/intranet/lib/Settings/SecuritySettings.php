<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Bitrix24\Feature;
use Bitrix\Bitrix24\IpAccess\Rights;
use Bitrix\Intranet\Service\MobileAppSettings;
use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Controls\Selector;
use Bitrix\Intranet\Settings\Controls\Switcher;
use Bitrix\Intranet\Settings\Search\SearchEngine;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Security;

class SecuritySettings extends AbstractSettings
{
	public const TYPE = 'security';

	private bool $isCloud;
	private array $deviceHistoryDays;
	private MobileAppSettings $mobileAppService;

	public function __construct(array $data = [])
	{
		parent::__construct($data);

		$this->isCloud = IsModuleInstalled('bitrix24');
		$this->deviceHistoryDays = [
			30 => '30',
			60 => '60',
			90 => '90',
			120 => '120',
			150 => '150',
			180 => '180',
		];

		if (!$this->isCloud)
		{
			$this->deviceHistoryDays[0] = Loc::getMessage('INTRANET_SETTINGS_SECURITY_UNLIMITED_DAYS');
		}
		$this->mobileAppService = ServiceLocator::getInstance()->get('intranet.option.mobile_app');
	}

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();

		if (!Loader::includeModule('security'))
		{
			$errors->setError(new Error(Loc::getMessage('INTRANET_SETTINGS_SECURITY_MODULE_ERROR')));
		}

		foreach ($this->data as $inputName => $ipStringList)
		{
			if (preg_match('/^SECURITY_IP_ACCESS_.+_IP$/', $inputName))
			{
				if ($ipStringList)
				{
					$ipList = explode(',', $ipStringList);

					foreach ($ipList as $ip)
					{
						$ip = trim($ip);

						if (mb_strpos($ip, '-') !== false)
						{
							$ipRange = explode('-', $ip);

							if (!filter_var($ipRange[0], FILTER_VALIDATE_IP) || !filter_var($ipRange[1], FILTER_VALIDATE_IP))
							{
								$errors->setError(new Error(
									Loc::getMessage('INTRANET_SETTINGS_SECURITY_WRONG_IP_ERROR'),
									0,
									[
										'page' => $this->getType(),
										'field' => $inputName,
									])
								);

								break;
							}
						}
						else
						{
							if (!filter_var($ip, FILTER_VALIDATE_IP))
							{
								$errors->setError(new Error(
										Loc::getMessage('INTRANET_SETTINGS_SECURITY_WRONG_IP_ERROR'),
										0,
										[
											'page' => $this->getType(),
											'field' => $inputName,
										])
								);

								break;
							}
						}
					}
				}
			}
		}

		return $errors;
	}

	public function save(): Result
	{
		$this->saveOtpSettings();
		$this->saveIpAccessRights();
		$this->saveDeviceHistorySettings();

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];

		$otpData = $this->getOtpSettings();

		if (!empty($otpData))
		{
			$data['SECURITY_OTP'] = $otpData['SECURITY_OTP'];
			$data['SECURITY_OTP_ENABLED'] = $otpData['SECURITY_OTP_ENABLED'];
			$data['SECURITY_IS_USER_OTP_ACTIVE'] = $otpData['SECURITY_IS_USER_OTP_ACTIVE'];
			$data['SECURITY_OTP_DAYS'] = [];
			$data['SEND_OTP_PUSH'] = Option::get('intranet', 'send_otp_push', 'N') === 'Y';
			$data['SECURITY_OTP_PATH'] = $otpData['SECURITY_OTP_PATH'];

			for ($i = 5; $i <= 10; $i++)
			{
				$current = $i === $otpData['SECURITY_OTP_DAYS'];

				if ($current)
				{
					$data['SECURITY_OTP_DAYS']['CURRENT'] = true;
				}

				$data['SECURITY_OTP_DAYS']['ITEMS'][] = [
					'value' => $i,
					'name' => FormatDate('ddiff', time() - 60 * 60 * 24 * $i),
					'selected' => $current,
				];
			}
		}

		$data['IS_BITRIX_24'] = $this->isCloud;
		$data['IP_ACCESS_RIGHTS_LABEL'] = Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DEVISE_HISTORY_CLEANUP_DAYS');
		$data['IP_ACCESS_RIGHTS_ENABLED'] = ($data['IS_BITRIX_24'] && Feature::isFeatureEnabled('ip_access_rights'));

		if ($data['IP_ACCESS_RIGHTS_ENABLED'])
		{
			$data['IP_ACCESS_RIGHTS'] = $this->getIpAccessRights();
		}

		$data['DEVICE_HISTORY_SETTINGS'] = $this->getDeviceHistorySettings();

		if (!$this->isCloud)
		{
			$data['EVENT_LOG'] = '/services/event_list.php';
		}
		elseif (
			\Bitrix\Main\Loader::includeModule('bitrix24')
			&& (\CBitrix24::IsLicensePaid() || \CBitrix24::IsNfrLicense() || \CBitrix24::IsDemoLicense())
		)
		{
			$data['EVENT_LOG'] = '/settings/configs/event_log.php';
		}

		if ($otpData['SECURITY_OTP_ENABLED'] ?? false)
		{
			$data['sectionOtp'] = new Section(
				'settings-security-section-otp',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_OTP'),
				'ui-icon-set --calendar-1'
			);

			$data['fieldSecurityOtp'] = new Switcher(
				'settigns-security-field-otp',
				'SECURITY_OTP',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SECURITY_OTP'),
				$otpData['SECURITY_OTP'] ? 'Y' : 'N'
			);
		}


		$data['sectionHistory'] = new Section(
			'settings-security-section-history',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_DEVICES_HISTORY'),
			'ui-icon-set --clock-with-arrow',
			!(isset($otpData['SECURITY_OTP_ENABLED']) && $otpData['SECURITY_OTP_ENABLED']),
			$data['DEVICE_HISTORY_SETTINGS']->isEnable(),
			bannerCode: 'limit_office_login_history'
		);

		$data['sectionEventLog'] = new Section(
			'settings-security-section-event_log',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_EVENT_LOG'),
			'ui-icon-set --list',
			false,
			isset($data['EVENT_LOG']),
			bannerCode: 'limit_office_login_log',
		);

		if ($this->isCloud)
		{
			$data['sectionAccessIp'] = new Section(
				'settings-security-section-access_ip',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_ACCESS_IP'),
				'ui-icon-set --attention-i-circle',
				false,
				$data['IP_ACCESS_RIGHTS_ENABLED'],
				bannerCode: 'limit_admin_ip',
			);
			$data['sectionBlackList'] = new Section(
				'settings-security-section-black_list',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_BLACK_LIST'),
				'ui-icon-set --cross-50',
				false,
				canCollapse: false
			);
		}

		if ($this->mobileAppService->isReady())
		{
			$data['sectionMobileApp'] = new Section(
				'settings-security-section-mobile_app',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_MOBILE_APP'),
				'ui-icon-set --mobile-2',
				false
			);

			$data['switcherDisableCopy'] = new Switcher(
				'settings-employee-field-allow_register',
				'disable_copy_text',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DISABLE_COPY'),
				$this->mobileAppService->canCopyText() ? 'N' : 'Y',
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_DISABLE_COPY'),
				]
			);

			$data['switcherDisableScreenshot'] = new Switcher(
				'settings-employee-field-allow_screenshot',
				'disable_copy_screenshot',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DISABLE_SCREENSHOT'),
				$this->mobileAppService->canTakeScreenshot() ? 'N' : 'Y',
				[
					'on' => Loc::getMessage('INTRANET_SETTINGS_FIELD_HINT_DISABLE_SCREENSHOT'),
				]
			);
		}

		return new static($data);
	}

	private function getDeviceHistorySettings(): Selector
	{
		$deviseHistoryDaysEnabled = (
			!$this->isCloud
			|| Feature::isFeatureEnabled('user_login_history')
		);

		$deviseHistoryDaysCurrent = $deviseHistoryDaysEnabled ? (int)Option::get('main', 'device_history_cleanup_days', 180) : 180;

		$deviseHistoryDaysValues = [];

		foreach ($this->deviceHistoryDays as $value => $name)
		{
			$deviseHistoryDaysValues[] = [
				'value' => $value,
				'name' => $name,
				'selected' => $value === $deviseHistoryDaysCurrent
			];
		}

		return new Selector(
			'settings-security-field-history_cleanup_days',
			'DEVICE_HISTORY_CLEANUP_DAYS',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DEVISE_HISTORY_CLEANUP_DAYS'),
			$deviseHistoryDaysValues,
			$deviseHistoryDaysCurrent,
			isEnable: $deviseHistoryDaysEnabled
		);
	}

	private function saveDeviceHistorySettings(): void
	{
		global $USER;

		if ($USER->isAdmin())
		{
			$days = (int)$this->data['DEVICE_HISTORY_CLEANUP_DAYS'];
			if ($days >= 0)
			{
				if ($this->isCloud && ($days === 0 || $days > 180))
				{
					return;
				}
				Option::set('main', 'device_history_cleanup_days', $days);
			}
		}
	}

	private function getOtpSettings(): array
	{
		$currentUser = CurrentUser::get();

		$result = [];

		if (!Loader::includeModule('security'))
		{
			return $result;
		}

		$result['SECURITY_MODULE'] = true;
		$result['SECURITY_OTP_ENABLED'] = Security\Mfa\Otp::isOtpEnabled();
		$result['SECURITY_IS_USER_OTP_ACTIVE'] = \CSecurityUser::IsUserOtpActive($currentUser->getId());
		$result['SECURITY_OTP_DAYS'] = Security\Mfa\Otp::getSkipMandatoryDays();
		$result['SECURITY_OTP'] = Security\Mfa\Otp::isMandatoryUsing();
		$result['SECURITY_OTP_PATH'] = SITE_DIR . 'company/personal/user/' . $currentUser->getId() . '/common_security/?page=otpConnected';

		if ($result['SECURITY_OTP'] && $this->isCloud)
		{
			$otpRights = Security\Mfa\Otp::getMandatoryRights();
			$adminGroup = 'G1';
			$employeeGroup = 'G' . \CBitrix24::getEmployeeGroupId();

			if (!in_array($adminGroup, $otpRights, true) || !in_array($employeeGroup, $otpRights, true))
			{
				$result['SECURITY_OTP'] = false;
			}
		}

		return $result;
	}

	private function saveOtpSettings(): void
	{
		if ($this->isCloud)
		{
			//otp is always mandatory for integrator group in cloud
			$otpRights = Security\Mfa\Otp::getMandatoryRights();
			$employeeGroup = 'G' . \CBitrix24::getEmployeeGroupId();
			$adminGroup = 'G1';

			if (isset($this->data['SECURITY_OTP']) && $this->data['SECURITY_OTP'] === 'Y')
			{
				if (!in_array($adminGroup, $otpRights, true))
				{
					$otpRights[] = $adminGroup;
				}

				if (!in_array($employeeGroup, $otpRights, true))
				{
					$otpRights[] = $employeeGroup;
				}
			}
			else
			{
				foreach ($otpRights as $key => $group)
				{
					if ($group === $adminGroup || $group === $employeeGroup)
					{
						unset($otpRights[$key]);
					}
				}
			}

			Security\Mfa\Otp::setMandatoryRights($otpRights);
		}
		else
		{
			Security\Mfa\Otp::setMandatoryUsing(isset($this->data['SECURITY_OTP']) && $this->data['SECURITY_OTP'] === 'Y');
		}

		if (isset($this->data['SECURITY_OTP_DAYS']))
		{
			$numDays = (int)$this->data['SECURITY_OTP_DAYS'];

			if ($numDays > 0)
			{
				Security\Mfa\Otp::setSkipMandatoryDays($numDays);
			}
		}

		if (isset($this->data['SEND_OTP_PUSH']) && $this->data['SEND_OTP_PUSH'] === 'Y')
		{
			Option::set('intranet', 'send_otp_push', 'Y');
		}
		else
		{
			Option::set('intranet', 'send_otp_push', 'N');
		}

		if (isset($this->data['disable_copy_text']))
		{
			$this->mobileAppService->setAllowCopyText(!($this->data['disable_copy_text'] === 'Y'));
		}

		if (isset($this->data['disable_copy_screenshot']))
		{
			$this->mobileAppService->setAllowScreenshot(!($this->data['disable_copy_screenshot'] === 'Y'));
		}
	}

	private function getIpAccessRights(): array
	{
		$result = [];

		$ipRights = Rights::getInstance()->getIpAccessRights();

		if (!empty($ipRights))
		{
			$ipUsersList = [];

			foreach ($ipRights as $userId => $ipList)
			{
				$ipString = implode(', ', $ipList);
				$ipUsersList[$ipString][] = $userId;
			}

			$fieldNumber = 0;

			foreach ($ipUsersList as $ipString => $userList)
			{
				$result[] = [
					'fieldNumber' => strval(++$fieldNumber),
					'ip' => $ipString,
					'users' => $userList,
				];
			}
		}

		return $result;
	}

	private function saveIpAccessRights(): void
	{
		if ($this->isCloud && Feature::isFeatureEnabled('ip_access_rights'))
		{
			$userRightList = [];
			$ipRightList = [];

			foreach ($this->data as $key => $value)
			{
				if (mb_strpos($key, 'SECURITY_IP_ACCESS_') !== false)
				{
					$right = str_replace('SECURITY_IP_ACCESS_', '', $key);

					if (mb_strpos($key, '_USERS') !== false)
					{
						$right = str_replace('_USERS', '', $right);
						$userRightList[$right] = $value;
					}
					elseif (mb_strpos($key, '_IP') !== false)
					{
						$right = str_replace('_IP', '', $right);
						$ipRightList[$right] = $value;
					}
				}
			}

			$ipSettings = [];

			foreach ($ipRightList as $right => $ipListString)
			{
				if (array_key_exists($right, $userRightList) && is_array($userRightList[$right]))
				{
					$ipList = array_map('trim', explode(',', $ipListString));

					foreach ($userRightList[$right] as $user)
					{
						if (empty($ipSettings[$user]))
						{
							$ipSettings[$user] = $ipList;
						}
						else
						{
							$ipSettings[$user] = array_unique(array_merge($ipSettings[$user], $ipList));
						}
					}
				}
			}

			Rights::getInstance()->saveIpAccessRights($ipSettings);
		}
	}

	public function find(string $query): array
	{
		$searchIndex = [
			'DEVICE_HISTORY_CLEANUP_DAYS' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DEVISE_HISTORY_CLEANUP_DAYS'),
			'SECURITY_IP_ACCESS_1_IP' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_SELECT_ACCEPTED_IP'),
			'settings-security-section-history' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_DEVICES_HISTORY'),
			'settings-security-section-event_log' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_EVENT_LOG'),
			'settings-security-section-mobile_app' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_MOBILE_APP'),
			'disable_copy_screenshot' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DISABLE_SCREENSHOT'),
			'disable_copy_text' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_DISABLE_COPY'),
		];
		$otpData = $this->getOtpSettings();
		if ($otpData['SECURITY_OTP_ENABLED'] ?? false)
		{
			$searchIndex['settings-security-section-otp'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_OTP');
		}
		if ($this->isCloud)
		{
			$searchIndex['settings-security-section-access_ip'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_ACCESS_IP');
			$searchIndex['settings-security-section-black_list'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_BLACK_LIST');
		}

		$searchEngine = SearchEngine::initWithDefaultFormatter($searchIndex);

		return $searchEngine->find($query);
	}
}
