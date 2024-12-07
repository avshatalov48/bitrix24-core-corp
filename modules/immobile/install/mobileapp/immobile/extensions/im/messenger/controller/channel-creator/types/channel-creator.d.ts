declare type EnterNameCompleteResult = {
	title: string,
	description: string,
	avatarBase64: string;
	previewAvatarPath: string;
}

declare type SettingsCompleteResult = {
	mode: 'open' | 'private',
}
