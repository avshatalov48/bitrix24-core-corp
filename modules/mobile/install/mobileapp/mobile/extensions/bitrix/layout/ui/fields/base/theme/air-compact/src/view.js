/**
 * @module layout/ui/fields/base/theme/air-compact/src/view
 */
jn.define('layout/ui/fields/base/theme/air-compact/src/view', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { Chip } = require('ui-system/blocks/chips/chip');
	const { IconView } = require('ui-system/blocks/icon');
	const { SafeImage } = require('layout/ui/safe-image');
	const { withCurrentDomain } = require('utils/url');
	const { Haptics } = require('haptics');

	const ICON_SIZE = 20;

	/**
	 * @param {string} testId
	 * @param {boolean} empty
	 * @param {boolean} [hasError=false]
	 * @param {boolean} [readOnly=false]
	 * @param {Avatar} avatar
	 * @param {boolean} multiple
	 * @param {object} [leftIcon]
	 * @param {string} [leftIcon.icon]
	 * @param {string} [leftIcon.uri]
	 * @param {string} [defaultLeftIcon]
	 * @param {string} text
	 * @param {string} textMultiple
	 * @param {function} onClick
	 * @param {number} count
	 * @param {boolean} showLoader
	 * @param {boolean} [wideMode=false]
	 * @param {string|null} [colorScheme=null]
	 * @param {function} [bindContainerRef]
	 * @param {number} [isRestricted=false]
	 */
	const AirCompactThemeView = ({
		testId,
		empty,
		avatar,
		hasError = false,
		readOnly = false,
		multiple,
		text,
		textMultiple,
		onClick,
		count,
		showLoader = false,
		leftIcon = {},
		defaultLeftIcon,
		wideMode = false,
		colorScheme = null,
		bindContainerRef,
		isRestricted = false,
	}) => {
		const leftContent = [];

		/** @type {{ content: Color, border: Color }} */
		let colors = ColorScheme.resolve(ColorScheme.DEFAULT);

		if (colorScheme)
		{
			colors = ColorScheme.resolve(colorScheme);
		}
		else if (readOnly)
		{
			colors = ColorScheme.resolve(ColorScheme.READONLY);
		}
		else if (empty)
		{
			colors = ColorScheme.resolve(ColorScheme.EMPTY);
		}
		else if (hasError)
		{
			colors = ColorScheme.resolve(ColorScheme.ERROR);
		}

		if (showLoader)
		{
			leftContent.push(
				Loader({
					style: {
						width: ICON_SIZE,
						height: ICON_SIZE,
					},
					tintColor: Color.base3.toHex(),
					animating: true,
					size: 'small',
				}),
			);
		}
		else if (avatar)
		{
			leftContent.push(avatar);
		}
		else if (leftIcon.icon)
		{
			leftContent.push(
				IconView({
					testId: `${testId}_COMPACT_FIELD_ICON`,
					icon: leftIcon.icon,
					color: (isRestricted ? Color.base1 : colors.content),
					size: ICON_SIZE,
				}),
			);
		}
		else if (leftIcon.uri)
		{
			leftContent.push(
				SafeImage({
					testId: `${testId}_COMPACT_FIELD_ICON`,
					uri: withCurrentDomain(leftIcon.uri),
					resizeMode: 'cover',
					style: {
						width: ICON_SIZE,
						height: ICON_SIZE,
						borderRadius: ICON_SIZE / 2,
						opacity: readOnly ? 0.22 : 1,
					},
				}),
			);
		}
		else if (defaultLeftIcon)
		{
			leftContent.push(
				IconView({
					testId: `${testId}_COMPACT_DEFAULT_FIELD_ICON`,
					icon: defaultLeftIcon,
					color: (isRestricted ? Color.base1 : colors.content),
					size: ICON_SIZE,
				}),
			);
		}

		const textToShow = (
			multiple && textMultiple && count > 0
				? textMultiple.replace('#COUNT#', String(count))
				: text
		);

		return Chip({
			testId: `${testId}_COMPACT_FIELD`,
			style: {
				maxWidth: wideMode ? undefined : 250,
			},
			onClick: () => {
				if (readOnly)
				{
					Haptics.notifyWarning();
				}

				if (onClick)
				{
					onClick();
				}
			},
			ref: bindContainerRef,
			indent: {
				left: Indent.M,
				right: Indent.L,
			},
			backgroundColor: Color.bgContentPrimary,
			borderColor: colors.border,
			children: [
				View(
					{
						testId: `${testId}_COMPACT_FIELD_CONTENT`,
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							flexShrink: 2,
						},
					},
					...leftContent,
					Text4({
						testId: `${testId}_COMPACT_FIELD_TEXT`,
						text: textToShow,
						color: colors.content,
						style: {
							marginLeft: Indent.XS.toNumber(),
							flexShrink: 2,
						},
						numberOfLines: 1,
						ellipsize: 'middle',
					}),
				),
			],
		});
	};

	const ColorScheme = {
		READONLY: 'READONLY',
		EMPTY: 'EMPTY',
		ERROR: 'ERROR',
		DEFAULT: 'DEFAULT',

		/**
		 * @typedef {Object} ColorSchemeObject
		 * @property {Color} content
		 * @property {Color} border
		 *
		 * @param {string} value
		 * @return {ColorSchemeObject}
		 */
		resolve(value)
		{
			const key = this[value] ?? 'DEFAULT';
			const values = {
				READONLY: {
					content: Color.base6,
					border: Color.bgSeparatorPrimary,
				},
				EMPTY: {
					content: Color.base3,
					border: Color.bgSeparatorPrimary,
				},
				ERROR: {
					content: Color.accentMainAlert,
					border: Color.accentSoftRed1,
				},
				DEFAULT: {
					content: Color.accentMainPrimary,
					border: Color.accentSoftBorderBlue,
				},
			};

			return values[key];
		},
	};

	module.exports = {
		AirCompactThemeView,
		ColorScheme,
	};
});
