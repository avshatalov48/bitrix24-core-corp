/**
 * @module layout/ui/fields/string
 */
jn.define('layout/ui/fields/string', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { stringify } = require('utils/string');

	const isIosPlatform = Application.getPlatform() === 'ios';

	/**
	 * @class StringField
	 */
	class StringField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.fieldValue = null;
			this.state.showAll = this.getValue().length <= 180;

			this.inputRef = null;
			this.showHideButton = this.getValue().length > 180;

			this.debouncedChangeText = debounce((text) => this.changeText(text), 50, this);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				keyboardType: 'default',
				autoCapitalize: BX.prop.getString(config, 'autoCapitalize', undefined),
				enableKeyboardHide: BX.prop.getBoolean(config, 'enableKeyboardHide', false),
				selectionOnFocus: BX.prop.getBoolean(config, 'selectionOnFocus', false),
				ellipsize: BX.prop.getBoolean(config, 'ellipsize', false),
				readOnlyElementType: BX.prop.getString(config, 'readOnlyElementType', 'Text'),
				onLinkClick: BX.prop.getFunction(config, 'onLinkClick', undefined),
				onSubmitEditing: BX.prop.getFunction(config, 'onSubmitEditing', null),
			};
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			//hide text onScroll ListView
			if (this.isReadOnly() && this.showHideButton && this.state.showAll && this.props.value === nextProps.value)
			{
				this.state.showAll = false;

				return true;
			}

			nextState = Array.isArray(nextState) ? nextState[0] : nextState;

			let prevPropsToCompare = this.props;
			let nextPropsToCompare = nextProps;

			if (this.fieldValue !== null)
			{
				const fieldValue = this.fieldValue;

				this.fieldValue = null;

				if (!isEqual(fieldValue, nextProps.value))
				{
					this.logComponentDifference({ value: fieldValue }, { value: nextProps.value }, null, null);

					return true;
				}

				const { value: prevValue, ...prevPropsWithoutValue } = this.props;
				const { value: nextValue, ...nextPropsWithoutValue } = nextProps;

				prevPropsToCompare = prevPropsWithoutValue;
				nextPropsToCompare = nextPropsWithoutValue;
			}

			const hasChanged = !isEqual(prevPropsToCompare, nextPropsToCompare) || !isEqual(this.state, nextState);
			if (hasChanged)
			{
				this.logComponentDifference(prevPropsToCompare, nextPropsToCompare, this.state, nextState);

				return true;
			}

			return false;
		}

		hasKeyboard()
		{
			return true;
		}

		/**
		 * @param {any} value
		 * @returns {String}
		 */
		prepareSingleValue(value)
		{
			return stringify(value);
		}

		/**
		 * @param {string} value
		 * @returns {boolean}
		 */
		isEmptyValue(value)
		{
			return typeof value === 'string' && value.trim() === '';
		}

		getDefaultStyles()
		{
			const defaultStyles = super.getDefaultStyles();
			const styles = this.getChildFieldStyles(defaultStyles);

			if (this.isLeftTitlePosition())
			{
				return this.getLeftTitleChildStyles(styles);
			}
			else if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return styles;
		}

		getChildFieldStyles(styles)
		{
			return {
				...styles,
				externalWrapper: {
					...styles.externalWrapper,
					borderBottomColor: this.showBorder() && this.state.focus ? '#0b66c3' : this.getExternalWrapperBorderColor(),
				},
				editableValue: {
					...styles.base,
				},
				textPlaceholder: {
					color: '#a8adb4',
				},
			};
		}

		getLeftTitleChildStyles(styles)
		{
			return styles;
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			const isFocusedEmptyEditable = this.isEmptyEditable() && !this.state.focus;
			const paddingBottomWithoutError = isFocusedEmptyEditable ? 20 : 11.5;

			return {
				...styles,
				textPlaceholder: {
					color: '#a8adb4',
				},
				wrapper: {
					...styles.wrapper,
					paddingTop: isFocusedEmptyEditable ? 14 : 8,
					paddingBottom: this.hasErrorOrTooltip() ? 5 : paddingBottomWithoutError,
				},
				title: {
					...styles.title,
					fontSize: isFocusedEmptyEditable ? 16 : 10,
					marginBottom: isFocusedEmptyEditable ? 0 : 2,
				},
				container: {
					...styles.container,
					// value = 1 to enable focus on Android
					height: isFocusedEmptyEditable ? 1 : null,
				},
			};
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			const params = this.getReadOnlyRenderParams();

			const getContent = (readOnlyElementType) => {
				switch (readOnlyElementType)
				{
					case 'BBCodeText':
						return BBCodeText({
							...params,
							value: this.getValue(),
							onLinkClick: this.getConfig().onLinkClick,
						});

					case 'TextInput':
						return TextInput({
							...params,
							value: this.getValue(),
							enable: false,
						});

					default:
						return Text({
							...params,
							text: this.getValue(),
						});
				}
			};

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							flexGrow: 2,
						},
					},
					getContent(this.getConfig().readOnlyElementType),
				),
				this.renderShowAllButton(1),
				this.renderHideButton(),
			);
		}

		getReadOnlyRenderParams()
		{
			return {
				style: {
					...this.styles.value,
					maxHeight: this.state.showAll ? null : this.getMaximusHeight(),
				},
			};
		}

		getMaximusHeight()
		{
			return isIosPlatform ? 50 : 40;
		}

		getEllipsizeParams()
		{
			return this.getConfig().ellipsize ? {
				numberOfLines: 1,
				ellipsize: 'end',
			} : null;
		}

		renderEditableContent()
		{
			return TextField(this.getFieldInputProps());
		}

		getFieldInputProps()
		{
			const { keyboardType, enableKeyboardHide, autoCapitalize } = this.getConfig();
			const { focus } = this.state;

			return {
				ref: (ref) => this.inputRef = ref,
				style: this.styles.editableValue,
				value: this.getValue(),
				focus: focus || undefined,
				keyboardType,
				enableKeyboardHide,
				autoCapitalize,
				placeholder: this.getPlaceholder(),
				placeholderTextColor: this.styles.textPlaceholder.color,
				onFocus: () => this.setFocus(),
				onBlur: () => this.removeFocus(),
				onChangeText: this.debouncedChangeText,
				onSubmitEditing: () => FocusManager.blurFocusedFieldIfHas(),
				onLinkClick: this.getConfig().onLinkClick,
			};
		}

		getPlaceholder()
		{
			return this.props.placeholder || BX.message('FIELDS_INLINE_FIELD_EMPTY_STRING_PLACEHOLDER');
		}

		changeText(currentText)
		{
			this.fieldValue = currentText;
			this.handleChange(currentText);
		}

		/**
		 * @public
		 * @return {Promise<never>|Promise<void>|*}
		 */
		focus()
		{
			if (this.isPossibleToFocus())
			{
				this.inputRef.focus();

				return Promise.resolve();
			}

			return Promise.reject();
		}

		handleAdditionalFocusActions()
		{
			if (this.inputRef)
			{
				if (this.getConfig().selectionOnFocus)
				{
					if (Application.getApiVersion() >= 46)
					{
						this.inputRef.selectAll();
					}
					else
					{
						this.inputRef.setSelection(0, this.getValue().length);
					}
				}
			}

			return Promise.resolve();
		}

		hasCapitalizeTitleInEmpty()
		{
			return !this.state.focus;
		}

		showAllCount()
		{
			return false;
		}
	}

	module.exports = {
		StringFieldClass: StringField,
		StringType: 'string',
		StringField: (props) => new StringField(props),
	};
});
