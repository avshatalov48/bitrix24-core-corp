<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Service;

use Bitrix\AI\Container;
use Bitrix\AI\Controller\ShareRole;
use Bitrix\AI\Facade\File;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Helper;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\ShareRole\Dto\CreateDto;
use Bitrix\AI\ShareRole\Dto\RoleForUpdateDto;
use Bitrix\AI\ShareRole\Repository\RolePromptRepository;
use Bitrix\AI\ShareRole\Repository\RoleRepository;
use Bitrix\AI\ShareRole\Repository\TranslateDescriptionRepository;
use Bitrix\AI\ShareRole\Repository\TranslateNameRepository;
use Bitrix\AI\Synchronization\RoleSync;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Result;

class RoleService
{
	private const INITIAL_CHAT_ACTIONS = [
		'universal_how_to_properly_write_a_business_letter',
		'universal_ideas_on_how_to_make_meetings_more_concise_and_substantive',
		'universal_ideas_for_short_breaks_for_physical_exercises',
	];

	public function __construct(
		protected RoleRepository $roleRepository,
		protected RolePromptRepository $rolePromptRepository,
		protected TranslateNameRepository $translateNameRepository,
		protected TranslateDescriptionRepository $translateDescriptionRepository
	)
	{
	}

	public function createRole(CreateDto $createDto): UpdateResult|AddResult
	{
		$createDto->roleCode = Helper::generateUUID();

		return $this->saveRole($createDto);
	}

	public function saveRole(CreateDto $createDto): AddResult|UpdateResult|Result
	{
		if(empty($createDto->roleAvatarPaths)){
			$newAvatarFileId = \CFile::SaveFile($createDto->roleAvatarFile, 'ai', true);
			$createDto->roleAvatarPaths = $this->getImagePath($newAvatarFileId);
		}

		$roleData = [
			'code' => $createDto->roleCode,
			'hash' => $createDto->getHash(),
			'name_translates' => [User::getUserLanguage() => $createDto->roleTitle],
			'description_translates' => [User::getUserLanguage() => $createDto->roleDescription],
			'instruction' => $createDto->roleText,
			'avatar' => $createDto->roleAvatarPaths,
			'industry_code' => $createDto->industryCode,
			'is_system' => 'N',
		];

		return (new RoleSync())->updateRoleByFields($roleData, $createDto->userCreatorId);
	}

	private function getImagePath(int $imageId): array
	{
		$imagePath = \CFile::GetPath($imageId);

		return [
			'small' => $imagePath,
			'medium' => $imagePath,
			'large' => $imagePath,
		];
	}

	public function addCreationActions(int $roleId): void
	{
			$promptIds = self::getPromptService()->getPromptIdsByCodes(self::INITIAL_CHAT_ACTIONS);

			$this->rolePromptRepository->addPromptsToRole($roleId, $promptIds);
	}

	public function getRoleByCode(array $data): ?array
	{
		if (empty($data))
		{
			return null;
		}

		$code = '';
		if (array_key_exists('CODE', $data))
		{
			$code = $data['CODE'];
		}

		if (empty($code))
		{
			return null;
		}

		$role = $this->roleRepository->getByCode($code);

		if (!$role)
		{
			return null;
		}

		return $role;
	}

	public function getMainRoleDataByCode(string $code): array
	{
		$roleData = $this->roleRepository->getByCode($code);
		if (empty($roleData['ID']))
		{
			return [null, null];
		}

		return [(int)$roleData['ID'], $roleData['IS_SYSTEM'] === 'Y'];
	}

	public function getRoleByIdForUpdate(int $roleId): ?RoleForUpdateDto
	{
		$roleData = $this->roleRepository->getByIdForUpdate($roleId);
		if (empty($roleData))
		{
			return null;
		}

		$urlManager = UrlManager::getInstance();
		$avatarUrl = $urlManager->createByController(
			new ShareRole(), 'showAvatar',
			[
				'roleId' => $roleId,
			]
		);
		$roleData['AVATAR_URL'] = $roleData['AVATAR']['small'];
		$roleData['AVATAR'] = (string)$avatarUrl;

		return new RoleForUpdateDto($roleData);
	}

	public function getAvatarIdByRoleId(int $roleId): int
	{
		$roleData = $this->roleRepository->getAvatarByRoleId($roleId);
		$paths = explode('/',$roleData['AVATAR']['small']);
		$fileName = $paths[array_key_last($paths)];

		return (int)\CFile::GetList([],
			[
				'MODULE_ID' => 'ai',
				'FILE_NAME' => $fileName,
			])
			->Fetch()['ID'];
	}

	public function changeActivateRole(int $roleId, bool $needActivate, int $userId): void
	{
		$this->roleRepository->changeActivateRole($roleId, $needActivate, $userId);
	}

	public function getRoleIdByCode(string $code): ?int
	{
		$roleData = $this->roleRepository->getByCode($code);

		if (empty($roleData['ID']))
		{
			return null;
		}

		return (int)$roleData['ID'];
	}

	public function getRoleIdInAccessibleList(int $userId, int $roleId, ?bool $ignoreDelete = null): ?int
	{
		$data = $this->roleRepository->getRoleIdInAccessibleList($userId, $roleId, $ignoreDelete);
		if (empty($data))
		{
			return null;
		}

		return (int)$data['ID'];
	}

	public function changeActivateRoles(array $roleIds, bool $needActivate, int $userId): Result
	{
		$this->roleRepository->changeActivateRoles($roleIds, $needActivate, $userId);

		return new Result();
	}

	public function getMainRoleDataByCodes(array $roleCodes): array
	{
		$rolesData = $this->roleRepository->getByRoleCodes($roleCodes);

		$result = [];
		foreach ($rolesData as $roleData)
		{
			$result[] = [(int)$roleData['ID'], $roleData['IS_SYSTEM'] === 'Y'];
		}

		return $result;
	}

	public function addTranslateNames(int $roleId, array $names, bool $needDeleteOld = false): void
	{
		if ($needDeleteOld)
		{
			$this->translateNameRepository->deleteByRoleId($roleId);
		}

		if (!empty($names))
		{
			$this->translateNameRepository->addNamesForRole($roleId, $names);
		}
	}

	public function addTranslateDescriptions(int $roleId, array $descriptions, bool $needDeleteOld = false): void
	{
		if ($needDeleteOld)
		{
			$this->translateDescriptionRepository->deleteByRoleId($roleId);
		}

		if (!empty($descriptions))
		{
			$this->translateDescriptionRepository->addDescriptionsForRole($roleId, $descriptions);
		}
	}

	private static function getPromptService(): PromptService
	{
		return Container::init()->getItem(PromptService::class);
	}
}
