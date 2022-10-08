<?php

namespace Bitrix\Crm\Ml\Model;

use Bitrix\Crm\LeadTable;
use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Ml\FeatureBuilder;
use Bitrix\Crm\Ml\DataProvider;
use Bitrix\Main\Localization\Loc;

class LeadScoring extends Base
{
	const MODEL_NAME ='CRM_LEAD_SCORING';

	/**
	 * Returns available model names for the deal scoring.
	 * @return string[]
	 */
	public static function getModelNames()
	{
		return [static::MODEL_NAME];
	}

	/**
	 * Returns title for lead scoring model.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage("CRM_LEAD_SCORING_TITLE");
	}

	public function hasAccess(int $userId = 0)
	{
		$userPermission = \CCrmPerms::GetUserPermissions($userId);
		return \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Lead, 0, $userPermission);
	}

	/**
	 * @return array
	 */
	public function getPossibleFields()
	{
		$result = [];

		// Common features
		$result["LEAD_ID"] = ["dataType" => "string", "isRowId" => true];
		$result["SUCCESS"] = ["dataType" => "bool", "isTarget" => true];
		$result["SOURCE_ID"] = ["dataType" => "string"];
		$result["SOURCE_DESCRIPTION"] = ["dataType" => "text"];
		$result["TITLE"] = ["dataType" => "text"];
		$result["COMMENTS"] = ["dataType" => "text"];
		$result["IS_RETURN_CUSTOMER"] = ["dataType" => "bool"];
		$result["HAS_EMAIL"] = ["dataType" => "bool"];
		$result["HAS_PHONE"] = ["dataType" => "bool"];
		$result["DATE_CREATE_MONTH"] = ["dataType" => "string"];
		$result["DATE_CREATE_DAY_OF_WEEK"] = ["dataType" => "string"];
		$result["DATE_CREATE_TIME"] = ["dataType" => "string"]; // category: morning, day, evening, night
		$result["ASSIGNED_BY_ID"] = ["dataType" => "string"];

		// UF features

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

		return $result;
	}

	/**
	 * Returns count of successful and failed records in the training set for this model.
	 *
	 * @return [$successfulCount, $failedCount]
	 */
	public function getTrainingSetSize()
	{
		static $cachedResult = null;
		if ($cachedResult != null)
		{
			return $cachedResult;
		}

		$cursor = LeadTable::getList([
			"select" => [
				"STATUS_SEMANTIC_ID",
				"CNT" => Query::expr()->count("ID")
			],
			"filter" => [
				"=STATUS_SEMANTIC_ID" => ["S", "F"],
				"=HAS_ACT" => 1,
			],
			"group" => ["STATUS_SEMANTIC_ID"],
			"runtime" => [
				new ExpressionField(
					"HAS_ACT",
					"CASE WHEN EXISTS(SELECT 'x' FROM b_crm_act_bind WHERE OWNER_TYPE_ID = " . \CCrmOwnerType::Lead . " and OWNER_ID = %s) THEN 1 ELSE 0 END",
					["ID"]
				),
			],
		]);

		$rows = [];
		while ($row = $cursor->fetch())
		{
			$rows[$row["STATUS_SEMANTIC_ID"]] = (int)$row["CNT"];
		}

		$cachedResult = [$rows["S"], $rows["F"]];
		return $cachedResult;
	}

	public function getTrainingSet($fromId, $limit)
	{
		$rows = LeadTable::getList([
			"select" => ["ID", "HAS_ACT"],
			"filter" => [
				"=STATUS_SEMANTIC_ID" => ["S", "F"],
				"=HAS_ACT" => 1,
				">ID" => $fromId
			],
			"runtime" => [
				new ExpressionField(
				"HAS_ACT",
				"CASE WHEN EXISTS(SELECT 'x' FROM b_crm_act WHERE OWNER_TYPE_ID = " . \CCrmOwnerType::Lead . " and OWNER_ID = %s) THEN 1 ELSE 0 END",
				["ID"]
				),
			],
			"limit" => $limit,
			"order" => ["ID" => "asc"]
		])->fetchAll();

		return array_column($rows, "ID");
	}

	public function getPredictionSet($modelName, $fromId, $limit)
	{
		$ids = [];
		$cursor = LeadTable::getList([
			"select" => ["ID", "HAS_ACT"],
			"filter" => [
				"=STATUS_SEMANTIC_ID" => ["P"],
				"=HAS_ACT" => 1,
				">ID" => $fromId
			],
			"runtime" => [
				new ExpressionField(
					"HAS_ACT",
					"CASE WHEN EXISTS(SELECT 'x' FROM b_crm_act WHERE OWNER_TYPE_ID = 1 and OWNER_ID = %s) THEN 1 ELSE 0 END",
					["ID"]
				),
			],
			"limit" => $limit,
			"order" => ["ID" => "asc"]
		]);

		while ($row = $cursor->fetch())
		{
			$ids[] = $row["ID"];
		}

		$result = [];

		foreach ($ids as $leadId)
		{
			$result[] = $this->buildFeaturesVector($leadId);
		}

		return $result;
	}

	/**
	 *
	 *
	 * "LEAD_ID"
	 * "SUCCESS"
	 * "SOURCE_ID"
	 * "SOURCE_DESCRIPTION"
	 * "TITLE"
	 * "COMMENTS"
	 * "IS_RETURN_CUSTOMER"
	 * "HAS_EMAIL"
	 * "HAS_PHONE"
	 * "MONTH"
	 * "DAY_OF_WEEK"
	 * "TIME"
	 *
	 */
	public function buildFeaturesVector($id)
	{
		$result = LeadTable::getList([
			"select" => [
				"LEAD_ID" => "ID",
				"DATE_CREATE",
				"ASSIGNED_BY_ID",
				"STATUS_SEMANTIC_ID",
				"SOURCE_ID" => "SOURCE_ID",
				"SOURCE_DESCRIPTION" => "SOURCE_DESCRIPTION",
				"TITLE",
				"COMMENTS",
				"IS_RETURN_CUSTOMER" => "IS_RETURN_CUSTOMER",
			],
			"filter" => [
				"=ID" => $id
			]
		])->fetch();

		if(!$result)
		{
			return false;
		}

		$status = $result["STATUS_SEMANTIC_ID"];
		unset($result["STAGE_SEMANTIC_ID"]);
		$isClosed = PhaseSemantics::isFinal($status);
		$dateClose = null;
		if($isClosed)
		{
			$result["SUCCESS"] = PhaseSemantics::isLost($status) ? "N" : "Y";
			$lastStatusHistoryEntry = \Bitrix\Crm\History\LeadStatusHistoryEntry::getLatest($id);
			$dateClose = $lastStatusHistoryEntry["CREATED_TIME"];
		}

		$result["SOURCE_DESCRIPTION"] = FeatureBuilder::clearText($result["SOURCE_DESCRIPTION"]);
		$result["TITLE"] = FeatureBuilder::clearText($result["TITLE"]);
		$result["COMMENTS"] = FeatureBuilder::clearText($result["COMMENTS"]);
		$result["IS_RETURN_CUSTOMER"] = $result["IS_RETURN_CUSTOMER"] === "Y" ? "Y" : "N";
		$result["IS_REPEATED_APPROACH"] = $result["IS_REPEATED_APPROACH"] === "Y" ? "Y" : "N";

		// MultiFields
		$result["HAS_EMAIL"] = "N";
		$result["HAS_PHONE"] = "N";
		$cursor = FieldMultiTable::getList([
			"select" => ["*"],
			"filter" => [
				"=TYPE_ID" => [\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL],
				"=ENTITY_ID" => \CCrmOwnerType::LeadName,
				"=ELEMENT_ID" => $id
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
			$providerFeatures = $provider->getFeatures(\CCrmOwnerType::Lead, $id);
			if(is_array($providerFeatures))
			{
				$result += $providerFeatures;
			}
		}

		return $result;
	}

	protected static function getUserFieldName()
	{
		static $result;

		if(!is_null($result))
		{
			return $result;
		}

		$result = [];
		$leadUserType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmLead::GetUserFieldEntityID());
		$userFields = $leadUserType->GetFields();
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

	protected static function getUserFieldFeatures($leadId)
	{
		static $fieldTypes;
		if(is_null($fieldTypes))
		{
			$fieldTypes = [];

			$leadUserType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmLead::GetUserFieldEntityID());
			$fields = $leadUserType->GetFields();

			$fieldTypes = array_map(function($field){return $field["USER_TYPE_ID"];}, $fields);
		}

		$fieldValues = LeadTable::getList([
			"select" => ["UF_*"],
			"filter" => [
				"=ID" => $leadId
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