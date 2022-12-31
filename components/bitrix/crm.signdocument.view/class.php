<?php

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('crm');
Loc::loadMessages(__FILE__);

class CrmSignDocumentViewComponent extends Bitrix\Crm\Component\Base
{
	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('documentId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if ($this->getErrors())
		{
			return;
		}

		if (!$this->isIframe())
		{
			// todo redirect ?
			return;
		}
		$this->arResult['title'] = Loc::getMessage('CRM_SIGNDOCUMENT_VIEW_TITLE');

		if (!\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error('Document signing is not enabled');
			return;
		}

		$documentId = $this->arParams['documentId'] ?? null;

		if (!$documentId)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_SIGNDOCUMENT_TRY_AGAIN_LATER'));
			return;
		}

		$document = Bitrix\Sign\Document::getById($documentId);

		if (!$document->getInitiatorMember())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_SIGNDOCUMENT_TRY_AGAIN_LATER'));
			return;
		}
		$documentHash = $document->getHash();
		$memberHash = $this->arParams['memberHash'] ?? null;
		$memberHash = $memberHash === 'undefined' ? null : $memberHash;

		$data = [
			'documentHash' => $document->getHash(),
			'secCode' => $document->getSecCode(),
		];

		$currentMember = null;
		if (!$this->arParams['memberHash'] && !$document->isAllMembersSigned())
		{
			foreach ($document->getMembers() as $member) {
				if ($document->isSignedByMember($member->getHash()))
				{
					$memberHash = $member->getHash();
				}
			}
		}

		if (!$memberHash && !$document->isAnyMembersSigned())
		{
			$memberHash = $document->getInitiatorMember()->getHash();
		}

		if ($memberHash)
		{
			$data['memberHash'] = $memberHash;
		}

		$status = \Bitrix\Sign\Proxy::sendCommand('document.file.getStatus',
			$data
		)['status'];

		if ($status != 'exists')
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_SIGNDOCUMENT_TRY_AGAIN_LATER'));
			return;
		}

		$basePath = '/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc&documentHash=%s';
		$this->arResult['pdfSource'] = $memberHash
			? sprintf(
				$basePath.'&memberHash=%s',
				$documentHash,
				$memberHash
			)
			: sprintf(
				$basePath,
				$documentHash
			)
		;

		if ($document->getEntityId() > 0)
		{
			$this->prepareChannelSelectorParameters($memberHash, $document);
		}

		$this->prepareRequisites($document);
	}

	public function executeComponent(): void
	{
		$this->init();
		if ($this->getErrors())
		{
			$this->showErrors();
			return;
		}
		$this->includeComponentTemplate();
	}

	protected function showErrors()
	{
		if(count($this->errorCollection) <= 0)
		{
			return;
		}

		$this->arResult['ERRORS'][] = $this->errorCollection->getValues()[0]->getMessage();
		$this->includeComponentTemplate('unavailable');
	}

	private function prepareRequisites(\Bitrix\Sign\Document $document): void
	{
		$link = EntityLink::getByEntity(CCrmOwnerType::SmartDocument, $document->getEntityId());
		$item = \Bitrix\Crm\Service\Container::getInstance()
			->getFactory(\CCrmOwnerType::SmartDocument)
			->getItem($document->getEntityId());

		if ($link)
		{
			$requisiteId = $link['MC_REQUISITE_ID'] ?? null;
			$linkedRequisiteId = ((int)$requisiteId > 0) ? (int)$requisiteId : null;
		}

		if (!empty($linkedRequisiteId))
		{
			$requisites = EntityRequisite::getSingleInstance()->getById($linkedRequisiteId);
		}
		elseif (isset($item->getData()['MYCOMPANY_ID']) && $item->getMycompanyId() > 0)
		{
			$defaultRequisite = new DefaultRequisite(
				new ItemIdentifier(\CCrmOwnerType::Company, $item->getMycompanyId())
			);

			$requisites = $defaultRequisite->get();
		}

		if (!empty($requisites))
		{
			$myCompanyCaption = \Bitrix\Crm\Format\Requisite::formatOrganizationName($requisites);
		}

		$this->arResult['myCompanyRequisites'] = [
			'title' => $myCompanyCaption ?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE'),
			'subTitle' => '',
			'link' => Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Company, $item->getMycompanyId()),
		];
		$this->arResult['clientRequisites'] = [
			'title' => $item->getContacts()[0]->getFormattedName(),
			'subTitle' => '',
			'link' => Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Contact, $item->getContacts()[0]->getId()),
		];
	}

	/**
	 * @param $memberHash
	 * @param \Bitrix\Sign\Document|null $document
	 * @return void
	 */
	private function prepareChannelSelectorParameters($memberHash, ?\Bitrix\Sign\Document $document): void
	{
		$member = $memberHash ? $document->getMemberByHash($memberHash) : $document->getInitiatorMember();

		if ($member->isInitiator() && $member->isSigned())
		{
			$members = $document->getMembers();

			foreach ($members as $member)
			{
				if (!$member->isSigned())
				{
					break;
				}
			}
		}
		$this->arResult['channelSelectorParameters'] = [
			'id' => 'document-channel-selector',
			'entityTypeId' => CCrmOwnerType::SmartDocument,
			'entityId' => $document->getEntityId(),
			'body' => $document->getTitle(),
			'configureContext' => 'crm.signdocument.view',
			'link' => $member ? $member->getDownloadUrl() : '',
			'isLinkObtainable' => true,
			'isConfigurable' => true,
		];

		$channels = [];

		$channels[] = $member->getCommunicationType() === \Bitrix\Sign\Document\Member::COMMUNICATION_TYPE_MAIL
			? [
				'id' => Email::ID,
				'type' => Email::ID,
				'title' => 'E-mail',
				'canBeShown' => true,
				'isAvailable' => true,
			]
			: [
				'id' => Phone::ID,
				'type' => Phone::ID,
				'title' => 'Sms',
				'canBeShown' => true,
				'isAvailable' => true,
			];

		$this->arResult['channelSelectorParameters']['channels'] = $channels;
	}
}
