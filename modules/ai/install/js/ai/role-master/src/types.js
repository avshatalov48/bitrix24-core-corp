export type RoleMasterPopupOptions = {
	roleMasterOptions: RoleMasterOptions;
}

export type RoleMasterOptions = {
	id?: string;
	authorId?: string;
	text?: string;
	/** The URL for the avatar */
	avatar?: string;
	avatarUrl?: string;
	name?: string;
	description?: string;
	/**
	 * The array of the items for EntitySelector in format [[entityId, itemId], [entityId, itemId]]
	 *
	 * For example [['user', 1], ['group', 2]]
	 *
	 * @see 'ui.entity-selector'
	 * */
	itemsWithAccess?: string;
}
