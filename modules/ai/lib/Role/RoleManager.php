<?php

declare(strict_types = 1);

namespace Bitrix\AI\Role;

use Bitrix\AI\Container;
use Bitrix\AI\Entity\TranslateTrait;
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
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

class RoleManager
{
	use TranslateTrait;

	private const UNIVERSAL_ROLE_CODE = 'copilot_assistant';
	private const RECENT_ROLE_LIMIT = 10;

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
			->setSelect(['*', 'RULES'])
			->setFilter(['=CODE' => $roleCodes])
			->fetchCollection()
		;

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
			->setSelect(['*', 'RULES'])
			->setFilter(['=CODE' => $roleCode])
			->fetchObject()
		;

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
		$industries = RoleIndustryTable::query()
			->setSelect(['CODE', 'NAME_TRANSLATES', 'ROLES', 'IS_NEW', 'ROLES.RULES'])
			->setOrder(['SORT' => 'ASC', 'ROLES.IS_NEW' => 'DESC', 'ROLES.SORT' => 'ASC'])
			->fetchCollection()
		;

		foreach ($industries as $industry)
		{
			$result[] = [
				'code' => $industry->getCode(),
				'name' => $industry->getName($this->languageCode),
				'roles' => $this->convertToArrayOnlyAvailableRoles($industry->getRoles()),
				'isNew' => $industry->getIsNew(),
			];
		}

		return $result;
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
	  		->setSelect(['*', 'RULES'])
			->setFilter(['IS_RECOMMENDED' => true])
			->setOrder(['IS_NEW' => 'DESC', 'SORT' => 'ASC'])
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->convertToArrayOnlyAvailableRoles($roles);
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
		$availableRuleService = static::getAvailableRuleService();

		$items = [];
		foreach ($roles as $role)
		{
			if (
				$role->getCode() === self::UNIVERSAL_ROLE_CODE
				|| $availableRuleService->isAvailableRules($role->getRules(), $this->languageCode)
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
			'name' => $role->getName($this->languageCode),
			'description' => $role->getDescription($this->languageCode),
			'avatar' => $role->getAvatar(),
			'industryCode' => $role->getIndustryCode(),
			'isNew' => $role->getIsNew(),
			'isRecommended' => $role->getIsRecommended(),
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
		$recentsRoles = RecentRoleTable::query()
			->setSelect([
				'ROLE', 'ROLE.RULES',
			])
			->setFilter(['USER_ID' => $this->userId, '!=ROLE.CODE' => [self::UNIVERSAL_ROLE_CODE, 'copilot_assistant_chat']])
			->setOrder(['DATE_TOUCH' => 'DESC'])
			->fetchCollection()
		;

		$availableRuleService = static::getAvailableRuleService();

		$roles = [];
		foreach ($recentsRoles as $role)
		{
			$roleItem = $role->getRole();
			if ($availableRuleService->isAvailableRules($roleItem->getRules(), $this->languageCode))
			{
				$roles[] = $this->convertRoleToArray($roleItem);
			}

		}

		return $roles;
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
		$favoriteRoles = RoleFavoriteTable::query()
			->setSelect([
				'ROLE', 'ROLE.RULES'
			])
			->setFilter(['USER_ID' => $this->userId])
			->setOrder(['DATE_CREATE' => 'DESC'])
			->fetchCollection()
		;

		$availableRuleService = static::getAvailableRuleService();

		$roles = [];
		foreach ($favoriteRoles as $role)
		{
			if (!$availableRuleService->isAvailableRules($role->getRole()->getRules(), $this->languageCode))
			{
				continue;
			}

			$roles[] = $this->convertRoleToArray($role->getRole());
		}

		return $roles;
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

	private function getPromptRepository(): PromptRepository
	{
		return Container::init()->getItem(PromptRepository::class);
	}

	private function getAvailableRuleService(): AvailableRuleService
	{
		return Container::init()->getItem(AvailableRuleService::class);
	}
}
