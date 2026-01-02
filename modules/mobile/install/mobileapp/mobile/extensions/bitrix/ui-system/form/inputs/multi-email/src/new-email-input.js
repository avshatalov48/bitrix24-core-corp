/**
 * @module ui-system/form/inputs/multi-email/src/new-email-input
 */
jn.define('ui-system/form/inputs/multi-email/src/new-email-input', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');
	const { TextInput } = require('ui-system/typography/text-input');
	const { TextField } = require('ui-system/typography/text-field');

	/**
	 * @typedef {Object} NewEmailInputProps
	 * @property {string} [testId]
	 * @property {string} [value]
	 * @property {string} [placeholder]
	 * @property {boolean} [disabled]
	 * @property {boolean} [focus]
	 * @property {function} [onFocus]
	 * @property {function} [onBlur]
	 * @property {function} [onNewEmails]
	 * @property {function} [onValueChanged]

	 * @class NewEmailInput
	 * @param {NewEmailInputProps} props
	 */
	class NewEmailInput extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'new-email-input',
				context: this,
			});
		}

		render()
		{
			const isAndroid = Application.getPlatform() === 'android';

			return View(
				{
					style: {
						width: '100%',
						paddingVertical: Indent.XS2.toNumber(),
					},
				},
				isAndroid && TextInput(this.getInputProps()),
				!isAndroid && TextField(this.getInputProps()),
			);
		}

		getInputProps()
		{
			const { disabled, placeholder, value } = this.props;

			const isAndroid = Application.getPlatform() === 'android';
			const inputProps = {
				testId: this.getTestId('input'),
				ref: this.#bindInputRef,
				value,
				forcedValue: value,
				placeholder,
				enable: !disabled,
				keyboardType: 'email-address',
				onChangeText: this.#onChangeText,
				onSubmitEditing: this.#onSubmitEditing,
				onBlur: this.#onBlur,
				onFocus: this.#onFocus,
				style: {
					width: '100%',
				},
			};

			if (isAndroid)
			{
				inputProps.multiline = false;
			}

			return inputProps;
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			const renderDepsProps = ['disabled', 'placeholder', 'focus'];

			return renderDepsProps.some((prop) => this.props[prop] !== nextProps[prop])
				|| this.inputRef?.getTextValue() !== nextProps.value;
		}

		#onBlur = () => {
			const { onBlur } = this.props;

			onBlur?.();
		};

		#onFocus = () => {
			const { onFocus } = this.props;

			onFocus?.();
		};

		#onSubmitEditing = ({ text }) => {
			const { onNewEmails } = this.props;
			const emails = text.toLowerCase().trim().split(' ').filter((email) => email !== '');

			if (emails.length > 0)
			{
				onNewEmails(emails ?? []);
			}
		};

		#bindInputRef = (ref) => {
			const { focus } = this.props;
			this.inputRef = ref;
			if (focus)
			{
				this.focus();
			}
		};

		focus = () => {
			this.inputRef?.focus();
		};

		getInputRef = () => {
			return this.inputRef;
		};

		#onChangeText = (newValue) => {
			const { onNewEmails, onValueChanged } = this.props;
			const preparedValue = newValue.toLowerCase();
			onValueChanged?.(preparedValue);

			const emails = preparedValue.trim().split(' ').filter((email) => email !== '');

			if (emails.length > 0 && preparedValue.includes(' '))
			{
				onNewEmails(emails ?? []);
			}
		};

		isFocused = () => {
			return this.inputRef.isFocused();
		};
	}

	module.exports = {
		/**
		 * @param {NewEmailInputProps} props
		 * @returns {NewEmailInput}
		 */
		NewEmailInput: (props) => new NewEmailInput(props),
	};
});
