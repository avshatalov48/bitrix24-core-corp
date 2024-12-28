<?php
declare(strict_types=1);

namespace Bitrix\Disk\Realtime\Events;

use Bitrix\Disk\Realtime;

final class UserRecentsEvent extends Realtime\Event
{
	public function __construct(
		private readonly int $userId,
		string $category,
		array $data = []
	)
	{
		parent::__construct($category, $data);
	}

	public function sendToUser(): void
	{
		$this->send([
			new Realtime\Tags\UserRecentsTag($this->userId),
		]);
	}
}