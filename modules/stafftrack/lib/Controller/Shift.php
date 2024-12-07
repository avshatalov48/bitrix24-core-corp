<?php

namespace Bitrix\StaffTrack\Controller;

use Bitrix\Main;
use Bitrix\Main\Access\Exception\UnknownActionException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SystemException;
use Bitrix\StaffTrack\Access\Model\ShiftModel;
use Bitrix\StaffTrack\Access\ShiftAccessController;
use Bitrix\StaffTrack\Access\ShiftAction;
use Bitrix\StaffTrack\Controller\Trait\ErrorResponseTrait;
use Bitrix\StaffTrack\Controller\Trait\IntranetUserTrait;
use Bitrix\StaffTrack\Helper\DateHelper;
use Bitrix\StaffTrack\Internals\Exception\InvalidDtoException;
use Bitrix\StaffTrack\Model;
use Bitrix\StaffTrack\Provider\ShiftProvider;
use Bitrix\StaffTrack\Service\ShiftService;
use Bitrix\StaffTrack\Shift\ShiftDto;
use Bitrix\StaffTrack\Shift\ShiftMapper;
use Bitrix\StaffTrack\Shift\ShiftRegistry;

class Shift extends Controller
{
	use ErrorResponseTrait;
	use IntranetUserTrait;

	private int $userId;

	private ShiftProvider $provider;
	private ShiftService $service;
	private ShiftAccessController $accessController;

	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				ShiftDto::class,
				'shiftDto',
				fn (string $className, array $fields): ShiftDto => ShiftDto::createFromArray($fields),
			),
			new ExactParameter(
				Model\Shift::class,
				'shift',
				fn (string $className, int $id): ?Model\Shift => ShiftRegistry::getInstance()->get($id),
			),
		];
	}

	public function init(): void
	{
		parent::init();
		$this->userId = (int)CurrentUser::get()->getId();
		$this->provider = ShiftProvider::getInstance($this->userId);
		$this->service = ShiftService::getInstance($this->userId);
		$this->accessController = ShiftAccessController::getInstance($this->userId);
	}

	/**
	 * @param Model\Shift|null $shift
	 * @return array
	 * @throws LoaderException
	 * @throws UnknownActionException
	 */
	public function getAction(?Model\Shift $shift): array
	{
		if (!$this->isIntranetUser($this->userId))
		{
			return $this->buildErrorResponse('Access denied');
		}

		if ($shift === null)
		{
			return $this->buildErrorResponse('Shift not found');
		}

		$accessModel = ShiftModel::createFromObject($shift);
		if (!$this->accessController->check(ShiftAction::VIEW, $accessModel))
		{
			return $this->buildErrorResponse('Access denied');
		}

		return [
			'shift' => $shift,
		];
	}

	/**
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @param int $limit
	 * @return array
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws UnknownActionException
	 */
	public function listAction(
		array $filter = [],
		array $select = [],
		array $order = [],
		int $limit = 0
	): array
	{
		if (!$this->isIntranetUser($this->userId))
		{
			return $this->buildErrorResponse('Access denied');
		}

		if (empty($filter))
		{
			return $this->buildErrorResponse('Filter must not be empty');
		}

		$collection = $this->provider->list($filter, $select, $order, $limit);

		return [
			'shiftList' => $this->provider->prepareClientData($collection),
		];
	}

	/**
	 * @param ShiftDto $shiftDto
	 * @return array
	 * @throws LoaderException
	 * @throws UnknownActionException
	 */
	public function addAction(ShiftDto $shiftDto): array
	{
		if (!$this->isIntranetUser($this->userId))
		{
			return $this->buildErrorResponse('Access denied');
		}

		$shiftDto = $this->provider->prepareToAdd($shiftDto);
		if (!$this->validateGeo($shiftDto))
		{
			return $this->buildErrorResponse('Stop hacking');
		}

		$accessModel = ShiftModel::createFromDto($shiftDto);
		if (!$this->accessController->check(ShiftAction::ADD, $accessModel))
		{
			return $this->buildErrorResponse('Access denied');
		}

		$existDateShift = $this->provider->findByDate(
			$shiftDto->shiftDate->format(DateHelper::DATE_FORMAT)
		);
		if ($existDateShift !== null)
		{
			return $this->buildErrorResponse('Shift already exist');
		}

		try
		{
			$saveResult = $this->service->add($shiftDto);
		}
		catch (InvalidDtoException $exception)
		{
			return $this->buildErrorResponse($exception->getMessage());
		}

		if ($saveResult->isSuccess())
		{
			return $saveResult->getData();
		}

		$this->addErrors($saveResult->getErrors());

		return [];
	}

	protected function validateGeo(ShiftDto $shiftDto): bool
	{
		if (empty($shiftDto->geoImageUrl) || in_array($shiftDto->geoImageUrl, $this->getRandomMapImages(), true))
		{
			return true;
		}

		try
		{
			$shiftDto
				->setGeoImageUrl((new Signer())->unsign($shiftDto->geoImageUrl))
				->setAddress((new Signer())->unsign($shiftDto->address))
			;
		}
		catch (BadSignatureException | ArgumentTypeException)
		{
			return false;
		}

		return true;
	}

	protected function getRandomMapImages(): array
	{
		$imagesPath = '/bitrix/mobileapp/stafftrackmobile/extensions/stafftrack/map/images';

		return array_map(static fn (int $i) => "$imagesPath/blurred-map-$i.png", range(1, 10));
	}

	/**
	 * @param Model\Shift|null $shift
	 * @param ShiftDto $shiftDto
	 * @return array
	 * @throws LoaderException
	 * @throws UnknownActionException
	 */
	public function updateAction(?Model\Shift $shift, ShiftDto $shiftDto): array
	{
		if (!$this->isIntranetUser($this->userId))
		{
			return $this->buildErrorResponse('Access denied');
		}

		if ($shift === null)
		{
			return $this->buildErrorResponse('Shift not found');
		}

		$shiftDto = $this->provider->prepareToUpdate($shift, $shiftDto);

		$accessModel = ShiftModel::createFromDto($shiftDto);
		if (!$this->accessController->check(ShiftAction::UPDATE, $accessModel))
		{
			return $this->buildErrorResponse('Access denied');
		}

		try
		{
			$updateResult = $this->service->update($shiftDto);
		}
		catch (InvalidDtoException $exception)
		{
			return $this->buildErrorResponse($exception->getMessage());
		}

		if ($updateResult->isSuccess())
		{
			return $updateResult->getData();
		}

		$this->addErrors($updateResult->getErrors());

		return [];
	}

	/**
	 * @param Model\Shift|null $shift
	 * @return array
	 * @throws LoaderException
	 * @throws UnknownActionException
	 */
	public function deleteAction(?Model\Shift $shift): array
	{
		if (!$this->isIntranetUser($this->userId))
		{
			return $this->buildErrorResponse('Access denied');
		}

		if ($shift === null)
		{
			return $this->buildErrorResponse('Shift not found');
		}

		$shiftDto = ShiftMapper::createDtoFromEntity($shift);

		$accessModel = ShiftModel::createFromDto($shiftDto);
		if (!$this->accessController->check(ShiftAction::DELETE, $accessModel))
		{
			return $this->buildErrorResponse('Access denied');
		}

		$deleteResult = $this->service->delete($shiftDto);

		$this->addErrors($deleteResult->getErrors());

		return [
			'isSuccess' => $deleteResult->isSuccess(),
		];
	}
}
