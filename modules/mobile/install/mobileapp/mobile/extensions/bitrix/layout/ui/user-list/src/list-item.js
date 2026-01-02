/**
 * @module layout/ui/user-list/src/list-item
 */
jn.define('layout/ui/user-list/src/list-item', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { UserName } = require('layout/ui/user/user-name');
	const { Text2, Text5 } = require('ui-system/typography/text');
	const { UserProfile } = require('user-profile');

	class ListItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.layoutWidget = null;
		}

		render()
		{
			return View(
				{},
				this.renderListItem(this.props),
			);
		}

		renderListItem({ user, showBorder, testId })
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginLeft: Indent.XL3.toNumber(),
					},
					testId,
					onClick: () => this.#openUserProfile(user.id),
				},
				Avatar({
					id: user.id,
					name: user.name,
					size: 40,
					testId: `${testId}_AVATAR`,
					image: user.avatar,
					withRedux: true,
				}),
				View(
					{
						style: {
							height: 70,
							justifyContent: 'center',
							flex: 1,
							flexDirection: 'column',
							marginHorizontal: Indent.XL.toNumber(),
							borderTopWidth: showBorder ? 1 : 0,
							borderTopColor: Color.bgSeparatorPrimary.toHex(),
							paddingVertical: Indent.XL2.toNumber(),
						},
					},
					UserName({
						id: user.id,
						testId: `${testId}_NAME`,
						text: user.name,
						numberOfLines: 1,
						ellipsize: 'end',
						textElement: Text2,
					}),
					this.#renderAdditionalText(user, testId),
				),
			);
		}

		#renderAdditionalText(user, testId)
		{
			if (this.props.renderCustomDescription)
			{
				return this.props.renderCustomDescription(user);
			}

			const { workPosition } = user;

			if (workPosition)
			{
				return Text5({
					color: Color.base3,
					testId: `${testId}-work-position`,
					numberOfLines: 1,
					ellipsize: 'end',
					text: workPosition,
				});
			}

			return null;
		}

		/**
		 * @returns {PageManager}
		 */
		getParentWidget()
		{
			const { parentWidget } = this.props;

			return parentWidget;
		}

		#openUserProfile(userId)
		{
			void UserProfile.open({
				ownerId: userId,
				parentWidget: this.getParentWidget(),
			});
		}
	}

	module.exports = { ListItem };
});
