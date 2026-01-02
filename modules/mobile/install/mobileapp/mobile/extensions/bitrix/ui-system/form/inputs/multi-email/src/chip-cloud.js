/**
 * @module ui-system/form/inputs/multi-email/src/chip-cloud
 */
jn.define('ui-system/form/inputs/multi-email/src/chip-cloud', (require, exports, module) => {
	const { ChipInput } = require('ui-system/form/inputs/multi-email/src/chip-input');
	const { createTestIdGenerator } = require('utils/test');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @typedef {Object} ChipCloudProps
	 * @property {string} [testId]
	 * @property {string[]} [emails]
	 * @property {boolean} [disabled]
	 * @property {string} [editChipText]
	 * @property {number} [indexToEdit]
	 * @property {function} [onChange]
	 * @property {function} [onEditStart]
	 * @property {function} [onEditChipTextChanged]
	 * @property {ChipTextInputStatusDesign} [chipDesign]

	 * @class ChipCloud
	 * @param {ChipCloudProps} props
	 */
	class ChipCloud extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'chip-cloud',
				context: this,
			});
		}

		render()
		{
			const { style } = this.props;

			return View(
				{
					testId: this.getTestId(),
					style: {
						width: '100%',
						flexDirection: 'row',
						flexWrap: 'wrap',
						alignItems: 'flex-start',
						justifyContent: 'flex-start',
						...style,
					},
				},
				...this.renderChips(),
			);
		}

		renderChips = () => {
			const { disabled, chipDesign, indexToEdit, emails } = this.props;

			return emails.map((email, index) => {
				return ChipInput({
					testId: this.getTestId(),
					email,
					disabled,
					onEditStart: this.#onChipInputEditStart,
					onTextChange: this.#onChipInputTextChange,
					onEdited: this.#onChipInputEdited,
					onRemove: this.#onChipInputRemove,
					editable: index === indexToEdit,
					chipDesign,
					index,
				});
			});
		};

		#onChipInputTextChange = (newValue) => {
			const { onEditChipTextChanged } = this.props;
			onEditChipTextChanged?.(newValue);
		};

		#onChipInputEditStart = (indexToEdit) => {
			const { onEditStart, emails, indexToEdit: prevIndexToEdit, editChipText = '' } = this.props;
			const newEmails = [...emails];
			if (prevIndexToEdit !== null && editChipText !== '')
			{
				newEmails[prevIndexToEdit] = editChipText;
			}
			onEditStart({
				editChipText: emails[indexToEdit],
				emails: newEmails,
				indexToEdit,
			});
		};

		#onChipInputEdited = (newEmails, index, toFocusNewEmailInput) => {
			const emails = this.props.emails.flatMap((email, _index) => (_index === index ? newEmails : email));
			this.props.onChange?.({
				emails,
				toFocusNewEmailInput,
				indexToEdit: null,
				editChipText: '',
			});
		};

		#onChipInputRemove = (indexToRemove) => {
			const emails = this.props.emails.filter((email, _index) => _index !== indexToRemove);
			this.props.onChange?.({
				emails,
				toFocusNewEmailInput: true,
				indexToEdit: null,
				editChipText: '',
			});
		};

		isFocused = () => {
			return this.props.indexToEdit !== null;
		};
	}

	module.exports = {
		/**
		 * @param {ChipCloudProps} props
		 * @returns {ChipCloud}
		 */
		ChipCloud: (props) => new ChipCloud(props),
	};
});
