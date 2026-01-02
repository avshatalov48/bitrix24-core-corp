import { JNChatBaseClassInterface } from '../../../types/common';

interface Button {
	id: string;
	sort: number;
	icon?: {
		name: string;
		tintColor?: string;
	};
	badge?: {
		value: number;
		backgroundColor?: string;
		textColor?: string;
	};
	border?: {
		width: number;
		color: string;
	};
}

interface BadgesDict {
	[key: string]: number | object;
}

interface JNChatFloatingButtonsBarEvents {
	/**
	 * @param buttonId
	 */
	tap: (buttonId: string) => void;
}

declare interface JNChatFloatingButtonsBar extends JNChatBaseClassInterface {
	/**
	 * @param id
	 */
	remove(id: string): Promise<void>;

	/**
	 * @param badges
	 */
	setBadges(badges: BadgesDict): void;

	/**
	 * @param items
	 */
	setItems(items: Button[]): Promise<void>;

	/**
	 * @param item
	 */
	upsert(item: Button): Promise<void>;

	/**
	 * @param event
	 * @param callback
	 */
	on<K extends keyof JNChatFloatingButtonsBarEvents>(
		event: K,
		callback: JNChatFloatingButtonsBarEvents[K]
	): void;

	/**
	 * @param event
	 * @param callback
	 */
	off<K extends keyof JNChatFloatingButtonsBarEvents>(
		event: K,
		callback: JNChatFloatingButtonsBarEvents[K]
	): void;
}