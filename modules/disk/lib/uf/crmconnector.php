<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrmConnector extends StubConnector
{
	protected function getMembers($members)
	{
		$result = array();

		foreach($members as $memberId)
		{
			$rs = \CUser::getList(
				($by="ID"),
				($order="ASC"),
				array("ID" => $memberId),
				array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "EMAIL", "PERSONAL_PHOTO"))
			);

			if ($ar = $rs->fetch())
			{
				$result[] = array(
					"NAME" => \CUser::formatName('#NAME# #LAST_NAME#',
						$ar,
						true,
						false
					),
					"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $memberId)),
					'AVATAR_SRC' => Ui\Avatar::getPerson($ar['PERSONAL_PHOTO']),
					"IS_EXTRANET" => "N"
				);
			}
		}

		return $result;
	}
}

class CrmMessageConnector extends CrmConnector
{
	public function getDataToShow()
	{
		return array(
			'DETAIL_URL' => \CComponentEngine::makePathFromTemplate('/crm/stream/?log_id=#log_id#', array('log_id' => $this->entityId)),
			'DESCRIPTION' => Loc::getMessage('DISK_UF_CRM_CONNECTOR_MESSAGE_DESCRIPTION')
		);
	}
}

class CrmMessageCommentConnector extends CrmConnector
{
	protected $logId = null;

	public function __construct($entityId, $logId)
	{
		parent::__construct($entityId);
		$this->logId = $logId;
	}

	public function getDataToShow()
	{
		return array(
			'DETAIL_URL' => \CComponentEngine::makePathFromTemplate(
					'/crm/stream/?log_id=#log_id#?commentId=#comment_id##com#comment_id#',
				array(
					'log_id' => $this->logId,
					'comment_id' => $this->entityId
				)
			),
			'DESCRIPTION' => Loc::getMessage('DISK_UF_CRM_CONNECTOR_MESSAGE_COMMENT_DESCRIPTION')
		);
	}
}

class CrmDealConnector extends CrmConnector
{
	public function getDataToShow()
	{
		$responsibleId = \CCrmOwnerType::getResponsibleID(\CCrmOwnerType::Deal, $this->entityId, false);

		return array(
			'TITLE' => Loc::getMessage('DISK_UF_CRM_CONNECTOR_DEAL_MESSAGE_TITLE', array('#NAME#' => \CCrmOwnerType::getCaption(\CCrmOwnerType::Deal, $this->entityId, false))),
		    'MEMBERS' => $this->getMembers(array($responsibleId))
		);
	}
}

class CrmLeadConnector extends CrmConnector
{
	public function getDataToShow()
	{
		$responsibleId = \CCrmOwnerType::getResponsibleID(\CCrmOwnerType::Lead, $this->entityId, false);

		return array(
			'TITLE' => Loc::getMessage('DISK_UF_CRM_CONNECTOR_LEAD_MESSAGE_TITLE', array('#NAME#' => \CCrmOwnerType::getCaption(\CCrmOwnerType::Lead, $this->entityId, false))),
		    'MEMBERS' => $this->getMembers(array($responsibleId))
		);
	}
}

class CrmCompanyConnector extends CrmConnector
{
	public function getDataToShow()
	{
		$responsibleId = \CCrmOwnerType::getResponsibleID(\CCrmOwnerType::Company, $this->entityId, false);

		return array(
			'TITLE' => Loc::getMessage('DISK_UF_CRM_CONNECTOR_COMPANY_MESSAGE_TITLE', array('#NAME#' => \CCrmOwnerType::getCaption(\CCrmOwnerType::Company, $this->entityId, false))),
			'MEMBERS' => $this->getMembers(array($responsibleId))
		);
	}
}

class CrmContactConnector extends CrmConnector
{
	public function getDataToShow()
	{
		$responsibleId = \CCrmOwnerType::getResponsibleID(\CCrmOwnerType::Contact, $this->entityId, false);

		return array(
			'TITLE' => Loc::getMessage('DISK_UF_CRM_CONNECTOR_CONTACT_MESSAGE_TITLE', array('#NAME#' => \CCrmOwnerType::getCaption(\CCrmOwnerType::Contact, $this->entityId, false))),
		    'MEMBERS' => $this->getMembers(array($responsibleId))
		);
	}
}