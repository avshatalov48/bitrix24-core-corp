<?
if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(__FILE__);

class CCrmDocument
{
	private static $UNGROUPED_USERS = array();
	private static $USER_GROUPS = array();
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
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Email::class
			),
			'phone' => array(
				'Name' => GetMessage('BPVDX_PHONE'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Phone::class
			),
			'web' => array(
				'Name' => GetMessage('BPVDX_WEB'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Web::class
			),
			'im' => array(
				'Name' => GetMessage('BPVDX_MESSANGER'),
				'BaseType' => 'string',
				'typeClass' => \Bitrix\Crm\Integration\BizProc\FieldType\Im::class
			),
			'text' => array('Name' => GetMessage('BPVDX_TEXT'), 'BaseType' => 'text'),
			'double' => array('Name' => GetMessage('BPVDX_NUM'), 'BaseType' => 'double'),
			'select' => array('Name' => GetMessage('BPVDX_LIST'), 'BaseType' => 'select', "Complex" => true),
			'file' => array('Name' => GetMessage('BPVDX_FILE'), 'BaseType' => 'file'),
			'user' => array('Name' => GetMessage('BPVDX_USER'), 'BaseType' => 'user'),
			'bool' => array('Name' => GetMessage('BPVDX_YN'), 'BaseType' => 'bool'),
			'datetime' => array('Name' => GetMessage('BPVDX_DATETIME'), 'BaseType' => 'datetime')
		);

		if (class_exists('\Bitrix\Bizproc\BaseType\InternalSelect'))
		{
			$arResult[\Bitrix\Bizproc\FieldType::INTERNALSELECT] = array(
				'Name'     => GetMessage("BPVDX_INTERNALSELECT"),
				'BaseType' => 'string',
				'Complex'  => true,
			);
		}

		//'Disk File' is disabled due to GUI issues (see CCrmFields::GetFieldTypes)
		$ignoredUserTypes = array(
			'string', 'double', 'boolean', 'integer', 'datetime', 'file', 'employee', 'enumeration', 'video',
			'string_formatted', 'webdav_element_history', 'disk_version', 'disk_file', 'vote', 'url_preview', 'hlblock',
			'mail_message'
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
				'BaseType' => $arType['BASE_TYPE']
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
				//TODO
			}
			elseif ($arType['USER_TYPE_ID'] === 'url')
			{
				//TODO
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
					'TYPE_ID' => strtoupper($arFieldType['Type']),
					'VALUES' => $value1
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
			/*$arUserFieldType = $USER_FIELD_MANAGER->GetUserType($sType);
			$arUserField = array(
				'ENTITY_ID' => 'CRM_'.$arDocumentID['TYPE'],
				'FIELD_NAME' => $arFieldName['Field'],
				'USER_TYPE_ID' => $sType,
				'SORT' => 100,
				'MULTIPLE' => $arFieldType['Multiple'] ? 'Y' : 'N',
				'MANDATORY' => $arFieldType['Required'] ? 'Y' : 'N',
				'EDIT_FORM_LABEL' => $arUserFieldType['DESCRIPTION'],
				'VALUE' => $fieldValue, //
				'USER_TYPE' => $arUserFieldType,
				'SETTINGS' => array(
				)
			);
			if (
				$arFieldType['Type'] == 'UF:iblock_element' ||
				$arFieldType['Type'] == 'UF:iblock_section' ||
				$arFieldType['Type'] == 'UF:crm_status' ||
				$arFieldType['Type'] == 'UF:boolean'
			)
			{
				if ($arFieldType['Type'] == 'UF:crm_status')
					$arUserField['SETTINGS']['ENTITY_TYPE'] = $arFieldType['Options'];
				else
					$arUserField['SETTINGS'] = $arFieldType['Options'];
			}
			elseif ($arFieldType['Type'] == 'UF:crm')
			{
				$arUserField['SETTINGS'] = $arFieldType['Options'];
				if (empty($arUserField['SETTINGS']))
					$arUserField['SETTINGS'] = array('LEAD' => 'Y', 'CONTACT' => 'Y', 'COMPANY' => 'Y', 'DEAL' => 'Y');//
			}

			$APPLICATION->IncludeComponent(
				'bitrix:system.field.edit',
				$sType,
				array(
					'arUserField' => $arUserField,
					'bVarsFromForm' => true,
					'form_name' => $arFieldName['Form'],
					'FILE_MAX_HEIGHT' => 400,
					'FILE_MAX_WIDTH' => 400,
					'FILE_SHOW_POPUP' => true
				),
				false,
				array('HIDE_ICONS' => 'Y')
			);*/

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
				<script language="JavaScript">
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
						'FILE_SHOW_POPUP' => true
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

					if (strpos($arFieldType['Type'], 'UF:') === 0)
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
								'FILE_SHOW_POPUP' => true
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
										'SHOW_TIME' => $arFieldType['Type'] === 'datetime' ? 'Y' : 'N'
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
						if (!in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) && (strpos($arFieldType['Type'], 'UF:') !== 0))
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
				if (in_array($arFieldType['Type'], array('file', 'bool', "date", "datetime")) || (strpos($arFieldType['Type'], 'UF:') === 0))
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
				$arEntity = $arFieldType['Options'];
				if (empty($arEntity))
					$arEntity = array('LEAD' => 'Y', 'CONTACT' => 'Y', 'COMPANY' => 'Y', 'DEAL' => 'Y');
				$result .= '<input type="checkbox" id="WFSFormOptionsXL" name="ENITTY[]" value="LEAD" '.($arEntity['LEAD'] == 'Y'? 'checked="checked"': '').'> '.GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_LEAD').' <br/>';
				$result .= '<input type="checkbox" id="WFSFormOptionsXC"  name="ENITTY[]" value="CONTACT" '.($arEntity['CONTACT'] == 'Y'? 'checked="checked"': '').'> '.GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_CONTACT').'<br/>';
				$result .= '<input type="checkbox" id="WFSFormOptionsXCO" name="ENITTY[]" value="COMPANY" '.($arEntity['COMPANY'] == 'Y'? 'checked="checked"': '').'> '.GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_COMPANY').'<br/>';
				$result .= '<input type="checkbox" id="WFSFormOptionsXD"  name="ENITTY[]" value="DEAL" '.($arEntity['DEAL'] == 'Y'? 'checked="checked"': '').'> '.GetMessage('CRM_DOCUMENT_CRM_ENTITY_TYPE_DEAL').'<br/>';
				$result .= '<input type="button" onclick="'.$jsFunctionName.'(WFSFormOptionsXCRM())" value="'.GetMessage('CRM_DOCUMENT_CRM_ENTITY_OK').'" />';
				$result .= '<script>
					function WFSFormOptionsXCRM()
					{
						var a = {};
						a["LEAD"] = BX("WFSFormOptionsXL").checked ? "Y" : "N";
						a["CONTACT"] = BX("WFSFormOptionsXC").checked ? "Y" : "N";
						a["COMPANY"] = BX("WFSFormOptionsXCO").checked ? "Y" : "N";
						a["DEAL"] = BX("WFSFormOptionsXD").checked ? "Y" : "N";
						return a;
					}
				</script>';
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
			$result .= '<script type="text/javascript">
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
		if (strpos($documentType, '_') === false)
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
						if (strlen($value) > 0)
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
						if (strlen($value) > 0)
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
						if (!is_array($arFieldType["Options"]) || count($arFieldType["Options"]) <= 0 || strlen($value) <= 0)
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
							elseif (strlen($value) > 0)
							{
								$value = strtolower($value);
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
						if (is_array($value) && array_key_exists("name", $value) && strlen($value["name"]) > 0)
						{
							if (!array_key_exists("MODULE_ID", $value) || strlen($value["MODULE_ID"]) <= 0)
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
					elseif (strpos($arFieldType["Type"], ":") !== false)
					{
						$customTypeID = str_replace('UF:', '', $arFieldType['Type']);
						$arCustomType = $GLOBALS["USER_FIELD_MANAGER"]->GetUserType($customTypeID);

						if($customTypeID === 'crm' && $value === '')
						{
							//skip empty crm entity references
							$value = null;
						}
						elseif ($value !== null && array_key_exists("CheckFields", $arCustomType))
						{
							$arErrorsTmp1 = call_user_func_array(
								$arCustomType["CheckFields"],
								array(
									array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
									array("VALUE" => $value)
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

						if (!is_array($value) && (strlen($value) == 0) || is_array($value) && (count($value) == 0 || count($value) == 1 && isset($value["VALUE"]) && !is_array($value["VALUE"]) && strlen($value["VALUE"]) == 0))
							$value = null;
					}
					else
					{
						if (!is_array($value) && strlen($value) <= 0)
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
						$result[] = ((strtoupper($r) != "N" && !empty($r)) ? GetMessage('BPVDX_YES') : GetMessage('BPVDX_NO'));
				}
				else
				{
					$result = ((strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? GetMessage('BPVDX_YES') : GetMessage('BPVDX_NO'));
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

					if (is_array($fieldValue) && is_array($fieldValue[strtoupper($arFieldType['Type'])]))
					{
						foreach ($fieldValue[strtoupper($arFieldType['Type'])] as $val)
						{
							if (!empty($val))
								$result[] = CCrmFieldMulti::GetEntityNameByComplex(strtoupper($arFieldType['Type']).'_'.$val['VALUE_TYPE'], false).': '.$val['VALUE'];
						}
					}
				break;
		}
		
		if (strpos($arFieldType['Type'], 'UF:') === 0)
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
					'USER_TYPE' => $arUserFieldType
				);
				if ($arFieldType['Type'] == 'UF:iblock_element' || $arFieldType['Type'] == 'UF:iblock_section')
					$arUserField['SETTINGS']['IBLOCK_ID'] = $arFieldType['Options'];
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
						'FILE_SHOW_POPUP' => true
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

		$arResult = null;

		switch ($arDocumentID['TYPE'])
		{
			case 'CONTACT':
				$dbDocumentList = CCrmContact::GetListEx(
					array(),
					array('ID' => $arDocumentID['ID'], "CHECK_PERMISSIONS" => "N"),
					false,
					false,
					array('*', 'UF_*')
				);
				break;
			case 'COMPANY':
				$dbDocumentList = CCrmCompany::GetListEx(
					array(),
					array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('*', 'UF_*')
				);
				break;
			case 'DEAL':
				$dbDocumentList = CCrmDeal::GetListEx(
					array(),
					array('ID' => $arDocumentID['ID'], "CHECK_PERMISSIONS" => "N"),
					false,
					false,
					array('*', 'UF_*')
				);
				break;
			case 'LEAD':
				$dbDocumentList = CCrmLead::GetListEx(
					array(),
					array('ID' => $arDocumentID['ID'], "CHECK_PERMISSIONS" => "N"),
					false,
					false,
					array('*', 'UF_*')
				);
				break;
		}


		if (($objDocument = $dbDocumentList->Fetch()) !== false)
		{
			$assignedByID = isset($objDocument['ASSIGNED_BY_ID'])
				? intval($objDocument['ASSIGNED_BY_ID']) : 0;

			if($assignedByID > 0)
			{
				$dbUsers = CUser::GetList(
					($sortBy = 'id'), ($sortOrder = 'asc'),
					array('ID' => $assignedByID),
					array('SELECT' => array(
							'EMAIL',
							'UF_SKYPE',
							'UF_TWITTER',
							'UF_FACEBOOK',
							'UF_LINKEDIN',
							'UF_XING',
							'UF_WEB_SITES',
							'UF_PHONE_INNER',
						)
					)
				);

				$arUser = is_object($dbUsers) ? $dbUsers->Fetch() : null;
				$objDocument['ASSIGNED_BY_EMAIL'] = is_array($arUser) ? $arUser['EMAIL'] : '';
				$objDocument['ASSIGNED_BY_WORK_PHONE'] = is_array($arUser) ? $arUser['WORK_PHONE'] : '';
				$objDocument['ASSIGNED_BY_PERSONAL_MOBILE'] = is_array($arUser) ? $arUser['PERSONAL_MOBILE'] : '';

				$objDocument['ASSIGNED_BY.LOGIN'] = is_array($arUser) ? $arUser['LOGIN'] : '';
				$objDocument['ASSIGNED_BY.ACTIVE'] = is_array($arUser) ? $arUser['ACTIVE'] : '';
				$objDocument['ASSIGNED_BY.NAME'] = is_array($arUser) ? $arUser['NAME'] : '';
				$objDocument['ASSIGNED_BY.LAST_NAME'] = is_array($arUser) ? $arUser['LAST_NAME'] : '';
				$objDocument['ASSIGNED_BY.SECOND_NAME'] = is_array($arUser) ? $arUser['SECOND_NAME'] : '';
				$objDocument['ASSIGNED_BY.WORK_POSITION'] = is_array($arUser) ? $arUser['WORK_POSITION'] : '';
				$objDocument['ASSIGNED_BY.PERSONAL_WWW'] = is_array($arUser) ? $arUser['PERSONAL_WWW'] : '';
				$objDocument['ASSIGNED_BY.PERSONAL_CITY'] = is_array($arUser) ? $arUser['PERSONAL_CITY'] : '';
				$objDocument['ASSIGNED_BY.UF_SKYPE'] = is_array($arUser) ? $arUser['UF_SKYPE'] : '';
				$objDocument['ASSIGNED_BY.UF_TWITTER'] = is_array($arUser) ? $arUser['UF_TWITTER'] : '';
				$objDocument['ASSIGNED_BY.UF_FACEBOOK'] = is_array($arUser) ? $arUser['UF_FACEBOOK'] : '';
				$objDocument['ASSIGNED_BY.UF_LINKEDIN'] = is_array($arUser) ? $arUser['UF_LINKEDIN'] : '';
				$objDocument['ASSIGNED_BY.UF_XING'] = is_array($arUser) ? $arUser['UF_XING'] : '';
				$objDocument['ASSIGNED_BY.UF_WEB_SITES'] = is_array($arUser) ? $arUser['UF_WEB_SITES'] : '';
				$objDocument['ASSIGNED_BY.UF_PHONE_INNER'] = is_array($arUser) ? $arUser['UF_PHONE_INNER'] : '';
			}

			$arUserField = array('CREATED_BY', 'CREATED_BY_ID', 'MODIFY_BY', 'MODIFY_BY_ID', 'ASSIGNED_BY', 'ASSIGNED_BY_ID');
			foreach ($arUserField as $sField)
				if (isset($objDocument[$sField]))
					$objDocument[$sField] = 'user_'.$objDocument[$sField];

			if (COption::GetOptionString('crm', 'bp_version', 2) == 2)
			{
				$userFieldsList = null;
				switch ($arDocumentID['TYPE'])
				{
					case 'CONTACT':
						$userFieldsList = CCrmContact::GetUserFields();
						break;
					case 'COMPANY':
						$userFieldsList = CCrmCompany::GetUserFields();
						break;
					case 'DEAL':
						$userFieldsList = CCrmDeal::GetUserFields();
						break;
					case 'LEAD':
						$userFieldsList = CCrmLead::GetUserFields();
						break;
				}
				if (is_array($userFieldsList))
				{
					foreach ($userFieldsList as $userFieldName => $userFieldParams)
					{
						$fieldTypeID = isset($userFieldParams['USER_TYPE']) ? $userFieldParams['USER_TYPE']['USER_TYPE_ID'] : '';
						$isFieldMultiple = isset($userFieldParams['MULTIPLE']) && $userFieldParams['MULTIPLE'] === 'Y';
						$fieldSettings = isset($userFieldParams['SETTINGS']) ? $userFieldParams['SETTINGS'] : array();

						if (isset($objDocument[$userFieldName]))
						{
							$fieldValue = $objDocument[$userFieldName];
						}
						elseif (isset($fieldSettings['DEFAULT_VALUE']))
						{
							$fieldValue = $fieldSettings['DEFAULT_VALUE'];
						}
						else
						{
							$objDocument[$userFieldName] = $objDocument[$userFieldName.'_PRINTABLE'] = '';
							continue;
						}

						if ($fieldTypeID == 'employee')
						{
							if(!$isFieldMultiple)
							{
								$objDocument[$userFieldName] = 'user_'.$fieldValue;
							}
							elseif(is_array($fieldValue))
							{
								$objDocument[$userFieldName] = array();
								foreach($fieldValue as $value)
								{
									$objDocument[$userFieldName][] = 'user_'.$value;
								}
							}
						}
						elseif ($fieldTypeID == 'crm')
						{
							$defaultTypeName = '';
							foreach($fieldSettings as $typeName => $flag)
							{
								if($flag === 'Y')
								{
									$defaultTypeName = $typeName;
									break;
								}
							}

							if(!$isFieldMultiple)
							{
								$objDocument[$userFieldName.'_PRINTABLE'] = static::PrepareCrmUserTypeValueView($fieldValue, $defaultTypeName);
							}
							elseif(is_array($fieldValue))
							{
								$views = array();
								foreach($fieldValue as $value)
								{
									$views[] = static::PrepareCrmUserTypeValueView($value, $defaultTypeName);
								}

								$objDocument[$userFieldName.'_PRINTABLE'] = $views;

							}
						}
						elseif ($fieldTypeID == 'enumeration')
						{
							static::ExternalizeEnumerationField($objDocument, $userFieldName);
						}
						elseif ($fieldTypeID === 'boolean')
						{
							$objDocument[$userFieldName] = CBPHelper::getBool($fieldValue) ? 'Y' : 'N';
							$objDocument[$userFieldName.'_PRINTABLE'] = GetMessage($objDocument[$userFieldName] === 'Y' ? 'MAIN_YES' : 'MAIN_NO');
						}
						elseif ($fieldTypeID === 'resourcebooking')
						{
							self::prepareResourceBookingField($objDocument, $userFieldName);
						}
					}
				}
			}

			$multiFields = self::getDocumentFieldMulti($objDocument, $arDocumentID['TYPE'], $arDocumentID['ID']);
			foreach ($multiFields as $ar)
			{
				if (!isset($objDocument[$ar['TYPE_ID']]))
					$objDocument[$ar['TYPE_ID']] = array();
				$objDocument[$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);

				if (!isset($objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']]))
					$objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']] = array();
				$objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']][] = $ar['VALUE'];

				if (!isset($objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']."_PRINTABLE"]))
					$objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']."_PRINTABLE"] = "";
				$objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']."_PRINTABLE"] .= (strlen($objDocument[$ar['TYPE_ID']."_".$ar['VALUE_TYPE']."_PRINTABLE"]) > 0 ? ", " : "").$ar['VALUE'];

				if (!isset($objDocument[$ar['TYPE_ID']."_PRINTABLE"]))
					$objDocument[$ar['TYPE_ID']."_PRINTABLE"] = "";
				$objDocument[$ar['TYPE_ID']."_PRINTABLE"] .= (strlen($objDocument[$ar['TYPE_ID']."_PRINTABLE"]) > 0 ? ", " : "").$ar['VALUE'];
			}

			$multiFieldTypes =  CCrmFieldMulti::GetEntityTypeList();
			foreach ($multiFieldTypes as $typeId => $arFields)
			{
				if(!isset($objDocument[$typeId]))
				{
					$objDocument[$typeId] = array();
				}

				$printableFieldName = $typeId.'_PRINTABLE';
				if(!isset($objDocument[$printableFieldName]))
				{
					$objDocument[$printableFieldName] = '';
				}

				foreach ($arFields as $valueType => $valueName)
				{
					$fieldName = $typeId.'_'.$valueType;
					if(!isset($objDocument[$fieldName]))
					{
						$objDocument[$fieldName] = array('');
					}

					$printableFieldName = $fieldName.'_PRINTABLE';
					if(!isset($objDocument[$printableFieldName]))
					{
						$objDocument[$printableFieldName] = '';
					}
				}
			}

			// Preparation of user names -->
			$nameFormat = CSite::GetNameFormat(false);

			if(isset($objDocument['ASSIGNED_BY_ID']))
			{
				$objDocument['ASSIGNED_BY_PRINTABLE'] =
					CUser::FormatName(
						$nameFormat,
						array(
							'LOGIN' => isset($objDocument['ASSIGNED_BY_LOGIN']) ? $objDocument['ASSIGNED_BY_LOGIN'] : '',
							'NAME' => isset($objDocument['ASSIGNED_BY_NAME']) ? $objDocument['ASSIGNED_BY_NAME'] : '',
							'LAST_NAME' => isset($objDocument['ASSIGNED_BY_LAST_NAME']) ? $objDocument['ASSIGNED_BY_LAST_NAME'] : '',
							'SECOND_NAME' => isset($objDocument['ASSIGNED_BY_SECOND_NAME']) ? $objDocument['ASSIGNED_BY_SECOND_NAME'] : ''
						),
						true, false
				);
			}

			if(isset($objDocument['CREATED_BY_ID']))
			{
				$objDocument['CREATED_BY_PRINTABLE'] =
					CUser::FormatName(
						$nameFormat,
						array(
							'LOGIN' => isset($objDocument['CREATED_BY_LOGIN']) ? $objDocument['CREATED_BY_LOGIN'] : '',
							'NAME' => isset($objDocument['CREATED_BY_NAME']) ? $objDocument['CREATED_BY_NAME'] : '',
							'LAST_NAME' => isset($objDocument['CREATED_BY_LAST_NAME']) ? $objDocument['CREATED_BY_LAST_NAME'] : '',
							'SECOND_NAME' => isset($objDocument['CREATED_BY_SECOND_NAME']) ? $objDocument['CREATED_BY_SECOND_NAME'] : ''
						),
						true, false
				);
			}
			// <-- Preparation of user names

			//communications
			$typeId = \CCrmOwnerType::ResolveID($arDocumentID['TYPE']);
			$objDocument += static::getCommunicationFieldsValues($typeId, $arDocumentID['ID']);
			\Bitrix\Crm\WebForm\Internals\BPDocument::fill($typeId, $arDocumentID['ID'], $objDocument);

			switch ($arDocumentID['TYPE'])
			{
				case 'DEAL':
					CCrmDocumentDeal::PrepareDocument($objDocument);
					break;
				case 'LEAD':
					CCrmDocumentLead::PrepareDocument($objDocument);
					break;
				case 'CONTACT':
					CCrmDocumentContact::PrepareDocument($objDocument);
					break;
				case 'COMPANY':
					CCrmDocumentCompany::PrepareDocument($objDocument);
					break;
			}

			return $objDocument;
		}
		return null;
	}

	private static function getDocumentFieldMulti($document, $entityType, $entityId)
	{
		$fields = [];
		$entities = [[$entityType, $entityId]];

		if ($entityType === CCrmOwnerType::LeadName
			&& CCrmLead::ResolveCustomerType($document) === \Bitrix\Crm\CustomerType::RETURNING
		)
		{
			$entities = [];
			if ($document['CONTACT_ID'] > 0)
			{
				$entities[] = [CCrmOwnerType::ContactName, $document['CONTACT_ID']];
			}
			if ($document['COMPANY_ID'] > 0)
			{
				$entities[] = [CCrmOwnerType::CompanyName, $document['COMPANY_ID']];
			}
			if (!$entities)
			{
				$entities[] = [$entityType, $entityId];
			}
		}

		foreach ($entities as list($type, $id))
		{
			$res = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $type, 'ELEMENT_ID' => $id)
			);
			while ($ar = $res->Fetch())
			{
				$fields[] = $ar;
			}
		}
		return $fields;
	}

	protected static function ExternalizeEnumerationField(array &$fields, $name)
	{
		$value = isset($fields[$name]) ? $fields[$name] : null;
		$valueInfos = array();
		if(!empty($value))
		{
			$dbRes = CUserFieldEnum::GetList(array(), array('ID' => $value));
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

	public static function GetDocumentForHistory($documentId, $historyIndex)
	{
		global $USER_FIELD_MANAGER;
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = static::GetDocument($documentId);

		switch ($arDocumentID['TYPE'])
		{
			case 'CONTACT':
				if (!empty($arResult['PHOTO']))
					$arResult['PHOTO'] = CBPDocument::PrepareFileForHistory(
						array('crm', 'CCrmDocument'.ucfirst(strtolower($arDocumentID['TYPE'])), $documentId),
						$arResult['PHOTO'],
						$historyIndex
					);
			break;
			case 'COMPANY':
				if (!empty($arResult['LOGO']))
					$arResult['LOGO'] = CBPDocument::PrepareFileForHistory(
						array('crm', 'CCrmDocument'.ucfirst(strtolower($arDocumentID['TYPE'])), $documentId),
						$arResult['LOGO'],
						$historyIndex
					);
			break;
		}

		$arUserFields = $USER_FIELD_MANAGER->GetUserFields('CRM_'.$arDocumentID['TYPE'], $arDocumentID['ID'], LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'file')
			{
				$arFiles = !is_array($arUserField[$FIELD_NAME]) ? array($arUserField[$FIELD_NAME]) : $arUserField[$FIELD_NAME];
				foreach ($arFiles as $sFilePath)
				{
					$sFilePath = CBPDocument::PrepareFileForHistory(
						array('crm', 'CCrmDocument'.ucfirst(strtolower($arDocumentID['TYPE'])), $documentId),
						$sFilePath,
						$historyIndex
					);
					if (!is_array($arUserField[$FIELD_NAME]))
					{
						$arResult[$FIELD_NAME] = $sFilePath;
						break;
					}
					else
						$arResult[$FIELD_NAME][] = $sFilePath;
				}
			}
		}

		return $arResult;
	}

	public static function RecoverDocumentFromHistory($documentId, $arDocument)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arFields = $arDocument['FIELDS'];

		switch ($arDocumentID['TYPE'])
		{
			case 'CONTACT':
				$CCrmEntity = new CCrmContact();
				if (!empty($arFields['PHOTO']))
					$arFields['PHOTO'] = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].$arFields['PHOTO']);
			break;
			case 'COMPANY':
				$CCrmEntity = new CCrmCompany();
				if (!empty($arFields['LOGO']))
					$arFields['LOGO'] = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].$arFields['LOGO']);
			break;
			case 'DEAL':
				$CCrmEntity = new CCrmDeal();
			break;
			case 'LEAD':
				$CCrmEntity = new CCrmLead();
			break;
		}

		$res = $CCrmEntity->Update($arDocumentID['ID'], $arFields);
		if (intVal($arFields['WF_STATUS_ID']) > 1 && intVal($arFields['WF_PARENT_ELEMENT_ID']) <= 0)
			static::UnpublishDocument($documentId);
		if (!$res)
			throw new Exception($CCrmEntity->LAST_ERROR);

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
		if($entityTypeName === CCrmOwnerType::DealName && isset($parameters['DealCategoryId']))
		{
			$operationParams['CATEGORY_ID'] = (int)$parameters['DealCategoryId'];
		}

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
			if ($userId == $arParameters['CreatedBy'])
				$arParameters['AllUserGroups'][] = 'Author';
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
		$arDocumentID = static::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$userId = intval($userId);
		if (!array_key_exists('AllUserGroups', $arParameters))
		{
			if (!array_key_exists('UserGroups', $arParameters))
				$arParameters['UserGroups'] = static::PrepareUserGroups($userId);

			$arParameters['AllUserGroups'] = $arParameters['UserGroups'];
			$arParameters['AllUserGroups'][] = 'Author';
		}

		if (array_key_exists('UserIsAdmin', $arParameters) && $arParameters['UserIsAdmin'] === true)
		{
			return true;
		}
		elseif(in_array(1, $arParameters['AllUserGroups']))
		{
			return true;
		}

		$permissionEntity = static::ResolvePermissionEntity($arDocumentID, $arParameters);
		$userPermissions = CCrmPerms::GetUserPermissions($userId);

		if ($operation == \CBPCanUserOperateOperation::CreateWorkflow)
		{
			return \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions);
		}

		if ($operation == \CBPCanUserOperateOperation::CreateAutomation)
		{
			if (isset($arParameters['DocumentCategoryId']) && $arParameters['DocumentCategoryId'] > 0)
			{
				$documentType .= '_C'.$arParameters['DocumentCategoryId'];
			}

			return \CCrmAuthorizationHelper::CheckAutomationCreatePermission($documentType, $userPermissions);
		}

		if( $operation === CBPCanUserOperateOperation::ViewWorkflow
			|| $operation === CBPCanUserOperateOperation::ReadDocument
		)
		{
			return CCrmAuthorizationHelper::CheckReadPermission($permissionEntity, 0, $userPermissions);
		}
		return CCrmAuthorizationHelper::CheckCreatePermission($permissionEntity, $userPermissions);
	}

	public static function DeleteDocument($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

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

		if($CCrmEntity !== null)
		{
			$CCrmEntity->Delete($arDocumentID['ID'], array(
				'CURRENT_USER' => static::getSystemUserId()
			));
		}
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
		if (strlen($documentType) <= 0)
			return false;

		$arDocumentID = static::GetDocumentInfo($documentType);
		if ($arDocumentID !== false)
			$documentType = $arDocumentID['TYPE'];

		$arResult = array('author' => GetMessage('CRM_DOCUMENT_AUTHOR'));

		$arGroupsID = array(1);
		$arUsersID = array();
		$arRelations = CCrmPerms::GetEntityRelations($documentType, BX_CRM_PERM_SELF);
		foreach($arRelations as $relation)
		{
			$preffix = substr($relation, 0, 1);
			if($preffix === 'G')
			{
				$arGroupsID[] = intval(substr($relation, 1));
			}
			elseif($preffix === 'U')
			{
				$arUsersID[] = substr($relation, 1);
			}
		}

		//Crutch for Bitrix24 context (user group management is not suppotted)
		if(IsModuleInstalled('bitrix24'))
		{
			$siteID = CAllSite::GetDefSite();
			$dbResult = CGroup::GetList(
				($by = ''),
				($order = ''),
				array('STRING_ID' => 'EMPLOYEES_'.$siteID,
				'STRING_ID_EXACT_MATCH' => 'Y')
			);
			if($arEmloyeeGroup = $dbResult->Fetch())
			{
				$employeeGroupID = intval($arEmloyeeGroup['ID']);
				if(!in_array($employeeGroupID, $arGroupsID, true))
				{
					$arGroupsID[] = $employeeGroupID;
				}
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
		$groupLc = strtolower($group);
		if ($groupLc == 'author')
		{
			$arDocumentID = static::GetDocumentInfo($documentId);
			if (empty($arDocumentID))
			{
				return array();
			}

			$dbDocumentList = null;
			$entityID = isset($arDocumentID['ID']) ? intval($arDocumentID['ID']) : 0;
			if($entityID > 0)
			{
				switch ($arDocumentID['TYPE'])
				{
					case 'CONTACT':
						$dbDocumentList = CCrmContact::GetListEx(
							array(),
							array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ASSIGNED_BY_ID')
						);
					break;
					case 'COMPANY':
						$dbDocumentList = CCrmCompany::GetListEx(
							array(),
							array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ASSIGNED_BY_ID')
						);
					break;
					case 'DEAL':
						$dbDocumentList = CCrmDeal::GetListEx(
							array(),
							array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ASSIGNED_BY_ID')
						);
					break;
					case 'LEAD':
						$dbDocumentList = CCrmLead::GetListEx(
							array(),
							array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ASSIGNED_BY_ID')
						);
					break;
				}
			}
			$arFields = is_object($dbDocumentList) ? $dbDocumentList->Fetch() : null;
			return is_array($arFields) && isset($arFields['ASSIGNED_BY_ID'])
				? array($arFields['ASSIGNED_BY_ID']) : array();
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
			($b = 'ID'),
			($o = 'ASC'),
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

		$exists = false;
		switch ($arDocumentID['TYPE'])
		{
			case 'CONTACT':
				$exists = CCrmContact::Exists($arDocumentID['ID']);
				break;
			case 'COMPANY':
				$exists = CCrmCompany::Exists($arDocumentID['ID']);
				break;
			case 'DEAL':
				$exists = CCrmDeal::Exists($arDocumentID['ID']);
				break;
			case 'LEAD':
				$exists = CCrmLead::Exists($arDocumentID['ID']);
				break;
		}

		if (!$exists)
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));
		}

		return $arDocumentID['TYPE'];
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
			'INVOICE' => \Bitrix\Crm\Integration\BizProc\Document\Invoice::class
		];

		$arDocumentId[0] = strtoupper($arDocumentId[0]);
		if (!isset($arMap[$arDocumentId[0]]))
		{
			return false;
		}

		return array(
			'TYPE' => $arDocumentId[0],
			'ID' => (int) $arDocumentId[1],
			'DOCUMENT_TYPE' => array("crm", $arMap[$arDocumentId[0]], $arDocumentId[0])
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
		if (strpos($documentType, '_') === false)
			$documentType .= '_0';

		$arDocumentID = static::GetDocumentInfo($documentType);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');


		$userTypeID = $arFields['type'];
		if(strpos($userTypeID, 'UF:') === 0)
		{
			$userTypeID = substr($userTypeID, 3);
		}

		$fieldName = strtoupper($arFields['code']);
		if(strpos($fieldName, 'UF_CRM_') !== 0)
		{
			$fieldName = "UF_CRM_{$fieldName}";
		}

		$arFieldsTmp = array(
			'USER_TYPE_ID' => $userTypeID,
			'FIELD_NAME' => $fieldName,
			'ENTITY_ID' => 'CRM_'.$arDocumentID['TYPE'],
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
						if (substr($option, 0, 1) == "[" && strpos($option, "]") !== false)
						{
							$key = substr($option, 1, strpos($option, "]") - 1);
							$value = trim(substr($option, strpos($option, "]") + 1));
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
				$arFieldsTmp['SETTINGS']['LEAD'] = isset($options['LEAD']) && strtoupper($options['LEAD']) === 'Y' ? 'Y' : 'N';
				$arFieldsTmp['SETTINGS']['CONTACT'] = isset($options['CONTACT']) && strtoupper($options['CONTACT']) === 'Y' ? 'Y' : 'N';
				$arFieldsTmp['SETTINGS']['COMPANY'] = isset($options['COMPANY']) && strtoupper($options['COMPANY']) === 'Y' ? 'Y' : 'N';
				$arFieldsTmp['SETTINGS']['DEAL'] = isset($options['DEAL']) && strtoupper($options['DEAL']) === 'Y' ? 'Y' : 'N';
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
					'VALUE_TYPE' => $defaultValueType
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
							'VALUE_TYPE' => isset($item['VALUE_TYPE']) ? $item['VALUE_TYPE'] : $defaultValueType
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

	protected static function PrepareCrmUserTypeValueView($value, $defaultTypeName = '')
	{
		$parts = explode('_', $value);
		if(count($parts) > 1)
		{
			return CCrmOwnerType::GetCaption(
				CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($parts[0])),
				$parts[1],
				false
			);
		}
		elseif($defaultTypeName !== '')
		{
			return CCrmOwnerType::GetCaption(
				CCrmOwnerType::ResolveID($defaultTypeName),
				$value,
				false
			);
		}

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
			$result[] = 'author';
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

	protected static function getSystemUserId()
	{
		return 0;
	}

	protected static function getWebFormSelectOptions()
	{
		if (self::$webFormSelectList === null)
		{
			self::$webFormSelectList = array();
			$result = \Bitrix\Crm\WebForm\Internals\FormTable::getList(array(
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
	 * @param array $documentId
	 * @param string $workflowId
	 * @param int $status
	 * @param null|CBPActivity $rootActivity
	 */
	public static function onWorkflowStatusChange($documentId, $workflowId, $status, $rootActivity)
	{
		if (!$rootActivity || ($rootActivity->getDocumentEventType() & CBPDocumentEventType::Manual) === 0)
		{
			return;
		}

		if ($status === CBPWorkflowStatus::Running && $rootActivity->workflow->isNew())
		{
			$status = CBPWorkflowStatus::Created;
		}

		\Bitrix\Crm\Timeline\BizprocController::getInstance()->onWorkflowStatusChange(
			$workflowId,
			$status
		);
	}

	protected static function normalizeDocumentIdInternal($documentId, $entityTypeName, $entityTypeAbbr)
	{
		$longPrefix = $entityTypeName.'_';
		$shortPrefix = $entityTypeAbbr.'_';

		if (is_numeric($documentId))
		{
			return $longPrefix.$documentId;
		}
		elseif (strpos($documentId, $shortPrefix) === 0)
		{
			return $longPrefix.substr($documentId, strlen($shortPrefix));
		}

		return $documentId;
	}

	protected static function getAssignedByFields()
	{
		return [
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
				'Editable' => true
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

	protected static function getCommunicationFieldsValues($typeId, $id)
	{
		$callId = Bitrix\Crm\Activity\Provider\Call::getId();
		$emailId = Bitrix\Crm\Activity\Provider\Email::getId();
		$olId = Bitrix\Crm\Activity\Provider\OpenLine::getId();
		$webFormId = Bitrix\Crm\Activity\Provider\WebForm::getId();

		$callDate = $emailDate = $olDate = $webFormDate = null;

		$ormRes = \Bitrix\Crm\ActivityTable::getList([
			'select' => ['END_TIME', 'PROVIDER_ID'],
			'filter' => [
				'=COMPLETED' => 'Y',
				'@PROVIDER_ID' => [$callId, $emailId, $olId, $webFormId],
				'=BINDINGS.OWNER_TYPE_ID' => $typeId,
				'=BINDINGS.OWNER_ID' => $id,
			],
			'order' => ['END_TIME' => 'DESC']
		]);

		while ($row = $ormRes->fetch())
		{
			if ($callDate === null)
			{
				if ($row['PROVIDER_ID'] === $callId)
				{
					$callDate = $row['END_TIME'];
				}
			}
			if ($emailDate === null)
			{
				if ($row['PROVIDER_ID'] === $emailId)
				{
					$emailDate = $row['END_TIME'];
				}
			}
			if ($olDate === null)
			{
				if ($row['PROVIDER_ID'] === $olId)
				{
					$olDate = $row['END_TIME'];
				}
			}
			if ($webFormDate === null)
			{
				if ($row['PROVIDER_ID'] === $webFormId)
				{
					$webFormDate = $row['END_TIME'];
				}
			}

			if ($callDate !== null && $emailDate !== null && $olDate !== null && $webFormDate !== null)
			{
				break;
			}
		}

		return [
			'COMMUNICATIONS.LAST_CALL_DATE' => (string) $callDate,
			'COMMUNICATIONS.LAST_EMAIL_DATE' => (string) $emailDate,
			'COMMUNICATIONS.LAST_OL_DATE' => (string) $olDate,
			'COMMUNICATIONS.LAST_FORM_DATE' => (string) $webFormDate,
		];
	}

	private static function prepareResourceBookingField(array &$document, $fieldId)
	{
		if (empty($document[$fieldId]) || !\Bitrix\Main\Loader::includeModule('calendar'))
		{
			return;
		}

		$resourceList = \Bitrix\Calendar\UserField\ResourceBooking::getResourceEntriesList((array) $document[$fieldId]);

		if ($resourceList)
		{
			$document[$fieldId.'.SERVICE_NAME'] = $resourceList['SERVICE_NAME'];
			$document[$fieldId.'.DATE_FROM'] = $resourceList['DATE_FROM'];
			$document[$fieldId.'.DATE_TO'] = $resourceList['DATE_TO'];
			$users = [];

			foreach ($resourceList['ENTRIES'] as $entry)
			{
				if ($entry['TYPE'] === 'user')
				{
					$users[] = 'user_'.$entry['RESOURCE_ID'];
				}
			}
			$document[$fieldId.'.USERS'] = $users;
		}
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
		return (COption::GetOptionString("crm", "bizproc_use_transaction", "Y") === "Y");
	}
}
