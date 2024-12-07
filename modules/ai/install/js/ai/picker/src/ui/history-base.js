import { Tag, Loc, bind } from 'main.core';
import { Popup } from 'main.popup';
import { Icon, Actions } from 'ui.icon-set.api.core';
import 'ui.icon-set.icon.actions';

import { Base } from './base';
import { HistoryItem } from '../types';

import 'ui.notification';
import 'clipboard';

export type HistoryBaseProps = {
	onLoadHistory: Function,
	onGenerate: Function,
	onSelect: Function,
	items: Array;
	capacity: number;
};

export type LoadHistoryResponse = {
	status: 'success' | 'error',
	data: LoadHistoryResponseData,
	errors: LoadHistoryResponseError[],
}

type LoadHistoryResponseData = {
	capacity: number;
	items: HistoryItem[],
}

type LoadHistoryResponseError = {

}

export class HistoryBase extends Base
{
	notifiers: Map<string, Popup>;
	listWrapper: HTMLElement;
	items: Array<HistoryItem>;
	capacity: number;
	onGenerate: Function;
	onLoadHistory: Function;
	onSelect: Function;
	isHistoryLoaded: boolean;

	constructor(props: HistoryBaseProps)
	{
		super(props);

		this.setEventNamespace('AI:Picker:History');

		this.onGenerate = props.onGenerate;
		this.onLoadHistory = props.onLoadHistory;
		this.onSelect = props.onSelect;
		this.notifiers = new Map();
		this.listWrapper = this.getListWrapper();
		this.items = props.items || [];
		this.capacity = props.capacity || 30;
		this.isHistoryLoaded = true;
	}

	/**
	 * Called when user want to use HistoryItem somewhere outside.
	 *
	 * @param {HistoryItem} item
	 */
	onSelectClick(item: HistoryItem): void
	{
		this.onSelect(item);
	}

	/**
	 * Shows notification near the Node.
	 *
	 * @param {HTMLElement} node Near this node notification will appear.
	 * @param {string} code Unique id.
	 * @param message Notification message.
	 */
	showNotify(node: HTMLElement, code: string, message: string): void
	{
		if (!this.notifiers.has(code))
		{
			const popup = new Popup(code, node, {
				content: message,
				darkMode: true,
				autoHide: true,
				angle: true,
				offsetLeft: 20,
				bindOptions: {
					position: 'top',
				},
			});

			bind(node, 'mouseout', () => {
				setTimeout(() => {
					this.notifiers.get(code).close();
				}, 300);
			});

			this.notifiers.set(code, popup);
		}

		this.notifiers.get(code).show();
	}

	/**
	 * Builds History container after loading History items.
	 *
	 */
	buildHistory(): void
	{
		// you must implement this method
	}

	addNewItem(item: HistoryItem): void
	{
		// you must implement this method
	}

	/**
	 * Returns label with note about History limitation.
	 *
	 * @param {number} capacity
	 * @return {HTMLElement}
	 */
	getCapacityLabel(capacity: number): HTMLElement
	{
		const iconColor = getComputedStyle(document.body).getPropertyValue('--ui-color-base-35');

		const arrowIcon = new Icon({
			icon: Actions.ARROW_TOP,
			size: 14,
			color: iconColor,
		});

		return Tag.render`
			<div class="ai__picker-text_capacity-label">
				${arrowIcon.render()}
				<div class="ai__picker-text_capacity-label-text">
					${this.getMessage('max_capacity').replace('#capacity#', capacity)}
				</div>
			</div>
		`;
	}

	/**
	 * Returns the loader or text when loading History.
	 *
	 * @return {HTMLElement}
	 */
	getListWrapper(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker_list-wrapper">
				<div class="ai__picker_list-wrapper-loader-text">
					${Loc.getMessage('AI_JS_PICKER_HISTORY_LOADING')}
				</div>
			</div>
		`;
	}

	/**
	 * Returns wrapper for History.
	 *
	 * @return {HTMLElement}
	 */
	getWrapper(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker_history">
				${this.listWrapper}
			</div>
		`;
	}

	/**
	 * Returns to parent.
	 *
	 * @return {HTMLElement}
	 */
	render(): HTMLElement
	{
		return this.getWrapper();
	}

	loadHistory(): Promise<LoadHistoryResponse>
	{
		if (!this.onLoadHistory)
		{
			return null;
		}

		return new Promise((resolve, reject) => {
			this.onLoadHistory()
				.then((res: LoadHistoryResponse) => {
					this.isHistoryLoaded = true;
					this.items = res.data.items;
					this.capacity = res.data.capacity;

					resolve(res);
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	generate(message: string, engineCode: string): Promise
	{
		// you must implement this method
	}
}
