<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
?>

<div class="crm-config-ps-list-task-options-payment-method">
	<?foreach ($arResult['PAY_SYSTEM'] as $personTypeId => $data):?>
		<div class="crm-config-ps-list-task-options-payment-method-row">
			<div class="crm-config-ps-list-task-options-settings-subtitle-container">
				<span class="crm-config-ps-list-task-options-settings-subtitle webform-payment-method-legal-title"><?=$arResult['PERSON_TYPE_LIST'][$personTypeId];?></span>
			</div>

			<?if (array_key_exists('N', $data)):?>
				<div class="crm-config-ps-list-task-options-payment-method-left-column">
					<?foreach ($data['N'] as $paySystem):?>
						<a href="<?=$paySystem['PATH_TO_PS_EDIT']?>" style="display: block">
							<div class="crm-config-ps-list-task-options-payment-method-block payment-method-block-red">
								<span class="crm-config-ps-list-task-options-payment-method-block-name"><?=$paySystem['NAME'];?></span>
								<span class="crm-config-ps-list-task-options-payment-method-block-element"><?=Loc::getMessage('CRM_PS_LIST_BLOCK_CONNECT');?></span>
							</div><!--crm-config-ps-list-task-options-payment-method-block-->
						</a>
					<?endforeach;?>
				</div><!--crm-config-ps-list-task-options-payment-method-left-column-->
			<?endif;?>

			<?if (array_key_exists('Y', $data)):?>
				<div class="crm-config-ps-list-task-options-payment-method-right-column">
					<?foreach ($data['Y'] as $paySystem):?>
						<div class="crm-config-ps-list-task-options-payment-active-method-block">
							<div class="crm-config-ps-list-task-options-payment-method-block-active payment-method-block-orange">
								<span class="crm-config-ps-list-task-options-payment-method-block-name"><?=$paySystem['NAME']?></span>
							</div><!--crm-config-ps-list-task-options-payment-method-block-->
							<div class="crm-config-ps-list-task-options-payment-method-active">
								<span class="crm-config-ps-list-task-options-payment-method-active-name"><?=Loc::getMessage('CRM_PS_LIST_BLOCK_ACTIVE')?></span>
							<span class="crm-config-ps-list-task-options-payment-method-active-date">
								<div class="crm-config-ps-list-task-options-active-date"></div>
								<div class="crm-config-ps-list-task-options-payment-off">
									<a href="<?=$paySystem['PATH_TO_PS_EDIT']?>" style="display: block"><?=Loc::getMessage('CRM_PS_LIST_BLOCK_DISCONNECT');?></a>
								</div>
							</span>
							</div><!--crm-config-ps-list-task-options-payment-method-active-->
						</div><!--crm-config-ps-list-task-options-payment-active-method-block-->
					<?endforeach;?>
				</div>
			<?endif;?>
		</div><!--crm-config-ps-list-task-options-payment-method-->
	<?endforeach;?>
</div><!--crm-config-ps-list-task-options-payment-method-->