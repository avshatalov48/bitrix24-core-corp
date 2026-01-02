/**
 * @module im/messenger/controller/sidebar/collab/profile-button-view
 */

jn.define('im/messenger/controller/sidebar/collab/profile-button-view', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { withPressed } = require('utils/color');

	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { IconView } = require('ui-system/blocks/icon');
	const { Card } = require('ui-system/layout/card');

	const { Theme } = require('im/lib/theme');

	/**
	 * @class CollabProfileBtn
	 */
	class ProfileButtonView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {Object} props
		 * @param {string} props.testId
		 * @param {Object} props.buttonIcon
		 * @param {string | Icon} props.buttonIcon.icon
		 * @param {?string} props.buttonIcon.testId
		 * @param {string} props.text
		 * @param {Function} props.callback
		 * @param {?number} props.counter
		 * @param {boolean} [props.disable=false]
		 */
		constructor(props)
		{
			super(props);
		}

		render()
		{
			const {
				testId,
				buttonIcon,
				text,
				callback,
				disable = false,
				counter,
			} = this.props;

			const backgroundColor = disable ? Theme.colors.bgContentPrimary : withPressed(Theme.colors.bgContentPrimary);

			const cardChildrenList = [
				View(
					{
						style: {
							position: 'relative',
							backgroundColor,
							width: 86,
							maxWidth: 86,
							paddingVertical: 12,
							paddingHorizontal: 14,
							alignItems: 'center',
						},
					},
					IconView({
						size: 32,
						icon: buttonIcon.icon,
						testId: buttonIcon?.testId,
						color: disable ? Theme.color.base7 : Theme.color.base0,
					}),
					Text({
						style: {
							color: disable ? Theme.colors.base7 : Theme.colors.base1,
							fontSize: 13,
							fontWeight: '400',
							marginTop: 4,
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text,
					}),
					counter ? this.renderBadge(counter) : null,
				),
			];

			return Card(
				{
					style: {
						alignItems: 'center',
						width: 86,
						maxWidth: 86,
						marginRight: Indent.M.toNumber(),
					},
					excludePaddingSide: {
						all: true,
					},
					testId,
					border: true,
					onClick: callback,
				},
				...cardChildrenList,
			);
		}

		/**
		 * @param {number} counter
		 */
		renderBadge(counter) {
			return View(
				{
					style: {
						position: 'absolute',
						top: 10,
						left: 48,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				BadgeCounter({
					value: counter > 99 ? '99+' : String(counter),
					design: BadgeCounterDesign.ALERT,
				}),
			);
		}
	}

	module.exports = { ProfileButtonView };
});
