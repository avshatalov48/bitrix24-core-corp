<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync\Command;


use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchRepo;
use Bitrix\Crm\Activity\FastSearch\Sync\ActivitySearchData;

final class Upsert implements SyncCommand
{
	private ActivityFastSearchRepo $activityFastsearchRepo;

	public function __construct(private ActivitySearchData $data)
	{
		$this->activityFastsearchRepo = ActivityFastSearchRepo::getInstance();
	}

	public function execute(): void
	{
		$this->activityFastsearchRepo->upsert($this->data->toORMArray());
	}

}