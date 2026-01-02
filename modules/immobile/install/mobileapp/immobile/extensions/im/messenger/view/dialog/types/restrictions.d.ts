// @ts-ignore
import {JNBaseClassInterface} from '../../../../../../../../../../mobile/dev/janative/api'


declare class DialogRestrictions
{
	ui: JNChatRestrictions;
}

declare interface JNChatRestrictions extends JNBaseClassInterface
{
	update(params: ChatRestrictionsParams): void;
}

declare type ChatRestrictionsParams = {
	reaction?: boolean,
	quote?: boolean,
	longTap?: boolean,
}
