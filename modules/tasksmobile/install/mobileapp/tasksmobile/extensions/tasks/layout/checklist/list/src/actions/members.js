/**
 * @module tasks/layout/checklist/list/src/actions/members
 */
jn.define('tasks/layout/checklist/list/src/actions/members', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Avatar } = require('layout/ui/user/avatar');
	const { ElementsStack } = require('elements-stack');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const {
		MEMBER_TYPE_ICONS,
		MEMBER_TYPE_RESTRICTION_FEATURE_META,
	} = require('tasks/layout/checklist/list/src/constants');

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
			const { item, onClick } = this.props;
			const memberSections = {};

			item.getPrepareMembers().forEach((member) => {
				const type = member.type;

				if (memberSections[type])
				{
					memberSections[type].push(this.createMembersElement(member));
				}
				else
				{
					memberSections[type] = [this.createMembersElement(member)];
				}
			});

			const memberSectionsKeys = Object.keys(memberSections).sort();

			return memberSectionsKeys.map((memberType, i) => View(
				{
					testId: `${memberType}_user`,
					style: {
						flexDirection: 'row',
						marginRight: memberSectionsKeys.length - 1 === i ? 0 : Indent.M.toNumber(),
					},
					onClick: () => {
						if (onClick)
						{
							onClick(item.getId(), memberType);
						}
					},
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
				this.membersStack(memberSections[memberType]),
			));
		}

		membersStack(children)
		{
			const { testId } = this.props;

			return ElementsStack({
				testId,
				maxElements: 1,
				textColor: Color.base3,
			}, ...children);
		}

		createMembersElement(member)
		{
			const { id, name, image } = member;

			return Avatar({ id: Number(id), name, size: IMAGE_SIZE, image });
		}
	}

	module.exports = { ItemMembers };
});
