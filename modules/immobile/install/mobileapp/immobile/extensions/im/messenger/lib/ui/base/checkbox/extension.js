/**
 * @module im/messenger/lib/ui/base/checkbox
 */
jn.define('im/messenger/lib/ui/base/checkbox', (require, exports, module) => {

	const { checkboxStyle : style } = require('im/messenger/lib/ui/base/checkbox/style');
	const { clone } = require('utils/object');

	class CheckBox extends LayoutComponent
	{
		/**
		 *
		 * @param{Object} props
		 * @param{boolean} props.checked
		 * @param{boolean} props.disabled
		 * @param{boolean} [props.readOnly]
		 * @param{Function} [props.onClick]
		 * @param{Object} [props.style]
		 * @param{number} props.style.size
		 */
		constructor(props)
		{
			super(props);

			this.state.checked = props.checked || false;
			this.state.disabled = props.disabled || false;
			this.setStyles(props.style);
		}

		componentWillReceiveProps(props)
		{
			this.state.checked = props.checked || false;
			this.state.disabled = props.disabled || false;
		}

		render()
		{
			return View(
				{
					style: {
						borderRadius: this.style.size / 2,
						borderColor: this.style.borderColor,
						borderWidth: this.state.checked ? 0 : 1,
						width: this.style.size,
						height: this.style.size,
						alignContent: this.style.alignContent,
						justifyContent: this.style.justifyContent,
					},
					onClick: () => {
						if (!this.state.disabled)
						{
							this.setState({checked: !this.state.checked});
						}
						if (typeof this.props.onClick === 'function')
						{
							this.props.onClick(this.state.checked);
						}
					}
				},
				this.state.checked
					? Image(
						{
							style: {
								width: this.style.size,
								height: this.style.size,
							},
							svg: {
								content: this.getIcon()
							},
						}
					)
					: null
			);
		}

		/**
		 *
		 * @return {boolean} switched state of the checkbox.
		 * If the "disabled" parameter is set, it returns its current state
		 */
		switch()
		{
			if (!this.state.disabled)
			{
				this.setState({checked: !this.state.checked});
			}

			return this.state.checked;
		}

		/**
		 *
		 * @param{Object} [styles]
		 * @param{number} styles.size
		 */
		setStyles(styles)
		{
			this.style = clone(style);
			if (!styles)
			{
				return;
			}

			this.style.size = styles.size || style.size;
		}

		/**
		 *
		 * @return {string}
		 */
		getIcon()
		{
			const color = !this.state.disabled ? style.icon.enable : style.icon.disable;

			return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24Z" fill="${color}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M10.8065 13.363L17.1442 7.10625L19.0621 9.03482L10.8364 17.19L10.8065 17.1599L10.7765 17.19L5.74451 12.3197L7.66244 10.3911L10.8065 13.363Z" fill="white"/>
				</svg>`;
		}
	}

	module.exports = { CheckBox };
});