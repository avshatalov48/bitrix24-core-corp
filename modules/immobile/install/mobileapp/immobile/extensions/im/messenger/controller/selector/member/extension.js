/**
 * @module im/messenger/controller/selector/member
 */
jn.define('im/messenger/controller/selector/member', (require, exports, module) => {
	const { openDialogSelector } = require('im/messenger/controller/selector/dialog/opener');

	const { Loc } = require('im/messenger/loc');

	class MemberSelector
	{
		/**
		 * @param {Object} props
		 * @param {String} props.title
		 * @param {Array<Number>} props.initSelectedIds
		 * @param {Function} props.onSelectMembers
		 * @param {Function} props.onSelectItem
		 * @param {Boolean} props.integrateSelectorToParentLayout
		 * @param {Boolean} props.allowMultipleSelection
		 * @param {Boolean} props.withCurrentUser
		 */
		constructor(props)
		{
			this.title = props?.title || Loc.getMessage('IMMOBILE_MESSENGER_MEMBER_SELECTOR_TITLE');
			this.initSelectedIds = props?.initSelectedIds || [];
			this.onSelectMembers = props?.onSelectMembers;
			this.onSelectItem = props?.onSelectItem;
			this.integrateSelectorToParentLayout = props?.integrateSelectorToParentLayout ?? false;
			this.allowMultipleSelection = props?.allowMultipleSelection ?? true;
			this.withCurrentUser = props?.withCurrentUser ?? false;
		}

		open(parentWidget = null)
		{
			openDialogSelector({
				title: this.title,
				providerOptions: {
					allowMultipleSelection: this.allowMultipleSelection,
					onlyUsers: true,
					withCurrentUser: this.withCurrentUser,
				},
				allowMultipleSelection: this.allowMultipleSelection,
				onClose: this.#onSelectMembers,
				onItemSelected: this.#onItemSelected,
				closeOnSelect: true,
				integrateSelectorToParentLayout: this.integrateSelectorToParentLayout,
				initSelectedIds: this.initSelectedIds,
			}, parentWidget);
		}

		#onSelectMembers = (members) => {
			if (this.allowMultipleSelection)
			{
				const membersIds = members.map((member) => Number(member.id));

				if (this.onSelectMembers)
				{
					this.onSelectMembers(membersIds, members);
				}
			}
		};

		#onItemSelected = ({ item }) => {
			if (!this.allowMultipleSelection && this.onSelectItem)
			{
				this.onSelectItem(item.params);
			}
		};
	}

	module.exports = { MemberSelector };
});
