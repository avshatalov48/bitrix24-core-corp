<?php
declare(strict_types=1);

namespace Bitrix\Disk\Realtime\Events;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Realtime;
use Bitrix\Disk\Realtime\Channels\ObjectChannel;

final class ObjectEvent extends Realtime\Event
{
	public function __construct(
		private readonly BaseObject $baseObject,
		string $category,
		array $data = []
	)
	{
		parent::__construct($category, $data);
	}

	public function sendToObjectChannel(): void
	{
		$realObjectId = (int)$this->baseObject->getRealObjectId();

		$objectChannel = new ObjectChannel($realObjectId);
		$objectTag = new Realtime\Tags\ObjectTag($realObjectId);

		$this->send([
			$objectChannel,
			$objectTag,
		]);
	}
}