<?
/**
 * Note: this is an internal class for disk module. It wont work without the module installed.
 * @internal
 */

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Disk\Uf;

Loc::loadMessages(__FILE__);

class CommentConnector extends Uf\StubConnector
{
	public function getDataToShow()
	{
		return $this->getDataToShowByUser($this->getUser()->getId());
	}

	public function getDataToShowByUser(int $userId)
	{
		$timelineBinding = TimelineBindingTable::getList(
			array(
				"filter" => array('OWNER_ID' => $this->entityId),
				"limit" => 1
			)
		);

		if(!($bind = $timelineBinding->fetch()))
		{
			return null;
		}

		$data = array();

		$connector = null;
		if ($bind["ENTITY_TYPE_ID"] == \CCrmOwnerType::Deal
			|| $bind["ENTITY_TYPE_ID"] == \CCrmOwnerType::DealRecurring)
		{
			$connector = new Uf\CrmDealConnector($bind["ENTITY_ID"]);
		}
		elseif ($bind["ENTITY_TYPE_ID"] == \CCrmOwnerType::Lead)
		{
			$connector = new Uf\CrmLeadConnector($bind["ENTITY_ID"]);
		}
		elseif ($bind["ENTITY_TYPE_ID"] == \CCrmOwnerType::Company)
		{
			$connector = new Uf\CrmCompanyConnector($bind["ENTITY_ID"]);
		}
		elseif ($bind["ENTITY_TYPE_ID"] == \CCrmOwnerType::Contact)
		{
			$connector = new Uf\CrmContactConnector($bind["ENTITY_ID"]);
		}

		if ($connector)
		{
			$subData = $connector->getDataToShow();
			$data = array_merge($data, $subData);
		}

		return $data;

	}

	public function canRead($userId)
	{
		$timelineBinding = TimelineBindingTable::getList(
			array(
				"filter" => array('OWNER_ID' => $this->entityId)
			)
		);

		$isAllowed = false;
		while(!$isAllowed && ($bind = $timelineBinding->fetch()))
		{
			$isAllowed = EntityAuthorization::checkReadPermission($bind['ENTITY_TYPE_ID'], $bind['ENTITY_ID']);
		}

		return $isAllowed;
	}

	public function canUpdate($userId)
	{
		$timelineBinding = TimelineBindingTable::getList(
			array(
				"filter" => array('OWNER_ID' => $this->entityId)
			)
		);

		$isAllowed = false;
		while(!$isAllowed && ($bind = $timelineBinding->fetch()))
		{
			$isAllowed = EntityAuthorization::checkUpdatePermission($bind['ENTITY_TYPE_ID'], $bind['ENTITY_ID']);
		}

		return $isAllowed;
	}

	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}
}