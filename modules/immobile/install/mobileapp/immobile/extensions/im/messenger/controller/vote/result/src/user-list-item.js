/**
 * @module im/messenger/controller/vote/result/user-list-item
 */
jn.define('im/messenger/controller/vote/result/user-list-item', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { Color, Component, Indent } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { UserName } = require('layout/ui/user/user-name');
	const { Text2, Text5 } = require('ui-system/typography/text');

	const USER_LIST_ITEM_TYPE = 'user';

	class UserListItem extends Base
	{
		/**
		 * @return {View}
		 */
		renderItemContent()
		{
			const {
				id,
				workPosition,
				showBorder,
				fullName,
				avatarSizeOriginal,
			} = this.props.item;
			const testId = `${this.testId}-item-content`;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginLeft: Component.paddingLr.toNumber(),
					},
					testId: `${testId}-container`,
				},
				Avatar({
					id,
					size: 40,
					testId: `${testId}-avatar`,
					name: fullName,
					uri: avatarSizeOriginal,
				}),
				View(
					{
						style: {
							flex: 1,
							height: 70,
							justifyContent: 'center',
							marginLeft: Indent.XL.toNumber(),
							paddingVertical: Indent.XL2.toNumber(),
							borderTopWidth: showBorder ? 1 : 0,
							borderTopColor: Color.bgSeparatorPrimary.toHex(),
						},
						testId: `${testId}-user-name-container`,
					},
					UserName({
						style: {
							marginRight: Indent.XL.toNumber(),
						},
						id,
						textElement: Text2,
						numberOfLines: 1,
						ellipsize: 'end',
						testId: `${testId}-user-name`,
					}),
					workPosition && Text5({
						color: Color.base3,
						numberOfLines: 1,
						ellipsize: 'end',
						text: workPosition,
						testId: `${testId}-work-position-field`,
					}),
				),
			);
		}
	}

	module.exports = { UserListItem, USER_LIST_ITEM_TYPE };
});
