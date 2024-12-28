<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\ActionFilter\UserType;
use Bitrix\IntranetMobile\ActionFilter\InviteIntranetMobileAccessControl;
use Bitrix\IntranetMobile\Dto\PhoneNumberDto;
use Bitrix\IntranetMobile\Dto\UserDto;
use Bitrix\IntranetMobile\Invitation\UserNumberExtractor;
use Bitrix\Main\Loader;
use \Bitrix\Intranet\Service\ServiceContainer;

class Invite extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new UserType(['employee', 'extranet']);
		$preFilters[] = new InviteIntranetMobileAccessControl();

		return $preFilters;
	}

	protected function getQueryActionNames(): array
	{
		return [
			'getInviteSettings',
			'getPhoneNumbersInviteStatus',
		];
	}

	/**
	 * @restMethod intranetmobile.invite.getInviteSettings
	 * @return array
	 */
	public function getInviteSettingsAction(): array
	{
		$canCurrentUserInvite = Invitation::canCurrentUserInvite();
		$isBitrix24Included = Loader::includeModule('bitrix24');
		$creatorEmailConfirmed = !$isBitrix24Included
			|| !\Bitrix\Bitrix24\Service\PortalSettings::getInstance()
				->getEmailConfirmationRequirements()
				->isRequiredByType(\Bitrix\Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type::INVITE_USERS);

		return [
			'adminConfirm' => $canCurrentUserInvite ? Invitation::getRegisterAdminConfirm() : null,
			'canInviteByPhone' => Invitation::canCurrentUserInviteByPhone(),
			'canInviteByLink' => Invitation::canCurrentUserInviteByLink(),
			'canCurrentUserInvite' => $canCurrentUserInvite,
			'inviteLink' => $canCurrentUserInvite ? Invitation::getRegisterUrl() : '',
			'sharingMessage' => $canCurrentUserInvite ? Invitation::getRegisterSharingMessage() : '',
			'creatorEmailConfirmed' => $creatorEmailConfirmed,
			'isBitrix24Included' => $isBitrix24Included,
			'adminInBoxRedirectLink' => '/company/',
		];
	}

	/**
	 * @restMethod intranetmobile.invite.inviteUsersByPhoneNumbers
	 * @return array
	 */
	public function inviteUsersByPhoneNumbersAction(array $users, ?int $departmentId = null): array
	{
		$result = Invitation::inviteUsers($this->prepareUsersForIntranetAPI($users, $departmentId));

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->getData();
	}

	private function prepareUsersForIntranetAPI(array $users, ?int $departmentId = null): array
	{
		$result = [
			'CONTEXT' => 'mobile',
			'PHONE' => [],
		];
		foreach ($users as $user) {
			$result['PHONE'][] = [
				'PHONE' => $user['phone'],
				'NAME' => $user['firstName'],
				'LAST_NAME' => $user['lastName'],
				'PHONE_COUNTRY' => $user['countryCode'],
			];
		}

		if (empty($departmentId))
		{
			$result['DEPARTMENT_ID'] = ServiceContainer::getInstance()
				->departmentRepository()
				->getRootDepartment()
				?->getId()
			;
		}
		else
		{
			$result['DEPARTMENT_ID'] = $departmentId;
		}

		return $result;
	}

	/**
	 * @restMethod intranetmobile.invite.getPhoneNumbersInviteStatus
	 * @return array
	 */
	public function getPhoneNumbersInviteStatusAction(array $phoneNumbers): array
	{
		$phoneUsers = [];

		if (!empty($phoneNumbers))
		{
			$numberExtractor = new UserNumberExtractor();
			$phoneNumbersDto = $this->createPhoneNumbersDto($phoneNumbers);
			$phoneUsers = $numberExtractor
				->addPhoneUsersByNumbers($phoneNumbersDto)
				->getPhoneUsers()
			;
		}

		return $this->getPhoneNumbersStatus($phoneUsers);
	}

	/**
	 * @param array $phoneNumbers
	 * @return PhoneNumberDto[]
	 */
	private function createPhoneNumbersDto(array $phoneNumbers): array
	{
		$result = [];

		foreach ($phoneNumbers as $phoneNumber)
		{
			$parsedPhoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($phoneNumber['phone']);
			$parsedPhoneNumber->setCountry($phoneNumber['countryCode']);
			$isValidPhoneNumber = $parsedPhoneNumber->isValid();

			$formattedPhone = $isValidPhoneNumber
				? $parsedPhoneNumber->format(\Bitrix\Main\PhoneNumber\Format::E164)
				: null;

			$key = $formattedPhone && empty($result[$formattedPhone]) ? $formattedPhone : $phoneNumber['phone'];
			$result[$key] = new PhoneNumberDto(
				phoneNumber: $phoneNumber['phone'],
				countryCode: $phoneNumber['countryCode'],
				formattedPhoneNumber: $formattedPhone ?? '',
				isValidPhoneNumber: $isValidPhoneNumber,
			);
		}

		return $result;
	}

	/**
	 * @param UserDto[] $phoneUsers
	 * @return array
	 */
	private function getPhoneNumbersStatus(array $phoneUsers): array
	{
		$result = [];

		foreach ($phoneUsers as $phoneUser)
		{
			$phoneNumber = $phoneUser->phoneNumber;
			$result[] = [
				'phone' => $phoneNumber->phoneNumber,
				'countryCode' => $phoneNumber->countryCode,
				'inviteStatus' => $phoneUser->employeeStatus,
				'isValidPhoneNumber' => $phoneNumber->isValidPhoneNumber,
				'formattedPhone' => $phoneNumber->formattedPhoneNumber,
				'userId' => $phoneUser->id,
			];
		}

		return $result;
	}
}
