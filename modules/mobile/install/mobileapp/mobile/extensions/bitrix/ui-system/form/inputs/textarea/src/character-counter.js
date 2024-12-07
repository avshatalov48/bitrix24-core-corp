/**
 * @module ui-system/form/inputs/textarea/src/character-counter
 */
jn.define('ui-system/form/inputs/textarea/src/character-counter', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { refSubstitution } = require('utils/function');
	const { Text7 } = require('ui-system/typography/text');
	const { PureComponent } = require('layout/pure-component');
	const { InputVisualDecorator } = require('ui-system/form/inputs/input');

	/**
	 * @class CharacterCounter
	 */
	class CharacterCounter extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.initState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.initState(nextProps);
		}

		initState(props)
		{
			this.state = {
				value: props.value,
				characterCount: this.getCharacterCount(props.value),
			};
		}

		render()
		{
			const { value } = this.state;

			return View(
				{
					style: {
						position: 'relative',
					},
				},
				InputVisualDecorator(
					{
						...this.props,
						value,
						onChange: this.handleOnChange,
					},
				),
				this.renderCharacterCounter(),
			);
		}

		handleOnChange = (value) => {
			const { onChange } = this.props;

			this.setValue(value, onChange);
		};

		setValue = (value, callback) => {
			this.setState(
				{
					value,
					characterCount: this.getCharacterCount(value),
				},
				() => {
					callback?.(value);
				},
			);
		};

		renderCharacterCounter()
		{
			const { characterCount } = this.state;

			return View(
				{
					style: {
						position: 'absolute',
						bottom: Indent.XL.toNumber(),
						right: Indent.XL.toNumber(),
					},
				},
				Text7({
					text: String(characterCount),
					color: Color.base3,
				}),
			);
		}

		getCharacterCount(value)
		{
			if (!value)
			{
				return 0;
			}

			return value?.length;
		}
	}

	module.exports = {
		CharacterCounter: refSubstitution(CharacterCounter),
	};
});
