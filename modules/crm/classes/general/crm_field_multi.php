<?php

use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Crm\Integration\UI\EntitySelector\CountryProvider;
use Bitrix\Crm\Format\PhoneNumberParser;
use Bitrix\Crm\Model\FieldMultiPhoneCountryTable;
use Bitrix\Crm\Multifield;
use Bitrix\Main;

if (!defined('CACHED_b_field_multi')) define('CACHED_b_field_multi', 360000);

IncludeModuleLangFile(__FILE__);

class CCrmFieldMulti
{
	protected $cdb = null;
	public $LAST_ERROR = '';
	private static $FIELDS = null;
	private static $ENTITY_TYPES = null;
	private static $ENTITY_TYPE_INFOS = null;

	private static $allowedCountryCodes = null;

	const PHONE = Multifield\Type\Phone::ID;
	const EMAIL = Multifield\Type\Email::ID;
	const WEB = Multifield\Type\Web::ID;
	const IM = Multifield\Type\Im::ID;
	const LINK = Multifield\Type\Link::ID;

	function __construct()
	{
		global $DB;

		$this->cdb = $DB;
	}

	public static function IsSupportedType($typeID)
	{
		return $typeID === self::PHONE
			|| $typeID === self::EMAIL
			|| $typeID === self::WEB
			|| $typeID === self::IM;
	}

	public static function PrepareFieldsInfo(array &$fieldsInfo)
	{
		$typeInfos = self::GetEntityTypeInfos();
		foreach($typeInfos as $typeID => $typeInfo)
		{
			$fieldsInfo[$typeID] = array(
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
	}

	public static function GetEntityTypeInfos()
	{
		if(self::$ENTITY_TYPE_INFOS === null)
		{
			self::$ENTITY_TYPE_INFOS = [
				'PHONE' => ['NAME' => GetMessage('CRM_FM_ENTITY_PHONE')],
				'EMAIL' => ['NAME' => GetMessage('CRM_FM_ENTITY_EMAIL')],
				'WEB' => ['NAME' => GetMessage('CRM_FM_ENTITY_WEB')],
				'IM' => ['NAME' => GetMessage('CRM_FM_ENTITY_IM')],
				'LINK' => ['NAME' => GetMessage('CRM_FM_ENTITY_LINK')],
			];
		}
		return self::$ENTITY_TYPE_INFOS;
	}
	public static function GetEntityTypeCaption($typeID)
	{
		$infos = self::GetEntityTypeInfos();
		return isset($infos[$typeID]['NAME']) ? $infos[$typeID]['NAME'] : $typeID;
	}
	public static function GetEntityTypes()
	{
		if(self::$ENTITY_TYPES === null)
		{
			self::$ENTITY_TYPES = Array(
				Multifield\Type\Phone::ID => [
					Multifield\Type\Phone::VALUE_TYPE_WORK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_WORK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_WORK_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_WORK_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
					Multifield\Type\Phone::VALUE_TYPE_MOBILE => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_MOBILE'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_MOBILE_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_MOBILE_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
					Multifield\Type\Phone::VALUE_TYPE_FAX => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_FAX'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_FAX_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_FAX_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
					Multifield\Type\Phone::VALUE_TYPE_HOME => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_HOME'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_HOME_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_HOME_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
					Multifield\Type\Phone::VALUE_TYPE_PAGER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_PAGER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_PAGER_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_PAGER_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
					Multifield\Type\Phone::VALUE_TYPE_MAILING => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_MAILING'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_MAILING_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_MAILING_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
					Multifield\Type\Phone::VALUE_TYPE_OTHER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_PHONE_OTHER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_OTHER_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_OTHER_ABBR'),
						'TEMPLATE' => '<a href="' . CCrmCallToUrl::Format('#VALUE#') . '">#VALUE_HTML#</a>',
					],
				],
				Multifield\Type\Web::ID => [
					Multifield\Type\Web::VALUE_TYPE_WORK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_WORK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_WORK_SHORT'),
						'TEMPLATE' => '<a href="https://#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://#VALUE_URL#',
					],
					Multifield\Type\Web::VALUE_TYPE_HOME => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_HOME'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_HOME_SHORT'),
						'TEMPLATE' => '<a href="https://#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://#VALUE_URL#',
					],
					Multifield\Type\Web::VALUE_TYPE_FACEBOOK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_FACEBOOK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_FACEBOOK_SHORT'),
						'TEMPLATE' => '<a href="https://www.facebook.com/#VALUE_URL#/" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://www.facebook.com/#VALUE_URL#/',
					],
					Multifield\Type\Web::VALUE_TYPE_VK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_VK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_VK_SHORT'),
						'TEMPLATE' => '<a href="https://vk.com/#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://vk.com/#VALUE_URL#',
					],
					Multifield\Type\Web::VALUE_TYPE_LIVEJOURNAL => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_LIVEJOURNAL'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_LIVEJOURNAL_SHORT'),
						'TEMPLATE' => '<a href="https://#VALUE_URL#.livejournal.com/" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://#VALUE_URL#.livejournal.com/',
					],
					Multifield\Type\Web::VALUE_TYPE_TWITTER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_TWITTER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_TWITTER_SHORT'),
						'TEMPLATE' => '<a href="https://twitter.com/#VALUE_URL#/" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://twitter.com/#VALUE_URL#/',
					],
					Multifield\Type\Web::VALUE_TYPE_OTHER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_WEB_OTHER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_OTHER_SHORT'),
						'TEMPLATE' => '<a href="https://#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://#VALUE_URL#',
					],
				],
				Multifield\Type\Email::ID => [
					Multifield\Type\Email::VALUE_TYPE_WORK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_EMAIL_WORK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_WORK_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_WORK_ABBR'),
						'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>',
					],
					Multifield\Type\Email::VALUE_TYPE_HOME => [
						'FULL' => GetMessage('CRM_FM_ENTITY_EMAIL_HOME'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_HOME_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_HOME_ABBR'),
						'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>',
					],
					Multifield\Type\Email::VALUE_TYPE_MAILING => [
						'FULL' => GetMessage('CRM_FM_ENTITY_EMAIL_MAILING1'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_MAILING_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_MAILING_ABBR'),
						'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>',
					],
					Multifield\Type\Email::VALUE_TYPE_OTHER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_EMAIL_OTHER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_OTHER_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_OTHER_ABBR'),
						'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>',
					],
				],
				Multifield\Type\Im::ID => [
					Multifield\Type\Im::VALUE_TYPE_FACEBOOK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_FACEBOOK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_FACEBOOK_SHORT'),
						'TEMPLATE' => '<a href="https://m.me/#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://m.me/#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_TELEGRAM => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_TELEGRAM'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_TELEGRAM_SHORT'),
						'TEMPLATE' => '<a href="https://t.me/#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://t.me/#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_VK => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_VK'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_VK_SHORT'),
						'TEMPLATE' => '<a href="https://vk.com/#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://vk.com/#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_SKYPE => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_SKYPE'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_SKYPE_SHORT'),
						'TEMPLATE' => '<a href="skype:#VALUE_URL#?chat">#VALUE_HTML#</a>',
						'LINK' => 'skype:#VALUE_URL#?chat',
					],
					Multifield\Type\Im::VALUE_TYPE_VIBER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_VIBER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_VIBER_SHORT'),
						'TEMPLATE' => '<a href="viber://chat?number=#VALUE_URL#" target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'viber://chat?number=#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_INSTAGRAM => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_INSTAGRAM'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_INSTAGRAM_SHORT'),
						'TEMPLATE' => '<a href="https://www.instagram.com/#VALUE_URL#"'
							. ' target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://www.instagram.com/#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_BITRIX24 => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_BITRIX24'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_BITRIX24_SHORT'),
						'TEMPLATE' => '#VALUE_HTML#',
						'LINK' => '#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_OPENLINE => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_WIDGET'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_WIDGET'),
						'TEMPLATE' => '#VALUE_HTML#',
						'LINK' => '#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_IMOL => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_OPENLINE'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_OPENLINE_SHORT'),
						'TEMPLATE' => '#VALUE_HTML#',
						'LINK' => '#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_ICQ => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_ICQ'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_ICQ_SHORT'),
						'TEMPLATE' => '<a href="https://www.icq.com/people/#VALUE_URL#/"'
							. ' target="_blank">#VALUE_HTML#</a>',
						'LINK' => 'https://www.icq.com/people/#VALUE_URL#/',
					],
					Multifield\Type\Im::VALUE_TYPE_MSN => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_MSN'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_MSN_SHORT'),
						'TEMPLATE' => '<a href="msn:#VALUE_URL#">#VALUE_HTML#</a>',
						'LINK' => 'msn:#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_JABBER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_JABBER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_JABBER_SHORT'),
						'TEMPLATE' => '#VALUE_HTML#',
						'LINK' => '#VALUE_URL#',
					],
					Multifield\Type\Im::VALUE_TYPE_OTHER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_IM_OTHER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_IM_OTHER_SHORT'),
						'TEMPLATE' => '#VALUE_HTML#',
						'LINK' => '#VALUE_URL#',
					],
				],
				Multifield\Type\Link::ID => [
					Multifield\Type\Link::VALUE_TYPE_USER => [
						'FULL' => GetMessage('CRM_FM_ENTITY_LINK_USER'),
						'SHORT' => GetMessage('CRM_FM_ENTITY_LINK_USER_SHORT'),
						'ABBR' => GetMessage('CRM_FM_ENTITY_LINK_USER_ABBR'),
						'TEMPLATE' => '#VALUE_HTML#',
					],
				],
			);
		}
		return self::$ENTITY_TYPES;
	}

	public static function GetDefaultValueType($entityTypeID)
	{
		return $entityTypeID !== 'IM' ? 'WORK' : 'OTHER';
	}

	public function Add($arFields, array $options = null)
	{
		$err_mess = (self::err_mess()).'<br />Function: Add<br>Line: ';

		if(isset($arFields['VALUE']))
		{
			$arFields['VALUE'] = trim($arFields['VALUE']);
		}

		if (!$this->CheckFields($arFields))
			return false;

		$arFields_i = Array(
			'ENTITY_ID'	=> $arFields['ENTITY_ID'],
			'ELEMENT_ID'=> intval($arFields['ELEMENT_ID']),
			'TYPE_ID'	=> $arFields['TYPE_ID'],
			'VALUE_TYPE'=> $arFields['VALUE_TYPE'],
			'COMPLEX_ID'=> $arFields['TYPE_ID'].'_'.$arFields['VALUE_TYPE'],
			'VALUE'		=> $arFields['VALUE'],
		);
		$ID = $this->cdb->Add('b_crm_field_multi', $arFields_i);

		if(is_array($options) && (!isset($options['ENABLE_NOTIFICATION']) || $options['ENABLE_NOTIFICATION']))
		{
			$entityTypeId = CCrmOwnerType::ResolveID($arFields_i['ENTITY_ID']);

			//region Register volatile duplicate criterion fields
			DuplicateCommunicationCriterion::processMultifieldsChange($entityTypeId, $arFields_i['ELEMENT_ID']);
			DuplicateVolatileCriterion::register(
				$entityTypeId,
				$arFields_i['ELEMENT_ID'],
				[FieldCategory::MULTI]
			);
			//endregion Register volatile duplicate criterion fields
		}

		$valueCountryCode = static::fetchCountryCode($arFields_i['TYPE_ID'], $arFields);
		if ($arFields_i['TYPE_ID'] === static::PHONE && !empty($valueCountryCode))
		{
			FieldMultiPhoneCountryTable::add([
				'FM_ID' => $ID,
				'COUNTRY_CODE' => $valueCountryCode,
			]);
		}

		return $ID;
	}

	public function Update($ID, $arFields, array $options = null)
	{
		$err_mess = (self::err_mess()).'<br />Function: Update<br>Line: ';

		$ID = (int)$ID;
		if($ID <= 0)
		{
			return false;
		}

		if(isset($arFields['VALUE']))
		{
			$arFields['VALUE'] = trim($arFields['VALUE']);
		}

		if (!$this->CheckFields($arFields))
			return false;

		$arFields_u = Array(
			'TYPE_ID'	=> $arFields['TYPE_ID'],
			'VALUE_TYPE'=> $arFields['VALUE_TYPE'],
			'COMPLEX_ID'=> $arFields['TYPE_ID'].'_'.$arFields['VALUE_TYPE'],
			'VALUE'		=> $arFields['VALUE'],
		);
		$strUpdate = $this->cdb->PrepareUpdate('b_crm_field_multi', $arFields_u);
		if (!$this->cdb->Query("UPDATE b_crm_field_multi SET $strUpdate WHERE ID=$ID", false, $err_mess.__LINE__))
			return false;

		if(is_array($options) && (!isset($options['ENABLE_NOTIFICATION']) || $options['ENABLE_NOTIFICATION']))
		{
			$info = $this->GetOwerInfo($ID);
			if(is_array($info) && isset($info['ENTITY_ID']) && isset($info['ELEMENT_ID']))
			{
				$entityTypeId = CCrmOwnerType::ResolveID($info['ENTITY_ID']);
				$entityId = (int)$info['ELEMENT_ID'];

				//region Register volatile duplicate criterion fields
				DuplicateCommunicationCriterion::processMultifieldsChange($entityTypeId, $entityId);
				DuplicateVolatileCriterion::register(
					$entityTypeId,
					$entityId,
					[FieldCategory::MULTI]
				);
				//endregion Register volatile duplicate criterion fields
			}
		}

		if ($arFields_u['TYPE_ID'] === static::PHONE)
		{
			$curData = FieldMultiPhoneCountryTable::getDataByMultiFieldId([$ID])[0] ?? [];
			$valueCountryCode = static::fetchCountryCode($arFields_u['TYPE_ID'], $arFields);
			if (empty($valueCountryCode))
			{
				if (isset($curData['ID']))
				{
					FieldMultiPhoneCountryTable::delete($curData['ID']);
				}
			}
			else
			{
				if (isset($curData['ID']))
				{
					FieldMultiPhoneCountryTable::update($curData['ID'], ['COUNTRY_CODE' => $valueCountryCode]);
				}
				else
				{
					FieldMultiPhoneCountryTable::add([
						'FM_ID' => $ID,
						'COUNTRY_CODE' => $valueCountryCode,
					]);
				}
			}
		}

		return $ID;
	}

	public function Delete($ID, array $options = null)
	{
		$err_mess = (self::err_mess()).'<br />Function: Delete<br>Line: ';

		$ID = (int)$ID;
		if($ID <= 0)
		{
			return false;
		}

		$info = null;
		if(is_array($options) && (!isset($options['ENABLE_NOTIFICATION']) || $options['ENABLE_NOTIFICATION']))
		{
			$info = $this->GetOwerInfo($ID);
		}

		$result = $this->cdb->Query("DELETE FROM b_crm_field_multi WHERE ID={$ID}", false, $err_mess.__LINE__);
		if(is_array($info) && isset($info['ENTITY_ID']) && isset($info['ELEMENT_ID']))
		{
			$entityTypeId = CCrmOwnerType::ResolveID($info['ENTITY_ID']);
			$entityId = (int)$info['ELEMENT_ID'];

			//region Register volatile duplicate criterion fields
			DuplicateCommunicationCriterion::processMultifieldsChange($entityTypeId, $entityId);
			DuplicateVolatileCriterion::register($entityTypeId, $entityId, [FieldCategory::MULTI]);
			//endregion Register volatile duplicate criterion fields
		}

		FieldMultiPhoneCountryTable::deleteByByMultiFieldId($ID);

		return $result;
	}

	public function DeleteByElement($entityId, $elementId)
	{
		$err_mess = (self::err_mess()).'<br>Function: DeleteByElement<br>Line: ';

		$elementId = intval($elementId);

		if ($entityId == '' || $elementId == 0)
		{
			return false;
		}

		$idsToRemove = [];
		$dbResult = $this->cdb->Query(
			"SELECT ID FROM b_crm_field_multi WHERE ENTITY_ID='" . $this->cdb->ForSql($entityId) . "' AND ELEMENT_ID=" . $elementId,
			false,
			$err_mess . __LINE__
		);
		while ($row = $dbResult->Fetch())
		{
			$idsToRemove[] = (int)$row['ID'];
		}

		$res = $this->cdb->Query(
			"DELETE FROM b_crm_field_multi "
			. "WHERE ENTITY_ID = '" . $this->cdb->ForSql($entityId) . "' AND ELEMENT_ID = '" . $elementId . "'",
			false,
			$err_mess . __LINE__
		);

		$entityTypeId = CCrmOwnerType::ResolveID($entityId);

		//region Register volatile duplicate criterion fields
		DuplicateCommunicationCriterion::processMultifieldsChange($entityTypeId, $elementId);
		DuplicateVolatileCriterion::register($entityTypeId, $elementId, [FieldCategory::MULTI]);
		//endregion Register volatile duplicate criterion fields

		if (!empty($idsToRemove))
		{
			foreach ($idsToRemove as $id)
			{
				FieldMultiPhoneCountryTable::deleteByByMultiFieldId($id);
			}
		}

		return $res;
	}

	protected function GetOwerInfo($ID)
	{
		$result = null;

		$err_mess = (self::err_mess()).'<br>Function: GetOwerInfo<br>Line: ';

		$dbResult = $this->cdb->Query(
			"SELECT ENTITY_ID, ELEMENT_ID FROM b_crm_field_multi WHERE ID={$ID}",
			false,
			$err_mess . __LINE__
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(is_array($fields))
		{
			$result = array();
			if(isset($fields['ENTITY_ID']))
			{
				$result['ENTITY_ID'] = $fields['ENTITY_ID'];
			}

			if(isset($fields['ELEMENT_ID']))
			{
				$result['ELEMENT_ID'] = (int)$fields['ELEMENT_ID'];
			}
		}
		return $result;
	}

	public static function GetTotals($entityId, $elementId)
	{
		$dbResult = self::GetListEx(
			array(),
			array('ENTITY_ID' => $entityId, 'ELEMENT_ID' => $elementId),
			array('TYPE_ID')
		);

		$results = array();
		while($fields = $dbResult->Fetch())
		{
			$results[$fields['TYPE_ID']] = (int)$fields['CNT'];
		}
		return $results;
	}

	public static function GetPhoneCountryList(array $multiFieldIds): array
	{
		if (empty($multiFieldIds))
		{
			return [];
		}

		$result = FieldMultiPhoneCountryTable::getDataByMultiFieldId($multiFieldIds);
		if (empty($result))
		{
			return [];
		}

		return array_column($result, 'COUNTRY_CODE', 'FM_ID');
	}

	public static function HasValues(array $arFieldData, $typeId)
	{
		if(!(isset($arFieldData[$typeId]) && is_array($arFieldData[$typeId])))
		{
			return false;
		}

		$arValues = $arFieldData[$typeId];
		foreach($arValues as $id => $arValue)
		{
			$value = isset($arValue['VALUE']) ? trim((string)$arValue['VALUE']) : '';
			if($value !== '')
			{
				return true;
			}
		}
		return false;
	}

	public function SetFields($entityId, $elementId, $arFieldData, array $options = null)
	{
		if (!is_array($arFieldData))
		{
			return false;
		}

		$arPreviousFieldData = array();
		$dbResult = self::GetListEx(array('ID' => 'asc'), array('ENTITY_ID' => $entityId, 'ELEMENT_ID' => $elementId));

		while($arPreviousField = $dbResult->Fetch())
		{
			$typeId = $arPreviousField['TYPE_ID'];
			if(!isset($arPreviousFieldData[$typeId]))
			{
				$arPreviousFieldData[$typeId] = array();
			}

			$arPreviousFieldData[$typeId][$arPreviousField['ID']] = $arPreviousField;
		}

		$addItems = array();
		$removeItems = array();
		$updateItems = array();

		foreach($arPreviousFieldData as $typeId => $previousItems)
		{
			$currentItems = isset($arFieldData[$typeId]) ? $arFieldData[$typeId] : array();
			foreach($previousItems as $id => $previousItem)
			{
				if(!isset($currentItems[$id]))
				{
					continue;
				}

				$currentItem = $currentItems[$id];

				$currentValue = isset($currentItem['VALUE']) ? trim($currentItem['VALUE']) : '';
				$currentValueType = isset($currentItem['VALUE_TYPE']) ? trim($currentItem['VALUE_TYPE']) : '';

				$previousValue = isset($previousItem['VALUE']) ? trim($previousItem['VALUE']) : '';
				$previousValueType = isset($previousItem['VALUE_TYPE']) ? trim($previousItem['VALUE_TYPE']) : '';

				if($currentValue === '')
				{
					$removeItems[$id] = true;
				}
				elseif($previousValue !== $currentValue || $previousValueType !== $currentValueType)
				{
					$updateItems[$id] = array(
						'TYPE_ID' => $typeId,
						'VALUE_TYPE' => $currentValueType,
						'VALUE' => $currentValue,
						'VALUE_COUNTRY_CODE' => static::fetchCountryCode($typeId, $currentItem),
					);
				}
			}
		}

		foreach($arFieldData as $typeId => $arValues)
		{
			foreach($arValues as $id => $arValue)
			{
				$currentValue = isset($arValue['VALUE']) ? trim($arValue['VALUE']) : '';
				$currentValueType = isset($arValue['VALUE_TYPE']) ? trim($arValue['VALUE_TYPE']) : '';

				if(mb_substr($id, 0, 1) === 'n' && $currentValue !== '')
				{
					$addItems[] = array(
						'ENTITY_ID' => $entityId,
						'ELEMENT_ID' => $elementId,
						'TYPE_ID' => $typeId,
						'VALUE_TYPE' => $currentValueType,
						'VALUE' => $currentValue,
						'VALUE_COUNTRY_CODE' => static::fetchCountryCode($typeId, $arValue),
					);
				}
			}
		}

		$isChanged = false;
		if(!empty($addItems))
		{
			foreach($addItems as $item)
			{
				$this->Add($item, array('ENABLE_NOTIFICATION' => false));
			}
			$isChanged = true;
		}

		if(!empty($removeItems))
		{
			foreach(array_keys($removeItems) as $id)
			{
				$this->Delete($id, array('ENABLE_NOTIFICATION' => false));
			}
			$isChanged = true;
		}

		if(!empty($updateItems))
		{
			foreach($updateItems as $id => $item)
			{
				$this->Update($id, $item, array('ENABLE_NOTIFICATION' => false));
			}
			$isChanged = true;
		}

		if ($isChanged)
		{
			$entityTypeId = CCrmOwnerType::ResolveID($entityId);

			//region Register volatile duplicate criterion fields
			DuplicateCommunicationCriterion::processMultifieldsChange($entityTypeId, $elementId);
			DuplicateVolatileCriterion::register(
				$entityTypeId,
				(int)$elementId,
				[FieldCategory::MULTI]
			);
			//endregion Register volatile duplicate criterion fields
		}

		return true;
	}

	public static function GetList($arSort=array(), $arFilter=array())
	{
		global $DB;

		$arSqlSearch = array();
		$err_mess = (self::err_mess()).'<br />Function: GetList<br>Line: ';
		if (is_array($arFilter))
		{
			self::PrepareSearchQuery($arFilter, $arSqlSearch);
		}

		$sOrder = '';
		foreach ($arSort as $key=>$val)
		{
			$ord = (mb_strtoupper($val) <> 'ASC' ? 'DESC' : 'ASC');
			switch(mb_strtoupper($key))
			{
				case 'ID':
					$sOrder .= ', CFM.ID '.$ord;
					break;
				case 'ENTITY_ID':
					$sOrder .= ', CFM.ENTITY_ID '.$ord;
					break;
				case 'ELEMENT_ID':
					$sOrder .= ', CFM.ELEMENT_ID '.$ord;
					break;
				case 'TYPE_ID':
					$sOrder .= ', CFM.TYPE_ID '.$ord;
					break;
				case 'VALUE_TYPE':
					$sOrder .= ', CFM.VALUE_TYPE '.$ord;
					break;
				case 'COMPLEX_ID':
					$sOrder .= ', CFM.COMPLEX_ID '.$ord;
					break;
				case 'VALUE':
					$sOrder .= ', CFM.VALUE '.$ord;
					break;
			}
		}

		if ($sOrder == '')
			$sOrder = 'CFM.ID DESC';

		$strSqlOrder = ' ORDER BY '.TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				CFM.ID, CFM.ENTITY_ID, CFM.ELEMENT_ID, CFM.TYPE_ID, CFM.VALUE_TYPE, CFM.COMPLEX_ID, CFM.VALUE
			FROM
				b_crm_field_multi CFM
			WHERE
			$strSqlSearch
			$strSqlOrder";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			'',
			'b_crm_field_multi',
			'CFM',
			self::GetFields(),
			'',
			'',
			null,
			null
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function GetFields()
	{
		if(self::$FIELDS === null)
		{
			self::$FIELDS = array(
				'ID' => array('FIELD' => 'CFM.ID', 'TYPE' => 'int'),
				'ENTITY_ID' => array('FIELD' => 'CFM.ENTITY_ID', 'TYPE' => 'string'),
				'ELEMENT_ID' => array('FIELD' => 'CFM.ELEMENT_ID', 'TYPE' => 'int'),
				'TYPE_ID' => array('FIELD' => 'CFM.TYPE_ID', 'TYPE' => 'string'),
				'VALUE_TYPE' => array('FIELD' => 'CFM.VALUE_TYPE', 'TYPE' => 'string'),
				'COMPLEX_ID' => array('FIELD' => 'CFM.COMPLEX_ID', 'TYPE' => 'string'),
				'VALUE' => array('FIELD' => 'CFM.VALUE', 'TYPE' => 'string')
			);
		}
		return self::$FIELDS;
	}

	public static function PrepareExternalFilter(&$filter, $params = array())
	{
		if(!isset($filter['FM']) || empty($filter['FM']) || !is_array($params) || empty($params))
		{
			return;
		}

		$entityID = isset($params['ENTITY_ID']) ? $params['ENTITY_ID'] : '';

		$masterAlias = isset($params['MASTER_ALIAS']) ? $params['MASTER_ALIAS'] : '';
		if($masterAlias === '')
		{
			$masterAlias = 'L';
		}

		$masterIdentity = isset($params['MASTER_IDENTITY']) ? $params['MASTER_IDENTITY'] : '';
		if($masterIdentity === '')
		{
			$masterIdentity = 'ID';
		}

		$fields = self::GetFields();
		$joins = array();
		$c = 0;
		foreach($filter['FM'] as $filterPart)
		{
			if($entityID !== '')
			{
				$filterPart['ENTITY_ID'] = $entityID;
			}

			$c++;
			$alias = "CFM{$c}";
			$where = CSqlUtil::PrepareWhere($fields, $filterPart, $joins);
			$joins[] = array(
				'TYPE' => 'INNER',
				'SQL' => "INNER JOIN (SELECT DISTINCT CFM.ELEMENT_ID FROM b_crm_field_multi CFM WHERE {$where}) {$alias} ON {$masterAlias}.{$masterIdentity} = {$alias}.ELEMENT_ID"
			);
		}

		if(!empty($joins))
		{
			if(!isset($filter['__JOINS']))
			{
				$filter['__JOINS'] = $joins;
			}
			else
			{
				$filter['__JOINS'] = array_merge($filter['__JOINS'], $joins);
			}
		}
	}

	private static function PrepareSearchQuery(&$arFilter, &$arSqlSearch)
	{
		global $DB;

		$filter_keys = array_keys($arFilter);
		for ($i=0, $ic=count($filter_keys); $i < $ic; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];

			if (!is_array($val) && ((string)$val == '' || (string)$val == "NOT_REF"))
			{
				continue;
			}

			$key = strtoupper($filter_keys[$i]);
			$operationInfo = CSqlUtil::GetFilterOperation($key);
			$operation = $operationInfo['OPERATION'];
			// Process only like operation
			$isLikeOperation = $operation === 'LIKE' ? 'Y' : 'N';
			$fieldName = $operationInfo['FIELD'];

			switch($fieldName)
			{
				case 'ID':
					$arSqlSearch[] = GetFilterQuery('CFM.ID', $val, 'N');
					break;
				case 'ENTITY_ID':
					if ($operation !== '=' || is_array($val))
					{
						$arSqlSearch[] = GetFilterQuery('CFM.ENTITY_ID', $val, $isLikeOperation);
					}
					else
					{
						$arSqlSearch[] = 'CFM.ENTITY_ID = "' . $DB->ForSql((string)$val) . '"';
					}

					break;
				case 'ELEMENT_ID':
					if (is_array($val))
					{
						$ar = array();
						foreach($val as $v)
							$ar[] = intval($v);
						if (!empty($ar))
							$arSqlSearch[] = 'CFM.ELEMENT_ID IN ('.implode(',', $ar).')';
					}
					else
						$arSqlSearch[] = 'CFM.ELEMENT_ID = '.intval($val);
					break;
				case 'TYPE_ID':
					$arSqlSearch[] = GetFilterQuery('CFM.TYPE_ID', $val, $isLikeOperation);
					break;
				case 'VALUE_TYPE':
					if (is_array($val))
					{
						$valueTypeFilter = '';
						foreach($val as $v)
						{
							$v = $DB->ForSql(trim(strval($v)));
							if($v === '')
							{
								continue;
							}

							if($valueTypeFilter !== '')
							{
								$valueTypeFilter .= ', ';
							}

							$valueTypeFilter .= "'{$v}'";
						}

						if ($valueTypeFilter !== '')
						{
							$arSqlSearch[] = "CFM.VALUE_TYPE IN ({$valueTypeFilter})";
						}
					}
					else
						$arSqlSearch[] = GetFilterQuery('CFM.VALUE_TYPE', $val, $isLikeOperation);
					break;
				case 'COMPLEX_ID':
					$arSqlSearch[] = GetFilterQuery('CFM.COMPLEX_ID', $val, $isLikeOperation);
					break;
				case 'VALUE':
					$arSqlSearch[] = GetFilterQuery('CFM.VALUE', $val, $isLikeOperation);
					break;
				case 'RAW_VALUE':
					$arSqlSearch[] = "CFM.VALUE = '".$DB->ForSql($val)."'";
					break;
				case 'FILTER':
				{
					$arSqlFilterSearch = array();
					if(is_array($val))
					{
						// Processing of filter parts
						foreach($val as $v)
						{
							// Prepering filter part - items are joined by 'AND'
							$arSqlInnerSearch = array();
							self::PrepareSearchQuery($v, $arSqlInnerSearch);
							if(!empty($arSqlInnerSearch))
							{
								$arSqlFilterSearch[] = '('.implode(' AND ', $arSqlInnerSearch).')';
							}
						}
					}
					if (!empty($arSqlFilterSearch))
					{
						//$logic = isset($arFilter['LOGIC']) && is_string($arFilter['LOGIC']) ? strtoupper($arFilter['LOGIC']) : '';
						//$logic = '';
						//if($logic === '')
						//{
						//	$logic = 'OR';
						//}

						// Prepering filter - parts are joined by 'OR'
						//$arSqlSearch[] = '('.implode(" {$logic} ", $arSqlFilterSearch).')';
						$arSqlSearch[] = '('.implode(" OR ", $arSqlFilterSearch).')';
					}
				}
				break;
			}
		}
	}

	public static function PrepareFields(&$arFields)
	{
		$i = 1;
		$arList = Array();

		$arEntityType = self::GetEntityTypes();

		foreach($arEntityType as $entityId => $ar)
		{
			foreach($ar as $valueType => $arValue)
			{
				$key = "{$entityId}_{$valueType}";
				if(!isset($arFields[$key]))
				{
					continue;
				}

				$arData = explode(';', $arFields[$key]);
				if (
					in_array($entityId, ['EMAIL', 'PHONE','WEB'])
					&&
					count($arData) == 1
				)
				{
					$arData = explode(',', $arFields[$key]);
					if ($entityId == 'EMAIL' && count($arData) == 1)
					{
						$arData = explode(' ', $arFields[$key]);
					}
				}
				foreach($arData as $data)
				{
					if (!empty($data))
					{
						//trim all spaces (include non breaking space issue #0092669)
						$arList[$entityId]['n'.$i]['VALUE'] = preg_replace("/(^\s+)|(\s+$)/", "", $data);
						$arList[$entityId]['n'.$i]['VALUE_TYPE'] = $valueType;
						$i++;
					}
				}
				unset($arFields[$entityId.'_'.$valueType]);
			}
		}

		if (!empty($arList))
			$arFields['FM'] = $arList;

		return $arList;
	}

	public static function ParseComplexName($complexName, $enableCheck = true)
	{
		$ary = explode('_', $complexName);
		if(count($ary) !== 2)
		{
			array();
		}

		if(!$enableCheck)
		{
			return array('TYPE' => $ary[0], 'VALUE_TYPE' => $ary[1]);
		}

		$type = $ary[0];
		$valueType = $ary[1];
		$entityTypes = self::GetEntityTypes();
		return isset($entityTypes[$type]) && isset($entityTypes[$type][$valueType])
			? array('TYPE' => $type, 'VALUE_TYPE' => $valueType) : array();
	}

	public static function GetEntityTypeList($entityType = '', $bFullName = true)
	{
		$arList = Array();
		static $arEntityType = array();

		$nameType = $bFullName? 'FULL': 'SHORT';
		$arEntityType[$nameType] = array();
		if (empty($arEntityType[$nameType]))
			$arEntityType[$nameType] = self::GetEntityTypes();

		if ($entityType == '')
			foreach($arEntityType[$nameType] as $entity => $ar)
				foreach($ar as $type => $ar)
					$arList[$entity][$type] = $ar[$nameType];
		elseif (isset($arEntityType[$nameType][$entityType]))
			foreach($arEntityType[$nameType][$entityType] as $type => $ar)
				$arList[$type] = $ar[$nameType];

		return $arList;
	}

	public static function GetEntityComplexList($entityType = '', $bFullName = true)
	{
		$arList = Array();
		static $arEntityType = array();

		$nameType = $bFullName? 'FULL': 'SHORT';
		if (empty($arEntityType[$nameType]))
			$arEntityType[$nameType] = self::GetEntityTypes();

		if ($entityType == '')
			foreach($arEntityType[$nameType] as $entity => $ar)
				foreach($ar as $type => $ar)
					$arList[$entity.'_'.$type] = $ar[$nameType];
		elseif (isset($arEntityType[$nameType][$entityType]))
			foreach($arEntityType[$nameType][$entityType] as $type => $ar)
				$arList[$entityType.'_'.$type] = $ar[$nameType];

		return $arList;
	}

	public static function GetEntityName($typeID, $valueType, $bFullName = true)
	{
		$typeID = strval($typeID);
		$valueType = strval($valueType);

		return self::GetEntityNameByComplex("{$typeID}_{$valueType}", $bFullName);
	}

	public static function GetEntityNameByComplex($complexName, $bFullName = true)
	{
		if ($complexName == '')
			return false;

		static $arList = array();

		$nameType = $bFullName? 'FULL': 'SHORT';

		$arList[$nameType] = array();
		if (empty($arList[$nameType]))
			$arList[$nameType] = self::GetEntityComplexList('', $bFullName);

		if (isset($arList[$nameType][$complexName]))
			return $arList[$nameType][$complexName];
		else
			return false;
	}

	// Obsolete. Please use PrepareListHeaders.
	public function ListAddHeaders(&$arHeaders, $skipTypes = array(), $skipValueTypes = array())
	{
		if(!is_array($skipTypes))
		{
			$skipTypes = array();
		}

		if(!is_array($skipValueTypes))
		{
			$skipValueTypes = array();
		}

		$ar =  CCrmFieldMulti::GetEntityTypeList();
		foreach($ar as $typeId => $arFields)
		{
			if(in_array($typeId, $skipTypes, true))
			{
				continue;
			}

			foreach($arFields as $valueType => $valueName)
			{
				if(in_array($valueType, $skipValueTypes, true))
				{
					continue;
				}

				$arHeaders[] = array(
					'id' => $typeId.'_'.$valueType,
					'name' => $valueName,
					'sort' => false,
					'default' => false,
					'editable' => false,
					'type' => 'string'
				);
			}
		}
	}

	public function PrepareListHeaders(&$arHeaders, $skipTypeIDs = array(), $prefix = '')
	{
		if(!is_array($skipTypeIDs))
		{
			$skipTypeIDs = array();
		}

		$arTypeInfos = self::GetEntityTypeInfos();
		foreach($arTypeInfos as $typeID => &$info)
		{
			if(in_array($typeID, $skipTypeIDs, true))
			{
				continue;
			}

			$arHeaders[] = array(
				'id' => "{$prefix}{$typeID}",
				'name' => $info['NAME'],
				'sort' => false,
				'default' => false,
				'editable' => false,
				'prevent_default' => false,
				'width' => 180,
				'type' => 'custom'
			);
		}
		unset($info);
	}

	public static function PrepareListItems(array $typeIDs = null)
	{
		$results = array();
		$infos = self::GetEntityTypeInfos();

		if($typeIDs === null)
		{
			$typeIDs = array_keys($infos);
		}

		foreach($typeIDs as $typeID)
		{
			if(isset($infos[$typeID]))
			{
				$results[$typeID] = $infos[$typeID]['NAME'];
			}
		}
		return $results;
	}

	public function ListAddFilterFields(&$arFilterFields, &$arFilterLogic, $sFormName = 'form1', $bVarsFromForm = true)
	{
		$ar = CCrmFieldMulti::GetEntityComplexList();
		foreach($ar as $complexId=>$complexName)
		{
			$arFilterFields[] = array(
				'id' => $complexId,
				'name' => htmlspecialcharsex($complexName),
				'type' => 'string',
				'value' => ''
			);
			$arFilterLogic[] = $complexId;
		}
	}

	public static function GetTemplate($typeID, $valueType, $value)
	{
		$typeID = strval($typeID);
		$valueType = strval($valueType);
		$value = strval($value);

		return self::GetTemplateByComplex("{$typeID}_{$valueType}", $value);
	}

	public static function GetTemplateByComplex($complexName, $value)
	{
		if ($complexName == '' || $value == '')
			return false;

		static $arList = Array();
		static $arEntityType = array();

		if (empty($arList))
		{
			if (empty($arEntityType))
				$arEntityType = self::GetEntityTypes();

			foreach($arEntityType as $entity => $ar)
				foreach($ar as $type => $ar)
					$arList[$entity.'_'.$type] = $ar['TEMPLATE'];
		}

		$valuer = $value;
		$valueUrl = $value;
		if (mb_strpos($complexName, 'PHONE_') === 0)
		{
			$valuer = preg_replace('/[^+0-9]/', '', $valuer);
		}
		if (mb_strpos($complexName, 'EMAIL_') === 0)
		{
			$crmEmail = mb_strtolower(trim(COption::GetOptionString('crm', 'mail', '')));
			if($crmEmail !== '')
			{
				$valueUrl .= '?cc='.urlencode($crmEmail);
			}
		}

		else if ($pos = mb_strpos($value, '://'))
		{
			$value_tmp = mb_substr($value, $pos + 3);
			return str_replace(array('#VALUE#', '#VALUE_HTML#', '#VALUE_URL#'), array($value_tmp, htmlspecialcharsbx($value_tmp), htmlspecialcharsbx($valueUrl)), '<a href="#VALUE_URL#" target="_blank">#VALUE_HTML#</a>');
		}

		if (isset($arList[$complexName]))
		{
			return str_replace(array('#VALUE#', '#VALUE_HTML#', '#VALUE_URL#'), array($valuer, htmlspecialcharsbx($value), htmlspecialcharsbx($valueUrl)), $arList[$complexName]);
		}
		else
			return false;
	}

	public function CheckFields($arFields, $bCheckStatusId = true)
	{
		$aMsg = array();

		if (!is_set($arFields, 'TYPE_ID') || !is_set($arFields, 'VALUE_TYPE'))
			$aMsg[] = array('id'=>'VALUE', 'text'=>GetMessage('CRM_MF_ERR_GET_NAME'));
		else
		{
			$fieldName = self::GetEntityNameByComplex($arFields['TYPE_ID'].'_'.$arFields['VALUE_TYPE']);
			if (is_set($arFields, 'VALUE') && trim($arFields['VALUE']) == '')
				$aMsg[] = array('id'=>'VALUE', 'text'=>GetMessage('CRM_MF_ERR_VALUE', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'VALUE') && mb_strlen($arFields['VALUE']) > 250)
				$aMsg[] = array('id'=>'VALUE', 'text'=>GetMessage('CRM_MF_ERR_VALUE_STRLEN', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'TYPE_ID') && trim($arFields['TYPE_ID']) == '')
				$aMsg[] = array('id'=>'TYPE_ID', 'text'=>GetMessage('CRM_MF_ERR_TYPE_ID', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'ENTITY_ID') && trim($arFields['ENTITY_ID']) == '')
				$aMsg[] = array('id'=>'ENTITY_ID', 'text'=>GetMessage('CRM_MF_ERR_ENTITY_ID', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'ELEMENT_ID') && intval($arFields['ELEMENT_ID']) <= 0)
				$aMsg[] = array('id'=>'ELEMENT_ID', 'text'=>GetMessage('CRM_MF_ERR_ELEMENT_ID', array('#FIELD_NAME#' => $fieldName)));
			if ($arFields['TYPE_ID'] == 'EMAIL' && !check_email($arFields['VALUE']))
				$aMsg[] = array('id'=>'ELEMENT_ID', 'text'=>GetMessage('CRM_MF_ERR_EMAIL_VALUE', array('#FIELD_NAME#' => $fieldName)));

		}

		if (!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}

	public function CheckComplexFields($arFields)
	{
		foreach($arFields as $fieldType => $ar)
			foreach($ar as $fieldId => $arValue)
			{
				$fieldName = self::GetEntityNameByComplex($fieldType.'_'.$arValue['VALUE_TYPE']);
				if (mb_strlen($arValue['VALUE']) > 250)
					$this->LAST_ERROR .= GetMessage('CRM_MF_ERR_VALUE_STRLEN', array('#FIELD_NAME#' => $fieldName))."<br />";
				if ($fieldType == 'EMAIL' && $arValue['VALUE'] <> '' && !check_email($arValue['VALUE']))
					$this->LAST_ERROR .= GetMessage('CRM_MF_ERR_EMAIL_VALUE', array('#FIELD_NAME#' => $fieldName))."<br />";
			}

		if ($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif)
	{
		$arMsg = Array();

		// prepare diff format
		$arField = Array();
		foreach($arFieldsOrig as $typeId => $arTypes)
			foreach($arTypes as $valueId => $arValues)
				$arField['original'][$valueId] = array_merge($arValues, Array('COMPLEX'=>$typeId.'_'.$arValues['VALUE_TYPE']));

		$addCnt = 1;
		foreach($arFieldsModif as $typeId => $arTypes)
			foreach($arTypes as $valueId => $arValues)
			{
				if(mb_substr($valueId, 0, 1) == 'n')
				{
					$arField['modified']['add'.($addCnt++)] = array_merge($arValues, Array('COMPLEX'=>$typeId.'_'.$arValues['VALUE_TYPE']));
				}
				else
				{
					$arField['modified'][$valueId] = array_merge($arValues, Array('COMPLEX'=>$typeId.'_'.$arValues['VALUE_TYPE']));
				}
			}

		if(isset($arField['modified']))
		{
			foreach ($arField['modified'] as $fieldId => $arValue)
			{
				if (isset($arField['original'][$fieldId]))
				{
					if ($arValue['VALUE'] == "")
					{
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_DELETE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']))),
							'EVENT_TEXT_1' => $arField['original'][$fieldId]['VALUE'],
						);
					}
					else if ($arField['original'][$fieldId]['COMPLEX'] != $arValue['COMPLEX']
					&& $arField['original'][$fieldId]['VALUE'] != $arValue['VALUE'] && $arValue['VALUE'] != "")
					{

						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_DELETE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']))),
							'EVENT_TEXT_1' => $arField['original'][$fieldId]['VALUE'],
						);
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_ADD', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arValue['COMPLEX']))),
							'EVENT_TEXT_1' => $arValue['VALUE'],
						);
					}
					else if ($arField['original'][$fieldId]['COMPLEX'] != $arValue['COMPLEX'])
					{
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_MODIFY_TYPE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']))),
							'EVENT_TEXT_1' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']),
							'EVENT_TEXT_2' => self::GetEntityNameByComplex($arValue['COMPLEX']),
						);
					}
					else if ($arField['original'][$fieldId]['VALUE'] != $arValue['VALUE'])
					{
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_MODIFY_VALUE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arValue['COMPLEX']))),
							'EVENT_TEXT_1' => $arField['original'][$fieldId]['VALUE'],
							'EVENT_TEXT_2' => $arValue['VALUE'],
						);
					}
				}
				elseif ($arValue['VALUE'] != "")
				{
					$arMsg[] = Array(
						'EVENT_NAME' => GetMessage('CRM_CF_FIELD_ADD', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arValue['COMPLEX']))),
						'EVENT_TEXT_1' => $arValue['VALUE'],
					);
				}
			}
		}
		return $arMsg;
	}

	public static function CompareValuesFields($fieldsOrig, &$fieldsModif)
	{
		foreach ($fieldsModif as $multiTypeId=>$ar)
		{
			$result = array();
			$qty = 0;
			foreach ($ar as $id=>$value)
			{
				if(mb_substr($id, 0, 1) == 'n')
				{
					if(key_exists($multiTypeId, $fieldsOrig))
					{
						$list = $fieldsOrig[$multiTypeId];
						foreach ($list as $item)
						{
							if($item['VALUE_TYPE']==$value['VALUE_TYPE'] && $item['VALUE']==$value['VALUE'])
							{
								$ar[$item['ID']]=$value;
								unset($ar[$id]);
							}
						}
					}
				}
			}
			foreach (array_keys($ar) as $id)
			{
				$key = $id > 0 ? $id : 'n'.(++$qty);
				$result[$key] = $ar[$id];
			}
			$fieldsModif[$multiTypeId] = $result;
		}
	}

	public static function HasImolValues($fieldsMulti)
	{
		$typeId = CCrmFieldMulti::IM;
		if (!self::HasValues($fieldsMulti, $typeId))
		{
			return false;
		}

		foreach($fieldsMulti[$typeId] as $id => $row)
		{
			$value = isset($row['VALUE']) ? trim((string)$row['VALUE']) : '';
			if(mb_strpos($value, 'imol|') === 0)
			{
				return true;
			}
		}

		return false;
	}

	public static function GetEntityFields($entityID, $elementID, $typeID, $bIgnoreEmpty = false, $bFullName = true)
	{
		$rsFields = self::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => $entityID,
				'ELEMENT_ID' => $elementID,
				'TYPE_ID' =>  $typeID
			)
		);

		$result = array();
		while($arField = $rsFields->Fetch())
		{
			if($bIgnoreEmpty && (!isset($arField['VALUE']) || $arField['VALUE'] == ''))
			{
				continue;
			}

			$arField['ENTITY_NAME'] = self::GetEntityNameByComplex($arField['COMPLEX_ID'], $bFullName);
			$result[] = $arField;
		}

		return $result;
	}

	/**
	 * @param $entityID
	 * @param $elementID
	 * @param $typeID
	 * @param false $bIgnoreEmpty
	 * @param bool $bFullName
	 * @return array|null
	 */
	public static function GetEntityFirstField(
		$entityID,
		$elementID,
		$typeID,
		$bIgnoreEmpty = false,
		$bFullName = true
	): ?array
	{
		$entityFields = \CCrmFieldMulti::GetEntityFields($entityID, $elementID, $typeID, $bIgnoreEmpty, $bFullName);
		foreach ($entityFields as $entityField)
		{
			return $entityField;
		}

		return null;
	}

	/**
	 * @param $entityID
	 * @param $elementID
	 * @param $typeID
	 * @param false $bIgnoreEmpty
	 * @param bool $bFullName
	 * @return Main\PhoneNumber\PhoneNumber|null
	 */
	public static function GetEntityFirstPhone(
		$entityID,
		$elementID,
		$bIgnoreEmpty = false,
		$bFullName = true
	)
	{
		$field = self::GetEntityFirstField($entityID, $elementID, self::PHONE, $bIgnoreEmpty, $bFullName);
		if (!$field)
		{
			return null;
		}

		return Main\PhoneNumber\Parser::getInstance()->parse($field['VALUE']);
	}

	public static function ExtractValues(&$fields, $typeName)
	{
		if(!(is_array($fields) && $typeName !== ''))
		{
			return array();
		}

		$values = array();
		$data = isset($fields[$typeName]) ? $fields[$typeName] : null;
		if(is_array($data))
		{
			foreach($data as &$item)
			{
				$value = isset($item['VALUE']) ? $item['VALUE'] : '';
				if($value === '')
				{
					continue;
				}

				$valueType = isset($item['VALUE_TYPE']) ? $item['VALUE_TYPE'] : '';
				if(!isset($values[$valueType]))
				{
					$values[$valueType] = array();
				}

				if(isset($item['VALUE']))
				{
					$values[$valueType][] = $item['VALUE'];
				}
			}
			unset($item);
		}
		return $values;
	}

	public static function PrepareEntityInfoBatch($typeID, $entityID, array &$entityInfos, array $options = null)
	{
		global $DB;

		if(empty($entityInfos))
		{
			return;
		}

		$enableNormalization = is_array($options) && isset($options['ENABLE_NORMALIZATION']) && $options['ENABLE_NORMALIZATION'];

		$elementIDs = array_keys($entityInfos);
		$elementSql = implode(',', $elementIDs);
		$sql = "SELECT m1.ELEMENT_ID AS ELEMENT_ID, m1.VALUE AS VALUE, m2.CNT AS CNT FROM b_crm_field_multi m1 INNER JOIN (SELECT MIN(ID) AS MIN_ID, COUNT(*) AS CNT FROM b_crm_field_multi m0 WHERE ENTITY_ID = '{$entityID}' AND ELEMENT_ID IN ({$elementSql}) AND TYPE_ID = '{$typeID}' GROUP BY ENTITY_ID, ELEMENT_ID) m2 ON m1.ID = m2.MIN_ID";

		$err_mess = (self::err_mess()).'<br />Function: GetInfoBatch<br>Line: ';
		$dbResult = $DB->Query($sql, false, $err_mess.__LINE__);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$elementID = (int)$fields['ELEMENT_ID'];
				if(isset($entityInfos[$elementID]))
				{
					$value = $fields['VALUE'];
					if($enableNormalization && $typeID === 'PHONE')
					{
						$value = NormalizePhone($value, 1);
					}

					$entityInfos[$elementID][$typeID] = array(
						'FIRST_VALUE' => $value,
						'TOTAL' => (int)$fields['CNT']
					);
				}
			}
		}
	}

	public static function PrepareEntityDataBulk($typeID, $entityID, array $elementIDs, array $options = null)
	{
		global $DB;

		$elementIDs = array_filter(array_map('intval', $elementIDs));
		if(empty($elementIDs))
		{
			return array();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$limit = isset($options['LIMIT']) ? (int)$options['LIMIT'] : 0;
		$enableComplexName = isset($options['ENABLE_COMPLEX_NAME']) && $options['ENABLE_COMPLEX_NAME'] === true;

		$typeSql = $DB->ForSql($typeID);
		$entitySql = $DB->ForSql($entityID);
		$elementSql = implode(',', $elementIDs);

		$sql = "SELECT * FROM b_crm_field_multi WHERE TYPE_ID = '{$typeSql}' AND ENTITY_ID = '{$entitySql}' AND ELEMENT_ID IN({$elementSql})";

		$results = array();
		$dbResult = $DB->Query($sql);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$elementID = $fields['ELEMENT_ID'];
				if(!isset($results[$elementID]))
				{
					$results[$elementID] = array();
				}
				elseif($limit > 0 && count($results[$elementID]) >= $limit)
				{
					continue;
				}

				if($typeID === static::PHONE)
					$fields['VALUE_FORMATTED'] = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($fields['VALUE'])->format();
				else
					$fields['VALUE_FORMATTED'] = $fields['VALUE'];

				if($enableComplexName)
				{
					$fields['COMPLEX_NAME'] = self::GetEntityNameByComplex(
						$fields['COMPLEX_ID'],
						false
					);
				}

				$results[$elementID][] = $fields;
			}
		}

		return $results;
	}

	public static function GetAllEntityFields($entityID, $elementID)
	{
		$results = array();

		$dbResult = self::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityID, 'ELEMENT_ID' => $elementID)
		);

		while($fields = $dbResult->Fetch())
		{
			$typeID = $fields['TYPE_ID'];
			if(!isset($results[$typeID]))
			{
				$results[$typeID] = array();
			}

			$results[$typeID][$fields['ID']] = array(
				'VALUE' => $fields['VALUE'],
				'VALUE_TYPE' => $fields['VALUE_TYPE']
			);
		}

		return $results;
	}

	public static function Rebind($srcEntityID, $srcElementID,  $dstEntityID, $dstElementID)
	{
		if(!(is_string($srcEntityID) && $srcEntityID !== ''))
		{
			throw new \Bitrix\Main\ArgumentException('Must be not empty string.', 'srcEntityID');
		}

		if(!(is_string($dstEntityID) && $dstEntityID !== ''))
		{
			throw new \Bitrix\Main\ArgumentException('Must be not empty string.', 'dstEntityID');
		}

		if(!is_int($srcElementID))
		{
			$srcElementID = (int)$srcElementID;
		}

		if($srcElementID <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('Must be greater than zero.', 'srcElementID');
		}

		if(!is_int($dstElementID))
		{
			$dstElementID = (int)$dstElementID;
		}

		if($dstElementID <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('Must be greater than zero.', 'dstElementID');
		}

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();

		$helper = $connection->getSqlHelper();
		$srcEntityID = $helper->forSql($srcEntityID);
		$dstEntityID = $helper->forSql($dstEntityID);
		$connection->queryExecute("
			UPDATE b_crm_field_multi SET ENTITY_ID = '{$dstEntityID}', ELEMENT_ID = {$dstElementID}
				WHERE ENTITY_ID = '{$srcEntityID}' AND ELEMENT_ID = {$srcElementID}
		");

		DuplicateCommunicationCriterion::processMultifieldsChange(
			CCrmOwnerType::ResolveID($srcEntityID),
			$srcElementID
		);

		DuplicateCommunicationCriterion::processMultifieldsChange(
			CCrmOwnerType::ResolveID($dstEntityID),
			$dstElementID
		);
	}

	private static function err_mess()
	{
		return '<br />Class: CCrmFieldMulti<br>File: '.__FILE__;
	}

	private static function fetchCountryCode(string $typeId, array $input): string
	{
		if ($typeId !== static::PHONE)
		{
			return '';
		}

		$phoneNumber = isset($input['VALUE']) ? trim($input['VALUE']) : '';
		if (empty($phoneNumber))
		{
			return '';
		}

		if (self::$allowedCountryCodes === null)
		{
			self::$allowedCountryCodes = array_column(GetCountries(), 'CODE');
			self::$allowedCountryCodes[] = CountryProvider::GLOBAL_COUNTRY_CODE;
		}

		$countryCode = isset($input['VALUE_EXTRA']['VALUE_COUNTRY_CODE'])
			? mb_strtoupper(trim($input['VALUE_EXTRA']['VALUE_COUNTRY_CODE']))
			: mb_strtoupper(trim($input['VALUE_COUNTRY_CODE']));
		if (in_array($countryCode, self::$allowedCountryCodes, true))
		{
			return $countryCode; // valid code
		}

		return static::detectCountryByPhone($phoneNumber);
	}

	private static function detectCountryByPhone(string $phoneNumber): string
	{
		/** @var Main\PhoneNumber\Parser $parserInstance */
		$parserInstance = Main\PhoneNumber\Parser::getInstance();

		$defaultResult = $parserInstance->parse($phoneNumber);
		if ($defaultResult->hasPlus() && $defaultResult->isValid())
		{
			return $defaultResult->getCountry();
		}

		// add "+" and try again
		$country = $parserInstance->parse('+' . $phoneNumber)->getCountry();

		return $country ?? '';
	}
}

?>
