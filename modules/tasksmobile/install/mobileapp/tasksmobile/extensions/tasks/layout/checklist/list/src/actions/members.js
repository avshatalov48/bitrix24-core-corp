/**
 * @module tasks/layout/checklist/list/src/actions/members
 */
jn.define('tasks/layout/checklist/list/src/actions/members', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const {
		MEMBER_TYPE_ICONS,
		MEMBER_TYPE_RESTRICTION_FEATURE_META,
	} = require('tasks/layout/checklist/list/src/constants');
	const { Text4 } = require('ui-system/typography/text');

	const IMAGE_SIZE = 22;

	/**
	 * @class ItemMembers
	 */
	class ItemMembers extends LayoutComponent
	{
		isShow()
		{
			const { item } = this.props;

			return item.getMembersCount() > 0;
		}

		render()
		{
			if (!this.isShow())
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				...this.renderMemberTypes(),
			);
		}

		renderMemberTypes()
		{
			const { item, testId } = this.props;
			const memberSections = {};

			item.getPrepareMembers().forEach(({ type, id }) => {
				if (memberSections[type])
				{
					memberSections[type].push(id);
				}
				else
				{
					memberSections[type] = [id];
				}
			});

			const memberSectionsKeys = Object.entries(memberSections).sort();

			return memberSectionsKeys.map(([memberType, ids], i) => View(
				{
					testId: `${memberType}_user`,
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginRight: memberSectionsKeys.length - 1 === i ? 0 : Indent.M.toNumber(),
					},
					onClick: this.handleOnClick(memberType),
				},
				IconView({
					icon: (
						MEMBER_TYPE_RESTRICTION_FEATURE_META[memberType].isRestricted()
							? Icon.LOCK
							: MEMBER_TYPE_ICONS[memberType]
					),
					size: IMAGE_SIZE,
					color: Color.base3,
				}),
				AvatarStack({
					testId,
					entities: ids,
					size: IMAGE_SIZE,
					withRedux: true,
					visibleEntityCount: 1,
					restView: this.renderRestView,
					onClick: this.handleOnClick(memberType),
				}),
			));
		}

		renderRestView = (count) => {
			if (!count)
			{
				return null;
			}

			return Text4({
				text: `+${count}`,
				color: Color.base3,
				style: {
					marginLeft: Indent.S.toNumber(),
				},
			});
		};

		handleOnClick = (memberType) => () => {
			const { onClick, item } = this.props;

			onClick(item.getId(), memberType);
		};
	}

	module.exports = { ItemMembers };
});
