declare type CreateChatMember = ['user' | 'department', number, string];

declare type CreateChatParams = {
	type: 'CHAT' | 'CHANNEL',
	ownerId: number,
	memberEntities: Array<CreateChatMember>,
	searchable: 'Y' | 'N',
	title?: string,
	description?: string,
	avatar?: string
}