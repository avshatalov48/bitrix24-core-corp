export type SidebarModelState = {
	dialogId: string,
	isMute: boolean,
};

export type SidebarModelActions =
	'sidebarModel/set'
	| 'sidebarModel/add'
	| 'sidebarModel/delete'
	| 'sidebarModel/update'
	| 'sidebarModel/changeMute'
