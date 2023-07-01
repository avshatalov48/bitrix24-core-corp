<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__DIR__ . '/./CalendarSharing.php');

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait CalendarSharing
{
	public function getSendedContactContentBlock(): ContentBlock
	{
		$result = new LineOfTextBlocks();

		$result->addContentBlock(
			'title',
			ContentBlockFactory::createTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_SENT')
			)
				->setColor(Text::COLOR_BASE_70)
		);

		$contactName = $this->getContactName();
		$contactUrl = $this->getContactUrl();

		$result->addContentBlock(
			'contactInfo',
			ContentBlockFactory::createTextOrLink(
				$contactName, $contactUrl ? new Redirect($contactUrl) : null
			)
		);

		$date = $this->getDateContent();
		$result->addContentBlock(
			'linkCreationDate',
			(new Date())
				->setDate($date)
				->setColor(Text::COLOR_BASE_90)
		);

		return $result;
	}

	public function getPlannedEventContentBlock(): ContentBlock
	{
		$result = new LineOfTextBlocks();

		if ($this->hasContact())
		{
			$contactName = $this->getContactName();
			$contactUrl = $this->getContactUrl();
		}
		else
		{
			$activity = $this->getAssociatedEntityModel();
			$settings = $activity ? $activity->get('SETTINGS') : [];
			$contactName = !empty($settings['GUEST_NAME'])
				? $settings['GUEST_NAME']
				: $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_GUEST')
			;
			$contactUrl = false;
		}


		$result->addContentBlock(
			'title',
			ContentBlockFactory::createTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_MEETING_PLANNED')
			)
				->setColor(Text::COLOR_BASE_70)
		);

		$result->addContentBlock(
			'contactInfo',
			ContentBlockFactory::createTextOrLink(
				$contactName, $contactUrl ? new Redirect($contactUrl) : null
			)
		);

		return $result;
	}

	public function getEventStartDateBlock(): ContentBlock
	{
		$result = new LineOfTextBlocks();

		$result->addContentBlock('title',
			ContentBlockFactory::createTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_DATE_AND_TIME_EVENT') . ':'
			)
		);

		$date = $this->getDateContent();
		$result->addContentBlock(
			'linkCreationDate',
			(new Date())
				->setDate($date)
				->setColor(Text::COLOR_BASE_90)
		);

		return $result;
	}

	public function getDateContent(): DateTime
	{
		$timestamp = $this->getModel()->getSettings()['TIMESTAMP'] ?? null;

		return ($timestamp && !\CCrmDateTimeHelper::IsMaxDatabaseDate($timestamp))
			? DateTime::createFromTimestamp($timestamp)
			: new DateTime()
		;
	}

	public function getContactName(): ?string
	{
		$contactTypeId = $this->getModel()->getSettings()['CONTACT_TYPE_ID'] ?? null;
		$contactId = $this->getModel()->getSettings()['CONTACT_ID'] ?? null;

		$result = false;
		if ($contactId && $contactTypeId)
		{
			$contactData = Container::getInstance()
				->getEntityBroker($contactTypeId)
				->getById($contactId)
			;

			if ($contactData)
			{
				if ($contactTypeId === \CCrmOwnerType::Contact)
				{
					$result = $contactData->getFullName();
				}
				else if ($contactTypeId === \CCrmOwnerType::Company)
				{
					$result = $contactData->getTitle();
				}
			}
		}

		return $result ?: $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_GUEST');
	}

	public function getContactUrl(): ?Uri
	{
		$result = null;

		$detailUrl = Container::getInstance()
			->getRouter()
			->getItemDetailUrl(
				$this->getModel()->getSettings()['CONTACT_TYPE_ID'] ?? null,
				$this->getModel()->getSettings()['CONTACT_ID'] ?? null
			)
		;
		if ($detailUrl)
		{
			$result = new Uri($detailUrl);
		}

		return $result;
	}

	public function getLinkUrl(): ?Uri
	{
		$result = null;
		$linkHash = $this->getModel()->getSettings()['LINK_HASH'];

		if (!$linkHash)
		{
			$historyItemModel = $linkHash = $this->getModel()->getHistoryItemModel();
			if ($historyItemModel)
			{
				$linkHash = $historyItemModel->get('LINK_HASH');
			}
		}

		if ($linkHash)
		{
			$result = new Uri($this->getSharingLinkUrl($linkHash));
		}

		return $result;
	}

	private function getSharingLinkUrl(string $hash): string
	{
		$sharingPublicPath = '/pub/calendar-sharing/';
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
		$server = $context->getServer();
		$domain = $server->getServerName() ?: \COption::getOptionString('main', 'server_name', '');

		if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
		{
			$domain = $matches['domain'];
			$port = (int)$matches['port'];
		}
		else
		{
			$port = (int)$server->getServerPort();
		}

		$port = in_array($port, [80, 443], true) ? '' : ':'.$port;
		$serverPath = $scheme . '://' . $domain . $port;
		$url = $serverPath . $sharingPublicPath . $hash . '/';

		return $serverPath . \CBXShortUri::getShortUri($url);
	}

	public function hasContact(): bool
	{
		return
			($this->getModel()->getSettings()['CONTACT_ID'] ?? null)
			&& ($this->getModel()->getSettings()['CONTACT_TYPE_ID'] ?? null)
		;
	}

	protected function getMessage(string $messageCode): string
	{
		return Loc::getMessage($messageCode);
	}
}