// @ts-ignore
import {BaseList, JNBaseClassInterface} from '../../../../../../../../mobile/dev/janative/api'

export type DialogId = number | string;
export type MessageId = number | string;

// @ts-ignore
export declare interface JNChatBaseClassInterface<TEvent extends Record<string, Function>> extends JNBaseClassInterface {
	on<T extends keyof TEvent>(eventName: T, handler: TEvent[T]): void;

	off<T extends keyof TEvent>(eventName: T, handler: TEvent[T]): void;

	once<T extends keyof TEvent>(eventName: T, handler: TEvent[T]): void;
}

export declare interface IChatRecentList extends BaseList {}


export type UserId = number;
