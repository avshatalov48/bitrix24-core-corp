<?php

if (!CModule::IncludeModule('bizproc'))
{
	return;
}

use Bitrix\Crm;
use \Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

class CCrmDocument
{
	protected const GROUP_RESPONSIBLE_HEAD = 'responsible_head';
	protected const GROUP_AUTHOR = 'author';

	private static $UNGROUPED_USERS = array();
	private static $USER_GROUPS = array();
	private static ?int $b24employeeGroupId;
	private static $USER_PERMISSION_CHECK = array();
	private static $webFormSelectList;

	public static function GetDocumentFieldTypes($documentType)
	{
		global $USER_FIELD_MANAGER;
		$arDocumentID = static::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		$arResult = array(
			'string' => array('Name' => GetMessage('BPVDX_STRING'), 'BaseType' => 'string'),
			'int' => array('Name' => GetMessage('BPVDX_NUMINT'), 'BaseType' => 'int'),
			'email' => array(
				'Name' => GetMessage('BPVDX_EMAIL'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Email::class,
			),
			'phone' => array(
				'Name' => GetMessage('BPVDX_PHONE'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Phone::class,
			),
			'web' => array(
				'Name' => GetMessage('BPVDX_WEB'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Web::class,
			),
			'im' => array(
				'Name' => GetMessage('BPVDX_MESSANGER'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Im::class,
			),
			'text' => array('Name' => GetMessage('BPVDX_TEXT'), 'BaseType' => 'text'),
			'double' => array('Name' => GetMessage('BPVDX_NUM'), 'BaseType' => 'double'),
			'select' => array('Name' => GetMessage('BPVDX_LIST'), 'BaseType' => 'select', "Complex" => true),
			'file' => array('Name' => GetMessage('BPVDX_FILE'), 'BaseType' => 'file'),
			'user' => array('Name' => GetMessage('BPVDX_USER'), 'BaseType' => 'user'),
			'bool' => array('Name' => GetMessage('BPVDX_YN'), 'BaseType' => 'bool'),
			'datetime' => array('Name' => GetMessage('BPVDX_DATETIME'), 'BaseType' => 'datetime'),
			\Bitrix\Bizproc\FieldType::INTERNALSELECT => [
				'Name' => GetMessage("BPVDX_INTERNALSELECT"),
				'BaseType' => 'string',
				'Complex' => true,
			],
			'deal_category' => [
				'Name' => \Bitrix\Crm\Integration\BizProc\FieldType\DealCategory::getName(),
				'BaseType' => \Bitrix\Crm\Integration\BizProc\FieldType\DealCategory::getType(),
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\DealCategory::class,
			],
			'deal_stage' => [
				'Name' => \Bitrix\Crm\Integration\BizProc\FieldType\DealStage::getName(),
				'BaseType' => \Bitrix\Crm\Integration\BizProc\FieldType\DealStage::getType(),
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\DealStage::class,
			],
			'lead_status' => [
				'Name' => \Bitrix\Crm\Integration\BizProc\FieldType\LeadStatus::getName(),
				'BaseType' => \Bitrix\Crm\Integration\BizProc\FieldType\LeadStatus::getType(),
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\LeadStatus::class,
			],
			'sms_sender' => [
				'Name' => \Bitrix\Crm\Integration\BizProc\FieldType\SmsSender::getName(),
				'BaseType' => \Bitrix\Crm\Integration\BizProc\FieldType\SmsSender::getType(),
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\SmsSender::class,
			],
			'mail_sender' => [
				'Name' => \Bitrix\Bizproc\UserType\MailSender::getName(),
				'BaseType' => \Bitrix\Bizproc\UserType\MailSender::getType(),
				'typeClass' => \Bitrix\Bizproc\UserType\MailSender::class,
			],
		);

		//'Disk File' is disabled due to GUI issues (see CCrmFields::GetFieldTypes)
		$ignoredUserTypes = array(
			'string', 'double', 'boolean', 'integer', 'datetime', 'file', 'employee', 'enumeration', 'video',
			'string_formatted', 'webdav_element_history', 'disk_version', 'disk_file', 'vote', 'url_preview', 'hlblock',
			'mail_message', 'snils',
		);
		$arTypes = $USER_FIELD_MANAGER->GetUserType();
		foreach ($arTypes as $arType)
		{
			if (in_array($arType['USER_TYPE_ID'], $ignoredUserTypes))
				continue;

			if ($arType['BASE_TYPE'] == 'enum')
			{
				$arType['BASE_TYPE'] = 'select';
			}

			$sType = 'UF:'.$arType['USER_TYPE_ID'];

			$arResult[$sType] = array(
				'Name' => $arType['DESCRIPTION'],
				'BaseType' => $arType['BASE_TYPE'],
			);

			if ($arType['USER_TYPE_ID'] === 'date')
			{
				$arResult[$sType]['typeClass'] = '\Bitrix\Bizproc\BaseType\Date';
				$arResult[$sType]['BaseType'] = 'date';
			}
			elseif ($arType['USER_TYPE_ID'] === 'iblock_element')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\IblockElement::class;
				$arResult[$sType]['Complex'] = true;
			}
			elseif ($arType['USER_TYPE_ID'] === 'iblock_section')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\IblockSection::class;
				$arResult[$sType]['Complex'] = true;
			}
			elseif ($arType['USER_TYPE_ID'] === 'crm_status')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\CrmStatus::class;
				$arResult[$sType]['Complex'] = true;
			}
			elseif ($arType['USER_TYPE_ID'] === 'crm')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\Crm::class;
				$arResult[$sType]['Complex'] = true;
			}
			elseif ($arType['USER_TYPE_ID'] === 'resourcebooking')
			{
				//TODO
			}
			elseif ($arType['USER_TYPE_ID'] === 'money')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\Money::class;
			}
			elseif ($arType['USER_TYPE_ID'] === 'address')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\Address::class;
			}
			elseif ($arType['USER_TYPE_ID'] === 'url')
			{
				$arResult[$sType]['typeClass'] = \Bitrix\Crm\Integration\BizProc\FieldType\Url::class;
			}
		}
		return $arResult;
	}

	public static function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false, $publicMode = false)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		$arDocumentID = static::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
			$arDocumentFieldTypes[$documentType] = static::GetDocumentFieldTypes($documentType);

		$arFieldType["BaseType"] = "string";
		$arFieldType["Complex"] = false;
		if (array_key_exists($arFieldType["Type"], $arDocumentFieldTypes[$documentType]))
		{
			$arFieldType["BaseType"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["BaseType"];
			$arFieldType["Complex"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["Complex"];
		}

		//$customMethodName = '';
		$_fieldValue = $fieldValue;
		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::IsAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		ob_start();
		if ($arFieldType['Type'] == 'select')
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" style="width:280px" name="<?= htmlspecialcharsbx($arFieldName["Field"]).($arFieldType["Multiple"] ? "[]" : "") ?>"<?= ($arFieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$arFieldType['Required'])
					echo '<option value="">['.GetMessage('BPVDX_NOT_SET').']</option>';
				foreach ($arFieldType['Options'] as $k => $v)
				{
					$ind = array_search($k, $fieldValueTmp);
					echo '<option value="'.htmlspecialcharsbx($k).'"'.($ind !== false ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					if ($ind !== false)
						unset($fieldValueTmp[$ind]);
				}
				?>
			</select>
			<?
			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($arFieldName['Field']) ?>_text" name="<?= htmlspecialcharsbx($arFieldName['Field']) ?>_text" value="<?
				if (count($fieldValueTmp) > 0)
				{
					$a = array_values($fieldValueTmp);
					echo htmlspecialcharsbx($a[0]);
				}
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName['Field']) ?>_text', 'select');">
				<?
			}
		}
		elseif ($arFieldType['Type'] == 'web' || $arFieldType['Type'] == 'phone' || $arFieldType['Type'] == 'email' || $arFieldType['Type'] == 'im')
		{
			/*$fkeys = array_keys($fieldValue);
			foreach ($fkeys as $key)
			{
				if (preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($fieldValue[$key])) || substr(trim($fieldValue[$key]), 0, 1) == "=")
				{
					$
				}
			}*/

			$value1 = $_fieldValue;
			$value2 = null;
			if ($bAllowSelection && !is_array($value1) && CBPDocument::IsExpression(trim($value1)))
			{
				$value1 = null;
				$value2 = $_fieldValue;
			}

			$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', '',
				Array(
					'FM_MNEMONIC' => $arFieldName['Field'],
					'ENTITY_ID' => $arDocumentID['TYPE'],
					'ELEMENT_ID' => $arDocumentID['ID'],
					'TYPE_ID' => mb_strtoupper($arFieldType['Type']),
					'VALUES' => $value1,
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($arFieldName['Field']) ?>_text" name="<?= htmlspecialcharsbx($arFieldName['Field']) ?>_text" value="<?
					echo $value2;
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName['Field']) ?>_text', 'select');">
				<?
			}
		}
		elseif ($arFieldType['Type'] == 'user')
		{
			$fieldValue = CBPHelper::UsersArrayToString($fieldValue, null, $arDocumentID["DOCUMENT_TYPE"]);
			?><input type="text" size="40" id="id_<?= htmlspecialcharsbx($arFieldName['Field']) ?>" name="<?= htmlspecialcharsbx($arFieldName['Field']) ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName['Field']) ?>', 'user');"><?
		}
		else
		{
			if($arFieldType['Type'] == 'UF:disk_file')
			{
				$arFieldType['Multiple'] = false;
			}

			if (!array_key_exists('CBPVirtualDocumentCloneRowPrinted_'.$documentType, $GLOBALS) && $arFieldType['Multiple'])
			{
				$GLOBALS['CBPVirtualDocumentCloneRowPrinted_'.$documentType] = 1;
				?>
				<script>
				<!--
				function CBPVirtualDocumentCloneRow(tableID)
				{
					var tbl = document.getElementById(tableID);
					var cnt = tbl.rows.length;
					var oRow = tbl.insertRow(cnt);
					var oCell = oRow.insertCell(0);
					var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('[n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf(']', s);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 2, e - s));
						sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
						p = s + 1;
					}
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('__n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf('_', s + 2);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 3, e - s));
						sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
						p = e + 1;
					}
					oCell.innerHTML = sHTML;
					var patt = new RegExp('<' + 'script' + '>[^\000]*?<' + '\/' + 'script' + '>', 'ig');
					var code = sHTML.match(patt);
					if (code)
					{
						for (var i = 0; i < code.length; i++)
						{
							if (code[i] != '')
							{
								var s = code[i].substring(8, code[i].length - 9);
								jsUtils.EvalGlobal(s);
							}
						}
					}
				}
				//-->
				</script>
				<?
			}

			if ($arFieldType['Multiple'])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.htmlspecialcharsbx($arFieldName["Field"]).'_Table">';

			$fieldValueTmp = $fieldValue;

			if (sizeof($fieldValue) == 0)
				$fieldValue[] = null;

			$ind = -1;

			if($arFieldType['Type'] == 'UF:disk_file')
			{
				$arUserFieldType = $USER_FIELD_MANAGER->GetUserType('disk_file');
				$arUserField = array(
					'ENTITY_ID' => 'CRM_'.$arDocumentID['TYPE'],
					'FIELD_NAME' => $arFieldName['Field'],
					'USER_TYPE_ID' => 'disk_file',
					'SORT' => 100,
					'MULTIPLE' => 'Y',
					'MANDATORY' => $arFieldType['Required'] ? 'Y' : 'N',
					'EDIT_IN_LIST' => 'Y',
					'EDIT_FORM_LABEL' => $arUserFieldType['DESCRIPTION'],
					'VALUE' => $fieldValue,
					'USER_TYPE' => $arUserFieldType,
					'SETTINGS' => array(),
					'ENTITY_VALUE_ID' => 1,
				);

				$APPLICATION->IncludeComponent(
					'bitrix:system.field.edit',
					'disk_file',
					array(
						'arUserField' => $arUserField,
						'bVarsFromForm' => false,
						'form_name' => $arFieldName['Form'],
						'FILE_MAX_HEIGHT' => 400,
						'FILE_MAX_WIDTH' => 400,
						'FILE_SHOW_POPUP' => true,
					),
					false,
					array('HIDE_ICONS' => 'Y')
				);
			}
			else
			{
				foreach ($fieldValue as $key => $value)
				{
					$ind++;
					$fieldNameId = 'id_'.htmlspecialcharsbx($arFieldName['Field']).'__n'.$ind.'_';
					$fieldNameName = htmlspecialcharsbx($arFieldName['Field']).($arFieldType['Multiple'] ? '[n'.$ind.']' : '');

					if ($arFieldType['Multiple'])
						echo '<tr><td>';

					if (mb_strpos($arFieldType['Type'], 'UF:') === 0)
					{
						$value1 = $value;
						if ($bAllowSelection && CBPDocument::IsExpression(trim($value1)))
							$value1 = null;
						else
							unset($fieldValueTmp[$key]);

						$sType = str_replace('UF:', '', $arFieldType['Type']);

						$_REQUEST[$arFieldName['Field']] = $value1;
						if ($sType == 'crm')
						{
							?>
							<script>
							BX.loadCSS('/bitrix/js/crm/css/crm.css');
							</script>
							<?
						}
						$arUserFieldType = $USER_FIELD_MANAGER->GetUserType($sType);

						$fields = $USER_FIELD_MANAGER->GetUserFields('CRM_'.$arDocumentID['TYPE']);
						$ufId = isset($fields[$fieldNameName]) ? $fields[$fieldNameName]['ID'] : null;

						$arUserField = array(
							'ID' => $ufId,
							'ENTITY_ID' => 'CRM_'.$arDocumentID['TYPE'],
							'FIELD_NAME' => $arFieldName['Field'],
							'USER_TYPE_ID' => $sType,
							'SORT' => 100,
							'MULTIPLE' => $arFieldType['Multiple'] ? 'Y' : 'N',
							'MANDATORY' => $arFieldType['Required'] ? 'Y' : 'N',
							'EDIT_IN_LIST' => 'Y',
							'EDIT_FORM_LABEL' => $arUserFieldType['DESCRIPTION'],
							'VALUE' => $value1,
							'USER_TYPE' => $arUserFieldType,
							'SETTINGS' => array(),
							'ENTITY_VALUE_ID' => 1,
						);

						if ($arFieldType['Type'] == 'UF:boolean' && ($arUserField['VALUE'] == "Y" || $arUserField['VALUE'] == "N"))
							$arUserField['VALUE'] = ($arUserField['VALUE'] == "Y") ? 1 : 0;

						if (
							$arFieldType['Type'] == 'UF:iblock_element' ||
							$arFieldType['Type'] == 'UF:iblock_section' ||
							$arFieldType['Type'] == 'UF:crm_status' ||
							$arFieldType['Type'] == 'UF:boolean'
						)
						{
							$options = $arFieldType['Options'];
							if(is_string($options))
							{
								if ($arFieldType['Type'] == 'UF:crm_status')
								{
									$arUserField['SETTINGS']['ENTITY_TYPE'] = $options;
								}
								else
								{
									$arUserField['SETTINGS']['IBLOCK_ID'] = $options;
								}
							}
							elseif(is_array($options))
							{
								$arUserField['SETTINGS']= $options;
							}
						}
						elseif ($arFieldType['Type'] == 'UF:crm')
						{
							$arUserField['SETTINGS'] = $arFieldType['Options'];
							if (empty($arUserField['SETTINGS']))
								$arUserField['SETTINGS'] = array('LEAD' => 'Y', 'CONTACT' => 'Y', 'COMPANY' => 'Y', 'DEAL' => 'Y');
						}

						$APPLICATION->IncludeComponent(
							'bitrix:system.field.edit',
							$sType,
							array(
								'arUserField' => $arUserField,
								'bVarsFromForm' => false,
								'form_name' => $arFieldName['Form'],
								'FILE_MAX_HEIGHT' => 400,
								'FILE_MAX_WIDTH' => 400,
								'FILE_SHOW_POPUP' => true,
							),
							false,
							array('HIDE_ICONS' => 'Y')
						);
					}
					else
					{
						switch ($arFieldType['Type'])
						{
							case 'int':
								unset($fieldValueTmp[$key]);
								?><input type="text" size="10" id="<?=$fieldNameId?>" name="<?=$fieldNameName?>" value="<?=htmlspecialcharsbx($value)?>"><?
								break;
							case 'file':
								if ($publicMode)
								{
									//unset($fieldValueTmp[$key]);
									?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
								}
								break;
							case 'bool':
								if (in_array($value, array('Y', 'N')))
									unset($fieldValueTmp[$key]);
								?>
								<select id='<?= $fieldNameId ?>' name='<?= $fieldNameName ?>'>
									<?
									if (!$arFieldType['Required'])
										echo '<option value="">['.GetMessage("BPVDX_NOT_SET").']</option>';
									?>
									<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPVDX_YES") ?></option>
									<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPVDX_NO") ?></option>
								</select>
								<?
								break;
							case "date":
							case "datetime":
								$v = "";
								if (!CBPDocument::IsExpression(trim($value)))
								{
									$v = $value;
									unset($fieldValueTmp[$key]);
								}

								$APPLICATION->IncludeComponent(
									'bitrix:main.calendar',
									'',
									array(
										'SHOW_INPUT' => 'Y',
										'FORM_NAME' => $arFieldName['Form'],
										'INPUT_NAME' => $fieldNameName,
										'INPUT_VALUE' => $v,
										'SHOW_TIME' => $arFieldType['Type'] === 'datetime' ? 'Y' : 'N',
									),
									false,
									array('HIDE_ICONS' => 'Y')
								);
								break;
							case 'text':
								unset($fieldValueTmp[$key]);
								?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
								break;
							default:
								unset($fieldValueTmp[$key]);
								?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
						}
					}

					if ($bAllowSelection)
					{
						if (!in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) && (mb_strpos($arFieldType['Type'], 'UF:') !== 0))
						{
							?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= $arFieldType["BaseType"] ?>');"><?
						}
					}

					if ($arFieldType['Multiple'])
						echo '</td></tr>';
				}
			}

			if ($arFieldType['Multiple'])
				echo '</table>';

			if (
				$arFieldType["Multiple"] && (($arFieldType["Type"] != "file") || $publicMode)
				&& $arFieldType["Type"] !== 'UF:date'
				&& $arFieldType["Type"] !== 'UF:iblock_element'
			)
			{
				echo '<input type="button" value="'.GetMessage("BPVDX_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\')"/><br />';
			}

			if ($bAllowSelection)
			{
				if (in_array($arFieldType['Type'], array('file', 'bool', "date", "datetime")) || (mb_strpos($arFieldType['Type'], 'UF:') === 0))
				{
					?>
					<input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>');">
					<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	public static function GetFieldInputControlOptions($documentType, &$arFieldType, $jsFunctionName, &$value)
	{
		$result = '';
		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
			$arDocumentFieldTypes[$documentType] = static::GetDocumentFieldTypes($documentType);

		if (!array_key_exists($arFieldType['Type'], $arDocumentFieldTypes[$documentType])
			|| !$arDocumentFieldTypes[$documentType][$arFieldType['Type']]['Complex'])
		{
			return '';
		}

		if ($arFieldType['Type'] == 'UF:iblock_element' || $arFieldType['Type'] == 'UF:iblock_section')
		{
			if (is_array($value))
			{
				reset($value);
				$valueTmp = intval(current($value));
			}
			else
				$valueTmp = intval($value);

			$iblockId = 0;
			if ($valueTmp > 0)
			{
				$dbResult = CIBlockElement::GetList(array(), array(($arFieldType['Type'] == 'UF:iblock_section' ? 'SECTION_ID' : 'ID') => $valueTmp), false, false, array('ID', 'IBLOCK_ID'));
				if ($arResult = $dbResult->Fetch())
					$iblockId = $arResult['IBLOCK_ID'];
			}

			if ($iblockId <= 0 && intval($arFieldType['Options']) > 0)
				$iblockId = intval($arFieldType['Options']);

			$defaultIBlockId = 0;

			$result .= '<select id="WFSFormOptionsX" onchange="'.$jsFunctionName.'(this.options[this.selectedIndex].value)">';
			$arIBlockType = CIBlockParameters::GetIBlockTypes();
			foreach ($arIBlockType as $iblockTypeId => $iblockTypeName)
			{
				$result .= '<optgroup label="'.$iblockTypeName.'">';
				$dbIBlock = CIBlock::GetList(array('SORT' => 'ASC'), array('TYPE' => $iblockTypeId, 'ACTIVE' => 'Y'));
				while ($arIBlock = $dbIBlock->GetNext())
				{
					$result .= '<option value="'.$arIBlock['ID'].'"'.(($arIBlock['ID'] == $iblockId) ? ' selected="selected"' : '').'>'.$arIBlock['NAME'].'</option>';
					if (($defaultIBlockId <= 0) || ($arIBlock['ID'] == $iblockId))
						$defaultIBlockId = $arIBlock['ID'];
				}

				$result .= '</optgroup>';
			}
			$result .= '</select><!--__defaultOptionsValue:'.$defaultIBlockId.'--><!--__modifyOptionsPromt:'.GetMessage('CRM_DOCUMENT_IBLOCK').'-->';

			$arFieldType['Options'] = $defaultIBlockId;
		}
		else if ($arFieldType['Type'] == 'UF:crm_status')
		{
			$statusID = $arFieldType['Options'];
			$arEntityTypes = CCrmStatus::GetEntityTypes();
			$default = 'STATUS';
			$result .= '<select id="WFSFormOptionsX" onchange="'.$jsFunctionName.'(this.options[this.selectedIndex].value)">';
			foreach ($arEntityTypes as $arEntityType)
			{
				$result .= '<option value="'.$arEntityType['ID'].'"'.(($arEntityType['ID'] == $statusID) ? ' selected="selected"' : '').'>'.htmlspecialcharsbx($arEntityType['NAME']).'</option>';
				if ($arEntityType['ID'] == $statusID)
					$default = $arEntityType['ID'];
			}
			$result .= '</select><!--__defaultOptionsValue:'.$default.'--><!--__modifyOptionsPromt:'.GetMessage('CRM_DOCUMENT_CRM_STATUS').'-->';
		}
		else if ($arFieldType['Type'] == 'UF:crm')
		{
			$settings = $arFieldType['Options'] ?? null;
			if (empty($settings))
			{
				$settings = \Bitrix\Crm\Integration\BizProc\FieldType\Crm::getDefaultFieldSettings();
			}
			$settings['buttonLabel'] = GetMessage('CRM_DOCUMENT_CRM_ENTITY_OK');
			$htmlPieces = \Bitrix\Crm\Integration\BizProc\FieldType\Crm::renderSettingsHtmlPieces($jsFunctionName, $settings);
			$result .= $htmlPieces['inputs'];
			$result .= $htmlPieces['button'];
			$result .= "<script>\n" . $htmlPieces['collectSettingsFunction'] . "\n</script>";
			$result .= '<!--__modifyOptionsPromt:'.GetMessage('CRM_DOCUMENT_CRM_ENTITY').'-->';
		}
		elseif ($arFieldType["Type"] == "select")
		{
			$valueTmp = $arFieldType["Options"];
			if (!is_array($valueTmp))
				$valueTmp = array($valueTmp => $valueTmp);

			$str = '';
			foreach ($valueTmp as $k => $v)
			{
				if (is_array($v) && count($v) == 2)
				{
					$v1 = array_values($v);
					$k = $v1[0];
					$v = $v1[1];
				}

				if ($k != $v)
					$str .= '['.$k.']'.$v;
				else
					$str .= $v;

				$str .= "\n";
			}
			$result .= '<textarea id="WFSFormOptionsX" rows="5" cols="30">'.htmlspecialcharsbx($str).'</textarea><br />';
			$result .= GetMessage("IBD_DOCUMENT_XFORMOPTIONS1").'<br />';
			$result .= GetMessage("IBD_DOCUMENT_XFORMOPTIONS2").'<br />';
			$result .= '<script>
				function WFSFormOptionsXFunction()
				{
					var result = {};
					var i, id, val, str = document.getElementById("WFSFormOptionsX").value;

					var arr = str.split(/[\r\n]+/);
					var p, re = /\[([^\]]+)\].+/;
					for (i in arr)
					{
						str = arr[i].replace(/^\s+|\s+$/g, \'\');
						if (str.length > 0)
						{
							id = str.match(re);
							if (id)
							{
								p = str.indexOf(\']\');
								id = id[1];
								val = str.substr(p + 1);
							}
							else
							{
								val = str;
								id = val;
							}
							result[id] = val;
						}
					}

					return result;
				}
				</script>';
			$result .= '<input type="button" onclick="'.htmlspecialcharsbx($jsFunctionName).'(WFSFormOptionsXFunction())" value="'.GetMessage("IBD_DOCUMENT_XFORMOPTIONS3").'">';
		}

		return $result;
	}

	public static function GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, &$arErrors)
	{
		if (mb_strpos($documentType, '_') === false)
			$documentType .= '_0';

		$arDocumentID = static::GetDocumentInfo($documentType);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$result = array();

		if ($arFieldType["Type"] == "user")
		{
			$value = array_key_exists($arFieldName["Field"], $arRequest) ? $arRequest[$arFieldName["Field"]] : '';
			if ($value !== '')
			{
				$arErrorsTmp1 = array();
				$result = CBPHelper::UsersStringToArray($value, $arDocumentID["DOCUMENT_TYPE"], $arErrorsTmp1);
				if (count($arErrorsTmp1) > 0)
				{
					foreach ($arErrorsTmp1 as $e)
						$arErrors[] = $e;
				}
			}
			elseif(array_key_exists($arFieldName["Field"]."_text", $arRequest))
			{
				$result[] = $arRequest[$arFieldName["Field"]."_text"];
			}
		}
		elseif (array_key_exists($arFieldName["Field"], $arRequest) || array_key_exists($arFieldName["Field"]."_text", $arRequest))
		{
			$arValue = array();
			if (array_key_exists($arFieldName["Field"], $arRequest))
			{
				$arValue = $arRequest[$arFieldName["Field"]];
				if (!is_array($arValue) || is_array($arValue) && CBPHelper::IsAssociativeArray($arValue))
					$arValue = array($arValue);
			}
			if (array_key_exists($arFieldName["Field"]."_text", $arRequest))
				$arValue[] = $arRequest[$arFieldName["Field"]."_text"];

			foreach ($arValue as $value)
			{
				if (is_array($value) || !is_array($value) && !CBPDocument::IsExpression(trim($value)))
				{
					if ($arFieldType['Type'] == 'email' || $arFieldType['Type'] == 'im' || $arFieldType['Type'] == 'web' || $arFieldType['Type'] == 'phone')
					{
						if (is_array($value))
						{
							$keys1 = array_keys($value);
							foreach ($keys1 as $key1)
							{
								if (is_array($value[$key1]))
								{
									$keys2 = array_keys($value[$key1]);
									foreach ($keys2 as $key2)
									{
										if (!isset($value[$key1][$key2]["VALUE"]) || empty($value[$key1][$key2]["VALUE"]))
											unset($value[$key1][$key2]);
									}
									if (count($value[$key1]) <= 0)
										unset($value[$key1]);
								}
								else
								{
									unset($value[$key1]);
								}
							}
							if (count($value) <= 0)
								$value = null;
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "int")
					{
						if ($value <> '')
						{
							$value = str_replace(" ", "", $value);
							if ($value."|" == intval($value)."|")
							{
								$value = intval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID1"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "double")
					{
						if ($value <> '')
						{
							$value = str_replace(" ", "", str_replace(",", ".", $value));
							if (is_numeric($value))
							{
								$value = doubleval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID11"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "select")
					{
						if (!is_array($arFieldType["Options"]) || count($arFieldType["Options"]) <= 0 || $value == '')
						{
							$value = null;
						}
						else
						{
							$ar = array_values($arFieldType["Options"]);
							if (is_array($ar[0]))
							{
								$b = false;
								foreach ($ar as $a)
								{
									if ($a[0] == $value)
									{
										$b = true;
										break;
									}
								}
								if (!$b)
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID35"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								if (!array_key_exists($value, $arFieldType["Options"]))
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID35"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
						}
					}
					elseif ($arFieldType["Type"] == "bool")
					{
						if ($value !== "Y" && $value !== "N")
						{
							if ($value === true)
							{
								$value = "Y";
							}
							elseif ($value === false)
							{
								$value = "N";
							}
							elseif ($value <> '')
							{
								$value = mb_strtolower($value);
								if (in_array($value, array("y", "yes", "true", "1")))
								{
									$value = "Y";
								}
								elseif (in_array($value, array("n", "no", "false", "0")))
								{
									$value = "N";
								}
								else
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID45"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								$value = null;
							}
						}
					}
					elseif ($arFieldType["Type"] == "file")
					{
						if (is_array($value) && array_key_exists("name", $value) && $value["name"] <> '')
						{
							if (!array_key_exists("MODULE_ID", $value) || $value["MODULE_ID"] == '')
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::SaveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID915"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif (mb_strpos($arFieldType["Type"], ":") !== false)
					{
						$customTypeID = str_replace('UF:', '', $arFieldType['Type']);
						$arCustomType = $GLOBALS["USER_FIELD_MANAGER"]->GetUserType($customTypeID);

						if($customTypeID === 'crm' && $value === '')
						{
							//skip empty crm entity references
							$value = null;
						}
						elseif ($value !== null && $arCustomType && array_key_exists("CheckFields", $arCustomType))
						{
							$arErrorsTmp1 = call_user_func_array(
								$arCustomType["CheckFields"],
								array(
									array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
									array("VALUE" => $value),
								)
							);
							if (count($arErrorsTmp1) > 0)
							{
								$value = null;
								foreach ($arErrorsTmp1 as $e)
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => $e,
										"parameter" => $arFieldName["Field"],
									);
							}
						}

						if (!is_array($value) && ($value == '') || is_array($value) && (count($value) == 0 || count($value) == 1 && isset($value["VALUE"]) && !is_array($value["VALUE"]) && $value["VALUE"] == ''))
							$value = null;
					}
					else
					{
						if (!is_array($value) && $value == '')
							$value = null;
					}
				}

				if ($value !== null)
					$result[] = $value;
			}
		}

		$qty = count($result);
		if($arFieldType["Type"] === "UF:boolean")
		{
			//Boolean is not multiple. Last value is actual.
			$result = $qty > 0 ? $result[$qty - 1] : null;
		}
		elseif($arFieldType["Type"] === "UF:disk_file")
		{
			$result = array_unique($result);
		}
		elseif(!$arFieldType["Multiple"])
		{
			$result = $qty > 0 ? $result[$qty - 1] : null;
		}

		return $result;
	}

	public static function GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue)
	{
		return static::PreparePrintableValue(static::GetDocumentInfo($documentType.'_0'), '', $arFieldType, $fieldValue);
	}

	public static function GetFieldValuePrintable($documentId, $fieldName, $fieldType, $fieldValue, $arFieldType)
	{
		return static::PreparePrintableValue(static::GetDocumentInfo($documentId), $fieldName, $arFieldType, $fieldValue);
	}

	protected static function PreparePrintableValue($arDocumentID, $fieldName, $arFieldType, $fieldValue)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$result = $fieldValue;
		switch ($arFieldType['Type'])
		{
			case 'date':
			case 'datetime':
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $_fieldValue)
						$result[] = !empty($_fieldValue) ? FormatDate('x', MakeTimeStamp($_fieldValue)) : '';
				}
				else
					$result = !empty($fieldValue) ? FormatDate('x', MakeTimeStamp($fieldValue)) : '';
				break;

			case 'user':
				if (!is_array($fieldValue))
					$fieldValue = array($fieldValue);

				$result = CBPHelper::UsersArrayToString($fieldValue, null, $arDocumentID["DOCUMENT_TYPE"]);
				break;

			case 'bool':
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
						$result[] = ((mb_strtoupper($r) != "N" && !empty($r)) ? GetMessage('BPVDX_YES') : GetMessage('BPVDX_NO'));
				}
				else
				{
					$result = ((mb_strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? GetMessage('BPVDX_YES') : GetMessage('BPVDX_NO'));
				}
				break;

			case 'file':
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$r."&h=".md5($arImg["SUBDIR"])."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$dbImg = CFile::GetByID($fieldValue);
					if ($arImg = $dbImg->Fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$fieldValue."&h=".md5($arImg["SUBDIR"])."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;

			case 'UF:url':
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$result[] = sprintf('[url=%s]%s[/url]', $r, $r);
					}
				}
				else
				{
					$result = sprintf('[url=%s]%s[/url]', $fieldValue, $fieldValue);
				}
				return $result;
			break;

			case 'select':
				if (is_array($arFieldType["Options"]))
				{
					if (is_array($fieldValue))
					{
						$result = array();
						foreach ($fieldValue as $r)
						{
							if (array_key_exists($r, $arFieldType["Options"]))
								$result[] = $arFieldType["Options"][$r];
						}
					}
					else
					{
						if (array_key_exists($fieldValue, $arFieldType["Options"]))
							$result = $arFieldType["Options"][$fieldValue];
					}
				}

				break;
			case 'web':
			case 'im':
			case 'email':
			case 'phone':
					$result = array();

					if (is_array($fieldValue) && !CBPHelper::IsAssociativeArray($fieldValue))
						$fieldValue = $fieldValue[0];

					if (is_array($fieldValue) && is_array($fieldValue[mb_strtoupper($arFieldType['Type'])]))
					{
						foreach ($fieldValue[mb_strtoupper($arFieldType['Type'])] as $val)
						{
							if (!empty($val))
								$result[] = CCrmFieldMulti::GetEntityNameByComplex(mb_strtoupper($arFieldType['Type']).'_'.$val['VALUE_TYPE'], false).': '.$val['VALUE'];
						}
					}
				break;
		}

		if (mb_strpos($arFieldType['Type'], 'UF:') === 0)
		{
			$sType = str_replace('UF:', '', $arFieldType['Type']);
			if($sType === 'crm')
			{
				$options = isset($arFieldType['Options']) && is_array($arFieldType['Options'])
						? $arFieldType['Options'] : array();
				$defaultTypeName = '';
				foreach($options as $typeName => $flag)
				{
					if($flag === 'Y')
					{
						$defaultTypeName = $typeName;
						break;
					}
				}

				if($defaultTypeName === '')
				{
					$defaultTypeName = 'LEAD';
				}

				if(isset($arFieldType['Multiple']) && $arFieldType['Multiple'] > 0 && is_array($fieldValue))
				{
					$result = array();
					foreach($fieldValue as $value)
					{
						$result[] = static::PrepareCrmUserTypeValueView($value, $defaultTypeName);
					}
				}
				else
				{
					$result = static::PrepareCrmUserTypeValueView($fieldValue, $defaultTypeName);
				}
			}
			else
			{
				$arUserFieldType = $USER_FIELD_MANAGER->GetUserType($sType);
				$arUserField = array(
					'ENTITY_ID' => 'CRM_LEAD',
					'FIELD_NAME' => 'UF_XXXXXXX',
					'USER_TYPE_ID' => $sType,
					'SORT' => 100,
					'MULTIPLE' => $arFieldType['Multiple'] ? 'Y' : 'N',
					'MANDATORY' => $arFieldType['Required'] ? 'Y' : 'N',
					'EDIT_FORM_LABEL' => $arUserFieldType['DESCRIPTION'],
					'VALUE' => $fieldValue,
					'USER_TYPE' => $arUserFieldType,
				);
				if ($arFieldType['Type'] == 'UF:iblock_element' || $arFieldType['Type'] == 'UF:iblock_section')
				{
					if (is_array($arFieldType['Options']))
					{
						$arUserField['SETTINGS'] = $arFieldType['Options'];
					}
					else
					{
						$arUserField['SETTINGS']['IBLOCK_ID'] = $arFieldType['Options'];
					}
				}
				elseif ($arFieldType['Type'] == 'UF:crm_status')
					$arUserField['SETTINGS']['ENTITY_TYPE'] = $arFieldType['Options'];
				elseif ($arFieldType['Type'] == 'UF:boolean' && ($fieldValue === 'Y' || $fieldValue === 'N'))
				{
					//Convert bizproc boolean values (Y/N) in to UF boolean values (1/0)
					$arUserField['VALUE'] = $fieldValue = ($fieldValue === 'Y') ? 1 : 0;
				}

				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:system.field.view',
					$sType,
					array(
						'arUserField' => $arUserField,
						'bVarsFromForm' => false,
						'form_name' => "",
						'printable' => true,
						'FILE_MAX_HEIGHT' => 400,
						'FILE_MAX_WIDTH' => 400,
						'FILE_SHOW_POPUP' => true,
					),
					false,
					array('HIDE_ICONS' => 'Y')
				);
				$result = ob_get_contents();
				$result = HTMLToTxt($result);
				ob_end_clean();
			}
		}
		return $result;
	}

	public static function GetGUIFieldEdit($documentType, $formName, $fieldName, $fieldValue, $arDocumentField = null, $bAllowSelection = false)
	{
		return static::GetFieldInputControl(
			$documentType,
			$arDocumentField,
			array('Form' => $formName, 'Field' => $fieldName),
			$fieldValue,
			$bAllowSelection
		);
	}

	public static function SetGUIFieldEdit($documentType, $fieldName, $arRequest, &$arErrors, $arDocumentField = null)
	{
		return static::GetFieldInputValue($documentType, $arDocumentField, array('Field' => $fieldName), $arRequest, $arErrors);
	}

	public static function GetJSFunctionsForFields()
	{
		return '';
	}

	public static function GetDocumentAdminPage($documentId)
	{
		$arDocumenInfo = static::GetDocumentInfo($documentId);
		if (empty($arDocumenInfo))
			return null;

		$entityTypeName = isset($arDocumenInfo['TYPE']) ? $arDocumenInfo['TYPE'] : '';
		$entityTypeID = $entityTypeName !== '' ? CCrmOwnerType::ResolveID($entityTypeName) : CCrmOwnerType::Undefined;
		$entityID = isset($arDocumenInfo['ID']) ? intval($arDocumenInfo['ID']) : 0;

		return $entityTypeID !== CCrmOwnerType::Undefined && $entityID > 0
			? CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID, false) : null;
	}

	public static function GetDocument($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		$entityTypeId = CCrmOwnerType::ResolveID($arDocumentID['TYPE']);
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);

		if (isset($factory))
		{
			return new Crm\Integration\BizProc\Document\ValueCollection\Item(
				$entityTypeId,
				$arDocumentID['ID']
			);
		}

		return null;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Integration\BizProc\Document\ValueCollection\Base::loadAddressValues
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @param array $documentFields
	 * @return array
	 */
	protected static function fillDocumentAddressFields(int $entityTypeId, int $entityId, array $documentFields): array
	{
		return $documentFields;
	}

	public static function externalizeEnumerationField(array &$fields, $name)
	{
		$value = isset($fields[$name]) ? $fields[$name] : null;
		$valueInfos = array();
		if(!empty($value))
		{
			$dbRes = CUserFieldEnum::GetList([], ['ID' => $value]);
			while($valueData = $dbRes->Fetch())
			{
				$valueInfos[] = array('NAME' => $valueData['XML_ID'], 'LABEL' => $valueData['VALUE']);
			}
		}

		$valueInfoQty = count($valueInfos);
		if($valueInfoQty === 0)
		{
			$fields[$name] = $fields["{$name}_PRINTABLE"] = '';
		}
		elseif($valueInfoQty === 1)
		{
			$valueInfo = $valueInfos[0];
			$fields[$name] = $valueInfo['NAME'];
			$fields["{$name}_PRINTABLE"] = $valueInfo['LABEL'];
		}
		else
		{
			$names = array();
			$labels = array();
			foreach($valueInfos as &$valueInfo)
			{
				$names[] = $valueInfo['NAME'];
				$labels[] = $valueInfo['LABEL'];
			}
			unset($valueInfo);

			$fields[$name] = $names;
			$fields["{$name}_PRINTABLE"] = implode(', ', $labels);
		}
	}

	protected static function InternalizeEnumerationField($entityTypeID, array &$fields, $name)
	{
		if(!isset($fields[$name]))
		{
			return;
		}

		$entityResult = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => $entityTypeID, "FIELD_NAME" => $name));
		$entity = $entityResult->Fetch();
		if(!is_array($entity))
		{
			return;
		}

		$isMultiple = isset($entity['MULTIPLE']) && $entity['MULTIPLE'] === 'Y';

		$enumXMap = array();
		$enumVMap = array();
		$enumResult = CUserTypeEnum::GetList($entity);
		while ($enum = $enumResult->GetNext())
		{
			$enumXMap[$enum["XML_ID"]] = $enum["ID"];
			$enumVMap[$enum["VALUE"]] = $enum["ID"];
		}

		$results = array();
		if(is_array($fields[$name]))
		{
			foreach($fields[$name] as $value)
			{
				if(CBPHelper::IsAssociativeArray($value))
				{
					//HACK: For IBlockDocument
					$value = array_keys($value);
					if(!$isMultiple)
					{
						$value = array_shift($value);
					}
				}

				if(is_array($value))
				{
					foreach($value as $v)
					{
						if(isset($enumXMap[$v]))
						{
							$results[] = $enumXMap[$v];
						}
						elseif(isset($enumVMap[$v]))
						{
							$results[] = $enumVMap[$v];
						}
					}
				}
				elseif(isset($enumXMap[$value]))
				{
					$results[] = $enumXMap[$value];
				}
				elseif(isset($enumVMap[$value]))
				{
					$results[] = $enumVMap[$value];
				}
			}
		}
		elseif(isset($enumXMap[$fields[$name]]))
		{
			$results[] = $enumXMap[$fields[$name]];
		}
		elseif(isset($enumVMap[$fields[$name]]))
		{
			$results[] = $enumVMap[$fields[$name]];
		}

		$results = array_unique($results);

		if(!empty($results))
		{
			$fields[$name] = $isMultiple ? $results : $results[0];
		}
		else
		{
			//Set "empty" value
			$fields[$name] = $isMultiple ? [] : null;
		}
	}

	/**
	 * @deprecated
	 * @param $documentId
	 * @param $historyIndex
	 * @return array
	 */
	public static function GetDocumentForHistory($documentId, $historyIndex)
	{
		return [];
	}

	/**
	 * @deprecated
	 * @param $documentId
	 * @param $arDocument
	 * @return bool
	 */
	public static function RecoverDocumentFromHistory($documentId, $arDocument)
	{
		return true;
	}

	public static function LockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function UnlockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function IsDocumentLocked($documentId, $workflowId)
	{
		return false;
	}

	protected static function PrepareUserGroups($userId)
	{
		$userId = intval($userId);
		if(!isset(self::$USER_GROUPS[$userId]))
		{
			self::$USER_GROUPS[$userId] = CUser::GetUserGroup($userId);
		}
		return self::$USER_GROUPS[$userId];
	}

	protected static function ResolvePermissionEntity(array $documentID, array $parameters = array())
	{
		$entityTypeName = isset($documentID['TYPE']) ? $documentID['TYPE'] : '';
		$entityID = isset($documentID['ID']) ? (int)$documentID['ID'] : 0;

		$operationParams = array();
		// old school deal way, for back compatibility
		if($entityTypeName === CCrmOwnerType::DealName && isset($parameters['DealCategoryId']))
		{
			$operationParams['CATEGORY_ID'] = (int)$parameters['DealCategoryId'];
			return CCrmPerms::ResolvePermissionEntityType($entityTypeName, $entityID, $operationParams);
		}

		// modern way after bizproc new version, where category passed always
		if (isset($parameters['DocumentCategoryId']))
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			if ($entityTypeId > 0)
			{
				return Service\UserPermissions::getPermissionEntityType($entityTypeId, (int)$parameters['DocumentCategoryId']);
			}
		}

		// universal way where category determined based on $entityID
		return CCrmPerms::ResolvePermissionEntityType($entityTypeName, $entityID, $operationParams);
	}

	public static function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$userId = intval($userId);

		$key = "{$documentId}_{$userId}_{$operation}";
		if(isset(self::$USER_PERMISSION_CHECK[$key]))
		{
			return self::$USER_PERMISSION_CHECK[$key];
		}

		if (!array_key_exists('AllUserGroups', $arParameters))
		{
			if (!array_key_exists('UserGroups', $arParameters))
			{
				$arParameters['UserGroups'] = static::PrepareUserGroups($userId);
				if (!array_key_exists('CreatedBy', $arParameters))
				{
					$responsibleID = CCrmOwnerType::GetResponsibleID(
						CCrmOwnerType::ResolveID($arDocumentID['TYPE']),
						$arDocumentID['ID'],
						false
					);
					if($responsibleID <= 0)
					{
						self::$USER_PERMISSION_CHECK[$key] = false;
						return false;
					}
					$arParameters['CreatedBy'] = $responsibleID;
				}
			}

			$arParameters['AllUserGroups'] = $arParameters['UserGroups'];
			if (isset($arParameters['CreatedBy']) && $userId == $arParameters['CreatedBy'])
			{
				$arParameters['AllUserGroups'][] = 'Author';
			}
		}

		if ((isset($arParameters['UserIsAdmin']) && $arParameters['UserIsAdmin'] === true)
			|| in_array(1, $arParameters['AllUserGroups']))
		{
			self::$USER_PERMISSION_CHECK[$key] = true;
			return true;
		}

		$permissionEntity = static::ResolvePermissionEntity($arDocumentID, $arParameters);
		$userPermissions = CCrmPerms::GetUserPermissions($userId);
		if ($arDocumentID['ID'] > 0)
		{
			$entityAttrs = isset($arParameters['CRMEntityAttr']) && is_array($arParameters['CRMEntityAttr']) && !empty($arParameters['CRMEntityAttr'])
				? $arParameters['CRMEntityAttr'] : null;

			if($operation == CBPCanUserOperateOperation::ViewWorkflow
				|| $operation == CBPCanUserOperateOperation::ReadDocument)
			{
				$result = CCrmAuthorizationHelper::CheckReadPermission($permissionEntity, $arDocumentID['ID'], $userPermissions, $entityAttrs);
			}
			else
			{
				$result = CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntity, $arDocumentID['ID'], $userPermissions, $entityAttrs);
			}
		}
		else
		{
			$result = CCrmAuthorizationHelper::CheckCreatePermission($permissionEntity, $userPermissions);
		}

		self::$USER_PERMISSION_CHECK[$key] = $result;
		return $result;
	}

	public static function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$arDocumentID = static::GetDocumentInfo($documentType . '_0');
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		$userId = intval($userId);
		if (!array_key_exists('AllUserGroups', $arParameters))
		{
			if (!array_key_exists('UserGroups', $arParameters))
			{
				$arParameters['UserGroups'] = static::PrepareUserGroups($userId);
			}

			$arParameters['AllUserGroups'] = $arParameters['UserGroups'];
			$arParameters['AllUserGroups'][] = 'Author';
		}

		if (array_key_exists('UserIsAdmin', $arParameters) && $arParameters['UserIsAdmin'] === true)
		{
			return true;
		}
		elseif (in_array(1, $arParameters['AllUserGroups']))
		{
			return true;
		}

		$permissionEntity = static::ResolvePermissionEntity($arDocumentID, $arParameters);
		$userPermissions = CCrmPerms::GetUserPermissions($userId);
		$entityTypeId = CCrmOwnerType::ResolveID($documentType);

		if (
			$operation == \CBPCanUserOperateOperation::CreateWorkflow
			|| $operation === CBPCanUserOperateOperation::DebugAutomation
		)
		{
			return \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions);
		}

		if ($operation == \CBPCanUserOperateOperation::CreateAutomation)
		{
			$categoryId = (
				isset($arParameters['DocumentCategoryId'])
				&& is_numeric($arParameters['DocumentCategoryId'])
				&& (int)$arParameters['DocumentCategoryId'] >= 0
			)
				? (int)$arParameters['DocumentCategoryId']
				: null;

			return Service\Container::getInstance()->getUserPermissions($userId)->canEditAutomation($entityTypeId, $categoryId);
		}

		if( $operation === CBPCanUserOperateOperation::ViewWorkflow
			|| $operation === CBPCanUserOperateOperation::ReadDocument
		)
		{
			return
				Container::getInstance()
							->getUserPermissions($userId)
							->canReadType(CCrmOwnerType::ResolveID($documentType))
				;
		}

		return CCrmAuthorizationHelper::CheckCreatePermission($permissionEntity, $userPermissions);
	}

	public static function DeleteDocument($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		$CCrmEntity = null;
		switch ($arDocumentID['TYPE'])
		{
			case 'CONTACT':
				$CCrmEntity = new CCrmContact(false);
				break;
			case 'COMPANY':
				$CCrmEntity = new CCrmCompany(false);
				break;
			case 'DEAL':
				$CCrmEntity = new CCrmDeal(false);
				break;
			case 'LEAD':
				$CCrmEntity = new CCrmLead(false);
				break;
		}

		$result = new \Bitrix\Main\Result();

		if($CCrmEntity !== null)
		{
			$deleteResult = $CCrmEntity->Delete(
				$arDocumentID['ID'],
				['CURRENT_USER' => static::getSystemUserId()]
			);

			if ($deleteResult === false && $CCrmEntity->LAST_ERROR !== '')
			{
				$result->addError(new \Bitrix\Main\Error($CCrmEntity->LAST_ERROR));
			}
		}

		return $result;
	}

	public static function PublishDocument($documentId)
	{
		return false;
	}

	public static function UnpublishDocument($documentId)
	{
	}

	public static function GetAllowableOperations($documentType)
	{
		return array();
	}

	public static function GetAllowableUserGroups($documentType)
	{
		$documentType = trim($documentType);
		if ($documentType == '')
			return false;

		$arDocumentID = static::GetDocumentInfo($documentType);
		if ($arDocumentID !== false)
			$documentType = $arDocumentID['TYPE'];

		$arResult = [
			static::GROUP_AUTHOR => GetMessage('CRM_DOCUMENT_AUTHOR'),
			static::GROUP_RESPONSIBLE_HEAD => GetMessage('CRM_DOCUMENT_RESPONSIBLE_HEAD'),
		];

		$arGroupsID = array(1);
		$arUsersID = array();
		$arRelations = CCrmPerms::GetEntityRelations($documentType, BX_CRM_PERM_SELF);
		foreach($arRelations as $relation)
		{
			$preffix = mb_substr($relation, 0, 1);
			if($preffix === 'G')
			{
				$arGroupsID[] = intval(mb_substr($relation, 1));
			}
			elseif($preffix === 'U')
			{
				$arUsersID[] = mb_substr($relation, 1);
			}
		}

		//Crutch for Bitrix24 context (user group management is not supported)
		if(IsModuleInstalled('bitrix24'))
		{
			if (!isset(static::$b24employeeGroupId))
			{
				$siteID = CSite::GetDefSite();
				$dbResult = CGroup::GetList(
					'',
					'',
					[
						'STRING_ID' => 'EMPLOYEES_' . $siteID,
						'STRING_ID_EXACT_MATCH' => 'Y',
					]
				);
				$arEmployeeGroup = $dbResult->fetch();
				static::$b24employeeGroupId = (int) ($arEmployeeGroup['ID'] ?? 0);
			}

			if(!in_array(static::$b24employeeGroupId, $arGroupsID, true))
			{
				$arGroupsID[] = static::$b24employeeGroupId;
			}
		}

		if(!empty($arGroupsID))
		{
			$dbGroupList = CGroup::GetListEx(array('NAME' => 'ASC'), array('ID' => $arGroupsID));
			while ($arGroup = $dbGroupList->Fetch())
			{
				$arResult[$arGroup['ID']] = $arGroup['NAME'];
			}
		}

		if(isset(self::$UNGROUPED_USERS[$documentType]))
		{
			unset(self::$UNGROUPED_USERS[$documentType]);
		}
		self::$UNGROUPED_USERS[$documentType] = $arUsersID;

		if(!empty($arUsersID))
		{
			//Group with empty name will be hidden in group list
			$arResult['ungrouped'] = '';
			//$arResult['ungrouped'] = GetMessage('CRM_DOCUMENT_UNGROUPED_USERS');
		}

		return $arResult;
	}

	public static function GetUsersFromUserGroup($group, $documentId)
	{
		$documentInfo = static::getDocumentInfo($documentId);
		if (empty($documentInfo))
		{
			return [];
		}
		$entityID = isset($documentInfo['ID']) ? intval($documentInfo['ID']) : 0;
		$responsibleId = 0;

		if ($group === static::GROUP_RESPONSIBLE_HEAD || $group === static::GROUP_AUTHOR)
		{
			$responsibleId = \CCrmOwnerType::loadResponsibleId(
				\CCrmOwnerType::ResolveID($documentInfo['TYPE']),
				$entityID,
				false
			);
		}

		$groupLc = mb_strtolower($group);
		if ($group === static::GROUP_RESPONSIBLE_HEAD)
		{
			$userService = \CBPRuntime::GetRuntime()->getUserService();

			return $responsibleId ? $userService->getUserHeads($responsibleId) : [];
		}
		if ($groupLc === static::GROUP_AUTHOR)
		{
			return array_filter([$responsibleId]);
		}
		elseif ($groupLc == 'ungrouped')
		{
			return isset(self::$UNGROUPED_USERS[$documentId]) ? self::$UNGROUPED_USERS[$documentId] : array();
		}

		$group = (int)$group;
		if ($group <= 0)
			return array();

		$arResult = array();
		$dbUsersList = CUser::GetList(
			'ID',
			'ASC',
			['GROUPS_ID' => $group, 'ACTIVE' => 'Y', 'IS_REAL_USER' => true],
			['FIELDS' => ['ID']]
		);

		while ($arUser = $dbUsersList->Fetch())
		{
			$arResult[] = $arUser['ID'];
		}

		return $arResult;
	}

	public static function GetDocumentType($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		if (!static::isDocumentExists($documentId))
		{
			throw new \Bitrix\Main\ArgumentException(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));
		}

		return $arDocumentID['TYPE'];
	}

	public static function isDocumentExists($documentId): bool
	{
		$documentInfo = static::getDocumentInfo($documentId);
		if (empty($documentInfo))
		{
			return false;
		}

		$exists = false;
		switch ($documentInfo['TYPE'])
		{
			case 'CONTACT':
				$exists = CCrmContact::Exists($documentInfo['ID']);
				break;
			case 'COMPANY':
				$exists = CCrmCompany::Exists($documentInfo['ID']);
				break;
			case 'DEAL':
				$exists = CCrmDeal::Exists($documentInfo['ID']);
				break;
			case 'LEAD':
				$exists = CCrmLead::Exists($documentInfo['ID']);
				break;
			default:
				$entityTypeId = $documentInfo['TYPE_ID'] ?? 0;
				$factory = Service\Container::getInstance()->getFactory($entityTypeId);
				$item = isset($factory) ? $factory->getItem($documentInfo['ID']) : null;

				$exists = isset($item);
				break;
		}

		return $exists;
	}

	protected static function GetDocumentInfo($documentId)
	{
		$arDocumentId = explode('_', $documentId);

		$cnt = count($arDocumentId);
		if ($cnt < 1)
		{
			return false;
		}
		if ($cnt < 2)
		{
			$arDocumentId[] = 0;
		}

		static $arMap = [
			'LEAD' => "CCrmDocumentLead",
			'CONTACT' => "CCrmDocumentContact",
			'DEAL' => "CCrmDocumentDeal",
			'COMPANY' => "CCrmDocumentCompany",
			'ORDER' => \Bitrix\Crm\Integration\BizProc\Document\Order::class,
			'INVOICE' => \Bitrix\Crm\Integration\BizProc\Document\Invoice::class,
			'ORDER_SHIPMENT' => \Bitrix\Crm\Integration\BizProc\Document\Shipment::class,
		];

		$arDocumentId[0] = mb_strtoupper($arDocumentId[0]);
		if (!isset($arMap[$arDocumentId[0]]))
		{
			return false;
		}

		return array(
			'TYPE' => $arDocumentId[0],
			'ID' => (int) $arDocumentId[1],
			'DOCUMENT_TYPE' => array("crm", $arMap[$arDocumentId[0]], $arDocumentId[0]),
		);
	}

	public static function SetPermissions($documentId, $arPermissions)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');
	}

	public static function AddDocumentField($documentType, $arFields)
	{
		if (mb_strpos($documentType, '_') === false)
			$documentType .= '_0';

		$arDocumentID = static::GetDocumentInfo($documentType);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');


		$userTypeID = $arFields['type'];
		if(mb_strpos($userTypeID, 'UF:') === 0)
		{
			$userTypeID = mb_substr($userTypeID, 3);
		}

		$fieldName = mb_strtoupper($arFields['code']);
		if(mb_strpos($fieldName, 'UF_CRM_') !== 0)
		{
			$fieldName = "UF_CRM_{$fieldName}";
		}

		$userFieldEntityId = CCrmOwnerType::ResolveUserFieldEntityID(CCrmOwnerType::ResolveID($arDocumentID['TYPE']));
		if ($userFieldEntityId === '')
		{
			$userFieldEntityId = 'CRM_' . $arDocumentID['TYPE'];
		}

		$arFieldsTmp = array(
			'USER_TYPE_ID' => $userTypeID,
			'FIELD_NAME' => $fieldName,
			'ENTITY_ID' => $userFieldEntityId,
			'SORT' => 150,
			'MULTIPLE' => $arFields['multiple'] == 'Y' ? 'Y' : 'N',
			'MANDATORY' => $arFields['required'] == 'Y' ? 'Y' : 'N',
			'SHOW_FILTER' => 'E',
		);

		$arFieldsTmp['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arFields['name'];
		$arFieldsTmp['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arFields['name'];
		$arFieldsTmp['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arFields['name'];

		if (array_key_exists('additional_type_info', $arFields))
			$arField['SETTINGS']['IBLOCK_ID'] = intval($arFields['additional_type_info']);

		switch ($userTypeID)
		{
			case 'select':
			case 'enumeration':
			{
				$arFieldsTmp['USER_TYPE_ID'] = 'enumeration';

				if(!is_array($arFieldsTmp['LIST']))
					$arFieldsTmp['LIST'] = array();

				$options = isset($arFields['options']) && is_array($arFields['options']) ? $arFields['options'] : array();
				if (empty($options) && !empty($arFields['options']) && is_string($arFields['options']))
				{
					$optionsFromString = explode("\n", $arFields["options"]);
					foreach ($optionsFromString as $option)
					{
						$option = trim(trim($option), "\r\n");
						if (!$option)
							continue;
						$key = $value = $option;
						if (mb_substr($option, 0, 1) == "[" && mb_strpos($option, "]") !== false)
						{
							$key = mb_substr($option, 1, mb_strpos($option, "]") - 1);
							$value = trim(mb_substr($option, mb_strpos($option, "]") + 1));
						}
						$options[$key] = $value;
					}
				}

				if (!empty($options))
				{
					$i = 10;
					foreach ($options as $k => $v)
					{
						$arFieldsTmp['LIST']['n'.$i] = array('XML_ID' => $k, 'VALUE' => $v, 'DEF' => 'N', 'SORT' => $i);
						$i = $i + 10;
					}
				}
				break;
			}
			case 'text':
			{
				$arFieldsTmp['USER_TYPE_ID'] = 'string';
				break;
			}
			case 'bool':
			{
				$arFieldsTmp['USER_TYPE_ID'] = 'boolean';
				break;
			}
			case 'int':
			{
				$arFieldsTmp['USER_TYPE_ID'] = 'integer';
				break;
			}
			case 'double':
			{
				$arFieldsTmp['SETTINGS'] = array('PRECISION' => 2);
				break;
			}
			case 'iblock_section':
			case 'iblock_element':
			{
				$options = isset($arFields['options']) && is_string($arFields['options']) ? $arFields['options'] : '';
				if($options !== '')
				{
					$arFieldsTmp['SETTINGS']['IBLOCK_ID'] = $options;
				}
				break;
			}
			case 'crm_status':
			{
				$options = isset($arFields['options']) && is_string($arFields['options']) ? $arFields['options'] : '';
				if($options !== '')
				{
					$arFieldsTmp['SETTINGS']['ENTITY_TYPE'] = $options;
				}
				break;
			}
			case 'crm':
			{
				$options = isset($arFields['options']) && is_array($arFields['options']) ? $arFields['options'] : array();
				$arFieldsTmp['SETTINGS']['LEAD'] = isset($options['LEAD']) && mb_strtoupper($options['LEAD']) === 'Y' ? 'Y' : 'N';
				$arFieldsTmp['SETTINGS']['CONTACT'] = isset($options['CONTACT']) && mb_strtoupper($options['CONTACT']) === 'Y' ? 'Y' : 'N';
				$arFieldsTmp['SETTINGS']['COMPANY'] = isset($options['COMPANY']) && mb_strtoupper($options['COMPANY']) === 'Y' ? 'Y' : 'N';
				$arFieldsTmp['SETTINGS']['DEAL'] = isset($options['DEAL']) && mb_strtoupper($options['DEAL']) === 'Y' ? 'Y' : 'N';
				break;
			}
			case 'user':
			case 'employee':
			{
				$arFieldsTmp['USER_TYPE_ID'] = 'employee';
				$arFieldsTmp['SHOW_FILTER'] = 'I';
				break;
			}
		}
		$crmFields = new CCrmFields($GLOBALS['USER_FIELD_MANAGER'], 'CRM_'.$arDocumentID['TYPE']);
		$crmFields->AddField($arFieldsTmp);
		$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_fields_list_'.$arFieldsTmp['FIELD_NAME']);

		return $arFieldsTmp['FIELD_NAME'];
	}

	private static  function ExtractEntityMultiFieldData(&$arSrcData, &$arDstData, $defaultValueType)
	{
		if(!is_array($arSrcData))
		{
			return;
		}

		foreach($arSrcData as &$item)
		{
			if(is_string($item))
			{
				$arDstData['n'.(count($arDstData) + 1)] = array(
					'VALUE' => $item,
					'VALUE_TYPE' => $defaultValueType,
				);
			}
			elseif(is_array($item))
			{
				if(isset($item['VALUE']))
				{
					if(is_string($item['VALUE']))
					{
						$arDstData['n'.(count($arDstData) + 1)] = array(
							'VALUE' => $item['VALUE'],
							'VALUE_TYPE' => isset($item['VALUE_TYPE']) ? $item['VALUE_TYPE'] : $defaultValueType,
						);
					}
					elseif(is_array($item['VALUE']))
					{
						self::ExtractEntityMultiFieldData(
							$item['VALUE'],
							$arDstData,
							isset($item['VALUE_TYPE']) ? $item['VALUE_TYPE'] : $defaultValueType
						);
					}
				}
			}
		}
		unset($item);

		return array();
	}
	protected static function PrepareEntityMultiFields(&$arFields, $typeName)
	{
		/*
		--- Var.#1 (invalid) ---
		'PHONE' =>
			array(
				'PHONE' => array(
					'n1' => array(
						'VALUE' => array(
							'n02690' => array(
								'VALUE' => '111',
								'VALUE_TYPE' => 'WORK'
							),
							...
						),
						'VALUE_TYPE' => 'WORK'
					)
				)
			)
		--- Var.#2 (valid) ---
		'PHONE' => array(
			'n02690' => array(
				'VALUE' => '111',
				'VALUE_TYPE' => 'WORK'
			),
			...
		)
		--- Var.#3 (invalid) ---
		'PHONE' => array(
			'PHONE' => array(
				'n1' => array(
					'VALUE' => array(
						'111',
						...
					),
					'VALUE_TYPE' => 'WORK'
				)
			)
		)
		--- Var.#4 (invalid) ---
		'PHONE' => array(
			'111',
			...
		)
		--- Var.#5 (invalid) ---
		'PHONE' => '111'
		)
		--- Var.#6 (invalid) ---
		'PHONE' =>
			array(
				0 => array(
					'PHONE' => array(
						'n1' => array(
							'VALUE' => array(
								'n02690' => array(
									'VALUE' => '111',
									'VALUE_TYPE' => 'WORK'
								),
								...
							),
							'VALUE_TYPE' => 'WORK'
						)
					)
				)
			)
		*/

		if(!isset($arFields[$typeName]))
		{
			return;
		}

		if(!is_array($arFields[$typeName]))
		{
			//Var.#5
			$arFields[$typeName] = array('n1' => array('VALUE' => $arFields[$typeName]));
		}

		$srcData = $arFields[$typeName];
		if(isset($srcData[$typeName]))
		{
			//Var.#1, Var.#3
			$srcData = $srcData[$typeName];
			if(!is_array($srcData))
			{
				return;
			}
		}
		elseif(isset($srcData[0]) && isset($srcData[0][$typeName]))
		{
			//Var.#6
			$srcData = $srcData[0][$typeName];
			if(!is_array($srcData))
			{
				return;
			}
		}


		$dstData = array();
		self::ExtractEntityMultiFieldData($srcData, $dstData, $typeName === 'IM' ? 'OTHER' : 'WORK');
		$arFields['FM'][$typeName] = $dstData;
		unset($arFields[$typeName]);
	}

	public static function prepareEntityMultiFieldsValue(&$fields, $typeName): void
	{
		self::PrepareEntityMultiFields($fields, $typeName);
		if (isset($fields['FM']) && isset($fields['FM'][$typeName]))
		{
			$fields[$typeName] = $fields['FM'][$typeName];
			unset($fields['FM'][$typeName]);
		}
	}

	public static function prepareCrmUserTypeValueView($value, $defaultTypeName = '')
	{
		$parts = explode('_', $value);
		if (count($parts) > 1)
		{
			$entityTypeId = CCrmOwnerType::ResolveID(
				CCrmOwnerTypeAbbr::ResolveName($parts[0] . $parts[1])
				?: CCrmOwnerTypeAbbr::ResolveName($parts[0])
			);
			$entityId = (int)end($parts);
		}
		elseif ($defaultTypeName !== '')
		{
			$entityTypeId = CCrmOwnerType::ResolveID($defaultTypeName);
			$entityId = (int)$value;
		}
		else
		{
			return $value;
		}

		$value = CCrmOwnerType::GetCaption(
			$entityTypeId,
			$entityId,
			false
		);

		return $value;
	}

	public static function GetDocumentAuthorID($documentId)
	{
		if(!is_array($documentId) || count($documentId) < 3)
		{
			return 0;
		}

		$documentInfo = static::GetDocumentInfo($documentId[2]);
		$entityTypeName = isset($documentInfo['TYPE']) ? $documentInfo['TYPE'] : '';
		$entityId = isset($documentInfo['ID']) ? intval($documentInfo['ID']) : 0;

		return CCrmOwnerType::GetResponsibleID(
			CCrmOwnerType::ResolveID($entityTypeName),
			$entityId,
			false
		);
	}

	public static function GetUserGroups($documentType, $documentId, $userId)
	{
		$userId = intval($userId);
		$result = static::PrepareUserGroups($userId);

		if($userId === static::GetDocumentAuthorID($documentId))
		{
			$result[] = static::GROUP_AUTHOR;
		}
		return $result;
	}

	/**
	 * @param string $entity Entity class name.
	 * @return string Entity real name.
	 */
	public static function getEntityName($entity)
	{
		$name = $entity;
		switch ($entity)
		{
			case 'CCrmDocumentCompany':
				$name = GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_COMPANY');
				break;
			case 'CCrmDocumentContact':
				$name = GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_CONTACT');
				break;
			case 'CCrmDocumentDeal':
				$name = GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_DEAL');
				break;
			case 'CCrmDocumentLead':
				$name = GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_LEAD');
				break;
		}

		return $name;
	}

	public static function getDocumentTypeCaption($documentType)
	{
		$typeId = CCrmOwnerType::ResolveID($documentType);
		if ($typeId === CCrmOwnerType::Undefined)
		{
			return '';
		}

		return CCrmOwnerType::GetCategoryCaption($typeId);
	}

	public static function getDocumentDetailUrl(array $parameterDocumentId, array $options = [])
	{
		[$entityTypeId, $id] = CCrmBizProcHelper::resolveEntityId($parameterDocumentId);
		$categoryId = array_key_exists('categoryId', $options) ? $options['categoryId'] : null;

		$url = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $id, $categoryId);
		if ($url === null)
		{
			return '';
		}

		return $url->getUri();
	}

	protected static function getSystemUserId()
	{
		return 0;
	}

	protected static function getWebFormSelectOptions()
	{
		if (self::$webFormSelectList === null)
		{
			self::$webFormSelectList = array();
			$result = \Bitrix\Crm\WebForm\Internals\FormTable::getDefaultTypeList(array(
				'select' => array('ID', 'NAME'),
				'order' => array('NAME' => 'ASC', 'ID' => 'ASC'),
			));
			foreach ($result as $row)
			{
				self::$webFormSelectList[$row['ID']] = $row['NAME'];
			}
		}
		return self::$webFormSelectList;
	}

	/**
	 * @param string $documentId
	 * @param int $taskId
	 * @param array $taskData
	 * @param int $status
	 * @return void
	 */
	public static function onTaskChange(string $documentId, int $taskId, array $taskData, int $status): void
	{
		$taskData['TASK_ID'] = $taskId;
		\Bitrix\Crm\Timeline\Bizproc\Controller::getInstance()
			->onTaskStatusChange(
				new Crm\Timeline\Bizproc\Dto\TaskStatusChangedDto(
					(string)($taskData['WORKFLOW_ID'] ?? ''),
					$status,
					$documentId,
					$taskData,
				)
			);
	}

	/**
	 * @param array $documentId
	 * @param string $workflowId
	 * @param int $authorId
	 * @return void
	 */
	public static function onWorkflowCommentAdded(array $documentId, string $workflowId, int $authorId): void
	{
		\Bitrix\Crm\Timeline\Bizproc\Controller::getInstance()->onCommentStatusChange(
			new Crm\Timeline\Bizproc\Dto\CommentStatusChangedDto(
				$workflowId,
				$documentId,
				$authorId,
				Crm\Timeline\Bizproc\Data\CommentStatus::Created
			)
		);
	}

	/**
	 * @param array $documentId
	 * @param string $workflowId
	 * @param int $authorId
	 * @return void
	 */
	public static function onWorkflowCommentDeleted(array $documentId, string $workflowId, int $authorId): void
	{
		\Bitrix\Crm\Timeline\Bizproc\Controller::getInstance()->onCommentStatusChange(
			new Crm\Timeline\Bizproc\Dto\CommentStatusChangedDto(
				$workflowId,
				$documentId,
				$authorId,
				Crm\Timeline\Bizproc\Data\CommentStatus::Deleted
			)
		);
	}

	/**
	 * @param array $documentId
	 * @param string $workflowId
	 * @param int $authorId
	 * @return void
	 */
	public static function onWorkflowAllCommentViewed(array $documentId, string $workflowId, int $authorId): void
	{
		\Bitrix\Crm\Timeline\Bizproc\Controller::getInstance()->onCommentStatusChange(
			new Crm\Timeline\Bizproc\Dto\CommentStatusChangedDto(
				$workflowId,
				$documentId,
				$authorId,
				Crm\Timeline\Bizproc\Data\CommentStatus::Viewed
			)
		);
	}

	/**
	 * @param string $documentId
	 * @param string $workflowId
	 * @param int $status
	 * @param null|CBPActivity $rootActivity
	 */
	public static function onWorkflowStatusChange($documentId, $workflowId, $status, $rootActivity)
	{
		if (!$rootActivity)
		{
			return;
		}

		if (
			$status === CBPWorkflowStatus::Running
			&& !$rootActivity->workflow->isNew()
			&& !self::isResumeWorkflowAvailable($documentId, $rootActivity->getDocumentEventType())
		)
		{
			throw new \Bitrix\Main\SystemException(GetMessage('CRM_DOCUMENT_RESUME_RESTRICTED'));
		}

		if ($status === CBPWorkflowStatus::Running && $rootActivity->workflow->isNew())
		{
			$status = CBPWorkflowStatus::Created;
		}

		\Bitrix\Crm\Timeline\Bizproc\Controller::getInstance()->onWorkflowStatusChange(
			new Crm\Timeline\Bizproc\Dto\WorkflowStatusChangedDto(
				$workflowId,
				$documentId,
				$rootActivity->getDocumentEventType(),
				(int)$status
			)
		);

		if (
			$rootActivity->getDocumentEventType() === CBPDocumentEventType::Script
			&& (
				$status === CBPWorkflowStatus::Running
				|| $status === CBPWorkflowStatus::Created
			)
		)
		{
			$clientCode = 'bizproc_script_' . ($status === CBPWorkflowStatus::Created ? 'start' : 'execution');
			self::logScriptExecution($rootActivity->getWorkflowTemplateId(), $clientCode);
		}
	}

	public static function onDebugSessionDocumentStatusChanged($documentId, int $userId, string $status)
	{
		if (!class_exists('\Bitrix\Bizproc\Debugger\Session\DocumentStatus'))
		{
			return;
		}

		switch ($status)
		{
			case \Bitrix\Bizproc\Debugger\Session\DocumentStatus::INTERCEPTED:
				$text = Loc::getMessage('CRM_DOCUMENT_AUTOMATION_DEBUG_MESSAGE_INTERCEPTED');
				break;
			case \Bitrix\Bizproc\Debugger\Session\DocumentStatus::REMOVED:
				$text = Loc::getMessage('CRM_DOCUMENT_AUTOMATION_DEBUG_MESSAGE_REMOVED');
				break;
			case \Bitrix\Bizproc\Debugger\Session\DocumentStatus::IN_DEBUG:
				$text = Loc::getMessage('CRM_DOCUMENT_AUTOMATION_DEBUG_MESSAGE_IN_DEBUG');
				break;
			case \Bitrix\Bizproc\Debugger\Session\DocumentStatus::FINISHED:
				$text = Loc::getMessage('CRM_DOCUMENT_AUTOMATION_DEBUG_MESSAGE_FINISHED');
				break;
			default:
				$text = '';
		}

		if ($text)
		{
			\Bitrix\Crm\Timeline\BizprocController::getInstance()
				->onDebugDocumentStatusChange($documentId, $userId, $text)
			;
		}

		return;
	}

	private static function logScriptExecution($tplId, $clientCode): void
	{
		if (
			\Bitrix\Main\Loader::includeModule('rest')
			&& method_exists(\Bitrix\Rest\UsageStatTable::class, 'logBizProc')
		)
		{
			$row = \Bitrix\Bizproc\Script\Entity\ScriptTable::getList([
				'filter' => ['=WORKFLOW_TEMPLATE_ID' => $tplId],
				'select' => ['ORIGIN_ID'],
			])->fetch();

			if ($row['ORIGIN_ID'])
			{
				\Bitrix\Rest\UsageStatTable::logBizProc($row['ORIGIN_ID'], $clientCode);
				\Bitrix\Rest\UsageStatTable::finalize();
			}
		}
	}

	private static function isResumeWorkflowAvailable($documentId, int $eventType): bool
	{
		if ($eventType === CBPDocumentEventType::Debug)
		{
			return true;
		}
		elseif ($eventType === CBPDocumentEventType::Automation)
		{
			$documentInfo = static::GetDocumentInfo($documentId);
			$entityTypeId = \CCrmOwnerType::ResolveID($documentInfo['TYPE']);

			return \Bitrix\Crm\Automation\Factory::isAutomationAvailable($entityTypeId);
		}
		elseif ($eventType === CBPDocumentEventType::Script)
		{
			$documentInfo = static::GetDocumentInfo($documentId);
			$entityTypeId = \CCrmOwnerType::ResolveID($documentInfo['TYPE']);

			return \Bitrix\Crm\Automation\Factory::isScriptAvailable($entityTypeId);
		}
		return CBPRuntime::isFeatureEnabled();
	}

	protected static function normalizeDocumentIdInternal($documentId, $entityTypeName, $entityTypeAbbr)
	{
		$longPrefix = $entityTypeName.'_';
		$shortPrefix = $entityTypeAbbr.'_';

		if (is_numeric($documentId))
		{
			return $longPrefix.$documentId;
		}
		elseif (mb_strpos($documentId, $shortPrefix) === 0)
		{
			return $longPrefix.mb_substr($documentId, mb_strlen($shortPrefix));
		}
		elseif(is_string($documentId))
		{
			$info = static::getDocumentInfo($documentId);
			$documentId = $info ? implode('_', [$info['TYPE'], $info['ID']]) : $longPrefix . '0';
		}

		return $documentId;
	}

	protected static function getVirtualFields(): array
	{
		$fields = [
			'CRM_ID' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_CRM_ID'),
				'Type' => 'string',
			],
			'URL' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_URL'),
				'Type' => 'string',
			],
			'URL_BB' => [
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_URL_BB'),
				'Type' => 'string',
			],
		];

		// remove after bizproc 23.400.0 has delivered
		if (defined('Bitrix\Bizproc\FieldType::TIME'))
		{
			$fields['TIME_CREATE'] = [
				'Name' => Loc::getMessage('CRM_DOCUMENT_FIELD_TIME_CREATE'),
				'Type' => 'time',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			];
		}

		return $fields;
	}

	protected static function getAssignedByFields()
	{
		$fields = [
			'ASSIGNED_BY_PRINTABLE' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_PRINTABLE'),
				'Type' => 'string',
			),
			'ASSIGNED_BY_EMAIL' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_EMAIL'),
				'Type' => 'string',
			),
			'ASSIGNED_BY_WORK_PHONE' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_WORK_PHONE'),
				'Type' => 'string',
			),
			'ASSIGNED_BY_PERSONAL_MOBILE' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_PERSONAL_MOBILE'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_PHONE_INNER' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_PHONE_INNER'),
				'Type' => 'string',
			),

			'ASSIGNED_BY.LOGIN' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_LOGIN'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.ACTIVE' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_ACTIVE'),
				'Type' => 'bool',
			),
			'ASSIGNED_BY.LAST_NAME' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_LAST_NAME'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.NAME' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_NAME'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.SECOND_NAME' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_SECOND_NAME'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.WORK_POSITION' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_WORK_POSITION'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.PERSONAL_WWW' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_PERSONAL_WWW'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.PERSONAL_CITY' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_PERSONAL_CITY'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_SKYPE' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_SKYPE'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_TWITTER' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_TWITTER'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_FACEBOOK' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_FACEBOOK'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_LINKEDIN' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_LINKEDIN'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_XING' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_XING'),
				'Type' => 'string',
			),
			'ASSIGNED_BY.UF_WEB_SITES' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_UF_WEB_SITES'),
				'Type' => 'string',
			),
		];

		return array_merge(
			$fields,
			static::getExtendedResponsibleFields(),
		);
	}

	protected static function getExtendedResponsibleFields(string $prefix = 'ASSIGNED_BY.'): array
	{
		$responsibleName = GetMessage('CRM_DOCUMENT_FIELD_ASSIGNED_BY_FIELD');
		$wrapName = fn($name) => sprintf('%s: %s', $responsibleName, $name);

		$userService = \CBPRuntime::getRuntime(true)->getUserService();
		$fields = [];

		foreach ($userService->getUserExtendedFields() as $id => $field)
		{
			$field['Name'] = $wrapName($field['Name']);
			$fields[$prefix . $id] = $field;
		}

		return $fields;
	}

	protected static function getUtmFields()
	{
		$fields = [];
		$codeNames = \Bitrix\Crm\UtmTable::getCodeNames();

		foreach ($codeNames as $code => $name)
		{
			$fields[$code] = [
				'Name' => $name,
				'Type' => 'string',
				'Editable' => true,
			];
		}

		return $fields;
	}

	protected static function getSiteFormFields(int $entityTypeId = null): array
	{
		return \Bitrix\Crm\WebForm\Internals\BPDocument::getFields($entityTypeId);
	}

	protected static function getCommunicationFields()
	{
		$callName = Bitrix\Crm\Activity\Provider\Call::getName();
		$emailName = Bitrix\Crm\Activity\Provider\Email::getName();
		$olName = Bitrix\Crm\Activity\Provider\OpenLine::getName();
		$webFormName = Bitrix\Crm\Activity\Provider\WebForm::getName();

		$msg = GetMessage('CRM_DOCUMENT_FIELD_LAST_COMMUNICATION_DATE');

		return [
			'COMMUNICATIONS.LAST_CALL_DATE' => [
				'Name' => $msg . ': '.$callName,
				'Type' => 'datetime',
			],
			'COMMUNICATIONS.LAST_EMAIL_DATE' => [
				'Name' => $msg . ': '.$emailName,
				'Type' => 'datetime',
			],
			'COMMUNICATIONS.LAST_OL_DATE' => [
				'Name' => $msg . ': '.$olName,
				'Type' => 'datetime',
			],
			'COMMUNICATIONS.LAST_FORM_DATE' => [
				'Name' => $msg . ': '.$webFormName,
				'Type' => 'datetime',
			],
		];
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Integration\BizProc\Document\ValueCollection\Base::loadCommunicationValues
	 * @param $typeId
	 * @param $id
	 * @return string[]
	 */
	protected static function getCommunicationFieldsValues($typeId, $id)
	{
		return [];
	}

	public static function isFeatureEnabled($documentType, $feature)
	{
		$supported = [
			//\CBPDocumentService::FEATURE_MARK_MODIFIED_FIELDS,
			\CBPDocumentService::FEATURE_SET_MODIFIED_BY,
		];

		return in_array($feature, $supported);
	}

	protected static function sanitizeCommentsValue($comments)
	{
		if ($comments !== '')
		{
			if(preg_match('/<[^>]+[\/]?>/i', $comments) === 1)
			{
				$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
				$comments = preg_replace("/([^>\r\n]{1})[\r\n]+/", '$1<br>', $comments);
			}
			else
			{
				$comments = str_replace(array("\r\n", "\r", "\n"), "<br>", $comments);
			}
		}
		return $comments;
	}

	protected static function shouldUseTransaction()
	{
		return (COption::GetOptionString("crm", "bizproc_use_transaction", "N") === "Y");
	}

	protected static function castFileFieldValues($id, $typeId, $fieldId, $values)
	{
		$arFileOptions = ['ENABLE_ID' => true];
		$prevValue = null;
		if ($id)
		{
			global $USER_FIELD_MANAGER;
			if ($USER_FIELD_MANAGER instanceof \CUserTypeManager)
			{
				$prevValue = array_flip((array)$USER_FIELD_MANAGER->GetUserFieldValue(
					\CCrmOwnerType::ResolveUserFieldEntityID($typeId),
					$fieldId,
					$id
				));

				foreach ($values as $fileId)
				{
					if (is_numeric($fileId))
					{
						unset($prevValue[$fileId]);
					}
				}
				$prevValue = array_flip($prevValue);
			}
		}

		foreach ($values as &$value)
		{
			//Issue #40380. Secure URLs and file IDs are allowed.
			$file = false;
			$resultResolveFile = CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
			if ($prevValue && $resultResolveFile)
			{
				$file['old_id'] = $prevValue;
			}
			$value = $file;
		}
		unset($value, $prevValue);

		return $values;
	}

	public static function getBizprocEditorUrl($documentType): ?string
	{
		if (isset($documentType[2]))
		{
			$entityTypeName = $documentType[2];

			return "/crm/configs/bp/CRM_{$entityTypeName}/edit/#ID#/";
		}

		return null;
	}
}
