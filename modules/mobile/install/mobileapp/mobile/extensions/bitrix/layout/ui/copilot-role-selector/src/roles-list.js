/**
 * @module layout/ui/copilot-role-selector/src/roles-list
 */
jn.define('layout/ui/copilot-role-selector/src/roles-list', (require, exports, module) => {
	const { CopilotRoleSelectorBaseList } = require('layout/ui/copilot-role-selector/src/base-list');
	const { RolesListItem, RolesListSkeleton } = require('layout/ui/copilot-role-selector/src/views');
	const { ListItemType } = require('layout/ui/copilot-role-selector/src/types');
	const { Type } = require('type');
	const { checkValueMatchQuery } = require('utils/search');

	class CopilotRoleSelectorRolesList extends CopilotRoleSelectorBaseList
	{
		searchItems(searchString)
		{
			const roleNameMatch = [];
			const roleDescriptionMatch = [];

			if (this.props.enableUniversalRole)
			{
				const universalRoleItemData = this.getUniversalRoleItemData();
				if (checkValueMatchQuery(searchString, universalRoleItemData.name))
				{
					roleNameMatch.push(universalRoleItemData);
				}
				else
					if (checkValueMatchQuery(searchString, universalRoleItemData.description))
					{
						roleDescriptionMatch.push(universalRoleItemData);
					}
			}

			this.items.forEach((role) => {
				if (checkValueMatchQuery(searchString, role.name))
				{
					roleNameMatch.push(role);

					return;
				}

				if (checkValueMatchQuery(searchString, role.description))
				{
					roleDescriptionMatch.push(role);
				}
			});

			return [...roleNameMatch, ...roleDescriptionMatch];
		}

		renderListItem(item, isLastItem = false)
		{
			return RolesListItem({
				item,
				isLastItem,
				clickHandler: () => {
					this.props.listItemClickHandler(item, ListItemType.ROLE);
				},
				industryName: this.props.selectedIndustry.name,
			});
		}

		renderListSkeleton(count = 10)
		{
			return RolesListSkeleton(count);
		}

		loadItems()
		{
			return new Promise((resolve) => {
				const items = Type.isArrayFilled(this.props.selectedIndustry?.roles)
					? [...this.props.selectedIndustry.roles]
					: [];
				resolve(items);
			});
		}

		isRenderFeedbackItemForEmptySearchString()
		{
			return true;
		}

		getItemType()
		{
			return ListItemType.ROLE;
		}
	}

	module.exports = {
		CopilotRoleSelectorRolesList: (props) => {
			return new CopilotRoleSelectorRolesList(props);
		},
	};
});
