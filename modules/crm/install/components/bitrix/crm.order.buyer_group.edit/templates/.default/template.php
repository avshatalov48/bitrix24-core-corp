<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Localization\Loc;
/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 */

$this->addExternalCss('/bitrix/js/crm/entity-editor/css/style.css');

\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.design-tokens']);
?>

<form class='crm-order-buyer-group-edit-wrapper' id="crm-order-buyer-group-edit-wrapper">
	<input type="hidden" name="ID" value="<?=($arResult['GROUP']['ID'] ?: 0)?>">
	<div id="bx-crm-error" class="crm-property-edit-top-block"></div>
	<table class="crm-table">
		<tr>
			<td class="crm-table-left-column">
				<div class="crm-entity-card-container" style="width: 100%">
					<div class="crm-entity-card-container-content">
						<div class="crm-entity-card-widget">
							<div class="crm-entity-card-widget-title">
								<span class="crm-entity-card-widget-title-text">
									<?=Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_GENERAL_TITLE')?>
								</span>
							</div>
							<div class="crm-entity-widget-content">
								<?
								foreach ($arResult['FIELDS'] as $field)
								{
									if (isset($arResult['GROUP'][$field['ID']]))
									{
										$fieldValue = htmlspecialcharsbx($arResult['GROUP'][$field['ID']]);
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
													$checked = $fieldValue === 'Y';

													if ($field['EDITABLE'])
													{
														?>
														<input type="hidden" name="<?=$field['ID']?>" value="N">
														<input type="checkbox" name="<?=$field['ID']?>" value="Y"
																checked="<?=($checked ? 'checked' : '')?>">
														<?
													}
													else
													{
														?>
														<input type="hidden" name="<?=$field['ID']?>"
																value="<?=$fieldValue?>">
														<?
														echo $checked
															? Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_YES')
															: Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_NO');
													}
												}
												else
												{

													if ($field['EDITABLE'])
													{
														?>
														<input type="text" class="crm-entity-widget-content-input"
																name="<?=$field['ID']?>" value="<?=$fieldValue?>">
														<?
													}
													else
													{
														?>
														<input type="hidden" name="<?=$field['ID']?>"
																value="<?=$fieldValue?>">
														<?
														echo $fieldValue;
													}
												}
												?>
											</div>
										</div>
										<?
									}
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</td>

			<!--<td class="crm-table-right-column">
				<div class="crm-entity-card-container" style="width: 100%">
					<div class="crm-entity-card-container-content">
						<div class="crm-entity-card-widget">
							<div class="crm-entity-card-widget-title">
								<span class="crm-entity-card-widget-title-text">
									Buyers list
								</span>
							</div>
							<div class="crm-entity-widget-content">
							</div>
						</div>
					</div>
				</div>
			</td>-->
		</tr>
	</table>
	<div class="crm-footer-container">
		<div class="crm-entity-section-control">
			<?
			if ($arResult['CAN_EDIT_GROUP'])
			{
				?>
				<a id="CRM_ORDER_BUYER_GROUP_EDIT_APPLY_BUTTON" class="ui-btn ui-btn-success">
					<?=Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_BUTTON_SAVE')?>
				</a>
				<a id="CRM_ORDER_BUYER_GROUP_EDIT_CANCEL" class="ui-btn ui-btn-link">
					<?=Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_BUTTON_CANCEL')?>
				</a>
				<?
			}
			else
			{
				?>
				<a id="CRM_ORDER_BUYER_GROUP_EDIT_BACK" class="ui-btn ui-btn-link">
					<?=Loc::getMessage('CRM_ORDER_BUYER_GROUP_EDIT_BUTTON_BACK')?>
				</a>
				<?
			}
			?>
		</div>
	</div>
</form>
<?
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'crm.order.buyer_group.edit');
?>
<script>
	new BX.Crm.BuyerGroup.Edit({
		params: <?=CUtil::PhpToJSObject($arParams)?>,
		signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
		componentName: '<?=CUtil::JSEscape($this->getComponent()->getName())?>'
	});
</script>