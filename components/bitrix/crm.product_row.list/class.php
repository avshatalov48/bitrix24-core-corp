<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Security\EntityPermissionType;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__ . DIRECTORY_SEPARATOR . 'component.php');

class CCrmProductRowListComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable,
	\Bitrix\Main\Errorable
{
	protected $errorCollection;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return array(
			'~PATH_TO_PRODUCT_FILE',
			'ALLOW_TAX',
			'CURRENCY_ID',
			'PERMISSION_TYPE',
			'PERMISSION_ENTITY_TYPE',
			'OWNER_ID',
			'OWNER_TYPE'
		);
	}

	public function getProductCreateDialogParamsAction()
	{
		$ownerID = $this->getOwnerId();

		$ownerType = $this->getOwnerType();
		$ownerName = CCrmProductRow::ResolveOwnerTypeName($ownerType);
		if ($ownerName === '')
		{
			$this->errorCollection->setError(new Main\Error(GetMessage('CRM_UNSUPPORTED_OWNER_TYPE', array('#OWNER_TYPE#' => $ownerType))));
			return null;
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (!CCrmAuthorizationHelper::CheckReadPermission(
			$this->getPermissionEntityType($ownerName, $ownerID), $ownerID, $userPermissions))
		{
			$this->errorCollection->setError(new Main\Error(GetMessage('CRM_PERMISSION_DENIED')));
			return null;
		}

		$this->initComponent('bitrix:crm.product_row.list');
		$this->initComponentTemplate('');
		$visibleFields = $this->getCreateDialogVisibleFields();
		$propsUSerTypes = $this->getProductPropsTypes();
		return $this->prepareCreateDialogFields(
			$this->getCreateDialogSettings(
				isset($this->arParams['CURRENCY_ID']) ? (string)$this->arParams['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID(),
				$visibleFields,
				isset($this->arParams['ALLOW_TAX']) ? ($this->arParams['ALLOW_TAX'] === 'Y') : CCrmTax::isVatMode()
			),
			$this->getProductProps($propsUSerTypes),
			$propsUSerTypes,
			$visibleFields,
			$this->arParams['PATH_TO_BUYER_EDIT']
		);
	}

	public function getOwnerId()
	{
		return isset($this->arParams['OWNER_ID']) ? (int)$this->arParams['OWNER_ID'] : 0;
	}

	public function getOwnerType()
	{
		// Check owner type (DEAL by default)
		return isset($this->arParams['OWNER_TYPE']) ? (string)$this->arParams['OWNER_TYPE'] : 'D';
	}

	public function getPermissionEntityType($ownerName, $ownerId)
	{
		$permissionEntityType = isset($this->arParams['PERMISSION_ENTITY_TYPE']) ? (string)$this->arParams['PERMISSION_ENTITY_TYPE'] : '';
		if ($permissionEntityType === '')
		{
			$permissionEntityType = CCrmPerms::ResolvePermissionEntityType($ownerName, $ownerId);
		}
		return $permissionEntityType;
	}

	protected function getCreateDialogSettings($currencyId, $visibleFields, $allowTax)
	{
		$measureListItems = $this->getMeasureListItems();
		$htmlPreviewPictureValue = $this->getPictureValueHtml('PREVIEW_PICTURE');
		$htmlDetailPictureValue = $this->getPictureValueHtml('DETAIL_PICTURE');
		return array(
			'formId' => 'crm_product_create_dialog_form',
			'url' => CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_PRODUCT_EDIT'],
				array('product_id' => 0)
			),
			'messages' => array(
				'dialogTitle' => GetMessage('CRM_PRODUCT_CREATE'),
				'waitMessage' => GetMessage('CRM_PRODUCT_CREATE_WAIT'),
				'ajaxError' => GetMessage('CRM_PRODUCT_CREATE_AJAX_ERR'),
				'buttonCreateTitle' => GetMessage('CRM_BUTTON_CREATE_TITLE'),
				'buttonCancelTitle' => GetMessage('CRM_BUTTON_CANCEL_TITLE'),
				'NAME' => GetMessage('CRM_FIELD_PRODUCT_NAME'),
				'DESCRIPTION' => GetMessage('CRM_FIELD_DESCRIPTION'),
				'ACTIVE' => GetMessage('CRM_FIELD_ACTIVE'),
				'CURRENCY' => GetMessage('CRM_FIELD_CURRENCY'),
				'PRICE' => GetMessage('CRM_FIELD_PRICE'),
				'MEASURE' => GetMessage('CRM_FIELD_MEASURE'),
				'VAT_ID' => GetMessage('CRM_FIELD_VAT_ID'),
				'VAT_INCLUDED' => GetMessage('CRM_FIELD_VAT_INCLUDED'),
				'SECTION' => GetMessage('CRM_FIELD_SECTION'),
				'SORT' => GetMessage('CRM_FIELD_SORT'),
				'PREVIEW_PICTURE' => GetMessage('CRM_PRODUCT_FIELD_PREVIEW_PICTURE'),
				'DETAIL_PICTURE' => GetMessage('CRM_PRODUCT_FIELD_DETAIL_PICTURE')
			),
			'fields' => array(
				array('textCode' => 'NAME', 'type' => 'text', 'maxLength' => 255, 'value' => '', 'skip' => 'N',
					'required' => 'Y'),
				array('textCode' => 'DESCRIPTION', 'type' => 'textarea', 'maxLength' => 7500, 'value' => '',
					'skip' => (!CCrmProductHelper::IsFieldVisible('DESCRIPTION', $visibleFields) ? 'Y' : 'N')),
				array('textCode' => 'ACTIVE', 'type' => 'checkbox', 'value' => 'Y', 'skip' => 'Y'),
				array('textCode' => 'CURRENCY', 'type' => 'select', 'value' => CCrmCurrency::GetBaseCurrencyID(),
					'items' => CCrmViewHelper::prepareSelectItemsForJS(CCrmCurrencyHelper::PrepareListItems()),
					'skip' => (!CCrmProductHelper::IsFieldVisible('CURRENCY', $visibleFields) ? 'Y' : 'N')),
				array('textCode' => 'PRICE', 'type' => 'text', 'maxLength' => 21, 'value' => '0.00',
					'skip' => (!CCrmProductHelper::IsFieldVisible('PRICE', $visibleFields) ? 'Y' : 'N')),
				array('textCode' => 'MEASURE', 'type' => 'select', 'value' => '',
					'items' => CCrmViewHelper::prepareSelectItemsForJS($measureListItems),
					'skip' => (!CCrmProductHelper::IsFieldVisible('MEASURE', $visibleFields) ? 'Y' : 'N')),
				array('textCode' => 'VAT_ID', 'type' => 'select', 'value' => '',
					'items' => ($allowTax)
						? CCrmViewHelper::prepareSelectItemsForJS(CCrmVat::GetVatRatesListItems()) : null,
					'skip' => ($allowTax) ? (!CCrmProductHelper::IsFieldVisible('VAT_ID', $visibleFields) ? 'Y' : 'N') : 'Y'),
				array('textCode' => 'VAT_INCLUDED', 'type' => 'checkbox', 'value' => 'N',
					'skip' => ($allowTax) ? (!CCrmProductHelper::IsFieldVisible('VAT_INCLUDED', $visibleFields) ? 'Y' : 'N') : 'Y'),
				array('textCode' => 'SECTION', 'type' => 'select', 'value' => '0',
					'items' => CCrmViewHelper::prepareSelectItemsForJS(
						CCrmProductHelper::PrepareSectionListItems(CCrmCatalog::EnsureDefaultExists())
					), 'skip' => (!CCrmProductHelper::IsFieldVisible('SECTION', $visibleFields) ? 'Y' : 'N')),
				array('textCode' => 'SORT', 'type' => 'text', 'maxLength' => 11, 'value' => 100, 'skip' => 'Y'),
				array('textCode' => 'PREVIEW_PICTURE', 'type' => 'custom', 'value' => $htmlPreviewPictureValue,
					'skip' => (!CCrmProductHelper::IsFieldVisible('PREVIEW_PICTURE', $visibleFields) ? 'Y' : 'N')),
				array('textCode' => 'DETAIL_PICTURE', 'type' => 'custom', 'value' => $htmlDetailPictureValue,
					'skip' => (!CCrmProductHelper::IsFieldVisible('DETAIL_PICTURE', $visibleFields) ? 'Y' : 'N'))
			),
			"ownerCurrencyId" => $currencyId
		);
	}

	public function prepareCreateDialogFields($settings, $props, $userTypeList, $visibleFields, $pathToProductFile)
	{
		$visibleFields = is_array($visibleFields) ? $visibleFields : [];
		$catalogTypeId = CCrmCatalog::GetCatalogTypeID();

		foreach ($props as $propID => $arProp)
		{
			if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
				&& !array_key_exists($arProp['USER_TYPE'], $userTypeList)
			)
			{
				continue;
			}

			$bMultipleListType = ($arProp['PROPERTY_TYPE'] === 'L' && $arProp['MULTIPLE'] === 'Y' && empty($arProp['USER_TYPE']));
			$skip = !CCrmProductHelper::IsFieldVisible($bMultipleListType ? $propID . '[]' : $propID, $visibleFields);
			$defaultValue = array(
				'n0' => array(
					'VALUE' => $arProp['DEFAULT_VALUE'],
					'DESCRIPTION' => '',
				)
			);
			if ($arProp['MULTIPLE'] == 'Y')
			{
				if (is_array($arProp['DEFAULT_VALUE']) || (is_string($arProp['DEFAULT_VALUE']) && $arProp['DEFAULT_VALUE'] != ''))
				{
					$defaultValue['n1'] = array('VALUE' => '', 'DESCRIPTION' => '');
				}
			}

			if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
				&& is_array($userTypeList[$arProp['USER_TYPE']])
				&& $arProp['MULTIPLE'] == 'Y'
				&& array_key_exists('GetPublicEditHTMLMulty', $userTypeList[$arProp['USER_TYPE']])
			)
			{
				$arProp['PROPERTY_USER_TYPE'] = $userTypeList[$arProp['USER_TYPE']];
				$html = call_user_func_array(
					$userTypeList[$arProp['USER_TYPE']]['GetPublicEditHTMLMulty'],
					array(
						$arProp,
						$defaultValue,
						array(
							'VALUE' => $propID,
							'DESCRIPTION' => '',
							'FORM_NAME' => $settings['formId'],
							'MODE' => 'FORM_FILL',
						),
					)
				);

				$settings['messages'][$propID] = $arProp['NAME'];
				$settings['fields'][] = array(
					'textCode' => $propID,
					'type' => 'custom',
					'value' => $html,
					'skip' => $skip ? 'Y' : 'N',
					'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
				);
			}
			else
			{
				if (
					isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
					&& is_array($userTypeList[$arProp['USER_TYPE']])
					&& array_key_exists('GetPublicEditHTML', $userTypeList[$arProp['USER_TYPE']])
				)
				{
					$arProp['PROPERTY_USER_TYPE'] = $userTypeList[$arProp['USER_TYPE']];
					if ($arProp['MULTIPLE'] == 'Y')
					{
						$html = '<table id="tbl' . $propID . '">';
						foreach ($defaultValue as $key => $value)
						{
							$html .= '<tr><td>' . call_user_func_array($userTypeList[$arProp['USER_TYPE']]['GetPublicEditHTML'],
									array(
										$arProp,
										$value,
										array(
											'VALUE' => $propID . '[' . $key . '][VALUE]',
											'DESCRIPTION' => '',
											'FORM_NAME' => $settings['formId'],
											'MODE' => ($arProp['USER_TYPE'] == 'HTML' ? 'SIMPLE' : 'FORM_FILL'),
											'COPY' => false,
										),
									)) . '</td></tr>';
						}
						$html .= '</table>';
						if ($arProp['USER_TYPE'] !== 'HTML')
						{
							$html .= '<input type="button" onclick="addNewTableRow(\'tbl' . $propID . '\')" value="' . GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON') . '">';
						}

						$settings['messages'][$propID] = $arProp['NAME'];
						$settings['fields'][] = array(
							'textCode' => $propID,
							'type' => 'custom',
							'value' => $html,
							'skip' => $skip ? 'Y' : 'N',
							'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
						);
					}
					else
					{
						foreach ($defaultValue as $key => $value)
						{
							$html = call_user_func_array($userTypeList[$arProp['USER_TYPE']]['GetPublicEditHTML'],
								array(
									$arProp,
									$value,
									array(
										'VALUE' => $propID . '[' . $key . '][VALUE]',
										'DESCRIPTION' => '',
										'FORM_NAME' => $settings['formId'],
										'MODE' => ($arProp['USER_TYPE'] == 'HTML' ? 'SIMPLE' : 'FORM_FILL'),
									),
								));
							break;
						}
						$settings['messages'][$propID] = $arProp['NAME'];
						$settings['fields'][] = array(
							'textCode' => $propID,
							'type' => 'custom',
							'value' => $html,
							'skip' => $skip ? 'Y' : 'N',
							'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
						);
					}
				}
				else
				{
					if ($arProp['PROPERTY_TYPE'] == 'N')
					{
						if ($arProp['MULTIPLE'] == 'Y')
						{
							$html = '<table id="tbl' . $propID . '">';
							foreach ($defaultValue as $key => $value)
								$html .= '<tr><td><input type="text" name="' . $propID . '[' . $key . '][VALUE]" value="' . $value['VALUE'] . '"></td></tr>';
							$html .= '</table>';
							$html .= '<input type="button" onclick="addNewTableRow(\'tbl' . $propID . '\')" value="' . GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON') . '">';
						}
						else
						{
							foreach ($defaultValue as $key => $value)
								$html = '<input type="text" name="' . $propID . '[' . $key . '][VALUE]" value="' . $value['VALUE'] . '">';
						}

						$settings['messages'][$propID] = $arProp['NAME'];
						$settings['fields'][] = array(
							'textCode' => $propID,
							'type' => 'custom',
							'value' => $html,
							'skip' => $skip ? 'Y' : 'N',
							'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
						);
					}
					else
					{
						if ($arProp['PROPERTY_TYPE'] == 'S')
						{
							$nCols = intval($arProp['COL_COUNT']);
							$nCols = ($nCols > 100) ? 100 : $nCols;
							if ($arProp['MULTIPLE'] == 'Y')
							{
								$html = '<table id="tbl' . $propID . '">';
								if ($arProp['ROW_COUNT'] > 1)
								{
									foreach ($defaultValue as $key => $value)
									{
										$html .= '<tr><td><textarea name="' . $propID . '[' . $key . '][VALUE]" rows="' . intval($arProp['ROW_COUNT']) . '" cols="' . $nCols . '">' . $value['VALUE'] . '</textarea></td></tr>';
									}
								}
								else
								{
									foreach ($defaultValue as $key => $value)
									{
										$html .= '<tr><td><input type="text" name="' . $propID . '[' . $key . '][VALUE]" value="' . $value['VALUE'] . '"></td></tr>';
									}
								}
								$html .= '</table>';
								$html .= '<input type="button" onclick="addNewTableRow(\'tbl' . $propID . '\')" value="' . GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON') . '">';
							}
							else
							{
								if ($arProp['ROW_COUNT'] > 1)
								{
									foreach ($defaultValue as $key => $value)
									{
										$html = '<textarea name="' . $propID . '[' . $key . '][VALUE]" rows="' . intval($arProp['ROW_COUNT']) . '" cols="' . $nCols . '">' . $value['VALUE'] . '</textarea>';
									}
								}
								else
								{
									foreach ($defaultValue as $key => $value)
									{
										$html = '<input type="text" name="' . $propID . '[' . $key . '][VALUE]" value="' . $value['VALUE'] . '" size="' . $nCols . '">';
									}
								}
							}
							unset($nCols);

							$settings['messages'][$propID] = $arProp['NAME'];
							$settings['fields'][] = array(
								'textCode' => $propID,
								'type' => 'custom',
								'value' => $html,
								'skip' => $skip ? 'Y' : 'N',
								'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
							);
						}
						else
						{
							if ($arProp['PROPERTY_TYPE'] == 'L')
							{
								$items = array('' => GetMessage('CRM_PRODUCT_PROP_NO_VALUE'));
								$prop_enums = CIBlockProperty::GetPropertyEnum($arProp['ID']);
								$defaultValue = '';
								while ($ar_enum = $prop_enums->Fetch())
								{
									$items[$ar_enum['ID']] = $ar_enum['VALUE'];
									if ('Y' === $ar_enum['DEF'])
									{
										if ($defaultValue === '')
										{
											$defaultValue = array($ar_enum['ID']);
										}
										else
										{
											if (is_array($defaultValue))
											{
												$defaultValue[] = $ar_enum['ID'];
											}
										}
									}
								}
								if ($arProp['MULTIPLE'] == 'Y')
								{
									$settings['messages'][$propID . '[]'] = $arProp['NAME'];
									$rowCount = 5;
									if (isset($arProp['ROW_COUNT']) && intval($arProp['ROW_COUNT']) > 0)
									{
										$rowCount = intval($arProp['ROW_COUNT']);
									}
									$settings['fields'][] = array(
										'textCode' => $propID . '[]',
										'type' => 'select',
										'value' => $defaultValue,
										'items' => CCrmViewHelper::prepareSelectItemsForJS($items),
										'skip' => $skip ? 'Y' : 'N',
										'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N',
										'params' => array('size' => $rowCount, 'multiple' => 'multiple')
									);
									unset($rowCount);
								}
								else
								{
									$settings['messages'][$propID] = $arProp['NAME'];
									$settings['fields'][] = array(
										'textCode' => $propID,
										'type' => 'select',
										'value' => $defaultValue,
										'items' => CCrmViewHelper::prepareSelectItemsForJS($items),
										'skip' => $skip ? 'Y' : 'N',
										'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
									);
								}
							}
							else
							{
								if ($arProp['PROPERTY_TYPE'] == 'F')
								{
									if ($arProp['MULTIPLE'] == 'Y')
									{
										$html = '<table id="tbl' . $propID . '">';
										foreach ($defaultValue as $key => $value)
										{
											$html .= '<tr><td>';

											$obFile = new CCrmProductFile(
												0,
												$propID,
												$value['VALUE']
											);

											$obFileControl = new CCrmProductFileControl($obFile, $propID . '[' . $key . '][VALUE]');

											$html .= $obFileControl->GetHTML(array(
												'max_size' => 102400,
												'max_width' => 150,
												'max_height' => 150,
												'url_template' => $pathToProductFile,
												'a_title' => GetMessage('CRM_PRODUCT_FILE_ENLARGE'),
												'download_text' => GetMessage('CRM_PRODUCT_FILE_DOWNLOAD'),
											));

											$html .= '</td></tr>';
										}
										$html .= '</table>';
										$html .= '<input type="button" onclick="addNewTableRow(\'tbl' . $propID . '\')" value="' . GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON') . '">';

										$settings['messages'][$propID] = $arProp['NAME'];
										$settings['fields'][] = array(
											'textCode' => $propID,
											'type' => 'custom',
											'value' => $html,
											'skip' => $skip ? 'Y' : 'N',
											'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
										);
									}
									else
									{
										foreach ($defaultValue as $key => $value)
										{
											$obFile = new CCrmProductFile(
												0,
												$propID,
												$value['VALUE']
											);

											$obFileControl = new CCrmProductFileControl($obFile, $propID . '[' . $key . '][VALUE]');

											$html = $obFileControl->GetHTML(array(
												'max_size' => 102400,
												'max_width' => 150,
												'max_height' => 150,
												'url_template' => $pathToProductFile,
												'a_title' => GetMessage('CRM_PRODUCT_FILE_ENLARGE'),
												'download_text' => GetMessage('CRM_PRODUCT_FILE_DOWNLOAD'),
											));

											$settings['messages'][$propID] = $arProp['NAME'];
											$settings['fields'][] = array(
												'textCode' => $propID,
												'type' => 'custom',
												'value' => $html,
												'skip' => $skip ? 'Y' : 'N',
												'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
											);
										}
									}
								}
								else
								{
									if ($arProp['PROPERTY_TYPE'] == 'G')
									{
									}
									else
									{
										if ($arProp['PROPERTY_TYPE'] == 'E')
										{
											if ($arProp['IS_REQUIRED'] == 'Y')
											{
												$items = array();
											}
											else
											{
												$items = array('' => GetMessage('CRM_PRODUCT_PROP_NO_VALUE'));
											}

											$rsElements = CIBlockElement::GetList(array('NAME' => 'ASC'), array('IBLOCK_ID' => $arProp['LINK_IBLOCK_ID']), false, false, array('ID', 'NAME'));
											while ($ar = $rsElements->Fetch())
												$items[$ar['ID']] = $ar['NAME'];

											ob_start();

											$arValues = array();
											if (is_array($defaultValue))
											{
												foreach (array_keys($defaultValue) as $key)
													if ($key > 0 && array_key_exists($key, $items))
													{
														$arValues[] = $items[$key] . ' [' . $key . ']';
													}
											}
											?><input type="hidden" name="<? echo $propID ?>[]"
													 value=""><? //This will emulate empty input
											$control_id = $GLOBALS['APPLICATION']->IncludeComponent(
												'bitrix:main.lookup.input',
												'elements',
												array(
													'INPUT_NAME' => $propID,
													'INPUT_NAME_STRING' => 'inp_' . $propID,
													'INPUT_VALUE_STRING' => implode("\n", $arValues),
													'START_TEXT' => GetMessage('CRM_PRODUCT_PROP_START_TEXT'),
													'MULTIPLE' => $arProp['MULTIPLE'],
													//These params will go throught ajax call to ajax.php in template
													'IBLOCK_TYPE_ID' => $catalogTypeId,
													'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
													'SOCNET_GROUP_ID' => '',
												), $this, array('HIDE_ICONS' => 'Y')
											);

											$name = $GLOBALS['APPLICATION']->IncludeComponent(
												'bitrix:main.tree.selector',
												'elements',
												array(
													'INPUT_NAME' => $propID,
													'ONSELECT' => 'jsMLI_' . $control_id . '.SetValue',
													'MULTIPLE' => $arProp['MULTIPLE'],
													'SHOW_INPUT' => 'N',
													'SHOW_BUTTON' => 'N',
													'GET_FULL_INFO' => 'Y',
													'START_TEXT' => GetMessage('CRM_PRODUCT_PROP_START_TEXT'),
													'NO_SEARCH_RESULT_TEXT' => GetMessage('CRM_PRODUCT_PROP_NO_SEARCH_RESULT_TEXT'),
													//These params will go throught ajax call to ajax.php in template
													'IBLOCK_TYPE_ID' => $catalogTypeId,
													'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
													'SOCNET_GROUP_ID' => '',
												), $this, array('HIDE_ICONS' => 'Y')
											);
											?><a href="javascript:void(0)"
												 onclick="<?= $name ?>.SetValue([]); <?= $name ?>.Show()"><? echo GetMessage('CRM_PRODUCT_PROP_CHOOSE_ELEMENT') ?></a><?

											$html = ob_get_contents();
											ob_end_clean();

											$settings['messages'][$propID] = $arProp['NAME'];
											$settings['fields'][] = array(
												'textCode' => $propID,
												'type' => 'custom',
												'value' => $html,
												'skip' => $skip ? 'Y' : 'N',
												'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
											);
										}
										else
										{
											if ($arProp['MULTIPLE'] == 'Y')
											{
												$html = '<table id="tbl' . $propID . '">';
												foreach ($defaultValue as $key => $value)
													$html .= '<tr><td><input type="text" name="' . $propID . '[' . $key . '][VALUE]" value="' . $value['VALUE'] . '"></td></tr>';
												$html .= '</table>';
												$html .= '<input type="button" onclick="addNewTableRow(\'tbl' . $propID . '\')" value="' . GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON') . '">';

												$settings['messages'][$propID] = $arProp['NAME'];
												$settings['fields'][] = array(
													'textCode' => $propID,
													'type' => 'custom',
													'value' => $html,
													'skip' => $skip ? 'Y' : 'N',
													'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
												);
											}
											else
											{
												if (is_array($defaultValue) && array_key_exists('VALUE', $defaultValue))
												{
													$settings['messages'][$propID . '[VALUE]'] = $arProp['NAME'];
													$settings['fields'][] = array(
														'textCode' => $propID . '[VALUE]',
														'type' => 'text',
														'value' => $defaultValue['VALUE'],
														'skip' => $skip ? 'Y' : 'N',
														'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
													);
												}
												else
												{
													$settings['messages'][$propID] = $arProp['NAME'];
													$settings['fields'][] = array(
														'textCode' => $propID,
														'type' => 'text',
														'skip' => $skip ? 'Y' : 'N',
														'required' => $arProp['IS_REQUIRED'] == 'Y' ? 'Y' : 'N'
													);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if (is_array($visibleFields) && count($visibleFields) > 0
			&& is_array($settings['fields'])
			&& count($settings['fields']) > 0)
		{
			$fields = $settings['fields'];
			$fieldsIndex = array();
			foreach ($fields as $index => $field)
			{
				$fieldsIndex[$field['textCode']] = array(
					'index' => $index,
					'ordered' => false
				);
			}
			$orderedFields = array();
			foreach ($visibleFields as $fieldName)
			{
				if (isset($fieldsIndex[$fieldName]))
				{
					$orderedFields[] = $fields[$fieldsIndex[$fieldName]['index']];
					$fieldsIndex[$fieldName]['ordered'] = true;
				}
			}
			foreach ($fieldsIndex as $index)
			{
				if ($index['ordered'] === false)
				{
					$orderedFields[] = $fields[$index['index']];
				}
			}
			$settings['fields'] = $orderedFields;
		}
		return $settings;
	}

	protected function getProductPropsTypes()
	{
		return CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'edit');
	}

	protected function getProductProps($arPropUserTypeList)
	{
		$catalogID = CCrmCatalog::EnsureDefaultExists();
		return CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList);
	}

	protected function getCreateDialogVisibleFields()
	{
		$visibleFields = array();
		$productFormOptions = CUserOptions::GetOption('main.interface.form', 'CRM_PRODUCT_EDIT', array());
		if (is_array($productFormOptions)
			&& is_array($productFormOptions['tabs']) && count($productFormOptions['tabs'])
			&& (!isset($productFormOptions['settings_disabled']) || $productFormOptions['settings_disabled'] !== 'Y'))
		{
			$tabFound = false;
			$tab = null;
			foreach ($productFormOptions['tabs'] as $tab)
			{
				if (isset($tab['id']) && $tab['id'] === 'tab_1')
				{
					$tabFound = true;
					break;
				}
			}
			if ($tabFound)
			{
				if (is_array($tab) && is_array($tab['fields']))
				{
					foreach ($tab['fields'] as $field)
					{
						if (isset($field['type']) && isset($field['id']) && $field['type'] !== 'section')
						{
							$visibleFields[] = $field['id'];
						}
					}
				}
			}
		}
		return $visibleFields;
	}

	protected function getMeasureListItems()
	{
		// measure list items
		$measureListItems = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
		$measures = \Bitrix\Crm\Measure::getMeasures(0);
		if (is_array($measures))
		{
			foreach ($measures as $measure)
				$measureListItems[$measure['ID']] = $measure['SYMBOL'];
			unset($measure);
		}
		return $measureListItems;
	}

	protected function getPictureValueHtml($fieldId)
	{
		$obFile = new CCrmProductFile(
			0,
			$fieldId,
			''
		);

		$obFileControl = new CCrmProductFileControl($obFile, $fieldId);

		return $obFileControl->GetHTML(array(
			'max_size' => 102400,
			'max_width' => 150,
			'max_height' => 150,
			'url_template' => $this->arParams['PATH_TO_PRODUCT_FILE'],
			'a_title' => GetMessage('CRM_PRODUCT_FILE_ENLARGE'),
			'download_text' => GetMessage('CRM_PRODUCT_FILE_DOWNLOAD'),
		));
	}
}