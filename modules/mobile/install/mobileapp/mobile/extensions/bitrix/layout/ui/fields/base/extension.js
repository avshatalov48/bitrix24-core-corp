/**
 * @module layout/ui/fields/base
 */
jn.define('layout/ui/fields/base', (require, exports, module) => {

	const { transition } = require('animation');
	const { Haptics } = require('haptics');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { PureComponent } = require('layout/pure-component');
	const { throttle, debounce } = require('utils/function');
	const { isEqual, mergeImmutable, isEmpty } = require('utils/object');
	const { capitalize, stringify } = require('utils/string');
	const { isNil } = require('utils/type');
	const { chevronDown, chevronUp } = require('assets/common');

	const ERROR_TEXT_COLOR = '#ff5752';
	const TOOLTIP_COLOR = '#e89b06';

	const TitlePosition = {
		top: 'top',
		left: 'left',
	};

	const fadeOut = (ref) => transition(ref, {
		opacity: 0,
		duration: 50,
		option: 'easeIn',
	})();
	const fadeIn = (ref) => transition(ref, {
		opacity: 1,
		duration: 50,
		option: 'easeOut',
	})();

	const tooltipTriangle = (color) => `<svg width="5" height="4" viewBox="0 0 5 4" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path fill-rule="evenodd" clip-rule="evenodd" d="M2.60352 2.97461L0 0V4H4.86133C3.99634 4 3.17334 3.62695 2.60352 2.97461Z" fill="${color}"/>
	</svg>`;

	/**
	 * @class BaseField
	 * @abstract
	 */
	class BaseField extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				focus: (props.focus || false),
				errorMessage: null,
				tooltipMessage: null,
				tooltipColor: TOOLTIP_COLOR,
				showAll: false,
			};

			this.uid = props.uid || Random.getString();
			this.preparedValue = null;

			this.handleContentClick = this.handleContentClick.bind(this);
			this.setFocusInternal = throttle(this.setFocusInternal, 300, this);

			this.debouncedValidation = debounce(this.validate, 300, this);

			this.customContentClickHandler = null;
			this.customValidation = null;

			this.fieldContainerRef = null;
			this.showHideButton = false;
		}

		componentDidMount()
		{
			if (this.state.focus)
			{
				FocusManager.setFocusedField(this);

				if (this.props.onFocusIn)
				{
					this.props.onFocusIn();
				}

				void this.handleAdditionalFocusActions();
			}
		}

		componentWillReceiveProps(newProps)
		{
			this.preparedValue = null;

			if (this.needToValidateCurrentTick(newProps))
			{
				this.debouncedValidation(false);
			}

			if (
				newProps.hasOwnProperty('focus')
				&& newProps.focus === true
				&& this.state.focus === false
			)
			{
				this.state.focus = true;
				FocusManager.setFocusedField(this);

				if (newProps.onFocusIn)
				{
					newProps.onFocusIn();
				}

				// componentDidUpdate still doesn't work correctly on Android, so we use this workaround
				setTimeout(() => this.handleAdditionalFocusActions(), 0/*30*/);
			}
		}

		get testId()
		{
			return this.props.testId;
		}

		get showAllFromProps()
		{
			return BX.prop.getBoolean(this.getConfig(), 'showAll', false);
		}

		isLogSuppressed()
		{
			return this.hasNestedFields();
		}

		useHapticOnChange()
		{
			return false;
		}

		hasKeyboard()
		{
			return false;
		}

		needToValidateCurrentTick({ readOnly, value })
		{
			if (readOnly)
			{
				return false;
			}

			return !isEqual(this.props.value, value);
		}

		isMultiple()
		{
			return BX.prop.getBoolean(this.props, 'multiple', false);
		}

		isReadOnly()
		{
			if (this.isDisabled())
			{
				return true;
			}

			return BX.prop.getBoolean(this.props, 'readOnly', false);
		}

		isEditable()
		{
			return BX.prop.getBoolean(this.props, 'editable', false);
		}

		isHidden()
		{
			return BX.prop.getBoolean(this.props, 'hidden', false);
		}

		isRequired()
		{
			return BX.prop.getBoolean(this.props, 'required', false);
		}

		showLeftIcon()
		{
			return BX.prop.getBoolean(this.props, 'showLeftIcon', true);
		}

		showRequired()
		{
			return BX.prop.getBoolean(this.props, 'showRequired', true);
		}

		showTitle()
		{
			return BX.prop.getBoolean(this.props, 'showTitle', true);
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', true);
		}

		isDisabled()
		{
			return BX.prop.getBoolean(this.props, 'disabled', false);
		}

		isLeftTitlePosition()
		{
			return (BX.prop.getString(this.props, 'titlePosition', TitlePosition.top) === TitlePosition.left);
		}

		hasHiddenEmptyView()
		{
			return BX.prop.getBoolean(this.props, 'hasHiddenEmptyView', false);
		}

		shouldAnimateOnFocus()
		{
			return this.isEmptyEditable() && this.hasKeyboard();
		}

		showBorder()
		{
			return BX.prop.getBoolean(this.props, 'showBorder', false);
		}

		hasSolidBorderContainer()
		{
			return BX.prop.getBoolean(this.props, 'hasSolidBorderContainer', false);
		}

		getExternalWrapperBorderColor()
		{
			const styles = this.getConfig().styles || {};

			const borderColor = BX.prop.getString(styles, 'externalWrapperBorderColor', null);
			const borderColorFocused = BX.prop.getString(styles, 'externalWrapperBorderColorFocused', null);

			if (borderColorFocused !== null && this.state.focus)
			{
				return borderColorFocused;
			}

			return borderColor;
		}

		getExternalWrapperBackgroundColor()
		{
			const styles = this.getConfig().styles || {};

			return BX.prop.getString(styles, 'externalWrapperBackgroundColor', null);
		}

		getConfig()
		{
			const config = BX.prop.getObject(this.props, 'config', {});

			return {
				...config,
				parentWidget: BX.prop.get(config, 'parentWidget', undefined),
			};
		}

		getContext()
		{
			return BX.prop.getObject(this.props, 'context', {});
		}

		getParentWidget()
		{
			return this.getConfig().parentWidget;
		}

		getPageManager()
		{
			return this.getParentWidget() || PageManager;
		}

		getId()
		{
			return this.props.id;
		}

		hasNestedFields()
		{
			return false;
		}

		getParent()
		{
			return this.props.parent;
		}

		handleChange(...values)
		{
			if (this.useHapticOnChange())
			{
				Haptics.impactLight();
			}

			if (typeof this.props.onChange === 'function')
			{
				return this.props.onChange(...values);
			}

			return Promise.resolve();
		}

		onBeforeHandleChange()
		{
			if (typeof this.props.onBeforeChange === 'function')
			{
				return this.props.onBeforeChange();
			}

			return Promise.resolve();
		}

		getStyles()
		{
			let compiledStyles = this.getDefaultStyles();
			const { styles, deepMergeStyles } = this.getConfig();

			if (styles)
			{
				compiledStyles = { ...compiledStyles, ...styles };
			}

			if (deepMergeStyles)
			{
				compiledStyles = mergeImmutable(compiledStyles, deepMergeStyles);
			}

			return compiledStyles;
		}

		getTooltipColor()
		{
			return this.state.tooltipColor;
		}

		getDefaultStyles()
		{
			const base = {
				flex: 1,
				fontSize: 16,
				fontWeight: '400',
				color: '#333333',
			};
			const emptyValue = {
				...base,
				color: '#a8adb4',
			};
			const value = {
				...base,
				color: this.isDisabled() ? '#828b95' : '#333333',
			};

			let styles = this.getBaseFieldStyles();

			if (this.isLeftTitlePosition())
			{
				styles = mergeImmutable(styles, this.getLeftTitleStyles());
			}
			else if (this.hasHiddenEmptyView())
			{
				styles = mergeImmutable(styles, this.getHiddenEmptyFieldStyles());
			}

			return {
				...styles,
				base,
				emptyValue,
				value,
			};
		}

		getBaseFieldStyles()
		{
			const isReadOnly = this.isReadOnly();

			return {
				externalWrapper: this.getExternalWrapperStyle(),
				wrapper: {
					paddingTop: 8,
					paddingBottom: this.hasErrorOrTooltip() ? 5 : 12,
				},
				readOnlyWrapper: {
					paddingTop: 8,
					paddingBottom: this.hasErrorOrTooltip() ? 5 : 12,
				},
				contentWrapper: {
					flexDirection: 'row',
					alignItems: 'center',
				},
				innerWrapper: {
					flexDirection: 'column',
					flex: 1,
					flexShrink: 2,
				},
				title: {
					marginBottom: 2,
					color: this.getTitleColor(),
					fontSize: 10,
					fontWeight: isReadOnly ? '400' : '500',
					flexShrink: 2,
				},
				container: {
					flexDirection: 'row',
					alignItems: 'center',
				},
				tooltipWrapper: {
					marginLeft: 1,
					marginTop: -2,
					marginBottom: 2,
				},
				tooltipIcon: {
					width: 5,
					height: 5,
				},
				tooltipContainer: {
					marginTop: -1,
					paddingLeft: 6,
					paddingRight: 9,
					backgroundColor: this.getTooltipColor(),
					borderTopRightRadius: 8,
					borderBottomLeftRadius: 8,
					alignSelf: 'flex-start',
				},
				errorText: {
					color: '#fff',
					fontSize: 13,
				},
				iconBeforeTitle: {
					width: 12,
					height: 12,
					marginRight: 5,
				},
				iconAfterTitle: {
					width: 12,
					height: 12,
					marginLeft: 5,
				},
			};
		}

		getExternalWrapperStyle()
		{
			if (this.showBorder())
			{
				if (this.hasSolidBorderContainer())
				{
					return {
						marginHorizontal: 6,
						paddingHorizontal: 16,
						paddingVertical: 9,
						backgroundColor: this.getExternalWrapperBackgroundColor(),
						borderRadius: 6,
						borderColor: this.getExternalWrapperBorderColor(),
						borderWidth: this.showBorder() ? 1 : 0,
					};
				}

				return {
					marginHorizontal: 16,
					backgroundColor: this.getExternalWrapperBackgroundColor(),
					borderBottomWidth: this.showBorder() ? 1 : 0,
					borderBottomColor: this.getExternalWrapperBorderColor(),
				};
			}

			return {
				marginHorizontal: 0,
				paddingHorizontal: 0,
				paddingVertical: 0,
				backgroundColor: null,
				borderRadius: 0,
				borderColor: null,
				borderWidth: 0,
				borderBottomWidth: 0,
				borderBottomColor: null,
			};
		}

		getHiddenEmptyFieldStyles()
		{
			const isEmptyEditable = this.isEmptyEditable();
			const isFocusedEmptyEditable = isEmptyEditable && !this.state.focus;
			const paddingBottomWithoutError = isEmptyEditable ? 21 : 14;

			return {
				wrapper: {
					justifyContent: isFocusedEmptyEditable ? 'center' : 'flex-start',
					paddingTop: isEmptyEditable ? 14 : 8,
					paddingBottom: this.hasErrorOrTooltip() ? 5 : paddingBottomWithoutError,
				},
				readOnlyWrapper: {
					justifyContent: isFocusedEmptyEditable ? 'center' : 'flex-start',
				},
				title: {
					fontSize: isEmptyEditable ? 16 : 10,
					marginBottom: this.isEmpty() ? 0 : 2,
					fontWeight: '400',
				},
				container: {
					opacity: isFocusedEmptyEditable ? 0 : 1,
					height: isFocusedEmptyEditable ? 0 : null,
				},
			};
		}

		getLeftTitleStyles()
		{
			return {
				wrapper: {
					paddingBottom: 5,
				},
				readOnlyWrapper: {
					paddingBottom: 5,
				},
				title: {
					marginBottom: 0,
				},
				container: {
					flex: 1,
				},
			};
		}

		isEmptyEditable()
		{
			return !this.isReadOnly() && this.isEmpty() && this.hasHiddenEmptyView();
		}

		getTitleColor()
		{
			if (this.state.focus && this.canFocusTitle())
			{
				return '#0b66c3';
			}

			if (this.hasErrorMessage())
			{
				return ERROR_TEXT_COLOR;
			}

			return '#a8adb4';
		}

		render()
		{
			if (this.isHidden())
			{
				return null;
			}

			let leftIcons = null;

			if (this.showLeftIcon())
			{
				leftIcons = this.renderLeftIcons();
			}

			this.styles = this.getStyles();

			const titleContent = this.isLeftTitlePosition()
				? this.renderLeftTitleContent()
				: this.renderTopTitleContent();

			return View(
				{
					style: this.styles.externalWrapper,
					ref: (ref) => this.fieldContainerRef = ref,
				},
				View(
					{
						testId: `${this.testId}_FIELD`,
						onClick: this.getContentClickHandler(),
						style: (this.isReadOnly() ? this.styles.readOnlyWrapper : this.styles.wrapper),
					},
					View(
						{
							style: this.styles.contentWrapper,
						},
						leftIcons,
						titleContent,
						this.renderRightIcons(),
						this.renderAdditionalContent(),
					),
				),
				this.hasErrorMessage() && this.renderError(),
				!this.hasErrorMessage() && this.hasTooltipMessage() && this.renderTooltip(),
			);
		}

		renderLeftTitleContent()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				View(
					{
						testId: `${this.testId}_TITLE`,
						style: {
							flexDirection: 'row',
							width: 105,
						},
					},
					(this.showTitle() && this.renderTitle()),
					(this.showTitle() && this.renderRequired()),
				),
				this.renderContentBlock(),
			);
		}

		renderTopTitleContent()
		{
			return View(
				{
					style: this.styles.innerWrapper,
				},
				!this.showTitle() ? null : View(
					{
						testId: `${this.testId}_TITLE`,
						style: {
							flexDirection: 'row',
						},
					},
					this.renderTitle(),
					this.renderRequired(),
				),
				this.renderContentBlock(),
			);
		}

		getContentClickHandler()
		{
			if (this.isReadOnly() && !this.props.onContentClick && !this.customContentClickHandler)
			{
				return null;
			}

			return this.handleContentClick;
		}

		handleContentClick()
		{
			if (this.props.onContentClick)
			{
				this.props.onContentClick();
			}

			if (this.customContentClickHandler)
			{
				this.customContentClickHandler();
			}
			else
			{
				this.focus();
			}
		}

		isPossibleToFocus()
		{
			return !this.isReadOnly();
		}

		/**
		 * @public
		 * @return {Promise<never>|Promise<void>|*}
		 */
		focus()
		{
			if (this.isPossibleToFocus())
			{
				return this.setFocus();
			}

			return Promise.reject();
		}

		/**
		 * @protected
		 * @return {Promise<void>|*}
		 */
		setFocus()
		{
			if (this.state.focus !== true)
			{
				return this.setFocusInternal();
			}

			return Promise.resolve();
		}

		/**
		 * @internal
		 */
		setFocusInternal()
		{
			const setFocusState = () => new Promise((resolve) => {
				let promise = Promise.resolve();

				if (this.shouldAnimateOnFocus())
				{
					promise = promise.then(() => fadeOut(this.fieldContainerRef));
				}

				promise.then(() => this.setState({ focus: true }, () => {
					FocusManager.setFocusedField(this);

					if (this.shouldAnimateOnFocus())
					{
						void fadeIn(this.fieldContainerRef);
					}

					const { onFocusIn } = this.props;
					if (onFocusIn)
					{
						onFocusIn();
					}

					resolve();
				}));
			});

			return (
				FocusManager
					.blurFocusedFieldIfHas(this)
					.then(() => setFocusState())
					.then(() => this.handleAdditionalFocusActions())
			);
		}

		handleAdditionalFocusActions()
		{
			return Promise.resolve();
		}

		removeFocus()
		{
			if (this.state.focus === false)
			{
				return Promise.resolve();
			}

			const shouldAnimateOnBlur = this.shouldAnimateOnFocus();

			return new Promise((resolve) => {
				let promise = Promise.resolve();

				if (shouldAnimateOnBlur)
				{
					promise = promise.then(() => fadeOut(this.fieldContainerRef));
				}

				promise.then(() => this.setState({ focus: false }, () => {
					if (shouldAnimateOnBlur)
					{
						void fadeIn(this.fieldContainerRef);
					}

					const { onFocusOut } = this.props;
					if (onFocusOut)
					{
						onFocusOut();
					}

					this.debouncedValidation();
					resolve();
				}));
			});
		}

		getValue()
		{
			if (this.preparedValue === null)
			{
				this.preparedValue = this.prepareValue(this.props.value);
			}

			return this.preparedValue;
		}

		prepareValue(value)
		{
			if (this.isMultiple())
			{
				if (!Array.isArray(value))
				{
					if (isNil(value))
					{
						value = [];
					}
					else
					{
						value = [value];
					}
				}

				return value.map((value) => this.prepareSingleValue(value));
			}

			return this.prepareSingleValue(value);
		}

		prepareSingleValue(value)
		{
			return value;
		}

		getValueWhileReady()
		{
			return Promise.resolve(this.getValue());
		}

		isEmpty()
		{
			const value = this.getValue();

			if (this.isMultiple())
			{
				return (
					value.length === 0
					|| value.every((value) => this.isEmptyValue(value))
				);
			}

			return this.isEmptyValue(value);
		}

		isEmptyValue(value)
		{
			return stringify(value) === '';
		}

		isValid()
		{
			const error = this.getValidationError() || this.getValidationErrorOnFocusOut();

			return !error;
		}

		validate(checkFocusOut = true)
		{
			if (this.isReadOnly())
			{
				return true;
			}

			const error = this.getValidationError();
			if (error)
			{
				this.setError(error);

				return false;
			}

			if (checkFocusOut || this.hasErrorMessage())
			{
				const error = this.getValidationErrorOnFocusOut();
				if (error)
				{
					this.setError(error);

					return false;
				}
			}

			this.clearError();

			if (!checkFocusOut)
			{
				this.checkTooltip();
			}

			return true;
		}

		getValidationError()
		{
			if (!this.checkRequired())
			{
				return this.props.requiredErrorMessage || BX.message('FIELDS_BASE_REQUIRED_ERROR');
			}

			if (this.hasCustomValidation())
			{
				return this.getCustomValidationError();
			}

			return null;
		}

		getValidationErrorOnFocusOut()
		{
			return null;
		}

		checkRequired()
		{
			if (!this.isRequired())
			{
				return true;
			}

			return !this.isEmpty();
		}

		hasCustomValidation()
		{
			return Boolean(this.props.customValidation);
		}

		getCustomValidationError()
		{
			return this.props.customValidation(this);
		}

		hasErrorMessage()
		{
			return typeof this.state.errorMessage === 'string' && this.state.errorMessage.length;
		}

		hasErrorOrTooltip()
		{
			return this.hasErrorMessage() || this.hasTooltipMessage();
		}

		setError(errorMessage)
		{
			if (this.state.errorMessage !== errorMessage)
			{
				this.setState({ errorMessage, tooltipColor: ERROR_TEXT_COLOR, tooltipMessage: null });
			}
		}

		clearError()
		{
			if (this.state.errorMessage !== null)
			{
				this.setState({ errorMessage: null });
			}
		}

		renderTooltip()
		{
			const tooltipMessage = this.getTooltipMessage();
			return View(
				{
					style: this.styles.tooltipWrapper,
				},
				Image(
					{
						style: this.styles.tooltipIcon,
						svg: {
							content: tooltipTriangle(this.getTooltipColor()),
						},
					},
				),
				View(
					{
						style: this.styles.tooltipContainer,
					},
					typeof tooltipMessage === 'string'
						? Text(
							{
								style: this.styles.errorText,
								text: this.getTooltipMessage(),
								numberOfLines: 1,
								ellipsize: 'end',
							})
						: tooltipMessage,
				),
			);
		}

		checkTooltip()
		{
			const { tooltip } = this.props;

			if (tooltip)
			{
				tooltip(this).then(({ message, color }) => {
					this.setTooltip(message, color);
				});
			}
		}

		clearTooltip()
		{
			const emptyMessage = { tooltipMessage: null };

			if (this.getParent())
			{
				this.getParent().updateTooltip(emptyMessage);
			}
			else
			{
				this.updateTooltip(emptyMessage);
			}
		}

		hasTooltipMessage()
		{
			return (typeof this.state.tooltipMessage === 'string' && this.state.tooltipMessage.length)
				|| this.state.tooltipMessage instanceof LayoutComponent;
		}

		getTooltipMessage()
		{
			return this.state.tooltipMessage;
		}

		setTooltip(tooltipMessage, tooltipColor)
		{
			const newTooltip = { tooltipMessage, tooltipColor };
			if (this.getParent())
			{
				this.getParent().updateTooltip(newTooltip);
			}
			else
			{
				this.updateTooltip(newTooltip);
			}
		}

		updateTooltip({ tooltipMessage, tooltipColor })
		{
			const result = {};

			if (this.state.tooltipMessage !== tooltipMessage)
			{
				result.tooltipMessage = tooltipMessage;
			}

			if (typeof tooltipColor !== 'undefined' && this.state.tooltipColor !== tooltipColor)
			{
				result.tooltipColor = tooltipColor;
			}

			if (!isEmpty(result))
			{
				this.setState(result);
			}
		}

		renderTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.hasIconBeforeTitle() && Image({
					style: this.styles.iconBeforeTitle,
					svg: this.getIconBeforeTitle(),
					resizeMode: 'contain',
				}),
				Text({
					testId: `${this.testId}_NAME`,
					style: this.styles.title,
					numberOfLines: 1,
					ellipsize: 'end',
					text: (
						this.isEmptyEditable() && this.hasCapitalizeTitleInEmpty()
							? capitalize(this.getTitleText(), true)
							: this.getTitleText().toLocaleUpperCase(env.languageId)
					),
				}),
				this.hasIconAfterTitle() && Image({
					style: this.styles.iconAfterTitle,
					svg: this.getIconAfterTitle(),
					resizeMode: 'contain',
				}),
			);
		}

		getTitleText()
		{
			return typeof this.props.title === 'string'
				? this.props.title
				: BX.message('FIELDS_BASE_EMPTY_TITLE');
		}

		hasCapitalizeTitleInEmpty()
		{
			return true;
		}

		/**
		 * @returns {boolean}
		 */
		hasIconBeforeTitle()
		{
			return Boolean(this.getIconBeforeTitle());
		}

		/**
		 * @returns {boolean}
		 */
		hasIconAfterTitle()
		{
			return Boolean(this.getIconAfterTitle());
		}

		/**
		 * @returns {Object|null}
		 */
		getIconBeforeTitle()
		{
			return this.getTitleIcon('before');
		}

		/**
		 * @returns {Object|null}
		 */
		getIconAfterTitle()
		{
			return this.getTitleIcon('after');
		}

		/**
		 *
		 * @param {String} position
		 * @returns {Object}
		 */
		getTitleIcon(position)
		{
			const icon = BX.prop.getObject(
				this.getConfig(),
				'titleIcon',
				{},
			);

			if (
				icon[position]
				&& icon[position].uri
				&& icon[position].uri.indexOf(currentDomain) !== 0
			)
			{
				icon[position].uri = `${currentDomain}${icon[position].uri}`;
			}

			return (icon[position] || null);
		}

		renderRequired()
		{
			return (
				this.isRequired() && this.showRequired() && !this.isReadOnly()
					? Text(
						{
							testId: `${this.testId}_REQUIRED`,
							style: {
								...this.styles.title,
								color: ERROR_TEXT_COLOR,
							},
							text: '*',
						},
					)
					: null
			);
		}

		renderContentBlock()
		{
			return View(
				{
					testId: `${this.testId}_CONTENT`,
					style: this.styles.container,
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			if (this.isReadOnly())
			{
				return this.renderReadOnlyContent();
			}

			return this.renderEditableContent();
		}

		renderAdditionalContent()
		{
			if (this.props.renderAdditionalContent)
			{
				return this.props.renderAdditionalContent();
			}

			return null;
		}

		renderReadOnlyContent()
		{
			throw new Error('Method "renderReadOnlyContent" must be implemented.');
		}

		renderEditableContent()
		{
			throw new Error('Method "renderEditableContent" must be implemented.');
		}

		renderEmptyContent()
		{
			return Text({
				style: this.styles.emptyValue,
				text: this.getReadOnlyEmptyValue(),
			});
		}

		getReadOnlyEmptyValue()
		{
			return this.props.emptyValue || BX.message('FIELDS_BASE_EMPTY_VALUE');
		}

		renderError()
		{
			const { errorMessage } = this.state;

			return View(
				{
					style: this.styles.tooltipWrapper,
				},
				Image(
					{
						style: this.styles.tooltipIcon,
						svg: {
							content: tooltipTriangle(ERROR_TEXT_COLOR),
						},
					},
				),
				View(
					{
						style: this.styles.tooltipContainer,
					},
					Text(
						{
							style: this.styles.errorText,
							text: errorMessage,
							numberOfLines: 1,
							ellipsize: 'end',
						},
					),
				),
			);
		}

		renderRightIcons()
		{
			if (!this.shouldShowEditIcon())
			{
				return null;
			}

			if (!this.isReadOnly())
			{
				return this.renderEditIcon();
			}

			return this.renderDefaultIcon();
		}

		renderLeftIcons()
		{
			return null;
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		renderEditIcon()
		{
			if (this.props.editIcon)
			{
				return this.props.editIcon;
			}

			return null;
		}

		isEditRestricted()
		{
			return BX.prop.getBoolean(this.props, 'restrictedEdit', false);
		}

		renderDefaultIcon()
		{
			return null;
		}

		static getExtensionPath()
		{
			return `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields`;
		}

		renderShowAllButton(hiddenFieldsCount = null)
		{
			if (this.showAllFromProps || this.state.showAll || isNil(hiddenFieldsCount) || Number.isInteger(hiddenFieldsCount) && hiddenFieldsCount <= 0)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: 3,
						paddingBottom: 6,
					},
					onClick: () => {
						this.setState({
							showAll: true,
						});
					},
				},
				View(
					{
						style: {
							width: 20,
							height: 20,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Image({
						style: {
							width: 12,
							height: 7,
						},
						resizeMode: 'cover',
						svg: {
							content: chevronDown(),
						},
					}),
				),
				Text({
					style: {
						flex: 1,
						color: '#a8adb4',
						fontSize: 13,
					},
					text: `${BX.message('FIELDS_BASE_SHOW_ALL')} ${this.showAllCount() ? hiddenFieldsCount : ''}`,
				}),
			);
		}

		renderHideButton()
		{
			if (!this.showAllFromProps && this.state.showAll && this.showHideButton)
			{
				return View(
					{
						style: {
							flexDirection: 'row',
							paddingTop: 3,
							paddingBottom: 6,
						},
						onClick: () => {
							this.setState({
								showAll: false,
							});
						},
					},
					View(
						{
							style: {
								width: 20,
								height: 20,
								justifyContent: 'center',
								alignItems: 'center',
							},
						},
						Image({
							style: {
								width: 12,
								height: 7,
							},
							resizeMode: 'cover',
							svg: {
								content: chevronUp(),
							},
						}),
					),
					Text({
						style: {
							flex: 1,
							color: '#a8adb4',
							fontSize: 13,
						},
						text: `${BX.message('FIELDS_BASE_HIDE')}`,
					}),
				);
			}

			return null;
		}

		showAllCount()
		{
			return true;
		}
	}

	module.exports = { BaseField };
});
