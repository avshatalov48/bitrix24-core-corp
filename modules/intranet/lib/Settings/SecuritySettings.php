<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Bitrix24\Feature;
use Bitrix\Bitrix24\OptionTable;
use Bitrix\Main\Config\Option;
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
			$data['SEND_OTP_PUSH'] = Option::get('intranet', 'send_otp_push', 'N');
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

		return new static($data);
	}

	private function getDeviceHistorySettings(): array
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

		return [
			'name' => 'DEVICE_HISTORY_CLEANUP_DAYS',
			'values' => $deviseHistoryDaysValues,
			'current' => $deviseHistoryDaysCurrent,
			'is_enable' => $deviseHistoryDaysEnabled,
		];
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
		global $USER;

		$result = [];

		if (!Loader::includeModule('security'))
		{
			return $result;
		}

		$result['SECURITY_MODULE'] = true;
		$result['SECURITY_OTP_ENABLED'] = Security\Mfa\Otp::isOtpEnabled();
		$result['SECURITY_IS_USER_OTP_ACTIVE'] = \CSecurityUser::IsUserOtpActive($USER->GetID());
		$result['SECURITY_OTP_DAYS'] = Security\Mfa\Otp::getSkipMandatoryDays();
		$result['SECURITY_OTP'] = Security\Mfa\Otp::isMandatoryUsing();
		$result['SECURITY_OTP_PATH'] = SITE_DIR . 'company/personal/user/' . $USER->getId() . '/common_security/?page=otpConnected';

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
			Security\Mfa\Otp::setMandatoryUsing(isset($this->data['SECURITY_OTP']));
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
	}

	private function getIpAccessRights(): array
	{
		$result = [];

		$dbIpRights = OptionTable::getList([
			'filter' => ['=NAME' => 'ip_access_rights'],
		]);

		if ($ipRights = $dbIpRights->Fetch())
		{
			$ipRights = unserialize($ipRights['VALUE'], ['allowed_classes' => false]);
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

			if (empty($ipSettings))
			{
				OptionTable::delete('ip_access_rights');
			}
			else
			{
				$ipSettingsSerialize = serialize($ipSettings);

				$dbIpRights = OptionTable::getList([
					'filter' => ['=NAME' => 'ip_access_rights'],
				]);

				if ($dbIpRights->Fetch())
				{
					OptionTable::update('ip_access_rights', ['VALUE' => $ipSettingsSerialize]);
				}
				else
				{
					OptionTable::add([
						'NAME' => 'ip_access_rights',
						'VALUE' => $ipSettingsSerialize,
					]);
				}
			}
		}
	}
}