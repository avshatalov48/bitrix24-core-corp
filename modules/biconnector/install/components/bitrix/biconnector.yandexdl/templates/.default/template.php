<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.design-tokens',
	'ui.sidepanel-content',
	'ui.buttons',
	'ui.info-helper',
]);
?>
<div class="wraper-bi">
	<div class="ui-slider-section biconnector-market__description">
		<div class="biconnector-market__section-icon">
			<svg width="158" height="129" viewBox="0 0 158 129" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M113.818 4.33819C114.776 4.33819 115.552 3.56172 115.552 2.60391C115.552 1.64609 114.776 0.869629 113.818 0.869629C112.86 0.869629 112.084 1.64609 112.084 2.60391C112.084 3.56172 112.86 4.33819 113.818 4.33819ZM28.2401 19.3196C30.3165 19.3196 31.9997 17.6363 31.9997 15.5599C31.9997 13.4835 30.3165 11.8003 28.2401 11.8003C26.1637 11.8003 24.4804 13.4835 24.4804 15.5599C24.4804 17.6363 26.1637 19.3196 28.2401 19.3196ZM28.2401 17.2234C29.1588 17.2234 29.9035 16.4786 29.9035 15.5599C29.9035 14.6412 29.1588 13.8965 28.2401 13.8965C27.3214 13.8965 26.5766 14.6412 26.5766 15.5599C26.5766 16.4786 27.3214 17.2234 28.2401 17.2234ZM9.13998 56.0228C11.9366 56.0228 14.2037 53.7557 14.2037 50.959C14.2037 48.1624 11.9366 45.8953 9.13998 45.8953C6.34335 45.8953 4.07623 48.1624 4.07623 50.959C4.07623 53.7557 6.34335 56.0228 9.13998 56.0228ZM9.13998 53.38C10.4771 53.38 11.561 52.2961 11.561 50.959C11.561 49.622 10.4771 48.5381 9.13998 48.5381C7.80292 48.5381 6.71901 49.622 6.71901 50.959C6.71901 52.2961 7.80292 53.38 9.13998 53.38ZM137.703 119.74C139.694 119.74 141.308 118.126 141.308 116.135C141.308 114.144 139.694 112.53 137.703 112.53C135.712 112.53 134.098 114.144 134.098 116.135C134.098 118.126 135.712 119.74 137.703 119.74ZM137.703 117.864C138.658 117.864 139.432 117.09 139.432 116.135C139.432 115.18 138.658 114.406 137.703 114.406C136.748 114.406 135.974 115.18 135.974 116.135C135.974 117.09 136.748 117.864 137.703 117.864ZM80.0001 129C114.242 129 142 101.242 142 67C142 32.7584 114.242 5.00002 80.0001 5.00002C45.7584 5.00002 18.0001 32.7584 18.0001 67C18.0001 101.242 45.7584 129 80.0001 129ZM17.9704 124.666H5.91744C5.81431 124.666 5.71207 124.662 5.61085 124.655C2.88685 124.591 0.697892 122.302 0.697754 119.487C0.698546 118.115 1.22957 116.8 2.174 115.831C2.6581 115.334 3.2305 114.949 3.85326 114.691C3.84284 114.552 3.83753 114.411 3.83753 114.268C3.83839 112.821 4.39872 111.433 5.39524 110.41C6.39177 109.387 7.74287 108.813 9.15131 108.814C10.9555 108.816 12.5484 109.743 13.5057 111.159C13.9603 110.993 14.4496 110.903 14.9594 110.903C17.2116 110.906 19.0613 112.671 19.2757 114.927C21.4336 115.41 23.0485 117.386 23.0466 119.748C23.0444 122.47 20.8959 124.676 18.2471 124.674C18.1542 124.674 18.0619 124.672 17.9704 124.666ZM145.704 28.7851H154.258C154.323 28.7888 154.389 28.7907 154.454 28.7907C156.334 28.7915 157.859 27.2879 157.861 25.4319C157.862 23.8213 156.716 22.4742 155.184 22.1446C155.032 20.6062 153.72 19.4033 152.121 19.4014C151.759 19.4012 151.412 19.4625 151.09 19.5754C150.41 18.6105 149.28 17.9783 147.999 17.9768C147 17.9762 146.041 18.3676 145.334 19.065C144.627 19.7625 144.229 20.7087 144.228 21.6956C144.228 21.7926 144.232 21.8888 144.239 21.984C143.798 22.1594 143.391 22.4221 143.048 22.7609C142.377 23.4219 142.001 24.3187 142 25.254C142 27.173 143.554 28.7338 145.487 28.7772C145.559 28.7825 145.631 28.7851 145.704 28.7851Z" fill="#E5F9FF"/>
				<circle cx="80" cy="67" r="31" fill="#5F82F7"/>
				<rect x="64.5" y="68.9375" width="5.8125" height="9.6875" fill="white"/>
				<rect x="72.25" y="59.25" width="5.8125" height="19.375" fill="white"/>
				<rect x="80" y="63.125" width="5.8125" height="15.5" fill="white"/>
				<rect x="87.75" y="53.4375" width="5.8125" height="25.1875" fill="white"/>
			</svg>
		</div>
		<div class="biconnector-market__content">
			<div class="biconnector-market__section-title ui-typography-heading-h1"><?=Loc::getMessage('TITLE_REPORT_TEMPLATES')?></div>
			<div class="ui-slider-inner-box">
				<p class="ui-slider-paragraph-2 biconnector-market__section-paragraph"><?=Loc::getMessage('DESCRIPTION_REPORT_TEMPLATES')?></p>
				<p class="ui-slider-paragraph-2 biconnector-market__section-paragraph">
					<a href="javascript:top.BX.Helper.show('redirect=detail&code=17402692');" class="biconnector-market__section-link"><?=Loc::getMessage('LINK_MANUAL_REPORT_TEMPLATES')?></a>
				</p>
				<div class="biconnector-market__info-block">
					<div class="biconnector-market__info-title">
						<svg class="biconnector-market__info-title-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M8.9849 0.00012207C13.9469 0.00012207 17.9695 4.02242 17.9695 8.98498C17.9695 13.9468 13.9469 17.9694 8.9849 17.9694C4.02266 17.9694 0 13.9468 0 8.98498C0 4.02242 4.02266 0.00012207 8.9849 0.00012207ZM10.2118 4.08379C10.2118 4.8272 9.60328 5.42985 8.85267 5.42985C8.10205 5.42985 7.49356 4.8272 7.49356 4.08379C7.49356 3.34039 8.10205 2.73774 8.85267 2.73774C9.60328 2.73774 10.2118 3.34039 10.2118 4.08379ZM6.15315 6.68282H9.32948V6.68509H10.2063V12.6612H11.5615V13.853L10.2063 13.853H7.74132L6.15315 13.853V12.6612H7.74132V7.89359H6.15315V6.68282Z" fill="#559BE6"/>
						</svg>
						<?=Loc::getMessage('CONNECT_DATA_TITLE')?>
					</div>
					<div class="biconnector-market__info-message">
						<span class="biconnector-market__info-message-subtitle"><?=Loc::getMessage('CONNECT_DATA_SERVER')?></span>
						<span class="biconnector-market__info-message-text"><?=$arResult['SERVER_NAME']?></span>
						<span class="biconnector-market__info-message-icon" id="biconnector-ya_btn_copy_server_name"></span>
					</div>
					<div class="biconnector-market__info-message">
						<span class="biconnector-market__info-message-subtitle"><?=Loc::getMessage('CONNECT_DATA_KEY')?></span>
						<span class="biconnector-market__info-message-text"><?=$arResult['ACCESS_KEY']?></span>
						<span class="biconnector-market__info-message-icon" id="biconnector-ya_btn_copy_access_key"></span>
					</div>
				</div>
				<p class="ui-slider-paragraph-2 biconnector-market__section-paragraph">
					<a href="<?=$arResult['CONNECT_LINK']?>" target="_blank" class="ui-btn ui-btn-primary"><?=Loc::getMessage('BTN_TEMPLATES')?></a>
				</p>
			</div>
		</div>
	</div>

	<?php
	/*
	Loader::includeModule('market');

	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:market.main',
		'',
		[
			'PLACEMENT' => 'by_yalens',
			'HIDE_CATEGORIES' => 'Y',
			'HIDE_TOOLBAR' => 'Y',
			'SHOW_TITLE' => 'N',
			'CHANGE_HISTORY' => 'N',
			'ADDITIONAL_BODY_CLASS' => 'biconnector-market__body-slider',
		]
	);
	*/
	?>
</div>

<script>
	BX.ready(() => {
		BX.clipboard.bindCopyClick(
			'biconnector-ya_btn_copy_server_name',
			{
				text: '<?=CUtil::JSescape($arResult['SERVER_NAME'])?>'
			}
		);
		BX.clipboard.bindCopyClick(
			'biconnector-ya_btn_copy_access_key',
			{
				text: '<?=CUtil::JSescape($arResult['ACCESS_KEY'])?>'
			}
		);

		<?php if ($arResult['SHOW_TITLE'] === 'N'): ?>
		document.querySelector('.ui-side-panel-wrap-title-name').innerText = '';
		<?php endif; ?>
	});
</script>
