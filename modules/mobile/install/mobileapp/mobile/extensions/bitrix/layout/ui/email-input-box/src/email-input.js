/**
 * @module layout/ui/email-input-box/src/email-input
 */
jn.define('layout/ui/email-input-box/src/email-input', (require, exports, module) => {
	const { Color } = require('tokens');
	const { emailRegExp } = require('utils/email');
	const { debounce } = require('utils/function');

	const emailRegExpWithGlobalFlag = new RegExp(emailRegExp.source, 'g');

	class EmailInput extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.selection = {
				start: 0,
				end: 0,
			};
			this.state = {
				value: '',
			};
			this.onChangeTextDebounce = debounce(this.onChangeText, 100);
		}

		get testId()
		{
			return `${this.props.testId}-input`;
		}

		get inputPlaceholder()
		{
			return this.props.inputPlaceholder ?? '';
		}

		render()
		{
			const textInputProps = {
				style: {
					height: 54,
				},
				testId: `${this.testId}-input`,
				ref: this.#bindInputRef,
				multiline: true,
				selection: this.selection,
				value: this.state.value,
				forcedValue: this.state.value,
				placeholder: this.props.inputPlaceholder,
				onChangeText: this.onChangeTextDebounce,
				onSelectionChange: this.onSelectionChange,
			};

			if (!this.#isSamsungSmartphone())
			{
				textInputProps.keyboardType = 'email-address';
			}

			return TextInput(textInputProps);
		}

		onSelectionChange = ({ selection }) => {
			this.selection = selection;
		};

		#isSamsungSmartphone = () => {
			return device.model?.toLowerCase?.().includes('samsung');
		};

		#bindInputRef = (ref) => {
			this.inputRef = ref;
		};

		focus = () => {
			if (this.inputRef)
			{
				this.inputRef.focus();
			}
		};

		onChangeText = (newValue) => {
			const inputStringWithoutBBCodes = newValue
				.replaceAll(`[COLOR=${Color.accentMainPrimary.toHex().toLowerCase()}]`, '')
				.replaceAll('[/COLOR]', '').toLowerCase();
			const emailsFound = inputStringWithoutBBCodes.match(emailRegExpWithGlobalFlag);
			const resultString = emailsFound
				? inputStringWithoutBBCodes.replaceAll(emailRegExpWithGlobalFlag, `[COLOR=${Color.accentMainPrimary.toHex().toLowerCase()}]$1[/COLOR]`)
				: inputStringWithoutBBCodes;

			if (this.state.value === resultString)
			{
				return;
			}

			this.setState({
				value: resultString,
			}, () => {
				if (this.props.onEmailsChanged)
				{
					this.props.onEmailsChanged(emailsFound ?? []);
				}
			});
		};
	}

	module.exports = {
		EmailInput: (props) => new EmailInput(props),
	};
});
