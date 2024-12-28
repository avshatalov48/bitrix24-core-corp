/**
 * @module im/messenger/controller/sidebar/collab/profile-button-view
 */

jn.define('im/messenger/controller/sidebar/collab/profile-button-view', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { Feature: MobileFeature } = require('feature');
	const { withPressed } = require('utils/color');

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
		 * @param {string | Icon} props.icon
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
				icon,
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
						icon,
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

			if (!MobileFeature.isAirStyleSupported())
			{
				return View(
					{
						style: {
							paddingVertical: 12,
							borderWidth: 1,
							width: 86,
							maxWidth: 86,
							paddingHorizontal: 10,
							borderRadius: 12,
							borderColor: Theme.colors.bgSeparatorPrimary,
							flexDirection: 'column',
							alignItems: 'center',
							justifyContent: 'space-between',
							marginRight: 8,
						},
						testId,
						clickable: !disable,
						onClick: () => callback(),
					},
					...cardChildrenList,
				);
			}

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
						backgroundColor: Theme.colors.accentMainAlert,
						borderRadius: 512,
						paddingHorizontal: Indent.S.toNumber(),
						position: 'absolute',
						top: 10,
						left: 48,
						justifyContent: 'center',
						alignItems: 'center',
						borderColor: Theme.colors.baseWhiteFixed,
						borderWidth: 1,
					},
				},
				Text({
					text: counter > 99 ? '99+' : String(counter),
					style: {
						color: Theme.colors.baseWhiteFixed,
						fontSize: 10,
						fontWeight: '500',
					},
				}),
			);
		}
	}

	module.exports = { ProfileButtonView };
});
