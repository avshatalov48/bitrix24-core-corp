/**
 * @module imconnector/connector/telegram/layout-components/token-input
 */
jn.define('imconnector/connector/telegram/layout-components/token-input', (require, exports, module) => {
	const { Loc } = require('loc');
	class TokenInput extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state.borderColor = colors.default;
			this.state.maxWidth = null;

			this.initial = true;
		}

		render()
		{
			return View(
				{
					style: {
						borderRadius: 4,
						borderColor: this.state.borderColor,
						borderWidth: 1,
						paddingHorizontal: 12,
						paddingVertical: 8.5,
					},
				},
				TextField({
					style: {
						fontSize: 16,
						maxWidth: this.state.maxWidth,
					},
					placeholder: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_TOKEN_INPUT_PLACEHOLDER'),
					placeholderTextColor: AppTheme.colors.base4,
					onFocus: () => {
						this.setState({ borderColor: colors.focused });
					},
					onBlur: () => {
						this.setState({ borderColor: colors.default });
					},
					onSubmitEditing: (text) => {
						this.props.onSubmitEditing(text.text);
						this.textFieldRef.blur();
						this.setState({ borderColor: colors.default });
					},
					onChangeText: (text) => this.props.onChangeText(text),
					ref: (ref) => this.textFieldRef = ref,
					value: this.props.token,
					onLayout: (props) => {
						if (this.initial)
						{
							this.state.maxWidth = props.width;
							this.initial = false;
						}
					},
				}),
			);
		}
	}

	const colors = {
		default: AppTheme.colors.accentBrandBlue,
		focused: AppTheme.colors.accentMainPrimaryalt,
		error: AppTheme.colors.accentSoftElementRed1,
	};

	module.exports = { TokenInput: (props) => new TokenInput(props), TokenInputColor: colors };
});

