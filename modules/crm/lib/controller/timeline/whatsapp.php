<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\Communication\WhatsAppConnectEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Web\Uri;

final class WhatsApp extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new Scope(Scope::AJAX);
		$filters[] = new ContentType([ContentType::JSON]);

		return $filters;
	}

	public function getConfigAction(int $entityTypeId, int $entityId): ?array
	{
		if ($entityTypeId <= 0 || $entityId <= 0)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		$whatsApp = new TimelineMenuBar\Item\WhatsApp(
			new TimelineMenuBar\Context($entityTypeId, $entityId),
		);
		$provider = $whatsApp->getProvider();
		if (!$provider || !$whatsApp->isAvailable())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}
		
		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);

		if (!$provider['canUse'])
		{
			$provider['manageUrl'] = $this->updateAnalyticsParams(
				$provider['manageUrl'],
				$entityTypeId,
				$itemIdentifier->getCategoryId(),
			);
		}

		return [
			'communications' => (new TimelineMenuBar\Communications($entityTypeId, $entityId))->get(),
			'provider' => $provider,
		];
	}

	private function updateAnalyticsParams(string $ednaManageUrl, int $entityTypeId, ?int $categoryId): string
	{
		$section = null;

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$section = Dictionary::SECTION_LEAD;
		}

		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$section = Dictionary::SECTION_DEAL;
		}

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			$section = Dictionary::SECTION_COMPANY;
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			$section = Dictionary::SECTION_CONTACT;
		}

		if ($entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			$section = Dictionary::SECTION_SMART_INVOICE;
		}

		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			$section = Dictionary::SECTION_QUOTE;
		}

		if ($entityTypeId === \CCrmOwnerType::SmartDocument)
		{
			$section = Dictionary::SECTION_CONTACT;
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$section = Dictionary::SECTION_DYNAMIC;
		}

		$section = Dictionary::getSectionByCategoryId($entityTypeId, $categoryId) ?? $section;

		if (!$section)
		{
			return $ednaManageUrl;
		}

		$current = new Uri($ednaManageUrl);
		$current->addParams([
			'analytics' => WhatsAppConnectEvent::createDefault($section)
				->setSubSection(Dictionary::SUB_SECTION_DETAILS)
				->buildData(),
		]);

		return $current->getUri();
	}
}
