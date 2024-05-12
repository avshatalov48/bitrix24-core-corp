/**
 * @module layout/ui/fields/base/theme/air-compact/src/view
 */
jn.define('layout/ui/fields/base/theme/air-compact/src/view', (require, exports, module) => {
	const { Indent, IndentTypes, Color } = require('tokens');
	const { Chip } = require('ui-system/blocks/chip');
	const { IconView } = require('ui-system/blocks/icon');
	const { SafeImage } = require('layout/ui/safe-image');
	const { PlainView } = require('ui-system/blocks/plain-view');

	const ICON_SIZE = 20;

	/**
	 * @param {string} testId
	 * @param {boolean} empty
	 * @param {boolean} multiple
	 * @param {object} leftIcon
	 * @param {string} leftIcon.icon
	 * @param {string} leftIcon.uri
	 * @param {string} text
	 * @param {function} onClick
	 * @param {number} count
	 */
	const AirCompactThemeView = ({
		testId,
		empty,
		multiple,
		leftIcon = {},
		text,
		onClick,
		count,
	}) => {
		const textColor = empty ? Color.base3 : Color.accentMainPrimary;
		const icon = leftIcon.uri
			? SafeImage({
				uri: leftIcon.uri,
				style: {
					width: ICON_SIZE,
					height: ICON_SIZE,
				},
				resizeMode: 'contain',
			})
			: IconView({
				icon: leftIcon.icon,
				color: empty ? Color.base3 : Color.accentMainPrimary,
				size: ICON_SIZE,
			});

		return View(
			{
				style: {
					marginHorizontal: Indent.XS,
					maxWidth: 200,
				},
			},
			Chip({
				testId,
				onClick,
				height: 32,
				indent: {
					left: IndentTypes.M,
					right: IndentTypes.L,
				},
				borderColor: empty
					? Color.bgSeparatorPrimary
					: Color.accentSoftBlue1,
				children: [
					PlainView({
						after: icon,
						indent: IndentTypes.XS,
						text,
						color: textColor,
					}),
					multiple && count > 1 && Text({
						text: `: ${count}`,
						style: {
							color: textColor,
							fontSize: 14,
						},
					}),
				],
			}),
		);
	};

	module.exports = {
		AirCompactThemeView,
	};
});
