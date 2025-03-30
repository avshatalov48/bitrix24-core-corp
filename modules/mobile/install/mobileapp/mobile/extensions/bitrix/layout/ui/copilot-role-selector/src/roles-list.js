/**
 * @module layout/ui/copilot-role-selector/src/roles-list
 */
jn.define('layout/ui/copilot-role-selector/src/roles-list', (require, exports, module) => {
	const { CopilotRoleSelectorBaseList } = require('layout/ui/copilot-role-selector/src/base-list');
	const { RolesListItem, RolesListSkeleton } = require('layout/ui/copilot-role-selector/src/views');
	const { ListItemType } = require('layout/ui/copilot-role-selector/src/types');
	const { checkValueMatchQuery } = require('utils/search');
	const { loadIndustries } = require('layout/ui/copilot-role-selector/src/api');

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
				showIndustry: false,
			});
		}

		renderListSkeleton(count = 10)
		{
			return RolesListSkeleton(count);
		}

		async loadItems()
		{
			const response = await loadIndustries(true);
			if (response?.status === 'success')
			{
				this.universalRoleItemData = {
					...response.data.universalrole,
					type: ListItemType.UNIVERSAL_ROLE,
				};

				if (response.data?.industries?.length > 0)
				{
					return response.data.industries[0].roles;
				}
			}

			return [];
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
