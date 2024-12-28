<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\ButtonLocation;

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var string $templateFolder */
/** @var array $arResult */

Loc::loadLanguageFile(__DIR__ . '/template.php');

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.forms',
	'crm.entity-editor',
	'sign.v2.ui.tokens',
	'sign.onboarding',
]);

$currentRegion = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

$stubImagesFolder = "$templateFolder/images/stub";
$stubPhoneImagePath = $currentRegion === 'ru'
	? "{$stubImagesFolder}/phone_ru.png"
	: "{$stubImagesFolder}/phone.png"
;

$openSignHelpdeskCode = (int)$arResult['SIGN_OPEN_HELPDESK_CODE'];
$signMasterOpenLink = new \Bitrix\Main\Web\Uri($arResult['SIGN_OPEN_MASTER_LINK']);
$needShowFooterLegalText = $currentRegion === 'ru';

$getStubTemplate =
	static function() use (
		$needShowFooterLegalText,
		$signMasterOpenLink,
		$openSignHelpdeskCode,
		$stubPhoneImagePath,
	): string
	{
		ob_start();
	?>
	<div class="sign-kanban-stub">
		<div class="sign-kanban-stub--all-info">
			<div class="sign-kanban-stub--info-text-container">
				<div class="sign-kanban-stub--info-text--header-container">
					<div class="sign-kanban-stub__text-info--header-title">
						<?= htmlspecialcharsbx(Loc::getMessage('SIGN_KANBAN_STUB_HEADER_TITLE_MSGVER_1')) ?>
					</div>
					<div class="sign-kanban-stub__text-info--header-description">
						<?= htmlspecialcharsbx(Loc::getMessage('SIGN_KANBAN_STUB_HEADER_DESCRIPTION')) ?>
					</div>
				</div>
				<div class="sign-kanban-stub__text-info--info-block-content">
					<ul class="sign-kanban-stub__text-info--list-items">
						<?php foreach (range(1, 5) as $featureNumber): ?>
							<li class="sign-kanban-stub__text-info--list-item"><?= htmlspecialcharsbx(Loc::getMessage('SIGN_KANBAN_STUB_BODY_FEATURE_' . $featureNumber)) ?></li>
						<?php endforeach; ?>
					</ul>
					<div class="sign-kanban__text-info--footer">
						<?= $needShowFooterLegalText ? htmlspecialcharsbx(Loc::getMessage('SIGN_KANBAN_STUB_BODY_FOOTER')) : '' ?>
					</div>
					<div class="sign-kanban-stub__text-info--bth-container">
						<button class="ui-btn ui-btn-success ui-btn-lg sign-kanban-stub__text-info--bth" id="sign-kanban-stub--btn-start">
							<?= htmlspecialcharsbx(Loc::getMessage('SIGN_KANBAN_STUB_BTN_START')) ?>
						</button>
						<button class="ui-btn ui-btn-primary ui-btn-lg sign-kanban-stub__text-info--bth" id="sign-kanban-stub--btn-detail">
							<?= htmlspecialcharsbx(Loc::getMessage('SIGN_KANBAN_STUB_BTN_DETAILS')) ?>
						</button>
					</div>
				</div>
			</div>
			<div class="sign-kanban-stub--info-image-block">
				<img src="<?= htmlspecialcharsbx($stubPhoneImagePath) ?>" alt="" class="sign-kanban-stub--info-image"/>
			</div>
		</div>
	</div>
	<script>
		const startBtn = document.getElementById('sign-kanban-stub--btn-start');
		const detailBtn = document.getElementById('sign-kanban-stub--btn-detail');

		startBtn.addEventListener('click', event => BX.SidePanel.Instance.open("<?= $signMasterOpenLink->getPathQuery() ?>"));
		detailBtn.addEventListener('click', event => BX.Helper.show("redirect=detail&code=<?= $openSignHelpdeskCode ?>"));
	</script>
	<?php
	return ob_get_clean();
};

if ($arResult['SHOW_STUB'] ?? false)
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(new \Bitrix\UI\Buttons\CreateButton([
		'link' => $signMasterOpenLink->getPathQuery(),
		'text' => Loc::getMessage('SIGN_KANBAN_TOOLBAR_BTN_SIGN'),
	]),
		ButtonLocation::AFTER_TITLE
	);
	echo $getStubTemplate();
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.item.kanban',
			'POPUP_COMPONENT_PARAMS' => [
				'entityTypeId' => $arResult['ENTITY_TYPE_ID'],
				'categoryId' => '0',
			],
			'USE_UI_TOOLBAR' => 'Y',
		],
		$this->getComponent()
	);
	?>
	<?php
}
?>

<script>
	BX.ready(() => {
		(new BX.Sign.Onboarding())
			.getB2bGuide('.ui-toolbar-after-title-buttons > a.ui-btn')
			.startOnce()
		;
	});
</script>
