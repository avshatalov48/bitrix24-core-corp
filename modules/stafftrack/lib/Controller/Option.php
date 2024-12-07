<?php

namespace Bitrix\StaffTrack\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\StaffTrack\Controller\Trait\ErrorResponseTrait;
use Bitrix\StaffTrack\Controller\Trait\IntranetUserTrait;
use Bitrix\StaffTrack\Dictionary;
use Bitrix\StaffTrack\Service\OptionService;

class Option extends Controller
{
	use IntranetUserTrait;
	use ErrorResponseTrait;

	private ?int $userId = null;
	private OptionService $service;

	public function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->service = OptionService::getInstance();
	}

	public function saveSelectedDepartmentIdAction(int $departmentId): void
	{
		$this->service->save($this->userId, Dictionary\Option::SELECTED_DEPARTMENT_ID, $departmentId);
	}

	/**
	 * @return array
	 * @throws LoaderException
	 */
	public function handleFirstHelpViewAction(): array
	{
		$result = [];

		$userId = (int)CurrentUser::get()->getId();
		if (!$this->isIntranetUser($userId))
		{
			return $this->buildErrorResponse('User not found');
		}

		OptionService::getInstance()->save($userId, Dictionary\Option::IS_FIRST_HELP_VIEWED, 'Y');

		return $result;
	}


	/**
	 * @param string $enabled
	 * @return array
	 * @throws LoaderException
	 */
	public function changeTimemanIntegrationOptionAction(string $enabled): array
	{
		$result = [];

		$userId = (int)CurrentUser::get()->getId();
		if (!$this->isIntranetUser($userId))
		{
			return $this->buildErrorResponse('User not found');
		}

		OptionService::getInstance()->save($userId, Dictionary\Option::TIMEMAN_INTEGRATION_ENABLED, $enabled === 'Y' ? 'Y' : 'N');

		return $result;
	}
}
