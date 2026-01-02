/**
 * @module ui-system/form/inputs/multi-email
 */
jn.define('ui-system/form/inputs/multi-email', (require, exports, module) => {
	const { NewEmailInput } = require('ui-system/form/inputs/multi-email/src/new-email-input');
	const { Indent } = require('tokens');
	const { ChipTextInputStatusDesign } = require('ui-system/blocks/chips/chip-text-input');
	const { EmailCounter } = require('ui-system/form/inputs/multi-email/src/email-counter');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { ChipCloud } = require('ui-system/form/inputs/multi-email/src/chip-cloud');
	const { InputClass, InputDesign } = require('ui-system/form/inputs/input');
	const { isValidEmail } = require('utils/email');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {InputProps} MultiEmailInputProps
	 * @property {string} [testId]
	 * @property {string[]} [emails = []]
	 * @property {boolean} [showEmailsCounter=true]
	 * @property {number} [indexToEdit = null]
	 * @property {ChipTextInputStatusDesign} [chipDesign = ChipTextInputStatusDesign.DEFAULT]
	 * @property {string} [newEmailInputValue = '']
	 * @property {boolean} [focus = false]
	 * @property {number} [height = 106]
	 * @property {boolean} [disabled = false]
	 * @property {string} [placeholder]
	 * @property {function} [onChange]

	 * @class MultiEmailInput
	 * @param {MultiEmailInputProps} props
	 */
	class MultiEmailInput extends InputClass
	{
		constructor(props)
		{
			super(props);
			this.scrollViewRef = null;
			this.chipCloudRef = null;
			this.newEmailInputRef = null;
			this.getTestId = createTestIdGenerator({
				prefix: 'multi-email',
				context: this,
			});
			this.#initState(props);
		}

		componentWillReceiveProps(props) {
			this.#initState(props);
		}

		#initState = (props) => {
			this.state = {
				indexToEdit: props.indexToEdit ?? null,
				editChipText: props.editChipText ?? '',
				newEmailInputValue: props.newEmailInputValue ?? '',
				emails: props.emails ?? [],
				isFocused: props.focus ?? false,
			};
		};

		getContainerHeight()
		{
			return this.props.height ?? null;
		}

		renderRightStick()
		{
			const { showEmailsCounter = true } = this.props;

			return View(
				{
					style: {
						height: '100%',
						paddingVertical: Indent.L.toNumber(),
					},
				},
				this.renderRightStickContent(),
				this.renderEraseIcon(),
				this.renderDropdownIcon(),
				showEmailsCounter && this.renderEmailsCounter(),
			);
		}

		renderContent()
		{
			const { placeholder, height, focus, disabled, chipDesign } = this.props;
			const { emails, indexToEdit, newEmailInputValue, editChipText } = this.state;
			const scrollViewHeight = height - Indent.XL3.toNumber();

			return ScrollView(
				{
					ref: this.#bindScrollViewRef,
					style: {
						flex: 1,
						height: scrollViewHeight,
					},
				},
				View(
					{
						testId: this.getTestId('root-view'),
					},
					emails.length > 0 && ChipCloud({
						testId: this.getTestId(),
						ref: this.#bindChipCloudRef,
						emails,
						editChipText,
						disabled,
						chipDesign,
						onChange: this.#onChipCloudChange,
						onEditChipTextChanged: this.#onEditChipTextChanged,
						onEditStart: this.#onChipCloudEditStart,
						indexToEdit,
						style: {
							marginTop: Indent.XS2.toNumber(),
						},
					}),
					NewEmailInput({
						ref: this.#bindNewEmailInputRef,
						testId: this.getTestId('new-email-input'),
						placeholder,
						disabled,
						focus,
						value: newEmailInputValue,
						onNewEmails: this.#onNewEmails,
						onValueChanged: this.#onNewEmailValueChanged,
						onBlur: this.#onNewEmailBlur,
						onFocus: this.#onNewEmailFocus,
					}),
				),
			);
		}

		#onEditChipTextChanged = (editChipText) => {
			this.setState({
				editChipText,
			});
		};

		#onNewEmailValueChanged = (newEmailInputValue) => {
			this.setState({
				newEmailInputValue,
			});
		};

		#onChipCloudEditStart = ({
			editChipText,
			emails,
			indexToEdit,
		}) => {
			if (this.state.indexToEdit !== null)
			{
				// need to prevent keyboard hiding
				this.focusNewEmailInput();
				// settimeout need to prevent android bug with wrong focus
				setTimeout(() => {
					this.setState({
						editChipText,
						emails,
						indexToEdit,
					}, () => this.updateFocus());
				}, 100);

				return;
			}

			this.setState({
				editChipText,
				emails,
				indexToEdit,
			}, () => this.updateFocus());
		};

		#onNewEmailBlur = () => {
			this.updateFocus();
		};

		#onNewEmailFocus = () => {
			this.updateFocus();
		};

		#onNewEmails = (newEmails) => {
			this.setState({
				emails: [...this.state.emails, ...newEmails],
				newEmailInputValue: '',
				indexToEdit: null,
			}, () => {
				const { newEmailInputValue, emails } = this.state;
				this.focusNewEmailInput();
				this.#scrollToNewEmailInput();
				this.props.onChange?.({
					allEmails: emails,
					validEmails: this.#getValidEmails(),
					newEmailInputValue,
				});
			});
		};

		#getValidEmails = () => {
			return this.state.emails.filter((email) => isValidEmail(email));
		};

		#onChipCloudChange = ({
			emails,
			toFocusNewEmailInput,
			indexToEdit,
			editChipText,
		}) => {
			const { newEmailInputValue } = this.state;
			if (toFocusNewEmailInput)
			{
				this.focusNewEmailInput();
				this.#scrollToNewEmailInput();
			}
			this.setState({
				emails,
				indexToEdit,
				editChipText,
			}, () => {
				this.props.onChange?.({
					allEmails: emails,
					validEmails: this.#getValidEmails(),
					newEmailInputValue,
				});
				this.updateFocus();
			});
		};

		updateFocus = () => {
			setTimeout(() => {
				const isFocusedNewValue = this.chipCloudRef?.isFocused() || this.newEmailInputRef?.isFocused();
				if (this.state.isFocused !== isFocusedNewValue)
				{
					this.setState({
						isFocused: isFocusedNewValue,
					});
				}
			}, 100);
		};

		#isFocused()
		{
			const { isFocused } = this.state;

			return Boolean(isFocused);
		}

		getBorderStyle({ filled } = {})
		{
			if (!this.isStroke())
			{
				return {};
			}

			const { borderRadius } = this.getSize().getContainer();

			const style = {
				borderWidth: 1,
				borderRadius: borderRadius.toNumber(),
			};

			if (filled)
			{
				const { borderColor, borderColorFocused } = this.getDesignStyle();

				if (this.isError())
				{
					style.borderColor = this.getErrorColor().toHex();
				}
				else if (this.#isFocused() && borderColorFocused)
				{
					style.borderColor = borderColorFocused.toHex();
				}
				else
				{
					style.borderColor = borderColor?.toHex();
				}
			}

			return style;
		}

		#bindChipCloudRef = (ref) => {
			this.chipCloudRef = ref;
		};

		#bindScrollViewRef = (ref) => {
			this.scrollViewRef = ref;
		};

		renderEmailsCounter()
		{
			const value = this.state.emails.length;

			return EmailCounter({
				value,
			});
		}

		#scrollToNewEmailInput(animated = true, timeout = 200)
		{
			setTimeout(() => {
				if (this.scrollViewRef && this.newEmailInputRef)
				{
					const { y } = this.scrollViewRef.getPosition(this.newEmailInputRef?.getInputRef());
					const positionY = y - 20;
					this.scrollViewRef.scrollTo({ y: positionY, animated });
				}
			}, timeout);
		}

		#bindNewEmailInputRef = (ref) => {
			this.newEmailInputRef = ref;
		};

		focusNewEmailInput = () => {
			this.newEmailInputRef?.focus();
		};

		isEditingSomeEmail = () => {
			return this.state.indexToEdit !== null;
		};
	}

	MultiEmailInput.defaultProps = {
		...InputClass.defaultProps,
		emails: [],
		height: 106,
		indexToEdit: null,
		showEmailsCounter: true,
		chipDesign: ChipTextInputStatusDesign.DEFAULT,
		newEmailInputValue: '',
		focus: false,
		disabled: false,
	};

	MultiEmailInput.propTypes = {
		...InputClass.propTypes,
		emails: PropTypes.arrayOf(PropTypes.string),
		height: PropTypes.number,
		indexToEdit: PropTypes.oneOfType([PropTypes.number, PropTypes.oneOf([null])]),
		showEmailsCounter: PropTypes.bool,
		chipDesign: PropTypes.instanceOf(ChipTextInputStatusDesign),
		newEmailInputValue: PropTypes.string,
		focus: PropTypes.bool,
		disabled: PropTypes.bool,
		onChange: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {MultiEmailInputProps} props
		 * @returns {MultiEmailInput}
		 */
		MultiEmailInput: (props) => new MultiEmailInput(props),
		ChipDesign: ChipTextInputStatusDesign,
		MultiEmailInputDesign: InputDesign,
	};
});
