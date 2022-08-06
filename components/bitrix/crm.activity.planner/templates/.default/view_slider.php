<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */
/** @global CMain $APPLICATION */

/** @var \Bitrix\Crm\Activity\Provider\Base $provider */
$provider = $arResult['PROVIDER'];
/** @var array $activity */
$activity = $arResult['ACTIVITY'];
$options = array(
	'title' => $provider::getTypeName($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION']),
	'important' => $activity['PRIORITY'] == CCrmActivityPriority::High,
	'isEditable' => !empty($arResult['IS_EDITABLE'])
);
$optionsJson = \Bitrix\Main\Web\Json::encode($options);

$activity['__template'] = 'slider';
$activity['__files'] = !empty($arResult['FILES_LIST']) ? $arResult['FILES_LIST'] : array();

$APPLICATION->restartBuffer();

\CJSCore::init(array('crm_activity_planner'));

?><!DOCTYPE html>
	<html>
	<head><? $APPLICATION->showHead(); ?></head>
	<body style="background: #eef2f4 !important; ">

	<div class="crm-activity-planner-slider-wrap" data-activity-id="<?=(int)$activity['ID']?>" data-role="options" data-options="<?=htmlspecialcharsbx($optionsJson)?>">
		<div class="crm-activity-planner-slider-container">
			<div class="crm-activity-planner-slider-header crm-activity-planner-slider-header-icon crm-activity-planner-slider-header-icon-<?=$arResult['TYPE_ICON']?>">
				<div class="crm-activity-planner-slider-header-title"><?=htmlspecialcharsbx($activity['SUBJECT'] ? $activity['SUBJECT'] : $provider::getTypeName($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION']))?></div>
				<div class="crm-activity-planner-slider-header-control-block">
					<div class="crm-activity-planner-slider-header-control-item">
						<input class="crm-activity-planner-slider-header-control-checkbox" type="checkbox" id="<?=($inputId = uniqid('inp_')) ?>" data-role="field-completed" <? if ($activity['COMPLETED'] == 'Y'): ?> checked<? endif ?>>
						<label class="crm-activity-planner-slider-header-control-text crm-activity-planner-slider-header-control-label" for="<?=$inputId ?>"><?=getMessage('CRM_ACTIVITY_PLANNER_COMPLETED_SLIDER') ?></label>
					</div>
					<div class="crm-activity-planner-slider-header-control-item crm-activity-planner-slider-header-control-important crm-activity-planner-slider-header-control-select crm-activity-planner-slider-header-icon-flame<? if ($options['important']): ?>-active<? endif ?>" data-role="field-important">
						<div class="crm-activity-planner-slider-header-control-text"><?=getMessage('CRM_ACTIVITY_PLANNER_IMPORTANT_SLIDER') ?></div>
						<div class="crm-activity-planner-slider-header-control-icon"></div>
					</div>
					<div class="crm-activity-planner-slider-header-control-item crm-activity-planner-slider-header-control-select" data-role="additional-switcher">
						<div class="crm-activity-planner-slider-header-control-text"><?=getMessage('CRM_ACTIVITY_PLANNER_MORE_SLIDER') ?></div>
						<div class="crm-activity-planner-slider-header-control-triangle"></div>
					</div>
					<? if (\CCrmActivityType::Email == $activity['TYPE_ID'] && $activity['UF_MAIL_MESSAGE'] > 0): ?>
						<? $APPLICATION->includeComponent(
							'bitrix:mail.message.actions',
							'',
							array(
								'MESSAGE_ID' => $activity['UF_MAIL_MESSAGE'],
							)
						); ?>
					<? endif ?>
				</div>
			</div><!--crm-task-list-header-->
			<div class="crm-activity-slider-container">
			<div class="crm-task-list-inner" data-role="additional-fields" style="display: none; ">
				<div class="crm-task-list-mail-additionally-info">
					<div class="crm-task-list-mail-additionally-info-title"><?=getMessage('CRM_ACTIVITY_PLANNER_ADDITIONAL2') ?></div>
					<table class="crm-task-list-mail-table-block">
						<? if (!empty($arResult['RESPONSIBLE_NAME'])): ?>
							<tr class="crm-task-list-mail-table-row">
								<td class="crm-task-list-mail-table-item">
									<div class="crm-task-list-mail-additionally-info-name"><?=getMessage('CRM_ACTIVITY_PLANNER_RESPONSIBLE_USER') ?>:</div>
								</td>
								<td class="crm-task-list-mail-table-item">
									<div class="crm-task-list-mail-additionally-info-content">
										<a class="crm-task-list-mail-additionally-info-text-bold" target="_blank"
											href="<?=htmlspecialcharsbx($arResult['RESPONSIBLE_URL']) ?>">
											<?=htmlspecialcharsbx($arResult['RESPONSIBLE_NAME']) ?></a>
									</div>
								</td>
							</tr>
						<? endif ?>
						<? if ($arResult['DOC_BINDINGS'] || \CCrmActivityType::Email == $activity['TYPE_ID'] && \CCrmOwnerType::Lead != $activity['OWNER_TYPE_ID']): ?>
							<tr class="crm-task-list-mail-table-row">
								<? if (\CCrmActivityType::Email == $activity['TYPE_ID'] && \CCrmOwnerType::Lead != $activity['OWNER_TYPE_ID']): ?>
									<td class="crm-task-list-mail-table-item">
										<div class="crm-task-list-mail-additionally-info-name"><?=getMessage('CRM_ACTIVITY_PLANNER_VIEW_DEAL') ?>:</div>
									</td>
									<td class="crm-task-list-mail-table-item" style="width: 100%; padding-bottom: 10px; ">
										<div class="crm-task-list-mail-square-block crm-task-list-mail-square-grey" id="crm_act_planner_view_docs_container">
											<span id="crm_act_planner_view_docs_item"></span>
											<span id="crm_act_planner_view_docs_input_box" style="display: none; vertical-align: top; ">
												<input type="text" value="" class="crm-task-list-mail-square-string" id="crm_act_planner_view_docs_input">
											</span>
											<a href="javascript:void(0)" class="feed-add-destination-link" id="crm_act_planner_view_docs_tag"><?=getMessage('CRM_ACT_EMAIL_REPLY_SET_DOCS') ?></a>
										</div>
									</td>
								<? else: ?>
									<td class="crm-task-list-mail-table-item">
										<div class="crm-task-list-mail-additionally-info-name"><?=getMessage('CRM_ACTIVITY_PLANNER_VIEW_DOCUMENTS') ?>:</div>
									</td>
									<td class="crm-task-list-mail-table-item">
										<div class="crm-task-list-mail-additionally-info-content">
											<? $k = count($arResult['DOC_BINDINGS']); ?>
											<? foreach ($arResult['DOC_BINDINGS'] as $item): ?>
											<a class="crm-task-list-mail-additionally-info-text-bold"
												<? if (!$arResult['IS_SLIDER_ENABLED'] || $item['OWNER_TYPE_ID'] != \CCrmOwnerType::Deal): ?>
													target="_top"
												<? endif ?>
												<? if ($item['DOC_URL']): ?> href="<?=htmlspecialcharsbx($item['DOC_URL']) ?>"<? endif ?>>
												<?=htmlspecialcharsbx($item['DOC_NAME']) ?> - <?=htmlspecialcharsbx($item['CAPTION']) ?></a><? if (--$k > 0): ?>, <? endif ?>
											<? endforeach ?>
										</div>
									</td>
								<? endif ?>
							</tr>
						<? endif ?>
					</table>
				</div>
			</div>
			<? if ((int)$activity['TYPE_ID'] === \CCrmActivityType::Email):?>
				<?=$provider::renderView($activity)?>
			<? else:?>
				<div class="crm-task-list-header-description">
					<span class="crm-task-list-header-description-item"><?=GetMessage('CRM_ACTIVITY_PLANNER_VIEW_DATE_AND_TIME')?>:</span>
					<span class="crm-task-list-header-description-date"><?=CCrmComponentHelper::TrimDateTimeString($activity['START_TIME'])?><?if ($activity['END_TIME'] && $activity['START_TIME'] != $activity['END_TIME']):?> - <?=CCrmComponentHelper::TrimDateTimeString($activity['END_TIME'])?><?endif?></span>
				</div>
				<div class="crm-task-list-inner">
					<?=$provider::renderView($activity)?>
				</div><!--crm-task-list-inner-->
				<?if (!empty($arResult['COMMUNICATIONS'])):?>
					<div class="crm-task-list-person">
						<div class="crm-task-list-person-item"><?=GetMessage('CRM_ACTIVITY_PLANNER_RECEIVER')?>:</div>
						<div class="crm-task-list-person-container">
							<div class="crm-task-list-person-slides" data-role="com-slider-slides">
								<?foreach($arResult['COMMUNICATIONS'] as $index => $communication):?>
									<div class="crm-task-list-person-inner">
										<span class="crm-task-list-person-user-image" <?if ($communication['IMAGE_URL']):?> style="background: url('<?=htmlspecialcharsbx($communication['IMAGE_URL'])?>')"<?endif;?>></span>
										<span class="crm-task-list-person-user-info">
						<?if ($communication['VIEW_URL']):?>
							<a href="<?=htmlspecialcharsbx($communication['VIEW_URL'])?>" target="<?=!$arResult['IS_SLIDER_ENABLED'] ? '_top' : ''?>" class="crm-task-list-person-info-name"><?=htmlspecialcharsbx($communication['TITLE'])?></a>
						<?endif;?>
						<div class="crm-task-list-person-info-description"><?=htmlspecialcharsbx($communication['DESCRIPTION'])?></div>
						<div class="crm-task-list-person-info-contacts">
							<?if (!empty($communication['FM']['PHONE'])):
								foreach ($communication['FM']['PHONE'] as $fm):
									if (empty($fm['VALUE']))
									{
										continue;
									}
									$entityType = 'CRM_'.mb_strtoupper(CCrmOwnerType::ResolveName($communication['ENTITY_TYPE_ID']));
									$entityID = $communication['ENTITY_ID'];
									?>
									<div class="crm-task-list-person-info-phone-block">
										<span class="crm-task-list-person-info-phone"><?=GetMessage('CRM_ACTIVITY_PLANNER_TEL')?>:</span>
											<? $link = \CCrmCallToUrl::PrepareLinkAttributes($fm['VALUE'], array(
												'ENTITY_TYPE' => $entityType,
												'ENTITY_ID' => $entityID,
												'SRC_ACTIVITY_ID' => $activity['ID']
											));?>
											<span class="crm-task-list-person-info-phone-item">
											<a href="<?=htmlspecialcharsbx($link['HREF'])?>" onclick="<?=htmlspecialcharsbx($link['ONCLICK'])?>">
												<?=htmlspecialcharsbx(
													\Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($fm['VALUE'])->format()
												)?>
											</a>
										</span>
									</div>
									<?
									break;//get only first number yet.
								endforeach;
							endif?>
							<?if (!empty($communication['FM']['EMAIL'])):
								foreach ($communication['FM']['EMAIL'] as $fm):
									if (empty($fm['VALUE']))
									{
										continue;
									}
								?>
								<div class="crm-task-list-person-info-mail-block">
									<span class="crm-task-list-person-info-mail"><?=GetMessage('CRM_ACTIVITY_PLANNER_EMAIL')?>:</span>
									<span class="crm-task-list-person-info-phone-item">
										<a title="<?=htmlspecialcharsbx($fm['VALUE'])?>" href="mailto:<?=htmlspecialcharsbx($fm['VALUE'])?>">
											<?=htmlspecialcharsbx($fm['VALUE'])?>
										</a>
									</span>
								</div>
								<?
									break;//get only first email yet.
								endforeach;
							endif?>
						</div>
					</span>
									</div><!--crm-task-list-person-inner-->
								<?endforeach?>
							</div><!--crm-task-list-person-slides-->
							<div class="crm-task-list-person-slide">
								<span class="crm-task-list-person-slide-left" data-role="com-slider-left"></span>
								<span class="crm-task-list-person-slide-item" data-role="com-slider-nav" data-current="1" data-cnt="<?=count($arResult['COMMUNICATIONS'])?>">1 / <?=count($arResult['COMMUNICATIONS'])?></span>
								<span class="crm-task-list-person-slide-right" data-role="com-slider-right"></span>
							</div>
						</div><!--crm-task-list-person-container-->
					</div><!--crm-task-list-person-->
				<?endif?>
				<?if (!empty($arResult['FILES_LIST'])):?>
					<div class="crm-task-list-receiver">
						<div class="crm-task-list-receiver-item"><?=GetMessage('CRM_ACTIVITY_PLANNER_FILES')?>:</div><!--crm-task-list-receiver-name-->
						<div class="crm-task-list-options-item-open-inner">
							<div class="bx-crm-dialog-view-activity-files">
								<?foreach ($arResult['FILES_LIST'] as $index => $file):?>
									<div class="bx-crm-dialog-view-activity-file">
										<span class="bx-crm-dialog-view-activity-file-num"><?=($index + 1)?></span>
										<a class="bx-crm-dialog-view-activity-file-text" target="_blank" href="<?=htmlspecialcharsbx($file['viewURL'])?>"><?=htmlspecialcharsbx($file['fileName'])?></a>
									</div>
								<?endforeach;?>
							</div>
						</div>
					</div><!--crm-task-list-receiver-->
				<?endif?>
				<div class="crm-activity-planner-section-control-active" style="height: 35px; ">
					<div class="crm-activity-planner-section crm-activity-planner-section-control">
						<?if ($provider::isTypeEditable($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION'])):?>
						<button class="webform-small-button webform-small-button-accept" data-role="button-edit">
							<span class="webform-small-button-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_EDIT')?></span>
						</button>
						<?endif;?>
						<a href="#" class="webform-button-link" data-role="button-close"><?=GetMessage('CRM_ACTIVITY_PLANNER_SLIDER_CLOSE')?></a>
						<div class="crm-activity-planner-section-control-error-block" style="height: 0;">
							<div class="crm-activity-planner-section-control-error-text"></div>
						</div>
					</div>
				</div>
			<?endif?>
			</div>
		</div><!--crm-task-list-container-->
	</div><!--crm-task-list-wrapper-->

	<script type="text/javascript">
		BX.ready(function() {

			<? if (\CCrmActivityType::Email == $activity['TYPE_ID'] && \CCrmOwnerType::Lead != $activity['OWNER_TYPE_ID']):

			$socNetLogDestTypes = array(
				\CCrmOwnerType::DealName    => 'deals',
				//\CCrmOwnerType::InvoiceName => 'invoices',
				//\CCrmOwnerType::QuoteName   => 'quotes',
			);

			$docsList = array(
				'companies' => array(),
				'contacts' => array(),
				'deals' => array(),
				'leads' => array(),
			);
			$docsLast = array(
				'crm' => array(),
				'companies' => array(),
				'contacts' => array(),
				'deals' => array(),
				'leads' => array(),
			);
			$docsSelected = array();
			foreach ($arResult['DOC_BINDINGS'] as $item)
			{
				$item['OWNER_TYPE'] = \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']);

				if (!array_key_exists($item['OWNER_TYPE'], $socNetLogDestTypes))
					continue;

				$id = 'CRM'.$item['OWNER_TYPE'].$item['OWNER_ID'];
				$type = $socNetLogDestTypes[$item['OWNER_TYPE']];

				$docsList[$type][$id] = array(
					'id'         => $id,
					'entityId'   => $item['OWNER_ID'],
					'entityType' => $type,
					'name'       => htmlspecialcharsbx($item['TITLE']),
					'desc'       => htmlspecialcharsbx($item['DESCRIPTION']),
				);
				$docsLast['crm'][$id] = $id;
				$docsLast[$type][$id] = $id;
				$docsSelected[$id] = $type;
			}

			?>

			var socNetLogDestTypes = {
				deals: BX.CrmEntityType.names.deal,
				invoices: BX.CrmEntityType.names.invoice,
				quotes: BX.CrmEntityType.names.quote
			};

			var docsSelectorContainer = BX('crm_act_planner_view_docs_container');
			docsSelectorContainer.__docs = {};

			var docsSelectorName = 'crm_act_planner_view_docs_selector';
			BX.SocNetLogDestination.init({
				name : docsSelectorName,
				searchInput : BX('crm_act_planner_view_docs_input'),
				extranetUser :  false,
				isCrmFeed : true,
				useClientDatabase: false,
				allowAddUser: false,
				allowSearchCrmEmailUsers: false,
				allowUserSearch: false,
				CrmTypes: ['CRMDEAL'],
				bindMainPopup : {
					node : BX('crm_act_planner_view_docs_container'),
					offsetTop : '5px',
					offsetLeft: '15px'
				},
				bindSearchPopup : {
					node : BX('crm_act_planner_view_docs_container'),
					offsetTop : '5px',
					offsetLeft: '15px'
				},
				callback : {
					select: function(item, type, search, undeleted, name, state)
					{
						var selected = BX.SocNetLogDestination.getSelected(docsSelectorName);
						for (var i in selected)
						{
							if (i != item.id || selected[i] != type)
								BX.SocNetLogDestination.deleteItem(i, selected[i], docsSelectorName);
						}

						docsSelectorContainer.__docs[item.id] = item;

						var data = {
							sessid: BX.bitrix_sessid(),
							ACTION: 'UPDATE_DOCS',
							ITEM_ID: <?=$activity['ID'] ?>,
							DOCS_ITEMS: []
						};
						for (var i in docsSelectorContainer.__docs)
						{
							item = docsSelectorContainer.__docs[i];
							data.DOCS_ITEMS.push({
								entityType: socNetLogDestTypes[item.entityType],
								entityId: item.entityId
							});
						}

						if ('init' != state)
						{
							BX.ajax({
								method: 'POST',
								url: '/bitrix/components/bitrix/crm.activity.editor/ajax.php?id=<?=$activity['ID'] ?>&action=docs',
								data: data,
								'dataType': 'json'
							});
						}

						BX.SocNetLogDestination.BXfpSelectCallback({
							item: item,
							type: type,
							varName: '__soc_net_log_dest',
							bUndeleted: false,
							containerInput: BX('crm_act_planner_view_docs_item'),
							valueInput: BX('crm_act_planner_view_docs_input'),
							formName: docsSelectorName,
							tagInputName: 'crm_act_planner_view_docs_tag',
							tagLink1: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_SET_DOCS')) ?>',
							tagLink2: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_ADD_DOCS')) ?>'
						});
						BX.SocNetLogDestination.closeDialog(docsSelectorName);
					},
					unSelect: function(item)
					{
						delete docsSelectorContainer.__docs[item.id];

						BX.SocNetLogDestination.BXfpUnSelectCallback.apply({
							formName: docsSelectorName,
							inputContainerName: 'crm_act_planner_view_docs_item',
							inputName: 'crm_act_planner_view_docs_input',
							tagInputName: 'crm_act_planner_view_docs_tag',
							tagLink1: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_SET_DOCS')) ?>',
							tagLink2: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_ADD_DOCS')) ?>'
						}, arguments);
					},
					openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: 'crm_act_planner_view_docs_input_box',
						inputName: 'crm_act_planner_view_docs_input',
						tagInputName: 'crm_act_planner_view_docs_tag'
					}),
					closeDialog : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
						inputBoxName: 'crm_act_planner_view_docs_input_box',
						inputName: 'crm_act_planner_view_docs_input',
						tagInputName: 'crm_act_planner_view_docs_tag'
					}),
					openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: 'crm_act_planner_view_docs_input_box',
						inputName: 'crm_act_planner_view_docs_input',
						tagInputName: 'crm_act_planner_view_docs_tag'
					})
				},
				items: <?=CUtil::phpToJSObject($docsList) ?>,
				itemsLast: <?=CUtil::phpToJSObject($docsLast) ?>,
				itemsSelected: <?=CUtil::phpToJSObject($docsSelected) ?>,
				destSort: {}
			});

			BX.bind(BX('crm_act_planner_view_docs_input'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
				formName: docsSelectorName,
				inputName: 'crm_act_planner_view_docs_input'
			}));
			BX.bind(BX('crm_act_planner_view_docs_input'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
				formName: docsSelectorName,
				inputName: 'crm_act_planner_view_docs_input',
				tagInputName: 'crm_act_planner_view_docs_tag'
			}));
			BX.bind(BX('crm_act_planner_view_docs_input'), 'paste', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
				formName: docsSelectorName,
				inputName: 'crm_act_planner_view_docs_input'
			}));
			BX.bind(BX('crm_act_planner_view_docs_input'), 'paste', BX.defer(BX.SocNetLogDestination.BXfpSearch, {
				formName: docsSelectorName,
				inputName: 'crm_act_planner_view_docs_input',
				tagInputName: 'crm_act_planner_view_docs_tag',
				onPasteEvent: true
			}));

			BX.bind(BX('crm_act_planner_view_docs_tag'), 'click', function (e) {
				BX.SocNetLogDestination.openDialog(docsSelectorName);
				BX.PreventDefault(e);
			});
			BX.bind(BX('crm_act_planner_view_docs_container'), 'click', function (e) {
				BX.SocNetLogDestination.openDialog(docsSelectorName);
				BX.PreventDefault(e);
			});

			<? endif ?>

		});
	</script>
	</body>
</html>