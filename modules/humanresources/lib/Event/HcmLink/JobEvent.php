<?php
namespace Bitrix\HumanResources\Event\HcmLink;
use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\Main\Event;

class JobEvent extends Event
{
	public const MODULE_ID = 'humanresources';
	public const EVENT_NAME = 'OnHumanResourcesHcmLinkJobChanged';

	public function __construct(
		public readonly Job $job
	)
	{
		parent::__construct(self::MODULE_ID, self::EVENT_NAME, ['job' => $job]);
	}
}