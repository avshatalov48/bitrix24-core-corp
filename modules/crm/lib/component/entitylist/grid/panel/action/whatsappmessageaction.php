<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Integration\Analytics\Builder\Communication\WhatsAppConnectEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\MessageSender\MassWhatsApp\SendItem;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Action;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\MessageService;

class WhatsAppMessageAction implements Action
{
	public function __construct(
		private readonly int $entityTypeId,
		private readonly ?int $categoryId,
	)
	{
	}

	public static function getId(): string
	{
		return 'whatsapp-message';
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}

	public function getControl(): ?array
	{
		$button = new Button();
		$button
			->setId(self::getId())
			->setText(Loc::getMessage('CRM_GRID_PANEL_GROUP_WHATSAPP_MESSAGE'))
			->setTitle(Loc::getMessage('CRM_GRID_PANEL_GROUP_WHATSAPP_MESSAGE'))
		;

		$sender = SmsManager::getSenderById(SendItem::DEFAULT_PROVIDER);

		$isWhatsAppEdnaEnabled = $this->isWhatsAppEdnaEnabled($sender);
		$ednaManageUrl =  $this->getEdnaManageUrl($sender);
		if (!$isWhatsAppEdnaEnabled)
		{
			$ednaManageUrl = $this->updateAnalyticsParams($ednaManageUrl);
		}

		$onchange = new Onchange();
		$onchange->addAction([
			'ACTION' => Actions::CALLBACK,
			'DATA' => [
				[
					'JS' => (new Event('BatchManager:whatsappMessage'))
						->addEntityTypeId($this->entityTypeId)
						->addParam('categoryId', $this->categoryId)
						->addParam('isWhatsAppEdnaEnabled', $isWhatsAppEdnaEnabled)
						->addParam('ednaManageUrl', $ednaManageUrl)
						->buildJsCallback(),
				],
			],
		]);

		$button->setOnchange($onchange);

		return $button->toArray();
	}

	private function isWhatsAppEdnaEnabled(?MessageService\Sender\Base $sender): bool
	{
		if (!$sender)
		{
			return false;
		}

		return $sender::isSupported() && $sender->canUse();
	}

	private function getEdnaManageUrl(?MessageService\Sender\Base $sender): string
	{
		if (!$sender)
		{
			return '/contact_center/connector/?ID=whatsappbyedna';
		}

		return $sender->getManageUrl();
	}

	private function updateAnalyticsParams(string $ednaManageUrl): string
	{
		$section = null;

		if ($this->entityTypeId === \CCrmOwnerType::Contact)
		{
			$section = Dictionary::SECTION_CONTACT;
		}

		if ($this->entityTypeId === \CCrmOwnerType::Company)
		{
			$section = Dictionary::SECTION_COMPANY;
		}

		$section = Dictionary::getSectionByCategoryId($this->entityTypeId, $this->categoryId) ?? $section;

		if (!$section)
		{
			return $ednaManageUrl;
		}

		$current = new Uri($ednaManageUrl);
		$current->addParams([
			'analytics' => WhatsAppConnectEvent::createDefault($section)
				->setSubSection(Dictionary::SUB_SECTION_LIST)
				->buildData(),
		]);

		return $current->getUri();

	}
}
