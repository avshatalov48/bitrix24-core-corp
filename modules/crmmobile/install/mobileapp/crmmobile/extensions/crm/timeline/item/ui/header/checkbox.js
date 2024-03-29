/**
 * @module crm/timeline/item/ui/header/checkbox
 */
jn.define('crm/timeline/item/ui/header/checkbox', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class Checkbox extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = { checked: false };
		}

		componentWillReceiveProps(props)
		{
			this.state.checked = props.checked;
		}

		/**
		 * @public
		 */
		uncheck()
		{
			this.setState({ checked: false });
		}

		render()
		{
			return View(
				{
					testId: this.props.testId,
					style: {
						paddingVertical: 10,
						paddingHorizontal: 13,
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick: () => {
						if (this.props.isReadonly)
						{
							return;
						}

						this.setState({ checked: !this.state.checked });
						if (this.props.onClick)
						{
							this.props.onClick();
						}
					},
				},
				Image({
					svg: {
						content: this.state.checked ? SvgIcons.Checked : SvgIcons.NonChecked,
					},
					style: {
						width: 14,
						height: 14,
					},
				}),
			);
		}
	}

	const SvgIcons = {
		Checked: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1 3C1 1.89543 1.89543 1 3 1H13C14.1046 1 15 1.89543 15 3V13C15 14.1046 14.1046 15 13 15H3C1.89543 15 1 14.1046 1 13V3Z" fill="${AppTheme.colors.accentExtraDarkblue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.12265 11.4866C6.91238 11.4907 6.70142 11.4144 6.53872 11.2573L4.1461 8.94673C3.8247 8.63635 3.81361 8.11048 4.13116 7.78164C4.45093 7.45052 4.97026 7.44129 5.29606 7.75591L7.08778 9.48615L10.9213 5.51643C11.2419 5.18448 11.7617 5.17968 12.0905 5.49723C12.4216 5.817 12.429 6.33824 12.1121 6.66639L7.70243 11.2327C7.54304 11.3978 7.33439 11.482 7.12446 11.4859L7.12265 11.4866Z" fill="${AppTheme.colors.baseWhiteFixed}"/></svg>`,
		NonChecked: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.5" y="0.5" width="13" height="13" rx="1.5" fill="white" stroke="${AppTheme.colors.base4}"/></svg>`,
	};

	module.exports = { Checkbox };
});
