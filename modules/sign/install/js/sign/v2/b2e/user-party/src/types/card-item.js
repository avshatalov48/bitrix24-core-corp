export type CardItem = {
	id: number,
	title: string,
	name: string,
	lastName: ?string,
	avatar: ?string,
	entityId: number,
	entityType: EntityType,
	container: HTMLElement,
}

export type EntityType = 'user' | 'department';
