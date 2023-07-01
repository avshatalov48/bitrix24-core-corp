<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\CrmEntityDataProvider;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Uri;

Loader::requireModule('documentgenerator');

Container::getInstance()->getLocalization()->loadMessages();

final class Document extends Activity
{
	private ?\Bitrix\DocumentGenerator\Document $document = null;
	private ?array $documentData = null;

	protected function getActivityTypeId(): string
	{
		return 'Document';
	}

	public function getIconCode(): ?string
	{
		return Icon::DOCUMENT;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_COMMON_DOCUMENT');
	}

	public function getTitleAction(): ?Layout\Action
	{
		return $this->getOpenDocumentAction();
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return
			Logo::getInstance(Logo::DOCUMENT)
				->createLogo()
				->setAction($this->getOpenDocumentAction())
		;
	}

	private function getOpenDocumentAction(): Layout\Action
	{
		return
			(new Layout\Action\JsEvent('Document:Open'))
				->addActionParamInt('documentId', $this->getDocumentId())
		;
	}

	private function getUrlAction(string $event, string $param, ?Uri $value): Layout\Action\JsEvent
	{
		$action =
			(new Layout\Action\JsEvent($event))
				->addActionParamInt('documentId', $this->getDocumentId())
		;

		if ($value)
		{
			$action->addActionParamString($param, (string)$value);
		}

		return $action;
	}

	public function getContentBlocks(): ?array
	{
		$blocks =  [];

		$documentsPermissions = Driver::getInstance()->getUserPermissions($this->getContext()->getUserId());

		if ($this->isScheduled() && $documentsPermissions->canModifyDocument($this->getDocument()))
		{
			$titleBlock =
				(new Layout\Body\ContentBlock\EditableText())
					->setValue($this->getDocumentTitle())
					->setAction(
						(new Layout\Action\JsEvent('Document:UpdateTitle'))
							->addActionParamInt('documentId', $this->getDocumentId())
						,
					)
			;

			$createDateBlock =
				(new Layout\Body\ContentBlock\EditableDate())
					->setDate($this->getDocumentCreateDate())
					->setAction(
						(new Layout\Action\JsEvent('Document:UpdateCreateDate'))
							->addActionParamInt('documentId', $this->getDocumentId())
						,
					)
			;
		}
		else
		{
			$titleBlock =
				(new Layout\Body\ContentBlock\Text())
					->setValue($this->getDocumentTitle())
			;

			$createDateBlock =
				(new Layout\Body\ContentBlock\Text())
					->setValue((string)$this->getDocumentCreateDate())
			;
		}

		$blocks['titleAndCreateDate'] = Layout\Body\ContentBlock\ContentBlockFactory::createLineOfTextFromTemplate(
			(string)Loc::getMessage('CRM_TIMELINE_ACTIVITY_DOCUMENT_TITLE_AND_CREATE_DATE'),
			[
				'#TITLE#' => $titleBlock,
				'#CREATE_DATE#' => $createDateBlock,
			],
			'titleAndCreateDate',
		);

		$productsCount = $this->getProductsCount();
		$opportunity = $this->getOpportunity();

		if (!is_null($opportunity))
		{
			if ($productsCount > 0)
			{
				$blocks['opportunityTitle'] =
					(new Layout\Body\ContentBlock\Link())
						->setValue(
							Loc::getMessage('CRM_TIMELINE_ACTIVITY_DOCUMENT_SUM_TITLE_WITH_PRODUCTS', ['#PRODUCTS_COUNT#' => $productsCount])
						)
						->setAction(
							(new Layout\Action\JsEvent('Item:OpenEntityDetailTab'))
								->addActionParamString('tabId', 'tab_products')
						)
				;
			}
			else
			{
				$blocks['opportunityTitle'] =
					(new Layout\Body\ContentBlock\Text())
						->setValue(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY'))
				;
			}

			$blocks['opportunity'] =
				(new Layout\Body\ContentBlock\Money())
					->setOpportunity($opportunity)
					->setCurrencyId($this->getCurrencyId())
			;
		}

		$blocks['myCompany'] =
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID'))
				->setContentBlock(
					(new Layout\Body\ContentBlock\Text())
						->setValue($this->getMyCompanyCaption())
				)
		;

		$blocks['client'] =
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_COMMON_CLIENT'))
				->setContentBlock(
					(new Layout\Body\ContentBlock\Text())
						->setValue($this->getClientCaption())
				)
		;

		return $blocks;
	}

	public function getButtons(): ?array
	{
		return [
			'open' =>
				(new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_OPEN'), Layout\Footer\Button::TYPE_PRIMARY))
					->setAction($this->getOpenDocumentAction())
			,

			'copyPublicLink' =>
				(new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_COPY_LINK'), Layout\Footer\Button::TYPE_SECONDARY))
					->setAction(
						$this->getUrlAction('Document:CopyPublicLink', 'publicUrl', $this->getPublicUrl()),
					)
			,

			'sign' =>
				(new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_SIGN'), Layout\Footer\Button::TYPE_SECONDARY))
					->setAction(
						//todo implement signing or hide this button
						new Layout\Action\JsCode("alert('you are signing the document');")
					)
			,
		];
	}

	public function getMenuItems(): array
	{
		$menuItems = parent::getMenuItems();

		unset($menuItems['delete'], $menuItems['view']);

		$menuItems['print'] =
			(new Layout\Menu\MenuItem(Loc::getMessage('CRM_COMMON_ACTION_PRINT')))
				->setIcon('print')
				->setAction(
					$this->getUrlAction('Document:Print', 'printUrl', $this->getPrintUrl()),
				)
				->setSort(100)
		;

		$menuItems['downloadPdf'] =
			(new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DOCUMENT_DOWNLOAD_PDF')))
				->setAction(
					$this->getUrlAction('Document:DownloadPdf', 'pdfUrl', $this->getPdfUrl()),
				)
				->setSort(200)
		;

		$menuItems['downloadDocx'] =
			(new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_ACTIVITY_DOCUMENT_DOWNLOAD_DOCX')))
				->setAction(
					$this->getUrlAction('Document:DownloadDocx', 'docxUrl', $this->getDocxUrl()),
				)
				->setSort(300)
		;

		return $menuItems;
	}

	//region Access to Document Data
	private function getDocumentId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID');
	}

	private function getDocumentTitle(): ?string
	{
		return $this->getDocumentData()['title'] ?? null;
	}

	private function getDocumentCreateDate(): ?Date
	{
		$createTimeField = $this->getDocument()->getFields(['DocumentCreateTime'])['DocumentCreateTime'] ?? null;
		if (is_array($createTimeField) && isset($createTimeField['VALUE']))
		{
			$createTime = $createTimeField['VALUE'];
			while ($createTime instanceof Value)
			{
				$createTime = $createTime->getValue();
			}

			if (is_string($createTime))
			{
				try
				{
					$parsedCreateTime = new Date($createTime);
				}
				catch (ObjectException $incorrectDateException)
				{
					$parsedCreateTime = null;
				}

				$createTime = $parsedCreateTime;
			}

			if ($createTime instanceof Date)
			{
				// createTime was modified by user, use modified value
				return $createTime;
			}
		}

		return $this->getDocument()->getCreateTime();
	}

	private function getPublicUrl(): ?Uri
	{
		return $this->getDocumentData()['publicUrl'] ?? null;
	}

	private function getPrintUrl(): ?Uri
	{
		return $this->getDocumentData()['printUrl'] ?? null;
	}

	private function getPdfUrl(): ?Uri
	{
		return $this->getDocumentData()['pdfUrl'] ?? null;
	}

	private function getDocxUrl(): ?Uri
	{
		return $this->getDocumentData()['downloadUrl'] ?? null;
	}

	private function getProductsCount(): ?int
	{
		$products = $this->getDocumentData()['products'] ?? null;
		if (is_array($products) && isset($products['totalRows']))
		{
			return (int)$products['totalRows'];
		}

		return null;
	}

	private function getOpportunity(): ?float
	{
		$products = $this->getDocumentData()['products'] ?? null;
		if (is_array($products) && isset($products['totalSum']))
		{
			return (float)$products['totalSum'];
		}

		return null;
	}

	private function getCurrencyId(): ?string
	{
		$products = $this->getDocumentData()['products'] ?? null;
		if (is_array($products) && isset($products['currencyId']))
		{
			return (string)$products['currencyId'];
		}

		return null;
	}

	private function getMyCompanyCaption(): string
	{
		$provider = $this->getDocument()->getProvider();
		if ($provider instanceof CrmEntityDataProvider)
		{
			[$myCompanyRequisites, ] = $provider->getMyCompanyRequisitesAndBankDetail();

			$myCompanyCaption = \Bitrix\Crm\Format\Requisite::formatOrganizationName($myCompanyRequisites);
		}

		return $myCompanyCaption ?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE');
	}

	private function getClientCaption(): string
	{
		$provider = $this->getDocument()->getProvider();
		if ($provider instanceof CrmEntityDataProvider)
		{
			[$clientRequisites, ] = $provider->getClientRequisitesAndBankDetail();

			$clientCaption = \Bitrix\Crm\Format\Requisite::formatOrganizationName($clientRequisites);
		}

		return $clientCaption ?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE');
	}

	private function getDocumentData(): array
	{
		if (is_null($this->documentData))
		{
			$this->documentData = $this->getDocument()->getFile(false)->getData();
		}

		return $this->documentData;
	}

	private function getDocument(): \Bitrix\DocumentGenerator\Document
	{
		if (!$this->document)
		{
			$this->document = \Bitrix\DocumentGenerator\Document::loadById($this->getDocumentId());
			if (!$this->document)
			{
				throw new ObjectNotFoundException('Could not find document with ID=' . $this->getDocumentId());
			}

			DocumentGeneratorManager::getInstance()->actualizeDocumentImmediately($this->document);
		}

		return $this->document;
	}
	//endregion
}
