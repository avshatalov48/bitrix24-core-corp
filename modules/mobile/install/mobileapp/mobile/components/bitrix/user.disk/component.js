//version 1.1
UserDisk.open(
	{
		userId: env.userId,
		ownerId: BX.componentParameters.get("ownerId", null),
		folderId: BX.componentParameters.get("folderId", null),
		title: BX.componentParameters.get("title", null),
		entityType: BX.componentParameters.get("entityType", null),
		destroyOnRemove: BX.componentParameters.get("destroyOnRemove", true),
		list: list
	}
);