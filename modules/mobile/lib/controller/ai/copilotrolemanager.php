<?php

namespace Bitrix\Mobile\Controller\Ai;

use Bitrix\Main\Engine\Controller;
use Bitrix\AI\Role\RoleManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Mobile\Dto\Ai\CopilotIndustryDto;
use Bitrix\Mobile\Dto\Ai\CopilotRoleDto;

class CopilotRoleManager extends Controller
{
	/**
	 * @return CopilotIndustryDto[]
	 * @throws LoaderException
	 */
	public function getIndustriesWithRolesAction(): array
	{
		if (!Loader::includeModule('ai'))
		{
			$this->addError(new Error('ai module not included', 1));

			return [];
		}

		$langCode =  \Bitrix\Main\Context::getCurrent()->getLanguage();
		$roleManager = new RoleManager($this->getCurrentUser()->getId(), $langCode);

		$result = [
			'industries' => $this->prepareIndustries($roleManager->getIndustriesWithRoles()),
			'universalRole' => $this->convertRolePropertiesArrayToDto($roleManager->getUniversalRole()),
		];
		return $this->convertKeysToCamelCase($result);
	}

	private function prepareIndustries(array $industries): array
	{
		$result = [];

		foreach ($industries as $industry)
		{
			$result[] = CopilotIndustryDto::make([
				'code' => $industry['code'],
				'name' => $industry['name'],
				'roles' => $this->prepareRoles($industry['roles']),
			]);
		}

		return $result;
	}

	private function prepareRoles(array $roles): array
	{
		$result = [];

		foreach ($roles as $role)
		{
			$result[] = $this->convertRolePropertiesArrayToDto($role);
		}

		return $result;
	}

	private function convertRolePropertiesArrayToDto(array $role): CopilotRoleDto
	{
		return CopilotRoleDto::make([
			'code' => $role['code'],
			'name' => $role['name'],
			'description' => $role['description'],
			'avatar' => $role['avatar'],
			'industryCode' => $role['industryCode'],
			'isRecommended' => $role['isRecommended'],
			'isNew' => $role['isNew'],
		]);
	}
}