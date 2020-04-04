<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$containerID = $arResult['CONTAINER_ID'];
$blocks = $arResult['BLOCKS'];
$isFolded = $arResult['IS_FOLDED'];

$lockData = $arResult['LOCK_CONTROL_DATA'];
$lockInfo = array(
	'enabled' => $lockData['ENABLED'],
	'editable' => $lockData['EDITABLE'],
	'fieldId' => $lockData['FIELD_ID'],
	'isLocked' => $lockData['IS_LOCKED'],
	'lockLegend' => $lockData['LOCK_LEGEND'],
	'unlockLegend' => $lockData['UNLOCK_LEGEND']
);

$titleData = $arResult['TITLE'];
$sipData = $arResult['SIP'];

if(!function_exists('__CrmEntitySummaryIsDisplayable'))
{
	function __CrmEntitySummaryIsDisplayable(&$item, $displayEmpty = true)
	{
		$id = isset($item['ID']) ? $item['ID'] : '';
		$type = isset($item['TYPE']) ? strtoupper($item['TYPE']) : '';
		$params = isset($item['PARAMS']) && is_array($item['PARAMS']) ? $item['PARAMS'] : array();
		$value = isset($item['VALUE']) ? $item['VALUE'] : '';

		$displayIfEmpty = isset($item['DISPLAY_IF_EMPTY'])
			? $item['DISPLAY_IF_EMPTY']
			: (isset($params['DISPLAY_IF_EMPTY']) ? $params['DISPLAY_IF_EMPTY'] : $displayEmpty);

		if($type === '')
		{
			return $value !== '' || $displayIfEmpty;
		}
		elseif($type === 'MULTIFIELD')
		{
			$valueCount = isset($params['VALUE_COUNT']) ? intval($params['VALUE_COUNT']) : 0;

			return $valueCount > 0 || $displayIfEmpty;
		}

		if($displayIfEmpty)
		{
			return true;
		}

		if($value === '')
		{
			return false;
		}

		if(strpos($id, 'UF_') === 0)
		{
			// HACK: CHECK FOR USER FIELD EMPTY WRAPPER
			return preg_match('/^(\s*<[a-z0-9]+[^>]*>\s*<\/[a-z0-9]+>\s*)+$/i', $value) !== 1;
		}

		return true;
	}
}
if(!function_exists('__CrmEntitySummaryRenderSingleSectionItem'))
{
	function __CrmEntitySummaryRenderSingleSectionItem(&$item)
	{
		$title = isset($item['TITLE']) ? $item['TITLE'] : '';
		if($title !== '')
		{
			$title .= ':';
		}

		$params = isset($item['PARAMS']) && is_array($item['PARAMS']) ? $item['PARAMS'] : array();
		$type = isset($params['TYPE']) ? strtoupper($params['TYPE']) : '';

		if ($type === 'CUSTOM')
		{
			$fieldID = isset($params['FIELD_ID']) ? $params['FIELD_ID'] : '';
			$value = isset($params['VALUE']) ? $params['VALUE'] : '';
			echo $value;
		}
		else
		{
			echo '<div class="crm-detail-comments-title">', htmlspecialcharsbx($title), '</div>';

			echo '<div class="crm-instant-editor-fld-block">';
			echo '<div class="crm-detail-comments-text-wrap">';
			echo '<div class="crm-detail-comments-text">';
			CCrmViewHelper::RenderInstantEditorField($params);
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}
	}
}
if(!function_exists('__CrmEntitySummaryRenderHorSectionItem'))
{
	function __CrmEntitySummaryRenderHorSectionItem(&$item)
	{
		$type = isset($item['TYPE']) ? strtoupper($item['TYPE']) : '';
		$title = isset($item['TITLE']) ? $item['TITLE'] : '';
		if($title !== '')
		{
			$title .= ':';
		}

		$params = isset($item['PARAMS']) && is_array($item['PARAMS']) ? $item['PARAMS'] : array();
		if($type === '')
		{
			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>',
				'<span class="crm-client-contacts-block-text">',
				htmlspecialcharsbx(isset($item['VALUE']) ? $item['VALUE'] : ''),
				'</span>';
		}
		elseif($type === 'PROGRESS')
		{
			echo '<div class="crm-detail-stage crm-detail-info-item">',
				'<div class="crm-detail-stage-name">', htmlspecialcharsbx($title), '</div>',
				CCrmViewHelper::RenderProgressControl($params),
				'</div>';
		}
		elseif($type === 'SELECT')
		{
			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>';
			echo '<span class="crm-client-contacts-block-text">';
			$params['CONTAINER_CLASS'] = 'crm-item-type';
			$params['TEXT_CLASS'] = 'crm-item-type-text';
			$params['ARROW_CLASS'] = 'crm-item-type-text-corner';
			CCrmViewHelper::RenderSelector($params);
			echo '</span>';
		}
		elseif($type === 'CLIENT_INFO')
		{
			echo '<div class="crm-item-client"><span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>',
				'<span class="crm-client-contacts-block-text">',
				CCrmViewHelper::PrepareClientInfoV2($params),
				'</span></div>';
		}
		elseif($type === 'CLIENT_BALLOON')
		{
			if(isset($params['name']))
			{
				$title = $params['name'];
				if($title !== '')
				{
					$title .= ':';
				}
			}

			echo '<div class="crm-item-client-ballon"><span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>',
				'<span class="crm-client-contacts-block-text">';

			if(isset($params['value']))
			{
				echo $params['value'];
			}
			else
			{
				echo CCrmViewHelper::PrepareEntityBaloonHtml($params);
			}

			echo '</span></div>';
		}
		elseif($type === 'MULTIFIELD')
		{
			$typeName = isset($params['TYPE']) ? $params['TYPE'] : '';
			$values = isset($params['VALUES']) ? $params['VALUES'] : array();
			$valueTypes = isset($params['VALUE_TYPES']) ? $params['VALUE_TYPES'] : array();
			$valueCount = isset($params['VALUE_COUNT']) ? intval($params['VALUE_COUNT']) : 0;
			$displayIfEmpty = isset($params['DISPLAY_IF_EMPTY']) ? $params['DISPLAY_IF_EMPTY'] : true;

			if(!$displayIfEmpty && $valueCount <= 0)
			{
				return;
			}

			$options = isset($item['OPTIONS']) && is_array($item['OPTIONS']) ? $item['OPTIONS'] : array();
			$enableSip = is_array($options) && isset($options['ENABLE_SIP']) && (bool)$options['ENABLE_SIP'];

			$titleClassName = 'crm-detail-info-item-name';
			if($enableSip)
			{
				$titleClassName .= ' crm-detail-info-item-name-tel-sip';
			}

			$contentClassName = 'crm-client-contacts-block-text crm-item-tel';
			if($enableSip)
			{
				$contentClassName .= ' crm-item-tel-tel-sip';
			}

			echo '<span class="'.$titleClassName.'">', htmlspecialcharsbx($title), '</span>',
				'<span class="'.$contentClassName.'">';

			echo CCrmViewHelper::PrepareFirstMultiFieldHtml(
				$typeName,
				$values,
				$valueTypes,
				array(),
				$options
			);

			if($valueCount > 1)
			{
				$prefix = isset($params['PREFIX']) ? $params['PREFIX'] : '';
				$anchorID = ($prefix !== '' ? "{$prefix}_" : '').strtolower($typeName);
				echo '<span class="crm-client-contacts-block-text-list-icon" id="', htmlspecialcharsbx($anchorID), '"',
				' onclick="',
				CCrmViewHelper::PrepareMultiFieldValuesPopup($anchorID, $anchorID, $typeName, $values, $valueTypes, $options),
				'"></span>';
			}
			echo '</span>';
		}
		elseif($type === 'RESPONSIBLE')
		{
			CCrmViewHelper::RenderResponsiblePanel($params);
		}
		elseif($type === 'MODIFICATION_INFO')
		{
			$date = isset($params['DATE']) ? $params['DATE'] : '';
			$userName = isset($params['USER_NAME']) ? $params['USER_NAME'] : '';
			$pathToUser = isset($params['PATH_TO_USER']) ? $params['PATH_TO_USER'] : '';

			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>',
				'<span class="crm-client-contacts-block-text">';

			echo $date !== '' ? FormatDate('SHORT', MakeTimeStamp($date)) : '-';

			if($userName !== '' && $pathToUser !== '')
			{
				echo ', <a class="crm-detail-info-link" href="',
					htmlspecialcharsbx($pathToUser),'">',
					htmlspecialcharsbx($userName),
					'</a>';
			}
			echo '</span>';
		}
		elseif($type === 'DURATION')
		{
			$from = !empty($params['FROM'])
				? CCrmComponentHelper::TrimDateTimeString(
					ConvertTimeStamp(MakeTimeStamp($params['FROM']), 'SHORT', SITE_ID)
				) : '-';
			$to = !empty($params['TO'])
				? CCrmComponentHelper::TrimDateTimeString(
					ConvertTimeStamp(MakeTimeStamp($params['TO']), 'SHORT', SITE_ID)
				) : '-';

			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>',
				'<span class="crm-client-contacts-block-text crm-item-date">';

			echo '<i>', htmlspecialcharsbx(GetMessage('CRM_DURATION_FROM')), '</i>',
				'<span class="crm-item-date-calendar">', htmlspecialcharsbx($from), '</span>',
				'<i>', htmlspecialcharsbx(GetMessage('CRM_DURATION_TO')), '</i>',
				'<span class="crm-item-date-calendar">', htmlspecialcharsbx($to), '</span>';

			echo '</span>';
		}
		elseif($type === 'MONEY')
		{
			$fieldID = isset($params['FIELD_ID']) ? $params['FIELD_ID'] : '';
			$value = isset($params['VALUE']) ? $params['VALUE'] : '';
			$currencyID = isset($params['CURRENCY_ID']) ? $params['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
			$editable = isset($params['EDITABLE']) ? $params['EDITABLE'] : false;

			echo '<div class="crm-item-sum', $editable ? ' crm-instant-editor-fld-block' : '', '">';
			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>';

			if(!$editable)
			{
				echo '<span class="crm-client-contacts-block-text">',
					CCrmCurrency::MoneyToString($value, $currencyID),
					'</span>';
			}
			else
			{
				echo '<span class="crm-client-contacts-block-text">';
				CCrmViewHelper::RenderInstantEditorField(
					array(
						'TYPE' => 'TEXT',
						'FIELD_ID' => $fieldID,
						'VALUE' => $value,
						'SUFFIX_HTML' => "&nbsp;{$currencyID}",
						'INPUT_WIDTH' => 80

					)
				);
				echo '</span>';
			}

			echo '</div>';
		}
		elseif($type === 'PERCENT')
		{
			$fieldID = isset($params['FIELD_ID']) ? $params['FIELD_ID'] : '';
			$value = isset($params['VALUE']) ? $params['VALUE'] : '';
			$editable = isset($params['EDITABLE']) ? $params['EDITABLE'] : false;

			echo '<div', ($editable ? ' class="crm-instant-editor-fld-block"' : ''),'>';
			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>';

			if(!$editable)
			{
				echo '<span class="crm-client-contacts-block-text">', htmlspecialcharsbx($value), '&nbsp;%</span>';
			}
			else
			{
				echo '<span class="crm-client-contacts-block-text">';
				CCrmViewHelper::RenderInstantEditorField(
					array(
						'TYPE' => 'TEXT',
						'FIELD_ID' => $fieldID,
						'VALUE' => $value,
						'SUFFIX_HTML' => "%",
						'INPUT_WIDTH' => 30

					)
				);
				echo '</span>';
			}

			echo '</div>';
		}
		elseif($type === 'TEXT')
		{
			$fieldID = isset($params['FIELD_ID']) ? $params['FIELD_ID'] : '';
			$value = isset($params['VALUE']) ? $params['VALUE'] : '';
			$editable = isset($params['EDITABLE']) ? $params['EDITABLE'] : false;
			$width = isset($params['WIDTH']) ? $params['WIDTH'] : 50;

			echo '<div', ($editable ? ' class="crm-instant-editor-fld-block"' : ''),'>';
			echo '<span class="crm-detail-info-item-name">', htmlspecialcharsbx($title), '</span>';

			if(!$editable)
			{
				echo '<span class="crm-client-contacts-block-text">', htmlspecialcharsbx($value), '</span>';
			}
			else
			{
				echo '<span class="crm-client-contacts-block-text"><span class="crm-detail-item-text-alignment">';

				CCrmViewHelper::RenderInstantEditorField(
					array(
						'TYPE' => 'TEXT',
						'FIELD_ID' => $fieldID,
						'VALUE' => $value,
						'INPUT_WIDTH' => $width

					)
				);
				echo '</span></span>';
			}

			echo '</div>';
		}
		elseif($type === 'CUSTOM')
		{
			$fieldID = isset($params['FIELD_ID']) ? $params['FIELD_ID'] : '';
			$value = isset($params['VALUE']) ? $params['VALUE'] : '';
			echo $value;
		}
	}
}
?>
<div class="crm-detail-lead-wrap-wrap">
	<div class="crm-detail-lead-wrap" id="<?=htmlspecialcharsbx($containerID)?>">
		<div class="crm-detail-title"><?
			if($lockData['ENABLED']):
				$isLocked = $lockData['IS_LOCKED']
				?><span class="crm-contact-locked-icon<?=$isLocked ? '' : ' crm-contact-unlocked-icon'?>" title="<?=htmlspecialcharsbx($isLocked ? $lockData['LOCK_LEGEND'] : $lockData['UNLOCK_LEGEND'])?>"></span><?
			endif;

			$logoID = isset($titleData['LOGO_ID']) ? intval($titleData['LOGO_ID']) : 0;
			$logoInfo = $logoID > 0
				? CFile::ResizeImageGet(
					$logoID,
					array('width' => 100, 'height' => 100),
					BX_RESIZE_IMAGE_EXACT
				)
				: false;

			if(is_array($logoInfo) && isset($logoInfo['src'])):
				?><div class="crm-detail-info-resp-img">
					<img src="<?=htmlspecialcharsbx($logoInfo['src'])?>"/>
				</div><?
			endif;
			if(!$titleData['EDITABLE']):
				?><div class="crm-title-name-wrap">
					<span class="crm-detail-title-name">
						<span class="crm-instant-editor-fld-text">
							<?=htmlspecialcharsbx($titleData['VALUE'])?>
						</span>
					</span>
				</div><?
				if($arResult['LEGEND'] !== ''):
					?><span class="crm-detail-title-number">(<?=htmlspecialcharsbx($arResult['LEGEND'])?>)</span><?
				endif;
			else:
			?><div class="crm-instant-editor-fld-block crm-title-name-wrap">
				<span class="crm-detail-title-name"><?
				CCrmViewHelper::RenderInstantEditorField(
					array(
						'TYPE' => 'TEXT',
						'FIELD_ID' => isset($titleData['FIELD_ID']) ? $titleData['FIELD_ID'] : 'TITLE',
						'VALUE' => $titleData['VALUE'],
						/*'INPUT_WIDTH' => 600,*/
						'SUFFIX_HTML'=> $arResult['LEGEND'] !== ''
							? '<span class="crm-detail-title-number">&nbsp;('.htmlspecialcharsbx($arResult['LEGEND']).')</span>'
							: ''
					)
				);
				?></span>
			</div>
			<?endif;?>
			<span class="crm-detail-title-btns">
				<a class="crm-detail-toggle" href="#"><?=htmlspecialcharsbx(GetMessage(
					$isFolded ? 'CRM_ENT_SMR_SHOW_DETAILS' : 'CRM_ENT_SMR_HIDE_DETAILS'))?></a>
			</span>
		</div><?
		foreach($blocks as $block):
			$isFold = isset($block['IS_FOLD']) ? $block['IS_FOLD'] : false;
			$enableAutoWidth = isset($block['AUTO_WIDTH']) ? $block['AUTO_WIDTH'] : false;
			$className = $isFold ? 'crm-detail-info-fold crm-detail-lead-resize' : 'crm-detail-info-extend';
			$isDisplayed = $isFold ? $isFolded : !$isFolded;
			$layout = isset($block['LAYOUT']) ? strtoupper($block['LAYOUT']) : '';
			if($layout === 'HORIZONTAL'):
				$sections = isset($block['SECTIONS']) ? $block['SECTIONS'] : null;
				if(!is_array($sections) || empty($sections)) continue;
				?><div class="<?=$className?> crm-detail-info-blocks-wrap"<?=!$isDisplayed ? ' style="display:none;"' : ''?>><div class="crm-detail-info-blocks"><table class="crm-detail-info-blocks-table"><tbody><tr><?
				$sectionCount = 0;
				foreach($sections as &$section):
					$items = isset($section['ITEMS']) ? $section['ITEMS'] : null;
					if(!is_array($items) || empty($items)) continue;
					$sectionCount++;
					?><td class="crm-detail-info-block<?= $enableAutoWidth ? ' crm-detail-info-block-width-auto' : ''?>"><?
						foreach($items as &$item):
							if(!__CrmEntitySummaryIsDisplayable($item)) continue;
							$type = isset($item['TYPE']) ? strtoupper($item['TYPE']) : '';
							?><div class="crm-detail-info-item"><?
								if($enableAutoWidth):
									?><div class="crm-detail-no-float"><?
								endif;
									__CrmEntitySummaryRenderHorSectionItem($item);
								if($enableAutoWidth):
									?></div><?
								endif;
							?></div><?
						endforeach;
						unset($item);
					?></td><?
				endforeach;
				unset($section);
				?></tr></tbody></table><?
				$borderCount = $sectionCount - 1;
				for($i = 1; $i <= $borderCount; $i++):
					?><div class="crm-detail-info-item-border_<?=$i?>"></div><?
			endfor;
			?></div></div><?
			elseif($layout === 'SINGLE'):
			$item = isset($block['ITEM']) ? $block['ITEM'] : null;
			if(is_array($item)):
				?><div class="<?=$className?> crm-detail-comments"<?=!$isDisplayed ? ' style="display:none;"' : ''?>><?
					__CrmEntitySummaryRenderSingleSectionItem($item);
				?></div><?
			endif;
			else:
				$sections = isset($block['SECTIONS']) ? $block['SECTIONS'] : null;
				if(!is_array($sections) || empty($sections)) continue;
				$sectionCount = count($sections);
				$sectionIndex = 0;
				foreach($sections as &$section):
					$items = isset($section['ITEMS']) ? $section['ITEMS'] : null;
					if(!is_array($items) || empty($items)) continue;
					$itemQty = 0;
					foreach($items as &$item)
						if(__CrmEntitySummaryIsDisplayable($item, false)) $itemQty++;
					unset($item);
					if($itemQty === 0) continue;
					?><div class="<?=$className?><?= $sectionIndex < ($sectionCount - 1) ? ' crm-detail-comments' : ' crm-detail-info-bottom'?>"<?=!$isDisplayed ? ' style="display:none;"' : ''?>>
					<table class="crm-detail-info-table"><tbody><?
					foreach($items as &$item):
						if(!__CrmEntitySummaryIsDisplayable($item, false)) continue;
						$enableTitle = isset($item['ENABLE_TITLE']) && is_bool($item['ENABLE_TITLE']) ? $item['ENABLE_TITLE'] : true;
						$title = isset($item['TITLE']) ? $item['TITLE'] : '';
						$value = isset($item['VALUE']) ? $item['VALUE'] : '';
						?><tr><?
							if($enableTitle):
								?><td class="crm-detail-info-table-cell"><?=htmlspecialcharsbx($title)?>:</td><?
							endif;
							?><td class="crm-detail-info-table-cell-r"<?=!$enableTitle ? ' colspan="2"' : ''?>><?=$value?></td>
						</tr><?
					endforeach;
					unset($item);
					?></tbody></table></div><?
					$sectionIndex++;
				endforeach;
				unset($section);
			endif;
		endforeach;
		unset($block);
	?></div>
</div>


<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmEntitySummary.messages =
				{
					"showDetails": "<?=GetMessageJS('CRM_ENT_SMR_SHOW_DETAILS')?>",
					"hideDetails": "<?=GetMessageJS('CRM_ENT_SMR_HIDE_DETAILS')?>"
				};
				BX.CrmEntitySummary.create(
						"<?=CUtil::JSEscape($containerID)?>",
						BX.CrmParamBag.create(
								{
									"containerId": "<?=CUtil::JSEscape($containerID)?>",
									"isFolded": <?=$isFolded ? 'true' : 'false'?>,
									"lockInfo": <?=CUtil::PhpToJSObject($lockInfo)?>,
									"editorId": "<?=CUtil::JSEscape($arResult['EDITOR_ID'])?>"
								}
						)
				);
			}
	);
</script>
<?if(isset($sipData['ENTITY_TYPE']) && $sipData['ENTITY_TYPE'] !== ''):?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmSipManager.getCurrent().setServiceUrl(
					"CRM_<?=CUtil::JSEscape($sipData['ENTITY_TYPE'])?>",
					"<?=isset($sipData['SERVICE_URL']) ? $sipData['SERVICE_URL'] : ''?>"
				);

				if(typeof(BX.CrmSipManager.messages) === 'undefined')
				{
					BX.CrmSipManager.messages =
					{
						"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
						"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
					};
				}
			}
	);
</script>
<?endif;?>
