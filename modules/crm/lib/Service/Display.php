<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\Settings\LayoutSettings;

class Display
{
	/** @var $displayedFields \Bitrix\Crm\Service\Display\Field[] */
	protected $displayedFields = [];
	/** @var $displayOptions Options */
	protected $displayOptions;
	protected $entityTypeId;

	protected $items = [];

	public function __construct(int $entityTypeId, array $displayedFields, Options $displayOptions = null)
	{
		$this->entityTypeId = $entityTypeId;
		$this->displayedFields = $displayedFields;

		$this->displayOptions = $displayOptions ?? (new Options());
	}

	public function setDisplayOptions(Options $options): Display
	{
		$this->displayOptions = $options;

		return $this;
	}

	public function addItem(int $itemId, array $itemFieldsValues): Display
	{
		$this->items[$itemId] = $itemFieldsValues;

		return $this;
	}

	public function setItems(array $items): Display
	{
		$this->items = $items;

		return $this;
	}

	public function getValue(int $itemId, string $fieldName)
	{
		$values = $this->getValues($itemId);

		return $values[$fieldName] ?? null;
	}

	public function getValues(int $itemId): ?array
	{
		$allValues = $this->getAllValues();

		return $allValues[$itemId] ?? null;
	}

	public function getAllValues(): array
	{
		return $this->processValues($this->items);
	}

	protected function processValues(array $items): array
	{
		$result = [];

		$restrictedItemIds = $this->displayOptions->getRestrictedItemIds();
		$restrictedFieldsToShow = $this->displayOptions->getRestrictedFieldsToShow();
		$restrictedValueReplacer = $this->displayOptions->needUseTextMode()
			? $this->displayOptions->getRestrictedValueTextReplacer()
			: $this->displayOptions->getRestrictedValueHtmlReplacer();
		$needLoadLinkedEntitiesValues = false;
		$linkedValuesIds = [];

		foreach ($this->displayedFields as $fieldId => $displayedField)
		{
			$isMultiple = $displayedField->isMultiple();

			foreach ($items as $itemId => $item)
			{
				$fieldValue = $items[$itemId][$fieldId];
				if ($displayedField->isValueEmpty($fieldValue))
				{
					if ($displayedField->isMultiple() && $displayedField->isUserField())
					{
						if (!isset($result[$itemId]))
						{
							$result[$itemId] = [];
						}
						$result[$itemId][$fieldId] = ''; // multiple user fields should be converted from empty array to empty string
					}
					continue;
				}
				$fieldType = $displayedField->getType();
				$displayParams = $displayedField->getDisplayParams();

				if (!isset($result[$itemId]))
				{
					$result[$itemId] = [];
				}
				if (in_array($itemId, $restrictedItemIds) && !in_array($fieldId, $restrictedFieldsToShow))
				{
					$result[$itemId][$fieldId] = $restrictedValueReplacer;
					if (!$this->displayOptions->needUseTextMode() && !empty($restrictedValueReplacer))
					{
						$displayedField->setWasRenderedAsHtml(true);
					}
					continue;
				}
				if ($fieldType === 'string' || $fieldType === 'text')
				{
					$valueType = $displayParams['VALUE_TYPE'] ?? \Bitrix\Crm\Field::VALUE_TYPE_PLAIN_TEXT;
					if ($valueType === \Bitrix\Crm\Field::VALUE_TYPE_HTML)
					{
						$displayedField->setWasRenderedAsHtml(true);
					}
				}

				if ($displayedField->needDisplayRawValue())
				{
					$result[$itemId][$fieldId] = $fieldValue;

					continue;
				}

				if (
					($fieldType === 'date' || $fieldType === 'datetime')
					&& !$displayedField->isUserField()
				)
				{
					$fieldValueArray = is_array($fieldValue) ? $fieldValue : [ $fieldValue ];
					foreach ($fieldValueArray as $value)
					{
						$format =
							$displayParams['DATETIME_FORMAT']
								?? $this->getDefaultDatetimeFormat($fieldType === 'datetime')
						;

						$timeZoneOffset = \CTimeZone::GetOffset();
						if ($value instanceof \Bitrix\Main\Type\DateTime || $value instanceof \DateTime)
						{
							$timestamp = $value->getTimestamp() + $timeZoneOffset;
						}
						elseif ($value instanceof \Bitrix\Main\Type\Date)
						{
							$timestamp = $value->getTimestamp();
						}
						else
						{
							$timestamp = \MakeTimeStamp($value);
						}
						$formattedValue = \FormatDate($format, $timestamp, time() + $timeZoneOffset);

						if ($isMultiple)
						{
							if (!isset($result[$itemId][$fieldId]))
							{
								$result[$itemId][$fieldId] = [];
							}
							$result[$itemId][$fieldId][] = $formattedValue;
						}
						else
						{
							$result[$itemId][$fieldId] = $formattedValue;
						}
					}
				}
				elseif ($fieldType === 'boolean')
				{
					foreach ((array)$fieldValue as $value)
					{
						$yesNoValue =
							($value === true || $value === 'Y' || $value === 1 || $value === '1')
								? Loc::getMessage('MAIN_YES')
								: Loc::getMessage('MAIN_NO');
						if ($isMultiple)
						{
							if (!isset($result[$itemId][$fieldId]))
							{
								$result[$itemId][$fieldId] = [];
							}
							$result[$itemId][$fieldId][] = $yesNoValue;
						}
						else
						{
							$result[$itemId][$fieldId] = $yesNoValue;
						}
					}
				}
				elseif ($fieldType === 'crm_status')
				{
					$statuses = isset($displayParams['ENTITY_TYPE'])
						? \CCrmStatus::GetStatusList($displayParams['ENTITY_TYPE'])
						: []
					;

					foreach ((array)$fieldValue as $value)
					{
						if (isset($statuses[$value]))
						{
							$encodedStatusValue = \Bitrix\Main\Text\HtmlFilter::encode($statuses[$value]);
							if ($isMultiple)
							{
								if (!isset($result[$itemId][$fieldId]))
								{
									$result[$itemId][$fieldId] = [];
								}
								$result[$itemId][$fieldId][] = $encodedStatusValue;
							}
							else
							{
								$result[$itemId][$fieldId] = $encodedStatusValue;
							}
						}
					}
					$displayedField->setWasRenderedAsHtml(true);
				}
				elseif ($fieldType === 'crm_currency')
				{
					foreach ((array)$fieldValue as $value)
					{
						$currencyName = \CCrmCurrency::GetByID($value)['FULL_NAME'] ?? $value;
						if ($isMultiple)
						{
							if (!isset($result[$itemId][$fieldId]))
							{
								$result[$itemId][$fieldId] = [];
							}
							$result[$itemId][$fieldId][] = $currencyName;
						}
						else
						{
							$result[$itemId][$fieldId] = $currencyName;
						}
					}
				}
				elseif ($fieldType === 'crm')
				{
					if (!isset($result[$itemId][$fieldId]))
					{
						$result[$itemId][$fieldId] = $isMultiple ? [] : '';
					}

					$crmEntityType = [];
					$entityTypes = array_flip(ElementType::getEntityTypeNames());
					foreach ($displayParams as $settingsEntityTypeId => $value)
					{
						if (
							$value === 'Y'
							&& (
								array_key_exists($settingsEntityTypeId, $entityTypes)
								|| \CCrmOwnerType::isPossibleDynamicTypeId(\CCrmOwnerType::ResolveID($settingsEntityTypeId))
							)
						)
						{
							$crmEntityType[] = $settingsEntityTypeId;
						}
					}

					$needLoadLinkedEntitiesValues = true;

					foreach ((array)$fieldValue as $value)
					{
						if (count($crmEntityType) > 1 || !is_numeric($value))
						{
							$valueParts = explode('_', $value);
							$entityType = ElementType::getLongEntityType($valueParts[0]);
							$entityId = (int)$valueParts[1];
						}
						else
						{
							$entityType = $crmEntityType[0];
							$entityId =(int)$value;
						}
						if ($entityId > 0)
						{
							$linkedValuesIds[$fieldType][$entityType][] = $entityId;
							$linkedValuesIds[$fieldType]['FIELD'][$itemId][$fieldId][$entityType][$entityId] = $entityId;
						}
					}
				}
				elseif (
					$fieldType === 'file'
					|| $fieldType === 'employee'
					|| $fieldType === 'user'
					|| $fieldType === 'iblock_element'
					|| $fieldType === 'enumeration'
					|| $fieldType === 'iblock_section'
				)
				{
					if (!isset($result[$itemId][$fieldId]))
					{
						$result[$itemId][$fieldId] = $isMultiple ? [] : '';
					}

					$needLoadLinkedEntitiesValues = true;
					foreach ((array)$fieldValue as $value)
					{
						if ($value === '' || $value <= 0)
						{
							continue;
						}
						$linkedValuesIds[$fieldType]['FIELD'][$itemId][$fieldId][$value] = $value;
						$linkedValuesIds[$fieldType]['ID'][] = $value;
					}
				}
				elseif (
					$fieldType === 'resourcebooking'
					&& $this->displayOptions->needUseTextMode()
					&& Loader::includeModule('calendar')
					&& \Bitrix\Crm\Integration\Calendar::isResourceBookingEnabled()
				)
				{
					$value = [];
					if (is_array($fieldValue))
					{
						$value = array_values(
							array_map('htmlspecialcharsback', $fieldValue)
						);
					}

					$result[$itemId][$fieldId] = \Bitrix\Calendar\UserField\ResourceBooking::getPublicText(
						array_merge(
							$displayedField->getUserFieldParams(),
							[
								'ENTITY_VALUE_ID' => $itemId,
								'VALUE' => $value,
							]
						)
					);
				}
				elseif (
					!$this->displayOptions->needUseTextMode()
					&& (
						$fieldType === 'address'
						|| $fieldType === 'money'
						|| $fieldType === 'url'
						|| $fieldType === 'resourcebooking'
					)
				)
				{
					if ($isMultiple)
					{
						$value = [];
						if (is_array($fieldValue))
						{
							$value = array_values(
								array_map('htmlspecialcharsback', $fieldValue)
							);
						}
						if ($this->displayOptions->needReturnMultipleFieldsAsSingle())
						{
							$result[$itemId][$fieldId] = $this->renderUserField($displayedField, $itemId, $value);
						}
						else
						{
							if (!empty($value))
							{
								$result[$itemId][$fieldId] = [];
								foreach ($value as $valueArrayItem)
								{
									$result[$itemId][$fieldId][] = $this->renderUserField($displayedField, $itemId, [$valueArrayItem]);
								}

							}
						}
					}
					else
					{
						$value = htmlspecialcharsback($fieldValue);
						$result[$itemId][$fieldId] = $this->renderUserField($displayedField, $itemId, $value);
					}


				}
				elseif ($isMultiple && is_array($fieldValue))
				{
					$value = array_values(
						array_map('htmlspecialcharsback', $fieldValue)
					);

					if ($this->displayOptions->needReturnMultipleFieldsAsSingle())
					{
						$result[$itemId][$fieldId] = $this->renderUserField($displayedField, $itemId, $value);
					}
					else
					{
						if (!empty($value))
						{
							$result[$itemId][$fieldId] = [];
							foreach ($value as $valueArrayItem)
							{
								$result[$itemId][$fieldId][] = $this->renderUserField($displayedField, $itemId, [$valueArrayItem]);
							}

						}
					}
				}
				elseif (!$isMultiple && $fieldValue !== '')
				{
					$result[$itemId][$fieldId] = $this->renderUserField($displayedField, $itemId, htmlspecialcharsback($fieldValue));
				}
			}
		}

		// The second loop for special field
		if ($needLoadLinkedEntitiesValues)
		{
			$fileViewer = new \Bitrix\Crm\UserField\FileViewer(
				$this->displayOptions->getFileEntityTypeId() ?? $this->entityTypeId
			);
			$linkedEntitiesValues = [];
			foreach ($linkedValuesIds as $fieldType => $linkedEntity)
			{
				// collect multi data
				if (
					$fieldType === 'iblock_section'
					&& !empty($linkedEntity['ID'])
					&& Loader::includeModule('iblock')
				)
				{
					$iblockSectionList = \CIBlockSection::GetList(
						[],
						[
							'=ID' => $linkedEntity['ID'],
						],
						false,
						[
							'ID',
							'NAME',
						]
					);
					while ($iblockSection = $iblockSectionList->Fetch())
					{
						$linkedEntitiesValues[$fieldType][$iblockSection['ID']] = $iblockSection;
					}
				}
				elseif ($fieldType === 'file')
				{
					$filesList = \CFile::GetList(
						[],
						[
							'@ID' => $linkedEntity['ID'],
						]
					);
					while ($file = $filesList->Fetch())
					{
						$linkedEntitiesValues[$fieldType][$file['ID']] = $file;
					}
				}
				elseif (
					$fieldType === 'iblock_element'
					&& !empty($linkedEntity['ID'])
					&& Loader::includeModule('iblock')
				)
				{
					$iblockElementList = \CIBlockElement::GetList(
						[],
						[
							'=ID' => $linkedEntity['ID'],
						],
						false,
						false,
						[
							'ID',
							'NAME',
							'DETAIL_PAGE_URL',
						]
					);
					while ($iblockElement = $iblockElementList->GetNext())
					{
						$linkedEntitiesValues[$fieldType][$iblockElement['ID']] = $iblockElement;
					}
				}
				elseif ($fieldType === 'employee' || $fieldType === 'user')
				{
					$linkedEntitiesValues[$fieldType] = Container::getInstance()->getUserBroker()->getBunchByIds($linkedEntity['ID']);
				}
				elseif ($fieldType === 'enumeration')
				{
					foreach ($linkedEntity['ID'] as $enumId)
					{
						$enumList = \CUserFieldEnum::GetList(
							[],
							[
								'ID' => $enumId,
							]
						);
						while ($enumValue = $enumList->Fetch())
						{
							$linkedEntitiesValues[$fieldType][$enumValue['ID']] = $enumValue;
						}
					}
				}
				elseif ($fieldType === 'crm')
				{
					if (isset($linkedEntity['LEAD']) && !empty($linkedEntity['LEAD']))
					{
						$crmItemsList = \CCrmLead::GetListEx(
							[],
							[
								'=ID' => $linkedEntity['LEAD'],
							],
							false,
							false,
							[
								'ID',
								'TITLE',
							]
						);
						while ($crmItem = $crmItemsList->Fetch())
						{
							$linkedEntitiesValues[$fieldType]['LEAD'][$crmItem['ID']] = $crmItem;
						}
					}
					if (isset($linkedEntity['CONTACT']) && !empty($linkedEntity['CONTACT']))
					{
						$crmItemsList = \CCrmContact::GetListEx(
							[],
							[
								'=ID' => $linkedEntity['CONTACT'],
							],
							false,
							false,
							[
								'ID',
								'HONORIFIC',
								'NAME',
								'SECOND_NAME',
								'LAST_NAME',
							]
						);
						while ($crmItem = $crmItemsList->Fetch())
						{
							$linkedEntitiesValues[$fieldType]['CONTACT'][$crmItem['ID']] = $crmItem;
						}
					}
					if (isset($linkedEntity['COMPANY']) && !empty($linkedEntity['COMPANY']))
					{
						$crmItemsList = \CCrmCompany::GetListEx(
							[],
							[
								'=ID' => $linkedEntity['COMPANY'],
							],
							false,
							false,
							[
								'ID',
								'TITLE',
							]
						);
						while ($crmItem = $crmItemsList->Fetch())
						{
							$linkedEntitiesValues[$fieldType]['COMPANY'][$crmItem['ID']] = $crmItem;
						}
					}
					if (isset($linkedEntity['DEAL']) && !empty($linkedEntity['DEAL']))
					{
						$crmItemsList = \CCrmDeal::GetListEx(
							[],
							[
								'=ID' => $linkedEntity['DEAL'],
							],
							false,
							false,
							[
								'ID',
								'TITLE',
							]
						);
						while ($crmItem = $crmItemsList->Fetch())
						{
							$linkedEntitiesValues[$fieldType]['DEAL'][$crmItem['ID']] = $crmItem;
						}
					}
					foreach ($linkedEntity as $entityTypeName => $entityIds)
					{
						$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
						if (
							$entityTypeId
							&& \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
							&& !empty($entityIds)
						)
						{
							$factory = Container::getInstance()->getFactory($entityTypeId);
							if ($factory)
							{
								$dynamicEntityList = $factory->getItemsFilteredByPermissions([
									'select' => ['ID', 'TITLE'],
									'filter' => ['@ID' => $entityIds],
								]);
								foreach ($dynamicEntityList as $dynamicEntity)
								{
									$linkedEntitiesValues[$fieldType][$entityTypeName][$dynamicEntity->getId()] =
										$dynamicEntity->getCompatibleData()
									;
								}
							}
						}
					}
				}

				foreach ($linkedEntity['FIELD'] as $itemId => $item)
				{
					foreach ($item as $fieldId => $fieldValue)
					{
						$displayedField = $this->displayedFields[$fieldId];
						$fieldType = $displayedField->getType();
						$isMultiple = $displayedField->isMultiple();
						$displayParams = $displayedField->getDisplayParams();

						foreach ($fieldValue as $fieldValueType => $fieldValueId)
						{
							$formattedValue = '';
							if ($fieldType === 'crm')
							{
								$entityTypeId = \CCrmOwnerType::ResolveID($fieldValueType);
								foreach ($fieldValueId as $crmEntityId)
								{
									$link = Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $crmEntityId);
									$title = null;
									$prefix = '';
									$tooltipLoader = null;
									$className = null;
									if ($fieldValueType === 'LEAD')
									{
										$title = $linkedEntitiesValues[$fieldType]['LEAD'][$crmEntityId]['TITLE'] ?? null;
										$prefix = 'L';
										$className = 'crm_balloon_no_photo';
									}
									elseif ($fieldValueType === 'CONTACT')
									{
										if (isset($linkedEntitiesValues[$fieldType]['CONTACT'][$crmEntityId]))
										{
											$title =
												isset($linkedEntitiesValues[$fieldType]['CONTACT'][$crmEntityId])
													? \CCrmContact::PrepareFormattedName(
													$linkedEntitiesValues[$fieldType]['CONTACT'][$crmEntityId]
												)
													: null
											;
										}
										$prefix = 'C';
									}
									elseif ($fieldValueType === 'COMPANY')
									{
										$title = $linkedEntitiesValues[$fieldType]['COMPANY'][$crmEntityId]['TITLE'] ?? null;
										$prefix = 'CO';
									}
									elseif ($fieldValueType === 'DEAL')
									{
										$title = $linkedEntitiesValues[$fieldType]['DEAL'][$crmEntityId]['TITLE'] ?? null;;
										$prefix = 'D';
										$className = 'crm_balloon_no_photo';
									}
									elseif ($fieldValueType === 'ORDER')
									{
										$title = $linkedEntitiesValues[$fieldType]['ORDER'][$crmEntityId]['TITLE'] ?? null;;
										$prefix = 'O';
									}
									elseif ($fieldValueType === 'QUOTE')
									{
										$className = 'crm_balloon_no_photo';
									}
									elseif (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
									{
										$title = $linkedEntitiesValues[$fieldType][$fieldValueType][$crmEntityId]['TITLE'] ?? null;
										$prefix = 'T';
										$tooltipLoader = UrlManager::getInstance()->create(
											'bitrix:crm.controller.tooltip.card',
											[
												'sessid' => bitrix_sessid(),
											]
										)
										;
										$className = 'crm_balloon_no_photo';
										$crmEntityId = $entityTypeId . '-' . $crmEntityId;
									}

									$formattedValue = '';
									if ($title !== null)
									{
										$formattedValue = htmlspecialcharsbx($title);
										if (!$this->displayOptions->needUseTextMode())
										{
											\Bitrix\Main\UI\Extension::load("ui.tooltip");
											$tooltipLoader = (
												$tooltipLoader
												?? htmlspecialcharsbx(
													'/bitrix/components/bitrix/crm.'
													. mb_strtolower($fieldValueType)
													. '.show/card.ajax.php'
												)
											);
											$className = ($className ?? 'crm_balloon_' . mb_strtolower($fieldValueType));

											$formattedValue = $this->getHtmlLink($link, $crmEntityId, $tooltipLoader, $className, $formattedValue);
										}
										else
										{
											if ($displayedField->isUserField())
											{
												$formattedValue = "[$prefix]$formattedValue";
											}
										}
									}

									$displayedField->setWasRenderedAsHtml(true);
								}
							}
							else
							{
								$entityValue = $linkedEntitiesValues[$fieldType][$fieldValueId];
								if ($fieldType === 'iblock_section')
								{
									$formattedValue = htmlspecialcharsbx($entityValue['NAME']);
									$displayedField->setWasRenderedAsHtml(true);
								}
								elseif ($fieldType === 'iblock_element')
								{
									$formattedValue = $entityValue['NAME'];
									$displayedField->setWasRenderedAsHtml(true);
									if (!$this->displayOptions->needUseTextMode())
									{
										$detailUrl = $entityValue['DETAIL_PAGE_URL'];
										if ($detailUrl != '')
										{
											$formattedValue = '<a href="' . $detailUrl . '">' . $formattedValue . '</a>';
										}
									}
								}
								elseif ($fieldType === 'employee' || $fieldType === 'user')
								{
									if (is_array($entityValue))
									{
										$formattedValue = $entityValue['FORMATTED_NAME'];
										if (!$this->displayOptions->needUseTextMode())
										{
											$customUrlTemplate = $displayParams['SHOW_URL_TEMPLATE'] ?? '';
											if ($customUrlTemplate !== '')
											{
												$entityValue['SHOW_URL'] = str_replace(
													'#user_id#',
													$entityValue['ID'],
													$customUrlTemplate
												);
											}
											if (isset($displayParams['AS_ARRAY']) && $displayParams['AS_ARRAY'])
											{
												$formattedValue = [
													'link' => htmlspecialcharsbx($entityValue['SHOW_URL']),
													'title' => htmlspecialcharsbx($entityValue['FORMATTED_NAME']),
													'picture' => htmlspecialcharsbx($entityValue['PHOTO_URL']),
												];
											}
											else
											{
												$formattedValue = \CCrmViewHelper::PrepareUserBaloonHtml([
													'PREFIX' => $this->displayOptions->getGridId() ?? '',
													'USER_ID' => $entityValue['ID'],
													'USER_NAME' => $entityValue['FORMATTED_NAME'],
													'USER_PROFILE_URL' => $entityValue['SHOW_URL'],
													'ENCODE_USER_NAME' => true,
												]);
											}

											$displayedField->setWasRenderedAsHtml(true);
										}
									}
								}
								elseif ($fieldType === 'enumeration')
								{
									$formattedValue = htmlspecialcharsbx($entityValue['VALUE']);
									$displayedField->setWasRenderedAsHtml(true);
								}
								elseif ($fieldType === 'file')
								{
									if ($this->displayOptions->needUseTextMode())
									{
										$fileUrl = \CFile::GetFileSRC($entityValue);
									}
									else
									{
										$fileUrlTemplate = $this->displayOptions->getFileUrlTemplate() ?? '';

										if ($fileUrlTemplate !== '')
										{
											$fileUrl = \CComponentEngine::MakePathFromTemplate(
												$fileUrlTemplate,
												[
													'owner_id' => $itemId,
													'field_name' => $fieldId,
													'file_id' => $entityValue['ID'],
												]
											);
										}
										else
										{
											$fileUrl = $fileViewer->getUrl($itemId, $fieldId, $entityValue['ID']);
										}

										$displayedField->setWasRenderedAsHtml(true);
									}
									if ($this->displayOptions->needUseTextMode())
									{
										$formattedValue = $fileUrl;
									}
									else
									{
										if (
											isset($displayParams['VALUE_TYPE'])
											&& $displayParams['VALUE_TYPE'] === \Bitrix\Crm\Field::VALUE_TYPE_IMAGE
										)
										{
											$resizedFile = \CFile::ResizeImageGet(
												$entityValue,
												[
													'width' => (int)($displayParams['IMAGE_WIDTH'] ?? 50),
													'height' => (int)($displayParams['IMAGE_HEIGHT'] ?? 50),
												],
												BX_RESIZE_IMAGE_PROPORTIONAL,
												false
											);
											$formattedValue = \CFile::ShowImage([
												'SRC' => $resizedFile['src'],
											]);
										}
										else
										{
											$formattedValue =
												'<a href="' . htmlspecialcharsbx($fileUrl) . '" target="_blank">'
												. htmlspecialcharsbx($entityValue['FILE_NAME'])
												. '</a>'
											;
										}
									}
								}
							}

							if ($formattedValue !== '')
							{
								if ($isMultiple)
								{
									if (!isset($result[$itemId][$fieldId]))
									{
										$result[$itemId][$fieldId] = [];
									}
									$result[$itemId][$fieldId][] = $formattedValue;
								}
								else
								{
									$result[$itemId][$fieldId] = $formattedValue;
								}
							}
						}
					}
				}
			}
		}

		if ($this->displayOptions->needReturnMultipleFieldsAsSingle())
		{
			$result = $this->convertMultipleValuesToSingle($result);
		}

		return $result;
	}

	protected function renderUserField(Field $displayedField, $entityId, $value): string
	{
		if (!$displayedField->isUserField() || empty($displayedField->getUserFieldParams()))
		{
			return
				is_array($value)
					? implode($this->displayOptions->getMultipleFieldsDelimiter(), $value)
					: (string)$value
			;
		}

		if ($this->displayOptions->needUseTextMode())
		{
			return (string)((new \Bitrix\Main\UserField\Renderer(
				array_merge(
					$displayedField->getUserFieldParams(),
					[
						'ENTITY_VALUE_ID' => $entityId,
						'VALUE' => $value,
					]
				),
				[
					'CONTEXT' => 'CRM_GRID',
					'mode' => 'main.public_text',
				])
			)->render());
		}
		else
		{
			$displayedField->setWasRenderedAsHtml(true);

			return (string)($GLOBALS['USER_FIELD_MANAGER']->getPublicView(
				array_merge(
					$displayedField->getUserFieldParams(),
					[
						'ENTITY_VALUE_ID' => $entityId,
						'VALUE' => $value,
					]
				),
				[
					'CONTEXT' => 'CRM_GRID',
				]
			));
		}
	}

	protected function convertMultipleValuesToSingle(array $items): array
	{
		foreach ($this->displayedFields as $fieldId => $displayedField)
		{
			if (!$displayedField->isMultiple())
			{
				continue;
			}
			foreach ($items as $itemId => $itemFields)
			{
				if (
					isset($itemFields[$fieldId])
					&& is_array($itemFields[$fieldId])
				)
				{
					$items[$itemId][$fieldId] = implode(
						$this->displayOptions->getMultipleFieldsDelimiter(),
						$itemFields[$fieldId]
					);
				}
			}
		}

		return $items;
	}

	protected function getHtmlLink(
		string $link,
		string $id,
		string $tooltipLoader,
		string $className,
		string $content
	): string
	{
		$result = '<a target="_blank"';
		$result .= ' href="' . $link . '"';
		$result .= ' bx-tooltip-user-id="' . $id . '"';
		$result .= ' bx-tooltip-loader="' . $tooltipLoader . '"';
		$result .= ' bx-tooltip-classname="' . $className . '"';
		$result .= ' >' . $content . '</a>';

		return $result;
	}

	private function getDefaultDatetimeFormat(bool $includeTime)
	{
		if(LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled() && $includeTime)
		{
			return [
				'tommorow' => 'tommorow',
				's' => 'sago',
				'i' => 'iago',
				'H3' => 'Hago',
				'today' => 'today',
				'yesterday' => 'yesterday',
				//'d7' => 'dago',
				'-' => \Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE),
			];
		}
		elseif ($includeTime)
		{
			return preg_replace('/:s$/', '', \Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
		}

		return \Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE);
	}
}
