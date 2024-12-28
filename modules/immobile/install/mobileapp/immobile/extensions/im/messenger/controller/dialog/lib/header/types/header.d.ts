import {ChatAvatarTitleParams} from "../../../lib/element/types/chat-avatar";
import {ChatTitleTileParams} from "../../../lib/element/types/chat-title";

type DialogHeaderButtonsIds =
	'call_video'
	| 'call_audio'
	| 'add_users'
	| 'subscribed_to_comments'
	| 'unsubscribed_from_comments'

type DialogHeaderTitleParams = ChatAvatarTitleParams & ChatTitleTileParams

type DialogHeaderButton = {
	id: string,
	testId: string,
	type: string,
	color?: string | null,
	badgeCode?: string,
	badgeValue?: string,
	svg?: object,
	name?: string,
	imageUrl?: string,
	accent?: boolean,
	dot?: boolean,
	callback?: () => any,
}
