<?php

namespace Bitrix\Crm\Activity\Provider\Bizproc;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\BizprocWorkflowStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

final class Workflow extends Base
{
	private const PROVIDER_ID = 'CRM_BIZPROC_WORKFLOW';
	private const PROVIDER_TYPE_ID = 'BIZPROC_WORKFLOW';

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function getProviderTypeId(): string
	{
		return self::PROVIDER_TYPE_ID;
	}

	public static function getSubject(): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BIZPROC_WORKFLOW_SUBJECT') ?? '';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BIZPROC_WORKFLOW_NAME') ?? '';
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::getProviderTypeId(),
			],
		];
	}

	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$badge =
			Container::getInstance()
				->getBadge(Badge::BIZPROC_WORKFLOW_STATUS_TYPE, BizprocWorkflowStatus::DONE_VALUE)
		;
		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId
		);

		foreach ($bindings as $singleBinding)
		{
			$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);

			if (($activityFields['COMPLETED'] ?? 'N') === 'Y')
			{
				$badge->unbind($itemIdentifier, $sourceIdentifier);
			}
			else
			{
				$badge->bind($itemIdentifier, $sourceIdentifier);
			}
		}
	}
}
