/**
 * @module im/messenger/controller/channel-creator/components/description-field
 */
jn.define('im/messenger/controller/channel-creator/components/description-field', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { clearTextButton } = require('im/messenger/controller/channel-creator/components/clear-text-button');

	/**
	 * @class DescriptionField
	 * @typedef {LayoutComponent<DescriptionFieldProps, DescriptionFieldState>} DescriptionField
	 */
	class DescriptionField extends LayoutComponent
	{
		/**
		 * @param {DescriptionFieldProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state.isTextEmpty = true;
			this.state.isFocused = false;

			this.textRef = null;
			this.badge = props.badge;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 2,
						borderBottomWidth: 1,
						paddingBottom: 3,
						borderBottomColor: this.state.isFocused
							? Theme.colors.accentMainPrimary
							: Theme.colors.bgSeparatorSecondary,
						justifyContent: 'center',
					},
				},
				View(
					{
						style: {
							marginBottom: 8,
						},
					},
					Text({
						style: {
							color: Theme.colors.base4,
							fontSize: 14,
						},
						text: this.badge,
						numberOfLines: 1,
					}),
				),
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					TextInput({
						ellipsize: 'end',
						placeholder: this.props.placeholder,
						placeholderTextColor: Theme.colors.base5,
						value: this.props.value ?? '',
						multiline: true,
						enableKeyboardHide: true,
						style: {
							flexGrow: 2,
							color: Theme.colors.base1,
							fontSize: 17,
							marginRight: 5,
							flex: 1,
						},
						onChangeText: (text) => {
							if (text !== '' && this.state.isTextEmpty)
							{
								this.setState({ isTextEmpty: false });
							}

							if (text === '' && !this.state.isTextEmpty)
							{
								this.setState({ isTextEmpty: true });
							}

							this.props.onChange(text);
						},
						onFocus: () => {
							this.setState({ isFocused: true });
						},
						onBlur: () => {
							this.setState({ isFocused: false });
						},
						ref: (ref) => this.textRef = ref,
					}),
					View(
						{
							style: {
								justifyContent: 'flex-end',
								alignSelf: 'flex-start',
							},
						},
						clearTextButton({
							isVisible: !this.state.isTextEmpty,
							onClick: () => {
								this.textRef.clear();
							},
						}),
					),
				),
			);
		}
	}

	module.exports = { DescriptionField };
});
