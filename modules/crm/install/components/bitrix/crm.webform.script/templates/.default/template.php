<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

CJSCore::Init(array('clipboard'));

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
				$counter++;
				?>
				<span data-bx-webform-script-tab-btn="<?=$type?>" class="<?=$addClass?> crm-webform-script-tab-item">
					<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_TAB_SCRIPT_' . $type)?>
				</span>
			<?endforeach;?>
		</div>
	</div>

	<?
	$counter = 0;
	foreach($scriptTypes as $type):
		$addStyle = $counter > 0 ? 'display: none;' : '';
		$type = htmlspecialcharsbx($type);
		$counter++;
		?>
		<div data-bx-webform-script-tab-cont="<?=$type?>" class="crm-webform-script-tab-body-container" style="<?=$addStyle?>">

			<div class="crm-webform-script-tab-body-item-container">
				<div class="crm-webform-script-tab-body-item-subtitle">
					<?=Loc::getMessage('CRM_WEBFORM_SCRIPT_WINDOW_' . $type)?>:
				</div>
				<div class="crm-webform-script-tab-body-item-inner">
					<div data-bx-webform-script-copy-text="SCRIPT_<?=$type?>" class="crm-webform-script-tab-body-item-content">
						<?=$getFormattedScript($arResult['SCRIPTS'][$type])?>
					</div>
					<div class="crm-webform-script-tab-body-item-block">
						<span class="crm-webform-script-tab-body-item-block-element">
							<img src="<?=$this->GetFolder()?>/images/demo_<?=strtolower($type)?>.png?4">
						</span>
					</div>
				</div>
			</div><!--crm-webform-script-tab-body-item-container-->
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