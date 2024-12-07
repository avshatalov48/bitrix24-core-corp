declare interface MessengerCoreInitializeOptions
{
	localStorage: {
		enable: boolean,
		readOnly: boolean,
	},
}

declare type MessengerCoreRepository = {
	option: OptionRepository,
	recent: RecentRepository,
	dialog: DialogRepository,
	file: FileRepository,
	user: UserRepository,
	message: MessageRepository,
	tempMessage: TempMessageRepository,
	reaction: ReactionRepository
	queue: QueueRepository
	smile: SmileRepository,
	pinMessage: PinMessageRepository,
	copilot?: CopilotRepository,
	sidebarFile: SidebarFileRepository,
}