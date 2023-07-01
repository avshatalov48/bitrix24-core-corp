<?php

IncludeModuleLangFile(__FILE__);

use	Bitrix\Main;
use	Bitrix\Main\Loader;
use Bitrix\Crm\UserField\UserFieldHistory;
use Bitrix\Main\Localization\Loc;

class CCrmFields
{
	private $sUFEntityID = '';

	protected $cUFM = null;

	protected $cdb = null;

	private $arUFList = array();

	private $arEntityType = array();

	private $arFieldType = array();

	private $arErrors = array();

	private $bError = false;

	function __construct(CUserTypeManager $cUFM, $sUFEntityID)
	{
		global $DB;

		$this->cUFM = $cUFM;

		$this->sUFEntityID = $sUFEntityID;

		$this->arEntityId = self::GetEntityTypes();

		if (!isset($this->arEntityId[$sUFEntityID]))
		{
			$this->SetError(array('id' => 'ENTITY_ID', 'text' => GetMessage("CRM_FIELDS_ERROR_ENTITY_ID")));

			return false;
		}

		$this->arFieldType = self::GetFieldTypes();

		$this->arUFList = $this->GetUserFields($sUFEntityID, 0, LANGUAGE_ID);

		$this->cdb = $DB;
	}

	protected function GetUserFields($entity_id, $value_id = 0, $LANG = false, $user_id = false)
	{
		$result = $this->cUFM->GetUserFields($entity_id, $value_id, $LANG, $user_id);

		// remove invoice reserved fields
		if ($entity_id === CCrmInvoice::GetUserFieldEntityID())
			foreach (CCrmInvoice::GetUserFieldsReserved() as $ufId)
				if (isset($result[$ufId]))
					unset($result[$ufId]);

		return $result;
	}

	public function GetFields()
	{
		return $this->arUFList;
	}

	public function GetByID($ID)
	{
		foreach($this->arUFList as $field)
		{
			if(isset($field['ID']) && $field['ID'] == $ID)
			{
				return $field;
			}
		}

		return false;
	}

	public function GetByName($ID)
	{
		return isset($this->arUFList[$ID]) ? $this->arUFList[$ID] : false;
	}

	public function GetFieldById($ID)
	{
		if (isset($this->arUFList[$ID]))
			return $this->arUFList[$ID];
		else
			return false;
	}

	public static function GetFieldTypes()
	{
		//'Disk File' is disabled due to GUI issues (see CCrmDocument::GetDocumentFieldTypes)
		$arFieldType = Array(
			'string' 		=> array( 'ID' =>'string', 'NAME' => GetMessage('CRM_FIELDS_TYPE_S')),
			'integer'		=> array( 'ID' =>'integer', 'NAME' => GetMessage('CRM_FIELDS_TYPE_I')),
			'double'		=> array( 'ID' =>'double', 'NAME' => GetMessage('CRM_FIELDS_TYPE_D')),
			'boolean'		=> array( 'ID' =>'boolean', 'NAME' => GetMessage('CRM_FIELDS_TYPE_B')),
			'datetime'		=> array( 'ID' =>'datetime', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DT')),
			'date'			=> array( 'ID' =>'date', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DATE')),
			'money' 		=> array( 'ID' =>'money', 'NAME' => GetMessage('CRM_FIELDS_TYPE_MONEY')),
			'url' 			=> array( 'ID' =>'url', 'NAME' => GetMessage('CRM_FIELDS_TYPE_URL')),
			'address'		=> array( 'ID' =>'address', 'NAME' => GetMessage('CRM_FIELDS_TYPE_ADDRESS_2')),
			'resourcebooking' => array( 'ID' =>'resourcebooking', 'NAME' => GetMessage('CRM_FIELDS_TYPE_RESOURCEBOOKING')),
			'enumeration' 	=> array( 'ID' =>'enumeration', 'NAME' => GetMessage('CRM_FIELDS_TYPE_E')),
			'file'			=> array( 'ID' =>'file', 'NAME' => GetMessage('CRM_FIELDS_TYPE_F')),
			'employee'		=> array( 'ID' =>'employee', 'NAME' => GetMessage('CRM_FIELDS_TYPE_EM')),
			'crm_status'	=> array( 'ID' =>'crm_status', 'NAME' => GetMessage('CRM_FIELDS_TYPE_CRM_STATUS')),
			'iblock_section'=> array( 'ID' =>'iblock_section', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_SECTION')),
			'iblock_element'=> array( 'ID' =>'iblock_element', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_ELEMENT')),
			'crm'			=> array( 'ID' =>'crm', 'NAME' => GetMessage('CRM_FIELDS_TYPE_CRM_ELEMENT'))
			//'disk_file'	=> array( 'ID' =>'disk_file', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DISK_FILE')),
		);
		return $arFieldType;
	}

	public static function GetEntityTypes()
	{
		$arEntityType = Array(
			'CRM_LEAD' => array(
				'ID' =>'CRM_LEAD',
				'NAME' => GetMessage('CRM_FIELDS_LEAD'),
				'DESC' => GetMessage('CRM_FIELDS_LEAD_DESC')
			),
			'CRM_CONTACT' => array(
				'ID' =>'CRM_CONTACT',
				'NAME' => GetMessage('CRM_FIELDS_CONTACT'),
				'DESC' => GetMessage('CRM_FIELDS_CONTACT_DESC')
			),
			'CRM_COMPANY' => array(
				'ID' =>'CRM_COMPANY',
				'NAME' => GetMessage('CRM_FIELDS_COMPANY'),
				'DESC' => GetMessage('CRM_FIELDS_COMPANY_DESC')
			),
			'CRM_DEAL'=> array(
				'ID' =>'CRM_DEAL',
				'NAME' => GetMessage('CRM_FIELDS_DEAL'),
				'DESC' => GetMessage('CRM_FIELDS_DEAL_DESC')
			),
			'CRM_QUOTE'=> array(
				'ID' =>'CRM_QUOTE',
				'NAME' => GetMessage('CRM_FIELDS_QUOTE'),
				'DESC' => GetMessage('CRM_FIELDS_QUOTE_DESC')
			),
			'CRM_INVOICE'=> array(
				'ID' =>'CRM_INVOICE',
				'NAME' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Invoice),
				'DESC' => GetMessage('CRM_FIELDS_INVOICE_DESC')
			),
			'ORDER' => array(
				'ID' => \Bitrix\Crm\Order\Manager::getUfId(),
				'NAME' => GetMessage('CRM_FIELDS_ORDER'),
				'DESC' => GetMessage('CRM_FIELDS_ORDER_DESC')
			)
		);

		if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$localization = \Bitrix\Crm\Service\Container::getInstance()->getLocalization();
			$arEntityType['CRM_INVOICE']['DESC'] = $localization->appendOldVersionSuffix($arEntityType['CRM_INVOICE']['DESC']);
		}

		//DEFERRED: CustomType
		//return array_merge($arEntityType, \Bitrix\Crm\Activity\CustomType::getUserFieldTypes());
		return $arEntityType;
	}

	public static function GetAdditionalFields($entityType, $fieldValue = Array())
	{
		global $APPLICATION;

		$arFields = Array();
		switch ($entityType)
		{
			case 'string':
				$arFields[] = array(
					'id' => 'ROWS',
					'name' => GetMessage('CRM_FIELDS_TEXT_ROW_COUNT'),
					'type' => 'text',
				);
				$arFields[] = array(
					'id' => 'DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_DEFAULT_VALUE'),
					'type' => 'text',
				);
				break;
			case 'url':
				$arFields[] = array(
					'id' => 'DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_DEFAULT_VALUE'),
					'type' => 'text',
				);
				break;
			case 'integer':
			case 'double':
				$arFields[] = array(
					'id' => 'DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_DEFAULT_VALUE'),
					'type' => 'text',
				);
				break;

			case 'boolean':
				$arFields[] = array(
					'id' => 'B_DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_TYPE_B_VALUE'),
					'type' => 'list',
					'items' => array(
						'1' => GetMessage('CRM_FIELDS_TYPE_B_VALUE_YES'),
						'0' => GetMessage('CRM_FIELDS_TYPE_B_VALUE_NO')
					),
				);
				$arFields[] = array(
					'id' => 'B_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_B_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'CHECKBOX' => GetMessage('CRM_FIELDS_TYPE_B_DISPLAY_CHECKBOX'),
						'RADIO' => GetMessage('CRM_FIELDS_TYPE_B_DISPLAY_RADIO'),
						'DROPDOWN' => GetMessage('CRM_FIELDS_TYPE_B_DISPLAY_DROPDOWN'),
					),
				);
				break;

			case 'datetime':
			case 'date':
				{
					$arFields[] = array(
						'id' => 'DT_TYPE',
						'name' => GetMessage('CRM_FIELDS_TYPE_DT_TYPE'),
						'type' => 'list',
						'items' => array(
							'NONE' => GetMessage('CRM_FIELDS_TYPE_DT_TYPE_NONE'),
							'NOW' => GetMessage($entityType === 'datetime'
								? 'CRM_FIELDS_TYPE_DT_TYPE_NOW' : 'CRM_FIELDS_TYPE_DATE_TYPE_NOW'),
							'FIXED' => GetMessage('CRM_FIELDS_TYPE_DT_TYPE_FIXED'),
						),
					);

					if ($entityType === 'datetime')
					{
						$arFields[] = array(
							'id' => 'DT_DEFAULT_VALUE',
							'name' => GetMessage('CRM_FIELDS_TYPE_DT_FIXED'),
							'type' => 'date',
							'params' => array('size' => 25)
						);
					}
					else
					{
						$arFields[] = [
							'id' => 'DT_DEFAULT_VALUE',
							'name' => Loc::getMessage('CRM_FIELDS_TYPE_DT_FIXED'),
							'type' => 'date_short',
							'params' => [
								'size' => 10,
							],
						];
					}
				}
				break;

			case 'enumeration':
				$arFields[] = [
					'id' => 'E_DISPLAY',
					'name' => Loc::getMessage('CRM_FIELDS_TYPE_E_DISPLAY'),
					'type' => 'list',
					'items' => [
						'LIST' => Loc::getMessage('CRM_FIELDS_TYPE_E_DISPLAY_LIST'),
						'UI' => Loc::getMessage('CRM_FIELDS_TYPE_E_DISPLAY_UI'),
						'CHECKBOX' => Loc::getMessage('CRM_FIELDS_TYPE_E_DISPLAY_CHECKBOX'),
						'DIALOG' => Loc::getMessage('CRM_FIELDS_TYPE_E_DISPLAY_DIALOG'),
					],
				];
				$arFields[] = [
					'id' => 'E_LIST_HEIGHT',
					'name' => Loc::getMessage('CRM_FIELDS_TYPE_E_LIST_HEIGHT'),
					'type' => 'text',
				];
				$arFields[] = [
					'id' => 'E_CAPTION_NO_VALUE',
					'name' => Loc::getMessage('CRM_FIELDS_TYPE_E_CAPTION_NO_VALUE'),
					'type' => 'text',
				];
				break;
			case 'money':
				if (Loader::includeModule('currency'))
				{
					ob_start();
					$APPLICATION->IncludeComponent(
						'bitrix:currency.money.input',
						'',
						array(
							'CONTROL_ID' => 'DEFAULT_VALUE_'.Main\Security\Random::getString(5),
							'FIELD_NAME' => 'DEFAULT_VALUE',
							'VALUE' => (isset($fieldValue['DEFAULT_VALUE']) ? $fieldValue['DEFAULT_VALUE'] : ''),
							'EXTENDED_CURRENCY_SELECTOR' => 'N'
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					$moneyContent = ob_get_contents();
					ob_end_clean();
					$arFields[] = array(
						'id' => 'DEFAULT_VALUE',
						'name' => GetMessage('CRM_FIELDS_DEFAULT_VALUE'),
						'type' => 'custom',
						'value' => $moneyContent
					);
				}
				else
				{
					$arFields[] = array(
						'id' => 'DEFAULT_VALUE',
						'name' => GetMessage('CRM_FIELDS_DEFAULT_VALUE'),
						'type' => 'text',
					);
				}
				break;
			case 'iblock_section':
				$id = isset($fieldValue['IB_IBLOCK_ID']) ? (int)$fieldValue['IB_IBLOCK_ID'] : 0;
				$bActiveFilter = isset($fieldValue['IB_ACTIVE_FILTER']) && $fieldValue['IB_ACTIVE_FILTER'] == 'Y' ? 'Y' : 'N';

				$arFields[] = array(
					'id' => 'IB_IBLOCK_TYPE_ID',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_IBLOCK_TYPE_ID'),
					'type' => 'custom',
					'value' => GetIBlockDropDownList($id, 'IB_IBLOCK_TYPE_ID', 'IB_IBLOCK_ID')
				);

				$arDefault = Array('' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE_ANY'));
				$found = false;
				if ($id > 0)
				{
					$arFilter = Array("IBLOCK_ID" => $id);
					if ($bActiveFilter === "Y")
						$arFilter["GLOBAL_ACTIVE"] = "Y";

					$rsSections = CIBlockSection::GetList(
						Array("LEFT_MARGIN" => "ASC"),
						$arFilter,
						false,
						array("ID", "DEPTH_LEVEL", "NAME", "LEFT_MARGIN")
					);

					while ($arSection = $rsSections->Fetch())
					{
						$arDefault[$arSection["ID"]] = str_repeat(". ", $arSection["DEPTH_LEVEL"] - 1).$arSection["NAME"];
						$found = true;
					}
					unset($arSection, $rsSections);
				}

				if ($found)
				{
					$arFields[] = array(
						'id' => 'IB_DEFAULT_VALUE',
						'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
						'items' => $arDefault,
						'type' => 'list',
					);
				}
				else
				{
					$arFields[] = array(
						'id' => 'IB_DEFAULT_VALUE',
						'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
						'type' => 'text',
					);
				}
				unset($arDefault);

				$arFields[] = array(
					'id' => 'IB_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'LIST'		=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_LIST'),
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_CHECKBOX'),
					),
				);
				$arFields[] = array(
					'id' => 'IB_LIST_HEIGHT',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_LIST_HEIGHT'),
					'type' => 'text',
				);
				$arFields[] = array(
					'id' => 'IB_ACTIVE_FILTER',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_ACTIVE_FILTER'),
					'type' => 'checkbox',
				);
			break;


			case 'iblock_element':
				$id = isset($fieldValue['IB_IBLOCK_ID'])? (int)$fieldValue['IB_IBLOCK_ID']: 0;
				$bActiveFilter = isset($fieldValue['IB_ACTIVE_FILTER']) && $fieldValue['IB_ACTIVE_FILTER'] == 'Y'? 'Y': 'N';

				$arFields[] = array(
					'id' => 'IB_IBLOCK_TYPE_ID',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_IBLOCK_TYPE_ID'),
					'type' => 'custom',
					'value' => GetIBlockDropDownList($id, 'IB_IBLOCK_TYPE_ID', 'IB_IBLOCK_ID')
				);

				$arDefault = Array(''=>GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE_ANY'));
				$found = false;
				if ($id > 0)
				{
					$arFilter = Array("IBLOCK_ID" => $id);
					if ($bActiveFilter === "Y")
						$arFilter["ACTIVE"] = "Y";

					$rs = CIBlockElement::GetList(
						array("SORT" => "DESC", "NAME" => "ASC"),
						$arFilter,
						false,
						false,
						array("ID", "NAME", "SORT")
					);

					while ($ar = $rs->Fetch())
					{
						$found = true;
						$arDefault[$ar["ID"]] = $ar["NAME"];
					}
					unset($sr, $rs);
				}

				if ($found)
				{
					$arFields[] = array(
						'id' => 'IB_DEFAULT_VALUE',
						'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
						'items' => $arDefault,
						'type' => 'list',
					);
				}
				else
				{
					$arFields[] = array(
						'id' => 'IB_DEFAULT_VALUE',
						'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
						'type' => 'text',
					);
				}
				unset($arDefault);

				$arFields[] = array(
					'id' => 'IB_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'LIST'		=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_LIST'),
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_CHECKBOX'),
					),
				);
				$arFields[] = array(
					'id' => 'IB_LIST_HEIGHT',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_LIST_HEIGHT'),
					'type' => 'text',
				);
				$arFields[] = array(
					'id' => 'IB_ACTIVE_FILTER',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_ACTIVE_FILTER'),
					'type' => 'checkbox',
				);

			break;

			case 'crm_status':

				$arItems = Array();
				$ar = CCrmStatus::GetEntityTypes();
				foreach ($ar as $data)
					$arItems[$data['ID']] = $data['NAME'];

				$arFields[] = array(
					'id' => 'ENTITY_TYPE',
					'name' => GetMessage('CRM_FIELDS_TYPE_CRM_STATUS_ENTITY_TYPE'),
					'type' => 'list',
					'items' => $arItems,
				);
			break;

			case 'crm':
				$settings = $fieldValue['SETTINGS'] ?? [];

				$entityTypeLead = isset($settings['LEAD']) && $settings['LEAD'] === 'Y'? 'Y': 'N';
				$entityTypeContact = isset($settings['CONTACT']) && $settings['CONTACT'] === 'Y'? 'Y': 'N';
				$entityTypeCompany = isset($settings['COMPANY']) && $settings['COMPANY'] === 'Y'? 'Y': 'N';
				$entityTypeDeal = isset($settings['DEAL']) && $settings['DEAL'] === 'Y'? 'Y': 'N';
				$entityTypeQuote = isset($settings['QUOTE']) && $settings['QUOTE'] === 'Y'? 'Y': 'N';

				$sVal = '
					<input type="checkbox" name="ENTITY_TYPE[LEAD]" value="Y" '.($entityTypeLead==="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_LEAD').' <br/>
					<input type="checkbox" name="ENTITY_TYPE[CONTACT]" value="Y" '.($entityTypeContact==="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_CONTACT').'<br/>
					<input type="checkbox" name="ENTITY_TYPE[COMPANY]" value="Y" '.($entityTypeCompany==="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_COMPANY').'<br/>
					<input type="checkbox" name="ENTITY_TYPE[DEAL]" value="Y" '.($entityTypeDeal==="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_DEAL').'<br/>
					<input type="checkbox" name="ENTITY_TYPE[QUOTE]" value="Y" '.($entityTypeQuote==="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_QUOTE').'<br/>
				';

				$dynamicTypes = \Bitrix\Crm\UserField\Types\ElementType::getUseInUserfieldTypes();
				foreach ($dynamicTypes as $dynamicId => $dynamicTitle)
				{
					$dynamicTypeName = \CCrmOwnerType::ResolveName($dynamicId);
					$sVal .= '<input type="checkbox" name="ENTITY_TYPE['.$dynamicTypeName.']" '.((isset($settings[$dynamicTypeName]) && $settings[$dynamicTypeName] === 'Y')  ? "checked": "").' value="Y"> '.Main\Text\HtmlFilter::encode($dynamicTitle).'<br/>';
				}

				if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
				{
					$sVal .= '<input type="checkbox" name="ENTITY_TYPE[' . \CCrmOwnerType::SmartInvoiceName . ']" '.((isset($settings[\CCrmOwnerType::SmartInvoiceName]) && $settings[\CCrmOwnerType::SmartInvoiceName] === 'Y')  ? "checked": "").' value="Y"> '.Main\Text\HtmlFilter::encode(\CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice)).'<br/>';
				}

				$arFields[] = array(
					'id' => 'ENTITY_TYPE',
					'name' => GetMessage('CRM_FIELDS_TYPE_CRM_ELEMENT_ENTITY_TYPE'),
					'type' => 'custom',
					'value' => $sVal
				);
			break;
		}
		return $arFields;
	}

	public function DeleteField($ID)
	{
		$obUserField = new CUserTypeEntity();
		@set_time_limit(0);
		$this->cdb->StartTransaction();
		if (!$obUserField->Delete($ID))
		{
			$this->cdb->Rollback();

			$this->SetError(array('id' => 'DELETE_ENTITY_ID', 'text' => GetMessage('CRM_FIELDS_ERROR_DELETE_ENTITY_ID')));

			return false;
		}
		$this->cdb->Commit();

		UserFieldHistory::processRemoval(CCrmOwnerType::ResolveIDByUFEntityID($this->sUFEntityID), $ID);

		$this->arUFList = $this->GetUserFields($this->sUFEntityID, 0, LANGUAGE_ID);

		return true;
	}

	public function AddField($arField)
	{
		$obUserField = new CUserTypeEntity();
		$ID = $obUserField->Add($arField);
		$res = $ID > 0;

		if ($res)
		{
			if ($arField['USER_TYPE_ID'] == 'enumeration' && is_array($arField['LIST']))
			{
				$obEnum = new CUserFieldEnum();
				$res = $obEnum->SetEnumValues($ID, $arField['LIST']);
				if (!$res)
				{
					$ex = $GLOBALS["APPLICATION"]->GetException();
				}

			}

			$this->cUFM->CleanCache();
			$this->arUFList = $this->GetUserFields($this->sUFEntityID, 0, LANGUAGE_ID);

			UserFieldHistory::processCreation(CCrmOwnerType::ResolveIDByUFEntityID($this->sUFEntityID), $ID);
		}
		else
		{
			$ex = $GLOBALS["APPLICATION"]->GetException();

		}

		return $res;
	}

	public function UpdateField($ID, $arField)
	{
		$obUserField  = new CUserTypeEntity();
		$res = $obUserField->Update($ID, $arField);

		if($res)
		{
			UserFieldHistory::processModification(CCrmOwnerType::ResolveIDByUFEntityID($this->sUFEntityID), $ID);
		}

		if ($res && $arField['USER_TYPE_ID'] == 'enumeration' && is_array($arField['LIST']))
		{
			$obEnum = new CUserFieldEnum();
			$res = $obEnum->SetEnumValues($ID, $arField['LIST']);
		}

		$this->arUFList = $this->GetUserFields($this->sUFEntityID, 0, LANGUAGE_ID);

		return $res;
	}

	public function GetNextFieldId()
	{
		return self::GenerateFieldName();
	}

	public static function GenerateFieldName()
	{
		return 'UF_CRM_'.time();
	}

	private function SetError($arMsg)
	{
		$this->arErrors[] = $arMsg;

		$this->bError = true;

		return true;
	}

	public function CheckError()
	{
		global $APPLICATION;

		$e = new CAdminException($this->arErrors);
		$APPLICATION->ThrowException($e);

		return $this->bError;
	}
}
?>
