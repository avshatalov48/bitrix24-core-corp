<?php

namespace Bitrix\Crm\Activity\Provider\Bizproc;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\WorkflowCommentStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

final class Comment extends Base
{
	private const PROVIDER_ID = 'CRM_BIZPROC_COMMENT';
	private const PROVIDER_TYPE_ID = 'BIZPROC_COMMENT';

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
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BIZPROC_COMMENT_SUBJECT') ?? '';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BIZPROC_COMMENT_NAME') ?? '';
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID,
			],
		];
	}

	public function find(string $workflowId, ItemIdentifier $identifier): array|false
	{
		$result = \Bitrix\Crm\ActivityTable::getList([
			'filter' => [
				'=OWNER_ID' => $identifier->getEntityId(),
				'=OWNER_TYPE_ID' => $identifier->getEntityTypeId(),
				'=PROVIDER_ID' => 'CRM_BIZPROC_COMMENT',
				'=PROVIDER_TYPE_ID' => 'BIZPROC_COMMENT',
				'=ORIGIN_ID' => $workflowId,
				'=COMPLETED' => 'N'
			],
			'select' => ['ID', 'SETTINGS']
		]);

		return  $result->fetch();
	}

	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$settings = $activityFields['SETTINGS'] ?? [];
		if (!is_array($settings))
		{
			return;
		}

		$badge =
			Container::getInstance()
				->getBadge(Badge::WORKFLOW_COMMENT_STATUS_TYPE, WorkflowCommentStatus::COMMENTS_ADDED)
		;
		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId
		);

		$commentsViewed = $settings['COMMENTS_VIEWED'] ?? null;
		foreach ($bindings as $singleBinding)
		{
			$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
			if (($activityFields['COMPLETED'] ?? 'N') === 'Y' || $commentsViewed === true)
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