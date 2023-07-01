<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\CrmEntityDataProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemFactory;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;

Container::getInstance()->getLocalization()->loadMessages();

final class Document extends Configurable
{
	use Mixin\Document;

	public function getType(): string
	{
		return 'Document';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_DOCUMENT_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::DOCUMENT;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return
			Logo::getInstance(Logo::DOCUMENT_PRINT)
				->createLogo()
				->setAdditionalIconCode('search')
				->setAction($this->getOpenDocumentAction())
		;
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];

		$data = $this->getDocument()->getFile(false)->getData();
		$title = $data['title'] ?? null;

		$blocks['title'] =
			(new Layout\Body\ContentBlock\LineOfTextBlocks())
				->addContentBlock(
					'heading',
					(new Layout\Body\ContentBlock\Text())
						->setValue(Loc::getMessage('CRM_COMMON_TITLE'))
						->setColor(Layout\Body\ContentBlock\Text::COLOR_BASE_50)
						->setFontSize(Layout\Body\ContentBlock\Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'spacing',
					(new Layout\Body\ContentBlock\Text())
						// empty space to separate heading from value. I'm really sorry for this
						->setValue(' ')
				)
				->addContentBlock(
					'titleValue',
					(new Layout\Body\ContentBlock\Text())
						->setValue($title)
				)
		;

		[$myCompanyRequisiteCaption, $clientRequisiteCaption] = $this->getMyCompanyAndClientRequisiteCaption();

		$blocks['myCompany'] =
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_TIMELINE_DOCUMENT_MY_COMPANY'))
				->setContentBlock(
					(new Layout\Body\ContentBlock\Text())
						->setValue($myCompanyRequisiteCaption)
				)
		;

		$blocks['client'] =
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_COMMON_CLIENT'))
				->setContentBlock(
					(new Layout\Body\ContentBlock\Text())
						->setValue($clientRequisiteCaption)
				)
		;

		return $blocks;
	}

	private function getMyCompanyAndClientRequisiteCaption(): array
	{
		$myCompanyRequisiteCaption = Loc::getMessage('CRM_COMMON_EMPTY_VALUE');
		$clientRequisiteCaption = Loc::getMessage('CRM_COMMON_EMPTY_VALUE');

		$provider = $this->getDocument()->getProvider();
		if ($provider instanceof CrmEntityDataProvider)
		{
			[$myCompanyRequisites, ] = $provider->getMyCompanyRequisitesAndBankDetail();

			$myCompanyRequisiteCaption =
				$this->getRequisiteCaption($myCompanyRequisites, $this->getMyCompanyTitle())
				?? $myCompanyRequisiteCaption
			;

			[$clientRequisites, ] = $provider->getClientRequisitesAndBankDetail();

			$clientRequisiteCaption =
				$this->getRequisiteCaption($clientRequisites, $this->getClientTitle())
				?? $clientRequisiteCaption
			;
		}

		return [
			$myCompanyRequisiteCaption,
			$clientRequisiteCaption,
		];
	}

	private function getRequisiteCaption(array $requisite, ?string $requisiteOwnerTitle): ?string
	{
		$name = \Bitrix\Crm\Format\Requisite::formatOrganizationName($requisite) ?: $requisiteOwnerTitle;
		if (!$name)
		{
			return null;
		}

		$shortRequisite = \Bitrix\Crm\Format\Requisite::formatShortRequisiteString($requisite);
		if (!$shortRequisite)
		{
			return $name;
		}

		return Loc::getMessage(
			'CRM_TIMELINE_DOCUMENT_REQUISITE_CAPTION',
			[
				'#ORGANIZATION_NAME#' => $name,
				'#SHORT_REQUISITE#' => $shortRequisite,
			],
		);
	}

	private function getMyCompanyTitle(): ?string
	{
		$provider = $this->getDocument()->getProvider();
		if ($provider instanceof CrmEntityDataProvider)
		{
			$myCompanyProvider = $provider->getMyCompanyProvider();
			if ($myCompanyProvider)
			{
				return $myCompanyProvider->getValue('TITLE');
			}
		}

		return null;
	}

	private function getClientTitle(): ?string
	{
		$provider = $this->getDocument()->getProvider();
		if (!($provider instanceof CrmEntityDataProvider))
		{
			return null;
		}

		$companyProvider = $provider->getValue('COMPANY');
		if ($companyProvider instanceof DataProvider\Company)
		{
			return $companyProvider->getValue('TITLE');
		}

		$contactProvider = $provider->getValue('CONTACT');
		if ($contactProvider instanceof DataProvider\Contact)
		{
			return $contactProvider->getValue('FORMATTED_NAME');
		}

		return null;
	}

	public function getButtons(): ?array
	{
		$buttons = [];

		$buttons['open'] =
			(new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_OPEN'), Layout\Footer\Button::TYPE_SECONDARY))
				->setAction($this->getOpenDocumentAction())
		;

		$signIntegration = ServiceLocator::getInstance()->get('crm.integration.sign');

		if (
			$this->getContext()->getEntityTypeId() === \CCrmOwnerType::Deal
			&& $signIntegration::isEnabled()
		)
		{
			if ($signIntegration::isEnabledInCurrentTariff())
			{
				if (\Bitrix\Crm\Service\Container::getInstance()
				->getUserPermissions()
				->checkAddPermissions(\CCrmOwnerType::SmartDocument))
				{
					$signButton = (new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_SIGN'), Layout\Footer\Button::TYPE_SECONDARY))
						->setAction(
							(new Layout\Action\JsEvent('Document:ConvertDeal'))
								->addActionParamInt('documentId', $this->getDocumentId())
								->setAnimation(Layout\Action\Animation::showLoaderForBlock())
						)
						->setScopeWeb()
					;
				}
			}
			else
			{
				$signButton = (new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_SIGN'), Layout\Footer\Button::TYPE_SECONDARY))
					->setAction(
						(new Layout\Action\JsEvent('Document:ShowInfoHelperSlider'))
							->addActionParamString('infoHelperCode', 'limit_crm_sign_integration')
					)
					->setScopeWeb()
				;
			}

			if (isset($signButton))
			{
				$buttons['sign'] = $signButton;
			}
		}

		return $buttons;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	public function getAdditionalIconButton(): ?Layout\Footer\IconButton
	{
		$action =
			(new Layout\Action\JsEvent('Document:Print'))
		;

		$printUrl = $this->getPrintUrl();
		if ($printUrl)
		{
			$action->addActionParamString('printUrl', (string)$printUrl);
			$action->addActionParamString('pdfUrl', (string)$this->getPdfUrl());
		}
		else
		{
			// if the button is clicked, wait until transformation is complete and the timeline item is updated on push
			$action->setAnimation(Layout\Action\Animation::showLoaderForBlock()->setForever());
		}

		return
			(new Layout\Footer\IconButton('print', Loc::getMessage('CRM_COMMON_ACTION_PRINT')))
				->setAction($action)
		;
	}

	/**
	 * Get footer context menu items
	 *
	 * @return MenuItem[]|null
	 */
	public function getMenuItems(): ?array
	{
		$menuItems = parent::getMenuItems();

		$pdfUrl = $this->getPdfUrl();
		$docxUrl = (string)$this->getDownloadUrl();

		$downloadPdfAction = (new Layout\Action\JsEvent('Document:DownloadPdf'))
			->addActionParamString('docxUrl', $docxUrl);

		if ($pdfUrl)
		{
			$downloadPdfAction->addActionParamString('pdfUrl', (string)$pdfUrl);
		}

		$menuItems['downloadPdf'] =
			(new MenuItem(Loc::getMessage('CRM_COMMON_ACTION_DOWNLOAD_FORMAT', ['#FORMAT#' => 'PDF'])))
				->setAction($downloadPdfAction)
		;

		$menuItems['downloadDocx'] =
			(new MenuItem(Loc::getMessage('CRM_COMMON_ACTION_DOWNLOAD_FORMAT', ['#FORMAT#' => 'DOCX'])))
				->setAction(
					(new Layout\Action\JsEvent('Document:DownloadDocx'))
						->addActionParamString('docxUrl', (string)$this->getDownloadUrl())
				)
		;

		$menuItems['delete'] = MenuItemFactory::createDeleteMenuItem()
			->setAction(
				(new Layout\Action\JsEvent('Document:Delete'))
					->addActionParamInt('id', $this->getModel()->getId())
					->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamString('confirmationText', Loc::getMessage('CRM_TIMELINE_DOCUMENT_DELETION_CONFIRM'))
					->setAnimation(Layout\Action\Animation::disableItem()->setForever())
			)
		;

		return $menuItems;
	}
}
