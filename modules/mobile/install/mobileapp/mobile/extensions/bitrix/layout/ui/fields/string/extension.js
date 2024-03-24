/**
 * @module layout/ui/fields/string
 */
jn.define('layout/ui/fields/string', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { inAppUrl } = require('in-app-url');
	const { BaseField } = require('layout/ui/fields/base');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { stringify } = require('utils/string');
	const { Type } = require('type');
	const { CollapsibleText } = require('layout/ui/collapsible-text');

	const isIosPlatform = Application.getPlatform() === 'ios';

	const ReadOnlyElementType = {
		BB_CODE_TEXT: 'BBCodeText',
		TEXT_INPUT: 'TextInput',
		TEXT: 'Text',
	};

	/**
	 * @class StringField
	 */
	class StringField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.fieldValue = null;

			this.inputRef = null;

			this.debouncedChangeText = debounce((text) => this.changeText(text), 50, this);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				keyboardType: 'default',
				autoCapitalize: BX.prop.getString(config, 'autoCapitalize'),
				enableKeyboardHide: BX.prop.getBoolean(config, 'enableKeyboardHide', false),
				selectionOnFocus: BX.prop.getBoolean(config, 'selectionOnFocus', false),
				ellipsize: BX.prop.getBoolean(config, 'ellipsize', false),
				readOnlyElementType: BX.prop.getString(config, 'readOnlyElementType', ReadOnlyElementType.TEXT),
				onLinkClick: BX.prop.getFunction(config, 'onLinkClick'),
				onSubmitEditing: BX.prop.getFunction(config, 'onSubmitEditing', null),
			};
		}

		shouldComponentUpdate(nextProps, nextState)
		{
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

			if (this.hasHiddenEmptyView())
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
					borderBottomColor: this.showBorder() && this.state.focus
						? AppTheme.colors.accentMainLinks
						: this.getExternalWrapperBorderColor(),
				},
				editableValue: {
					...styles.base,
				},
				textPlaceder: {
					color: AppTheme.colors.base4,
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
					color: AppTheme.colors.base4,
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

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
					},
				},
				new CollapsibleText({
					value: this.getValue(),
					style: this.getStyles(),
					bbCodeMode: this.getConfig().readOnlyElementType === ReadOnlyElementType.BB_CODE_TEXT,
					onLinkClick: this.getOnLinkClick(),
					onLongClick: this.getContentLongClickHandler(),
				}),
			);
		}

		getReadOnlyRenderParams()
		{
			return {
				style: {
					...this.styles.value,
				},
			};
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
				ref: (ref) => {
					this.inputRef = ref;
				},
				style: this.styles.editableValue,
				value: this.getValue(),
				forcedValue: this.getForcedValue(),
				focus: focus || undefined,
				keyboardType,
				enableKeyboardHide,
				autoCapitalize,
				placeholder: this.getPlaceholder(),
				placeholderTextColor: this.styles.textPlaceholder?.color || AppTheme.colors.base4,
				onFocus: () => this.setFocus(),
				onBlur: () => this.removeFocus(),
				onChangeText: this.debouncedChangeText,
				onSubmitEditing: () => FocusManager.blurFocusedFieldIfHas(),
				onLinkClick: this.getOnLinkClick(),
				isPassword: this.isPassword(),
			};
		}

		getForcedValue()
		{
			return (Type.isNil(this.props.forcedValue) ? undefined : this.prepareValue(this.props.forcedValue));
		}

		getPlaceholder()
		{
			return this.props.placeholder || BX.message('FIELDS_INLINE_FIELD_EMPTY_STRING_PLACEHOLDER');
		}

		isPassword()
		{
			return this.props.isPassword || false;
		}

		getOnLinkClick()
		{
			const defaultOnLinkClick = ({ url }) => inAppUrl.open(url);

			return BX.prop.getFunction(this.getConfig(), 'onLinkClick', defaultOnLinkClick);
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

		setCursorPositionTo(start = 0, end = 0)
		{
			if (this.isPossibleToFocus())
			{
				this.inputRef.focus();
				this.inputRef.setSelection(start, end);

				return Promise.resolve();
			}

			return Promise.reject();
		}

		handleAdditionalFocusActions()
		{
			if (this.inputRef && this.getConfig().selectionOnFocus)
			{
				this.inputRef.selectAll();
			}

			return Promise.resolve();
		}

		hasCapitalizeTitleInEmpty()
		{
			return !this.state.focus;
		}

		canCopyValue()
		{
			return this.isReadOnly();
		}
	}

	module.exports = {
		StringFieldClass: StringField,
		StringType: 'string',
		StringField: (props) => new StringField(props),
		ReadOnlyElementType,
	};
});
