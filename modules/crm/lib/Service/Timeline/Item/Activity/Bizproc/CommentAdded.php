<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class CommentAdded extends Base
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_COMMENT_TITLE');
	}

	protected function getActivityTypeId(): string
	{
		return 'BizprocCommentAdded';
	}

	public function getIconCode(): ?string
	{
		return Icon::BIZPROC;
	}

	public function getLogo(): ?Body\Logo
	{
		return Common\Logo::getInstance(Common\Logo::BIZPROC)
			->createLogo()
			?->setInCircle()
			?->setAdditionalIconCode('comment')
		;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$settings = $this->getActivitySettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		$users = $settings['USERS'] ?? null;
		$templateName = $settings['WORKFLOW_TEMPLATE_NAME'] ?? null;

		if (empty($workflowId) || empty($users) || empty($templateName))
		{
			return null;
		}

		$processNameBlock = $this->buildProcessNameBlock($templateName, $workflowId);
		if (isset($processNameBlock))
		{
			$result['processNameBlock'] = $processNameBlock;
		}

		$fromBlock = $this->buildAssignedBlock(
			$workflowId,
			$users,
			Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_COMMENT_FROM')
		);
		if (isset($fromBlock))
		{
			$result['fromBlock'] = $fromBlock;
		}

		$updateBlock = $this->buildUpdateBlock($settings);
		if (isset($updateBlock))
		{
			$result['updateBlock'] = $updateBlock;
		}

		return $result;
	}

	public function getButtons(): ?array
	{
		$settings = $this->getActivitySettings();
		$isScheduled = $this->getModel()->isScheduled();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		if (empty($workflowId))
		{
			return [];
		}

		$commentsViewed = $settings['COMMENTS_VIEWED'] ?? null;
		$btnType = $commentsViewed || !$isScheduled ? Button::TYPE_SECONDARY : Button::TYPE_PRIMARY;

		return [
			'open' =>
				$this->createOpenButton($workflowId, $btnType)
					->setState(!$this->isBizprocEnabled() ? 'hidden' : null)
		];
	}

	private function buildUpdateBlock(array $settings): ?ContentBlock
	{
		$unreadCommentsCount = $settings['UNREAD_CNT'] ?? null;
		if (empty($unreadCommentsCount))
		{
			return null;
		}

		$lastCommentDate = $settings['LAST_COMMENT_DATE'] ?? time();
		$unreadCommentsCountBlock = new Text();
		$unreadCommentsCountBlock->setValue($unreadCommentsCount);
		$lastCommentDateBlock = new Date();
		$lastCommentDateBlock->setDate(DateTime::createFromTimestamp($lastCommentDate));

		$contentBlock = ContentBlockFactory::createLineOfTextFromTemplate(
			$this->getTemplate($unreadCommentsCount),
			[
				'#COMMENT_COUNT#' => $unreadCommentsCountBlock,
				'#DATE_TIME#' => $lastCommentDateBlock,
			]
		);

		return (new ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_COMMENT_UPDATE'))
			->setContentBlock($contentBlock)
			->setInline()
		;
	}

	public function getTags(): ?array
	{
		return [
			'status' => $this->getStatusBlock(),
		];
	}

	private function getStatusBlock(): Tag
	{
		$settings = $this->getActivitySettings();
		$commentsViewed = $settings['COMMENTS_VIEWED'] ?? null;
		$statusName = Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_COMMENT_STATUS_ADDED') ?? '';
		$tagType = Tag::TYPE_WARNING;
		if (isset($commentsViewed) && $commentsViewed)
		{
			$statusName = Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_COMMENT_STATUS_VIEWED') ?? '';
			$tagType = Tag::TYPE_SECONDARY;
		}

		return new Tag(
			$statusName,
			$tagType
		);
	}

	private function getTemplate(int $unreadCommentsCount): string
	{
		$form = Loc::getPluralForm($unreadCommentsCount);

		return Loc::getMessage("CRM_TIMELINE_ACTIVITY_BIZPROC_COMMENT_COUNT_PLURAL_{$form}") ?? '';
	}
}