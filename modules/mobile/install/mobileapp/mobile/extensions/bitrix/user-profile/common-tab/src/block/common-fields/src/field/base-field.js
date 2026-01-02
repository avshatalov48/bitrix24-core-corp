/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/base-field
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/base-field', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Text5, Capital } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { Link4 } = require('ui-system/blocks/link');
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { Text7 } = require('ui-system/typography/text');

	/**
	 * @typedef {Object} FieldProps
	 * @property {string} testId
	 * @property {bool} isEditMode
	 * @property {bool} isMultiple
	 * @property {string} title
	 * @property {string} value
	 * @property {boolean} isFirst
	 * @property {function} onChange
	 * @property {function} onFocus
	 * @property {string} id

	 * @class BaseField
	 */
	class BaseField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.refs = {};
			this.getTestId = createTestIdGenerator({
				prefix: `common-field-${props.testId}`,
				context: this,
			});
			this.initState(props);
		}

		componentWillReceiveProps(newProps)
		{
			this.initState(newProps);
		}

		initState(props)
		{
			this.state = {
				value: props.value ?? null,
				isValid: props.isValid ?? true,
			};
		}

		render()
		{
			const { isEditMode, isMultiple } = this.props;
			const { value, isValid } = this.state;

			return View(
				{
					testId: this.getTestId('field-container-wrapper'),
					style: this.getFieldContainerWrapperStyle(),
				},
				View(
					{
						testId: this.getTestId('field-container'),
						style: this.getFieldContainerStyle(),
					},
					this.#renderFieldTitle(),
					!isEditMode && !isMultiple && this.renderViewModeFieldValue(value, 0),
					isEditMode && !isMultiple && this.renderEditModeFieldValue(value, 0),
					!isEditMode && isMultiple && this.renderViewModeFieldMultipleValues(),
					isEditMode && isMultiple && this.renderEditModeFieldMultipleValues(),
				),
				isEditMode && !isValid && this.#renderErrorText(),
			);
		}

		#renderErrorText()
		{
			return Text7({
				numberOfLines: 1,
				ellipsize: 'middle',
				text: this.getErrorText(),
				color: Color.accentMainAlert,
				style: {
					position: 'absolute',
					bottom: Indent.L.toNumber() / 2,
					backgroundColor: Color.bgContentPrimary.toHex(),
					marginHorizontal: Indent.M.toNumber(),
					paddingHorizontal: Indent.XS.toNumber(),
					textAlign: 'center',
				},
			});
		}

		getErrorText()
		{
			return '';
		}

		getFieldContainerWrapperStyle()
		{
			const { isEditMode } = this.props;

			return {
				paddingBottom: isEditMode ? Indent.L.toNumber() : 0,
			};
		}

		getFieldContainerStyle()
		{
			const { isEditMode, isFirst } = this.props;
			const { isValid } = this.state;

			return {
				marginTop: isFirst ? 0 : Indent.XL2.toNumber(),
				paddingBottom: isEditMode ? Indent.L.toNumber() : 0,
				alignItems: 'flex-start',
				borderBottomColor: isValid ? Color.bgSeparatorSecondary.toHex() : Color.accentMainAlert.toHex(),
				borderBottomWidth: isEditMode ? 1 : 0,
				...this.getFieldContainerCustomStyle(),
			};
		}

		getFieldContainerCustomStyle()
		{
			return {};
		}

		#renderFieldTitle()
		{
			const { isEditMode, title } = this.props;

			if (isEditMode)
			{
				return Capital({
					text: title.toUpperCase(),
					color: Color.base4,
					style: this.getFieldTitleStyle(),
				});
			}

			return Text5({
				text: title,
				color: Color.base3,
				style: this.getFieldTitleStyle(),
			});
		}

		getFieldTitleStyle()
		{
			return {
				marginBottom: Indent.S.toNumber(),
			};
		}

		renderViewModeFieldValue(value, idx)
		{
			// This method should be overridden in subclasses to provide specific field value editing
			return null;
		}

		renderEditModeFieldValue(value, idx)
		{
			// This method should be overridden in subclasses to provide specific field value editing
			return null;
		}

		getDefaultValue()
		{
			// This method should be overridden in subclasses to provide specific default value for new entries
			return null;
		}

		renderViewModeFieldMultipleValues()
		{
			const { value } = this.state;
			if (!Array.isArray(value) || value.length === 0)
			{
				return null;
			}

			return View(
				{
					testId: this.getTestId('multiple-values-view-container'),
					style: {
						width: '100%',
					},
				},
				...value.map((item, idx) => this.renderViewModeFieldValue(item, idx)),
			);
		}

		renderEditModeFieldMultipleValues()
		{
			const { id } = this.props;
			const { value = [] } = this.state;

			return View(
				{
					testId: this.getTestId(`${id.toLowerCase()}-multiple-values-edit-container`),
					style: {
						width: '100%',
					},
				},
				...value.map((item, idx) => this.renderEditModeFieldValue(item, idx)),
				this.renderAddNewValueButton(this.addValue),
			);
		}

		renderAddNewValueButton(onAddNewValue)
		{
			const { id } = this.props;

			return Link4({
				testId: this.getTestId(`${id.toLowerCase()}-add-new-value-button`),
				text: Loc.getMessage('M_PROFILE_COMMON_FIELDS_ADD_VALUE_BUTTON_TEXT'),
				size: 4,
				color: Color.link,
				onPress: onAddNewValue,
				style: {
					marginTop: Indent.S.toNumber(),
				},
			});
		}

		onChange = (newValue, idx) => {
			const { onChange, isMultiple } = this.props;
			const { value } = this.state;
			const newValues = isMultiple
				? value.map((item, i) => (i === idx ? newValue : item))
				: newValue;
			this.setState({
				value: newValues,
			}, () => {
				onChange?.(newValues);
			});
		};

		addValue = () => {
			const { onChange } = this.props;
			const { value } = this.state;
			onChange?.([...value, this.getDefaultValue()]);
		};

		bindRef = (ref, idx) => {
			this.refs[idx] = ref;
		};

		onFocus = (idx) => {
			const { onFocus } = this.props;
			onFocus?.(this.refs?.[idx]);
		};
	}

	BaseField.propTypes = {
		id: PropTypes.string.isRequired,
		testId: PropTypes.string.isRequired,
		isEditMode: PropTypes.bool.isRequired,
		isMultiple: PropTypes.bool.isRequired,
		title: PropTypes.string.isRequired,
		value: PropTypes.any,
		isFirst: PropTypes.bool,
		onChange: PropTypes.func,
		onFocus: PropTypes.func,
	};

	BaseField.defaultProps = {
		value: null,
		isFirst: false,
	};

	module.exports = {
		BaseField,
	};
});
