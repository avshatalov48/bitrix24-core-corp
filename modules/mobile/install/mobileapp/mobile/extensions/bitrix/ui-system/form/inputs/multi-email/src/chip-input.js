/**
 * @module ui-system/form/inputs/multi-email/src/chip-input
 */
jn.define('ui-system/form/inputs/multi-email/src/chip-input', (require, exports, module) => {
	const { ChipTextInput, ChipTextInputStatusDesign } = require('ui-system/blocks/chips/chip-text-input');
	const { Indent } = require('tokens');
	const { Icon } = require('assets/icons');
	const { Loc } = require('loc');
	const { UIMenu } = require('layout/ui/menu');

	const { emailRegExp } = require('utils/email');
	const emailRegExpWithGlobalFlag = new RegExp(emailRegExp.source, 'g');
	const { createTestIdGenerator } = require('utils/test');
	const { PureComponent } = require('layout/pure-component');
	const { TextField } = require('ui-system/typography/text-field');

	/**
	 * @typedef {Object} ChipInputProps
	 * @property {string} [testId]
	 * @property {string} [email]
	 * @property {boolean} [editable]
	 * @property {boolean} [disabled]
	 * @property {ChipTextInputStatusDesign} [chipDesign]
	 * @property {function} [onRemove]
	 * @property {function} [onTextChange]
	 * @property {function} [onEdited]
	 * @property {function} [onEditStart]
	 * @property {number} [index]

	 * @class ChipInput
	 * @param {ChipInputProps} props
	 */
	class ChipInput extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.rootViewRef = null;
			this.inputRef = null;
			this.menu = null;
			this.getTestId = createTestIdGenerator({
				prefix: 'chip',
				context: this,
			});
		}

		render()
		{
			const { editable, email } = this.props;

			return View(
				{
					ref: this.#bindRef,
					testId: this.getTestId(),
					style: {
						marginRight: Indent.S.toNumber(),
						marginBottom: Indent.S.toNumber(),
						maxWidth: '100%',
					},
				},
				editable && this.#renderEditInput(),
				!editable && ChipTextInput({
					testId: this.getTestId(),
					text: email,
					dropdown: true,
					design: this.#getChipDesign(),
					onClick: this.#onClick,
					style: {
						flexGrow: 1,
					},
				}),
			);
		}

		#getChipDesign = () => {
			const { chipDesign, email } = this.props;
			const isValid = this.#isValidEmail(email);

			return isValid
				? (chipDesign ?? ChipTextInputStatusDesign.DEFAULT)
				: ChipTextInputStatusDesign.ERROR;
		};

		#renderEditInput = () => {
			const { email } = this.props;

			return View(
				{
					style: {
						height: 24,
						maxWidth: '100%',
						paddingVertical: Indent.XS2.toNumber(),
						paddingLeft: Indent.L.toNumber(),
						paddingRight: 26,
					},
				},
				TextField({
					testId: this.getTestId('edit-input'),
					ref: this.#bindInputRef,
					adjustTextWidth: true,
					value: email,
					keyboardType: 'email-address',
					onChangeText: this.#onEditInputChangeText,
					onBlur: this.#onEditInputBlur,
					onSubmitEditing: this.#onSubmitEditing,
					style: {
						maxWidth: '100%',
					},
					onContentSizeChange: (width, height) => {
						// todo: remove callback after native fix
					},
				}),
			);
		};

		#onSubmitEditing = ({ text }) => {
			const { onEdited, index } = this.props;

			const emails = text.toLowerCase().trim().split(' ').filter((email) => email !== '');

			if (emails.length > 0)
			{
				onEdited(emails, index, true);
			}
		};

		#onEditInputChangeText = (newValue) => {
			const { onRemove, onTextChange, onEdited, index } = this.props;

			const preparedValue = newValue.toLowerCase().trim();
			if (preparedValue === '')
			{
				onRemove?.(index);

				return;
			}

			onTextChange?.(preparedValue);
			const emailsFound = preparedValue.split(' ').filter((email) => email !== '');
			if (emailsFound && emailsFound.length > 1)
			{
				onEdited(emailsFound, index, true);

				return;
			}

			if (newValue.endsWith(' '))
			{
				onEdited([preparedValue], index, true);
			}
		};

		#isValidEmail = (text) => {
			const emailsFound = text?.match?.(emailRegExpWithGlobalFlag);

			return emailsFound && emailsFound.length === 1;
		};

		#onEditInputBlur = () => {
			const { onEdited, index, editable } = this.props;
			const value = this.inputRef?.getTextValue() ?? '';
			if (editable && value !== '')
			{
				onEdited([value], index, false);
			}
		};

		#bindInputRef = (ref) => {
			this.inputRef = ref;
			if (this.props.editable)
			{
				this.inputRef?.focus?.();
			}
		};

		#bindRef = (ref) => {
			this.rootViewRef = ref;
		};

		#getUiMenuItems = () => {
			return [{
				id: 'edit',
				testId: this.getTestId('menu-item'),
				title: Loc.getMessage('MULTI_EMAIL_EDIT_MENU_ITEM'),
				iconName: Icon.EDIT,
				onItemSelected: this.#onItemSelected,
			},
			{
				id: 'remove',
				testId: this.getTestId('menu-item'),
				isDestructive: true,
				title: Loc.getMessage('MULTI_EMAIL_REMOVE_MENU_ITEM'),
				iconName: Icon.TRASHCAN,
				onItemSelected: this.#onItemSelected,
			}];
		};

		#onItemSelected = (event, item) => {
			const { onEditStart, onRemove, index } = this.props;
			if (item.id === 'edit')
			{
				onEditStart?.(index);

				return;
			}

			onRemove?.(index);
		};

		#onClick = () => {
			const { disabled = false } = this.props;
			if (!disabled)
			{
				this.menu = new UIMenu(this.#getUiMenuItems());
				this.menu.show({
					target: this.rootViewRef,
				});
			}
		};
	}

	module.exports = {
		/**
		 * @param {ChipInputProps} props
		 * @returns {ChipInput}
		 */
		ChipInput: (props) => new ChipInput(props),
	};
});
