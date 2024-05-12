/**
 * @module layout/ui/fields/theme/air/elements/add-button
 */
jn.define('layout/ui/fields/theme/air/elements/add-button', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { IconView } = require('ui-system/blocks/icon');
	const { OutlineIconTypes } = require('assets/icons/types');

	/**
	 * @param {function} onClick
	 * @param {string} text
	 */
	const AddButton = ({
		onClick,
		text,
	}) => {
		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					paddingVertical: Indent.S,
					marginBottom: Indent.M,
				},
				onClick,
			},
			View(
				{
					style: {
						width: 32,
						height: 32,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				IconView({
					icon: OutlineIconTypes.plus,
					iconSize: {
						width: 24,
						height: 24,
					},
					iconColor: Color.base3,
				}),
			),
			Text({
				text,
				style: {
					color: Color.base3,
					fontSize: 14,
					marginLeft: Indent.M,
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
		);
	};

	module.exports = {
		AddButton,
	};
});
