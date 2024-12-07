/**
 * @module layout/ui/search-bar/preset
 */
jn.define('layout/ui/search-bar/preset', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseItem } = require('layout/ui/search-bar/base-item');

	/**
	 * @class Preset
	 * @typedef {LayoutComponent<SearchBarPresetProps, {}>}
	 */
	class Preset extends BaseItem
	{
		constructor(props)
		{
			super(props);

			this.preset = {
				id: props.id,
				name: props.name,
			};
		}

		getSearchButtonBackgroundColor()
		{
			return (this.isDefault() ? null : Color.accentMainPrimary.toHex());
		}

		getOnClickParams()
		{
			const params = super.getOnClickParams();
			params.preset = this.preset;
			params.presetId = this.preset.id;

			return params;
		}
	}

	module.exports = { Preset };
});
