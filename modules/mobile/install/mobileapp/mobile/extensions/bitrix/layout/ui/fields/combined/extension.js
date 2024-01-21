/**
 * @module layout/ui/fields/combined
 */
jn.define('layout/ui/fields/combined', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseField } = require('layout/ui/fields/base');
	const { useCallback } = require('utils/function');

	/**
	 * @class CombinedField
	 */
	class CombinedField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.primaryFieldRef = null;
			this.secondaryFieldRef = null;

			this.bindPrimaryRef = this.bindPrimaryRef.bind(this);
			this.bindSecondaryRef = this.bindSecondaryRef.bind(this);
		}

		hasNestedFields()
		{
			return true;
		}

		needToValidateCurrentTick(newProps)
		{
			return false;
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				primaryField: BX.prop.getObject(config, 'primaryField', {}),
				secondaryField: BX.prop.getObject(config, 'secondaryField', {}),
				styles: BX.prop.getObject(config, 'styles', {}),
			};
		}

		prepareSingleValue(value)
		{
			if (!BX.type.isPlainObject(value))
			{
				value = {};
			}

			return value;
		}

		isEmptyValue(value)
		{
			return Object.values(value).length < 2;
		}

		render()
		{
			if (this.isHidden())
			{
				return null;
			}

			this.styles = this.getStyles();

			if (this.isReadOnly() && this.isEmpty())
			{
				return super.render();
			}

			const { primaryField, secondaryField } = this.prepareFieldsConfig();
			const value = this.getValue();

			const primaryId = primaryField.id;
			const secondaryId = secondaryField.id;
			const {
				[primaryId]: primaryValue,
				[secondaryId]: secondaryValue,
			} = value;

			const renderPrimaryField = primaryField.renderField || this.props.renderField;
			const renderSecondaryField = secondaryField.renderField || this.props.renderField;

			return View(
				{
					style: this.styles.combinedContainerWrapper,
				},
				View(
					{
						style: this.styles.combinedContainer,
						testId: `${this.testId}_COMBINED_FIELD`,
					},
					View(
						{
							style: this.styles.primaryFieldContainer,
							testId: `${this.testId}_PRIMARY_FIELD`,
						},
						renderPrimaryField({
							...primaryField,
							focus: this.state.focus && !primaryField.disabled || undefined,
							required: primaryField.required || this.props.required,
							readOnly: this.isReadOnly(),
							value: primaryValue,
							ref: this.bindPrimaryRef,
							testId: `${this.testId}_${primaryId}`,
							uid: this.props.uid,
							context: this.props.context,
							tooltip: this.props.tooltip,
							isNew: this.isNew(),
							parent: this,
							onChange: useCallback((value) => this.handleChange({
								[primaryId]: value,
								[secondaryId]: secondaryValue,
							}, [primaryId, secondaryId, secondaryValue])),
							onFocusIn: this.props.onFocusIn,
							onFocusOut: this.props.onFocusOut,
							showBorder: false,
						}),
					),
					View(
						{
							style: this.styles.secondaryFieldContainer,
							testId: `${this.testId}_SECONDARY_FIELD`,
						},
						renderSecondaryField({
							...secondaryField,
							focus: undefined,
							readOnly: secondaryField.readOnly ?? this.isReadOnly(),
							value: secondaryValue,
							ref: this.bindSecondaryRef,
							testId: `${this.testId}_${secondaryId}`,
							uid: this.props.uid,
							isNew: this.props.isNew,
							parent: this,
							onChange: useCallback((value) => this.handleChange({
								[primaryId]: primaryValue,
								[secondaryId]: value,
							}, [primaryId, secondaryId, secondaryValue])),
							onFocusIn: this.props.onFocusIn,
							onFocusOut: this.props.onFocusOut,
							showEditIcon: false,
							showBorder: false,
						}),
					),
					this.renderAdditionalContent(),
				),
				!this.hasErrorMessage() && this.hasTooltipMessage() && this.renderTooltip(),
			);
		}

		renderReadOnlyContent()
		{
			return this.renderEmptyContent();
		}

		getTitleText()
		{
			const { primaryField } = this.prepareFieldsConfig();

			return primaryField.title || super.getTitleText();
		}

		bindPrimaryRef(ref)
		{
			this.primaryFieldRef = ref;
		}

		bindSecondaryRef(ref)
		{
			this.secondaryFieldRef = ref;
		}

		prepareFieldsConfig()
		{
			const styles = this.getStyles();

			const config = this.getConfig();
			const { primaryField, secondaryField } = config;

			delete config.primaryField;
			delete config.secondaryField;
			delete config.styles;
			delete config.deepMergeStyles;

			if (!primaryField.type && !primaryField.renderField)
			{
				throw new Error('Primary field {type} or {renderField} is required.');
			}

			if (!secondaryField.type && !secondaryField.renderField)
			{
				throw new Error('Secondary field {type} or {render} functions is required.');
			}

			if (!primaryField.id || !secondaryField.id)
			{
				throw new Error('Primary and secondary ids are required.');
			}

			if (this.props.title)
			{
				primaryField.title = this.props.title;
			}

			primaryField.config = { ...config, ...primaryField.config };
			primaryField.config.deepMergeStyles = {
				...primaryField.config.deepMergeStyles,
				wrapper: { ...styles.primaryFieldWrapper },
				readOnlyWrapper: { ...styles.primaryFieldWrapper },
				title: { ...styles.primaryFieldTitle },
				externalWrapper: {
					marginHorizontal: 0,
				},
			};

			secondaryField.config = { ...config, ...secondaryField.config };
			secondaryField.config.deepMergeStyles = {
				...secondaryField.config.deepMergeStyles,
				wrapper: { ...styles.secondaryFieldWrapper },
				readOnlyWrapper: { ...styles.secondaryFieldWrapper },
				title: { ...styles.secondaryFieldTitle },
				value: styles.secondaryFieldValue,
				selectorWrapper: { ...styles.selectorWrapper },
				arrowImageContainer: styles.secondaryArrowImage || {},
				externalWrapper: {
					marginHorizontal: 0,
				},
				innerWrapper: {
					flex: 0,
				},
			};

			return { primaryField, secondaryField };
		}

		focus()
		{
			if (this.primaryFieldRef && this.primaryFieldRef.isPossibleToFocus())
			{
				return this.primaryFieldRef.focus();
			}

			return Promise.reject();
		}

		validate(checkFocusOut = true)
		{
			let result = true;

			if (this.primaryFieldRef)
			{
				result &= this.primaryFieldRef.validate(checkFocusOut);
			}

			if (this.secondaryFieldRef)
			{
				result &= this.secondaryFieldRef.validate(checkFocusOut);
			}

			return Boolean(result);
		}

		hasEditableFields()
		{
			const { primaryField, secondaryField } = this.getConfig();

			return (
				(primaryField && !primaryField.readonly)
				|| (secondaryField && !secondaryField.readonly)
			);
		}

		getPrimaryFieldType()
		{
			const { primaryField } = this.prepareFieldsConfig();

			return primaryField.type;
		}

		isNew()
		{
			return BX.prop.getBoolean(this.props, 'isNew', false);
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();
			const hasEditableFields = this.hasEditableFields();

			return {
				...styles,
				combinedContainerWrapper: {
					width: '100%',
					flexDirection: 'column',
				},
				combinedContainer: {
					flexWrap: 'wrap',
					justifyContent: 'center',
					alignItems: 'center',
					flexDirection: 'row',
					width: '100%',
					paddingTop: 8,
					paddingBottom: 13,
				},
				primaryFieldWrapper: {
					paddingTop: 0,
					paddingBottom: 0,
				},
				primaryFieldContainer: {
					flex: 1,
					marginRight: 7,
					maxWidth: '100%',
				},
				secondaryFieldTitle: {
					marginBottom: hasEditableFields ? 5 : 1,
				},
				secondaryFieldWrapper: {
					flex: 0,
					borderLeftWidth: 0.5,
					borderLeftColor: AppTheme.colors.bgSeparatorPrimary,
					paddingTop: 0,
					paddingBottom: 0,
					paddingLeft: 14,
					paddingRight: 14,
				},
				secondaryFieldContainer: {
					width: 111,
					flexDirection: 'row',
				},
			};
		}
	}

	module.exports = {
		CombinedType: 'combined',
		CombinedFieldClass: CombinedField,
		CombinedField: (props) => new CombinedField(props),
	};
});
