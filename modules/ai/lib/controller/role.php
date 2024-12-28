<?php
namespace Bitrix\AI\Controller;

use Bitrix\AI\Parameter\DefaultParameter;
use Bitrix\AI\Prompt;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Role\RoleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;

class Role extends Controller
{

	public function getAutoWiredParameters()
	{
		return [
			new DefaultParameter(),
			new ExactParameter(
				RoleManager::class,
				'roleManager',
				function($className, $parameters){
					return new $className(User::getCurrentUserId(), User::getUserLanguage());
				}
			)
		];
	}

	public function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
		];
	}

	/**
	 * Returns Roles list by industry.
	 *
	 * @param array $parameters Parameters with context. See parent class.
	 * @return array
	 */
	public function listAction(RoleManager $roleManager, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		return [
			'items' => $roleManager->getIndustriesWithRoles(),
		];
	}

	/**
	 * Returns History role's usage.
	 *
	 * @param array $parameters Parameters with context. See parent class.
	 * @return array
	 */
	public function recentsAction(RoleManager $roleManager, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		return [
			'items' => $roleManager->getRecentRoles(),
		];
	}

	/**
	 * Returns Recommended role's list.
	 *
	 * @param array $parameters Parameters with context. See parent class.
	 * @return array
	 */
	public function recommendsAction(RoleManager $roleManager, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		return [
			'items' => $roleManager->getRecommendedRoles(),
		];
	}

	/**
	 * Add role to favorite list.
	 *
	 * @param RoleManager $roleManager
	 * @param string|null $roleCode
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function addFavoriteAction(RoleManager $roleManager, string $roleCode = null, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		$role = Prompt\Role::get($roleCode);

		if ($role === null)
		{
			$this->addError(new Error('Role not found'));

			return [];
		}

		$roleManager->addFavoriteRole($role);

		return [
			'items' => $roleManager->getFavoriteRoles(),
		];
	}

	/**
	 * Drop role from favorite list.
	 *
	 * @param RoleManager $roleManager
	 * @param string|null $roleCode
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function removeFavoriteAction(RoleManager $roleManager, string $roleCode = null, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		$role = Prompt\Role::get($roleCode);

		if ($role === null)
		{
			$this->addError(new Error('Role not found'));

			return [];
		}

		$roleManager->removeFavoriteRole($role);

		return [
			'items' => $roleManager->getFavoriteRoles(),
		];
	}

	/**
	 * Return favorite role's list.
	 *
	 * @param RoleManager $roleManager
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function listFavoritesAction(RoleManager $roleManager, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		return [
			'items' => $roleManager->getFavoriteRoles(),
		];
	}


	/**
	 * Returns default roles information
	 *
	 * @param array $parameters Parameters with context. See parent class.
	 * @return array
	 */
	public function pickerAction(RoleManager $roleManager, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		return [
			'universalRole' => $roleManager->getUniversalRole(),
			'items' => $roleManager->getIndustriesWithRoles(),
			'recommended' => $roleManager->getRecommendedRoles(),
			'recents' => $roleManager->getRecentRoles(),
			'favorites' => $roleManager->getFavoriteRoles(),
			'customs' => $roleManager->getCustomRoles(),
		];
	}
}
