<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

class OrderCheckNotPrinted extends OrderCheckPrintStatus
{
	use Mixin\HasCheckDetails;

	public function getType(): string
	{
		return 'OrderCheckNotPrinted';
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRINT_ERROR'),
				Tag::TYPE_FAILURE
			)
		];
	}

	public function getContentBlocks(): ?array
	{
		$historyItemModel = $this->getModel()->getHistoryItemModel();
		if (!$historyItemModel)
		{
			return parent::getContentBlocks();
		}

		$printed = $historyItemModel->get('PRINTED');
		$errorMessage = $historyItemModel->get('ERROR_MESSAGE');
		if ($printed !== 'N' || !$errorMessage)
		{
			return parent::getContentBlocks();
		}

		$contentBlocks = [
			'error' =>
				(new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_ERROR_TITLE'))
					->setInline()
					->setContentBlock(
						(new Layout\Body\ContentBlock\Text())
							->setValue($errorMessage),
					)
			,
		];

		return array_merge(parent::getContentBlocks(), $contentBlocks);
	}

	private function isReprintButtonEnabledInMobile(): bool
	{
		$crmmobileVersion = ModuleManager::getVersion('crmmobile');
		if (!$crmmobileVersion)
		{
			return false;
		}

		return version_compare($crmmobileVersion, '24.600.0') >= 0;
	}

	public function getButtons(): ?array
	{
		if (
			$this->getAssociatedEntityModel()->get('STATUS') !== 'E'
			|| (
				$this->getContext()->getType() === Context::MOBILE
				&& !$this->isReprintButtonEnabledInMobile()
			)
		)
		{
			return parent::getButtons();
		}

		$result = [
			'reprint' => (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_REPRINT'),
				Layout\Footer\Button::TYPE_SECONDARY
			))
				->setAction($this->getReprintCheckAction()),
		];

		return array_merge($result, parent::getButtons());
	}

	private function getReprintCheckAction(): ?Action
	{
		if (!$this->isCheckAssociatedEntityModelSupported())
		{
			return null;
		}

		return (new Action\JsEvent('OrderCheck:ReprintCheck'))
			->addActionParamInt('checkId', $this->getAssociatedEntityModel()->get('ID'))
		;
	}
}
