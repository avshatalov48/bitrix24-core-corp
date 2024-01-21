<? use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
} ?>
<script id="external-link-setting-input" type="text/html">
	<div class="disk-detail-sidebar-public-link">
		<div class="disk-detail-sidebar-public-link-handler">
			<div class="disk-detail-sidebar-public-link-handler-label"><?= Loc::getMessage('DISK_FILE_VIEW_EXTERNAL_LINK') ?></div>
			<div class="disk-detail-sidebar-switcher">
				<div class="disk-switcher {{#link}}js-disk-switcher-on{{/link}} {{^link}}js-disk-switcher-off{{/link}} {{#link}}js-disk-switcher-on{{/link}} {{#firstRender}} {{#link}}disk-switcher-on{{/link}} {{^link}}disk-switcher-off{{/link}} {{/firstRender}} {{^firstRender}} {{#link}}disk-switcher-animate-to-on{{/link}} {{^link}}disk-switcher-animate-to-off{{/link}} {{/firstRender}}" data-entity="external-link-switcher">
					<span class="disk-switcher-label" id="bx-disk-sidebar-shared-outlink-label">{{#link}}<?= Loc::getMessage('DISK_JS_EL_INPUT_EXT_LINK_ON') ?>{{/link}}{{^link}}<?= Loc::getMessage('DISK_JS_EL_INPUT_EXT_LINK_OFF') ?>{{/link}}</span>
					<div class="disk-switcher-point"></div>
				</div>
			</div>
		</div>
		<div class="disk-detail-sidebar-public-link-block {{#link}}disk-detail-sidebar-public-link-block-show{{/link}} {{^link}}disk-detail-sidebar-public-link-block-hide{{/link}}" id="disk-detail-sidebar-public-link-block" data-entity="external-link-block">
			<div class="disk-detail-sidebar-public-link-block-wrapper" id="disk-detail-sidebar-public-link-block-wrapper" data-entity="external-link-block-wrapper">
				<div class="disk-detail-sidebar-public-link-copy">
					<div class="disk-detail-sidebar-public-link-input-copy" for="bx-disk-sidebar-shared-outlink-input" data-entity="copy-btn" title="<?= Loc::getMessage('DISK_JS_EL_INPUT_PUBLIC_LINK_COPY_HINT') ?>"></div>
					<input class="disk-detail-sidebar-public-link-input" id="bx-disk-sidebar-shared-outlink-input" value="{{link}}" type="text" data-entity="external-link-input" readonly="readonly">
					<div class="disk-detail-sidebar-public-link-config" id="disk-detail-sidebar-public-link-config" data-entity="public-link-config"></div>
				</div>
				{{&placeholderDescription}}
			</div>
		</div>

	</div>

</script>