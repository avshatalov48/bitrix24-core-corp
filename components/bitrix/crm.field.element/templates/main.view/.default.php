<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 */

Bitrix\Main\UI\Extension::load('ui.tooltip');

Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
if(\CCrmSipHelper::isEnabled())
{
	Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
}

$publicMode = (isset($arParams['PUBLIC_MODE']) && $arParams['PUBLIC_MODE'] === true);
?>

	<table cellpadding="0" cellspacing="0" class="field_crm">
		<?php
		foreach($arResult['value'] as $entityType => $arEntity)
		{
			?>
			<tr>
				<?php
				if($arParams['PREFIX']):
					?>
					<td class="field_crm_entity_type">
						<?= Loc::getMessage('CRM_ENTITY_TYPE_' . $entityType) ?>:
					</td>
				<?php
				endif;
				?>
				<td class="field_crm_entity">
					<?php
					$first = true;
					foreach($arEntity as $entityId => $entity)
					{
						echo(!$first ? ', ' : '');

						if($publicMode)
						{
							print HtmlFilter::encode($entity['ENTITY_TITLE']);
						}
						else
						{
							$entityTypeLower = mb_strtolower($entityType);

							if($entityType === 'ORDER')
							{
								$url = '/bitrix/components/bitrix/crm.order.details/card.ajax.php';
							}
							else
							{
								$url = '/bitrix/components/bitrix/crm.' . $entityTypeLower . '.show/card.ajax.php';
							}

							$crmBalloonClass = (
							$entityType === 'LEAD' || $entityType === 'DEAL'
								? '_no_photo' : '_' . $entityTypeLower
							);
							?>
							<a
								href="<?= HtmlFilter::encode($entity['ENTITY_LINK']) ?>"
								target="_blank"
								bx-tooltip-user-id="<?= HtmlFilter::encode($entityId) ?>"
								bx-tooltip-loader="<?= HtmlFilter::encode($url) ?>"
								bx-tooltip-classname="crm_balloon<?= $crmBalloonClass ?>"
							>
								<?= HtmlFilter::encode($entity['ENTITY_TITLE']) ?>
							</a>
							<?php
						}
						$first = false;
					}

					?>
				</td>
			</tr>
			<?php
		}
		?>
	</table>

<?php
if(\CCrmSipHelper::isEnabled())
{
	?>
	<script type="text/javascript">
		BX.ready(
			function ()
			{
				if (
					typeof (window['BXIM']) === "undefined"
					||
					typeof (BX.CrmSipManager) === "undefined")
				{
					return;
				}

				if (typeof (BX.CrmSipManager.messages) === "undefined")
				{
					BX.CrmSipManager.messages =
						{
							"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
							"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
						};
				}

				var sipMgr = BX.CrmSipManager.getCurrent();
				sipMgr.setServiceUrl(
					"CRM_<?=CUtil::JSEscape(CCrmOwnerType::LeadName)?>",
					"/bitrix/components/bitrix/crm.lead.show/ajax.php?<?=bitrix_sessid_get()?>"
				);

				sipMgr.setServiceUrl(
					"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
					"/bitrix/components/bitrix/crm.contact.show/ajax.php?<?=bitrix_sessid_get()?>"
				);

				sipMgr.setServiceUrl(
					"CRM_<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
					"/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>"
				);
			}
		);
	</script>
	<?php
}
?>