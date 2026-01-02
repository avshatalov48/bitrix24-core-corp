import {AttachConfig} from "../../../../../model/messages/src/types/attach";
import {AvatarDetail} from "../../chat-avatar";
import {CommentInfoMessageFormat} from "./element/comment-info";
import {KeyboardButtonConfig} from "../../../../../model/messages/src/types/keyboard";
import {MessageVideo} from "./element/video";
import {MessageImage} from "./element/image";
import {MessageFile} from "./element/file";
import {MessageAudio} from "./element/audio";
import {
	CopilotMessageCopilotData,
	InitialPromptMessageData
} from "./copilot";
import {VoteMessageData} from "../../../src/dialog/message/vote/types/vote";
import {CheckInMessageData} from "../../../src/dialog/message/check-in/types/check-in-configuration";
import {CallMessageData} from "../../../src/dialog/message/call/types/call-configuration";

declare type MessageRichLink = {
	description: string,
	previewUrl: string,
	link: string,
	name: string,
	attachId: string,
	previewSize: {
		height: number,
		width: number,
	},
}

declare type MessageFootnote = {
	text: string,
	textColor: string,
	backgroundColor: string,
}

declare type MessageButton = {
	id: string,
	text: string,
	leftIcon?: string,
	editable: boolean,
	code?: string,
}

export type DialogWidgetItem = MediaGalleryDialogWidgetItem|FileGalleryDialogWidgetItem|
	FileDialogWidgetItem|ImageDialogWidgetItem|VideoDialogWidgetItem|AudioDialogWidgetItem|
	CopilotDialogWidgetItem|CopilotPromptDialogWidgetItem|CopilotErrorDialogWidgetItem|
	VoteDialogWidgetItem|CheckInDialogWidgetItem|CallDialogWidgetItem|BannerDialogWidgetItem;

export type BaseDialogWidgetItem = {
	align: 'center' | 'left' | 'right' | null,
	attach: Array<AttachConfig>,
	avatar: AvatarDetail | null,
	avatarUrl: string | null,
	canBeChecked: boolean,
	canBeQuoted: boolean,
	commentInfo: CommentInfoMessageFormat | null,
	forwardText: string,
	id: string,
	isAuthorBottomMessage: boolean,
	isAuthorTopMessage: boolean,
	isBackgroundWide: boolean,
	keyboard: Array<KeyboardButtonConfig>,
	likeCount: number,
	loadText: string,
	me: boolean,
	meLiked: boolean,
	message: Array<{ type: string, text: string }>,
	reactions: Array<DialogWidgetItemReactions>,
	read: boolean,
	richLink: MessageRichLink | null,
	showAvatar: boolean,
	showAvatarsInReaction: boolean,
	showReaction: boolean,
	showUsername: boolean,
	status: null | string,
	statusText: string,
	style: {
		textAlign: string,
		isBackgroundOn: boolean,
		isBackgroundWide?: boolean,
		roundedCorners: boolean,
		fontColor?: string,
		marginTop?: number,
		marginBottom?: number,
		rightTail?: boolean,
		leftTail?: boolean,
		userNameColor: string,
	},
	testId: string,
	time: string,
	title: { text?: string, color?: string, },
	type: string,
	userColor: string,
	username: string,
}

export type DialogWidgetItemReactions = {
	id: string,
	testId: string,
	counter: number,
	meLiked: boolean,
	users?: Array<object>,
}

export type MediaGalleryDialogWidgetItem = BaseDialogWidgetItem & {
	mediaList: Array<MessageVideo | MessageImage>,
}

export type ImageDialogWidgetItem = BaseDialogWidgetItem & {
	image: MessageImage,
	imageUrl: string,
	previewParams: {
		height: number,
		width: number,
	},
}

export type FileGalleryDialogWidgetItem = BaseDialogWidgetItem & {
	fileList: Array<MessageFile>,
}
export type FileDialogWidgetItem = BaseDialogWidgetItem & {
	file: MessageFile,
	fileName: string,
	fileSize: number,
	fileIconDownloadSvg: string,
	fileIconSvg: string,
}

export type VideoDialogWidgetItem = BaseDialogWidgetItem & {
	video: MessageVideo,
	videoUrl: string,
	localVideoUrl: string,
	previewImage: string,
	previewParams: {
		height: number,
		width: number,
	},
}

export type AudioDialogWidgetItem = BaseDialogWidgetItem & {
	video: MessageAudio,
	audioUrl: string,
	localAudioUrl: string,
	size: number,
}

export type CopilotDialogWidgetItem = BaseDialogWidgetItem & {
	copilot: CopilotMessageCopilotData,
}
export type CopilotErrorDialogWidgetItem = BaseDialogWidgetItem & {
	copilot: CopilotMessageCopilotData,
}
export type CopilotPromptDialogWidgetItem = BaseDialogWidgetItem & {
	copilot: CopilotMessageCopilotData,
	initialPrompt: InitialPromptMessageData,
}

export type VoteDialogWidgetItem = BaseDialogWidgetItem & {
	vote: VoteMessageData,
}

export type CheckInDialogWidgetItem = BaseDialogWidgetItem & {
	checkIn: CheckInMessageData,
}

export type CallDialogWidgetItem = BaseDialogWidgetItem & {
	call: CallMessageData,
}

export type BannerDialogWidgetItem = BaseDialogWidgetItem & {
	banner: CallMessageData,
}

export type AiAssistantDialogWidgetItem = BaseDialogWidgetItem & {
	buttons: MessageButton[],
	footnote: MessageFootnote,
}
