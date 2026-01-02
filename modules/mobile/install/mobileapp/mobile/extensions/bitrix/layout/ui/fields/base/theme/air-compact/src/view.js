/**
 * @module layout/ui/fields/base/theme/air-compact/src/view
 */
jn.define('layout/ui/fields/base/theme/air-compact/src/view', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Haptics } = require('haptics');
	const { ChipButton, ChipButtonMode, ChipButtonDesign, Icon } = require('ui-system/blocks/chips/chip-button');

	/**
	 * @typedef AirCompactThemeViewProps
	 * @property {string} testId
	 * @property {boolean} empty
	 * @property {boolean} [hasError=false]
	 * @property {boolean} [readOnly=false]
	 * @property {Avatar | AvatarStack | ProjectAvatar} avatar
	 * @property {boolean} multiple
	 * @property {object} [leftIcon]
	 * @property {string} [leftIcon.icon]
	 * @property {string} [leftIcon.uri]
	 * @property {string} [defaultLeftIcon]
	 * @property {string} text
	 * @property {string} textMultiple
	 * @property {function} onClick
	 * @property {number} count
	 * @property {boolean} showLoader
	 * @property {boolean} [wideMode=false]
	 * @property {string|null} [colorScheme=null]
	 * @property {function} [bindContainerRef]
	 * @property {number} [isRestricted=false]
	 */
	class AirCompactThemeView extends LayoutComponent
	{
		/**
		 * @param {AirCompactThemeViewProps} props
		 */
		constructor(props)
		{
			super(props);

			this.handleOnClick = this.handleOnClick.bind(this);
		}

		render()
		{
			const { bindContainerRef } = this.props;
			const { design } = this.getDesignScheme();

			return ChipButton({
				design,
				rounded: false,
				text: this.getText(),
				content: this.getContent(),
				forwardRef: bindContainerRef,
				mode: ChipButtonMode.OUTLINE,
				testId: this.getTestId('compact-field'),
				icon: this.getLeftIcon(),
				iconColor: this.getIconColor(),
				avatar: this.getAvatar(),
				onClick: this.handleOnClick,
				style: this.getStyle(),
				loading: this.showLoader(),
			});
		}

		getIconColor()
		{
			if (this.isRestricted())
			{
				return Color.base1;
			}

			return null;
		}

		getAvatar()
		{
			const { avatar } = this.props;

			return avatar;
		}

		getLeftIcon()
		{
			const { leftIcon, defaultLeftIcon } = this.props;

			if (leftIcon?.icon)
			{
				return leftIcon.icon;
			}

			return defaultLeftIcon;
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].filter(Boolean).join('-');
		}

		getDesignScheme()
		{
			const schemeParams = {
				design: ChipButtonDesign.PRIMARY,
			};

			if (this.isReadOnly())
			{
				schemeParams.disabled = true;
			}
			else if (this.isEmpty())
			{
				schemeParams.design = ChipButtonDesign.GREY;
			}
			else if (this.hasError())
			{
				schemeParams.design = ChipButtonDesign.ALERT;
			}

			return schemeParams;
		}

		getDesignSchemeColor()
		{
			const { design } = this.getDesignScheme();
			const { color } = design.getStyle(ChipButtonMode.OUTLINE);

			return color;
		}

		isReadOnly()
		{
			const { readOnly = false } = this.props;

			return Boolean(readOnly);
		}

		isEmpty()
		{
			const { empty = false } = this.props;

			return Boolean(empty);
		}

		isRestricted()
		{
			const { isRestricted = false } = this.props;

			return Boolean(isRestricted);
		}

		hasError()
		{
			const { hasError = false } = this.props;

			return Boolean(hasError);
		}

		showLoader()
		{
			const { showLoader = false } = this.props;

			return Boolean(showLoader);
		}

		handleOnClick()
		{
			const { onClick } = this.props;

			if (this.isReadOnly())
			{
				Haptics.notifyWarning();
			}

			onClick?.();
		}

		getStyle()
		{
			const { wideMode = false } = this.props;

			if (wideMode)
			{
				return {
					maxWidth: 250,
				};
			}

			return {};
		}

		getText()
		{
			const { multiple, textMultiple, text, count } = this.props;

			if (text && typeof text !== 'string')
			{
				return text;
			}

			return multiple && textMultiple && count > 0
				? textMultiple.replace('#COUNT#', String(count))
				: text;
		}

		getContent()
		{
			return null;
		}
	}

	const ColorScheme = {
		EMPTY: ChipButtonDesign.GREY,
		ERROR: ChipButtonDesign.ALERT,
		DEFAULT: ChipButtonDesign.PRIMARY,
	};

	AirCompactThemeView.defaultProps = {
		empty: false,
		readOnly: false,
		hasError: false,
		showLoader: false,
		wideMode: false,
		multiple: false,
		isRestricted: false,
	};

	AirCompactThemeView.propTypes = {
		testId: PropTypes.string,
		text: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.object,
		]),
		textMultiple: PropTypes.string,
		empty: PropTypes.bool,
		multiple: PropTypes.bool,
		readOnly: PropTypes.bool,
		hasError: PropTypes.bool,
		wideMode: PropTypes.bool,
		isRestricted: PropTypes.bool,
		showLoader: PropTypes.bool,
		count: PropTypes.number,
		onClick: PropTypes.func,
		avatar: PropTypes.object,
		leftIcon: PropTypes.shape({
			icon: PropTypes.instanceOf(Icon),
		}),
		defaultLeftIcon: PropTypes.instanceOf(Icon),
	};

	module.exports = {
		/**
		 * @param {AirCompactThemeViewProps} props
		 */
		AirCompactThemeView: (props) => new AirCompactThemeView(props),
		AirCompactThemeViewClass: AirCompactThemeView,
		ColorScheme,
	};
});
