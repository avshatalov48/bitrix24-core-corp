<?
namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use \Bitrix\Socialnetwork\Livefeed\Provider;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Socialnetwork\LogTable;

Loc::loadMessages(__FILE__);

final class CrmInvoice extends Provider
{
	const PROVIDER_ID = 'CRM_INVOICE';
	const CONTENT_TYPE_ID = 'CRM_INVOICE';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	public function getEventId()
	{
		return array(
			\CCrmLiveFeedEvent::InvoicePrefix.\CCrmLiveFeedEvent::Add
		);
	}

	public function getType()
	{
		return Provider::TYPE_POST;
	}

	public function getCommentProvider()
	{
		$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment();
		return $provider;
	}

	public function initSourceFields()
	{
		$entityId = $this->getEntityId();
		$logId = $this->getLogId();

		$fields = $entity = $logEntry = array();

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
//				$this->setSourceDescription($logEntry['MESSAGE']);
			}
		}

		$this->setSourceFields($fields);
	}

	public function getLiveFeedUrl()
	{
		$result = '';
		$logId = $this->getLogId();

		if ($logId > 0)
		{
			$result = "/crm/stream/?log_id=".$logId;
		}

		return $result;
	}
}