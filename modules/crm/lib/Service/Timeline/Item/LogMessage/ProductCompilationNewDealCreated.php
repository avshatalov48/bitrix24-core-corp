<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Context;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class ProductCompilationNewDealCreated extends LogMessage
{
	public function getType(): string
	{
		return 'ProductCompilationNewDealCreated';
	}

	public function getTitle(): ?string
	{
		return null;
	}

	public function getDate(): ?DateTime
	{
		return null;
	}

	public function getAuthorId(): ?int
	{
		return null;
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK;
	}

	public function getContentBlocks(): ?array
	{
		$newDealData = $this->getHistoryItemModel()->get('NEW_DEAL_DATA');
		$newDealData = $newDealData ?? [];

		$dealTitle = $newDealData['TITLE'] ?? '';
		$dealAction = isset($newDealData['SHOW_URL']) ? new Redirect(new Uri($newDealData['SHOW_URL'])) : null;
		$dealDate = isset($newDealData['DATE_CREATE'])
			? FormatDate(
				Context::getCurrent()->getCulture()->getLongDateFormat(),
				(new Date($newDealData['DATE_CREATE']))->getTimestamp()
			)
			: ''
		;

		$opportunity = $newDealData['OPPORTUNITY'] ?? null;
		$currency = $newDealData['CURRENCY_ID'] ?? null;

		if ($opportunity && $currency)
		{
			$sumWithCurrency = (new Money())
				->setOpportunity((float)$newDealData['OPPORTUNITY'])
				->setCurrencyId((string)$newDealData['CURRENCY_ID'])
			;
		}
		else
		{
			$sumWithCurrency = new Text();
		}

		$content =
			ContentBlockFactory::createLineOfTextFromTemplate(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRODUCT_SELECTION_DEAL_CREATED'),
				[
					'#DEAL_TITLE#' => ContentBlockFactory::createTextOrLink($dealTitle, $dealAction),
					'#DATE_CREATE#' => (new Text())->setValue($dealDate),
					'#SUM_WITH_CURRENCY#' => $sumWithCurrency,
				]
			)
				->setTextColor(Text::COLOR_BASE_90)
		;

		return [
			'content' => $content,
		];
	}
}
