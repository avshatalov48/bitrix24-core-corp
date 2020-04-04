<?
namespace Bitrix\Crm\Update\UserField;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

class CrmQuoteField extends Stepper
{
	protected static $moduleId = "crm";

	public function execute(array &$result)
	{
		if(!Loader::includeModule("crm"))
			return false;

		$className = get_class($this);
		$option = Option::get("crm", $className, 0);
		$result["steps"] = $option;

		$limit = 50;
		$result["steps"] = isset($result["steps"]) ? $result["steps"] : 0;
		$selectedRowsCount = 0;

		$listUfFieldsForCheck = $this->getListUfFieldsForCheck("CRM_QUOTE");
		$select = array("ID");
		foreach($listUfFieldsForCheck as $fieldId => $field)
			$select[] = $field["FIELD_NAME"];

		$objectQuery = \CCrmQuote::getList(
			array("ID" => "DESC"),
			array("CHECK_PERMISSIONS" => "N"),
			false,
			false,
			$select,
			array("QUERY_OPTIONS" => array("LIMIT" => $limit, "OFFSET" => $result["steps"]))
		);
		if($objectQuery)
		{
			$selectedRowsCount = $objectQuery->selectedRowsCount();
			while($entity = $objectQuery->fetch())
			{
				foreach($listUfFieldsForCheck as $fieldId => $field)
				{
					if(!empty($entity[$field["FIELD_NAME"]]))
					{
						$listValuesForUpdate = $this->prepareListValuesForUpdate($field, $entity);
						$this->setFieldValue($field, $entity, $listValuesForUpdate);
					}
				}
			}
		}

		if($selectedRowsCount < $limit)
		{
			Option::delete("crm", array("name" => $className));
			return false;
		}
		else
		{
			$result["steps"] = $result["steps"] + $selectedRowsCount;
			$option = $result["steps"];
			Option::set("crm", $className, $option);
			return true;
		}
	}

	protected function getListUfFieldsForCheck($entityId)
	{
		$queryObject = \CUserTypeEntity::getList(array(), array("ENTITY_ID" => $entityId, "USER_TYPE_ID" => "crm"));
		$listUfFieldsForCheck = array();
		while($listUfFields = $queryObject->fetch())
		{
			if(is_array($listUfFields["SETTINGS"]))
			{
				$tmpArray = array_filter($listUfFields["SETTINGS"], function($mark){
					return $mark == "Y";
				});
				if(count($tmpArray) == 1)
				{
					$listUfFieldsForCheck[$listUfFields["ID"]]= array(
						"ENTITY_ID" => $listUfFields["ENTITY_ID"],
						"FIELD_NAME" => $listUfFields["FIELD_NAME"],
						"AVAILABLE_ENTITY_TYPE" => array_search("Y", $tmpArray)
					);
				}
			}
		}

		return $listUfFieldsForCheck;
	}

	protected function prepareListValuesForUpdate($field, $entity)
	{
		$ufFieldValues = $entity[$field["FIELD_NAME"]];
		$listValuesForUpdate = array($field["FIELD_NAME"] => array());
		if(!empty($ufFieldValues))
		{
			if(is_array($ufFieldValues))
			{
				foreach($ufFieldValues as $fieldValue)
				{
					if(!intval($fieldValue))
					{
						$explode = explode('_', $fieldValue);
						if(\CUserTypeCrm::getLongEntityType($explode[0]) == $field["AVAILABLE_ENTITY_TYPE"])
						{
							$listValuesForUpdate[$field["FIELD_NAME"]][] = intval($explode[1]);
						}
					}
				}
			}
			else
			{
				if(!intval($ufFieldValues))
				{
					$explode = explode('_', $ufFieldValues);
					if(\CUserTypeCrm::getLongEntityType($explode[0]) == $field["AVAILABLE_ENTITY_TYPE"])
					{
						$listValuesForUpdate[$field["FIELD_NAME"]] = intval($explode[1]);
					}
				}
			}
		}

		return $listValuesForUpdate;
	}

	protected function setFieldValue($field, $entity, array $listValuesForUpdate)
	{
		global $USER_FIELD_MANAGER;
		if(!empty($listValuesForUpdate[$field["FIELD_NAME"]]))
		{
			\CCrmEntityHelper::normalizeUserFields($listValuesForUpdate, $field["ENTITY_ID"],
				$USER_FIELD_MANAGER, array("IS_NEW" => false));
			$USER_FIELD_MANAGER->Update($field["ENTITY_ID"], $entity["ID"], $listValuesForUpdate);
		}
	}
}