/**
 * @module layout/ui/fields/boolean
 */
jn.define('layout/ui/fields/boolean', (require, exports, module) => {
	const { BlinkView } = require('animation/components/blink-view');
	const { Haptics } = require('haptics');
	const { BaseField } = require('layout/ui/fields/base');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { throttle } = require('utils/function');

	const Mode = {
		SWITCHER: 'switcher',
		ICON: 'icon',
	};

	const COLORS = {
		TEXT_DEFAULT: '#333333',
		BACKGROUND_DEFAULT: '#ffffff',

		DEFAULT_TOGGLE: '#d5d7db',
		ACTIVE_TOGGLE: '#2fc6f6',

		DEFAULT_ICON: '#bdc1c6',
		ACTIVE_ICON: '#ffc34d',
	};

	/**
	 * @class BooleanField
	 */
	class BooleanField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.throttleToggleValue = throttle(this.toggleValue, 500, this);

			if (!this.isReadOnly())
			{
				this.customContentClickHandler = this.throttleToggleValue;
			}
		}

		get activeToggleColor()
		{
			const styles = this.getConfig().styles || {};

			return BX.prop.getString(styles, 'activeToggleColor', COLORS.ACTIVE_TOGGLE);
		}

		get defaultToggleColor()
		{
			const styles = this.getConfig().styles || {};

			return BX.prop.getString(styles, 'defaultToggleColor', COLORS.DEFAULT_TOGGLE);
		}

		get activeIconColor()
		{
			const styles = this.getConfig().styles || {};

			return BX.prop.getString(styles, 'activeIconColor', COLORS.ACTIVE_ICON);
		}

		get defaultIconColor()
		{
			const styles = this.getConfig().styles || {};

			return BX.prop.getString(styles, 'defaultIconColor', COLORS.DEFAULT_ICON);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				mode: BX.prop.getString(config, 'mode', Mode.SWITCHER),
				description: BX.prop.get(config, 'description', ''),
				descriptionYes: BX.prop.getString(config, 'descriptionYes', BX.message('FIELDS_BOOLEAN_YES')),
				descriptionNo: BX.prop.getString(config, 'descriptionNo', BX.message('FIELDS_BOOLEAN_NO')),
				iconUriYes: BX.prop.getString(config, 'iconUriYes', false),
				iconUriNo: BX.prop.getString(config, 'iconUriNo', false),
				showSwitcher: BX.prop.getBoolean(config, 'showSwitcher', !this.isReadOnly()),
			};
		}

		toggleValue()
		{
			Haptics.impactLight();

			const doToggleValue = () => {
				const wasChecked = this.getValue();
				const animations = [];

				if (this.isSwitcherMode())
				{
					animations.push(
						new Promise((resolve) => {
							this.switcherRef.animate({
								duration: 200,
								left: (wasChecked ? 3 : 23),
							}, resolve);
						}),
					);
					animations.push(
						new Promise((resolve) => {
							this.switcherContainerRef.animate({
								duration: 200,
								backgroundColor: (wasChecked ? this.defaultToggleColor : this.activeToggleColor),
							}, resolve);
						}),
					);
				}
				else if (this.isIconMode())
				{
					animations.push(
						new Promise((resolve) => {
							this.iconRef.animate({
								duration: 400,
								backgroundColor: (wasChecked ? this.defaultIconColor : this.activeIconColor),
							}, resolve);
						}),
					);
				}

				if (this.showBooleanFieldDescription && this.isBlinkable())
				{
					animations.push(this.blinkViewRef.blink(!wasChecked));
				}

				return Promise.all(animations).then(() => this.handleChange(!wasChecked));
			};

			return (
				this.onBeforeHandleChange()
					.then(() => FocusManager.blurFocusedFieldIfHas(this))
					.then(() => doToggleValue())
			);
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		prepareSingleValue(value)
		{
			return Boolean(value);
		}

		renderReadOnlyContent()
		{
			const checked = this.getValue();
			const config = this.getConfig();

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
						minHeight: 21,
					},
				},
				(this.isIconMode() ? this.renderIcon() : (config.showSwitcher && this.renderSwitcher())),
				(
					this.isBlinkable()
						? Text({
							style: {
								...this.styles.value,
								color: COLORS.TEXT_DEFAULT,
							},
							text: (checked ? config.descriptionYes : config.descriptionNo),
						})
						: this.renderBooleanFieldDescription()
				),
			);
		}

		renderEditableContent()
		{
			return View(
				{
					style: {
						flexShrink: 2,
						flexDirection: 'row',
						alignItems: 'center',
						minHeight: 21,
					},
					onClick: this.throttleToggleValue,
				},
				(this.isIconMode() ? this.renderIcon() : this.renderSwitcher()),
				this.renderBooleanFieldDescription(),
			);
		}

		renderIcon()
		{
			return View(
				{
					ref: ref => this.iconRef = ref,
					style: this.styles.booleanIconContainer,
				},
				Image({
					style: this.styles.booleanIcon,
					uri: this.getImageUrl(this.getConfig().iconUri),
				}),
			);
		}

		renderSwitcher()
		{
			const checked = this.getValue();

			return View(
				{
					ref: ref => this.switcherContainerRef = ref,
					style: this.styles.switcherContainer(checked),
				},
				View(
					{
						ref: ref => this.switcherRef = ref,
						style: this.styles.switcher(checked),
					},
				),
			);
		}

		renderBooleanFieldDescription()
		{
			if (!this.showBooleanFieldDescription)
			{
				return null;
			}

			const config = this.getConfig();
			const { description: descriptionStyle } = this.getStyles();

			if (!this.isBlinkable())
			{
				return typeof config.description === 'string'
					? Text({
						style: this.styles.description,
						text: config.description,
					})
					: config.description;
			}

			return new BlinkView({
				ref: ref => this.blinkViewRef = ref,
				data: this.getValue(),
				slot: (checked) => {
					return Text({
						style: this.styles.description,
						text: (checked ? config.descriptionYes : config.descriptionNo),
					});
				},
			});
		}

		get showBooleanFieldDescription()
		{
			const { description, descriptionYes, descriptionNo } = this.getConfig();

			return (
				description !== ''
				|| descriptionYes !== BX.message('FIELDS_BOOLEAN_YES')
				|| descriptionNo !== BX.message('FIELDS_BOOLEAN_NO')
			);
		}

		isSwitcherMode()
		{
			return (this.getConfig().mode === Mode.SWITCHER);
		}

		isIconMode()
		{
			return (this.getConfig().mode === Mode.ICON);
		}

		isBlinkable()
		{
			const { description, descriptionYes, descriptionNo } = this.getConfig();

			return (
				description === ''
				&& descriptionYes !== ''
				&& descriptionNo !== ''
			);
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			return encodeURI(imageUrl);
		}

		getDefaultStyles()
		{
			const styles = this.getChildFieldStyles();

			if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return {
				...styles,
				description: {
					flexShrink: 2,
					color: '#333333',
					fontSize: 16,
				},
			};
		}

		getChildFieldStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				switcherContainer: (checked) => ({
					borderRadius: 14,
					backgroundColor: (checked ? this.activeToggleColor : this.defaultToggleColor),
					width: 37,
					height: 17,
					marginRight: 8,
					opacity: (this.isReadOnly() ? 0.5 : 1),
				}),
				switcher: (checked) => ({
					width: 11,
					height: 11,
					backgroundColor: COLORS.BACKGROUND_DEFAULT,
					borderRadius: 8,
					position: 'absolute',
					top: 3,
					left: (checked ? 23 : 3),
				}),
				description: {
					flexShrink: 2,
					color: COLORS.TEXT_DEFAULT,
					fontSize: 16,
					...styles.description,
				},
				booleanIconContainer: {
					width: 24,
					height: 24,
					justifyContent: 'center',
					alignItems: 'center',
					marginRight: 8,
					borderRadius: 12,
					backgroundColor: (this.getValue() ? this.activeIconColor : this.defaultIconColor),
				},
				booleanIcon: {
					width: 12,
					height: 16,
				},
			};
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			const hasErrorMessage = this.hasErrorMessage();

			return {
				...styles,
				wrapper: {
					...styles.wrapper,
					justifyContent: null,
					paddingTop: 12,
					paddingBottom: hasErrorMessage ? 5 : 12,
				},
				readOnlyWrapper: {
					paddingTop: 12,
					paddingBottom: hasErrorMessage ? 5 : 12,
				},
				container: {
					...styles.container,
					opacity: 1,
					height: null,
				},
				title: {
					...styles.title,
					fontSize: 10,
					marginBottom: 2,
				},
			};
		}

		hasCapitalizeTitleInEmpty()
		{
			return false;
		}
	}

	module.exports = {
		BooleanType: 'boolean',
		BooleanMode: Mode,
		BooleanField: props => new BooleanField(props),
	};
});
