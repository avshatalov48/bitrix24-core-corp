/**
 * @module stafftrack/ui/text-input-with-max-height
 */
jn.define('stafftrack/ui/text-input-with-max-height', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { TextInput } = require('ui-system/typography/text-input');

	/**
	 * @class TextInputWithMaxHeight
	 */
	class TextInputWithMaxHeight extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				height: 0,
			};

			this.onChangeText = (value) => {
				this.props.onChangeText(value);
				setTimeout(() => this.setState({ height: 0 }), 40);
			};
		}

		render()
		{
			return TextInput({
				...this.props,
				onChangeText: this.onChangeText,
				style: {
					minHeight: this.state.height ?? 0,
					maxHeight: this.props.style.maxHeight,
					...this.props.style,
				},
				onLayout: ({ height }) => this.setState({ height }),
			});
		}
	}

	module.exports = { TextInputWithMaxHeight };
});
