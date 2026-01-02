/**
 * @module im/messenger/lib/ui/base/checkbox
 */
jn.define('im/messenger/lib/ui/base/checkbox', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { checkboxStyle: style } = require('im/messenger/lib/ui/base/checkbox/style');
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
						borderWidth: this.state.checked ? 0 : 1.6,
						width: this.style.size,
						height: this.style.size,
						alignContent: this.style.alignContent,
						justifyContent: this.style.justifyContent,
						backgroundColor: this.#prepareBackgroundColor(),
					},
					onClick: () => {
						if (!this.state.disabled && !this.props.readOnly)
						{
							this.setState({ checked: !this.state.checked });
						}

						if (typeof this.props.onClick === 'function')
						{
							this.props.onClick(this.state.checked);
						}
					},
				},
				this.state.checked
					? Image(
						{
							style: {
								width: this.style.size,
								height: this.style.size,
							},
							tintColor: style.icon.color,
							named: this.getIconName(),
						},
					)
					: null,
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
				this.setState({ checked: !this.state.checked });
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
		getIconName()
		{
			return Icon.CHECK_SIZE_S.getIconName();
		}

		/**
		 * @returns {string}
		 */
		#prepareBackgroundColor() {
			const colors = this.state.checked ? style.backgroundColor.checked : style.backgroundColor.unchecked;

			return this.state.disabled ? colors.disabled : colors.enabled;
		}
	}

	module.exports = { CheckBox };
});
