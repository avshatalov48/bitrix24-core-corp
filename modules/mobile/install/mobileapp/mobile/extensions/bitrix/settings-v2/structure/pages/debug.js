/**
 * @module settings-v2/structure/pages/debug
 */
jn.define('settings-v2/structure/pages/debug', (require, exports, module) => {
	const { SettingsPageId } = require('settings-v2/const');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const {
		createButton,
		createSection,
	} = require('settings-v2/structure/src/item-create-helper');
	const { NativeDebugService } = require('settings-v2/services/native');

	/** @type SettingPage */
	const DebugPage = {
		id: SettingsPageId.DEBUG,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_ROOT_DEBUG'),
		items: [
			createSection({
				id: 'debug-section',
				items: [
					createButton({
						id: 'debug-calls-log',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_DEBUG_CALLS_LOG'),
						icon: Icon.CHEVRON_TO_THE_RIGHT,
						onClick: () => {
							NativeDebugService.sendCallLogs();
						},
					}),
					createButton({
						id: 'debug-system-log',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_DEBUG_SYSTEM_LOG'),
						icon: Icon.CHEVRON_TO_THE_RIGHT,
						onClick: () => {
							NativeDebugService.sendSystemLogs();
						},
					}),
				],
			}),
		],
	};

	module.exports = {
		DebugPage,
	};
});
