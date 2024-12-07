<?php

namespace Bitrix\Crm\Service\Communication\Channel\Queue;

use Bitrix\Crm\Service\Communication\Channel\ChannelInterface;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\MemberRequestDistributionAllInterface;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\MemberRequestDistributionEvenlyInterface;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\TimeBeforeRequestNextMemberInterface;
use Bitrix\Crm\Service\Communication\Channel\Queue\Interface\TimeTrackingInterface;
use Bitrix\Crm\Traits\Singleton;

final class QueueConfig
{
	use Singleton;

	public const CFG_FORWARD_TO = 'FORWARD_TO';
	public const CFG_TIME_TRACKING = 'TIME_TRACKING';
	public const CFG_MEMBER_REQUEST_DISTRIBUTION = 'MEMBER_REQUEST_DISTRIBUTION';
	public const CFG_MEMBER_REQUEST_DISTRIBUTION_STRICTLY = 'MEMBER_REQUEST_DISTRIBUTION_STRICTLY';
	public const CFG_MEMBER_REQUEST_DISTRIBUTION_EVENLY = 'MEMBER_REQUEST_DISTRIBUTION_EVENLY';
	public const CFG_MEMBER_REQUEST_DISTRIBUTION_ALL = 'MEMBER_REQUEST_DISTRIBUTION_ALL';
	public const CFG_TIME_BEFORE_REQUEST_NEXT_MEMBER = 'TIME_BEFORE_REQUEST_NEXT_MEMBER';

	public function get(ChannelInterface $channel): array
	{
		$result[self::CFG_FORWARD_TO] = true; // always enabled
		$result[self::CFG_MEMBER_REQUEST_DISTRIBUTION_STRICTLY] = true; // always enabled

		if ($channel instanceof MemberRequestDistributionEvenlyInterface)
		{
			$result[self::CFG_MEMBER_REQUEST_DISTRIBUTION_EVENLY] = true;
		}

		if ($channel instanceof MemberRequestDistributionAllInterface)
		{
			$result[self::CFG_MEMBER_REQUEST_DISTRIBUTION_ALL] = true;
		}

		if ($channel instanceof TimeTrackingInterface)
		{
			$result[self::CFG_TIME_TRACKING] = true;
		}

		if ($channel instanceof TimeBeforeRequestNextMemberInterface)
		{
			$result[self::CFG_TIME_BEFORE_REQUEST_NEXT_MEMBER] = $channel->getTimeOffsetVariants();
		}

		return $result;
	}
}
