<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync\Command;


use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchRepo;

final class Remove implements SyncCommand
{
	private ActivityFastSearchRepo $activityFastsearchRepo;

	public function __construct(private int $actId)
	{
		$this->activityFastsearchRepo = ActivityFastSearchRepo::getInstance();
	}

	public function execute(): void
	{
		$this->activityFastsearchRepo->delete($this->actId);
	}

}