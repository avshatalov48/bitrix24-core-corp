(() => {
	const defaultParams = {
		useLargeTitleMode: false,
		detailTextColor: '#a8adb4'
	};

	class DetailCardNavigation
	{
		constructor(config)
		{
			this.typeMap = this.prepareTypes(config.types);
		}

		prepareTypes(types)
		{
			const map = new Map();

			types = Array.isArray(types) ? types : [];
			types.forEach((type) => {
				map.set(type.id, type);
			});

			return map;
		}

		getTitleParamsByType(type)
		{
			const typeData = this.typeMap.get(type);
			if (typeData)
			{
				return {
					detailText: typeData.name,
					imageUrl: typeData.logo,
					...defaultParams
				};
			}

			return {
				detailText: null,
				imageUrl: null,
				...defaultParams
			};
		}
	}

	this.DetailCardNavigation = DetailCardNavigation;
})();
