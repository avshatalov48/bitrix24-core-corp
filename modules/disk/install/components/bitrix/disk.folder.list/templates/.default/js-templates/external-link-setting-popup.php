<? use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
} ?>
<script id="external-link-setting-popup" type="text/html">
		<div class="disk-external-link-setting-popup">
			<label for="public-link-setting-checkbox-time-limit" class="disk-external-link-setting-popup-label">
				<input type="checkbox" {{#hasDeathTime}} checked="checked" {{/hasDeathTime}} class="disk-external-link-setting-checkbox" data-entity="public-link-setting-checkbox-time-limit" id="public-link-setting-checkbox-time-limit">
				<span><?= Loc::getMessage('DISK_JS_EL_SETTINGS_LINK_HINT_PROTECT_BY_LIFETIME') ?></span>
			</label>
		</div>
		{{#hasDeathTime}}
		<div class="disk-external-link-setting-popup" data-entity="public-link-setting-time-limit">
			<span class="disk-external-link-setting-time-limit"><?= Loc::getMessage('DISK_JS_EL_SETTINGS_LINK_VALUE_PROTECT_BY_LIFETIME_2') ?>{{! getFormattedDeathTime}}</span>
		</div>
		{{/hasDeathTime}}
		{{^hasDeathTime}}
		<div class="disk-external-link-setting-popup disk-external-link-setting-popup-flex disk-external-link-setting-popup-disabled"
			 data-entity="public-link-setting-time-limit">
			<input type="text" class="disk-external-link-setting-popup-input" data-entity="public-link-setting-popup-input" value="{{defaultValueForTime}}">
			<div class="disk-external-link-setting-popup-dropdown" data-entity="public-link-setting-popup-dropdown"><?= Loc::getMessage('DISK_JS_EL_SETTINGS_LINK_LABEL_MINUTE') ?></div>
		</div>
		{{/hasDeathTime}}
		<div class="disk-external-link-setting-popup">
			<label for="public-link-setting-checkbox-password" class="disk-external-link-setting-popup-label">
				<input type="checkbox" {{#hasPassword}} checked="checked" {{/hasPassword}} class="disk-external-link-setting-checkbox" data-entity="public-link-setting-checkbox-password" id="public-link-setting-checkbox-password">
				<span><?= Loc::getMessage('DISK_JS_EL_SETTINGS_LINK_PROTECT_BY_PASSWORD') ?></span>
			</label>
		</div>
		{{^hasPassword}}
		<div class="disk-external-link-setting-popup disk-external-link-setting-popup-password disk-external-link-setting-popup-disabled"
			 data-entity="public-link-setting-password">
			<input type="password" class="disk-external-link-setting-popup-input disk-external-link-setting-popup-input-password"
				   data-entity="public-link-setting-popup-input-password" placeholder="<?= Loc::getMessage('DISK_JS_EL_SETTINGS_LINK_HINT_PROTECT_BY_PASSWORD') ?>" autocomplete="nope">
			<div class="disk-external-link-setting-popup-password-show" data-entity="public-link-setting-popup-password-show"></div>
		</div>
		{{/hasPassword}}
		{{#hasPassword}}
		<div class="disk-external-link-setting-popup disk-external-link-setting-popup-password" data-entity="public-link-setting-password">
			<span class="disk-external-link-setting-password"><?= Loc::getMessage('DISK_JS_EL_SETTINGS_LINK_VALUE_PROTECT_BY_PASSWORD') ?></span>
		</div>
		{{/hasPassword}}
</script>