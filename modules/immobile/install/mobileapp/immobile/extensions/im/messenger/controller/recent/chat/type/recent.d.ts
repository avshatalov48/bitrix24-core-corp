import { CopilotRecentItemData, RecentItemData } from "../../copilot/types/recent";

declare type imV2RecentDialogResult = {
	hasMore: boolean,
	hasMorePages: boolean,
	items: Array<RecentItemData>,
	copilot: CopilotRecentItemData,
}
