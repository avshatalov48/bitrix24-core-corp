/**
 * @module layout/ui/fields/boolean
 */
jn.define('layout/ui/fields/boolean', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { animate } = require('animation');
	const { throttle } = require('utils/function');
	const { Switcher } = require('layout/ui/switcher');
	const { BaseField } = require('layout/ui/fields/base');
	const { BlinkView } = require('animation/components/blink-view');
	const { FocusManager } = require('layout/ui/fields/focus-manager');

	const Mode = {
		SWITCHER: 'switcher',
		ICON: 'icon',
	};

	const COLORS = {
		TEXT_DEFAULT: AppTheme.colors.base1,

		DEFAULT_TOGGLE: AppTheme.colors.base6,
		ACTIVE_TOGGLE: AppTheme.colors.accentBrandBlue,

		DEFAULT_ICON: AppTheme.colors.base3,
		ACTIVE_ICON: AppTheme.colors.accentMainWarning,
	};

	/**
	 * @class BooleanField
	 */
	class BooleanField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.iconRef = null;
			this.blinkViewRef = null;
			this.handleOnChange = throttle(this.handleOnChange, 500, this);
			this.throttleToggleValue = throttle(this.toggleValue, 500, this);

			if (!this.isReadOnly())
			{
				this.customContentClickHandler = this.handleOnChange;
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
			const doToggleValue = () => {
				const wasChecked = this.getValue();
				const animations = [
					animate(this.iconRef, {
						duration: 400,
						backgroundColor: wasChecked ? this.defaultIconColor : this.activeIconColor,
					}),
					...this.getBlinkAnimation(!wasChecked),
				];

				return Promise.all(animations).then(() => this.handleChange(!wasChecked));
			};

			return (
				this.onBeforeHandleChange()
					.then(() => FocusManager.blurFocusedFieldIfHas(this))
					.then(() => doToggleValue())
			);
		}

		getBlinkAnimation(checked)
		{
			if (this.blinkViewRef && this.showBooleanFieldDescription && this.isBlinkable())
			{
				return [this.blinkViewRef.blink(checked)];
			}

			return [];
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
				this.isIconMode()
					? this.renderIcon()
					: config.showSwitcher && this.renderSwitcher(),
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

		handleOnChange()
		{
			Haptics.impactLight();

			if (this.isSwitcherMode())
			{
				const wasChecked = this.getValue();

				this.onBeforeHandleChange()
					.then(() => FocusManager.blurFocusedFieldIfHas(this))
					.then(() => this.handleChange(!wasChecked))
					.catch(console.error);
			}
			else
			{
				this.throttleToggleValue();
			}
		}

		renderEditableContent()
		{
			return View(
				{
					testId: `${this.props.testId}-Container`,
					style: {
						flexShrink: 2,
						flexDirection: 'row',
						alignItems: 'center',
						minHeight: 21,
					},
					onClick: this.handleOnChange,
				},
				this.isIconMode() ? this.renderIcon() : this.renderSwitcher(),
				this.renderBooleanFieldDescription(),
			);
		}

		getImage()
		{
			const imageUri = this.getConfig()?.iconUri;
			if (imageUri)
			{
				return { uri: this.getImageUrl(imageUri) };
			}

			const svg = this.getConfig()?.svg;
			if (svg)
			{
				return { svg };
			}

			return {};
		}

		renderIcon()
		{
			return View(
				{
					ref: (ref) => {
						this.iconRef = ref;
					},
					style: this.styles.booleanIconContainer,
				},
				Image({
					style: this.styles.booleanIcon,
					...this.getImage(),
				}),
			);
		}

		renderSwitcher()
		{
			const checked = this.getValue();

			return new Switcher({
				testId: `${this.testId}-Switcher`,
				checked,
				animations: this.getBlinkAnimation(),
				disabled: this.isReadOnly(),
				trackColor: {
					true: this.activeToggleColor,
					false: this.defaultToggleColor,
				},
			});
		}

		renderBooleanFieldDescription()
		{
			if (!this.showBooleanFieldDescription)
			{
				return null;
			}

			const config = this.getConfig();

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
				ref: (ref) => {
					this.blinkViewRef = ref;
				},
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
			return this.getConfig().mode === Mode.SWITCHER;
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

		getImageUrl(url)
		{
			let imageUrl = url;

			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(String(currentDomain), '');
				imageUrl = (imageUrl.indexOf('http') === 0 ? imageUrl : `${currentDomain}${imageUrl}`);
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
					color: AppTheme.colors.base3,
					fontSize: 16,
				},
			};
		}

		getChildFieldStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
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
					backgroundColor: this.getValue() ? this.activeIconColor : this.defaultIconColor,
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
		BooleanField: (props) => new BooleanField(props),
	};
});
