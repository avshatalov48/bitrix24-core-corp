<?php

namespace Bitrix\Crm\Activity\Provider\ToDo;

use Bitrix\Crm\Activity\Provider\ToDo\Block\Address;
use Bitrix\Crm\Activity\Provider\ToDo\Block\BlockInterface;
use Bitrix\Crm\Activity\Provider\ToDo\Block\Calendar;
use Bitrix\Crm\Activity\Provider\ToDo\Block\Client;
use Bitrix\Crm\Activity\Provider\ToDo\Block\File;
use Bitrix\Crm\Activity\Provider\ToDo\Block\Link;

final class BlocksFactory
{
	public static function getInstance(
		string $name,
		array $blockData = [],
		array $activityData = []
	): ?BlockInterface
	{
		if ($name === Calendar::TYPE_NAME)
		{
			return new Calendar($blockData, $activityData);
		}

		if ($name === Client::TYPE_NAME)
		{
			return new Client($blockData, $activityData);
		}

		if ($name === Link::TYPE_NAME)
		{
			return new Link($blockData, $activityData);
		}

		if ($name === File::TYPE_NAME)
		{
			return new File($blockData, $activityData);
		}

		if ($name === Address::TYPE_NAME)
		{
			return new Address($blockData, $activityData);
		}

		return null;
	}
}
