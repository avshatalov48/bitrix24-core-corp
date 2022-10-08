<?php

namespace Bitrix\Crm\Ml\Model;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Category\Entity\DealCategoryTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\Ml\DataProvider;
use Bitrix\Crm\Ml\FeatureBuilder;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

class DealScoring extends Base
{
	protected $deals;
	protected const CACHE_TTL = 86400;

	/**
	 * Returns available model names for the deal scoring.
	 * @return string[]
	 */
	public static function getModelNames()
	{
		static $result = [];
		if (!empty($result))
		{
			return $result;
		}

		$cursor = DealCategoryTable::getList([
			'select' => ['ID'],
			'cache' => ['ttl' => static::CACHE_TTL]
		]);

		$result[] = 'CRM_DEAL_DEFAULT';
		while ($row = $cursor->fetch())
		{
			$result[] = 'CRM_DEAL_CATEGORY_' . $row['ID'];
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getPossibleFields()
	{
		$result = [];

		// Common features
		$result["DEAL_ID"] = ["dataType" => "string", "isRowId" => true];
		$result["SUCCESS"] = ["dataType" => "bool", "isTarget" => true];
		$result["SOURCE_ID"] = ["dataType" => "string"];
		$result["SOURCE_DESCRIPTION"] = ["dataType" => "string"];
		$result["IS_RETURN_CUSTOMER"] = ["dataType" => "bool"];
		$result["IS_REPEATED_APPROACH"] = ["dataType" => "bool"];
		$result["TITLE"] = ["dataType" => "text"];
		$result["COMMENTS"] = ["dataType" => "text"];
		$result["COMPANY_TYPE"] = ["dataType" => "string"];
		$result["COMPANY_INDUSTRY"] = ["dataType" => "string"];
		//$result["COMPANY_CITY"] = ["dataType" => "string"];
		$result["CONTACT_TYPE_ID"] = ["dataType" => "string"];
		$result["CONTACT_SOURCE_ID"] = ["dataType" => "string"];
		$result["CONTACT_POST"] = ["dataType" => "string"];
		$result["HAS_EMAIL"] = ["dataType" => "bool"];
		$result["HAS_PHONE"] = ["dataType" => "bool"];
		$result["DATE_CREATE_MONTH"] = ["dataType" => "string"];
		$result["DATE_CREATE_DAY_OF_WEEK"] = ["dataType" => "string"];
		$result["DATE_CREATE_TIME"] = ["dataType" => "string"]; // category: morning, day, evening, night
		$result["ASSIGNED_BY_ID"] = ["dataType" => "string"];

		$ufFeatures = static::getUserFieldName();
		if(count($ufFeatures) > 0)
		{
			$result += $ufFeatures;
		}

		/** @var DataProvider\Base[] $providers */
		$providers = [
			new DataProvider\Mail(),
			new DataProvider\OpenLines(),
			new DataProvider\Telephony(),
			new DataProvider\Tracking(),
		];

		foreach ($providers as $provider)
		{
			$featureMap = $provider->getFeatureMap();
			if(is_array($featureMap))
			{
				$result += $featureMap;
			}
		}

		// Form features
		//$result["HAS_FILLED_FORMS"] = ["dataType" => "bool"];

		return $result;
	}

	/**
	 * Returns count of successful and failed records in the training set for this model.
	 *
	 * @return [$successfulCount, $failedCount]
	 */
	public function getTrainingSetSize()
	{
		static $cache = [];
		if (isset($cache[$this->name]))
		{
			return $cache[$this->name];
		}

		$categoryId = static::getModelCategory($this->name);
		$cursor = DealTable::getList([
			"select" => [
				"STAGE_SEMANTIC_ID",
				"CNT" => \Bitrix\Main\Entity\Query::expr()->count("ID")
			],
			"filter" => [
				"=STAGE_SEMANTIC_ID" => ["S", "F"],
				"=CATEGORY_ID" => $categoryId,
				"HAS_ACT" => 1
			],
			"group" => ["STAGE_SEMANTIC_ID"],
			"runtime" => [
				new \Bitrix\Main\ORM\Fields\ExpressionField(
					"HAS_ACT",
					"CASE WHEN EXISTS(SELECT 'x' FROM b_crm_act_bind WHERE OWNER_TYPE_ID = " . \CCrmOwnerType::Deal . " and OWNER_ID = %s) THEN 1 ELSE 0 END",
					["ID"]
				),
			],
		]);
		$rows = [];
		while ($row = $cursor->fetch())
		{
			$rows[$row["STAGE_SEMANTIC_ID"]] = (int)$row["CNT"];
		}

		$cache[$this->name] = [$rows["S"], $rows["F"]];
		return $cache[$this->name];
	}

	public function getTrainingSet($fromId, $limit)
	{
		$categoryId = static::getModelCategory($this->name);
		$rows = DealTable::getList([
			"select" => ["ID"],
			"filter" => [
				"=STAGE_SEMANTIC_ID" => ["S", "F"],
				"=CATEGORY_ID" => $categoryId,
				"=HAS_ACT" => 1,
				">ID" => $fromId
			],
			"runtime" => [
				new \Bitrix\Main\ORM\Fields\ExpressionField(
					"HAS_ACT",
					"CASE WHEN EXISTS(SELECT 'x' FROM b_crm_act WHERE OWNER_TYPE_ID = " . \CCrmOwnerType::Deal . " and OWNER_ID = %s) THEN 1 ELSE 0 END",
					["ID"]
				),
			],
			"limit" => $limit,
			"order" => ["ID" => "asc"]
		])->fetchAll();

		return array_column($rows, "ID");
	}

	public function getPredictionSet($fromId, $limit)
	{
		$ids = [];
		$categoryId = static::getModelCategory($this->name);
		$cursor = DealTable::getList([
			"select" => ["ID"],
			"filter" => [
				"=STAGE_SEMANTIC_ID" => "P",
				"=CATEGORY_ID" => $categoryId,
				">ID" => $fromId
			],
			"limit" => $limit,
			"order" => ["ID" => "asc"]
		]);

		while ($row = $cursor->fetch())
		{
			$ids[] = $row["ID"];
		}

		$result = [];

		foreach ($ids as $dealId)
		{
			$result[] = $this->buildFeaturesVector($dealId);
		}

		return $result;
	}

	public function buildFeaturesVector($id) // filter maybe?
	{
		$result = DealTable::getList([
			"select" => [
				"DEAL_ID" => "ID",
				"DATE_CREATE",
				"STAGE_SEMANTIC_ID",
				"SOURCE_ID" => "SOURCE_ID",
				"SOURCE_DESCRIPTION" => "SOURCE_DESCRIPTION",
				"ASSIGNED_BY_ID",
				"IS_RETURN_CUSTOMER" => "IS_RETURN_CUSTOMER",
				"IS_REPEATED_APPROACH" => "IS_REPEATED_APPROACH",
				"TITLE",
				"COMMENTS",
				"COMPANY_ID",
				"COMPANY_TYPE" => "COMPANY.COMPANY_TYPE",
				"COMPANY_INDUSTRY" => "COMPANY.INDUSTRY",
				"CONTACT_ID",
				"CONTACT_TYPE_ID" => "CONTACT.TYPE_ID",
				"CONTACT_SOURCE_ID" => "CONTACT.SOURCE_ID",
				"CONTACT_POST" => "CONTACT.POST",
			],
			"filter" => [
				"=ID" => $id
			]
		])->fetch();

		if(!$result)
		{
			return false;
		}

		$stage = $result["STAGE_SEMANTIC_ID"];
		unset($result["STAGE_SEMANTIC_ID"]);
		$isClosed = PhaseSemantics::isFinal($stage);
		$dateClose = null;
		if($isClosed)
		{
			$result["SUCCESS"] = PhaseSemantics::isLost($stage) ? "N" : "Y";
			$lastStageHistoryEntry = \Bitrix\Crm\History\DealStageHistoryEntry::getLatest($id);
			$dateClose = $lastStageHistoryEntry["CREATED_TIME"];
		}

		$result["IS_RETURN_CUSTOMER"] = $result["IS_RETURN_CUSTOMER"] === "Y" ? "Y" : "N";
		$result["IS_REPEATED_APPROACH"] = $result["IS_REPEATED_APPROACH"] === "Y" ? "Y" : "N";
		$result["TITLE"] = FeatureBuilder::clearText($result["TITLE"]);
		$result["COMMENTS"] = FeatureBuilder::clearText($result["COMMENTS"]);

		// MultiFields
		$result["HAS_EMAIL"] = "N";
		$result["HAS_PHONE"] = "N";
		$bindings = [];
		if($result["COMPANY_ID"] > 0)
		{
			$bindings[] = [
				"=ENTITY_ID" => \CCrmOwnerType::CompanyName,
				"=ELEMENT_ID" => $result["COMPANY_ID"]
			];
		}
		unset($result["COMPANY_ID"]);
		if($result["CONTACT_ID"] > 0)
		{
			$bindings[] = [
				"=ENTITY_ID" => \CCrmOwnerType::ContactName,
				"=ELEMENT_ID" => $result["CONTACT_ID"]
			];
		}
		unset($result["CONTACT_ID"]);
		$additionalContacts = DealContactTable::getDealContactIDs($id);
		foreach ($additionalContacts as $contactId)
		{
			$bindings[] = [
				"=ENTITY_ID" => \CCrmOwnerType::ContactName,
				"=ELEMENT_ID" => $contactId
			];
		}

		if(count($bindings) > 0)
		{
			$cursor = FieldMultiTable::getList([
				"select" => ["*"],
				"filter" => [
					"=TYPE_ID" => [\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL],
					[
						"LOGIC" => "OR",
						$bindings
					]
				]
			]);

			while ($row = $cursor->fetch())
			{
				if($row["TYPE_ID"] === \CCrmFieldMulti::EMAIL)
				{
					$result["HAS_EMAIL"] = "Y";
				}
				else if($row["TYPE_ID"] === \CCrmFieldMulti::PHONE)
				{
					$result["HAS_PHONE"] = "Y";
				}
			}
		}

		$result["DATE_CREATE_MONTH"] = $result["DATE_CREATE"] instanceof DateTime ? FeatureBuilder::getMonth($result["DATE_CREATE"]) : "";
		$result["DATE_CREATE_DAY_OF_WEEK"] = $result["DATE_CREATE"] instanceof DateTime ? FeatureBuilder::getDayOfWeek($result["DATE_CREATE"]) : "";
		$result["DATE_CREATE_TIME"] = $result["DATE_CREATE"] instanceof DateTime ? FeatureBuilder::getTimeMnemonic($result["DATE_CREATE"]) : "";
		unset($result["DATE_CREATE"]);

		// UF features
		$ufFeatures = static::getUserFieldFeatures($id);
		if(is_array($ufFeatures))
		{
			$result += $ufFeatures;
		}

		/** @var DataProvider\Base[] $providers */
		$providers = [
			new DataProvider\Mail($dateClose),
			new DataProvider\OpenLines($dateClose),
			new DataProvider\Telephony($dateClose),
			new DataProvider\Tracking($dateClose),
		];

		foreach ($providers as $provider)
		{
			$providerFeatures = $provider->getFeatures(\CCrmOwnerType::Deal, $id);
			if(is_array($providerFeatures))
			{
				$result += $providerFeatures;
			}
		}

		return $result;
	}

	public static function getModelNameByDeal($dealId)
	{
		$dealId = (int)$dealId;
		if(!$dealId)
		{
			return false;
		}

		$row = DealTable::getList([
			"select" => ["CATEGORY_ID"],
			"filter" => [
				"=ID" => $dealId
			]
		])->fetch();

		if(!$row)
		{
			return false;
		}

		return static::getModelName($row["CATEGORY_ID"]);
	}

	protected static function getModelName($categoryId)
	{
		return $categoryId == 0 ? "CRM_DEAL_DEFAULT" :"CRM_DEAL_CATEGORY_" . $categoryId;
	}

	protected static function getModelCategory($modelName)
	{
		if($modelName === "CRM_DEAL_DEFAULT")
		{
			return 0;
		}
		else if(mb_strpos($modelName, "CRM_DEAL_CATEGORY_") !== false)
		{
			return (int)mb_substr($modelName, 18);
		}
		else {
			throw new ArgumentException("Unknown model name $modelName");
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle()
	{
		$dealCategory = static::getModelCategory($this->getName());

		if($dealCategory > 0)
		{
			return Loc::getMessage("CRM_DEAL_SCORING_MODEL_TITLE", [
				"#CATEGORY_NAME#" => DealCategory::getName($dealCategory)
			]);
		}
		else
		{
			return Loc::getMessage("CRM_DEAL_SCORING_MODEL_TITLE_DEFAULT");
		}

	}

	public function hasAccess(int $userId = 0)
	{
		$categoryId = static::getModelCategory($this->name);
		$userPermission = \CCrmPerms::GetUserPermissions($userId);

		return \CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId);
	}

	protected static function getUserFieldName()
	{
		static $result;

		if(!is_null($result))
		{
			return $result;
		}

		$result = [];
		$dealUserType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmDeal::GetUserFieldEntityID());
		$userFields = $dealUserType->GetFields();
		foreach ($userFields as $fieldName => $fieldDescription)
		{
			switch ($fieldDescription["USER_TYPE_ID"])
			{
				case "enumeration":
				case "string":
					$result[$fieldDescription["FIELD_NAME"]] = ["dataType" => "text"];
					break;
				case "boolean":
					$result[$fieldDescription["FIELD_NAME"]] = ["dataType" => "bool"];
					break;
				case "integer":
				case "double":
					$result[$fieldDescription["FIELD_NAME"]] = ["dataType" => "int"];
					break;
				case "date":
					$result[$fieldDescription["FIELD_NAME"]."_DAY_OF_WEEK"] = ["dataType" => "string"];
					$result[$fieldDescription["FIELD_NAME"]."_MONTH"] = ["dataType" => "string"];
					break;
				case "datetime":
					$result[$fieldDescription["FIELD_NAME"]."_DAY_OF_WEEK"] = ["dataType" => "string"];
					$result[$fieldDescription["FIELD_NAME"]."_MONTH"] = ["dataType" => "string"];
					$result[$fieldDescription["FIELD_NAME"]."_TIME"] = ["dataType" => "string"];
					break;
				default:
					$result[$fieldName."_FILLED"] = ["dataType" => "bool"];
					break;
			}
		}

		return $result;
	}

	protected static function getUserFieldFeatures($dealId)
	{
		static $fieldTypes;
		if(is_null($fieldTypes))
		{
			$fieldTypes = [];

			$dealUserType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmDeal::GetUserFieldEntityID());
			$fields = $dealUserType->GetFields();

			$fieldTypes = array_map(function($field){return $field["USER_TYPE_ID"];}, $fields);
		}

		$fieldValues = DealTable::getList([
			"select" => ["UF_*"],
			"filter" => [
				"=ID" => $dealId
			]
		])->fetch();


		if(!is_array($fieldTypes) || count($fieldTypes) == 0)
		{
			return false;
		}

		$result = [];

		foreach ($fieldTypes as $fieldName => $type)
		{
			switch ($type)
			{
				case "enumeration":
				case "string":
					$result[$fieldName] = "";
					break;
				case "boolean":
					$result[$fieldName] = "N";
					break;
				case "integer":
				case "double":
					$result[$fieldName] = "";
					break;
				case "date":
					$result[$fieldName."_DAY_OF_WEEK"] = "";
					$result[$fieldName."_MONTH"] = "";
					break;
				case "datetime":
					$result[$fieldName."_DAY_OF_WEEK"] = "";
					$result[$fieldName."_MONTH"] = "";
					$result[$fieldName."_TIME"] = "";
					break;
				default:
					$result[$fieldName."_FILLED"] = "N";
					break;
			}
		}

		foreach ($fieldValues as $fieldName => $value)
		{
			$fieldType = $fieldTypes[$fieldName];
			if(!$fieldType)
			{
				continue;
			}

			if($fieldType == "string" || $fieldType == "enumeration")
			{
				if(is_array($value))
				{
					$value = implode(" ", $value);
				}
				$result[$fieldName] = FeatureBuilder::clearText($value);
			}
			else if($fieldType == "boolean")
			{
				if(is_array($value))
				{
					$value = $value[0];
				}
				$result[$fieldName] = $value ? "Y" : "N";
			}
			else if($fieldType == "integer" || $fieldType == "double")
			{
				if(is_array($value))
				{
					$value = $value[0];
				}
				$result[$fieldName] = $value != "" ? (int)$value : "";
			}
			else if($fieldType == "date")
			{
				if(is_array($value))
				{
					$value = $value[0];
				}
				$result[$fieldName."_DAY_OF_WEEK"] = $value instanceof Date ? FeatureBuilder::getDayOfWeek($value) : "";
				$result[$fieldName."_MONTH"] = $value instanceof Date ? FeatureBuilder::getMonth($value) : "";
			}
			else if($fieldType == "datetime")
			{
				if(is_array($value))
				{
					$value = $value[0];
				}
				$result[$fieldName."_DAY_OF_WEEK"] = $value instanceof DateTime ? FeatureBuilder::getDayOfWeek($value) : "";
				$result[$fieldName."_MONTH"] = $value instanceof DateTime ? FeatureBuilder::getMonth($value) : "";
				$result[$fieldName."_TIME"] = $value instanceof DateTime ? FeatureBuilder::getTimeMnemonic($value) : "";
			}
			else
			{
				$result[$fieldName."_FILLED"] =empty($value) ? "N" : "Y";
			}
		}

		return $result;
	}



}