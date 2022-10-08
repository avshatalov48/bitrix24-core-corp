<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'clipboard',
]);

$getFormattedScript = function ($script)
{
	$script = htmlspecialcharsbx($script);
	$script = str_replace("\t", str_repeat('&nbsp;', 8), $script);
	return nl2br($script);
};

$defaultTemplateContainerId = 'CRM_WEBFORM_EDIT_SCRIPT';
$templateContainerId = $arParams['TEMPLATE_CONTAINER_ID'] ? $arParams['TEMPLATE_CONTAINER_ID'] : $defaultTemplateContainerId;
$templateContainerId = htmlspecialcharsbx($templateContainerId);
$scriptTypes = array_keys($arResult['SCRIPTS']);

$isFormInline = $defaultTemplateContainerId == $templateContainerId;
?>

<div id="<?=$templateContainerId?>" class="crm-webform-script-popup <?=($isFormInline ? 'crm-webform-inline' : '')?>">

	<?if($isFormInline):?>
		<div class="crm-webform-script-tab-body-item-container">
			<div class="crm-webform-script-tab-body-item-subtitle"><?=Loc::getMessage('CRM_WEBFORM_SCRIPT_LINK')?>:</div>
			<div class="crm-webform-script-tab-body-item-content crm-webform-script-item-inline">
					<span class="crm-webform-script-url-link-container">
						<a data-bx-webform-script-copy-text="LINK" target="_blank" href="<?=htmlspecialcharsbx($arResult['LINK'])?>" class="crm-webform-edit-url-link">
							<?=htmlspecialcharsbx($arResult['LINK'])?>
						</a>
						<span data-bx-webform-script-copy-btn="LINK" class="crm-webform-script-url-link-icon"></span>
					</span>
			</div>
		</div>
		<br>
		<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_SCRIPT_ON_SITE')?>:
		<br>
		<br>
	<?endif;?>

	<div class="crm-webform-script-tab-container">
		<div class="crm-webform-script-tab-list">
			<?
			$counter = 0;
			foreach($scriptTypes as $type):
				$addClass = $counter > 0 ? '' : 'crm-webform-script-tab-item-active';
				$type = htmlspecialcharsbx($type);
				$msgType = $type === 'AUTO' ? 'DELAY' : $type;
				$counter++;
				?>
				<span data-bx-webform-script-tab-btn="<?=$type?>" class="<?=$addClass?> crm-webform-script-tab-item">
					<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_TAB_SCRIPT_' . $msgType)?>
				</span>
			<?endforeach;?>
		</div>
	</div>

	<?
	$counter = 0;
	foreach($scriptTypes as $type):
		$addStyle = $counter > 0 ? 'display: none;' : '';
		$type = htmlspecialcharsbx($type);
		$msgType = $type === 'AUTO' ? 'DELAY' : $type;
		$typeLower = mb_strtolower($type);
		$counter++;
		$view = !empty($arResult['VIEWS'][$typeLower]) ? $arResult['VIEWS'][$typeLower] : [];
		?>
		<div data-bx-webform-script-tab-cont="<?=$type?>" class="crm-webform-script-tab-body-container" style="<?=$addStyle?>">
			<div class="crm-webform-script-tab-body-item-container">
				<div class="crm-webform-script-tab-body-item-subtitle">
					<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_WINDOW_' . $msgType)?>:
				</div>
				<div data-bx-webform-script-kind="" style="<?=($arResult['IS_AVAILABLE_EMBEDDING'] ? '' : 'display: none;')?>">
					<div class="crm-webform-script-tab-body-item-inner">
						<div data-bx-webform-script-copy-text="SCRIPT_<?=$type?>" class="crm-webform-script-tab-body-item-content">
							<?=$getFormattedScript($arResult['SCRIPTS'][$type]['text'])?>
						</div>
					</div>
					<?if ($type !== 'INLINE'):?>
						<div class="crm-webform-script-code-settings">
							<span class="crm-webform-script-code-setting">
								<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_VIEW_TYPE')?>
								<select name="VIEWS[<?=$typeLower?>][type]" <?=($isFormInline ? '' : 'disabled')?>>
									<option value="panel" <?=($view['type'] == 'panel' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_VIEW_TYPE_PANEL')?>
									</option>
									<option value="popup" <?=($view['type'] == 'popup' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_VIEW_TYPE_POPUP')?>
									</option>
								</select>
							</span>
							<span class="crm-webform-script-code-setting">
								<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_POSITION')?>
								<select name="VIEWS[<?=$typeLower?>][position]" <?=($isFormInline ? '' : 'disabled')?>>
									<option value="right" <?=($view['position'] == 'right' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_POSITION_RIGHT')?>
									</option>
									<option value="left" <?=($view['position'] == 'left' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_POSITION_LEFT')?>
									</option>
									<option value="center" <?=($view['position'] == 'center' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_POSITION_CENTER')?>
									</option>
								</select>
							</span>
							<span class="crm-webform-script-code-setting">
								<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_ANIMATION')?>
								<select name="VIEWS[<?=$typeLower?>][vertical]" <?=($isFormInline ? '' : 'disabled')?>>
									<option value="bottom" <?=($view['vertical'] == 'bottom' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_ANIMATION_BOTTOM')?>
									</option>
									<option value="top" <?=($view['vertical'] == 'top' ? 'selected' : '')?>>
										<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_ANIMATION_TOP')?>
									</option>
								</select>
							</span>
						</div>
						<?if ($type !== 'AUTO'):?>
							<div class="crm-webform-script-code-settings">
								<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_HINT_INJECT')?>
							</div>
						<?else:?>
							<div class="crm-webform-script-code-settings">
								<span class="crm-webform-script-code-setting">
									<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_PARAM_DELAY')?>
									<select name="VIEWS[<?=$typeLower?>][delay]" <?=($isFormInline ? '' : 'disabled')?>>
										<?foreach ([3,5,7,10,15,20,25,30,40,60, 120] as $delay):?>
											<option value="<?=$delay?>" <?=($view['delay'] == $delay ? 'selected' : '')?>>
												<?=$delay?> <?=Loc::getMessage('CRM_WEBFORM_SCRIPT_SEC')?>
											</option>
										<?endforeach?>
									</select>
								</span>
							</div>
						<?endif;?>
					<?endif;?>
				</div>
			</div>
		</div>
	<?endforeach;?>

	<div class="crm-webform-script-tab-body-item-button-container" >
	<?
	$counter = 0;
	foreach($scriptTypes as $type):
		$addStyle = $counter > 0 ? 'display: none;' : '';
		$type = htmlspecialcharsbx($type);
		$counter++;
	?>
		<span style="<?=$addStyle?>" data-bx-webform-script-copy-btn="SCRIPT_<?=$type?>" class="webform-small-button webform-small-button-blue crm-webform-edit-task-options-fields-rule-button">
			<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_BTN_COPY')?>
		</span>
	<?endforeach;?>
	</div>

</div>

<script type="text/javascript">
	BX.ready(function(){
		(new CrmWebFormEditScript({
			context: BX('<?=$templateContainerId?>')
		}));
	});
</script>