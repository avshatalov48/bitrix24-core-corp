/**
 * @module tab-presets-new/utils
 */
jn.define('tab-presets-new/utils', (require, exports, module) => {
	const { Icon } = require('ui-system/blocks/icon');
	const { downloadImages, makeLibraryImagePath } = require('asset-manager');
	const { RequestExecutor } = require('rest');

	const icons = {
		chevron: Icon.CHEVRON_TO_THE_RIGHT,
		task: Icon.CIRCLE_CHECK,
		chat: Icon.MESSAGE,
		crm: Icon.CRM,
		menu: Icon.APPS,
		terminal: Icon.PAYMENT_TERMINAL,
		catalog_store: Icon.INVENTORY_MANAGEMENT,
		projects: Icon.FOLDER,
		calendar: Icon.CALENDAR,
		stream: Icon.NEWSFEED,
		crm_custom_section: Icon.ACTIVITY,
		calendar_with_slots: Icon.CALENDAR_WITH_SLOTS,
		drag: Icon.DRAG,
		bizproc: Icon.BUSINESS_PROCESS,
		file: Icon.FILE,
	};

	const presetInfoImagePath = {
		task: makeLibraryImagePath('project-management.svg', 'graphic'),
		stream: makeLibraryImagePath('company-feed.svg', 'graphic'),
		crm: makeLibraryImagePath('sales.svg', 'graphic'),
		collaboration: makeLibraryImagePath('teamwork.svg', 'graphic'),
		team_communication: makeLibraryImagePath('connection.svg', 'graphic'),
		terminal: makeLibraryImagePath('terminal.svg', 'graphic'),
	};

	void downloadImages(Object.values(presetInfoImagePath));

	const TabPresetsNewUtils = {
		cacheId: () => `tab.settings.user.${env.userId}`,
		getPresetGetDataRequestExecutor: () => {
			return new RequestExecutor('mobile.tabs.getdata').setCacheId(TabPresetsNewUtils.cacheId());
		},
		getSortedPresets: (list, current) => {
			const result = {};
			const keys = Object.keys(list).filter((preset) => preset !== current);

			if (list[current])
			{
				keys.unshift(current);
			}
			keys.forEach((preset) => {
				result[preset] = list[preset];
			});

			return result;
		},
		setCurrentPreset: (name) => {
			return new Promise((resolve, reject) => {
				void new RequestExecutor('mobile.tabs.setpreset', { name })
					.setHandler((result, more, error) => {
						if (result && !error)
						{
							resolve(result);
						}
						else
						{
							reject(error);
						}
					})
					.call(false)
				;
			});
		},
		changeCurrentPreset: (presetId, preset) => {
			Application.storage.updateObject(
				TabPresetsNewUtils.cacheId(),
				{},
				(saved) => {
					if (saved.presets)
					{
						return {
							...saved,
							presets: {
								...saved.presets,
								current: presetId,
								list: {
									...saved.presets.list,
									...(preset ? { [presetId]: preset } : {}),
								},
							},
						};
					}

					return saved;
				},
			);
			BX.onCustomEvent('onPresetChanged', [presetId]);
		},
		setUserConfig: (config) => {
			return new RequestExecutor('mobile.tabs.setconfig', { config }).call(false);
		},
		getIcon: (code) => {
			const list = ['crm_custom_section'];
			const modifiedCode = list.find((id) => code.startsWith(id)) ?? code;

			return icons[modifiedCode] ?? '';
		},
		getPresetInfoImagePath: (presetId) => (presetInfoImagePath[presetId] ?? ''),
	};

	module.exports = TabPresetsNewUtils;
});
