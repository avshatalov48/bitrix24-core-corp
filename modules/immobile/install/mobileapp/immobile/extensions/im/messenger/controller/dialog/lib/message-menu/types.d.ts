declare type MessageContextMenuButton = {
	id: string,
	testId: string,
	type: 'button',
	text: string,
	iconName: string,
	iconFallbackUrl: string,
	/** @deprecated after API 54 use iconName and iconFallbackUrl instead */
	iconSvg?: string,
	style?: {
		fontColor?: string,
		iconColor?: string
	},
};

declare type MessageContextMenuSeparator = {
	type: 'separator',
};
