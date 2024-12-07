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
						borderWidth: this.state.checked ? 0 : 1.6,
						width: this.style.size,
						height: this.style.size,
						alignContent: this.style.alignContent,
						justifyContent: this.style.justifyContent,
					},
					onClick: () => {
						if (!this.state.disabled && !this.props.readOnly)
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

			return `<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12.5" r="12" fill="${color}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M10.9522 16.8198C10.5639 17.1983 9.94464 17.1983 9.5563 16.8198L6.76366 14.0981C6.34512 13.6902 6.34512 13.0175 6.76366 12.6096C7.16723 12.2163 7.81074 12.2163 8.21431 12.6096L10.2542 14.5977L15.7857 9.20689C16.1893 8.81358 16.8328 8.81358 17.2363 9.20689C17.6549 9.61479 17.6549 10.2875 17.2363 10.6954L10.9522 16.8198Z" fill="white"/></svg>`;
		}
	}

	module.exports = { CheckBox };
});