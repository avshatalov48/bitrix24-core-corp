<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */

$toolbarId = $arParams['TOOLBAR_ID'];

$moreItems = array();
$enableMoreButton = false;
$labelText = '';
?>
<div id="<?=htmlspecialcharsbx($toolbarId)?>" class="bx-crm-interface-toolbar">
	<table cellpadding="0" cellspacing="0" border="0" class="bx-crm-interface-toolbar" style="width: 100%;">
		<tbody>
		<tr>
			<td class="bx-content">
				<table cellpadding="0" cellspacing="0" border="0">
					<tbody>
					<tr><?php
					foreach($arParams['BUTTONS'] as $item)
					{
						if ($item['LABEL'] === true)
						{
							$labelText = isset($item['TEXT']) ? $item['TEXT'] : '';
							continue;
						}

						if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true):
							$enableMoreButton = true;
							continue;
						endif;

						if($enableMoreButton):
							$moreItems[] = $item;
							continue;
						endif;

						$text = isset($item['TEXT']) ? $item['TEXT'] : '';
						$title = isset($item['TITLE']) ? $item['TITLE'] : '';
						$link = isset($item['LINK']) ? $item['LINK'] : '#';
						$iconClassName = 'bx-crm-context-button-icon';
						if(isset($item['ICON']))
						{
							$iconClassName .= ' '.$item['ICON'];
						}
						?>
						<td>
							<a href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" hidefocus="true" class="bx-crm-context-button">
								<span class="<?= htmlspecialcharsbx($iconClassName); ?>"></span>
								<span class="bx-crm-context-button-text"><?=htmlspecialcharsbx($text)?></span>
							</a>
						</td><?php
					}
						?>
					</tr>
					</tbody>
				</table>
			</td>
			<?
			if ($labelText != '')
			{
				?><td>
					<div class="crm-toolbar-label1"><span
							id="<?= htmlspecialcharsbx($toolbarId).'_label' ?>"><?= $labelText ?></span></div>
				</td><?
			}
			if(!empty($moreItems))
			{
			?><td>
				<span hidefocus="true" class="bx-crm-context-button crm-toolbar-alignment-right">
					<span class="bx-crm-context-button-icon btn-settings"></span>
				</span>
			</td>
			<script type="text/javascript">
				BX.ready(
					function ()
					{
						BX.InterfaceToolBar.create(
							"<?=CUtil::JSEscape($toolbarId)?>",
							BX.CrmParamBag.create(
								{
									"containerId": "<?=CUtil::JSEscape($toolbarId)?>",
									"items": <?=CUtil::PhpToJSObject($moreItems)?>,
									"moreButtonClassName": "btn-settings"
								}
							)
						);
					}
				);
			</script><?
			}
			?>
		</tr>
		</tbody>
	</table>
</div>