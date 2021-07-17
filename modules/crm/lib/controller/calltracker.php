<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Entity\Company;
use Bitrix\Crm\Entity\Contact;
use Bitrix\Crm\Activity;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

class CallTracker extends \Bitrix\Main\Engine\Controller
{
	public function createActivityAction(string $phoneNumber, int $direction = \CCrmActivityDirection::Incoming)
	{
		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('CRM module is not installed'));

			return null;
		}

		if (\Bitrix\Crm\Settings\LeadSettings::isEnabled())
		{
			$this->addError(new Error('Wrong crm mode. Only simple crm is supported'));

			return null;
		}

		if ((Option::get('mobile', 'crm_call_tracker_enabled', 'N') !== 'Y'))
		{
			$this->addError(new Error('Feature is not available'));

			return null;
		}

		if (!\Bitrix\Crm\Restriction\RestrictionManager::isCallTrackerPermitted())
		{
			$this->addError(new Error('Not supported for your tariff'));

			return null;
		}

		if (!in_array($direction, [\CCrmActivityDirection::Incoming, \CCrmActivityDirection::Outgoing]))
		{
			$this->addError(new Error('Wrong direction id'));

			return null;
		}

		$facility = new \Bitrix\Crm\EntityManageFacility();
		$selector = $facility->getSelector();
		$selector
			->appendPhoneCriterion($phoneNumber)
			->search()
		;

		$contact = null;
		$company = null;

		$isExistedClient = false;

		$phoneOwnerId = 0;
		$phoneOwnerPhotoId = 0;

		$ownerTypeId = \CCrmOwnerType::Deal;
		$dealId = $selector->getDealId();

		$dealId =
			($dealId && \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Deal, $dealId))
				? $dealId
				: null;

		$result = null;

		$contactId = $selector->getContactId();
		$companyId = $selector->getCompanyId();

		$contactId =
			($contactId && \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Contact, $contactId))
			? $contactId
			: null;

		$companyId =
			($companyId && \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Company, $companyId))
			? $companyId
			: null;

		if ($contactId > 0)
		{
			$phoneOwnerTypeId = \CCrmOwnerType::Contact;
			$contact = Contact::getInstance()->getByID($contactId);
			if ($contact)
			{
				$phoneOwnerId = $contact['ID'];
				$isExistedClient = true;
				if ($contact['PHOTO'] > 0)
				{
					$phoneOwnerPhotoId = $contact['PHOTO'];
				}
			}

			$companyId = $contact['COMPANY_ID'] ?? 0;
			if (
				$companyId > 0
				&&  \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Company, $companyId)
			)
			{
				$company = Company::getInstance()->getByID($companyId);
				if (!$phoneOwnerPhotoId && $company['LOGO'] > 0)
				{
					$phoneOwnerPhotoId = $company['LOGO'];
				}
			}
			else
			{
				$companyId = 0;
			}
		}
		elseif ($companyId > 0)
		{
			$phoneOwnerTypeId = \CCrmOwnerType::Company;
			$company = Company::getInstance()->getByID($selector->getCompanyId());
			if ($company)
			{
				$phoneOwnerId = $company['ID'];
				$isExistedClient = true;
				if ($company['LOGO'] > 0)
				{
					$phoneOwnerPhotoId = $company['LOGO'];
				}
			}
		}
		else
		{
			$contactFields = [
				'NAME' => \CCrmContact::GetDefaultName(),
				'SOURCE_ID' => $this->getSourceId(),
				'FM' => [
					\CCrmFieldMulti::PHONE => [
						'n0' => [
							'VALUE_TYPE' => 'WORK',
							'VALUE' => $phoneNumber,
						],
					],
				],
			];

			$dealId = null; // create new contact, so have to create new deal
			$facility->getSelector()->clear();
			$facility->registerContact($contactFields);
			$contactId = $facility->getRegisteredId();
			if (!$contactId)
			{
				return $result;
			}
			$phoneOwnerTypeId = \CCrmOwnerType::Contact;
			$phoneOwnerId = $contactId;
			$facility->getSelector()->setContactId($contactId);
		}

		if (!$dealId)
		{
			$dealFields = [
				'SOURCE_ID' => $this->getSourceId(),
				'CATEGORY_ID' => $this->getDealCategoryId(),
				'TITLE' => $this->getDealTitle($direction, $phoneNumber),
			];
			$facility->registerTouch(\CCrmOwnerType::Deal, $dealFields);
			$dealId = $facility->getRegisteredId();
			if (!$dealId)
			{
				return $result;
			}
		}

		$activityId = $this->addCallTrackerActivity(
			$ownerTypeId,
			(int)$dealId,
			(string)$phoneNumber,
			(int)$direction,
			$phoneOwnerTypeId,
			$phoneOwnerId
		);

		if ($activityId)
		{
			$result = [
				'OWNER_ID' => $dealId,
				'OWNER_TYPE_ID' => $ownerTypeId,
				'COMPANY' => $company ?
					[
						'ID' => $company['ID'],
						'TITLE' => $company['TITLE'],
					] : null,
				'CONTACT' => $contact ?
					[
						'ID' => $contact['ID'],
						'FULL_NAME' => \CCrmContact::GetFullName($contact),
					] : null,
				'PHONE_OWNER' => $isExistedClient ?
					[
						'TYPE_ID' => $phoneOwnerTypeId,
						'PHOTO' => $this->getPhotoUrl((int)$phoneOwnerPhotoId),
					] : null,
				'ACTIVITY_ID' => $activityId,
			];
		}

		return $result;
	}

	public function startCallAction(int $activityId)
	{
		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('CRM module is not installed'));

			return null;
		}
		$activity = \CCrmActivity::GetByID($activityId);

		if (
			!$activity
			|| $activity['TYPE_ID'] != \CCrmActivityType::Provider
			|| $activity['PROVIDER_ID'] != Activity\Provider\CallTracker::PROVIDER_ID
		)
		{
			$this->addError(new Error('Activity not found'));

			return null;
		}

		$settings = $activity['SETTINGS'];
		$settings['STARTED'] = true;

		return (bool)\CCrmActivity::Update($activity['ID'], ['SETTINGS' => $settings]);
	}

	public function setCallDurationAction(int $activityId, int $duration)
	{
		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('CRM module is not installed'));

			return null;
		}
		$activity = \CCrmActivity::GetByID($activityId);

		if (
			!$activity
			|| $activity['TYPE_ID'] != \CCrmActivityType::Provider
			|| $activity['PROVIDER_ID'] != Activity\Provider\CallTracker::PROVIDER_ID
		)
		{
			$this->addError(new Error('Activity not found'));

			return null;
		}

		if ($duration < 0)
		{
			$this->addError(new Error('Wrong duration value'));

			return null;
		}

		$settings = $activity['SETTINGS'];
		$settings['DURATION'] = $duration;
		$settings['STARTED'] = true;
		$settings['FINISHED'] = true;

		return (bool)\CCrmActivity::Update($activity['ID'], ['SETTINGS' => $settings]);
	}

	protected function getPhotoUrl(int $photoId): string
	{
		$photoUrl = '';
		if ($photoId > 0)
		{
			$resizedPhoto = \CFile::ResizeImageGet($photoId, ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_PROPORTIONAL);
			if ($resizedPhoto)
			{
				if (mb_substr($resizedPhoto['src'], 0, 1) === '/')
				{
					$photoUrl = \CHTTP::URN2URI($resizedPhoto['src']);
				}
				else
				{
					$photoUrl = $resizedPhoto['src'];
				}
			}
		}

		return $photoUrl;
	}

	protected function getSourceId(): string
	{
		return 'CALL_TRACKER';
	}

	protected function getDealCategoryId(): int
	{
		return 0;
	}

	protected function getDealTitle(int $direction, string $phoneNumber): string
	{
		$title = '';
		switch ($direction)
		{
			case \CCrmActivityDirection::Incoming:
				$title = Loc::getMessage('CRM_CALL_TRACKER_DIRECTION_INCOMING');
				break;
			case \CCrmActivityDirection::Outgoing:
				$title = Loc::getMessage('CRM_CALL_TRACKER_DIRECTION_OUTGOING');
				break;
		}

		return $title <> '' ? ($phoneNumber . ' - ' . $title) : $phoneNumber;
	}

	protected function addCallTrackerActivity(
		int $ownerTypeId,
		int $ownerId,
		string $phoneNumber,
		int $direction,
		int $phoneOwnerTypeId,
		int $phoneOwnerId
	): ?int
	{
		$userId = (int)\CCrmPerms::GetCurrentUserID();
		$currentUserOffset = \CTimeZone::getOffset();

		$nowTimestamp = time();
		$siteId = \Bitrix\Crm\Integration\Main\Site::getPortalSiteId();
		$datetime = convertTimeStamp($nowTimestamp + $currentUserOffset, 'FULL', $siteId);

		$activityFields = [
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => Activity\Provider\CallTracker::PROVIDER_ID,
			'PROVIDER_TYPE_ID' => Activity\Provider\CallTracker::TYPE_ID,
			'BINDINGS' => [
				[
					'OWNER_ID' => $ownerId,
					'OWNER_TYPE_ID' => $ownerTypeId //'CRM_DEAL', 'CRM_LEAD', etc.
				],
			],
			'START_TIME' => (string)$datetime,
			'END_TIME' => $this->getDeadline($userId),
			'SUBJECT' => \Bitrix\Crm\Activity\Provider\CallTracker::getName(),
			'COMPLETED' => 'N',
			'RESPONSIBLE_ID' => $userId,
			'AUTHOR_ID' => $userId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'DIRECTION' => $direction,
			'SETTINGS' => [
				'PHONE_NUMBER' => $phoneNumber,
				'DURATION' => 0,
				'TIMESTAMP' => (new DateTime())->getTimestamp(),
				'STARTED' => false,
				'FINISHED' => false,
			],
		];

		$id = \CCrmActivity::Add($activityFields, false, true);

		if ($id > 0 && $phoneOwnerTypeId !== \CCrmOwnerType::Undefined && $phoneOwnerId > 0)
		{
			$communicationFields = [
				[
					'ID' => 0,
					'TYPE' => 'PHONE',
					'VALUE' => $phoneNumber,
					'ENTITY_ID' => $phoneOwnerId,
					'ENTITY_TYPE_ID' => $phoneOwnerTypeId,
				],
			];
			\CCrmActivity::SaveCommunications(
				$id,
				$communicationFields,
				$activityFields,
				true,
				false
			);
		}

		return ($id ? (int)$id : null);
	}

	/**
	 * @param int $userId
	 * @return string
	 * @note copypaste from \CCrmEMail::imapEmailMessageAdd()
	 */
	private function getDeadline(int $userId): string
	{
		$nowTimestamp = time();
		$currentUserOffset = \CTimeZone::getOffset();
		$userOffset = \CTimeZone::getOffset($userId);

		$siteId = \Bitrix\Crm\Integration\Main\Site::getPortalSiteId();

		$deadlineTimestamp = strtotime('tomorrow');
		$deadline = convertTimeStamp($deadlineTimestamp, 'FULL', $siteId);
		if (Loader::includeModule('calendar'))
		{
			$calendarSettings = \CCalendar::getSettings();

			$workTimeEndHour = $calendarSettings['work_time_end'] > 0 ? $calendarSettings['work_time_end'] : 19;
			$dummyDeadline = new \Bitrix\Main\Type\DateTime();
			$dummyDeadline->setTime(
				$workTimeEndHour,
				0,
				$currentUserOffset - $userOffset
			);
			$deadlineTimestamp += $workTimeEndHour * 60 * 60; // work time end in tomorrow
			$deadline = convertTimeStamp($deadlineTimestamp, 'FULL', $siteId);

			if ($dummyDeadline->getTimestamp() > $nowTimestamp + $currentUserOffset)
			{
				$deadline = $dummyDeadline->format(\Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
			}
		}

		return $deadline;
	}
}
