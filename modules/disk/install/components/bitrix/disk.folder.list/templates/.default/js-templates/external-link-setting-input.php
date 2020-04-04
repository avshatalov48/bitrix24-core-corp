<? use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
} ?>
<script id="external-link-setting-input" type="text/html">
	<div class="disk-public-link">

		<div class="disk-public-link-block {{#link}}disk-public-link-block-show{{/link}} {{^link}}disk-public-link-block-hide{{/link}}" id="disk-detail-sidebar-public-link-block" data-entity="external-link-block">
			<div class="disk-public-link-block-wrapper" id="disk-detail-sidebar-public-link-block-wrapper" data-entity="external-link-block-wrapper">
				<div class="disk-public-link-copy disk-public-link-copy-center">
					<div class="disk-switcher disk-switcher-flex">
						<div class="disk-switcher {{#link}}js-disk-switcher-on{{/link}} {{^link}}js-disk-switcher-off{{/link}} {{#link}}js-disk-switcher-on{{/link}} {{#firstRender}} {{#link}}disk-switcher-on{{/link}} {{^link}}disk-switcher-off{{/link}} {{/firstRender}} {{^firstRender}} {{#link}}disk-switcher-animate-to-on{{/link}} {{^link}}disk-switcher-animate-to-off{{/link}} {{/firstRender}}" data-entity="external-link-switcher">
							<span class="disk-switcher-label" id="bx-disk-sidebar-shared-outlink-label">{{#link}}<?= Loc::getMessage('DISK_JS_EL_INPUT_EXT_LINK_ON') ?>{{/link}}{{^link}}<?= Loc::getMessage('DISK_JS_EL_INPUT_EXT_LINK_OFF') ?>{{/link}}</span>
							<div class="disk-switcher-point"></div>
						</div>
					</div>
					<div class="disk-public-link-input-copy" for="bx-disk-sidebar-shared-outlink-input" data-entity="copy-btn" title="<?= Loc::getMessage('DISK_JS_EL_INPUT_PUBLIC_LINK_COPY_HINT') ?>"></div>
					<input class="disk-public-link-input" id="bx-disk-sidebar-shared-outlink-input" value="{{link}}" type="text" data-entity="external-link-input">
					<div class="disk-public-link-config" id="disk-detail-sidebar-public-link-config" data-entity="public-link-config"></div>
				</div>
				{{&placeholderDescription}}
				<div class="disk-public-link-config-place" data-entity="external-link-config-place"></div>
			</div>
		</div>

	</div>

</script>