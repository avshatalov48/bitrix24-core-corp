<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 */

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.design-tokens'
]);

$this->addExternalCss('/bitrix/js/crm/entity-editor/css/style.css');

if (!empty($arResult['ERRORS']))
{
	foreach ($arResult['ERRORS'] as $error)
	{
		ShowError($error);
	}
}
else
{
	?>
	<form class='crm-order-buyer-edit-wrapper' id="crm-order-buyer-edit-wrapper">
		<input type="hidden" name="ID" value="<?=($arResult['BUYER']['ID'] ?: 0)?>">
		<div id="bx-crm-error" class="crm-property-edit-top-block"></div>
		<table class="crm-table">
			<tr>
				<td class="crm-table-left-column">
					<div class="crm-entity-card-container" style="width: 100%">
						<div class="crm-entity-card-container-content">
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
								<span class="crm-entity-card-widget-title-text">
									<?=Loc::getMessage('CRM_ORDER_BUYER_EDIT_GENERAL_TITLE')?>
								</span>
								</div>
								<div class="crm-entity-widget-content">
									<?
									foreach ($arResult['FIELDS'] as $field)
									{
										?>
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
											<div class="crm-entity-widget-content-block-title">
												<span class="crm-entity-widget-content-block-title-text">
													<?=$field['NAME']?>
													<?=($field['REQUIRED'] ? '<span style="color: rgb(255, 0, 0);">*</span>' : '')?>
												</span>
											</div>
											<div class="crm-entity-widget-content-block-inner">
												<?
												if ($field['TYPE'] === 'checkbox')
												{
													$value = isset($arResult['BUYER'][$field['ID']])
														? $arResult['BUYER'][$field['ID']]
														: 'N';
													$checked = $value === 'Y';

													if ($field['EDITABLE'])
													{
														?>
														<input type="hidden" name="<?=$field['ID']?>" value="N">
														<input type="checkbox" name="<?=$field['ID']?>" value="Y"
															<?=($checked ? 'checked="checked"' : '')?>>
														<?
													}
													else
													{
														?>
														<input type="hidden" name="<?=$field['ID']?>"
																value="<?=$value?>">
														<?
														echo $checked
															? Loc::getMessage('CRM_ORDER_BUYER_EDIT_YES')
															: Loc::getMessage('CRM_ORDER_BUYER_EDIT_NO');
													}
												}
												else
												{
													$value = isset($arResult['BUYER'][$field['ID']])
														? htmlspecialcharsbx($arResult['BUYER'][$field['ID']])
														: '';

													if ($field['EDITABLE'])
													{
														?>
														<input type="<?=$field['TYPE']?>"
																class="crm-entity-widget-content-input"
																name="<?=$field['ID']?>"
																value="<?=$value?>">
														<?
													}
													else
													{
														?>
														<input type="hidden" name="<?=$field['ID']?>"
																value="<?=$value?>">
														<?
														echo $value;
													}
												}
												?>
											</div>
										</div>
										<?
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</td>
				<td class="crm-table-right-column">
					<div class="crm-entity-card-container" style="width: 100%">
						<div class="crm-entity-card-container-content">
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
								<span class="crm-entity-card-widget-title-text">
									<?=Loc::getMessage('CRM_ORDER_BUYER_EDIT_GROUP_TITLE')?>
								</span>
								</div>
								<div class="crm-entity-widget-content">
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-select">
										<select class="crm-order-buyer-group-edit-field-select" name="GROUP_ID[]"
												multiple size="<?=count($arResult['GROUPS'])?>">
											<?
											foreach ($arResult['GROUPS'] as $group)
											{
												$selected = in_array($group['ID'], $arResult['USER_GROUPS']) ? 'selected' : '';
												?>
												<option value="<?=$group['ID']?>" <?=$selected?>><?=htmlspecialcharsbx($group['NAME'])?></option>
												<?
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?
		if ($arParams['IFRAME'])
		{
			?>
			<div class="crm-footer-container">
				<div class="crm-entity-section-control">
					<a id="CRM_ORDER_BUYER_EDIT_APPLY_BUTTON" class="ui-btn ui-btn-success">
						<?=Loc::getMessage('CRM_ORDER_BUYER_EDIT_BUTTON_SAVE')?>
					</a>
					<a id="CRM_ORDER_BUYER_EDIT_CANCEL" class="ui-btn ui-btn-link">
						<?=Loc::getMessage('CRM_ORDER_BUYER_EDIT_BUTTON_CANCEL')?>
					</a>
				</div>
			</div>
			<?
		}
		else
		{
			?>
			<div>
				<a id="CRM_ORDER_BUYER_EDIT_SUBMIT_BUTTON" class="ui-btn ui-btn-success">
					<?=Loc::getMessage('CRM_ORDER_BUYER_EDIT_BUTTON_APPLY')?>
				</a>
			</div>
			<?
		}
		?>
	</form>
	<?
	$signer = new \Bitrix\Main\Security\Sign\Signer;
	$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'crm.order.buyer_group.edit');
	?>
	<script>
		new BX.Crm.Buyer.Edit({
			params: <?=CUtil::PhpToJSObject($arParams)?>,
			signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
			componentName: '<?=CUtil::JSEscape($this->getComponent()->getName())?>'
		});
	</script>
	<?
}