declare const CallMessageType: {
	readonly START: 'START',
	readonly FINISH: 'FINISH',
	readonly BUSY: 'BUSY',
	readonly DECLINED: 'DECLINED',
	readonly MISSED: 'MISSED',
}

type CallMetaDataValue = {
	iconName: string,
	iconColors: ({ modelMessage }) => CallMessageIconColor,
	iconFallbackUrl: string,
}

type CallMessageIconColor = {
	iconColor: string,
	iconBorderColor: string,
}

export type CallMetaData = Record<keyof typeof CallMessageType, CallMetaDataValue>

export type CallMessageData = {
	title: string,
	description: string,
	iconName: string,
	iconColor: string,
	iconFallbackUrl: string,
	iconBorderColor: string,
}
