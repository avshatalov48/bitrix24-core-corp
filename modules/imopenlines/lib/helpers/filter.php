<?php
namespace Bitrix\ImOpenLines\Helpers;

use Bitrix\ImOpenLines\Session;
use Bitrix\Main\ArgumentException;
use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\HttpApplication;

use \Bitrix\ImOpenlines\Security,
	\Bitrix\ImOpenLines\Model\SessionTable;

class Filter
{
	/**
	 * @return array
	 * @throws ArgumentException
	 */
	public static function getFilter(string $filterId, ?array $arResult = null, ?array $filterDefinition = null): array
	{
		$request = HttpApplication::getInstance()->getContext()->getRequest();

		$configId = (int)$request->get('CONFIG_ID') ?? 0;

		$ufFieldsList = self::getUfFieldList();

		$filterOptions = new \Bitrix\Main\UI\Filter\Options($filterId);

		if (!$filterDefinition)
		{
			$filterDefinition = self::getFilterDefinition($ufFieldsList, $configId, $arResult);
		}

		$filter = $filterOptions->getFilter($filterDefinition);

		$userPermissions = Security\Permissions::createWithCurrentUser();
		$allowedUserIds = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$userPermissions->getPermission(Security\Permissions::ENTITY_SESSION, Security\Permissions::ACTION_VIEW)
		);

		$result = [];

		if ($request->get('GUEST_USER_ID'))
		{
			$result['=USER_ID'] = intval($request->get('GUEST_USER_ID'));
		}

		if (!isset($filter['OPERATOR_ID']) && $request->get('OPERATOR_ID') !== null)
		{
			$value = $request->get('OPERATOR_ID');
			$filter['OPERATOR_ID'] = (
			is_string($value) && mb_strtolower($value) === 'empty'
				? 'empty'
				: intval($value)
			);
		}

		if (isset($filter['CLIENT_NAME']))
		{
			$filterUserClient = \Bitrix\Main\UserUtils::getUserSearchFilter(Array(
				'FIND' => $filter['CLIENT_NAME']
			));

			$filterUserClient['EXTERNAL_AUTH_ID'] = array('imconnector');

			$userClientRaw = \Bitrix\Main\UserTable::getList(Array(
				'select' => Array('ID'),
				'filter' => $filterUserClient
			));

			while ($userClientRow = $userClientRaw->fetch())
			{
				$result['=USER_ID'][] = $userClientRow['ID'];
			}

			if (empty($result['=USER_ID']))
			{
				$result['=USER_ID'] = -1;
			}
		}

		if (isset($filter['OPERATOR_ID']))
		{
			$filter['OPERATOR_ID'] =
				mb_strtolower($filter['OPERATOR_ID']) === 'empty'
					? false
					: (int)$filter['OPERATOR_ID']
			;

			if (is_array($allowedUserIds))
			{
				$result['=OPERATOR_ID'] = array_intersect(array_merge($allowedUserIds, array(false)), array($filter['OPERATOR_ID']));
			}
			else
			{
				$result['=OPERATOR_ID'] = $filter['OPERATOR_ID'];
			}
		}
		elseif (is_array($allowedUserIds))
		{
			$result['=OPERATOR_ID'] = $allowedUserIds;
		}

		if (\CTimeZone::GetOffset() == 0)
		{
			$userOffset = (new \DateTime())->getOffset();
		}
		else
		{
			$userOffset = \CTimeZone::GetOffset() + (new \DateTime())->getOffset();
		}
		$userTimeZone = \Bitrix\Main\Type\DateTime::secondsToOffset($userOffset);
		$timeZone = new \DateTimeZone($userTimeZone);

		$extractDateRange = function($fieldName) use (&$filter, &$result, $timeZone)
		{
			if (!empty($filter["{$fieldName}_from"]))
			{
				try
				{
					$result[">={$fieldName}"] = new \Bitrix\Main\Type\DateTime($filter["{$fieldName}_from"], null, $timeZone);
				}
				catch (\Exception $e)
				{
				}
			}
			if (!empty($filter["{$fieldName}_to"]))
			{
				try
				{
					$result["<={$fieldName}"] = new \Bitrix\Main\Type\DateTime($filter["{$fieldName}_to"], null, $timeZone);
				}
				catch (\Exception $e)
				{
				}
			}
		};

		$extractDateRange('DATE_CREATE');
		$extractDateRange('DATE_CLOSE');

		if (isset($filter['SOURCE']) && is_array($filter['SOURCE']))
		{
			$result['=SOURCE'] = $filter['SOURCE'];
		}

		if (isset($filter['CONFIG_ID']) && is_array($filter['CONFIG_ID']))
		{
			$result['=CONFIG_ID'] = $filter['CONFIG_ID'];
		}
		else if ($configId)
		{
			$result['=CONFIG_ID'] = $configId;
		}

		if (!empty($filter['EXTRA_URL']))
		{
			$result['%EXTRA_URL'] = $filter['EXTRA_URL'];
		}

		if (!empty($filter['EXTRA_TARIFF']))
		{
			if (mb_strpos($filter['EXTRA_TARIFF'],'%') !== false && mb_strpos($filter['EXTRA_TARIFF'],'%') == 0)
			{
				$result['%EXTRA_TARIFF'] = mb_substr($filter['EXTRA_TARIFF'], 1);
			}
			else
			{
				$result['=EXTRA_TARIFF'] = $filter['EXTRA_TARIFF'];
			}
		}

		if (!empty($filter['EXTRA_USER_LEVEL']))
		{
			$result['=EXTRA_USER_LEVEL'] = $filter['EXTRA_USER_LEVEL'];
		}

		if (!empty($filter['EXTRA_PORTAL_TYPE']))
		{
			$result['=EXTRA_PORTAL_TYPE'] = $filter['EXTRA_PORTAL_TYPE'];
		}

		if (isset($filter['STATUS']))
		{
			switch ($filter['STATUS'])
			{
				case 'client':
					$result['<STATUS'] = Session::STATUS_OPERATOR;
					break;

				case 'operator':
					$result['>=STATUS'] = Session::STATUS_OPERATOR;
					$result['<STATUS'] = Session::STATUS_CLOSE;
					break;

				case 'closed':
					$result['>=STATUS'] = Session::STATUS_CLOSE;
					break;
			}
		}

		if (isset($filter['STATUS_DETAIL']) && is_array($filter['STATUS_DETAIL']))
		{
			$result['=STATUS'] = $filter['STATUS_DETAIL'];
		}

		if (isset($filter['CRM']))
		{
			$result['=CRM'] = $filter['CRM'];
		}

		if (isset($filter['CRM_ENTITY']) && $filter['CRM_ENTITY'] != '')
		{
			$crmFilter = array();
			try
			{
				$crmFilter = \Bitrix\Main\Web\Json::decode($filter['CRM_ENTITY']);
			}
			catch (\Bitrix\Main\ArgumentException $e)
			{
			}

			if (count($crmFilter) == 1)
			{
				//TODO: improve search
				$entityTypes = array_keys($crmFilter);
				$entityType = $entityTypes[0];
				$entityId = $crmFilter[$entityType][0];
				//$result['=CRM_ENTITY_TYPE'] = $entityType;
				//$result['=CRM_ENTITY_ID'] = $entityId;
			}
		}

		if (isset($filter['SEND_FORM']))
		{
			if ($filter['SEND_FORM'] == 'Y')
			{
				$result['!=SEND_FORM'] = 'none';
			}
			else
			{
				$result['=SEND_FORM'] = 'none';
			}
		}

		$extractBoolean = function($fieldName, $typeCast = 'enum') use (&$filter, &$result)
		{
			if (isset($filter[$fieldName]))
			{
				$trueVal = $typeCast == 'enum' ? 'Y' : 1;
				if ($filter[$fieldName] == 'Y')
				{
					$result["={$fieldName}"] = $trueVal;
				}
				else if ($filter[$fieldName] == 'N')
				{
					$result["!={$fieldName}"] = $trueVal;
				}
			}
		};

		$extractBoolean('SEND_HISTORY', 'enum');
		$extractBoolean('SPAM', 'enum');

		$extractNumberRange = function($fieldName, $typeCast = 'intval') use (&$filter, &$result)
		{
			if (isset($filter["{$fieldName}_numsel"]))
			{
				if ($filter["{$fieldName}_numsel"] == 'range')
				{
					if ($typeCast($filter["{$fieldName}_from"]) > 0 && $typeCast($filter["{$fieldName}_to"]) == 0)
					{
						$filter["{$fieldName}_numsel"] = 'more';
						$filter["{$fieldName}_from"] = $typeCast($filter["{$fieldName}_from"]) - 1;
					}
					elseif ($typeCast($filter["{$fieldName}_from"]) == 0 && $typeCast($filter["{$fieldName}_to"]) > 0)
					{
						$filter["{$fieldName}_numsel"] = 'less';
						$filter["{$fieldName}_to"] = $typeCast($filter["{$fieldName}_to"]) + 1;
					}
					else
					{
						$result[">={$fieldName}"] = $typeCast($filter["{$fieldName}_from"]);
						$result["<={$fieldName}"] = $typeCast($filter["{$fieldName}_to"]);
					}
				}
				if ($filter["{$fieldName}_numsel"] == 'more')
				{
					$result[">{$fieldName}"] = $typeCast($filter["{$fieldName}_from"]);
				}
				elseif ($filter["{$fieldName}_numsel"] == 'less')
				{
					$result["<{$fieldName}"] = $typeCast($filter["{$fieldName}_to"]);
				}
				elseif ($filter["{$fieldName}_numsel"] != 'range')
				{
					$result["={$fieldName}"] = $typeCast($filter["{$fieldName}_from"]);
				}
			}
			elseif (isset($filter["{$fieldName}"]))
			{
				$result["={$fieldName}"] = $typeCast($filter["{$fieldName}"]);
			}
		};

		$extractNumberRange('MESSAGE_COUNT', 'intval');
		$extractNumberRange('EXTRA_REGISTER', 'intval');

		if (isset($filter['TYPE']))
		{
			$result['=MODE'] = $filter['TYPE'];
		}

		if (isset($filter['ID']))
		{
			$result['=ID'] = $filter['ID'];
		}

		if (isset($filter['WORKTIME']))
		{
			$result['=WORKTIME'] = $filter['WORKTIME'];
		}

		if (isset($filter['VOTE']))
		{
			$result['=VOTE'] = intval($filter['VOTE']);
		}

		if (isset($filter['VOTE_HEAD']) && is_array($filter['VOTE_HEAD']))
		{
			foreach ($filter['VOTE_HEAD'] as $key => $value)
			{
				if ($value == 'wo')
				{
					$filter['VOTE_HEAD'][$key] = 0;
				}
			}
			$result['=VOTE_HEAD'] = $filter['VOTE_HEAD'];
		}

		$minSearchToken = \Bitrix\Main\Config\Option::get('imopenlines', 'min_search_token');
		if (
			isset($filter['FIND'])
			&& (
				$minSearchToken <= 0
				|| \Bitrix\Main\Search\Content::isIntegerToken($filter['FIND'])
				|| mb_strlen($filter['FIND']) >= $minSearchToken
			)
			&& \Bitrix\Main\Search\Content::canUseFulltextSearch($filter['FIND'], \Bitrix\Main\Search\Content::TYPE_MIXED)
		)
		{
			global $DB;
			if (
				!\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT')
				&& $DB->IndexExists('b_imopenlines_session_index', array('SEARCH_CONTENT'), true)
			)
			{
				\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->enableFullTextIndex('SEARCH_CONTENT');
			}
			if (\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT'))
			{
				if (\Bitrix\Main\Search\Content::isIntegerToken($filter['FIND']))
				{
					$result['*INDEX.SEARCH_CONTENT'] = \Bitrix\Main\Search\Content::prepareIntegerToken($filter['FIND']);
				}
				else
				{
					$result['*INDEX.SEARCH_CONTENT'] = \Bitrix\Main\Search\Content::prepareStringToken($filter['FIND']);
				}
			}
		}

		// UF
		foreach ($ufFieldsList as $fieldName => $field)
		{
			if (
				$field['SHOW_FILTER'] != 'N'
				&& $field['USER_TYPE']['BASE_TYPE'] !== \CUserTypeManager::BASE_TYPE_FILE
			)
			{
				if ($field['USER_TYPE']['BASE_TYPE'] === \CUserTypeManager::BASE_TYPE_DATETIME)
				{
					$extractDateRange($fieldName);
				}
				elseif ($field['USER_TYPE']['USER_TYPE_ID'] === \Bitrix\Main\UserField\Types\BooleanType::USER_TYPE_ID)
				{
					$extractBoolean($fieldName, 'intval');
				}
				elseif ($field['USER_TYPE']['BASE_TYPE'] === \CUserTypeManager::BASE_TYPE_INT)
				{
					$extractNumberRange($fieldName, 'intval');
				}
				elseif ($field['USER_TYPE']['BASE_TYPE'] === \CUserTypeManager::BASE_TYPE_DOUBLE)
				{
					$extractNumberRange($fieldName, 'doubleval');
				}
				elseif (isset($filter[$fieldName]))
				{
					$result["={$fieldName}"] = $filter[$fieldName];
				}
			}
		}

		return $result;
	}

	public static function getFilterDefinition(array $ufFieldsList, int $configId, ?array $arResult)
	{
		\Bitrix\Main\Loader::includeModule('ImConnector');

		$filterFields = array(
			'CONFIG_ID' => array(
				'id' => 'CONFIG_ID',
				'name' => Loc::getMessage('OL_STATS_HEADER_CONFIG_NAME'),
				'type' => 'list',
				'items' => $arResult['LINES'] ?? Loc::getMessage('OL_SEARCH_OL'),
				'default' => !$configId,
				'default_value' => $configId ?: '',
				'params' => array(
					'multiple' => 'Y'
				)
			),

			'TYPE' => array(
				'id' => 'TYPE',
				'name' => Loc::getMessage('OL_STATS_HEADER_MODE_NAME'),
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'input' => Loc::getMessage('OL_COMPONENT_TABLE_INPUT'),
					'output' => Loc::getMessage('OL_COMPONENT_TABLE_OUTPUT'),
				),
				'default' => false,
			),
			'DATE_CREATE' => array(
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_CREATE'),
				'type' => 'date',
				'default' => true
			),
			'DATE_CLOSE' => array(
				'id' => 'DATE_CLOSE',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_CLOSE'),
				'type' => 'date',
				'default' => false
			),
			'OPERATOR_ID' => array(
				'id' => 'OPERATOR_ID',
				'name' => Loc::getMessage('OL_STATS_HEADER_OPERATOR_NAME'),
				'type' => 'dest_selector',
				'params' => array(
					'apiVersion' => '3',
					'context' => 'OL_STATS_FILTER_OPERATOR_ID',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableEmpty' => 'Y',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
					'allowBots' => 'Y'
				),
				'default' => true,
			),
			'CLIENT_NAME' => array(
				'id' => 'CLIENT_NAME',
				'name' => Loc::getMessage('OL_STATS_HEADER_USER_NAME'),
				'type' => 'string',
				'default' => false,
			),
			'SOURCE' => array(
				'id' => 'SOURCE',
				'name' => Loc::getMessage('OL_STATS_HEADER_SOURCE_TEXT_2'),
				'type' => 'list',
				'items' => \Bitrix\ImConnector\Connector::getListConnector(),
				'default' => true,
				'params' => array(
					'multiple' => 'Y'
				)
			),
			'ID' => array(
				'id' => 'ID',
				'name' => Loc::getMessage('OL_STATS_HEADER_SESSION_ID'),
				'type' => 'string',
				'default' => true
			),
			'EXTRA_URL' => array(
				'id' => 'EXTRA_URL',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_URL'),
				'type' => 'string',
				'default' => false
			),
			'STATUS' => array(
				'id' => 'STATUS',
				'name' => Loc::getMessage('OL_STATS_HEADER_STATUS'),
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'client' => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_NEW'),
					'operator' => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW'),
					'closed' => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLOSED'),
				),
				'default' => true,
			),
			'STATUS_DETAIL' => array(
				'id' => 'STATUS_DETAIL',
				'name' => Loc::getMessage('OL_STATS_HEADER_STATUS_DETAIL'),
				'type' => 'list',
				'items' => array(
					'' => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					(string)Session::STATUS_NEW => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_NEW'),
					(string)Session::STATUS_SKIP => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP_NEW'),
					(string)Session::STATUS_ANSWER => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER_NEW'),
					(string)Session::STATUS_CLIENT => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_NEW'),
					(string)Session::STATUS_CLIENT_AFTER_OPERATOR => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR_NEW'),
					(string)Session::STATUS_OPERATOR => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW'),
					(string)Session::STATUS_WAIT_CLIENT => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2'),
					(string)Session::STATUS_CLOSE => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLOSED'),
					(string)Session::STATUS_SPAM => Loc::getMessage('OL_STATS_HEADER_SPAM_2'),
				),
				'params' => array(
					'multiple' => 'Y'
				),
				'default' => false
			),
		);
		if (self::isFdcMode())
		{
			$filterFields['EXTRA_TARIFF'] = array(
				'id' => 'EXTRA_TARIFF',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_TARIFF'),
				'type' => 'string',
				'default' => false
			);
			$filterFields['EXTRA_USER_LEVEL'] = array(
				'id' => 'EXTRA_USER_LEVEL',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_USER_LEVEL'),
				'type' => 'string',
				'default' => false
			);
			$filterFields['EXTRA_PORTAL_TYPE'] = array(
				'id' => 'EXTRA_PORTAL_TYPE',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_PORTAL_TYPE'),
				'type' => 'string',
				'default' => false
			);
			$filterFields['EXTRA_REGISTER'] = array(
				'id' => 'EXTRA_REGISTER',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_REGISTER'),
				'default' => false,
				'type' => 'number'
			);
		}
		if (Loader::includeModule('crm'))
		{
			$filterFields['CRM'] = array(
				'id' => 'CRM',
				'name' => Loc::getMessage('OL_STATS_HEADER_CRM'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
					'N' => Loc::getMessage('OL_STATS_FILTER_N'),
				)
			);
			/*
			$filterFields['CRM_ENTITY'] = array(
				'id' => 'CRM_ENTITY',
				'name' => Loc::getMessage('OL_STATS_HEADER_CRM_TEXT'),
				'default' => false,
				'type' => 'custom_entity',
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'CRM_ENTITY',
						'FIELD_ID' => 'CRM_ENTITY',
						'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::LeadName, CCrmOwnerType::CompanyName, CCrmOwnerType::ContactName, CCrmOwnerType::DealName),
						'IS_MULTIPLE' => false
					)
				)
			);
			*/
		}

		$filterFields = array_merge($filterFields, array(
			'SEND_FORM' => array(
				'id' => 'SEND_FORM',
				'name' => Loc::getMessage('OL_STATS_HEADER_SEND_FORM'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
					'N' => Loc::getMessage('OL_STATS_FILTER_N'),
				)
			),
			'SEND_HISTORY' => array(
				'id' => 'SEND_HISTORY',
				'name' => Loc::getMessage('OL_STATS_HEADER_SEND_HISTORY'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
					'N' => Loc::getMessage('OL_STATS_FILTER_N'),
				)
			),
			'WORKTIME' => array(
				'id' => 'WORKTIME',
				'name' => Loc::getMessage('OL_STATS_HEADER_WORKTIME_TEXT'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
					'N' => Loc::getMessage('OL_STATS_FILTER_N'),
				)
			),
			'SPAM' => array(
				'id' => 'SPAM',
				'name' => Loc::getMessage('OL_STATS_HEADER_SPAM_2'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
					'N' => Loc::getMessage('OL_STATS_FILTER_N'),
				)
			),
			'MESSAGE_COUNT' => array(
				'id' => 'MESSAGE_COUNT',
				'name' => Loc::getMessage('OL_STATS_FILTER_MESSAGE_COUNT'),
				'default' => false,
				'type' => 'number'
			),
			'VOTE' => array(
				'id' => 'VOTE',
				'name' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
					'5' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT_LIKE'),
					'1' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT_DISLIKE'),
				)
			),
			'VOTE_HEAD' => array(
				'id' => 'VOTE_HEAD',
				'name' => Loc::getMessage('OL_STATS_HEADER_VOTE_HEAD_1'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					'wo' => Loc::getMessage('OL_STATS_HEADER_VOTE_HEAD_WO'),
					'5' => Session::VOTE_LIKE,
					'4' => 4,
					'3' => 3,
					'2' => 2,
					'1' => Session::VOTE_DISLIKE,
				),
				'params' => array(
					'multiple' => 'Y'
				)
			),
		));

		// UF
		foreach ($ufFieldsList as $fieldName => $field)
		{
			if (
				$field['SHOW_FILTER'] != 'N'
				&& $field['USER_TYPE']['BASE_TYPE'] != \CUserTypeManager::BASE_TYPE_FILE
			)
			{
				$fieldClass = $field['USER_TYPE']['CLASS_NAME'];
				if (
					is_a($fieldClass, \Bitrix\Main\UserField\Types\BaseType::class, true)
					&& is_callable([$fieldClass, 'getFilterData'])
				)
				{
					$filterFields[$fieldName] = $fieldClass::getFilterData(
						$field,
						[
							'ID' => $fieldName,
							'NAME' => $field['LIST_FILTER_LABEL'] ?: $field['FIELD_NAME'],
						]
					);
				}
			}
		}

		return $filterFields;
	}

	public static function getUfFieldList(): array
	{
		$ufFields = [];

		$ufData = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => SessionTable::getUfId(), 'LANG' => LANGUAGE_ID));
		while($field = $ufData->Fetch())
		{
			$field['USER_TYPE'] = self::getUfTypeManager()->getUserType($field['USER_TYPE_ID']);

			$ufFields[$field['FIELD_NAME']] = $field;
		}

		return $ufFields;
	}

	/**
	 * @return bool
	 */
	public static function isFdcMode(): bool
	{
		return defined('IMOL_FDC');
	}

	public static function getUfTypeManager()
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER;
	}
}
