<?php

namespace Bitrix\StaffTrack\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\StaffTrack\Access\DepartmentStatisticsAccessController;
use Bitrix\StaffTrack\Access\DepartmentStatisticsAction;
use Bitrix\StaffTrack\Access\Model\DepartmentStatisticsModel;
use Bitrix\StaffTrack\Controller\Trait\ErrorResponseTrait;
use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\Stafftrack\Integration\HumanResources\Structure;
use Bitrix\StaffTrack\Provider\ShiftProvider;
use Bitrix\StaffTrack\Provider\UserProvider;
use Bitrix\Stafftrack\Integration\Pull;

class DepartmentStatistics extends Controller
{
	use ErrorResponseTrait;

	private ?int $userId = null;
	private Structure $structure;
	private ShiftProvider $shiftProvider;
	private UserProvider $userProvider;
	private DepartmentStatisticsAccessController $accessController;

	/**
	 * @return array[]
	 */
	public function configureActions(): array
	{
		return [
			'get' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getForMonth' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->structure = Structure::getInstance();
		$this->shiftProvider = ShiftProvider::getInstance($this->userId);
		$this->userProvider = UserProvider::getInstance();
		$this->accessController = DepartmentStatisticsAccessController::getInstance($this->userId);
	}

	/**
	 * @param int $id
	 * @param string $date
	 * @return array
	 * @throws \Bitrix\Main\Access\Exception\UnknownActionException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getAction(int $id, string $date): ?array
	{
		$userIds = $this->structure->getDepartmentUserIds($id);

		$accessModel = DepartmentStatisticsModel::createFromId($id);
		if (!$this->accessController->check(DepartmentStatisticsAction::VIEW, $accessModel))
		{
			return $this->buildErrorResponse('Access denied');
		}

		$users = $this->userProvider->getUsers($userIds);

		$collection = $this->shiftProvider->list([
			'USER_ID' => $userIds,
			'SHIFT_DATE' => $date,
			'STATUS' => Status::WORKING->value,
		]);

		$shifts = $this->shiftProvider->prepareClientData($collection);

		Pull\PushService::subscribeToTag(Pull\Tag::getDepartmentTag($id));

		return [
			'users' => $users->toArray(),
			'shifts' => $shifts,
		];
	}

	/**
	 * @param int $id
	 * @param string $monthCode
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getForMonthAction(int $id, string $monthCode): ?array
	{
		$userIds = $this->structure->getDepartmentUserIds($id);

		$accessModel = DepartmentStatisticsModel::createFromId($id);
		if (!$this->accessController->check(DepartmentStatisticsAction::VIEW, $accessModel))
		{
			return $this->buildErrorResponse('Access denied');
		}

		$statistics = $this->shiftProvider->getUsersStatisticsByMonthCode($userIds, $monthCode);

		Pull\PushService::subscribeToTag(Pull\Tag::getDepartmentTag($id));

		return [
			'statistics' => $statistics,
		];
	}
}