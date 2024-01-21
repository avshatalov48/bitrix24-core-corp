/**
 * @module layout/ui/search-bar/preset
 */
jn.define('layout/ui/search-bar/preset', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseItem } = require('layout/ui/search-bar/base-item');
	const { CloseIcon, Title } = require('layout/ui/search-bar/ui');

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

		renderContent()
		{
			return [
				Title({
					text: this.props.name,
					disabled: this.isDisabled(),
				}),
				this.props.active && CloseIcon(),
			];
		}

		getSearchButtonBackgroundColor()
		{
			return (this.isDefault() ? null : AppTheme.colors.accentBrandBlue);
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
