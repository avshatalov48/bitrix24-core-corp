<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Member;
use Bitrix\Sign\Error;

/** @var array $arParams */
/** @var array $arResult */
/** @var Document $document */
/** @var SignMasterComponent $component */

$document = $arResult['DOCUMENT'] ?? null;
if (!$document)
{
	return;
}

$members = $document->getMembers();
$document->getBlank()->updateBlocks();
$blocks = $document->getBlank()->getBlocks();
$blocksInfo = array_filter($blocks, function($block) {return $block->getPart() === 1;});
$blocksFill = array_filter($blocks, function($block) {return $block->getPart() > 1;});
$linkEditor = str_replace('#doc_id#', $document->getId(), $arParams['PAGE_URL_EDIT']);
$linkBack = $component->getRequestedPage([
	$arParams['VAR_STEP_ID'] => 'changePartner'
]);

\Bitrix\Main\UI\Extension::load([
	'ui.notification',
	'clipboard',
	'ui.forms',
	'sign.backend',
	'sign.tour',
]);
?>

<input type="hidden" name="actionName" value="sendDocument" />

<div class="sign-master__content-title"><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_SEND_HEADER')?></div>




<div class="sign-master__send-summary--wrapper" data-wrapper="titleEditor">
	<div class="sign-master__send-summary--icon"></div>
	<div class="sign-master__send-summary">

		<div class="sign-master__send-summary-value">
			<span class="sign-master__send-summary--title">
				<span data-wrapper="title"><?php echo \htmlspecialcharsbx($document->getTitle())?></span>
				<span class="sign-master__send-summary--title-edit-icon" data-wrapper="edit"><i></i></span>
			</span>
			<div class="sign-maste__send-summary--text">
				<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_SEND_ADDED_BLOCKS', ['#CNT#' => count($blocksInfo)])?>
			</div>
			<div class="sign-master__send-summary--text">
				<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_SEND_ADDED_BLOCKS_FILL', ['#CNT#' => count($blocksFill)])?>
			</div>
		</div>

		<div class="sign-master__send-summary-edit">
			<div class="sign-master__send-summary--title-edit">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100 sign-master__send-summary--title-input" data-wrapper="inputWrapper">
					<input type="text" class="ui-ctl-element" data-param="saveTitleValue" value="<?= \htmlspecialcharsbx($document->getTitle())?>">
				</div>

				<?php /*
				<input type="text" style="width: 90%;" data-param="saveTitleValue" value="<?= \htmlspecialcharsbx($document->getTitle())?>" >
				<input type="button" value="save" style="width: 30px;" data-action="saveTitle" >
				<input type="button" value="save" style="width: 30px;" data-action="saveTitle" >
				*/?>

				<div class="ui-btn ui-btn-sm ui-btn-primary sign-master__send-summary--title-edit-save" data-action="saveTitle"><i></i></div>
				<div class="ui-btn ui-btn-sm ui-btn-light-border sign-master__send-summary--title-edit-cancel" data-action="cancel"><i></i></div>
			</div>
			<div class="sign-maste__send-summary--text"><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_SEND_BAN_SYMBOLS') ?></php></div>
		</div>

	</div>
	<div class="sign-master__send-summary--links">
		<span onclick="BX.Sign.Component.Master.openEditor('<?php echo \CUtil::JSEscape($linkEditor)?>');" class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round" data-master-change-document>
			<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_BUTTON_EDIT')?>
		</span>
	</div>
</div>

<?php
$communications = [];

foreach ($members as $member): /** @var Member $member */
	$memberCommunications = $member->getCommunications();
	$communications[$member->getId()] = $memberCommunications;
	$memberUrl = null;

	if ($memberCommunications)
	{
		$memberCommunications = [
			'type' => $member->getCommunicationType(),
			'value' => $member->getCommunicationValue(),
		];
	}
?>
<div class="sign-master__send-member--wrapper">

	<div class="sign-master__send-member--container">
		<div class="sign-master__send-member--info-container">
			<div class="sign-master__send-member--whois">
				<?php echo $member->isThirdParty() ? Loc::getMessage('SIGN_CMP_MASTER_TPL_LABEL_WHOIS_3PARTY') : Loc::getMessage('SIGN_CMP_MASTER_TPL_LABEL_WHOIS_WHO')?>
			</div>

			<?php if ($member->isThirdParty()): ?>
				<div class="sign-master__send-member--name">
					<a href="<?php echo $memberUrl = \Bitrix\Sign\Integration\CRM::getContactUrl($member->getContactId())?>">
						<?php echo \htmlspecialcharsbx($member->getContactName() ?: Loc::getMessage('SIGN_CMP_MASTER_TPL_CONTACT_NONAME'))?>
					</a>
				</div>
			<?php else: ?>
				<div class="sign-master__send-member--name">
					<a href="<?php echo $memberUrl = \Bitrix\Sign\Integration\CRM::getCompanyUrl($document->getCompanyId())?>">
						<?php echo \htmlspecialcharsbx((string)$document->getCompanyTitle())?>
					</a>
				</div>
			<?php endif?>

		</div>

		<div class="sign-master__send-member--info-btn">
			<?php if ($member->isSigned()):?>
				<span class="sign-master__send-member--signlabel --signed">
					<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_MEMBER_SIGNED')?>
				</span>
			<?php else:?>
				<span class="sign-master__send-member--signlabel">
					<?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_MEMBER_NOT_SIGNED')?>
				</span>
			<?php endif?>
		</div>
	</div>
	<div class="sign-master__send-member--info-additional" data-recipient-layout="1">
		<div class="sign-master__send-member--link-wrapper" data-recipient-layout="1">
			<div class="sign-master__send-member--communications" data-member-communication-id="<?php echo $member->getId()?>" data-member-url="<?php echo $memberUrl?>">
				<span class="sign-master__send-member--communications-title"><?php echo htmlspecialcharsbx(Loc::getMessage('SIGN_CMP_MASTER_TPL_CONFIRMATION_CHANEL_IDENTIFICATION')) ?> </span>
				<span class="sign-master__send-member--communications-arrow" title="<?php echo htmlspecialcharsbx($memberCommunications['value'] ?? Loc::getMessage('SIGN_CMP_MASTER_TPL_MEMBER_NEW_COMMUNICATION'))?>">
					<span><?php echo htmlspecialcharsbx($memberCommunications['value'] ?? Loc::getMessage('SIGN_CMP_MASTER_TPL_MEMBER_NEW_COMMUNICATION'))?></span>
				</span>
				<input type="hidden" name="communications[<?php echo htmlspecialcharsbx($member->getId())?>]" value="<?php echo htmlspecialcharsbx($memberCommunications ? $memberCommunications['type'] . '|' . $memberCommunications['value'] : '')?>" />
			</div>
		</div>
	</div>


	<script>
		//@todo: remove
		<?php
			if (defined('SIGN_SELF_HOSTED_DUMP_LINKS') && SIGN_SELF_HOSTED_DUMP_LINKS === true)
			{
				echo 'console.log('.$member->getPart().', "'.$member->getSignUrl().'");';
			}
		?>
	</script>
</div>
<?php endforeach?>

<div class="sign-master__content-bottom">
	<a href="<?php echo htmlspecialcharsbx($linkBack)?>" class="ui-btn ui-btn-lg ui-btn-light-border ui-btn-round" data-master-next-step-button><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_BUTTON_BACK')?></a>
	<button type="submit" class="ui-btn ui-btn-lg ui-btn-primary ui-btn-round" data-role="sign-editor__btm-save" data-master-prev-step-button><?php echo Loc::getMessage('SIGN_CMP_MASTER_TPL_BUTTON_FINISH')?></button>
</div>

<script>
	function smsNotAllowed()
	{
		top.BX.UI.InfoHelper.show('limit_crm_sign_messenger_identification');
	}

	BX.ready(function()
	{
		BX.Sign.Component.Master.adjustLockNavigation(
			document.querySelector('[data-master-next-step-button]'),
			document.querySelector('[data-master-prev-step-button]')
		);

		BX.Sign.Component.Master.saveTitle(
			<?= $document->getId()?>
		);

		BX.Sign.Component.Master.adjustLockNavigation(
			document.querySelector('[data-master-change-document]')
		);

		BX.Sign.Component.Master.initMuteCheckbox(
			document.querySelectorAll('.sign-master__send-member--not-send-checkbox'),
			'.sign-master__send-member--wrapper',
			'data-recipient-layout'
		);

		BX.Sign.Component.Master.initCommunicationsSelector({
			containers: document.querySelectorAll('.sign-master__send-member--communications'),
			attrMemberIdName: 'data-member-communication-id',
			attrMemberUrlName: 'data-member-url',
			communications: <?php echo \CUtil::phpToJSObject($communications, false, false, true)?>,
			smsAllowed: <?php echo $component->isSmsAllowed() ? 'true' : 'false'?>,
			smsNotAllowedCallback: smsNotAllowed,
		});

		<?php if (Error::getInstance()->getErrorByCode('SMS_IS_NOT_ALLOWED')):?>
		smsNotAllowed();
		<?php endif?>
	});
</script>
