<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Imopenlines\Limit;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>

<div class="imopenlines-form-settings-section">
	<?if(!empty($arResult['ERROR'])):?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message">
			<?foreach ($arResult['ERROR'] as $error):?>
				<?= $error ?><br>
			<?endforeach;?>
			</span>
		</div>
	<?endif;?>
	<div class="imopenlines-control-checkbox-container">
		<label class="imopenlines-control-checkbox-label">
			<input type="checkbox"
				   class="imopenlines-control-checkbox"
				   id="imol_vote_message"
				   name="CONFIG[VOTE_MESSAGE]"
				   value="Y"
				   data-limit="<?=!Limit::canUseVoteClient()?'Y':'N';?>"
				   <?if($arResult['CONFIG']['VOTE_MESSAGE'] === 'Y') { ?>checked<? } ?>>
			<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_NEW')?>
			<?if(!Limit::canUseVoteClient()):?>
				<span class="tariff-lock"></span>
				<script type="text/javascript">
					BX.bind(BX('imol_vote_message'), 'change', function(e){
						BX('imol_vote_message').checked = false;
						window.BX.imolTrialHandler.openPopupQueueVote();
					});
				</script>
			<?elseif(Limit::isDemoLicense()):?>
				<span class="tariff-lock" onclick="window.BX.imolTrialHandler.openPopupQueueVote(); return false;"></span>
			<?endif;?>
		</label>
	</div>
	<div id="imol_vote_message_block" <?if($arResult['CONFIG']['VOTE_MESSAGE'] !== 'Y') { ?>class="invisible"<? } ?>>
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   class="imopenlines-control-checkbox"
					   name="CONFIG[VOTE_BEFORE_FINISH]"
					   value="Y"
					   <?if($arResult['CONFIG']['VOTE_BEFORE_FINISH'] === 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_BEFORE_FINISH')?>
			</label>
		</div>
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   class="imopenlines-control-checkbox"
					   <?/*id="imol_vote_message"*/?>
					   name="CONFIG[VOTE_CLOSING_DELAY]"
					   value="Y"
					   <?if($arResult['CONFIG']['VOTE_CLOSING_DELAY'] === 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_CLOSING_DELAY_NEW')?>
			</label>
		</div>
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_vote_time_limit"
					   class="imopenlines-control-checkbox"
					   name="CONFIG[VOTE_ENABLE_TIME_LIMIT]"
					   value="Y"
					   <?if($arResult['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] === 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_ENABLE_VOTE_TIME_LIMIT')?>
			</label>
		</div>
		<div<?if($arResult['CONFIG']['VOTE_ENABLE_TIME_LIMIT'] !== 'Y') { ?> class="invisible"<? } ?> id="imol_action_vote_time_limit">
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT')?>
				</div>
				<div class="imopenlines-control-inner">
					<select class="imopenlines-control-input" name="CONFIG[VOTE_TIME_LIMIT]">
						<option value="86400" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 86400) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_1_DAY')?></option>
						<option value="172800" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 172800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_2_DAY')?></option>
						<option value="259200" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 259200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_3_DAY')?></option>
						<option value="345600" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 345600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_4_DAY')?></option>
						<option value="432000" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 432000) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_5_DAY')?></option>
						<option value="604800" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 604800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_7_DAY')?></option>
						<option value="2592000" <?if((int)$arResult['CONFIG']['VOTE_TIME_LIMIT'] === 2592000) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_TIME_LIMIT_30_DAY')?></option>
					</select>
				</div>
			</div>
		</div>
		<div class="imopenlines-control-container">
			<div class="imol-vote-container">
				<div class="imopenlines-form-settings-title imopenlines-form-settings-title-top">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_1_TITLE')?>
				</div>
				<div class="imopenlines-vote-container">
					<div class="imopenlines-vote-text-block">
						<div class="imopenlines-control-container">
							<div class="imopenlines-vote-icons-item">
								<div class="imol-vote-block-icon imol-vote-icon-like-small"></div>
								<div class="imol-vote-block-icon imol-vote-icon-dislike-small"></div>
							</div>
							<div class="imopenlines-control-subtitle">
								<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_TEXT_NEW')?>
							</div>
							<div class="imopenlines-control-inner">
								<input class="imopenlines-control-input"
									   maxlength="100"
									   type="text"
									   name="CONFIG[VOTE_MESSAGE_1_TEXT]"
									   value="<?=str_replace(array('[BR]', '[br]', '#BR#'), PHP_EOL, htmlspecialcharsbx($arResult['CONFIG']['VOTE_MESSAGE_1_TEXT']))?>">
							</div>
						</div>
						<div class="imopenlines-control-container">
							<div class="imopenlines-vote-icons-item">
								<div class="imol-vote-content-icon imol-vote-content-icon-sad"></div>
							</div>
							<div class="imopenlines-control-subtitle">
								<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_DISLIKE_NEW')?>
							</div>
							<div class="imopenlines-control-inner">
								<input class="imopenlines-control-input"
									   maxlength="100"
									   type="text"
									   name="CONFIG[VOTE_MESSAGE_1_DISLIKE]"
									   value="<?=str_replace(array('[BR]', '[br]', '#BR#'), PHP_EOL, htmlspecialcharsbx($arResult['CONFIG']['VOTE_MESSAGE_1_DISLIKE']))?>">
							</div>
						</div>
						<div class="imopenlines-control-container">
							<div class="imopenlines-vote-icons-item">
								<div class="imol-vote-content-icon imol-vote-content-icon-smile"></div>
							</div>
							<div class="imopenlines-control-subtitle">
								<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_LIKE_NEW')?>
							</div>
							<div class="imopenlines-control-inner">
								<input class="imopenlines-control-input"
									   maxlength="100"
									   type="text"
									   name="CONFIG[VOTE_MESSAGE_1_LIKE]"
									   value="<?=str_replace(['[BR]', '[br]', '#BR#'], PHP_EOL, htmlspecialcharsbx($arResult['CONFIG']['VOTE_MESSAGE_1_LIKE']))?>">
							</div>
						</div>
					</div>
				</div>
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_1_DESC')?>
				</div>
			</div>
			<div class="imol-vote-container">
				<div class="imopenlines-form-settings-title">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_2_TITLE_NEW')?>
				</div>
				<div class="imopenlines-vote-container">
					<div class="imopenlines-vote-text-block">
						<div class="imopenlines-control-container">
							<div class="imopenlines-vote-icons-item">
								<div class="imol-vote-block-number imol-vote-block-number-like">1</div>
								<div class="imol-vote-block-number imol-vote-block-number-dislike">0</div>
							</div>
							<div class="imopenlines-control-subtitle">
								<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_TEXT_NEW')?>
							</div>
							<div class="imopenlines-control-inner">
								<textarea class="imopenlines-control-input imopenlines-control-input-vote"
										  name="CONFIG[VOTE_MESSAGE_2_TEXT]"><?=str_replace(['[BR]', '[br]', '#BR#'], PHP_EOL, htmlspecialcharsbx($arResult['CONFIG']['VOTE_MESSAGE_2_TEXT']))?></textarea>
							</div>
						</div>
						<div class="imopenlines-control-container">
							<div class="imopenlines-vote-icons-item">
								<div class="imol-vote-content-icon imol-vote-content-icon-sad"></div>
							</div>
							<div class="imopenlines-control-subtitle">
								<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_DISLIKE_NEW')?>
							</div>
							<div class="imopenlines-control-inner">
								<input name="CONFIG[VOTE_MESSAGE_2_DISLIKE]"
									   class="imopenlines-control-input"
									   type="text"
									   value="<?=str_replace(['[BR]', '[br]', '#BR#'], PHP_EOL, htmlspecialcharsbx($arResult['CONFIG']['VOTE_MESSAGE_2_DISLIKE']))?>">
							</div>
						</div>
						<div class="imopenlines-control-container">
							<div class="imopenlines-vote-icons-item">
								<div class="imol-vote-content-icon imol-vote-content-icon-smile"></div>
							</div>
							<div class="imopenlines-control-subtitle">
								<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_LIKE_NEW')?>
							</div>
							<div class="imopenlines-control-inner">
								<input name="CONFIG[VOTE_MESSAGE_2_LIKE]"
									   class="imopenlines-control-input"
									   type="text"
									   value="<?=str_replace(['[BR]', '[br]', '#BR#'], PHP_EOL, htmlspecialcharsbx($arResult['CONFIG']['VOTE_MESSAGE_2_LIKE']))?>">
							</div>
						</div>
					</div>
				</div>
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_VOTE_MESSAGE_2_DESC_NEW')?>
				</div>
			</div>
		</div>
	</div>
</div>

