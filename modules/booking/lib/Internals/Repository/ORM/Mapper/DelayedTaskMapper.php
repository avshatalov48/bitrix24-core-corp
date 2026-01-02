<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Internals\Model\EO_DelayedTask;
use Bitrix\Booking\Internals\Service\DelayedTask\Data\DelayedTaskDataMapper;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTask;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskStatus;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskType;
use Bitrix\Main\Web\Json;

class DelayedTaskMapper
{
	public function convertFromOrm(EO_DelayedTask $ormDelayedTask): DelayedTask
	{
		$delayedTask = new DelayedTask();
		$type = DelayedTaskType::from($ormDelayedTask->getType());
		$delayedTask
			->setId($ormDelayedTask->getId())
			->setCode($ormDelayedTask->getCode())
			->setType($type)
			->setData(DelayedTaskDataMapper::mapFromArray($type, Json::decode($ormDelayedTask->getData())))
			->setStatus(DelayedTaskStatus::from($ormDelayedTask->getStatus()))
			->setCreatedAt($ormDelayedTask->getCreatedAt()->getTimestamp())
			->setUpdatedAt($ormDelayedTask->getUpdatedAt()->getTimestamp())
		;

		return $delayedTask;
	}
}
