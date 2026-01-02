type AutoDeleteMessagesContextMenuProps = {
	ref: () => void,
	selectedItem: number,
	disabled?: boolean,
	onItemSelected?: (value: string) => void,
}

type ContextMenuItem = {
	id: string,
	testId: string,
	title: string,
	iconName: typeof Icon,
	onItemSelected: () => void,
	style: object,
}

export { ContextMenuItem, AutoDeleteMessagesContextMenuProps };
