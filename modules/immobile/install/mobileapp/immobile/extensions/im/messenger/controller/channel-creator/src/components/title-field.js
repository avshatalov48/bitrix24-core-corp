/**
 * @module im/messenger/controller/channel-creator/components/title-field
 */
jn.define('im/messenger/controller/channel-creator/components/title-field', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { clearTextButton } = require('im/messenger/controller/channel-creator/components/clear-text-button');
	/**
	 * @class TitleField
	 * @typedef {LayoutComponent<TitleFieldProps, TitleFieldState>} TitleField
	 */
	class TitleField extends LayoutComponent
	{
		/**
		 * @param {TitleFieldProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state.isTextEmpty = true;
			this.state.isFocused = false;

			this.textRef = null;
			this.badge = props.customBadge ?? Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_TITLE_FIELD_BADGE');
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
				// View(
				// 	{},
				// 	Text({
				// 		style: {
				// 			color: Theme.colors.base4,
				// 			fontSize: 14,
				// 		},
				// 		text: this.badge,
				// 		numberOfLines: 1,
				// 	}),
				// ),
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					TextField({
						placeholder: this.props.placeholder,
						placeholderTextColor: Theme.colors.base5,
						value: this.props.value ?? '',
						multiline: false,
						style: {
							flexGrow: 2,
							color: Theme.colors.base1,
							fontSize: 17,
							marginRight: 5,
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
						onSubmitEditing: () => this.textRef.blur(),
						ref: (ref) => this.textRef = ref,
					}),
					View(
						{
							style: {
								justifyContent: 'flex-end',
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

	module.exports = { TitleField };
});
