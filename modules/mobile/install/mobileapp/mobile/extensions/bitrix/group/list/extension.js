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
				imageUrl: (typeof group.GROUP_IMAGE === 'undefined' || group.GROUP_IMAGE == null || group.GROUP_IMAGE.length <= 0 ? undefined : encodeURI(group.GROUP_IMAGE)),
				sortValues: {
					name: group.GROUP_NAME,
				},
				params: {
					id: group.GROUP_ID,
					extranet: (typeof group.IS_EXTRANET !== 'undefined' && group.IS_EXTRANET === 'Y')
				},
			}

		}
	}

	jnexport(GroupList);

})();