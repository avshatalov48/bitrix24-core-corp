export type AvatarType = 'groupChat' | 'channel'

declare type ElementAvatarButtonProps = {
	preview: string,
	type: AvatarType,
	onChange: (params: { preview: string, avatar: string }) => any,
	canClick?: boolean,
}

declare type ElementAvatarButtonState = {
	preview: string | null,
	canClick: boolean,
};

declare type ParticipantProps = {
	backgroundColor: Color,
	title: string,
	subtitle: string,
	type: 'user' | 'department',
	uri: string,
};
