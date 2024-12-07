declare type PrivacySelectorProps = {
	firstMode: 'open' | 'private',
	defaultMode: 'open' | 'private',
	onChangeMode: (value: 'open' | 'private') => void,
	badge: string,
	privateModeDescription: string,
	openModeDescription: string,
}

declare type PrivacySelectorState = {
	currentMode: 'open' | 'private',
}