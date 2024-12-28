import { DialogId } from "../../../types/common";

type CreateMessageOptions = {
	showUsername?: boolean,
	showAvatar?: boolean,
	showReactions?: boolean,
	fontColor?: string,
	canBeQuoted?: boolean,
	canBeChecked?: boolean,
	isBackgroundOn?: boolean,
	showReaction?: boolean,
	marginTop?: number,
	marginBottom?: number,
	showCommentInfo?: boolean,
	audioRate?: number,
	showAvatarsInReaction?: boolean,
	initialPostMessageId?: string,
	dialogId: DialogId,
};
