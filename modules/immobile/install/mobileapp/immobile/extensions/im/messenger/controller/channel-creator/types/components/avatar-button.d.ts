declare type AvatarButtonProps = {
	defaultIconSvg: string,
	previewAvatarPath?: string,
	cornerRadius: number,
	onAvatarSelected: (params: { previewAvatarPath: string, avatarBase64: string }) => any,

}

declare type AvatarButtonState = {
	previewAvatarPath: string | null,
}