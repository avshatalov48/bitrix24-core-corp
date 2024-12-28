<?php

namespace Bitrix\Crm\Badge\Type;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\ValueItem;
use Bitrix\Crm\Badge\ValueItemOptions;
use Bitrix\Main\Localization\Loc;

class WorkflowCommentStatus extends Badge
{
	public const COMMENTS_ADDED = 'comments_added';

	public function getValuesMap(): array
	{
		return [
			new ValueItem(
				self::COMMENTS_ADDED,
				Loc::getMessage('CRM_BADGE_WORKFLOW_COMMENT_STATUS_COMMENTS_ADDED') ?? '',
				ValueItemOptions::TEXT_COLOR_WARNING,
				ValueItemOptions::BG_COLOR_WARNING,
			),
		];
	}

	public function getFieldName(): string
	{
		return Loc::getMessage('CRM_BADGE_WORKFLOW_COMMENT_STATUS') ?? '';
	}

	public function getType(): string
	{
		return 'workflow_comment_status';
	}
}
