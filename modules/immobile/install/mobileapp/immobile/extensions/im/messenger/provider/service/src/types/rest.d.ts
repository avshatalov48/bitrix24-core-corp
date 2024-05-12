import {RawFile, RawMessage, RawPin, RawReaction, RawUser} from "./sync-list-result";

declare type ImV2ChatPinTailResult = {
	additionalMessages: Array<RawMessage>,
	files: Array<RawFile>,
	reactions: Array<RawReaction>,

	pins: Array<RawPin>,
	reminders: Array<any>,
	users: Array<RawUser>,
}