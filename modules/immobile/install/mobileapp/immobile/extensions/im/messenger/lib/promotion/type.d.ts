export interface showSpotlightOptions {
	setComponent?: {
		component: object,
		params?: object,
	},
	setHint?: {
		text: string,
	},
	setTarget?: {
		target: string | object,
		params?: object,
	},
	setHandler?: () => any,
}
