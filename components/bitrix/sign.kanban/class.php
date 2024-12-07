<?php

use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Sign\Config\Storage;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignKanbanComponent extends SignBaseComponent
{
	protected function exec(): void
	{
		parent::exec();
		$this->prepareResult();
	}

	private function prepareResult(): void
	{
		$this->arResult['ENTITY_TYPE_ID'] = \Bitrix\Sign\Document\Entity\Smart::getEntityTypeId();
		$this->arResult['SHOW_STUB'] = $this->needShowStub();
		$this->arResult['SIGN_OPEN_MASTER_LINK'] = '/sign/doc/0/?categoryId=0';
		$this->arResult['SIGN_OPEN_HELPDESK_CODE'] = 16571388;
	}

	private function needShowStub(): bool
	{
		$someDocumentInPortal = \Bitrix\Sign\Internal\DocumentTable::query()
			->addSelect('ID')
			->setLimit(1)
			->fetchObject()
		;

		if ($someDocumentInPortal === null)
		{
			SmartDocument::createTypeIfNotExists();

			return true;
		}

		return false;
	}
}
