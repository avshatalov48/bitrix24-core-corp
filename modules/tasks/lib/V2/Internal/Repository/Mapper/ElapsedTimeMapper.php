<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Trait\CastTrait;
use CTaskElapsedItem;

class ElapsedTimeMapper
{
	use CastTrait;

	public function __construct(
		private readonly ElapsedTimeSourceMapper $elapsedTimeSourceMapper,
	)
	{

	}

	public function mapToEntity(array $elapsedTime): Entity\Task\ElapsedTime
	{
		$data = [];

		if (isset($elapsedTime['ID']))
		{
			$data['id'] = (int)$elapsedTime['ID'];
		}

		if (isset($elapsedTime['TASK_ID']))
		{
			$data['taskId'] = (int)$elapsedTime['TASK_ID'];
		}

		if (isset($elapsedTime['USER_ID']))
		{
			$data['userId'] = (int)$elapsedTime['USER_ID'];
		}

		if (isset($elapsedTime['SECONDS']))
		{
			$data['seconds'] = (int)$elapsedTime['SECONDS'];
			$data['minutes'] = (int)round($elapsedTime['SECONDS'] / 60);
		}
		elseif (isset($elapsedTime['MINUTES']))
		{
			$data['seconds'] = 60 * (int)$elapsedTime['MINUTES'];
			$data['minutes'] = (int)$elapsedTime['MINUTES'];
		}

		if (isset($elapsedTime['SOURCE']))
		{
			$data['source'] = $this->elapsedTimeSourceMapper->mapToEnum((int)$elapsedTime['SOURCE'])->value;
		}

		if (isset($elapsedTime['COMMENT_TEXT']))
		{
			$data['text'] = (string)$elapsedTime['COMMENT_TEXT'];
		}

		if (isset($elapsedTime['CREATED_DATE']))
		{
			$data['createdAtTs'] = $this->castDateTime($elapsedTime['CREATED_DATE']);
		}

		if (isset($elapsedTime['START_DATE']))
		{
			$data['startTs'] = $this->castDateTime($elapsedTime['START_DATE']);
		}

		if (isset($elapsedTime['STOP_DATE']))
		{
			$data['stopTs'] = $this->castDateTime($elapsedTime['STOP_DATE']);
		}

		return Entity\Task\ElapsedTime::mapFromArray($data);
	}

	public function mapFromEntity(Entity\Task\ElapsedTime $elapsedTime): array
	{
		$data = [];

		if ($elapsedTime->id !== null)
		{
			$data['ID'] = $elapsedTime->id;
		}

		if ($elapsedTime->taskId !== null)
		{
			$data['TASK_ID'] = $elapsedTime->taskId;
		}

		if ($elapsedTime->userId !== null)
		{
			$data['USER_ID'] = $elapsedTime->userId;
		}

		if ($elapsedTime->seconds !== null)
		{
			$data['SECONDS'] = $elapsedTime->seconds;
			$data['MINUTES'] = (int)round($elapsedTime->seconds / 60);
		}
		elseif ($elapsedTime->minutes !== null)
		{
			$data['SECONDS'] = 60 * $elapsedTime->minutes;
			$data['MINUTES'] = $elapsedTime->minutes;
		}

		if ($elapsedTime->source !== null)
		{
			$data['SOURCE'] = $this->elapsedTimeSourceMapper->mapFromEnum($elapsedTime->source);
		}

		if ($elapsedTime->text !== null)
		{
			$data['COMMENT_TEXT'] = $elapsedTime->text;
		}

		if ($elapsedTime->createdAtTs !== null)
		{
			$data['CREATED_DATE'] = $elapsedTime->createdAtTs
				? DateTime::createFromTimestamp($elapsedTime->createdAtTs)
				: new DateTime();
		}

		if ($elapsedTime->startTs !== null)
		{
			$data['DATE_START'] = DateTime::createFromTimestamp($elapsedTime->startTs);
		}

		if ($elapsedTime->stopTs !== null)
		{
			$data['DATE_STOP'] = DateTime::createFromTimestamp($elapsedTime->stopTs);
		}

		return $data;
	}
}