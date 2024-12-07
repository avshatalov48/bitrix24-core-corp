<?php

namespace Bitrix\StaffTrack\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\StaffTrack\Controller\Trait\ErrorResponseTrait;
use Bitrix\StaffTrack\Controller\Trait\IntranetUserTrait;
use Bitrix\StaffTrack\Integration\Im\ChatService;
use Bitrix\StaffTrack\Provider\UserProvider;

class Feature extends Controller
{
	use IntranetUserTrait;
	use ErrorResponseTrait;

	protected UserProvider $userProvider;

	/**
	 * @return void
	 */
	protected function init(): void
	{
		parent::init();

		$this->userProvider = UserProvider::getInstance();
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function turnCheckInSettingOnAction(): void
	{
		if ($this->userIsAdmin())
		{
			\Bitrix\StaffTrack\Feature::turnCheckInSettingOn();
		}
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function turnCheckInSettingOffAction(): void
	{
		if ($this->userIsAdmin())
		{
			\Bitrix\StaffTrack\Feature::turnCheckInSettingOff();
		}
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function turnCheckInGeoOnAction(): void
	{
		if ($this->userIsAdmin())
		{
			\Bitrix\StaffTrack\Feature::turnCheckInGeoOn();
		}
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function turnCheckInGeoOffAction(): void
	{
		if ($this->userIsAdmin())
		{
			\Bitrix\StaffTrack\Feature::turnCheckInGeoOff();
		}
	}

	/**
	 * @param string $featureName
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function createDepartmentHeadChatAction(string $featureName): array
	{
		$userId = (int)CurrentUser::get()->getId();

		if (!$this->isIntranetUser($userId))
		{
			return $this->buildErrorResponse('Access denied');
		}

		$feature = \Bitrix\StaffTrack\Dictionary\Feature::tryFrom($featureName);
		if (!$feature)
		{
			return $this->buildErrorResponse('Wrong feature name');
		}

		$chatCreateResult = (new ChatService($userId))->createDepartmentHeadChat($feature);
		if (!$chatCreateResult->isSuccess())
		{
			$this->addErrors($chatCreateResult->getErrors());

			return [];
		}

		return $chatCreateResult->getData();
	}

	/**
	 * @return bool
	 */
	private function userIsAdmin(): bool
	{
		$userId = (int)CurrentUser::get()->getId();

		return $this->userProvider->isUserAdmin($userId);
	}
}
