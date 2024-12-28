<?php

declare(strict_types = 1);

namespace Bitrix\AI\Role;

use Bitrix\AI\Container;
use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\RoleTranslateDescriptionTable;
use Bitrix\AI\Model\RoleTranslateNameTable;
use Bitrix\AI\Prompt;
use Bitrix\AI\Dto\PromptDto;
use Bitrix\AI\Dto\PromptType;
use Bitrix\AI\Entity\Role;
use Bitrix\AI\Model\EO_Role_Collection;
use Bitrix\AI\Model\RoleFavoriteTable;
use Bitrix\AI\Model\RoleIndustryTable;
use Bitrix\AI\Model\RecentRoleTable;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Repository\PromptRepository;
use Bitrix\AI\Services\AvailableRuleService;
use Bitrix\AI\ShareRole\Model\OwnerTable;
use Bitrix\AI\ShareRole\Model\ShareTable;
use Bitrix\AI\ShareRole\Repository\UserAccessRepository;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserAccessTable;

class RoleManager
{
	use TranslateTrait;

	private const UNIVERSAL_ROLE_CODE = 'copilot_assistant';
	private const LIBRARY_SYSTEM_ROLE_CODE = 'library_system_role';
	private const RECENT_ROLE_LIMIT = 10;
	private const FLAG_IS_NOT_SYSTEM = 'N';
	private const FLAG_IS_ACTIVE = 1;
	private int $userId;
	private string $languageCode;

	/**
	 * @param int $userId
	 * @param string $language
	 */
	public function __construct(int $userId, string $language)
	{
		$this->userId = $userId;
		$this->languageCode = $language;
	}

	/**
	 * Get exists roles list by code
	 *
	 * @param string[] $roleCodes
	 *
	 * @return array
	 */
	public function getRolesByCode(array $roleCodes): array
	{
		$roles = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'IS_ACTIVE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'RULES',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
			])
			->setFilter([
				'=CODE' => $roleCodes,
			])
		;

		$roles = $this->addTranslateReferenceFields($roles);
		$roles = $roles->fetchCollection();

		return $this->convertToArrayOnlyAvailableRoles($roles);
	}

	/**
	 * Get exists role by code
	 *
	 * @param string $roleCode
	 *
	 * @return array|null
	 */
	public function getRoleByCode(string $roleCode): array|null
	{
		$role = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'IS_ACTIVE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'RULES',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
			])
			->setFilter([
				'=CODE' => $roleCode,
			])
		;
		$role = $this->addTranslateReferenceFields($role);
		$role = $role->fetchObject();

		if (!$role || !$this->getAvailableRuleService()->isAvailableRules($role->getRules(), $this->languageCode))
		{
			return null;
		}

		return $this->convertRoleToArray($role);
	}

	/**
	 * Returns roles list by industry.
	 *
	 * @return array
	 */
	public function getIndustriesWithRoles(): array
	{
		$result = [];
		$roles = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'IS_ACTIVE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'RULES',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
				'INDUSTRY.CODE',
				'INDUSTRY.NAME_TRANSLATES',
				'INDUSTRY.IS_NEW',
			])
			->where(Query::filter()
				->logic('and')
				->whereNot('INDUSTRY_CODE', 'custom')
				->whereNot('INDUSTRY_CODE', '')
			)
			->setOrder(['INDUSTRY.SORT' => 'ASC', 'IS_NEW' => 'DESC', 'SORT' => 'ASC'])
		;

		$roles = $this->addTranslateReferenceFields($roles);
		$roles = $roles->fetchCollection();

		foreach ($roles as $role) {
			$industryCode = $role->get('INDUSTRY_CODE');
			$industryName = $role->get('INDUSTRY')->getNameTranslates();
			$industryIsNew = $role->get('INDUSTRY')->getIsNew();
			$result[$industryCode]['name'] = $industryName[$this->languageCode] ?? $industryName['en'];
			$result[$industryCode]['isNew'] = $industryIsNew;
			$result[$industryCode]['code'] = $industryCode;
			$result[$industryCode]['roles'][] = $this->convertRoleToArray($role);
		}

		return array_values($result);
	}

	/**
	 * Get list of recommended roles
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getRecommendedRoles(int $limit = 10): array
	{
		if ($limit < 0)
		{
			$limit = 10;
		}

		$roles = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'IS_ACTIVE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'RULES',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
			])
			->setFilter(['IS_RECOMMENDED' => true])
			->setOrder(['IS_NEW' => 'DESC', 'SORT' => 'ASC'])
			->setLimit($limit * 2)
		;

		$roles = $this->addTranslateReferenceFields($roles);
		$roles = $roles->fetchCollection();

		return $this->convertToArrayOnlyAvailableRoles($roles);
	}

	/**
	 * Get list of custom roles for current user
	 *
	 * @return array
	 */
	public function getCustomRoles(): array
	{
		$userId = User::getCurrentUserId();

		$userAccessRepository = new UserAccessRepository();
		$accessGroups = $userAccessRepository->getCodesForUserGroup($userId);
		$accessCodes = array_merge($accessGroups, [UserAccessRepository::CODE_ALL_USER]);

		$userAccessSubQuery = UserAccessTable::query()
			->setSelect(['ACCESS_CODE'])
			->where('USER_ID', $userId);

		$roles = RoleTable::query()
			->setSelect([
				'CODE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'IS_ACTIVE',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
			])
			->registerRuntimeField(new Reference(
				'ROLE_SHARE',
				ShareTable::class,
				Join::on('this.ID', 'ref.ROLE_ID'),
				['join_type' => Join::TYPE_LEFT]
			))
			->registerRuntimeField(new Reference(
				'ROLE_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.ROLE_ID')
					->where('ref.USER_ID', '=', $userId)
			))
			->where('IS_SYSTEM', self::FLAG_IS_NOT_SYSTEM)
			->where('IS_ACTIVE', self::FLAG_IS_ACTIVE)
			->where(
				Query::filter()
					->logic('or')
					->whereIn('ROLE_SHARE.ACCESS_CODE', $accessCodes)
					->whereIn('ROLE_SHARE.ACCESS_CODE', $userAccessSubQuery)
			)
			->where(
				Query::filter()
					->logic('or')
					->where('ROLE_OWNERS.IS_DELETED', 0)
					->whereNull('ROLE_OWNERS.ID')
			)
		;

		$roles = $this->addTranslateReferenceFields($roles);
		$roles = $roles->fetchCollection();

		$result = [];

		foreach ($roles as $role)
		{
			$result[] = $this->convertRoleToArray($role);
		}

		return $result;
	}

	/**
	 * Return universal role code for default
	 *
	 * @return string
	 */
	public static function getUniversalRoleCode(): string
	{
		return self::UNIVERSAL_ROLE_CODE;
	}

	/**
	 * Return system role preprompt code
	 *
	 * @return string
	 */
	public static function getLibrarySystemRoleCode(): string
	{
		return self::LIBRARY_SYSTEM_ROLE_CODE;
	}

	/**
	 * Return universal role
	 *
	 * @return array|null
	 */
	public function getUniversalRole(): array|null
	{
		return $this->getRoleByCode(self::UNIVERSAL_ROLE_CODE);
	}

	/**
	 * Returned role by role code or universal role
	 *
	 * @param string $roleCode
	 * @return array|null
	 */
	public function getRoleByCodeOrUniversalRole(string $roleCode): array|null
	{
		if (!empty($roleCode))
		{
			$role = $this->getRoleByCode($roleCode);
		}

		if (empty($role))
		{
			return $this->getRoleByCode(self::getUniversalRoleCode());
		}

		return $role;
	}

	/**
	 * Convert roles collection to array.
	 *
	 * @param EO_Role_Collection $roles
	 * @return array
	 */
	private function convertToArrayOnlyAvailableRoles(EO_Role_Collection $roles): array
	{
		$availableRuleService = $this->getAvailableRuleService();

		$items = [];
		foreach ($roles as $role)
		{
			if (
				$role->getCode() === self::UNIVERSAL_ROLE_CODE
				|| ($availableRuleService->isAvailableRules($role->getRules(), $this->languageCode)
					&& $role->getIsActive())
			)
			{
				$items[] = $this->convertRoleToArray($role);
			}
		}

		return $items;
	}

	/**
	 * Convert role to array.
	 *
	 * @param Role $role
	 *
	 * @return array
	 */
	private function convertRoleToArray(Role $role): array
	{
		return [
			'code' => $role->getCode(),
			'name' => $role->getName(),
			'description' => $role->getDescription(),
			'avatar' => $role->getAvatar(),
			'industryCode' => $role->getIndustryCode(),
			'isNew' => $role->getIsNew(),
			'isRecommended' => $role->getIsRecommended(),
			'isSystem' => $role->getIsSystem(),
		];
	}

	/**
	 * Save role code to recent role table.
	 *
	 * @param Prompt\Role $role role code.
	 * @return void
	 */
	public function addRecentRole(Prompt\Role $role): void
	{
		$helper = Application::getConnection()->getSqlHelper();

		$merge = $helper->prepareMerge(
			RecentRoleTable::getTableName(),
			['ROLE_CODE', 'USER_ID'],
			[
				'ROLE_CODE' => $role->getCode(),
				'USER_ID' => $this->userId,
			],
			[
				'ROLE_CODE' => $role->getCode(),
				'USER_ID' => $this->userId,
				'DATE_TOUCH' => new DateTime(),
			]
		);

		if ($merge[0] != '')
		{
			Application::getConnection()->query($merge[0]);
		}
	}

	/**
	 * Get list of recent used roles
	 *
	 * @return array
	 */
	public function getRecentRoles(): array
	{
		$recentsRoles = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'IS_ACTIVE',
				'RULES',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
				'RECENT_ROLE.DATE_TOUCH'
			])
			->registerRuntimeField(new Reference(
				'RECENT_ROLE',
				RecentRoleTable::class,
				Join::on('this.CODE', 'ref.ROLE_CODE')
					->where('ref.USER_ID', $this->userId),
				['join_type' => Join::TYPE_INNER]
			))
			->setFilter([
				'!=CODE' => [self::UNIVERSAL_ROLE_CODE, 'copilot_assistant_chat'],
				'RECENT_ROLE.USER_ID' => $this->userId
			])
			->setOrder(['RECENT_ROLE.DATE_TOUCH' => 'DESC']);

		$recentsRoles = $this->addTranslateReferenceFields($recentsRoles)->fetchCollection();

		return $this->convertToArrayOnlyAvailableRoles($recentsRoles);
	}

	/**
	 * Add role to favorite role table.
	 *
	 * @param Prompt\Role $role role code.
	 *
	 * @return void
	 */
	public function addFavoriteRole(Prompt\Role $role): void
	{
		$exists = RoleFavoriteTable::query()
			->setSelect(['ID'])
			->setFilter([
				'=ROLE_CODE' => $role->getCode(),
				'USER_ID' => $this->userId,
			])
			->fetchObject()
		;

		if ($exists !== null)
		{
			return;
		}

		RoleFavoriteTable::add([
			'ROLE_CODE' => $role->getCode(),
			'USER_ID' => $this->userId,
		]);
	}

	/**
	 * Remove role code from favorite role table.
	 *
	 * @param Prompt\Role $role role code.
	 *
	 * @return void
	 */
	public function removeFavoriteRole(Prompt\Role $role): void
	{
		RoleFavoriteTable::deleteByFilter([
			'ROLE_CODE' => $role->getCode(),
			'USER_ID' => $this->userId,
		]);
	}

	/**
	 * Return list of favorite roles.
	 *
	 * @return array
	 */
	public function getFavoriteRoles(): array
	{
		$favoriteRoles = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
				'INDUSTRY_CODE',
				'IS_NEW',
				'IS_RECOMMENDED',
				'IS_SYSTEM',
				'IS_ACTIVE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'RULES',
				'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'ROLE_TRANSLATE_NAME.TEXT',
				'ROLE_FAVORITE.DATE_CREATE'
			])
			->registerRuntimeField(new Reference(
				'ROLE_FAVORITE',
				RoleFavoriteTable::class,
				Join::on('this.CODE', 'ref.ROLE_CODE')
					->where('ref.USER_ID', $this->userId),
				['join_type' => Join::TYPE_INNER]
			))
			->setFilter([
				'ROLE_FAVORITE.USER_ID' => $this->userId
			])
			->setOrder(['ROLE_FAVORITE.DATE_CREATE' => 'DESC']);

		$favoriteRoles = $this->addTranslateReferenceFields($favoriteRoles)->fetchCollection();

		return $this->convertToArrayOnlyAvailableRoles($favoriteRoles);
	}

	/**
	 * Get list prompts by category and roleCode
	 *
	 * @param string $category
	 * @param string $roleCode
	 *
	 * @return PromptDto[]
	 */
	public function getPromptsBy(string $category, string $roleCode): array
	{
		$prompts = [];
		$role = RoleTable::query()
			->setSelect(['RULES'])
			->setFilter(['=CODE' => $roleCode])
			->fetchObject()
		;

		if(
			$role === null
			|| !$this->getAvailableRuleService()->isAvailableRules($role->getRules(), $this->languageCode)
		)
		{
			return $prompts;
		}

		$prompts = $this->getPromptRepository()->getPromptsByRoleCodes(
			$category,
			$roleCode,
			$this->languageCode
		);

		if (empty($prompts))
		{
			return [];
		}

		$result = [];
		foreach ($prompts as $promptData)
		{
			try
			{
				/** @var PromptType $promptType */
				$promptType = (new \ReflectionEnum(PromptType::class))
					->getCase($promptData['TYPE'])
					->getValue()
				;
			}
			catch (\Exception $exception)
			{
				continue;
			}

			$prompt = $this->preparePrompt($promptData);

			$result[] = new PromptDto(
				$prompt['CODE'],
				$promptType,
				$prompt['TITLE'],
				$prompt['TRANSLATE'],
				$prompt['IS_NEW'] == 1,
			);
		}

		return $result;
	}

	protected function preparePrompt(array $prompt): array
	{
		$prompt['TRANSLATE'] = '';
		if (!empty($prompt['TEXT_TRANSLATES']))
		{
			$prompt['TRANSLATE'] = self::translate($prompt['TEXT_TRANSLATES'], $this->languageCode);
		}

		$prompt['TITLE'] = '';
		if (!empty($prompt['TITLE_DEFAULT']))
		{
			$prompt['TITLE'] = $prompt['TITLE_DEFAULT'];
		}

		if (!empty($prompt['TITLE_FOR_USER']))
		{
			$prompt['TITLE'] = $prompt['TITLE_FOR_USER'];
		}

		return $prompt;
	}

	private function addTranslateReferenceFields(Query $query): Query
	{
		$userLanguage = User::getUserLanguage();

		return $query
			->registerRuntimeField(new Reference(
					'ROLE_TRANSLATE_DESCRIPTION',
					RoleTranslateDescriptionTable::class,
					Join::on('this.ID', 'ref.ROLE_ID')
						->where('ref.LANG', $userLanguage),
					['join_type' => Join::TYPE_LEFT]
				)
			)
			->registerRuntimeField(new Reference(
					'ROLE_TRANSLATE_NAME',
					RoleTranslateNameTable::class,
					Join::on('this.ID', 'ref.ROLE_ID')
						->where('ref.LANG', $userLanguage),
					['join_type' => Join::TYPE_LEFT]
				)
			)
		;
	}

	private function getPromptRepository(): PromptRepository
	{
		return Container::init()->getItem(PromptRepository::class);
	}

	private function getAvailableRuleService(): AvailableRuleService
	{
		return Container::init()->getItem(AvailableRuleService::class);
	}
}
