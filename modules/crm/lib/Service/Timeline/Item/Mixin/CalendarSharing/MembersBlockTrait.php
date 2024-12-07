<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Web\Uri;

trait MembersBlockTrait
{
	use MessageTrait;

	public function buildMembersTitleBlock(): ContentBlock
	{
		return ContentBlock\ContentBlockFactory::createTitle(
			$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_MEMBERS'),
		);
	}

	public function buildMembersBlock(array $memberIds): ContentBlock
	{
		$preShownMaxCount = 5;

		$users = Container::getInstance()->getUserBroker()->getBunchByIds($memberIds);
		$preShownUsers = array_slice($users, 0, $preShownMaxCount);

		$lineBlock = new ContentBlock\LineOfTextBlocks();

		$preShownUsersCount = count($preShownUsers);
		$usersCount = count($users);

		$doShowMoreButton = $preShownUsersCount < $usersCount;
		foreach (array_values($preShownUsers) as $index => $user)
		{
			$isLast = $index === $preShownUsersCount - 1;
			$text = $user['FORMATTED_NAME'] . (!$isLast || $doShowMoreButton ? ', ' : '');
			$action = new Redirect(new Uri($user['SHOW_URL']));
			$userLink = ContentBlock\ContentBlockFactory::createTextOrLink($text, $action);
			$lineBlock->addContentBlock("sharing_member_$index", $userLink);
		}

		if ($doShowMoreButton)
		{
			$value = $this->getMessage(
				'CRM_TIMELINE_CALENDAR_SHARING_OPEN_SLOTS_MEMBERS_MORE_BUTTON',
				['#COUNT#' => ($usersCount - $preShownUsersCount)]
			);
			$textMore =
				(new ContentBlock\Link())
					->setAction($this->getOpenMembersPopupAction($users))
					->setValue($value)
					->setIcon('bottom-caret')
					->setColor(ContentBlock\Text::COLOR_BASE_70)
			;
			$lineBlock->addContentBlock("sharing_member_more_button", $textMore);
		}

		return $lineBlock;
	}

	private function getOpenMembersPopupAction(array $members): Action\JsEvent
	{
		return
			(new Action\JsEvent($this->getType() . ':ShowMembers'))
				->addActionParamArray('members', $members)
				->addActionParamString(
					'title',
					$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_MEMBERS'),
				)
		;
	}
}
