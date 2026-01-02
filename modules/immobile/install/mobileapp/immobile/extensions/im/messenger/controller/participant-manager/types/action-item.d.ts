type ActionItem = {
	id: string,
	title: string,
	callback: () => void,
	onItemSelected: () => void,
	icon: Object,
	testId: string,
	style: Object,
}

export { ActionItem };
