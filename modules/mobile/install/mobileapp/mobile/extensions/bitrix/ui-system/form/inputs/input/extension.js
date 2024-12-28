/**
 * @module ui-system/form/inputs/input
 */
jn.define('ui-system/form/inputs/input', (require, exports, module) => {
	const { Type } = require('type');
	const { isEmpty } = require('utils/object');
	const { debounce } = require('utils/function');
	const { Color, Indent, Component } = require('tokens');
	const { Text7 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { TextInput } = require('ui-system/typography/text-input');
	const { PropTypes } = require('utils/validation');
	const { InputMode } = require('ui-system/form/inputs/input/src/enums/mode-enum');
	const { InputSize } = require('ui-system/form/inputs/input/src/enums/size-enum');
	const { InputDesign } = require('ui-system/form/inputs/input/src/enums/design-enum');
	const { InputVisualDecorator } = require('ui-system/form/inputs/input/src/visual-decorator');

	const ALIGN = {
		left: 'flex-start',
		center: 'center',
		right: 'flex-end',
	};

	const ICON_SIZE = 22;

	const ICON_CONTENT_SIZE = {
		width: 26,
		height: 22,
	};

	/**
	 * @typedef {Object} InputProps
	 * @property {string} testId
	 * @property {Function} [forwardRef]
	 * @property {string} [value]
	 * @property {string} [placeholder]
	 * @property {string} [label]
	 * @property {InputSize} [size=InputSize.M]
	 * @property {InputMode} [mode=InputMode.STROKE]
	 * @property {InputDesign} [design=InputDesign.PRIMARY]
	 * @property {boolean} [focus=false]
	 * @property {boolean} [disabled=false]
	 * @property {boolean} [readOnly=false]
	 * @property {boolean} [multiline=false]
	 * @property {boolean} [locked=false]
	 * @property {boolean} [edit=false]
	 * @property {boolean} [dropdown=false]
	 * @property {boolean} [erase=false]
	 * @property {boolean} [required=false]
	 * @property {boolean} [error=false]
	 * @property {boolean} [enableKeyboardHide]
	 * @property {boolean} [enableLineBreak]
	 * @property {boolean} [showBBCode]
	 * @property {string} [errorText]
	 * @property {'left' | 'center' | 'right'} [align]
	 * @property {'number-pad' | 'decimal-pad' | 'numeric' | 'email-address' | 'phone-pad'} [keyboardType='default']
	 * @property {'characters' | 'words' | 'sentences' | 'none'} [autoCapitalize='none']
	 * @property {Color} [backgroundColor]
	 * @property {Object | Icon} [leftContent]
	 * @property {Function} [onClickLeftContent]
	 * @property {Object | Icon} [rightContent]
	 * @property {Function} [onClickRightContent]
	 * @property {Object | Icon} [rightStickContent]
	 * @property {Function} [onClickRightStickContent]
	 * @property {Function} [onFocus]
	 * @property {Function} [onChange]
	 * @property {Function} [onSubmit]
	 * @property {Function} [onBlur]
	 * @property {Function} [onErase]
	 * @property {Function} [onClick]
	 * @property {Function} [onError]
	 * @property {Function} [onLongClick]
	 * @property {Function} [onSelectionChange]
	 * @property {Function} [onCursorPositionChange]
	 * @property {Object} [style]

	 * @class Input
	 * @param {InputProps} props
	 */
	class Input extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.refMap = new Map();
			this.currentValue = null;
			this.contentFieldRef = null;
			this.actionsAfterMounted = [];

			this.initProperties();

			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.handleOnBlur = this.handleOnBlur.bind(this);
			this.handleOnSubmit = this.handleOnSubmit.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnChangeText = this.handleOnChangeText.bind(this);
			this.handleOnContentClick = this.handleOnContentClick.bind(this);
			this.handleOnContentLongClick = this.handleOnContentLongClick.bind(this);
			this.handleOnClickLeftContent = this.handleOnClickLeftContent.bind(this);
			this.handleOnClickRightContent = this.handleOnClickRightContent.bind(this);
			this.handleOnCursorPositionChange = this.handleOnCursorPositionChange.bind(this);
			this.handleOnSelectionChange = debounce(this.handleOnSelectionChange, 200, this);
		}

		get field()
		{
			const { field } = this.props;

			return field;
		}

		componentDidMount()
		{
			this.mounted = true;
		}

		componentDidUpdate()
		{
			this.invokeActionsAfterMounted();
		}

		componentWillUnmount()
		{
			this.mounted = false;
			this.currentValue = null;
			this.actionsAfterMounted = [];
		}

		componentWillReceiveProps(nextProps)
		{
			this.currentValue = nextProps.value ?? nextProps.text;
		}

		invokeActionsAfterMounted()
		{
			if (this.actionsAfterMounted.length > 0)
			{
				this.actionsAfterMounted.forEach((action) => {
					action?.();
				});

				this.actionsAfterMounted = [];
			}
		}

		initProperties()
		{}

		render()
		{
			return View(
				{
					testId: this.getTestId(),
					style: this.getContainerStyle(),
				},
				...this.renderBaseContent(),
			);
		}

		renderBaseContent()
		{
			return [
				this.renderInput(),
				this.renderLabel(),
				this.renderError(),
			];
		}

		renderInput()
		{
			return View(
				{
					style: this.getInputStyle(),
					onClick: this.handleOnContentClick,
					onLongClick: this.handleOnContentLongClick,
				},
				this.renderWrapperContent([
					this.renderLeftContent(),
					this.renderContent(),
					this.renderRightContent(),
					this.renderLockIcon(),
					this.renderEditIcon(),
				]),
				this.renderRightStick(),
			);
		}

		renderWrapperContent(content)
		{
			return View(
				{
					style: this.getWrapperContentStyle(),
				},
				...content,
			);
		}

		renderContent()
		{
			const { element } = this.props;

			const inputElement = element ?? TextInput;

			return View(
				{
					style: {
						flex: 1,
					},
				},
				inputElement(this.getFieldProps()),
			);
		}

		renderLabel()
		{
			const { label } = this.props;

			if (!this.shouldRenderLabel())
			{
				return null;
			}

			const { typography: Text, minPosition } = this.getSize().getLabel();
			const isNaked = this.isNaked();

			return View(
				{
					testId: this.getTestId('label'),
					style: {
						position: 'absolute',
						top: 0,
						alignSelf: this.getAlign(),
					},
				},
				View(
					{
						style: {
							marginHorizontal: isNaked ? 0 : minPosition.toNumber(),
							paddingHorizontal: isNaked ? 0 : Indent.XS.toNumber(),
							backgroundColor: this.getBackgroundColor(),
							borderRadius: Component.elementXSCorner.toNumber(),
						},
					},
					Text({
						text: label,
						color: Color.base3,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
			);
		}

		renderError()
		{
			if (!this.shouldRenderErrorText())
			{
				return null;
			}

			const { minPosition } = this.getSize().getLabel();
			const isNaked = this.isNaked();
			const { errorText } = this.props;

			return View(
				{
					testId: this.getTestId('error'),
					style: {
						position: 'absolute',
						bottom: 0,
						alignSelf: this.getAlign(),
					},
				},
				View(
					{
						style: {
							marginHorizontal: isNaked ? 0 : minPosition.toNumber(),
							paddingHorizontal: isNaked ? 0 : Indent.XS.toNumber(),
							backgroundColor: this.getBackgroundColor(),
							borderRadius: Component.elementXSCorner.toNumber(),
						},
					},
					Text7({
						numberOfLines: 1,
						ellipsize: 'middle',
						text: errorText,
						color: this.getErrorColor(),
					}),
				),
			);
		}

		renderRightStick()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderRightStickContent(),
				this.renderEraseIcon(),
				this.renderDropdownIcon(),
			);
		}

		renderRightStickContent()
		{
			const rightStickContent = this.getRightStickContent();

			if (this.isErase() || !rightStickContent)
			{
				return null;
			}

			const { onClickRightStickContent } = this.props;

			return this.renderIconContent({
				position: 'right_stick',
				content: rightStickContent,
				iconColor: Color.base2,
				onClick: onClickRightStickContent,
			});
		}

		/**
		 * @protected
		 */
		renderLeftContent()
		{
			const leftContent = this.getLeftContent();

			if (!leftContent)
			{
				return null;
			}

			return this.renderIconContent({
				position: 'left',
				content: leftContent,
				style: {
					marginRight: Indent.XS.toNumber(),
				},
				onClick: this.handleOnClickLeftContent,
			});
		}

		/**
		 * @protected
		 */
		renderRightContent()
		{
			const rightContent = this.getRightContent();

			if (!rightContent)
			{
				return null;
			}

			return this.renderIconContent({
				position: 'right',
				content: rightContent,
				style: {
					marginLeft: Indent.XS2.toNumber(),
				},
				onClick: this.handleOnClickRightContent,
			});
		}

		renderLockIcon()
		{
			if (!this.isLocked() || this.#isFocused() || this.isDisabled())
			{
				return null;
			}

			return IconView({
				size: Input.getIconSize(),
				icon: Icon.LOCK,
				color: Color.base4,
			});
		}

		renderEditIcon()
		{
			if (!this.isEditable() || this.#isFocused() || this.isDisabled())
			{
				return null;
			}

			return IconView({
				size: Input.getIconSize(),
				icon: Icon.EDIT,
				color: Color.base3,
			});
		}

		renderEraseIcon()
		{
			if (!this.isErase())
			{
				return null;
			}

			return IconView({
				testId: this.getTestId('erase'),
				size: Input.getIconSize(),
				icon: Icon.CROSS,
				color: Color.base2,
				onClick: this.handleOnErase,
			});
		}

		renderDropdownIcon()
		{
			if (!this.isDropdown() || this.isErase())
			{
				return null;
			}

			return IconView({
				testId: this.getTestId('dropdown'),
				size: Input.getIconSize(),
				icon: Icon.CHEVRON_DOWN_SIZE_M,
				color: Color.base2,
			});
		}

		/**
		 * @protected
		 */
		renderIconContent({ position, content, style, iconColor, onClick })
		{
			if (!content)
			{
				return null;
			}

			const iconWrapper = (element) => View(
				{
					testId: this.getTestId(`${position}-content`),
					ref: (ref) => {
						this.refMap.set(position, ref);
					},
					style: {
						...style,
						...Input.getIconContentSize(),
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick,
				},
				element,
			);

			if (content instanceof Icon)
			{
				return iconWrapper(IconView({
					size: Input.getIconSize(),
					icon: content,
					color: iconColor || Color.base3,
				}));
			}

			return iconWrapper(
				Type.isFunction(content)
					? content()
					: content,
			);
		}

		getContainerStyle()
		{
			const { style = {} } = this.props;
			const containerHeight = this.getContainerHeight();
			const paddingTop = this.shouldRenderLabel() ? Indent.M.toNumber() : 0;
			const paddingBottom = this.getContainerPaddingBottom();
			const height = containerHeight + paddingTop + paddingBottom + (this.isIOS() ? 0 : 1);

			return {
				height,
				paddingTop,
				position: 'relative',
				width: '100%',
				backgroundColor: this.getBackgroundColor(),
				...this.getBorderStyle(),
				...style,
			};
		}

		/**
		 * @protected
		 */
		getContainerPaddingBottom()
		{
			return this.shouldRenderErrorText() ? Indent.S.toNumber() : 0;
		}

		/**
		 * @protected
		 */
		getContainerHeight()
		{
			const { height } = this.getSize().getInput();

			return height;
		}

		/**
		 * @param {'right' | 'left' | 'right_stick'}elementPosition
		 */
		getRef = (elementPosition) => {
			if (elementPosition)
			{
				return this.refMap.get(elementPosition);
			}

			return this.contentFieldRef;
		};

		getBackgroundColor()
		{
			const { backgroundColor } = this.props;

			return Color.resolve(backgroundColor, Color.bgContentPrimary).toHex();
		}

		/**
		 * @protected
		 */
		getInputStyle()
		{
			const { paddingHorizontal } = this.getSize().getInput();
			const style = {
				height: this.getContainerHeight(),
				flexDirection: 'row',
				alignItems: 'center',
				paddingHorizontal: this.isNaked() ? 0 : paddingHorizontal.toNumber(),
				...this.getBorderStyle({ filled: true }),
			};

			if (this.isDisabled())
			{
				const { backgroundColor } = this.getDesignStyle();
				style.backgroundColor = backgroundColor.toHex();
			}

			return style;
		}

		getDesignStyle()
		{
			const { design } = this.props;
			let designEnum = InputDesign.resolve(design, InputDesign.PRIMARY);

			if (this.isDisabled())
			{
				designEnum = designEnum.getDisabled();
			}

			return designEnum.getStyle();
		}

		getFieldStyle()
		{
			return {
				textAlign: this.getAlign(true),
			};
		}

		getWrapperContentStyle()
		{
			return {
				flex: 1,
				alignItems: 'center',
				flexDirection: 'row',
			};
		}

		/**
		 * @returns {InputSize}
		 */
		getSize()
		{
			const { size } = this.props;

			return InputSize.resolve(size, InputSize.M);
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

		getValue()
		{
			const { value } = this.props;

			return String(this.currentValue ?? value);
		}

		isValid()
		{
			const { error } = this.props;

			return !error;
		}

		getPlaceholder()
		{
			const { placeholder } = this.props;

			if (placeholder && !this.isDisabled())
			{
				return placeholder;
			}

			return '';
		}

		getPlaceholderTextColor()
		{
			return Color.base4.toHex();
		}

		getKeyboardType()
		{
			const { keyboardType } = this.props;

			return keyboardType || 'default';
		}

		getTextSize()
		{
			const { textSize } = this.getSize().getInput();

			return textSize;
		}

		isStroke()
		{
			return this.getMode().equal(InputMode.STROKE);
		}

		isNaked()
		{
			return this.getMode().equal(InputMode.NAKED);
		}

		isDisabled()
		{
			const { disabled } = this.props;

			return disabled;
		}

		isLocked()
		{
			const { locked } = this.props;

			return Boolean(locked);
		}

		isEditable()
		{
			const { edit } = this.props;

			return Boolean(edit);
		}

		isScrollEnabled()
		{
			const { isScrollEnabled } = this.props;

			return Boolean(isScrollEnabled);
		}

		#isFocused()
		{
			const { isFocused, focus } = this.props;

			return Boolean(isFocused || focus);
		}

		isEnable()
		{
			return !this.isReadOnly() && !this.isDisabled();
		}

		isMultiline()
		{
			const { multiline } = this.props;

			return Boolean(multiline);
		}

		/**
		 * @protected
		 */
		isEnableKeyboardHide()
		{
			const { enableKeyboardHide } = this.props;

			return Boolean(enableKeyboardHide);
		}

		isReadOnly()
		{
			const { readOnly } = this.props;

			return Boolean(readOnly);
		}

		isDropdown()
		{
			const { dropdown } = this.props;

			return Boolean(dropdown);
		}

		/**
		 * @returns {boolean}
		 */
		shouldRenderLabel()
		{
			const { label } = this.props;

			return label && typeof label === 'string';
		}

		/**
		 * @returns {boolean}
		 */
		shouldRenderErrorText()
		{
			const { errorText } = this.props;

			return this.isError() && errorText && typeof errorText === 'string';
		}

		handleOnContentLongClick()
		{
			const { onLongClick } = this.props;

			onLongClick?.();
		}

		handleOnClickLeftContent()
		{
			const { onClickLeftContent } = this.props;

			if (onClickLeftContent)
			{
				onClickLeftContent();
			}
		}

		handleOnClickRightContent()
		{
			const { onClickRightContent } = this.props;

			if (onClickRightContent)
			{
				onClickRightContent();
			}
		}

		handleOnContentClick()
		{
			if (this.isEnable())
			{
				this.handleOnFocus();
			}
			else
			{
				this.handleOnClick();
			}
		}

		handleOnFocus()
		{
			const { onFocus } = this.props;

			return onFocus?.();
		}

		handleOnBlur()
		{
			const { onBlur } = this.props;

			return onBlur?.();
		}

		handleOnClick()
		{
			const { onClick } = this.props;

			onClick?.();
		}

		handleOnSubmit(value)
		{
			const { onSubmit } = this.props;

			if (onSubmit)
			{
				onSubmit(value);
			}
		}

		/**
		 * @protected
		 */
		async handleOnChangeText(value)
		{
			this.handleOnChange(value);
		}

		/**
		 * @protected
		 */
		handleOnChange(value)
		{
			this.currentValue = value;
			const { onChange } = this.props;

			if (onChange)
			{
				onChange(value);
			}
		}

		handleOnErase = () => {
			const { onErase } = this.props;

			if (onErase)
			{
				onErase();
			}
		};

		/**
		 * @protected
		 */
		handleOnCursorPositionChange(value)
		{
			const { onCursorPositionChange } = this.props;

			onCursorPositionChange?.(value);
		}

		/**
		 * @protected
		 */
		handleOnSelectionChange(value)
		{
			const { onSelectionChange } = this.props;

			onSelectionChange?.(value);
		}

		#handleOnForwardRef = (ref) => {
			const { forwardRef } = this.props;

			if (forwardRef)
			{
				forwardRef(ref);
			}
		};

		/**
		 * @param {boolean} [textStyle]
		 * @returns {*}
		 */
		getAlign(textStyle)
		{
			const { align } = this.props;

			if (textStyle)
			{
				return ALIGN[align] ? align : 'left';
			}

			return ALIGN[align] || ALIGN.left;
		}

		getFieldProps()
		{
			const props = {
				testId: this.getTestId(this.isEmpty() ? 'placeholder' : 'value'),
				ref: (ref) => {
					this.#handleOnForwardRef(ref);
					this.contentFieldRef = ref;
				},
				multiline: this.isMultiline(),
				focus: this.#isFocused(),
				enable: this.isEnable(),
				size: this.getTextSize(),
				value: this.getValue(),
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				keyboardType: this.getKeyboardType(),
				onChangeText: this.handleOnChangeText,
				placeholder: this.getPlaceholder(),
				placeholderTextColor: this.getPlaceholderTextColor(),
				style: this.getFieldStyle(),
				enableKeyboardHide: this.isEnableKeyboardHide(),
				showBBCode: this.shouldShowBBCode(),
				isScrollEnabled: this.isScrollEnabled(),
				onSelectionChange: this.handleOnSelectionChange,
				onCursorPositionChange: this.handleOnSelectionChange,
			};

			if (!this.isEnableLineBreak())
			{
				props.onSubmitEditing = this.handleOnSubmit;
			}

			return props;
		}

		getErrorColor()
		{
			return Color.accentMainAlert;
		}

		/**
		 * @returns {string}
		 */
		getValidationErrorMessage()
		{
			return '';
		}

		static getIconSize()
		{
			return ICON_SIZE;
		}

		static getIconContentSize()
		{
			return ICON_CONTENT_SIZE;
		}

		getLeftContent()
		{
			const { leftContent } = this.props;

			return leftContent;
		}

		getRightContent()
		{
			const { rightContent } = this.props;

			return rightContent;
		}

		getRightStickContent()
		{
			const { rightStickContent } = this.props;

			return rightStickContent;
		}

		/**
		 * @returns {InputMode}
		 */
		getMode()
		{
			const { mode } = this.props;

			return InputMode.resolve(mode, InputMode.STROKE);
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		isEmpty()
		{
			return isEmpty(this.getValue());
		}

		isError()
		{
			const { error } = this.props;

			return Boolean(error);
		}

		isErase()
		{
			const { erase } = this.props;

			return Boolean(erase);
		}

		isIOS()
		{
			return Application.getPlatform() === 'ios';
		}

		isMounted()
		{
			return this.mounted;
		}

		/**
		 * @protected
		 */
		isEnableLineBreak()
		{
			const { enableLineBreak } = this.props;

			return Boolean(enableLineBreak);
		}

		shouldShowBBCode()
		{
			return Boolean(this.props.showBBCode);
		}

		focus = () => {
			if (this.isReadOnly())
			{
				this.handleOnClick();

				return;
			}

			if (!this.contentFieldRef && !this.isMounted())
			{
				this.actionsAfterMounted.push(this.focus);
			}
			else if (this.contentFieldRef)
			{
				this.contentFieldRef.focus();
			}
		};

		/**
		 * @deprecated
		 */
		setFocused()
		{
			this.focus();
		}

		blur = () => {
			this.contentFieldRef?.blur({ hideKeyboard: true });
		};
	}

	Input.defaultProps = {
		disabled: false,
		readOnly: false,
		locked: false,
		edit: false,
		error: false,
		dropdown: false,
		required: false,
		erase: false,
		multiline: false,
		focus: false,
		enableKeyboardHide: false,
		enableLineBreak: false,
		showBBCode: true,
	};

	Input.propTypes = {
		testId: PropTypes.string.isRequired,
		forwardRef: PropTypes.func,
		value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		placeholder: PropTypes.string,
		label: PropTypes.string,
		size: PropTypes.instanceOf(InputSize),
		design: PropTypes.instanceOf(InputDesign),
		mode: PropTypes.instanceOf(InputMode),
		focus: PropTypes.bool,
		disabled: PropTypes.bool,
		readOnly: PropTypes.bool,
		multiline: PropTypes.bool,
		locked: PropTypes.bool,
		edit: PropTypes.bool,
		dropdown: PropTypes.bool,
		required: PropTypes.bool,
		error: PropTypes.bool,
		enableKeyboardHide: PropTypes.bool,
		enableLineBreak: PropTypes.bool,
		showBBCode: PropTypes.bool,
		errorText: PropTypes.string,
		keyboardType: PropTypes.string,
		autoCapitalize: PropTypes.string,
		align: PropTypes.oneOf(Object.keys(ALIGN)),
		backgroundColor: PropTypes.instanceOf(Color),
		style: PropTypes.object,
		leftContent: PropTypes.oneOfType([PropTypes.func, PropTypes.object, PropTypes.instanceOf(Icon)]),
		onClickLeftContent: PropTypes.func,
		rightContent: PropTypes.oneOfType([PropTypes.object, PropTypes.instanceOf(Icon)]),
		onClickRightContent: PropTypes.func,
		rightStickContent: PropTypes.oneOfType([PropTypes.object, PropTypes.instanceOf(Icon)]),
		onClickRightStickContent: PropTypes.func,
		onChange: PropTypes.func,
		onSubmit: PropTypes.func,
		onBlur: PropTypes.func,
		onFocus: PropTypes.func,
		onErase: PropTypes.func,
		onClick: PropTypes.func,
		onError: PropTypes.func,
		onLongClick: PropTypes.func,
		onSelectionChange: PropTypes.func,
		onCursorPositionChange: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {InputProps} props
		 * @returns {Input}
		 */
		Input: (props) => InputVisualDecorator({ component: Input, ...props }),
		Icon,
		InputMode,
		InputSize,
		InputDesign,
		InputClass: Input,
		InputVisualDecorator,
	};
});
