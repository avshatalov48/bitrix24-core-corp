<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class CheckManualCreation extends LogMessage
{
	private const HELP_ARTICLE = 13742126;

	public function getType(): string
	{
		return 'CheckManualCreation';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_MANUAL_CHECK_PRINT');
	}

	public function getIconCode(): ?string
	{
		return Icon::ATTENTION;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'description' =>
				(new Text())
					->setValue(
						Loc::getMessage('CRM_TIMELINE_ECOMMERCE_MANUAL_CHECK_PRINT_DESCRIPTION')
					)
					->setColor(Text::COLOR_BASE_90)
			,
			'paymentLink' =>
				(new Link())
					->setValue(
						Loc::getMessage('CRM_TIMELINE_ECOMMERCE_MANUAL_CHECK_PRINT_OPEN_PAYMENT')
					)
					->setAction(
						new Redirect(
							new Uri(
								(string)$this->getAssociatedEntityModel()->get('SHOW_URL')
							)
						)
					)
			,
			'helpLink' =>
				(new Link())
					->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_HOW_CREATE_CHECK'))
					->setAction(
						(new JsEvent('Helpdesk:Open'))
						->addActionParamInt('articleCode', self::HELP_ARTICLE)
					)
			,
		];
	}
}
