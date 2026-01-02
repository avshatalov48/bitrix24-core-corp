/**
 * @module im/messenger/controller/vote/creation/text-item
 */
jn.define('im/messenger/controller/vote/creation/text-item', (require, exports, module) => {
	const { Icon } = require('ui-system/blocks/icon');
	const { TextAreaInput, InputSize, InputDesign } = require('ui-system/form/inputs/textarea');
	const { Color } = require('tokens');

	/**
	 * @class TextItem
	 */
	class TextItem extends LayoutComponent
	{
		/**
		 * @typedef {object} TextItemProps
		 * @property {string} [text = '']
		 * @property {string} [placeholder = '']
		 * @property {number} [maxLength]
		 * @property {boolean} [canRemove = false]
		 * @property {string} testId
		 * @property {function} [onRef]
		 * @property {function} [onFocus]
		 * @property {function} [onSubmit]
		 * @property {function} [onCursorPositionChange]
		 * @property {function} [onChange]
		 * @property {function} [onErase]
		 * @property {function} [onRemove]
		 *
		 * @param {TextItemProps} props
		 */
		constructor(props)
		{
			super(props);

			this.onRef = this.onRef.bind(this);
			this.onFocus = this.onFocus.bind(this);
			this.onSubmit = this.onSubmit.bind(this);
			this.onCursorPositionChange = this.onCursorPositionChange.bind(this);
			this.onRemove = this.onRemove.bind(this);
			this.onErase = this.onErase.bind(this);
			this.onChange = this.onChange.bind(this);

			this.state = {
				text: this.props.text,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state.text = props.text;
		}

		render()
		{
			const { maxLength, placeholder, canRemove, testId } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					testId: `${testId}-container`,
				},
				TextAreaInput({
					style: {
						flex: 1,
					},
					maxLength,
					placeholder,
					value: this.state.text,
					showCharacterCount: false,
					height: null,
					size: InputSize.L,
					design: InputDesign.GREY,
					enableLineBreak: false,
					erase: !canRemove,
					eraseIconColor: Color.base5,
					rightStickContent: Icon.TRASHCAN,
					rightStickContentColor: Color.base5,
					testId: `${testId}-field`,
					ref: this.onRef,
					onFocus: this.onFocus,
					onSubmit: this.onSubmit,
					onCursorPositionChange: this.onCursorPositionChange,
					onChange: this.onChange,
					onErase: this.onErase,
					onClickRightStickContent: this.onRemove,
				}),
			);
		}

		getText()
		{
			return this.state.text;
		}

		onRef(ref)
		{
			this.props.onRef?.(ref);
		}

		onFocus()
		{
			this.props.onFocus?.();
		}

		onSubmit()
		{
			this.props.onSubmit?.();
		}

		onCursorPositionChange({ y })
		{
			this.props.onCursorPositionChange?.(y);
		}

		onRemove()
		{
			this.props.onRemove?.();
		}

		onErase()
		{
			this.setState({ text: '' }, () => this.props.onErase?.());
		}

		onChange(text)
		{
			this.setState({ text }, () => this.props.onChange?.());
		}
	}

	TextItem.defaultProps = {
		text: '',
		placeholder: '',
		canRemove: false,
	};

	TextItem.propTypes = {
		text: PropTypes.string,
		placeholder: PropTypes.string,
		maxLength: PropTypes.number,
		canRemove: PropTypes.bool,
		testId: PropTypes.string.isRequired,
		onRef: PropTypes.func,
		onFocus: PropTypes.func,
		onSubmit: PropTypes.func,
		onCursorPositionChange: PropTypes.func,
		onChange: PropTypes.func,
		onErase: PropTypes.func,
		onRemove: PropTypes.func,
	};

	module.exports = {
		/** @param {TextItemProps} props */
		TextItem: (props) => new TextItem(props),
	};
});
