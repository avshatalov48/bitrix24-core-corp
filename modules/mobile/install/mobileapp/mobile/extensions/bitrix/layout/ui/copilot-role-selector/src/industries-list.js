/**
 * @module layout/ui/copilot-role-selector/src/industries-list
 */
jn.define('layout/ui/copilot-role-selector/src/industries-list', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { CopilotRoleSelectorBaseList } = require('layout/ui/copilot-role-selector/src/base-list');
	const {
		IndustriesListItem,
		IndustriesListSkeleton,
		RolesListItem,
	} = require('layout/ui/copilot-role-selector/src/views');
	const { ListItemType } = require('layout/ui/copilot-role-selector/src/types');
	const { checkValueMatchQuery } = require('utils/search');
	const cacheId = 'layout/ui/copilot-role-selector/src/industries-list';
	const cacheTtl = 604_800; // 60 * 60 * 24 * 7;

	class CopilotRoleSelectorIndustriesList extends CopilotRoleSelectorBaseList
	{
		renderListItem(item, isLastItem = false)
		{
			switch (item.type)
			{
				case ListItemType.INDUSTRY:
					return IndustriesListItem({
						item,
						isLastItem,
						clickHandler: () => {
							if (this.props.listItemClickHandler)
							{
								this.props.listItemClickHandler(item, ListItemType.INDUSTRY, this.getUniversalRoleItemData());
							}
						},
					});
				case ListItemType.ROLE:
				case ListItemType.UNIVERSAL_ROLE:
					return RolesListItem({
						item,
						isLastItem,
						clickHandler: () => {
							if (this.props.listItemClickHandler)
							{
								this.props.listItemClickHandler(item, ListItemType.ROLE);
							}
						},
						industryName: item.industryName,
						showIndustry: item.type === ListItemType.ROLE,
					});
				default:
					return View();
			}
		}

		searchItems(searchString)
		{
			const roleNameMatch = [];
			const roleDescriptionMatch = [];
			const industryNameMatch = [];

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

			this.items.forEach((industry) => {
				const currentIndustryNameMatched = checkValueMatchQuery(searchString, industry.name);
				industry.roles.forEach((role) => {
					if (checkValueMatchQuery(searchString, role.name))
					{
						roleNameMatch.push(role);

						return;
					}

					if (checkValueMatchQuery(searchString, role.description))
					{
						roleDescriptionMatch.push(role);

						return;
					}

					if (currentIndustryNameMatched)
					{
						industryNameMatch.push(role);
					}
				});
			});

			return [...roleNameMatch, ...roleDescriptionMatch, ...industryNameMatch];
		}

		renderListSkeleton(count = 10)
		{
			return IndustriesListSkeleton(count);
		}

		loadItemsHandler(resolver, response)
		{
			if (response
				&& response.status === 'success'
				&& response.errors.length === 0)
			{
				resolver(response.data);
			}
			else
			{
				console.error(response.errors);
				resolver([]);
			}
		}

		loadItems()
		{
			return new Promise((resolve) => {
				const handler = (response) => {
					if (response
						&& response.status === 'success'
						&& response.errors.length === 0)
					{
						this.universalRoleItemData = {
							...response.data.universalrole,
							type: ListItemType.UNIVERSAL_ROLE,
						};
						resolve(response.data.industries);
					}
					else
					{
						console.error(response.errors);
						resolve([]);
					}
				};

				(new RunActionExecutor('mobile.ai.CopilotRoleManager.getIndustriesWithRoles'))
					.setHandler(handler)
					.setCacheHandler(handler)
					.setCacheId(cacheId)
					.setCacheTtl(cacheTtl)
					.call(true)
				;
			});
		}

		prepareItems(items)
		{
			return items.map((industry) => {
				return {
					...industry,
					type: this.getItemType(),
					roles: industry.roles.map((role) => {
						return {
							...role,
							industryName: industry.name,
							type: ListItemType.ROLE,
						};
					}),
				};
			});
		}

		getItemType()
		{
			return ListItemType.INDUSTRY;
		}
	}

	module.exports = {
		CopilotRoleSelectorIndustriesList: (props) => {
			return new CopilotRoleSelectorIndustriesList(props);
		},
	};
});
