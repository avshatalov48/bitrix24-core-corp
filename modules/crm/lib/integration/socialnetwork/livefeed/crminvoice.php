<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CrmInvoice extends CrmEntity
{
	public const PROVIDER_ID = 'CRM_INVOICE';
	public const CONTENT_TYPE_ID = 'CRM_INVOICE';

	public function getEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::InvoicePrefix . \CCrmLiveFeedEvent::Add,
		];
	}

	public function initSourceFields()
	{
		$entityId = $this->getEntityId();

		$fields = array();

		if ($entityId > 0)
		{
			$fields = array(
				'ID' => $entityId
			);

			if ($currentEntity = \CCrmInvoice::getById($entityId, false))
			{
				$fields['CURRENT_ENTITY'] = $currentEntity;
				$this->setSourceTitle(Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_TITLE_INVOICE', array(
					'#ACCOUNT_NUMBER#' => $currentEntity['ACCOUNT_NUMBER'],
					'#ORDER_TOPIC#' => $currentEntity['ORDER_TOPIC']
				)));
			}
		}

		$this->setSourceFields($fields);
	}
}
