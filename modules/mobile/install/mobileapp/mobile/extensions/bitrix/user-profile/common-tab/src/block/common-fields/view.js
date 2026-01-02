/**
 * @module user-profile/common-tab/src/block/common-fields/view
 */
jn.define('user-profile/common-tab/src/block/common-fields/view', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { FieldFactory } = require('user-profile/common-tab/src/block/common-fields/src/field/factory');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { Text4 } = require('ui-system/typography/text');
	const { openInfoBox } = require('user-profile/common-tab/src/block/common-fields/src/info-box');
	const { BaseEditWrapper } = require('user-profile/common-tab/src/block/base-edit');
	const { isFieldValueEmpty } = require('user-profile/common-tab/src/block/common-fields/src/utils');
	const { PropTypes } = require('utils/validation');

	const ViewModeFieldIds = ['PERSONAL_MOBILE', 'UF_PHONE_INNER', 'EMAIL', 'DEPARTMENT_HEAD', 'UF_DEPARTMENT', 'PERSONAL_BIRTHDAY'];

	/**
	 * @typedef {Object} CommonFieldsProps
	 * @property {boolean} isEditMode
	 * @property {Array} [sections = []]
	 * @property {function} [onFocus]
	 * @property {function} [onChange]
	 * @property {Object} [parentWidget]

	 * @class CommonFields
	 */
	class CommonFields extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'common-fields-block',
				context: this,
			});
			this.changedFields = {};
		}

		render()
		{
			const { isEditMode } = this.props;

			if (isEditMode)
			{
				return this.#renderEditModeSections();
			}

			return this.#renderViewModeFields();
		}

		#renderEditModeSections()
		{
			const { sections = [] } = this.props;
			const renderedSections = sections.map(
				(section) => this.#renderSection(section),
			);

			return View(
				{
					testId: this.getTestId('edit-list'),
				},
				...renderedSections,
			);
		}

		#renderSection(section)
		{
			const { isEditMode, onFocus } = this.props;
			const { title, fields = [] } = section;
			const renderedFields = fields
				.filter((field) => this.#isFieldVisible(field))
				.map((field, index) => FieldFactory.create(field.type, {
					...field,
					testId: field.id,
					isEditMode,
					isFirst: index === 0,
					onChange: this.onFieldValueChange.bind(this, field.id),
					onFocus,
				}));

			return BaseEditWrapper({
				testId: this.getTestId('edit-section'),
				title: title?.toUpperCase(),
				content: View(
					{},
					...renderedFields,
				),
			});
		}

		#isFieldVisible = (field) => {
			const { isEditMode } = this.props;
			const { isEditable } = field;

			return (isEditMode && isEditable) || (!isEditMode && !isFieldValueEmpty(field));
		};

		onFieldValueChange = (fieldId, value, isValid = true) => {
			const { onChange } = this.props;
			const { id, type } = this.#getFieldById(fieldId);
			this.changedFields[fieldId] = {
				id,
				type,
				value,
				isValid,
			};
			onChange?.('commonFields', this.changedFields, this.isChangedFieldsValid());
		};

		isChangedFieldsValid = () => {
			return Object.values(this.changedFields).every((field) => field.isValid);
		};

		#getFieldById(fieldId)
		{
			const { sections = [] } = this.props;

			return sections
				.flatMap((section) => section.fields || [])
				.find((field) => field.id === fieldId);
		}

		#renderViewModeFields()
		{
			const { isEditMode, sections = [] } = this.props;

			const renderedFields = sections
				.flatMap((section) => section.fields || [])
				.filter((field) => !isFieldValueEmpty(field) && ViewModeFieldIds.includes(field.id))
				.sort((a, b) => ViewModeFieldIds.indexOf(a.id) - ViewModeFieldIds.indexOf(b.id))
				.map((field, index) => FieldFactory.create(field.type, {
					...field,
					testId: field.id,
					isEditMode,
					isFirst: index === 0,
				}));

			return View(
				{},
				...renderedFields,
				this.#renderShowAllFieldsButton(),
			);
		}

		#renderShowAllFieldsButton()
		{
			return View(
				{
					style: {
						width: '100%',
						alignItems: 'center',
					},
					onClick: this.onShowAllFieldsLinkClick,
				},
				this.#renderDivider(),
				Text4({
					color: Color.base3,
					text: Loc.getMessage('M_PROFILE_COMMON_FIELDS_EXPAND_BUTTON'),
					testId: this.getTestId('show-all-fields-link'),
				}),
			);
		}

		onShowAllFieldsLinkClick = () => {
			const { sections = [], parentWidget } = this.props;
			openInfoBox({
				testId: this.getTestId('common-fields-details'),
				sections,
				parentWidget,
			});
		};

		#renderDivider()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: Color.bgSeparatorSecondary.toHex(),
					marginVertical: Indent.XL2.toNumber(),
				},
			});
		}
	}

	CommonFields.defaultProps = {
		sections: [],
	};

	CommonFields.propTypes = {
		isEditMode: PropTypes.bool.isRequired,
		sections: PropTypes.array,
		onFocus: PropTypes.func,
		onChange: PropTypes.func,
		parentWidget: PropTypes.object,
	};

	module.exports = {
		/**
		 * @property {CommonFieldsProps} props
		 * @returns {CommonFields}
		 */
		CommonFields: (props) => new CommonFields(props),
	};
});
