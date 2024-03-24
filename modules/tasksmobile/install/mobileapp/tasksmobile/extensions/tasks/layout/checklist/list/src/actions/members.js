/**
 * @module tasks/layout/checklist/list/src/actions/members
 */
jn.define('tasks/layout/checklist/list/src/actions/members', (require, exports, module) => {
	const { Avatar } = require('layout/ui/user/avatar');
	const { ElementsStack } = require('elements-stack');

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

		getMembers()
		{
			const { item } = this.props;

			return item.getMembers();
		}

		createMembersElement(member)
		{
			const { id, name, image } = member;

			return Avatar({ id: Number(id), name, size: IMAGE_SIZE, image });
		}

		membersStack({ onClick })
		{
			const members = this.getMembers();
			const children = Object.keys(members).map((id) => this.createMembersElement({ id, ...members[id] }));

			return ElementsStack({
				indent: 1,
				maxElements: 3,
				children,
				onClick,
			});
		}

		render()
		{
			if (!this.isShow())
			{
				return null;
			}

			const { onClick } = this.props;

			return this.membersStack({ onClick });
		}
	}

	module.exports = { ItemMembers };
});
