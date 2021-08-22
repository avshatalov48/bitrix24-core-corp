<?
/**
 * Global variables
 * @var array $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

CJSCore::Init(["voximplant.common", "ui.alerts", "ui.buttons", "ui.sidepanel-content"]);

?>
<div class="ui-slider-section">
    <div class="tel-set-item">
        <div class="bx-vi-docs-body">
			<?=GetMessage('VI_DOCS_BODY_2');?>
			<? if (empty($arResult['DOCUMENTS'])): ?>
				<?=GetMessage('VI_DOCS_UPLOAD_WHILE_RENT');?>
			<? endif; ?>
		</div>

		<? $previousCountry = '' ?>
        <?foreach ($arResult['DOCUMENTS'] as $key => $verification):?>
			<? if ($verification['COUNTRY_CODE'] != $previousCountry): ?>
				<div class="tel-set-item-select-wrap">
					<div class="ui-slider-heading-4"><?= htmlspecialcharsbx($verification['COUNTRY']) ?></div>
				</div>
			<? endif ?>
			<? if($verification['COUNTRY_CODE'] !== 'RU'): ?>
				<div class="voximplant-doc-label"><?= htmlspecialcharsbx($verification['ADDRESS']) ?></div>
			<? endif ?>
            <div class="bx-vi-docs-box">
				<?
					switch ($verification['STATUS'])
					{
						case 'VERIFIED':
							$alertClass = "ui-alert-success";
							break;
						case 'DECLINED':
							$alertClass = "ui-alert-danger";
							break;
						default:
							$alertClass = "ui-alert-warning";
					}
				?>
				<?if($verification['UNVERIFIED_HOLD_UNTIL']):?>
					<div class="ui-alert">
						<span class="ui-alert-message">
							<?=GetMessage('VI_DOCS_UNTIL_DATE', Array('#DATE#' => '<b>'.$verification['UNVERIFIED_HOLD_UNTIL'].'</b>'));?><br><br>
							<?=GetMessage('VI_DOCS_UNTIL_DATE_NOTICE');?>
						</span>
					</div>
				<? endif ?>
				<div id="vi_docs_table_btn_<?=$key?>" class="ui-alert voximplant-status-panel <?=$alertClass?> <?=($verification['STATUS'] !== 'VERIFIED' ? 'voximplant-status-panel-btn-active' : '')?>">
					<span class="ui-alert-message voximplant-status-panel-badge"><?=($verification['STATUS'] == 'ERROR' ? GetMessage('VI_DOCS_SERVICE_ERROR') : $verification['STATUS_NAME']);?></span>
					<br>

					<span class="voximplant-status-panel-btn"></span>
				</div>
				<div id="vi_docs_table_body_<?=$key?>"  class="tel-phones-list-body" style="<?=($verification['STATUS'] == 'VERIFIED'?'display:none':'')?>">
					<table cellspacing="0" cellpadding="0" class="voximplant-status-table" style="width: 100%;">
						<col width="15%">
						<col width="15%">
						<col width="15%">
						<col width="40%">
						<col width="15%">
						<tr>
							<td class="tel-phones-list-th" ><?=GetMessage('VI_DOCS_TABLE_UPLOAD');?></td>
							<td class="tel-phones-list-th"><?=GetMessage('VI_DOCS_TABLE_STATUS');?></td>
							<? if($verification["COUNTRY_CODE"] === "RU"):?>
								<td class="tel-phones-list-th"><?=GetMessage('VI_DOCS_TABLE_TYPE');?></td>
							<? else: ?>
								<td class="tel-phones-list-th"><?=GetMessage('VI_DOCS_TABLE_OWNER');?></td>
							<? endif ?>
							<td class="tel-phones-list-th"><?=GetMessage('VI_DOCS_TABLE_COMMENT');?></td>
							<td class="tel-phones-list-th"></td>
						</tr>
						<?if (is_array($verification['DOCUMENTS'])): ?>
							<?foreach ($verification['DOCUMENTS'] as $document):?>
								<?
								$tdColor = 'red';
								if ($document['DOCUMENT_STATUS'] === 'ACCEPTED' || $document['DOCUMENT_STATUS'] === 'VERIFIED')
									$tdColor = 'VERIFIED';
								else if ($document['DOCUMENT_STATUS'] === 'REJECTED' || $document['DOCUMENT_STATUS'] === 'DECLINED')
									$tdColor = 'DECLINED';
								else
									$tdColor = 'yellow';
								?>
								<tr class="voximplant-status-panel-<?=$tdColor?>">
									<td class="tel-phones-list-td"><?=$document['UPLOADED']?></td>
									<td class="tel-phones-list-td">
										<span class="voximplant-status-panel-badge"><?=$document['DOCUMENT_STATUS_NAME']?></span>
									</td>
									<? if($verification["COUNTRY_CODE"] === "RU"):?>
										<td class="tel-phones-list-td" style="white-space: nowrap;"><?=$document['IS_INDIVIDUAL_NAME']?></td>
									<? else: ?>
										<td class="tel-phones-list-td" style="white-space: nowrap;"><?=htmlspecialcharsbx($document['OWNER'])?></td>
									<? endif ?>
									<td class="tel-phones-list-td"><?=((string)$document['REVIEWER_COMMENT'] !== ''? $document['REVIEWER_COMMENT']: '-')?></td>
									<td class="tel-phones-list-td">
										<? if ($document['DOCUMENT_STATUS'] === 'PENDING'): ?>
											<button
													class="ui-btn ui-btn-xs ui-btn-primary"
													data-verification-id="<?= (int)$document['REG_ID']?>"
													data-role="upload-additional"
											><?= GetMessage("VI_DOCS_SERVICE_UPLOAD") ?></button>
										<? endif ?>
									</td>
								</tr>
							<?endforeach;?>
						<?endif;?>
						<tr>
							<td colspan="5" class="tel-phones-list-td-footer">
								<?if($verification['COUNTRY_CODE']==='RU'):?>
									<a id="vi_docs_upload_btn_<?=$verification['COUNTRY_CODE']?>" href="#docs" class="ui-btn ui-btn-primary">
										<?=($verification['STATUS'] === 'REQUIRED'? GetMessage('VI_DOCS_UPLOAD_BTN'): GetMessage('VI_DOCS_UPDATE_BTN'))?>
									</a>
								<?endif?>
							</td>
						</tr>
					</table>
					<script type="text/javascript">
						BX.bind(BX('vi_docs_table_btn_<?=$key?>'), 'click', function(e)
						{
							if (BX('vi_docs_table_body_<?=$key?>').style.display == 'none')
							{
								BX.addClass(BX('vi_docs_table_body_<?=$key?>'), 'tel-connect-pbx-animate');
								BX.addClass(BX('vi_docs_table_btn_<?=$key?>'), 'voximplant-status-panel-btn-active');
								BX('vi_docs_table_body_<?=$key?>').style.display = 'block';
							}
							else
							{
								BX.removeClass(BX('vi_docs_table_body_<?=$key?>'), 'tel-connect-pbx-animate');
								BX.removeClass(BX('vi_docs_table_btn_<?=$key?>'), 'voximplant-status-panel-btn-active');
								BX('vi_docs_table_body_<?=$key?>').style.display = 'none';
							}

							BX.PreventDefault(e);
						});
					</script>
				</div>

            </div>
            <div class="tel-set-divider"></div>
			<?if(isset($verification['UPLOAD_IFRAME_URL'])):?>
				<div id="vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>" class="tel-set-block-wrap tel-set-block-wrap-2" <?=($verification['SHOW_UPLOAD_IFRAME'] ? '' : 'style="display: none;"')?>>
					<div class="tel-set-block tel-set-block-active">
						<div style="display: block;" class="tel-set-block-inner-wrap" id="tel-set-first">
							<div class="tel-set-inner">
								<?=GetMessage('VI_DOCS_UPLOAD_NOTICE')?>
								<div class="bx-vi-docs-iframe">
									<iframe src="<?=$verification['UPLOAD_IFRAME_URL']?>" frameborder="0" width="100%" height="100%"></iframe>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?if($verification['SHOW_UPLOAD_IFRAME']):?>
					<script>
						BX.scrollToNode("vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>");
					</script>
				<?endif?>
			<?endif?>
            <script type="text/javascript">
                BX.bind(BX('vi_docs_upload_btn_<?=$verification['COUNTRY_CODE']?>'), 'click', function(e)
                {
                    if (BX('vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>').style.display == 'none')
                    {
                        BX.removeClass(BX('vi_docs_upload_btn_<?=$verification['COUNTRY_CODE']?>'), 'ui-btn-primary');
                        BX.addClass(BX('vi_docs_upload_btn_<?=$verification['COUNTRY_CODE']?>'), 'ui-btn-light-border');
                        BX.addClass(BX('vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>'), 'tel-connect-pbx-animate');
                        BX('vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>').style.display = 'block';
                    }
                    else
                    {
                        BX.removeClass(BX('vi_docs_upload_btn_<?=$verification['COUNTRY_CODE']?>'), 'ui-btn-light-border');
                        BX.addClass(BX('vi_docs_upload_btn_<?=$verification['COUNTRY_CODE']?>'), 'ui-btn-primary');
                        BX.removeClass(BX('vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>'), 'tel-connect-pbx-animate');
                        BX('vi_docs_upload_form_<?=$verification['COUNTRY_CODE']?>').style.display = 'none';
                    }

                    BX.PreventDefault(e);
                });
                document.querySelectorAll('[data-role="upload-additional"]').forEach(function(element)
				{
					element.addEventListener('click', function ()
					{
						var verificationId = element.dataset.verificationId;
						BX.ajax.runAction("voximplant.urlmanager.getAdditionalDocumentsUploadUrl", {
							data: {verificationId: verificationId}
						}).then(function (response)
						{
							var data = response.data;
							console.log(data.url)
							window.open(data.url);
						}).catch(function (response)
						{
							console.error(response.errors);
						})
					})
				})
            </script>
			<? $previousCountry = $verification['COUNTRY_CODE'] ?>
        <?endforeach;?>
    </div>
</div>

