/**
 * @module tab.presets/utils
 */
/**
 * @bxjs_lang_path ../extension.php
 */
jn.define('tab.presets/utils', (require, exports, module) => {
	const TabPresetUtils = {
		presetLoader: () => new RequestExecutor('mobile.tabs.getdata', {}).setCacheId(TabPresetUtils.cacheId()),
		cacheId: () => `tab.settings.user.${env.userId}`,
		setCurrentPreset: (name) => {
			return new Promise((resolve, reject) => {
				(new RequestExecutor('mobile.tabs.setpreset', { name }))
					.setHandler((result, more, error) => {
						console.error(result, more, error);
						if (result && !error)
						{
							resolve(result);
						}
						else
						{
							reject({ message: BX.message('TAB_PRESET_APPLY_ERROR'), object: error });
						}
					})
					.call(false);
			});
		},
		setUserConfig: (config) => {
			return new RequestExecutor('mobile.tabs.setconfig', { config }).call(false);
		},
		getSortedPresets: (list, current) => {
			const keys = Object.keys(list).filter((preset) => preset !== current);
			const result = {};
			if (list[current])
			{
				keys.unshift(current);
			}
			keys.forEach((preset) => result[preset] = list[preset]);

			return result;
		},
		changeCurrentPreset: (name) => {
			Application.storage.updateObject(TabPresetUtils.cacheId(), {}, (saved) => {
				if (saved.presets)
				{
					saved.presets.current = name;
				}

				return saved;
			});

			BX.onCustomEvent('onPresetChanged', [name]);
		},
	};

	module.exports = TabPresetUtils;
});
