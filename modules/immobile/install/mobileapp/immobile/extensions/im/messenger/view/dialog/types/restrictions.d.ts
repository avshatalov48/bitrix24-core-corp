import { JNChatBaseClassInterface } from '../../../../../../../../../../mobile/dev/janative/api.td';

declare interface JNChatRestrictions extends JNChatBaseClassInterface
{
	update(params: ChatRestrictionsParams): void;
}

declare type ChatRestrictionsParams = {
	reaction?: boolean,
	quote?: boolean,
	longTap?: boolean,
}
