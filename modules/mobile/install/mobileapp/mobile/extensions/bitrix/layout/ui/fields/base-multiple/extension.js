/**
 * @module layout/ui/fields/base-multiple
 */
jn.define('layout/ui/fields/base-multiple', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { useCallback } = require('utils/function');

	/**
	 * @class BaseMultipleField
	 * @abstract
	 */
	class BaseMultipleField extends BaseField
	{
		constructor(props)
		{
			super(props);

			/** @type {?number} */
			this.newInnerFieldIndex = null;
			/** @type {BaseField[]} */
			this.fieldsRef = [];
		}

		hasNestedFields()
		{
			return true;
		}

		isEnableToEdit()
		{
			return BX.prop.getBoolean(this.props, 'enableToEdit', !this.isReadOnly());
		}

		needToValidateCurrentTick(newProps)
		{
			return false;
		}

		generateNextIndex()
		{
			if (!this.generatorFunction)
			{
				let index = 0;

				this.generatorFunction = () => index++;
			}

			return 'n' + this.generatorFunction();
		}

		prepareValue(value)
		{
			if (!Array.isArray(value))
			{
				value = [value];
			}

			value = super.prepareValue(value);

			if (value.length === 0)
			{
				value.push(this.prepareSingleValue(''));
			}

			return value;
		}

		validate(checkFocusOut = true)
		{
			let result = true;

			this.fieldsRef.forEach((fieldRef) => {
				if (fieldRef)
				{
					result &= fieldRef.validate(checkFocusOut);
				}
			});

			return Boolean(result);
		}

		renderField(item, index)
		{
			const fieldProps = this.getFieldProps(item, index);

			return View(
				{
					style: this.styles.multipleFieldWrapper,
				},
				View(
					{
						style: this.styles.multipleFieldContainer,
					},
					this.props.renderField(fieldProps),
				),
			);
		}

		getFieldProps(item, index)
		{
			const { ref, ...passThroughProps } = this.props;
			const { id, value, isNew = false } = item;

			let focus;

			if (this.newInnerFieldIndex !== null)
			{
				focus = this.newInnerFieldIndex === index;
				if (focus)
				{
					this.newInnerFieldIndex = null;
				}
			}
			else if (this.isEmptyEditable() && this.state.focus)
			{
				focus = true;
			}

			return {
				...passThroughProps,
				ref: useCallback((ref) => {
					this.fieldsRef[index] = ref;
				}, [index]),
				id,
				value,
				focus,
				parent: this,
				isNew: isNew && this.isEditable(),
				multiple: false,
				title: this.getInnerFieldTitle(index),
				onChange: useCallback(
					(value) => {
						const val = [...this.getValue()];
						val[index].value = value;
						this.handleChange(val);
					},
					[index],
				),
				config: this.getInnerFieldConfig(),
				readOnly: this.isReadOnly() || !this.isEnableToEdit(),
				hasHiddenEmptyView: false,
				renderAdditionalContent: useCallback(
					() => this.renderAddOrDeleteFieldButton(index, isNew),
					[index, isNew],
				),
			};
		}

		getInnerFieldTitle(index)
		{
			if (index > 0)
			{
				const formatTitle = this.getFormatTitle(this.getConfig());
				if (formatTitle)
				{
					return formatTitle(index);
				}

				return `${this.props.title} ${index + 1}`;
			}

			return this.props.title;
		}

		getFormatTitle(config)
		{
			return BX.prop.getFunction(config, 'formatTitle', null);
		}

		renderAddOrDeleteFieldButton(index, isNew)
		{
			return null;
		}

		onDeleteField(index)
		{
			const value = [...this.getValue()];

			value.splice(index, 1);

			FocusManager
				.blurFocusedFieldIfHas(this)
				.then(() => this.handleChange(value));
		}

		onAddField()
		{
			this.state.showAll = true;

			const value = this.getValue();
			this.newInnerFieldIndex = value.length;

			return this.handleChange([
				...value,
				this.prepareSingleValue('', value),
			]);
		}

		getInnerFieldConfig()
		{
			const config = this.getConfig();
			const styles = config.styles || {};

			return {
				...config,
				multiple: true,
				styles: this.getInnerFieldStyles(styles),
			};
		}

		getInnerFieldStyles(styles)
		{
			return {
				...styles,
			};
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				multipleFieldWrapper: {
					flexDirection: 'row',
					flexWrap: 'no-wrap',
					alignItems: 'center',
				},
				multipleFieldContainer: {
					flexGrow: 2,
				},
				addOrDeleteFieldButtonWrapper: {
					justifyContent: 'center',
					marginLeft: 7,
					width: 33,
					height: 33,
				},
				buttonContainer: {
					width: 24,
					height: 24,
				},
			};
		}
	}

	module.exports = { BaseMultipleField };
});