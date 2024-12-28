<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Intranet\User\Filter\IntranetUserSettings;
use Bitrix\Intranet\Util;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class IntranetUserDataProvider extends EntityDataProvider
{
	public const PHONE_APPS_FIELD = 'PHONE_APPS';
	public const DESKTOP_APPS_FIELD = 'DESKTOP_APPS';

	private const ANDROID_APP = 'android';
	private const IOS_APP = 'ios';
	private const LINUX_APP = 'linux';
	private const MAC_APP = 'mac';
	private const WINDOWS_APP = 'windows';
	private const NOT_INSTALLED_APP = 'notInstalled';
	private IntranetUserSettings $settings;

	public function __construct(IntranetUserSettings $settings)
	{
		$this->settings = $settings;
	}

	public function getSettings(): IntranetUserSettings
	{
		return $this->settings;
	}

	public function prepareFields(): array
	{
		$fieldList['PHONE_APPS'] = $this->createField(
			'PHONE_APPS',
			[
				'name' => Loc::getMessage('INTRANET_USER_FILTER_MOBILE_APP') ?? '',
				'type' => 'list',
				'partial' => true,
			]
		);

		$fieldList['DESKTOP_APPS'] = $this->createField(
			'DESKTOP_APPS',
			[
				'name' => Loc::getMessage('INTRANET_USER_FILTER_DESKTOP_APP') ?? '',
				'type' => 'list',
				'partial' => true,
			]
		);

		if ($this->getSettings()->isFilterAvailable(IntranetUserSettings::WAIT_CONFIRMATION_FIELD))
		{
			$fieldList[IntranetUserSettings::WAIT_CONFIRMATION_FIELD] = $this->createField(
				IntranetUserSettings::WAIT_CONFIRMATION_FIELD,
				[
					'name' => Loc::getMessage('INTRANET_USER_FILTER_WAIT_CONFIRMATION') ?? '',
					'type' => 'checkbox',
				]
			);
		}

		return $fieldList;
	}

	public function prepareFieldData($fieldID): array
	{
		$result = [];

		if ($fieldID === self::PHONE_APPS_FIELD)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => [
					self::NOT_INSTALLED_APP => Loc::getMessage('INTRANET_USER_FILTER_APP_NOT_INSTALLED'),
					self::ANDROID_APP => Loc::getMessage('INTRANET_USER_FILTER_MOBILE_APP_ANDROID'),
					self::IOS_APP => Loc::getMessage('INTRANET_USER_FILTER_MOBILE_APP_IOS'),
				],
			];
		}

		if ($fieldID === self::DESKTOP_APPS_FIELD)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => [
					self::NOT_INSTALLED_APP => Loc::getMessage('INTRANET_USER_FILTER_APP_NOT_INSTALLED'),
					self::WINDOWS_APP => Loc::getMessage('INTRANET_USER_FILTER_DESKTOP_APP_WINDOWS'),
					self::MAC_APP => Loc::getMessage('INTRANET_USER_FILTER_DESKTOP_APP_MAC'),
					self::LINUX_APP => Loc::getMessage('INTRANET_USER_FILTER_DESKTOP_APP_LINUX'),
				],
			];
		}

		return $result;
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		// compatibility with old filters
		$this->checkFiredField($filterValue);
		$this->checkAdminField($filterValue);
		$this->checkIntegratorField($filterValue);
		$this->checkOnlineField($filterValue);
		$this->checkVisitorField($filterValue);
		$this->checkTagsField($filterValue);
		$this->checkInvitedField($filterValue);
		$this->checkWaitConfirmationField($filterValue);
		$this->checkAppField($filterValue);

		return $filterValue;
	}

	private function checkFiredField(array &$filterValue): void
	{
		if ($this->getSettings()->isFilterAvailable(IntranetUserSettings::INVITED_FIELD))
		{
			$invitedFilter = [
				'=ACTIVE' => 'Y',
				'!CONFIRM_CODE' => '',
			];

			if (!$this->getSettings()->isCurrentUserAdmin())
			{
				$invitedFilter['INVITATION.ORIGINATOR_ID'] = $this->getSettings()->getCurrentUserId();
			}
		}
		else
		{
			$invitedFilter = [];
		}

		if ($this->getSettings()->isFilterAvailable(IntranetUserSettings::WAIT_CONFIRMATION_FIELD))
		{
			$waitingFilter = [
				'=ACTIVE' => 'N',
				'!CONFIRM_CODE' => '',
			];
		}
		else
		{
			$waitingFilter = [];
		}

		if (
			empty($filterValue[IntranetUserSettings::FIRED_FIELD])
			&& $this->getSettings()->isFilterAvailable(IntranetUserSettings::FIRED_FIELD)
		)
		{
			$filterValue[] = [
				'LOGIC' => 'OR',
				$waitingFilter,
				$invitedFilter,
				[
					'=ACTIVE' => 'Y',
					'CONFIRM_CODE' => '',
				],
				[
					'=ACTIVE' => 'N',
					'CONFIRM_CODE' => '',
				],
			];
		}
		elseif (
			!$this->getSettings()->isFilterAvailable(IntranetUserSettings::FIRED_FIELD)
			|| empty($filterValue[IntranetUserSettings::FIRED_FIELD])
			|| $filterValue[IntranetUserSettings::FIRED_FIELD] === 'N'
		)
		{
			$filterValue[] = [
				'LOGIC' => 'OR',
				$waitingFilter,
				$invitedFilter,
				[
					'=ACTIVE' => 'Y',
					'CONFIRM_CODE' => '',
				]
			];
		}
		elseif (
			$this->getSettings()->isFilterAvailable(IntranetUserSettings::FIRED_FIELD)
			&& isset($filterValue[IntranetUserSettings::FIRED_FIELD])
			&& $filterValue[IntranetUserSettings::FIRED_FIELD] === 'Y'
		)
		{
			$filterValue['=ACTIVE'] = 'N';
			$filterValue['CONFIRM_CODE'] = '';
		}
	}

	private function checkVisitorField(array &$filterValue): void
	{
		if (
			!empty($filterValue[IntranetUserSettings::VISITOR_FIELD])
			&& $filterValue[IntranetUserSettings::VISITOR_FIELD] === 'Y'
			&& $this->getSettings()->isFilterAvailable(IntranetUserSettings::VISITOR_FIELD)
		)
		{
			$extranetGroupId = Loader::includeModule('extranet') ? \CExtranet::getExtranetUserGroupId() : 0;
			$filterValue['UF_DEPARTMENT'] = false;

			if ($extranetGroupId)
			{
				$filterValue['INTRANET_USER_EXTRANET_GROUP_GROUP_ID'] = false;
			}
		}
		elseif (
			!$this->getSettings()->isFilterAvailable(IntranetUserSettings::VISITOR_FIELD)
			|| (
				!empty($filterValue[IntranetUserSettings::VISITOR_FIELD])
				&& $filterValue[IntranetUserSettings::VISITOR_FIELD] === 'N'
			)
		)
		{
			if (Loader::includeModule('extranet') && \CExtranet::getExtranetUserGroupId())
			{
				$filterValue[] = [
					'LOGIC' => 'OR',
					['!UF_DEPARTMENT' => false],
					['!INTRANET_USER_EXTRANET_GROUP_GROUP_ID' => false],
				];
			}
			else
			{
				$filterValue['!UF_DEPARTMENT'] = false;
			}
		}
	}

	private function checkInvitedField(array &$filterValue): void
	{
		if (
			!empty($filterValue[IntranetUserSettings::INVITED_FIELD])
			&& $this->getSettings()->isFilterAvailable(IntranetUserSettings::INVITED_FIELD)
		)
		{
			if ($filterValue[IntranetUserSettings::INVITED_FIELD] === 'Y')
			{
				$filterValue['=ACTIVE'] = 'Y';
				$filterValue['!CONFIRM_CODE'] = '';
			}
			elseif ($filterValue[IntranetUserSettings::INVITED_FIELD] === 'N')
			{
				$filterValue[] = [
					'LOGIC' => 'OR',
					[
						'=ACTIVE' => 'N',
						'!CONFIRM_CODE' => '',
					],
					[
						'=ACTIVE' => 'Y',
						'CONFIRM_CODE' => '',
					]
				];
			}
		}
	}

	private function checkIntegratorField(array &$filterValue): void
	{
		if (
			!empty($filterValue[IntranetUserSettings::INTEGRATOR_FIELD])
			&& $filterValue[IntranetUserSettings::INTEGRATOR_FIELD] === 'Y'
			&& $this->getSettings()->isFilterAvailable(IntranetUserSettings::INTEGRATOR_FIELD)
			&& Loader::includeModule('bitrix24')
		)
		{
			$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
			if ($integratorGroupId)
			{
				$filterValue['=GROUPS.GROUP_ID'] = $integratorGroupId;
			}
		}
	}

	private function checkAdminField(array &$filterValue): void
	{
		if (
			!empty($filterValue[IntranetUserSettings::ADMIN_FIELD])
			&& $filterValue[IntranetUserSettings::ADMIN_FIELD] === 'Y'
			&& $this->getSettings()->isFilterAvailable(IntranetUserSettings::ADMIN_FIELD)
		)
		{
			$filterValue['=GROUPS.GROUP_ID'] = 1;

			if (
				Loader::includeModule('bitrix24')
				&& $this->getSettings()->isFilterAvailable(IntranetUserSettings::INTEGRATOR_FIELD)
			)
			{
				$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();

				if ($integratorGroupId)
				{
					$filterValue['!=GROUPS.GROUP_ID'] = $integratorGroupId;
				}
			}
		}
	}

	private function checkOnlineField(array &$filterValue): void
	{
		if (
			!empty($filterValue['IS_ONLINE'])
			&& in_array($filterValue['IS_ONLINE'], [ 'Y', 'N' ])
		)
		{
			$filterValue['IS_ONLINE'] = (
				$filterValue['IS_ONLINE'] === 'Y' ? 'Y' : 'N'
			);
		}
	}

	private function checkTagsField(array &$filterValue): void
	{
		if (isset($filterValue['TAGS']))
		{
			$tagsSearchValue = trim($filterValue['TAGS']);
			if ($tagsSearchValue <> '')
			{
				$filterValue['%=TAGS.NAME'] = $tagsSearchValue.'%';
			}
		}
	}

	private function checkWaitConfirmationField(array &$filterValue): void
	{
		if (isset($filterValue[IntranetUserSettings::WAIT_CONFIRMATION_FIELD]))
		{
			if ($filterValue[IntranetUserSettings::WAIT_CONFIRMATION_FIELD] === 'Y')
			{
				$filterValue['=ACTIVE'] = 'N';
				$filterValue['!CONFIRM_CODE'] = '';
			}
			elseif ($filterValue[IntranetUserSettings::WAIT_CONFIRMATION_FIELD] === 'N')
			{
				$filterValue['CONFIRM_CODE'] = '';
			}
		}
	}

	private function checkAppField(array &$filterValue): void
	{
		if (!empty($filterValue[self::PHONE_APPS_FIELD]))
		{
			$filter = [];

			if (in_array(self::NOT_INSTALLED_APP, $filterValue[self::PHONE_APPS_FIELD]))
			{
				$filter[] = ['!@ID' => $this->getUsersPhoneApps()];
			}

			if (in_array(self::ANDROID_APP, $filterValue[self::PHONE_APPS_FIELD]))
			{
				$filter[] = ['@ID' => $this->getUsersPhoneApps(self::ANDROID_APP)];
			}

			if (in_array(self::IOS_APP, $filterValue[self::PHONE_APPS_FIELD]))
			{
				$filter[] = ['@ID' => $this->getUsersPhoneApps(self::IOS_APP)];
			}

			if (!empty($filter))
			{
				$filterValue[] = [
					'LOGIC' => 'OR',
					...$filter
				];
			}
		}

		if (!empty($filterValue[self::DESKTOP_APPS_FIELD]))
		{
			$filter = [];

			if (in_array(self::NOT_INSTALLED_APP, $filterValue[self::DESKTOP_APPS_FIELD]))
			{
				$filter[] = ['!@ID' => $this->getUsersDesktopApps()];
			}

			if (in_array(self::WINDOWS_APP, $filterValue[self::DESKTOP_APPS_FIELD]))
			{
				$filter[] = ['@ID' => $this->getUsersDesktopApps(self::WINDOWS_APP)];
			}

			if (in_array(self::MAC_APP, $filterValue[self::DESKTOP_APPS_FIELD]))
			{
				$filter[] = ['@ID' => $this->getUsersDesktopApps(self::MAC_APP)];
			}

			if (in_array(self::LINUX_APP, $filterValue[self::DESKTOP_APPS_FIELD]))
			{
				$filter[] = ['@ID' => $this->getUsersDesktopApps(self::LINUX_APP)];
			}

			if (!empty($filter))
			{
				$filterValue[] = [
					'LOGIC' => 'OR',
					...$filter
				];
			}
		}
	}

	private function getOsOptionName(?string $osName): ?string
	{
		return match ($osName) {
			self::ANDROID_APP => 'AndroidLastActivityDate',
			self::IOS_APP => 'iOsLastActivityDate',
			self::MAC_APP => 'MacLastActivityDate',
			self::WINDOWS_APP => 'WindowsLastActivityDate',
			self::LINUX_APP => 'LinuxLastActivityDate',
			default => null,
		};
	}

	private function getUsersDesktopApps(string $osName = null): array
	{
		$optionNames = $this->getOsOptionName($osName) ?? [
				$this->getOsOptionName(self::MAC_APP),
				$this->getOsOptionName(self::WINDOWS_APP),
				$this->getOsOptionName(self::LINUX_APP),
			];

		return $this->getUserIdsOptions('im', $optionNames);
	}

	private function getUsersPhoneApps(string $osName = null): array
	{
		$optionNames = $this->getOsOptionName($osName) ?? [
				$this->getOsOptionName(self::ANDROID_APP),
				$this->getOsOptionName(self::IOS_APP),
			];

		return $this->getUserIdsOptions('mobile', $optionNames);
	}

	private function getUserIdsOptions(string $category, array|string $names): array
	{
		$userIds = [];

		$result = \CUserOptions::GetList([],
			[
				'CATEGORY' => $category,
				is_array($names) ? '@NAME' : 'NAME' => $names,
			]
		);
		$appActivityTimeout = Util::getAppsActivityTimeout();

		while ($option = $result->Fetch())
		{
			if ($option['VALUE'])
			{
				$value = unserialize($option['VALUE'], ['allowed_classes' => false]);

				if (is_int($value) && $value > time() - $appActivityTimeout)
				{
					$userIds[] = $option['USER_ID'];
				}
			}
		}

		if (empty($userIds))
		{
			return [0];
		}

		return $userIds;
	}
}