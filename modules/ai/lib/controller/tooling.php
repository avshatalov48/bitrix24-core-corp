<?php
namespace Bitrix\AI\Controller;

use Bitrix\AI\Config;
use Bitrix\AI\Engine;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\Rest;
use Bitrix\AI\Facade\User;
use Bitrix\AI\History;
use Bitrix\AI\Prompt;
use Bitrix\AI\Role\RoleManager;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;

class Tooling extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
		];
	}

	/**
	 * Returns tooling for Client's UI by category.
	 *
	 * @param string $category Engine's category.
	 * @param array $parameters Additional params for tuning query.
	 * @return array
	 */
	public function getAction(string $category, array $parameters = []): array
	{
		if (!empty($this->getErrors()))
		{
			return [];
		}

		$roleCode = $parameters['roleCode'] ?? User::getLastUsedRoleCode($category , $this->context->getModuleId());
		$roleManager = new RoleManager(User::getCurrentUserId(), User::getUserLanguage());

		return [
			'engines' => Engine::getData($category, $this->context),
			'history' => [
				'items' => History\Manager::readHistory($this->context)->toArray(),
				'capacity' => History\Manager::getCapacity(),
			],
			'prompts' => isset($parameters['promptCategory'])
				? Prompt\Manager::getList($parameters['promptCategory'], $roleCode)
				: null
			,
			'role' => $roleManager->getRoleByCodeOrUniversalRole($roleCode),
			'permissions' => [
				'can_edit_settings' => User::isAdmin(),
			],
			// todo replace with Bitrix24:getPortalZone() when Bitrix24:getPortalZone() will be fixed
			'portal_zone' => Loader::includeModule('bitrix24') ? Bitrix24::getPortalZone() : LANGUAGE_ID,
			'kits' => array_map(fn($code) => [
				'code' => $code,
				'installed' => Rest::isMarketKitInstalled($code),
				'install_started' => Config::getValue('install_started') === 'Y',
			], Rest::getMarketKits()),
			'first_launch' => Config::getPersonalValue('first_launch') !== 'N' && Bitrix24::shouldUseB24(),
		];
	}

	/**
	 * Marks that AI was launched.
	 *
	 * @return void
	 */
	public function setLaunchedAction(): void
	{
		Config::setPersonalValue('first_launch', 'N');
	}

	/**
	 * Install Market kit (set of applications) by kit code.
	 *
	 * @param string $code
	 * @param array $parameters
	 * @return void
	 */
	public function installKitAction(string $code, array $parameters = []): void
	{
		Config::setOptionsValue('install_started', 'Y');
		Config::setPersonalValue('first_launch', 'N');

		Rest::installMarketKit($code, $error);
		if (!empty($error))
		{
			$this->addError($error);
		}
	}

	/**
	 * Deletes all system prompts from local DB and loads new.
	 *
	 * @param array $parameters System params.
	 * @return void
	 */
	public static function refreshPromptsAction(array $parameters = []): void
	{
		Prompt\Manager::clearAndRefresh();
	}
}
