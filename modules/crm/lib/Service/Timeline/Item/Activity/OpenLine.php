<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Localization\Loc;

class OpenLine extends Activity
{
	protected function getActivityTypeId(): string
	{
		return 'OpenLine';
	}

	public function getIconCode(): ?string
	{
		return 'IM';
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_TITLE_OPEN_LINE');
	}

	public function getTitleAction(): ?Layout\Action
	{
		return $this->getOpenChatAction();
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return (new Layout\Body\Logo('openlines'))
			->setAction($this->getOpenChatAction())
		;
	}

	public function getContentBlocks(): array
	{
		$result = [];
		$subject = $this->getAssociatedEntityModel()->get('SUBJECT');
		if ($subject)
		{
			$result['subject'] = Layout\Body\ContentBlock\ContentBlockFactory::createTextOrLink(
				$subject,
				$this->getOpenChatAction()
			);
		}

		foreach ($this->getChatMessages() as $i => $chatMessage)
		{
			$result['chat_message_' . $i] = (new Layout\Body\ContentBlock\ChatMessage())
				->setMessage($chatMessage['MESSAGE'])
				->setIsIncoming((bool)$chatMessage['IS_EXTERNAL'])
			;
		}

		$clientContentBlock = $this->getClientContentBlock();
		if ($clientContentBlock)
		{
			$result['client'] = $clientContentBlock;
		}

		return $result;
	}

	public function getButtons(): array
	{
		$openChatAction = $this->getOpenChatAction();
		if (!$openChatAction)
		{
			return [];

		}

		return [
			'openChat' => (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_BUTTON_OPEN_CHAT'),
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction($openChatAction),
		];
	}

	protected function getOpenChatAction(): ?Layout\Action
	{
		$communication = $this->getAssociatedEntityModel()->get('COMMUNICATION') ?? [];
		$dialogId = $communication['VALUE'] ?? null;
		if (!$dialogId || $communication['TYPE'] !== 'IM')
		{
			return null;
		}

		return (new Layout\Action\JsEvent('Openline:OpenChat'))
			->addActionParamString('dialogId', $dialogId)
		;
	}

	protected function getChatMessages(): array
	{
		$sessionId = $this->getModel()->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID') ?? 0;
		if ($sessionId > 0)
		{
			return OpenLineManager::getSessionMessages($sessionId, 5);
		}

		return [];
	}
}
