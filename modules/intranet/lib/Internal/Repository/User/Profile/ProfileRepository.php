<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Repository\User\Profile;

use Bitrix\HumanResources\Item\Node;
use Bitrix\Intranet\Component\UserProfile\Form;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Exception\UserFieldTypeException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Internal\Entity\User\Field\FieldCollection;
use Bitrix\Intranet\Internal\Entity\User\Profile\FieldSectionCollection;
use Bitrix\Intranet\Internal\Entity\User\Profile\BaseInfo;
use Bitrix\Intranet\Internal\Entity\User\Field\Field;
use Bitrix\Intranet\Internal\Entity\User\Profile\FieldSection;
use Bitrix\Intranet\Internal\Entity\User\Profile\Profile;
use Bitrix\Intranet\Internal\Factory\User\UserFieldFactory;
use Bitrix\Intranet\Internal\Integration\Humanresources\DepartmentRepository;
use Bitrix\Intranet\Internal\Integration\Humanresources\TeamRepository;
use Bitrix\Intranet\Internals\Trait\UserUpdateError;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;

class ProfileRepository
{
	use UserUpdateError;

	private \CUser $cUser;

	public function __construct(
		private UserFieldFactory $userFieldFactory,
		private DepartmentRepository $departmentRepository,
		private TeamRepository $teamRepository,
	)
	{
		global $USER;
		$this->cUser = $USER instanceof \CUser ? $USER : new \CUser();
	}

	public static function createByDefault(): static
	{
		return new static(
			UserFieldFactory::createByDefault(),
			new DepartmentRepository(),
			new TeamRepository(),
		);
	}

	public function getUserDataById(int $userId): array
	{
		$filter = [
			'ID_EQUAL_EXACT' => $userId,
		];

		$params = [
			'FIELDS' => [
				'ID', 'ACTIVE', 'CONFIRM_CODE', 'EXTERNAL_AUTH_ID', 'LAST_ACTIVITY_DATE', 'DATE_REGISTER',
				'LOGIN', 'EMAIL', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'WORK_POSITION',
				'PERSONAL_PHOTO', 'PERSONAL_BIRTHDAY', 'PERSONAL_GENDER',
				'PERSONAL_WWW', 'PERSONAL_MOBILE', 'WORK_PHONE', 'PERSONAL_CITY',
				'TIME_ZONE', 'AUTO_TIME_ZONE', 'TIME_ZONE_OFFSET',
				'PERSONAL_COUNTRY', 'PERSONAL_FAX', 'PERSONAL_MAILBOX',
				'PERSONAL_PHONE', 'PERSONAL_STATE', 'PERSONAL_STREET', 'PERSONAL_ZIP',
				'WORK_CITY', 'WORK_COUNTRY', 'WORK_COMPANY', 'WORK_DEPARTMENT',
				'PERSONAL_PROFESSION', 'WORK_NOTES', 'WORK_PROFILE', 'LANGUAGE_ID',
			],
			'SELECT' => ['UF_PHONE_INNER', 'UF_SKYPE', 'UF_SKYPE_LINK', 'UF_ZOOM', 'UF_PUBLIC'],
		];

		$dbUser = \CUser::GetList('id', 'asc', $filter, $params);
		$user = $dbUser->fetch();

		if (!$user)
		{
			throw new ObjectNotFoundException('User not found');
		}

		$userDepartments = $this->departmentRepository->getDepartmentsByUserId($userId);

		$user['UF_DEPARTMENT'] = array_values(
			$userDepartments->map(fn (Department $department) => $department->getId()),
		);

		$user['DEPARTMENT'] = $userDepartments->map(fn (Department $department) => $department->getName());

		$user['DEPARTMENT_HEAD'] = $this->departmentRepository
			->getDepartmentHeadsByUserId($userId)
			->map(fn (User $user) => $user->getFormattedName(true, false))
		;

		$userTeams = $this->teamRepository->getAllByUserId($userId);
		$user['TEAM'] = isset($userTeams) ? array_values($userTeams->map(fn (Node $team) => $team->name)) : [];

		return $user;
	}

	/**
	 * @throws ObjectNotFoundException
	 * @throws SystemException
	 * @throws WrongIdException
	 */
	public function getById(int $userId): Profile
	{
		if ($userId <= 0)
		{
			throw new WrongIdException();
		}

		$profileData = $this->getUserDataById($userId);
		$profileForm = new Form($userId);

		return $this->createUserProfileByProfileFormAndProfileData($profileForm, $profileData);
	}

	public function getUserFieldsByUserData(array $userData): FieldCollection
	{
		$profileForm = new Form();

		return $this->createUserFieldCollectionByProfileFormAndProfileData(
			$profileForm,
			$userData,
		);
	}

	public function getUserBaseInfoByUserData(array $userData): BaseInfo
	{
		$user = User::initByArray($userData);
		$fullName = \CUser::FormatName(\CSite::GetNameFormat(), $userData, false, false);

		return new BaseInfo(
			userId: (int)$userData['ID'],
			fullName: $fullName,
			userRole: $user->getRole(),
			invitationStatus: $user->getInviteStatus(),
			photoId: isset($userData['PERSONAL_PHOTO']) ? (int)$userData['PERSONAL_PHOTO'] : null,
		);
	}

	/**
	 * @param int $userId
	 * @param FieldCollection $userFieldCollection
	 * @throws UpdateFailedException
	 */
	public function saveUserProfileFields(int $userId, FieldCollection $userFieldCollection): void
	{
		$userFieldArray = $this->createArrayFromUserFieldCollection($userFieldCollection);

		$result = $this->cUser->Update($userId, $userFieldArray);

		if (!$result)
		{
			throw new UpdateFailedException($this->getErrorCollectionFromUpdateLastError($this->cUser->LAST_ERROR));
		}
	}

	private function createArrayFromUserFieldCollection(FieldCollection $userFieldCollection): array
	{
		$result = [];

		/** @var Field $userField */
		foreach ($userFieldCollection as $userField)
		{
			$result[$userField->getId()] = $userField->getValue();
		}

		return $result;
	}

	private function createUserProfileByProfileFormAndProfileData(Form $profileForm, array $profileData): Profile
	{
		$profileSections = $profileForm->getNewConfig();
		$userFieldCollection = $this->createUserFieldCollectionByProfileFormAndProfileData(
			$profileForm,
			$profileForm->getData(['User' => $profileData]),
		);

		$profileSectionCollection = $this->createSectionCollectionFromProfileSectionsAndUserFields(
			$profileSections,
			$userFieldCollection,
		);

		return new Profile(
			baseInfo: $this->getUserBaseInfoByUserData($profileData),
			fieldSectionCollection: $profileSectionCollection,
		);
	}

	private function createSectionCollectionFromProfileSectionsAndUserFields(
		array $profileSections,
		FieldCollection $userFieldCollection,
	): FieldSectionCollection {
		$profileSectionCollection = new FieldSectionCollection();

		foreach ($profileSections as $profileSection)
		{
			$sectionUserFieldCollection = new FieldCollection();

			foreach ($profileSection['elements'] as $element)
			{
				$userFieldId = $element['name'] ?? null;

				if (empty($userFieldId))
				{
					continue;
				}

				$userField = $userFieldCollection->findById($userFieldId);

				if (isset($userField))
				{
					$sectionUserFieldCollection->add($userField);
					$userFieldCollection->removeItem($userField);
				}
			}

			$section = new FieldSection(
				id: $profileSection['name'],
				title: $profileSection['title'],
				isEditable: $profileSection['data']['isChangeable'],
				isRemovable: $profileSection['data']['isRemovable'],
				userFieldCollection: $sectionUserFieldCollection,
				isDefault: $profileSection['data']['isDefault'] ?? false,
			);

			$profileSectionCollection->add($section);
		}

		if (!$userFieldCollection->isEmpty())
		{
			$this->addUserFieldsToDefaultSection($userFieldCollection, $profileSectionCollection);
		}

		return $profileSectionCollection;
	}

	private function addUserFieldsToDefaultSection(
		FieldCollection $userFieldCollection,
		FieldSectionCollection $profileSectionCollection,
	): void {
		/* @var FieldSection $defaultSection */
		$defaultSection = $profileSectionCollection->find(
			fn (FieldSection $section) => $section->isDefault,
		);

		if (isset($defaultSection))
		{
			foreach ($userFieldCollection as $userField)
			{
				$defaultSection->userFieldCollection->add($userField);
			}
		}
	}

	private function createUserFieldCollectionByProfileFormAndProfileData(
		Form $profileForm,
		array $profileData,
	): FieldCollection {
		$profileFieldInfo = $profileForm->getFieldInfo($profileData, [], [], false);
		$userFieldCollection = new FieldCollection();

		foreach ($profileFieldInfo as $fieldInfo)
		{
			if (
				!isset($fieldInfo['name'])
				|| !array_key_exists($fieldInfo['name'], $profileData)
			) {
				continue;
			}

			try
			{
				$userField = $this->userFieldFactory->createUserFieldByArray($fieldInfo, $profileData[$fieldInfo['name']]);
			}
			catch (UserFieldTypeException|ArgumentException)
			{
				continue;
			}

			$userFieldCollection->add($userField);
		}

		return $userFieldCollection;
	}
}
