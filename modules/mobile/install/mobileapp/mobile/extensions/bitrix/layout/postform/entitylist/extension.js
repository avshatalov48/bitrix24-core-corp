(() =>
{

	const entityIdMap = {
		"users": 'user',
		"departments": 'department',
		"groups": 'project',
	}

	const entityIdBackMap = {
		'user': "users",
		'department': "departments",
		'project': "groups",
	}

	function convertToModernFormat(oldFormatData)
	{
		return Object.keys(oldFormatData).reduce((result, entityId) =>
		{
			let realEntityId = entityIdMap[entityId]
			if (realEntityId)
			{
				result[realEntityId] = oldFormatData[entityId]
					.map(item => item)
					.filter(item =>
					{
						if (realEntityId === 'user' && item.id === 'A')
						{
							let newItem = Object.assign({}, item);
							newItem.id = 'all-users'
							result['meta-user'] = [newItem]
							return false
						}
						return true;
					})
			}

			return result

		}, {});
	}

	function convertFormModernFormat(modernFormatData)
	{
		let correctImageUrl = item => {
			if(item.imageUrl && !item.imageUrl.startsWith("http")) {
				item.imageUrl = currentDomain + item.imageUrl;
			}

			return item;
		};
		return Object.keys(modernFormatData).reduce((result, entityId) =>
		{
			if (entityId === 'meta-user')
			{
				let allItem = modernFormatData[entityId].find((item) => item.id === 'all-users')
				allItem.id = 'A'
				if(typeof allItem.params !== 'object' || allItem.params === null) {
					allItem.params = {};
				}

				allItem.params.id = 'A'
				if (allItem)
				{
					result["users"].push(allItem)
				}
			}
			else
			{
				let oldEntityId = entityIdBackMap[entityId];
				if (oldEntityId)
				{
					result[oldEntityId] = modernFormatData[entityId]
						.concat(result[oldEntityId])
						.map(correctImageUrl)
				}
				else
				{
					result[entityId] = modernFormatData[entityId].map(correctImageUrl);
				}
			}

			return result

		}, {users: [], departments: [], groups: []});
	}

	function convertEntityOptions(oldEntityOptions) {
		return Object.keys(oldEntityOptions).reduce((result, entityId) => {
			let realEntityId = entityIdMap[entityId]
			if (realEntityId)
			{
				let options = oldEntityOptions[entityId];
				result[realEntityId] = options;
				if (realEntityId === 'user') {
					if (typeof options.showAll !== "undefined") {
						let showAllUsers = Boolean(options.showAll);
						if(!showAllUsers) {
							result["meta-user"] = {};
						}
					}
				}
			}
		}, {});
	}

	/**
	 * @class FormEntitySelector
	 */
	class FormEntitySelector extends RecipientSelector
	{
		constructor(context, entities, entityOptions)
		{
			const convertedEntities = Array.isArray(entities)
				? entities.map(old =>
				{
					if (entityIdMap[old])
					{
						return entityIdMap[old];
					}
					return old;
				})
				: undefined;

			super(context, convertedEntities)

			if(entityOptions) {
				this.setEntitiesOptions(entityOptions)
			}
		}

		open(params = {})
		{
			if (typeof params["singleChoose"] !== 'undefined') {
				this.singleSelection = Boolean(params["singleChoose"])
			}

			if (typeof params["allowMultipleSelection"] !== 'undefined') {
				this.ui.allowMultipleSelection(Boolean(params["allowMultipleSelection"]));
			}

			if (params.selected) {
				params.selected = convertToModernFormat(params.selected)
			}

			if(!params.title) {
				params.title = BX.message("RECIPIENT_TITLE")
			}

			return super.open(params);
		}

		onResult(data)
		{
			try {
				let result = convertFormModernFormat(data);
				super.onResult(result);
			}
			catch (e) {
				console.error(e);
			}
		}
	}

	jnexport(FormEntitySelector)
})();