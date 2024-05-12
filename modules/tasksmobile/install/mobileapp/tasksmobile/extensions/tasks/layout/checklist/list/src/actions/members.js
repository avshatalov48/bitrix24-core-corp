/**
 * @module tasks/layout/checklist/list/src/actions/members
 */
jn.define('tasks/layout/checklist/list/src/actions/members', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Avatar } = require('layout/ui/user/avatar');
	const { ElementsStack } = require('elements-stack');
	const { IconView, iconTypes } = require('ui-system/blocks/icon');

	const IMAGE_SIZE = 22;
	const MEMBER_TYPE_ICONS = {
		A: iconTypes.outline.group,
		U: iconTypes.outline.observer,
	};

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

			Object.values(item.getMembers()).forEach((member) => {
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

			return memberSectionsKeys.map((type, i) => {
				const memberType = item.getMemberType(type);

				return View(
					{
						testId: `${memberType}_user`,
						style: {
							flexDirection: 'row',
							marginRight: memberSectionsKeys.length - 1 === i ? 0 : Indent.XS,
						},
						onClick: () => {
							if (onClick)
							{
								onClick(item.getId(), memberType);
							}
						},
					},
					IconView({
						icon: MEMBER_TYPE_ICONS[type],
						iconSize: IMAGE_SIZE,
						iconColor: Color.base3,
					}),
					this.membersStack(memberSections[type], type),
				);
			});
		}

		membersStack(children)
		{
			const { testId } = this.props;

			return ElementsStack({
				testId,
				indent: 1,
				maxElements: 3,
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
