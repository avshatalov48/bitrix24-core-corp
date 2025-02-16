export type MessageStatusModel = {
	title: string,
	description: string,
	semantic: 'secondary' | 'success' | 'primary' | 'failure',
	isDisabled: boolean,
}

export type MessageStatusState = {
	collection: { [bookingId: string]: MessageStatusModel },
}
