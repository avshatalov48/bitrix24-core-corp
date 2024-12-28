<?php

namespace Bitrix\Crm\Copilot\CallAssessment;

use Bitrix\Crm\Client\ClientType;
use Bitrix\Crm\Client\ClientTypeResolver;
use Bitrix\Crm\Copilot\CallAssessment;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use CCrmActivity;

final class AssessmentClientTypeResolver
{
	public function resolveByActivityId(int $activityId): ?Enum\ClientType
	{
		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		if (!$activity)
		{
			return null;
		}

		$communications  = CCrmActivity::PrepareCommunicationInfos(
			[$activityId],
			['ENABLE_PERMISSION_CHECK' => false]
		)[$activityId] ?? null;
		if (!$communications)
		{
			return null;
		}

		$itemIdentifier = ItemIdentifier::createFromArray($communications);
		if (!$itemIdentifier)
		{
			return null;
		}

		return $this->resolveByIdentifier($itemIdentifier);
	}

	public function resolveByIdentifier(ItemIdentifier $itemIdentifier): ?CallAssessment\Enum\ClientType
	{
		$clientType = (new ClientTypeResolver())->getType($itemIdentifier);

		return match($clientType)
		{
			ClientType::New => CallAssessment\Enum\ClientType::NEW,
			ClientType::Existing => CallAssessment\Enum\ClientType::IN_WORK,
			ClientType::PreviouslyContacted => CallAssessment\Enum\ClientType::REPEATED_APPROACH,
			ClientType::WithSale => CallAssessment\Enum\ClientType::RETURN_CUSTOMER,
			default => null,
		};
	}
}
