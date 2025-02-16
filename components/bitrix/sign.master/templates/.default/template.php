<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Sign\Document;
use Bitrix\Sign\Helper\JsonHelper;

/** @var array $arResult */
/** @var array $arParams */
/** @var Document $document */
/** @var SignMasterComponent $component */
/** @var CMain $APPLICATION */

Loc::loadMessages(__FILE__);

if ($arResult['IS_MASTER_PERMISSIONS_FOR_USER_DENIED'] ?? false)
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:ui.info.error',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'USE_PADDING' => false,
			'USE_UI_TOOLBAR' => 'N',
		]
	);

	return;
}

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	"ui.buttons.icons",
	'ui.notification',
	'ui.hint',
	'ui.alerts',
	'crm.entity-editor',
	'main.loader',
	'sign.tour',
	'sign.preview',
	'sign.v2.ui.tokens',
	'sign.v2.sign-settings-factory',
	'sign.v2.b2e.sign-ses-com-agreement',
	'sign.v2.b2e.user-party-counters',
]);

// steps
$steps = include 'steps_map.php';

// init vars
$steIdFromRequest = $component->getRequest($arParams['VAR_STEP_ID']);
$stepId = $steIdFromRequest ?: 'loadFile';
$currentStep =& $steps[$stepId];
$currentStep['active'] = true;
$document = $arResult['DOCUMENT'] ?? null;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'sign-master_slider');

//if new_sign_enable check start
if ((!$document && \Bitrix\Sign\Config\Storage::instance()->isNewSignEnabled()) || $document?->getUid()):?>
	<div id="sign-settings-container"></div>

	<script>
		BX.ready(function()
		{
			BX.message(<?php echo Json::encode([
				'SIGN_CMP_MASTER_TPL_PREVIEW_PAGE' => Loc::getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_PAGE'),
				'SIGN_CMP_MASTER_TPL_PREVIEW_ZOOM' => Loc::getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_ZOOM'),
				'SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE' => Loc::getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE'),
				'SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE_ALERT' => Loc::getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE_ALERT'),
				'SIGN_CMP_MASTER_TPL_MEMBER_NEW_COMMUNICATION' => Loc::getMessage('SIGN_CMP_MASTER_TPL_MEMBER_NEW_COMMUNICATION'),
				'SIGN_CMP_MASTER_TPL_PREVIEW_LOADIN_ERROR' => Loc::getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_LOADIN_ERROR'),
				'SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TITLE' => Loc::getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TITLE'),
				'SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TEXT' => Loc::getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TEXT'),
				'SIGN_CMP_MASTER_TPL_TOUR_STEP_CHOOSE_MEMBER_USER_PARTY_DESCRIPTION' => Loc::getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_CHOOSE_MEMBER_USER_PARTY_DESCRIPTION'),
			])?>);
		});
	</script>

	<script>
		BX.ready(async () => {
			await top.BX.Runtime.loadExtension('sign.v2.editor');
			BX.Sign.V2.createSignSettings('sign-settings-container', {
				uid: '<?=CUtil::JSEscape($document?->getUid() ?? '')?>',
				config: <?= JsonHelper::encodeOrDefault(
					'{}',
					$arResult["WIZARD_CONFIG"],
					false,
					false,
					true,
				) ?>,
				type: '<?= CUtil::JSEscape($arResult['SCENARIO'] ?? '') ?>',
				documentMode: '<?= CUtil::JSEscape($arResult['DOCUMENT_MODE']) ?>',
				templateUid: '<?= CUtil::JSEscape($arResult['TEMPLATE_UID'] ?? '') ?>',
				initiatedByType: '<?= CUtil::JSEscape($arResult['INITIATED_BY_TYPE'] ?? '' )?>',
				chatId: <?= (int)($arResult['CHAT_ID'] ?? 0) ?>,
				b2eDocumentLimitCount: <?= (int)($arResult['MAX_DOCUMENT_COUNT'] ?? 20) ?>,
				},
				<?= JsonHelper::encodeOrDefault('{}', $arResult['ANALYTIC_CONTEXT'] ?? '') ?>
			);
		});
	</script>
<?php

	if (!$arResult['IS_SES_COM_AGREEMENT_ACCEPTED'])
	{
		$APPLICATION->IncludeComponent('bitrix:sign.b2e.sescom.agreement', '', []);
	}

	return;
endif;
//if new_sign_enabled check end

// redirect to the next step
if ($currentStep['nextCode'])
{
	if ($component->isPostRequest() && \Bitrix\Sign\Error::getInstance()->isEmpty())
	{
		\localRedirect($component->getRequestedPage([
			$arParams['VAR_DOC_ID'] => $document ? $document->getEntityId() : null,
			$arParams['VAR_STEP_ID'] => $currentStep['nextCode']
		], [
			$arParams['VAR_STEP_ID'] . '_editor'
		]));
	}
}
?>

<div class="sign-master sign-master__scope">
	<div class="sign-master__wrapper">
		<div class="sign-master__head">
			<div class="sign-master__head-icon"></div>
			<div class="sign-master__head-container">
				<div class="sign-master__head-title"><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_HEADER')?></div>
				<div class="sign-master__head-title--sub"><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_SUB_HEADER')?></div>
			</div>
		</div>
		<form id="sign-master__form" data-role="sign-master__form" method="post" action="<?php echo POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
			<?php echo bitrix_sessid_post()?>
			<input type="hidden" name="<?php echo $arParams['VAR_STEP_ID']?>" value="<?php echo $currentStep['code']?>">
			<div class="sign-master__container">
				<div class="sign-master__steps">
					<?php
					$memberPrevClass = ' --prev';
					foreach ($steps as $code => $step):
						if ($step['active'])
						{
							$memberPrevClass = null;
						}
						if (!$step['title'])
						{
							continue;
						}
						?>
						<div class="sign-master__steps-item<?php echo $memberPrevClass?><?php echo $step['active'] ? ' --active' : ''?>">
							<?php echo $step['title']?>
						</div>
					<?php endforeach?>
				</div>
				<div class="sign-master__content" data-role="sign-master__content">
					<?php /*@see Master.loadFileHandler*/?>
					<div class="ui-alert ui-alert-danger" id="sign-master__too_many_pics" style="display: none;">
						<span class="ui-alert-message">
							<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_ERROR_TOO_MANY_PICS')?>
						</span>
					</div>
					<?php $component->includeError('ui.error')?>
					<?php include 'steps/' . $currentStep['content']?>
				</div>
			</div>
		</form>
	</div>

	<div class="sign-master__preview <?php if (!$document):?>--sm<?php endif;?>">
		<div id="sign-master__preview"></div>
	</div>
</div>

<script>
	BX.ready(function()
	{
		<?php if ($document):
			$layout = $document->getLayout();
			$blank = $document->getBlank();
			?>

		new BX.Sign.Preview({
			renderTo: document.getElementById('sign-master__preview'),
			documentHash: '<?php echo $document->getHash()?>',
			secCode: '<?php echo $document->getSecCode()?>',
			pages: <?php echo \CUtil::phpToJSObject($layout['layout'] ?? [], false, false, true)?>,
			blocks:  <?php echo \CUtil::phpToJSObject($blank ? $blank->getBlocksData() : [], false, false, true)?>
		});

		let buttonNextStep = document.querySelector('[data-role="sign-master__btn-next-step"]');

		if (buttonNextStep)
		{
			buttonNextStep.classList.remove('ui-btn-disabled');
		}

		<?php else: ?>

		new BX.Sign.Preview({
			renderTo: document.getElementById('sign-master__preview')
		});
	<?php endif; ?>
	});
</script>
