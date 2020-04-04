(()=>{
	/**
	 * @class GroupList
	 */
	class GroupList extends BaseList
	{
		static id()
		{
			return "groups";
		}

		static method()
		{
			return "sonet_group.user.groups";
		}

		static prepareItemForDrawing(group)
		{
			return {
				title: group.GROUP_NAME,
				sectionCode: "groups",
				color: "#5D5C67",
				useLetterImage: true,
				id: group.GROUP_ID,
				sortValues: {
					name: group.GROUP_NAME
				},
				params: {
					id: group.GROUP_ID,
				},
			}

		}
	}

	jnexport(GroupList);

})();