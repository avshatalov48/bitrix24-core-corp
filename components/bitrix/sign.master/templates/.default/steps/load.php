<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load('ui.icons.disk');
\Bitrix\Main\UI\Extension::load('ui.icons.service');
\Bitrix\Main\UI\Extension::load('ui.sidepanel-content');
\Bitrix\Main\UI\Extension::load('ui.textcrop');


/** @var array $arResult */
?>

<div class="sign-master__content-title">
	<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_LOAD_FILE_HEADER')?>
</div>

<div class="sign-master__row">
	<div class="sign-master__item" data-element="element" data-action="loadFile" data-multiple="Y">
		<div class="sign-master__item-border"></div>
		<div class="sign-master__item--icon">
			<?/* icon phone <div class="sign-master__item--icon-img --img-photo-phone"></div>*/?>
			<div class="sign-master__item--icon-img">
				<div class="ui-icon ui-icon-file-img"><i></i></div>
			</div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_LOAD_FILE_CREATE_NEW_PIC')?>
			</div>
			<div class="sign-master__item--text-info">
				jpeg, png
			</div>
		</div>
	</div>
	<div class="sign-master__item" data-element="element" data-action="loadFile">
		<div class="sign-master__item-border"></div>
		<div class="sign-master__item--icon">
			<div class="sign-master__item--icon-img">
				<div class="ui-icon ui-icon-file-pdf"><i></i></div>
			</div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_LOAD_FILE_CREATE_NEW_PDF')?>
			</div>
			<div class="sign-master__item--text-info">
				Adobe Acrobat
			</div>
		</div>
	</div>
	<div class="sign-master__item" data-element="element" data-action="loadFile">
		<div class="sign-master__item-border"></div>
		<div class="sign-master__item--icon">
			<div class="sign-master__item--icon-img">
				<div class="ui-icon ui-icon-file-docx"><i></i></div>
			</div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_LOAD_FILE_CREATE_NEW_DOC')?>
			</div>
			<div class="sign-master__item--text-info">
				Microsoft Word
			</div>
		</div>
	</div>
</div>

<div class="sign-master__content-subtitle">
	<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_LOAD_FILE_BLANK')?>
</div>

<div class="sign-master__row">
	<?php foreach ($arResult['BLANKS'] as $blank):?>
		<div class="sign-master__item" data-element="element">
			<input class="sign-master__item--input-hidden" type="radio" name="loadBlank" value="<?php echo $blank['ID']?>" />
			<div class="sign-master__item-border"></div>
			<div class="sign-master__item--icon --bg-blue">
				<div class="sign-master__item--icon-img --img-suitcase"></div>
			</div>
			<div class="sign-master__item--text">
				<div class="sign-master__item_text-name" title="<?php echo htmlspecialcharsbx($blank['TITLE'])?>">
					<div data-crop="crop"><?php echo htmlspecialcharsbx($blank['TITLE']) ?></div>
					<div class="sign-master__item--text-info"><?php echo \FormatDate('x', $blank['DATE_CREATE']);?></div>
				</div>
			</div>
			<?/*
 			<div class="sign-master__item--detailed-info">
				<div class="ui-icon ui-icon-xs ui-icon-service-light-other"><i></i></div>
			</div>
 			*/?>
		</div>
	<?php endforeach?>

	<?/*
	<div class="sign-master__item">
		<div class="sign-master__item--icon --bg-blue">
			<div class="sign-master__item--icon-img --img-basket"></div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">Договор на продажу товаров</div>
		</div>
		<div class="sign-master__item--detailed-info">
			<div class="ui-icon ui-icon-xs ui-icon-service-light-other"><i></i></div>
		</div>
	</div>
	*/?>
</div>

<?/*
<div class="sign-master__content-subtitle">
	Образцы документов
</div>

<div class="sign-master__row">
	<div class="sign-master__item" data-element="element">
		<div class="sign-master__item--icon --bg-blue">
			<div class="sign-master__item--icon-img --img-suitcase"></div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				Договор на оказание услуг
			</div>
		</div>
		<div class="sign-master__item--detailed-info">
			<div class="ui-icon ui-icon-xs ui-icon-service-light-other"><i></i></div>
		</div>
	</div>

	<div class="sign-master__item" data-element="element">
		<div class="sign-master__item--icon --bg-blue">
			<div class="sign-master__item--icon-img --img-basket"></div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				Договор на продажу товаров
			</div>
		</div>
		<div class="sign-master__item--detailed-info">
			<div class="ui-icon ui-icon-xs ui-icon-service-light-other"><i></i></div>
		</div>
	</div>

	<div class="sign-master__item" data-element="element">
		<div class="sign-master__item--icon --bg-blue">
			<div class="sign-master__item--icon-img --img-book"></div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				Договор на проведение мас...
			</div>
		</div>
		<div class="sign-master__item--detailed-info">
			<div class="ui-icon ui-icon-xs ui-icon-service-light-other"><i></i></div>
		</div>
	</div>

	<div class="sign-master__item" data-element="element">
		<div class="sign-master__item--icon --bg-blue">
			<div class="sign-master__item--icon-img --img-key"></div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item_text-name">
				Договор дарения юр.лица
			</div>
		</div>
		<div class="sign-master__item--detailed-info">
			<div class="ui-icon ui-icon-xs ui-icon-service-light-other"><i></i></div>
		</div>
	</div>

	<div class="sign-master__item --download-block">
		<div class="sign-master__item--icon --download-block ">
			<div class="sign-master__item--icon-img --img-market"></div>
		</div>
		<div class="sign-master__item--text">
			<div class="sign-master__item--text-name --market">
				Маркет<span>24</span>
			</div>
			<div class="sign-master__item--text-info">
				Скачайте готовый шаблон
			</div>
		</div>
	</div>
</div>
*/?>

<input type="hidden" name="actionName" value="createDocument" />
<input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.rtf,.odt" name="file[]" data-action="inputFile" style="display: none" />
<input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.rtf,.odt" name="fileMulti[]" data-action="inputFileMulti" multiple style="display: none" />

<div class="sign-master__content-bottom">
	<button type="submit" class="ui-btn ui-btn-lg ui-btn-primary ui-btn-round ui-btn-disabled" id="sign-master__btn" data-role="sign-master__btn-next-step" data-master-next-step-button><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_BUTTON_CONTINUE')?></button>
</div>

<script>
	BX.addCustomEvent('BX.Sign:Error', function() {
		setTimeout(function() {
			document.querySelector('[data-master-next-step-button]').classList.add('ui-btn-disabled');
		});
	});

	BX.ready(function()
	{

		let textCropNodes = document.querySelectorAll('[data-crop="crop"]');

		for (let i = 0; i < textCropNodes.length; i++)
		{
			let text = new BX.UI.TextCrop({
				rows: 2,
				target: textCropNodes[i],

			});
			text.init();
		}

		BX.Sign.Component.Master.adjustLockNavigation(
			document.querySelector('[data-master-next-step-button]'),
			document.querySelector('[data-master-prev-step-button]')
		);

		BX.Sign.Component.Master.loadFileHandler('[data-action="loadFile"]');
		BX.Sign.Component.Master.nextStepHandler(
			'[data-action="inputFile"]',
			'[data-action="inputFileMulti"]',
			'[data-element="element"]'
		);
		<?php if (!\Bitrix\Sign\Restriction::isSignAvailable()): ?>
			BX.Sign.Component.Master.showInfoHelperSlider('limit_crm_sign');
		<?php endif; ?>
	});
</script>
