<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\Provider\ProviderManager;
use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\Animation;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ClientMark;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\ChangeStreamButton;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use CCrmActivity;

class OpenLine extends Activity
{
	protected function getActivityTypeId(): string
	{
		return 'OpenLine';
	}

	public function getIconCode(): ?string
	{
		return Icon::OPENLINE_INCOMING_MESSAGE;
	}

	public function getTitle(): string
	{
		return Loc::getMessage(
			$this->isScheduled()
				? 'CRM_TIMELINE_TITLE_OPEN_LINE_MSGVER_1'
				: 'CRM_TIMELINE_TITLE_OPEN_LINE_DONE'
		);
	}

	public function getTitleAction(): ?Action
	{
		return $this->getOpenChatAction();
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$logoCode = Layout\Common\Logo::CHANNEL_CHAT; // default icon

		// logos map, see connector codes OpenLineManager::$supportedConnectors
		$logoMap = [
			'avito' => Layout\Common\Logo::CHANNEL_AVITO,
			'imessage' => Layout\Common\Logo::CHANNEL_APPLE,
			'facebook' => Layout\Common\Logo::CHANNEL_FACEBOOK,
			'facebookmessenger' => Layout\Common\Logo::CHANNEL_FACEBOOK_CHAT,
			'facebookcomments' => Layout\Common\Logo::CHANNEL_FACEBOOK_CHAT,
			'fbinstagram' => Layout\Common\Logo::CHANNEL_INSTAGRAM_DIRECT,
			'fbinstagramdirect' => Layout\Common\Logo::CHANNEL_INSTAGRAM_DIRECT,
			'livechat' => Layout\Common\Logo::CHANNEL_CHAT,
			'network' => Layout\Common\Logo::CHANNEL_BITRIX,
			'ok' => Layout\Common\Logo::CHANNEL_ODNOKLASSNIKI,
			'telegram' => Layout\Common\Logo::CHANNEL_TELEGRAM,
			'telegrambot' => Layout\Common\Logo::CHANNEL_TELEGRAM,
			'viber' => Layout\Common\Logo::CHANNEL_VIBER,
			'vkgroup' => Layout\Common\Logo::CHANNEL_VK,
			'vkgrouporder' => Layout\Common\Logo::CHANNEL_VK_ORDER,
			'whatsappbytwilio' => Layout\Common\Logo::CHANNEL_WHATSAPP_BITRIX,
			'whatsappbyedna' => Layout\Common\Logo::CHANNEL_WHATSAPP,
		];
		$userCode = $this->getAssociatedEntityModel()?->get('PROVIDER_PARAMS')['USER_CODE'];
		if (isset($userCode))
		{
			$connectorType = OpenLineManager::getLineConnectorType($userCode);
			if (isset($logoMap[$connectorType]))
			{
				$logoCode = $logoMap[$connectorType];
			}
		}

		return Layout\Common\Logo::getInstance($logoCode)
			->createLogo()
			?->setAction($this->getOpenChatAction())
		;
	}

	public function getContentBlocks(): array
	{
		$result = [];
		$userCode = $this->getAssociatedEntityModel()?->get('PROVIDER_PARAMS')['USER_CODE'];

		$lineName = OpenLineManager::getLineTitle($userCode);
		if ($lineName)
		{
			$result['lineTitle'] = (new ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_TIMELINE_BLOCK_TITLE_NAME'))
				->setContentBlock((new Link())->setValue($lineName)->setAction($this->getOpenChatAction()))
				->setInline()
			;
		}

		$clientBlock = $this->buildClientBlock(Client::BLOCK_WITH_FIXED_TITLE);
		if (isset($clientBlock))
		{
			$result['client'] = $clientBlock;
		}

		$sourceList = [];
		$providerId = $this->getAssociatedEntityModel()?->get('PROVIDER_ID');
		if ($providerId && $provider = CCrmActivity::GetProviderById($providerId))
		{
			$sourceList = $provider::getResultSources();
		}

		if (!empty($sourceList))
		{
			$connectorType = OpenLineManager::getLineConnectorType($userCode);
			$channelName = $sourceList[$connectorType] ?? $sourceList['livechat'];
			$result['chatTitle'] = (new ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_TIMELINE_BLOCK_TITLE_CHANNEL'))
				->setContentBlock(ContentBlockFactory::createTitle($channelName))
				->setInline()
			;
		}

		if (empty($result))
		{
			$subject = (string)$this->getAssociatedEntityModel()->get('SUBJECT');
			if (!empty($subject))
			{
				$result['subject'] = ContentBlockFactory::createTextOrLink(
					$subject,
					$this->getOpenChatAction()
				);
			}
		}

		$clientMarkBlock = $this->buildClientMarkBlock();
		if (isset($clientMarkBlock))
		{
			$result['clientMark'] = $clientMarkBlock;
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
			'openChat' => (
				new Button(
					Loc::getMessage($this->isScheduled() ? 'CRM_TIMELINE_BUTTON_OPEN_CHAT_MSGVER_1' : 'CRM_TIMELINE_BUTTON_SEE_CHAT'),
					$this->isScheduled() ? Button::TYPE_PRIMARY : Button::TYPE_SECONDARY
				)
			)->setAction($openChatAction)
		];
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();
		unset($items['view']);

		return $items;
	}

	public function getTags(): ?array
	{
		$tags = [];

		$userCode = $this->getAssociatedEntityModel()?->get('PROVIDER_PARAMS')['USER_CODE'];
		$responsibleId = $this->getAssociatedEntityModel()?->get('RESPONSIBLE_ID');

		// the tag will not be removed until the responsible user reads all messages
		if (
			$this->isScheduled()
			&& OpenLineManager::getChatUnReadMessages($userCode, $responsibleId) > 0
		)
		{
			$tags['notReadChat'] = new Tag(
				Loc::getMessage('CRM_TIMELINE_TAG_CHAT_NOT_READ'),
				Tag::TYPE_WARNING
			);
		}
		else if (BadgeTable::isActivityHasBadge($this->getActivityId()))
		{
			$activity = CCrmActivity::GetByID($this->getActivityId(), false);
			if (is_array($activity))
			{
				ProviderManager::syncBadgesOnActivityUpdate($this->getActivityId(), $activity);
			}
		}

		return $tags;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	protected function getOpenChatAction(): ?Action
	{
		$communication = $this->getAssociatedEntityModel()?->get('COMMUNICATION') ?? [];
		$dialogId = $communication['VALUE'] ?? null;
		if (!$dialogId || $communication['TYPE'] !== 'IM')
		{
			return null;
		}

		return (new JsEvent('Openline:OpenChat'))
			->addActionParamString('dialogId', $dialogId)
		;
	}

	protected function getCompleteButton(): ?ChangeStreamButton
	{
		if (!$this->isScheduled())
		{
			return null;
		}

		$completeAction =  (new JsEvent('Openline:Complete'))
			->addActionParamInt('activityId', $this->getActivityId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
			->addActionParamString('ajaxAction', $this->getCompleteAction()->getAction())
			->setAnimation(Animation::disableItem()->setForever())
		;

		return (new ChangeStreamButton())
			->setTypeComplete()
			->setDisableIfReadonly()
			->setAction($completeAction)
		;
	}

	protected function canMoveTo(): bool
	{
		return $this->isScheduled();
	}

	private function buildClientMarkBlock(): ?ContentBlock
	{
		$sessionData = $this->getSessionData();
		if (empty($sessionData))
		{
			return null;
		}

		$vote = (int)$sessionData['VOTE'];
		if ($vote <= 0)
		{
			return null;
		}

		$clientMark = $this->mapClientMark($vote);
		if (!isset($clientMark))
		{
			return null;
		}

		return (new ClientMark())
			->setMark($clientMark)
			->setText(
				Loc::getMessage(
					sprintf(
						'CRM_TIMELINE_BLOCK_CLIENT_MARK_%s',
						mb_strtoupper($clientMark)
					)
				)
			)
		;
	}

	private function getSessionData(): array
	{
		$sessionId = $this->getModel()->getAssociatedEntityModel()?->get('ASSOCIATED_ENTITY_ID') ?? 0;

		return $sessionId > 0
			? OpenLineManager::getSessionData($sessionId)
			: [];
	}

	private function mapClientMark(int $vote): ?string
	{
		if ($vote > 3)
		{
			return ClientMark::POSITIVE;
		}

		if ($vote === 3)
		{
			return ClientMark::NEUTRAL;
		}

		if ($vote > 0)
		{
			return ClientMark::NEGATIVE;
		}

		return null;
	}
}
