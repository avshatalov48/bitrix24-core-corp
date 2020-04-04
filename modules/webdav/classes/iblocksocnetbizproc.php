<?php
use Bitrix\Disk\File;

IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule("iblock"))
	return;
elseif (!CModule::IncludeModule("socialnetwork"))
	return;
elseif (!CModule::IncludeModule("bizproc"))
	return;

class CIBlockDocumentWebdavSocnet extends CIBlockDocument
{
	private static function proxyToDisk($methodName, array $args = array())
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return;
		}

		//call_user_func don't like &
		if(strtolower($methodName) == 'getfieldinputvalue')
		{
			list($documentType, $fieldType, $fieldName, $request, $errors) = $args;
			return \Bitrix\Disk\BizProcDocumentCompatible::getFieldInputValue($documentType, $fieldType, $fieldName, $request, $errors);
		}
		if(strtolower($methodName) == 'getfieldinputcontroloptions')
		{
			list($documentType, $arFieldType, $jsFunctionName, $value) = $args;
			return \Bitrix\Disk\BizProcDocumentCompatible::getFieldInputControlOptions($documentType, $arFieldType, $jsFunctionName, $value);
		}

		$className = \Bitrix\Disk\BizProcDocumentCompatible::className();

		return call_user_func_array(array($className, $methodName), $args);
	}

	private static function processGetDiskIdByDocId($documentId)
	{
		$arDocFilter = array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y");
		$dbDoc = CIBlockElement::GetList(
			array(),
			$arDocFilter,
			false,
			false,
			array('IBLOCK_ID')
		);
		if ($arDoc = $dbDoc->Fetch())
		{
			$arDocFilter['IBLOCK_ID'] = $arDoc['IBLOCK_ID']; // required for iblock 2.0
		}

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			$arDocFilter
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();
			if($dfile = self::needProxyToDiskByDocProp($arDocumentProperties, $arDocumentFields))
			{
				return $dfile->getId();
			}
		}
		return null;
	}

	private static function needProxyToDiskByDocProp(array $documentProperties, array $arDocumentFields)
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return false;
		}
		if(empty($arDocumentFields['ID']))
		{
			return false;
		}
		return File::load(array('XML_ID' => $arDocumentFields['ID']));
	}

	private static function needProxyToDiskByDocType($documentType)
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return false;
		}
		if(empty($documentType))
		{
			return false;
		}
		$storage = null;
		if(substr($documentType, 0, 7) == 'STORAGE')
		{
			$storageId = (int)substr($documentType, 8);
			if($storageId)
			{
				$storage = \Bitrix\Disk\Storage::loadById($storageId);
			}
			if($storage)
			{
				return $storage;
			}
		}
		list(, $iblockId, $typeLib, $entityId) = explode('_', $documentType);

		if($typeLib == 'user')
		{
			$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($entityId);
		}
		elseif($typeLib == 'group')
		{
			$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByGroupId($entityId);
		}
		else
		{
			return false;
		}

		return $storage;
	}

	private static function getDiskIdFromDocProp(array $documentProperties)
	{
		return $documentProperties['UF_DISK_FILE_ID']['VALUE'];
	}

	function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false, $publicMode = false)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $arFieldName, $fieldValue, $bAllowSelection, $publicMode));
		}

		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
			$arDocumentFieldTypes[$documentType] = self::GetDocumentFieldTypes($documentType);

		$arFieldType["BaseType"] = "string";
		$arFieldType["Complex"] = false;
		if (array_key_exists($arFieldType["Type"], $arDocumentFieldTypes[$documentType]))
		{
			$arFieldType["BaseType"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["BaseType"];
			$arFieldType["Complex"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["Complex"];
		}

		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::IsAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		ob_start();

		if ($arFieldType["Type"] == "select")
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= $arFieldName["Field"] ?>" name="<?= $arFieldName["Field"].($arFieldType["Multiple"] ? "[]" : "") ?>"<?= ($arFieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$arFieldType["Required"])
					echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
				foreach ($arFieldType["Options"] as $k => $v)
				{
					if (is_array($v) && count($v) == 2)
					{
						$v1 = array_values($v);
						$k = $v1[0];
						$v = $v1[1];
					}

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
				<br /><input type="text" id="id_<?= $arFieldName["Field"] ?>_text" name="<?= $arFieldName["Field"] ?>_text" value="<?
				if (count($fieldValueTmp) > 0)
				{
					$a = array_values($fieldValueTmp);
					echo htmlspecialcharsbx($a[0]);
				}
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>_text', 'select');">
				<?
			}
		}
		elseif ($arFieldType["Type"] == "user")
		{
			$fieldValue = CBPHelper::UsersArrayToString($fieldValue, null, array("webdav", "CIBlockDocumentWebdavSocnet", $documentType));
			?><input type="text" size="40" id="id_<?= $arFieldName["Field"] ?>" name="<?= $arFieldName["Field"] ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>', 'user');"><?
		}
		else
		{
			if (!array_key_exists("CBPVirtualDocumentCloneRowPrinted", $GLOBALS) && $arFieldType["Multiple"])
			{
				$GLOBALS["CBPVirtualDocumentCloneRowPrinted"] = 1;
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

			$customMethodName = "";
			if (strpos($arFieldType["Type"], ":") !== false)
			{
				$ar = CIBlockProperty::GetUserType(substr($arFieldType["Type"], 2));
				if (array_key_exists("GetPublicEditHTML", $ar))
					$customMethodName = $ar["GetPublicEditHTML"];
			}

			if ($arFieldType["Multiple"])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.$arFieldName["Field"].'_Table">';

			$fieldValueTmp = $fieldValue;

			$ind = -1;
			foreach ($fieldValue as $key => $value)
			{
				$ind++;
				$fieldNameId = 'id_'.$arFieldName["Field"].'__n'.$ind.'_';
				$fieldNameName = $arFieldName["Field"].($arFieldType["Multiple"] ? "[n".$ind."]" : "");

				if ($arFieldType["Multiple"])
					echo '<tr><td>';

				if (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0)
				{
					$value1 = $value;
					if ($bAllowSelection && preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value1)))
						$value1 = null;
					else
						unset($fieldValueTmp[$key]);

					echo call_user_func_array(
						$customMethodName,
						array(
							array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
							array("VALUE" => $value1),
							array(
								"FORM_NAME" => $arFieldName["Form"],
								"VALUE" => $fieldNameName
							),
							true
						)
					);
				}
				else
				{
					switch ($arFieldType["Type"])
					{
						case "int":
						case "double":
							unset($fieldValueTmp[$key]);
							?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
							break;
						case "file":
							if ($publicMode)
							{
								//unset($fieldValueTmp[$key]);
								?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
							}
							break;
						case "bool":
							if (in_array($value, array("Y", "N")))
								unset($fieldValueTmp[$key]);
							?>
							<select id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>">
								<?
								if (!$arFieldType["Required"])
									echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
								?>
								<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
								<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
							</select>
							<?
							break;
						case "text":
							unset($fieldValueTmp[$key]);
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
							break;
						case "date":
						case "datetime":
							$v = "";
							if (!preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value)))
							{
								$v = $value;
								unset($fieldValueTmp[$key]);
							}
							require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
							echo CAdminCalendar::CalendarDate($fieldNameName, $v, 19, ($arFieldType["Type"] != "date"));
							break;
						default:
							unset($fieldValueTmp[$key]);
							?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
					}
				}

				if ($bAllowSelection)
				{
					if (!in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) && (is_array($customMethodName) && count($customMethodName) <= 0 || !is_array($customMethodName) && strlen($customMethodName) <= 0))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= $arFieldType["BaseType"] ?>');"><?
					}
				}

				if ($arFieldType["Multiple"])
					echo '</td></tr>';
			}

			if ($arFieldType["Multiple"])
				echo "</table>";

			if ($arFieldType["Multiple"])
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\')"/><br />';

			if ($bAllowSelection)
			{
				if (in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) || (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0))
				{
					?>
					<input type="text" id="id_<?= $arFieldName["Field"] ?>_text" name="<?= $arFieldName["Field"] ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>_text', '<?= $arFieldType["BaseType"] ?>');">
					<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	function GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, &$arErrors)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $arFieldName, $arRequest, &$arErrors));
		}

		$result = array();

		if ($arFieldType["Type"] == "user")
		{
			$value = $arRequest[$arFieldName["Field"]];
			if (strlen($value) > 0)
			{
				$result = CBPHelper::UsersStringToArray($value, array("webdav", "CIBlockDocumentWebdavSocnet", $documentType), $arErrors);
				if (count($arErrors) > 0)
				{
					foreach ($arErrors as $e)
						$arErrors[] = $e;
				}
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
				if (is_array($value) || !is_array($value) && !preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value)))
				{
					if ($arFieldType["Type"] == "int")
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
							if ($value."|" == doubleval($value)."|")
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
						$arCustomType = CIBlockProperty::GetUserType(substr($arFieldType["Type"], 2));
						if (array_key_exists("GetLength", $arCustomType))
						{
							if (call_user_func_array(
								$arCustomType["GetLength"],
								array(
									array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
									array("VALUE" => $value)
								)
							) <= 0)
							{
								$value = null;
							}
						}

						if (($value != null) && array_key_exists("CheckFields", $arCustomType))
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
					}
					else
					{
						if (!is_array($value) && strlen($value) <= 0)
							$value = null;
					}
				}

				if ($value != null)
					$result[] = $value;
			}
		}

		if (!$arFieldType["Multiple"])
		{
			if (count($result) > 0)
				$result = $result[0];
			else
				$result = null;
		}

		return $result;
	}

	function GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $fieldValue));
		}
		$result = $fieldValue;

		switch ($arFieldType['Type'])
		{
			case "user":
				if (!is_array($fieldValue))
					$fieldValue = array($fieldValue);

				$result = CBPHelper::UsersArrayToString($fieldValue, null, array("webdav", "CIBlockDocumentWebdavSocnet", $documentType));
				break;

			case "bool":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
						$result[] = ((strtoupper($r) != "N" && !empty($r)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				else
				{
					$result = ((strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				break;

			case "file":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$r."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$dbImg = CFile::GetByID($fieldValue);
					if ($arImg = $dbImg->Fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$fieldValue."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;

			case "select":
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
		}

		if (strpos($arFieldType['Type'], ":") !== false)
		{
			$arCustomType = CIBlockProperty::GetUserType(substr($arFieldType['Type'], 2));
			if (array_key_exists("GetPublicViewHTML", $arCustomType))
			{
				if (is_array($fieldValue) && !CBPHelper::IsAssociativeArray($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $value)
					{
						$r = call_user_func_array(
							$arCustomType["GetPublicViewHTML"],
							array(
								array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
								array("VALUE" => $value),
								""
							)
						);

						$result[] = HTMLToTxt($r);
					}
				}
				else
				{
					$result = call_user_func_array(
						$arCustomType["GetPublicViewHTML"],
						array(
							array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
							array("VALUE" => $fieldValue),
							""
						)
					);

					$result = HTMLToTxt($result);
				}
			}
		}

		return $result;
	}

	function GetFieldValuePrintable($documentId, $fieldName, $fieldType, $fieldValue, $arFieldType)
	{
		$documentType = null;

		if ($fieldType == "user")
		{
			static $arCache = array();
			if (!array_key_exists($documentId, $arCache))
			{
				if (substr($documentId, 0, strlen("iblock_")) == "iblock_")
					$arCache[$documentId] = $documentId;
				else
					$arCache[$documentId] = self::GetDocumentType($documentId);
			}
			$documentType = $arCache[$documentId];
		}

		if (is_null($arFieldType) || !is_array($arFieldType) || count($arFieldType) <= 0)
			$arFieldType = array();
		$arFieldType["Type"] = $fieldType;

		return self::GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue);
	}

	public function GetDocumentFieldTypes($documentType)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId())));
		}

		$arResult = array(
			"string" => array("Name" => GetMessage("BPCGHLP_PROP_STRING"), "BaseType" => "string"),
			"text" => array("Name" => GetMessage("BPCGHLP_PROP_TEXT"), "BaseType" => "text"),
			"int" => array("Name" => GetMessage("BPCGHLP_PROP_INT"), "BaseType" => "int"),
			"double" => array("Name" => GetMessage("BPCGHLP_PROP_DOUBLE"), "BaseType" => "double"),
			"select" => array("Name" => GetMessage("BPCGHLP_PROP_SELECT"), "BaseType" => "select", "Complex" => true),
			"bool" => array("Name" => GetMessage("BPCGHLP_PROP_BOOL"), "BaseType" => "bool"),
			"date" => array("Name" => GetMessage("BPCGHLP_PROP_DATA"), "BaseType" => "date"),
			"datetime" => array("Name" => GetMessage("BPCGHLP_PROP_DATETIME"), "BaseType" => "datetime"),
			"user" => array("Name" => GetMessage("BPCGHLP_PROP_USER"), "BaseType" => "user"),
		);

		foreach (CIBlockProperty::GetUserType() as	$ar)
		{
			$t = $ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"];

			if (!array_key_exists("GetPublicEditHTML", $ar) || $t == "S:UserID" || $t == "S:DateTime")
				continue;

			$arResult[$t] = array("Name" => $ar["DESCRIPTION"], "BaseType" => "string");
		}

		return $arResult;
	}

	/**
	* Метод по коду документа возвращает ссылку на страницу документа в административной части.
	*
	* @param string $documentId - код документа.
	* @return string - ссылка на страницу документа в административной части.
	*/
	public function GetDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		$db_res = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
			false,
			false,
			array("ID", "CODE", "EXTERNAL_ID", "IBLOCK_ID", "IBLOCK_TYPE_ID", "IBLOCK_SECTION_ID"));
		if ($db_res && $arElement = $db_res->Fetch())
		{
			$db_res = CIBlock::GetList(array(), array("ID" => $arElement["IBLOCK_ID"], "CHECK_PERMISSIONS"=>"N", "SITE_ID" => SITE_ID));
			if ($db_res && $arIblock = $db_res->Fetch())
			{
				$arr = array(
					"LANG_DIR" => SITE_ID,
					"ID" => $documentId,
					"CODE" => $arElement["CODE"],
					"EXTERNAL_ID" => $arElement["EXTERNAL_ID"],
					"IBLOCK_TYPE_ID" => $arIblock["IBLOCK_TYPE_ID"],
					"IBLOCK_ID" => $arIblock["IBLOCK_ID"],
					"IBLOCK_CODE" => $arIblock["IBLOCK_CODE"],
					"IBLOCK_EXTERNAL_ID" => $arIblock["IBLOCK_EXTERNAL_ID"],
					"SECTION_ID" => $arElement["IBLOCK_SECTION_ID"]);

				$arIblock["DETAIL_PAGE_URL"] = CIBlock::ReplaceDetailUrl($arIblock["DETAIL_PAGE_URL"], $arr, true, "E");

				if (
					IsModuleInstalled('extranet')
					&& CModule::IncludeModule('extranet')
					&& CExtranet::IsExtranetSite()
				)
				{
					$rsSite = CSite::GetByID(CExtranet::GetExtranetSiteID());
					if ($arSite = $rsSite->GetNext())
					{
						$arIblock["DETAIL_PAGE_URL"] = str_replace(array("///","//"), "/", $arSite['DIR'] . $arIblock["DETAIL_PAGE_URL"]);
					}
				}

				$dbSectionsChain = CIBlockSection::GetNavChain($arElement["IBLOCK_ID"], $arElement["IBLOCK_SECTION_ID"]);
				if ($arSection = $dbSectionsChain->Fetch())
				{
					$arIblock["DETAIL_PAGE_URL"] = str_replace(
						array("#SOCNET_USER_ID#", "#USER_ID#", "#SOCNET_GROUP_ID#", "#GROUP_ID#", "#SOCNET_OBJECT#", "#SOCNET_OBJECT_ID#"),
						array($arSection["CREATED_BY"], $arSection["CREATED_BY"], $arSection["SOCNET_GROUP_ID"], $arSection["SOCNET_GROUP_ID"],
							($arSection["SOCNET_GROUP_ID"] > 0 ? "group" : "user"),
							($arSection["SOCNET_GROUP_ID"] > 0 ? $arSection["SOCNET_GROUP_ID"] : $arSection["CREATED_BY"])), $arIblock["DETAIL_PAGE_URL"]);

				}
				return $arIblock["DETAIL_PAGE_URL"];
			}
		}
		return null;
	}

	public function GetDocumentType($documentId)
	{
		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		$result = '';
		$dbResult = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"), false, false);
		$arResult = $dbResult->Fetch();
		if (!$arResult)
			throw new Exception("Element is not found");

		$nav = CIBlockSection::GetNavChain(IntVal($arResult['IBLOCK_ID']), IntVal($arResult['IBLOCK_SECTION_ID']));
		if ($nav && $arSection = $nav->GetNext())
		{
			$result = implode('_', array(
				'iblock',
				intval($arResult['IBLOCK_ID']),
				($arSection["SOCNET_GROUP_ID"] > 0 ? "group" : "user"),
				($arSection["SOCNET_GROUP_ID"] > 0 ? $arSection["SOCNET_GROUP_ID"] : $arSection["CREATED_BY"])
			));
		}

		return $result;
	}

	/**
	* Метод возвращает массив произвольной структуры, содержащий всю информацию о документе. По этому массиву документ восстановливается методом RecoverDocumentFromHistory.
	*
	* @param string $documentId - код документа.
	* @return array - массив документа.
	*/
	public function GetDocumentForHistory($documentId, $historyIndex, $update = false)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $historyIndex, $update));
		}

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			$arResult["NAME"] = $arDocumentFields["~NAME"];

			$arResult["FIELDS"] = array();
			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if ($fieldKey == "~PREVIEW_PICTURE" || $fieldKey == "~DETAIL_PICTURE")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = CBPDocument::PrepareFileForHistory(
						array("webdav", "CIBlockDocumentWebdavSocnet", $documentId),
						$fieldValue,
						$historyIndex
					);
				}
				elseif (substr($fieldKey, 0, 1) == "~")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = $fieldValue;
				}
			}

			$arResult["PROPERTIES"] = array();
			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE_ENUM_ID"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F" && $propertyKey == 'FILE') // primary webdav file
				{
					$arDocID = $documentId;
					if (!is_array($documentId))
						$arDocID = array("webdav", "CIBlockDocumentWebdavSocnet", $documentId);

					$arResult['PROPERTIES'][$propertyKey] = CWebdavDocumentHistory::GetFileForHistory($arDocID, $propertyValue, $historyIndex);
					$arResult['OLD_FILE_ID'] = $propertyValue['VALUE']; //for historical comment.

					if ($update)
						$historyGlueState = CWebdavDocumentHistory::GetHistoryState($arDocID, null, null, array('CHECK_TIME'=>'Y'));
					else
						$historyGlueState = CWebdavDocumentHistory::GetHistoryState($arDocID, null, null, array('NEW'=>'Y', 'CHECK_TIME'=>'Y'));

					$arResult['PROPERTIES'][$propertyKey]['HISTORYGLUE'] = $historyGlueState;
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => CBPDocument::PrepareFileForHistory(
							array("webdav", "CIBlockDocumentWebdavSocnet", $documentId),
							$propertyValue["VALUE"],
							$historyIndex
						),
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				else
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
			}
		}

		return $arResult;
	}

	/**
	* Метод проверяет права на выполнение операций над заданным документом. Проверяются операции 0 - просмотр данных рабочего потока, 1 - запуск рабочего потока, 2 - право изменять документ, 3 - право смотреть документ.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($operation, $userId, $diskId, $arParameters));
		}

		$userId = intval($userId);

		global $USER;

		if ($USER->IsAuthorized() && $USER->GetID() == $userId && CSocNetUser::IsCurrentUserModuleAdmin())
			return true;

		if (array_key_exists("IBlockPermission", $arParameters) && false):
			if ($arParameters["IBlockPermission"] < "R")
				return false;
			elseif ($arParameters["IBlockPermission"] >= "W")
				return true;
		endif;

		// Если мы оказались здесь, то либо не указан IBlockPermission, либо IBlockPermission == U
		// Если нам явно не сказали, а нам нужно, то узнаем код инфоблока, автора элемента, тип хранилища и владельца хранилища
		if ($documentId > 0 &&
			(!array_key_exists("IBlockId", $arParameters) || !array_key_exists("CreatedBy", $arParameters) ||
				!array_key_exists("OwnerType", $arParameters) || !array_key_exists("OwnerId", $arParameters))
				||
				($operation == CBPWebDavCanUserOperateOperation::ReadDocument))
		{
			$db_res = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY", "IBLOCK_SECTION_ID", "WF_STATUS_ID", "WF_PARENT_ELEMENT_ID")
			);
			$arElement = $db_res->Fetch();

			if (!$arElement)
				return false;

			$arParameters["IBlockId"] = $arElement["IBLOCK_ID"];
			$arParameters["CreatedBy"] = $arElement["CREATED_BY"];

			if (!array_key_exists("OwnerType", $arParameters) || !array_key_exists("OwnerId", $arParameters))
			{
				$dbSectionsChain = CIBlockSection::GetNavChain($arElement["IBLOCK_ID"], $arElement["IBLOCK_SECTION_ID"]);
				if ($arSect = $dbSectionsChain->Fetch())
				{
					$arParameters["OwnerType"] = (intVal($arSect["SOCNET_GROUP_ID"]) > 0 ? "group" : "user");
					$arParameters["OwnerId"] = (intVal($arSect["SOCNET_GROUP_ID"]) > 0 ? $arSect["SOCNET_GROUP_ID"] : $arSect["CREATED_BY"]);
				}
			}
			$arParameters["Published"] = ((intVal($arElement["WF_STATUS_ID"]) == 1 && intVal($arElement["WF_PARENT_ELEMENT_ID"]) <= 0) ? "Y" : "N");
		}
		elseif (array_key_exists("DocumentType", $arParameters))
		{
			$res = explode("_", (is_array($arParameters["DocumentType"]) ? $arParameters["DocumentType"][2] : $arParameters["DocumentType"]));
			if (count($res) != 4)
				return false;
			$arParameters["IBlockId"] = intval($res[1]);
			$arParameters["OwnerType"] = $res[2];
			$arParameters["OwnerId"] = intval($res[3]);
		}

		// Если нет необходимых параметров, то возвращаем false
		if (!in_array($arParameters["OwnerType"], array("user", "group")) || $arParameters["OwnerId"] <= 0 || $arParameters["IBlockId"] <= 0):
			return false;
		// Если пользователь является владельцем хранилища, то возвращаем true
		elseif ($arParameters["OwnerType"] == "user" && $arParameters["OwnerId"] == $userId):
			return true;
		endif;

		// Если нам явно не сказали, то узнаем права пользователя на инфоблок
		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			$res = CIBlockWebdavSocnet::GetUserMaxPermission($arParameters["OwnerType"], $arParameters["OwnerId"], $userId, $arParameters["IBlockId"]);
			$arParameters["IBlockPermission"] = $res["PERMISSION"];
		}

		if (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_read") < "R")
			return false;
		elseif ($operation != CBPWebDavCanUserOperateOperation::DeleteDocument && CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") >= "W")
			return true;
		elseif ($operation == CBPWebDavCanUserOperateOperation::DeleteDocument && CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_delete") >= "X")
			return true;
		elseif ($operation == CBPWebDavCanUserOperateOperation::ReadDocument && $arParameters["Published"] == "Y")
			return true;
		// AllUserGroups
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			$arParameters["AllUserGroups"] = CIBlockDocumentWebdavSocnet::GetUserGroups(
				$arParameters["DocumentType"],
				$documentId,
				$userId);
		}

		// Если нам явно не сказали, то узнаем текущие статусы документа
		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("webdav", "CIBlockDocumentWebdavSocnet", "x"),
				array("webdav", "CIBlockDocumentWebdavSocnet", $documentId)
			);
		}

		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - поток не является автоматом
		// $arAllowableOperations == array() - в автомате нет допустимых операций
		// $arAllowableOperations == array("read", ...) - допустимые операции
		if (!is_array($arAllowableOperations))
			return false;
		$r = false;
		switch ($operation)
		{
			case CBPWebDavCanUserOperateOperation::ViewWorkflow:
				// право на просмотр бизнес-процесса есть только у пользователей, которым разрешено читать
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_read") >= "U" && !empty($arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::StartWorkflow:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_bizproc_start") > "U" || in_array("write", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::CreateWorkflow:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U");
				break;
			case CBPWebDavCanUserOperateOperation::WriteDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U" || in_array("write", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::DeleteDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_delete") >= "X" || in_array("delete", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::ReadDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U" || in_array("read", $arAllowableOperations) || in_array("write", $arAllowableOperations));
				break;
			default:
				$r = false;
		}

		return $r;
	}

	function GetUserGroups($documentType = null, $documentId = null, $userId = 0)
	{
		$documentType = trim(is_array($documentType) ? $documentType[2] : $documentType);

		if (is_array($documentType))
			$documentType = null;
		else
			$documentType = (($documentType == null || $documentType == '') ? null : $documentType);

		$userId = intVal($userId);
		$documentIdReal = $documentId = (is_array($documentId) ? $documentId[2] : $documentId);
		$documentId = intVal($documentId);
		$arParameters = array();

		if (($documentType == null && $documentId <= 0) || $userId <= 0)
			return false;
		elseif ($documentType != null)
		{
			$res = explode("_", $documentType);
			if (count($res) != 4)
				return false;
			$arParameters = array(
				"IBlockId" => intval($res[1]),
				"OwnerType" => $res[2],
				"OwnerId" => intval($res[3]));
		}

		if ($documentId > 0)
		{
			$db_res = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY", "IBLOCK_SECTION_ID")
			);
			if ($db_res && $arElement = $db_res->Fetch())
			{
				$dbSectionsChain = CIBlockSection::GetNavChain($arElement["IBLOCK_ID"], $arElement["IBLOCK_SECTION_ID"]);
				if ($arSect = $dbSectionsChain->Fetch())
				{
					$arParameters["OwnerType"] = (intVal($arSect["SOCNET_GROUP_ID"]) > 0 ? "group" : "user");
					$arParameters["OwnerId"] = (intVal($arSect["SOCNET_GROUP_ID"]) > 0 ? $arSect["SOCNET_GROUP_ID"] : $arSect["CREATED_BY"]);

					$arParameters["IBlockId"] = $arElement["IBLOCK_ID"];
					$arParameters["CreatedBy"] = $arElement["CREATED_BY"];
				}
			}
		}

		$arParameters["UserGroups"] = array();

		if ($arParameters["OwnerType"] == "group")
		{
			$arParameters["UserGroups"][] = SONET_ROLES_ALL;
			$r = CSocNetUserToGroup::GetUserRole($userId, $arParameters["OwnerId"]);
			if (strlen($r) > 0)
			{
				$arParameters["UserGroups"][] = $r;
				foreach ($GLOBALS["arSocNetAllowedInitiatePerms"] as $perm)
					if ($r < $perm)
						$arParameters["UserGroups"][] = $perm;
			}
		}
		elseif ($arParameters["OwnerType"] == "user")
		{
			$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_ALL;
			if ($arParameters["OwnerId"] == $userId)
				$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_NONE;
			if (CSocNetUserRelations::IsFriends($userId, $arParameters["OwnerId"]))
				$arParameters["UserGroups"][] = SONET_RELATIONS_TYPE_FRIENDS;
		}
		if ($documentIdReal != null && ($documentId <= 0 || $userId > 0 && $userId == $arParameters["CreatedBy"]))
			$arParameters["UserGroups"][] = "author";
		return $arParameters["UserGroups"];
	}

	/**
	* Метод проверяет права на выполнение операций над документами заданного типа. Проверяются операции 4 - право изменять шаблоны рабочий потоков для данного типа документа.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код типа документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array($operation, $userId, \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arParameters));
		}

		$userId = intval($userId);

		global $USER;

		// Если пользователь является администратором модуля соц. сети, то возвращаем true
		if ($USER->IsAuthorized() && $USER->GetID() == $userId && CSocNetUser::IsCurrentUserModuleAdmin())
			return true;

		$res = explode("_", $documentType);
		if (count($res) != 4)
			return false;

		$arParameters["IBlockId"] = intval($res[1]);
		$arParameters["OwnerType"] = $res[2];
		$arParameters["OwnerId"] = intval($res[3]);
		// Если нет необходимых параметров, то возвращаем false
		if (!in_array($arParameters["OwnerType"], array("user", "group")) || $arParameters["OwnerId"] <= 0 || $arParameters["IBlockId"] <= 0)
			return false;
		// Если пользователь является владельцем хранилища, то возвращаем true
		elseif ($arParameters["OwnerType"] == "user" && $arParameters["OwnerId"] == $userId)
			return true;

		// Если нам явно не сказали, то узнаем права пользователя на хранилище
		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			$res = CIBlockWebdavSocnet::GetUserMaxPermission($arParameters["OwnerType"], $arParameters["OwnerId"], $userId, $arParameters["IBlockId"]);
			$arParameters["IBlockPermission"] = $res["PERMISSION"];
		}
		if ($arParameters["IBlockPermission"] < "R")
			return false;
		elseif ($arParameters["IBlockPermission"] >= "W")
			return true;

		// Если мы тут, то инфоблочные права равны U

		// Если нам явно не сказали, то узнаем группы пользователя
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			$arParameters["AllUserGroups"] = CIBlockDocumentWebdavSocnet::GetUserGroups(
				$documentType,
				null,
				$userId);
		}

		// Если нам явно не сказали, то узнаем текущие статусы документа
		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("webdav", "CIBlockDocumentWebdavSocnet", "x"),
				null
			);
		}

		// Если нужно проверить только для одного рабочего потока
		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - поток не является автоматом
		// $arAllowableOperations == array() - в автомате нет допустимых операций
		// $arAllowableOperations == array("read", ...) - допустимые операции
		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = false;
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = false;
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = false;
				break;
			default:
				$r = false;
		}

		return $r;
	}

	// array(SONET_RELATIONS_TYPE_FRIENDS => "Друзья", SONET_RELATIONS_TYPE_FRIENDS2 => "Друзья друзей", 3 => ..., "Author" => "Автор")
	public function GetAllowableUserGroups($documentType, $withExtended = false)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId())));
		}

		global $APPLICATION;
		$arResult = array(
			"author" => GetMessage("WD_USER_GROUPS_AUTHOR"),
		);

		$res = explode("_", $documentType);
		if (count($res) != 4)
			return false;

		if ($res[2] == "user")
		{
			$arResult[SONET_RELATIONS_TYPE_NONE] = GetMessage("WD_USER_GROUPS_NONE");
			$arResult[SONET_RELATIONS_TYPE_FRIENDS] = GetMessage("WD_USER_GROUPS_FRIEND");
			$arResult[SONET_RELATIONS_TYPE_ALL] = GetMessage("WD_USER_GROUPS_ALL");
		}
		else
		{
			$arResult[SONET_ROLES_OWNER] = GetMessage("WD_USER_GROUPS_OWNER");
			$arResult[SONET_ROLES_MODERATOR] = GetMessage("WD_USER_GROUPS_MODS");
			$arResult[SONET_ROLES_USER] = GetMessage("WD_USER_GROUPS_MEMBERS");
			$arResult[SONET_ROLES_ALL] = GetMessage("WD_USER_GROUPS_ALL");
		}
		return $arResult;
	}

	public function GetUsersFromUserGroup($group, $documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($group, $diskId));
		}

		$arResult = array();
		$arParameters = array(
			"IBlockId" => 0,
			"OwnerType" => "",
			"OwnerId" => 0);

		if (strLen($documentId) <= 0)
			return $arResult;

		$res = explode("_", $documentId);
		if (count($res) == 4)
		{
			$arParameters = array(
				"IBlockId" => $res[1],
				"OwnerType" => $res[2],
				"OwnerId" => $res[3]);
		}
		elseif (intVal($documentId) > 0)
		{
			$db_res = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY", "IBLOCK_SECTION_ID")
			);
			$arElement = $db_res->Fetch();

			if (!$arElement)
				return false;

			$arParameters["IBlockId"] = $arElement["IBLOCK_ID"];
			$arParameters["CreatedBy"] = $arElement["CREATED_BY"];

			$dbSectionsChain = CIBlockSection::GetNavChain($arElement["IBLOCK_ID"], $arElement["IBLOCK_SECTION_ID"]);
			if ($arSect = $dbSectionsChain->Fetch())
			{
				$arParameters["OwnerType"] = (intVal($arSect["SOCNET_GROUP_ID"]) > 0 ? "group" : "user");
				$arParameters["OwnerId"] = (intVal($arSect["SOCNET_GROUP_ID"]) > 0 ? $arSect["SOCNET_GROUP_ID"] : $arSect["CREATED_BY"]);
			}
		}

		$sGroup = strtoupper($group);
		if ($sGroup == "AUTHOR")
		{
			return array($arParameters["CreatedBy"]);
		}
		elseif ($sGroup == SONET_RELATIONS_TYPE_NONE)
		{
			return array($arParameters["OwnerId"]);
		}
		elseif ($arParameters["OwnerId"] <= 0)
		{
			return array();
		}


		if ($arParameters["OwnerType"] == "user")
		{
			$db_res = CSocNetUserRelations::GetRelatedUsers($arParameters["OwnerId"], SONET_RELATIONS_FRIEND);
			if ($db_res && $res = $db_res->Fetch())
			{
				do
				{
					if ($res["FIRST_USER_ID"] == $arParameters["OwnerId"])
						$arResult[] = $res["SECOND_USER_ID"];
					else
						$arResult[] = $res["FIRST_USER_ID"];
				} while ($res = $db_res->Fetch());
			}
		}
		else
		{

			if ($sGroup == SONET_ROLES_OWNER)
			{
				$arGroup = CSocNetGroup::GetByID($arParameters["OwnerId"]);
				if ($arGroup)
					$arResult[] = $arGroup["OWNER_ID"];
			}
			elseif ($sGroup == SONET_ROLES_MODERATOR)
			{
				$db = CSocNetUserToGroup::GetList(
					array(),
					array(
						"GROUP_ID" => $arParameters["OwnerId"],
						"<=ROLE" => SONET_ROLES_MODERATOR,
						"USER_ACTIVE" => "Y"
					),
					false,
					false,
					array("USER_ID")
				);
				while ($ar = $db->Fetch())
					$arResult[] = $ar["USER_ID"];
			}
			elseif ($sGroup == SONET_ROLES_USER)
			{
				$db = CSocNetUserToGroup::GetList(
					array(),
					array(
						"GROUP_ID" => $arParameters["OwnerId"],
						"<=ROLE" => SONET_ROLES_USER,
						"USER_ACTIVE" => "Y"
					),
					false,
					false,
					array("USER_ID")
				);
				while ($ar = $db->Fetch())
					$arResult[] = $ar["USER_ID"];
			}
		}

		return $arResult;
	}

	/**
	* Метод публикует документ. То есть делает его доступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	public function PublishDocument($documentId)
	{
		global $DB;
		$documentId = intval($documentId);
		if ($documentId <= 0)
			return;

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		$ID = intval($documentId);
		$db_element = CIBlockElement::GetByID($ID);

		$PARENT_ID = 0; $arParent = array();
		if($arElement = $db_element->Fetch())
		{
			$PARENT_ID = intval($arElement["WF_PARENT_ELEMENT_ID"]);
			if ($PARENT_ID > 0)
			{
				CBPDocument::MergeDocuments(
					array("webdav", "CIBlockDocumentWebdavSocnet", $PARENT_ID),
					array("webdav", "CIBlockDocumentWebdavSocnet", $documentId));
				$db_res = CIBlockElement::GetList(
					array(),
					array("ID" => $PARENT_ID, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
					false,
					false,
					array("IBLOCK_ID", "ID", "NAME"));
				$arParent = $db_res->Fetch();
			}
		}
		parent::PublishDocument($documentId);
		if ($PARENT_ID > 0)
		{
			CBPDocument::AddDocumentToHistory(
				array("webdav", "CIBlockDocumentWebdavSocnet", $PARENT_ID),
				str_replace(
					array("#PARENT_ID#", "#PARENT_NAME#", "#ID#", "#NAME#"),
					array($PARENT_ID, $arParent["NAME"], $documentId, $arElement["NAME"]),
					GetMessage("IBD_TEXT_001")),
				$GLOBALS["USER"]->GetID());
		}

		$arElement["ID"] = ($PARENT_ID > 0 ? $PARENT_ID : $arElement["ID"]);
		// socnet
		$arConstructor = array(
			"FILES_PROPERTY_CODE" => "FILE");
		$dbSectionsChain = CIBlockSection::GetNavChain($arElement["IBLOCK_ID"], $arElement["IBLOCK_SECTION_ID"]);
		$user_id = $group_id = false;
		if ($arSection = $dbSectionsChain->Fetch())
		{
			if (intVal($arSection["SOCNET_GROUP_ID"]) > 0)
			{
				$arConstructor["FILES_GROUP_IBLOCK_ID"] = $arElement["IBLOCK_ID"];
				$arConstructor["PATH_TO_GROUP_FILES_ELEMENT"] = CIBlockDocumentWebdavSocnet::GetDocumentAdminPage($documentId);
				$group_id = $arSection["SOCNET_GROUP_ID"];
			}
			else
			{
				$arConstructor["FILES_USER_IBLOCK_ID"] = $arElement["IBLOCK_ID"];
				$arConstructor["PATH_TO_USER_FILES_ELEMENT"] = CIBlockDocumentWebdavSocnet::GetDocumentAdminPage($documentId);
				$user_id = $arSection["CREATED_BY"];
			}
		}

		$bxSocNetSearch = new CSocNetSearch($user_id, $group_id, $arConstructor);
		$bxSocNetSearch->IBlockElementUpdate($arElement);

		if ($arElement)
		{
			$rsEvents = GetModuleEvents("webdav", "OnBizprocPublishDocument");
			while ($arEvent = $rsEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($arElement['ID']));
			}
		}

		return $PARENT_ID > 0 ? $PARENT_ID : $documentId;
	}

	/**
	* Метод клонирует документ.
	*
	* @param string $documentId - ID документа.
	* @param string $arFields - поля для замены.
	*/
	public function CloneElement($ID, $arFields = array(), $arParams = array())
	{
		global $DB;
		$ID = intval($ID);
		$CHILD_ID = parent::CloneElement($ID, $arFields);
		if ($CHILD_ID > 0)
		{
			$db_res = CIBlockElement::GetList(
				array(),
				array("ID" => $ID, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("IBLOCK_ID", "ID", "NAME"));
			$arParent = $db_res->Fetch();
			CBPDocument::AddDocumentToHistory(
				array("webdav", "CIBlockDocumentWebdavSocnet", $CHILD_ID),
				str_replace(
					array("#ID#", "#NAME#", "#PARENT_ID#", "#PARENT_NAME#"),
					array($CHILD_ID, $arFields["NAME"], $ID, $arParent["NAME"]),
					GetMessage("IBD_TEXT_002")),
				$GLOBALS["USER"]->GetID());
		}
		return $CHILD_ID;
	}

	/**
	* Метод снимает документ с публикации. То есть делает его недоступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	public function UnpublishDocument($documentId)
	{
		global $DB;

		$documentId = intval($documentId);
		if ($documentId <= 0)
			return;

		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		CIBlockElement::WF_CleanUpHistoryCopies($documentId, 0);
		$strSql = "update b_iblock_element set WF_STATUS_ID='2', WF_NEW='Y' WHERE ID=".intval($documentId)." AND WF_PARENT_ELEMENT_ID IS NULL";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);

		CSocNetSearch::IBlockElementDelete(array("ID" => $documentId));
	}

	public function RecoverDocumentFromHistory($documentId, $arDocument)
	{
		if(parent::RecoverDocumentFromHistory($documentId, $arDocument))
		{
			CWebDavDiskDispatcher::sendEventToOwners($arDocument['FIELDS'], null, 'recover from history');
			return true;
		}
	}

	public function GetDocument($documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId));
		}
		return parent::GetDocument($documentId);
	}

	public function GetDocumentFields($documentType)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId())));
		}
		return parent::GetDocumentFields($documentType);
	}

	public function AddDocumentField($documentType, $arFields)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFields));
		}
		return parent::AddDocumentField($documentType, $arFields);
	}

	public function UpdateDocument($documentId, $arFields)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId, $arFields));
		}
		parent::UpdateDocument($documentId, $arFields);
	}

	public function LockDocument($documentId, $workflowId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId, $arFields));
		}
		return parent::LockDocument($documentId, $workflowId);
	}

	public function UnlockDocument($documentId, $workflowId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId));
		}

		return parent::UnlockDocument($documentId, $workflowId);
	}

	public function IsDocumentLocked($documentId, $workflowId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId));
		}

		return parent::IsDocumentLocked($documentId, $workflowId);
	}

	public function DeleteDocument($documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId));
		}
		parent::DeleteDocument($documentId);
	}

	function GetFieldInputControlOptions($documentType, &$arFieldType, $jsFunctionName, &$value)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if($iblockId > 0 && $storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $jsFunctionName, $value));
		}

		return parent::GetFieldInputControlOptions($documentType, $arFieldType, $jsFunctionName, $value);
	}

	public function SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite = true)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId, $arPermissions, $bRewrite));
		}
		
		return parent::SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite);
	}
}
