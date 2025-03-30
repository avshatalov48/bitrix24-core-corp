import { DatetimeConverter } from 'crm.timeline.tools';
import { ajax as Ajax, Dom, Extension, Loc, Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { UI } from 'ui.notification';
import { BasicEditor } from 'ui.text-editor';
import { BitrixVue } from 'ui.vue3';
import ActionsPopup from './actions-popup';
import type { ElementIdsType, SectionIdType, SubSectionIdType } from './analytics';
import { Analytics, ElementIds, EventIds, Section, SubSection } from './analytics';
import {
	TodoEditorBlocksAddress,
	TodoEditorBlocksCalendar,
	TodoEditorBlocksClient,
	TodoEditorBlocksFile,
	TodoEditorBlocksLink,
} from './components/block/index';
import { Events, TodoEditor as TodoEditorComponent } from './components/todo-editor';
import { TodoEditorBorderColor } from './enums/border-color';

import './todo-editor.css';

declare type TodoEditorParams = {
	container: HTMLElement,
	deadline?: Date,
	defaultDescription?: string,
	events?: { [event: string]: (event) => {} },
	ownerId: number,
	currentUser?: Object,
	pingSettings: Object,
	copilotSettings?: Object,
	colorSettings: ColorSettings,
	calendarSettings?: Object,
	actionMenuSettings?: ActionMenuSettings,
	ownerTypeId: number,
	borderColor: string,
	popupMode: boolean,
	analytics?: AnalyticsSettings,
};

export type ColorSettings = {
	valuesList: ColorItem[],
	currentValueId: ?string,
}

type ColorItem = {
	id: string,
	title: string,
	color: string,
	iconBackground: string,
	itemBackground: string,
	logoBackground: string,
}

type ActionMenuSettings = {
	hiddenActionItems: string[],
}

type CancelParams = {
	sendAnalytics: boolean,
	analytics?: AnalyticsSettings;
}

type AnalyticsSettings = {
	section: SectionIdType;
	subSection: SubSectionIdType;
	element?: ElementIdsType;
	notificationSkipPeriod?: string;
}

export type BlockSettings = {
	id: string,
	title: string,
	icon: string,
	sort: number,
	settings?: Object,
	active?: boolean,
	focused?: boolean,
	filledValues?: Object,
}

export const TodoEditorMode = {
	ADD: 'add',
	UPDATE: 'update',
	COPY: 'copy',
};

export type ActionMenuItem = {
	id?: string,
	hidden?: boolean,
	messageCode?: string,
	svgData?: string,
	componentId?: string,
	componentParams?: Object,
	type?: string,
	onClick?: Function,
}

/**
 * @memberOf BX.Crm.Activity
 */
export class TodoEditorV2
{
/**
 * @event onFocus
 * @event onSaveHotkeyPressed
 */
	static BorderColor = TodoEditorBorderColor;
	static AnalyticsSection = Section;
	static AnalyticsSubSection = SubSection;
	static AnalyticsElement = ElementIds;
	static AnalyticsEvent = EventIds;

	#container: HTMLElement = null;
	#layoutApp = null;
	#layoutComponent = null;
	#loadingPromise: ?Promise = null;

	#mode: String = TodoEditorMode.ADD;
	#ownerTypeId: Number = null;
	#ownerId: Number = null;
	#user: Object = null;
	#currentUser: Object = null;
	#pingSettings: Object = null;
	#copilotSettings: ?Object = null;
	#colorSettings: ?ColorSettings = null;
	#calendarSettings: ?Object = null;
	#defaultTitle: String = null;
	#defaultDescription: String = null;
	#deadline: Date = null;
	#parentActivityId: Number = null;
	#borderColor = '';
	#activityId: Number = null;
	#eventEmitter: EventEmitter = null;
	#popupMode: boolean = false;
	#hiddenActionItems: string[] = [];
	#actionsPopup: ?ActionsPopup = null;
	#analytics: ?Analytics = null;
	#textEditor: BasicEditor = null;

	constructor(params: TodoEditorParams)
	{
		this.#checkParams(params);
		this.#initParams(params);
		this.#prepareContainer();

		this.onUpdateHandler = this.onUpdateHandler.bind(this);
		this.onRepeatHandler = this.onRepeatHandler.bind(this);

		this.#bindEvents(params);
	}

	#checkParams(params: TodoEditorParams): void
	{
		if (!Type.isDomNode(params.container))
		{
			throw new Error('TodoEditor container must be a DOM Node');
		}

		if (!Type.isNumber(params.ownerTypeId))
		{
			throw new TypeError('OwnerTypeId must be set');
		}

		if (!Type.isNumber(params.ownerId))
		{
			throw new TypeError('OwnerId must be set');
		}

		if (!Type.isObjectLike(params.currentUser))
		{
			throw new TypeError('Current user must be set');
		}

		if (!Type.isObjectLike(params.pingSettings) || Object.keys(params.pingSettings).length === 0)
		{
			throw new TypeError('Ping settings must be set');
		}

		if (!Type.isObjectLike(params.calendarSettings) || Object.keys(params.calendarSettings).length === 0)
		{
			throw new TypeError('Calendar settings must be set');
		}

		if (!Type.isObjectLike(params.colorSettings) || Object.keys(params.colorSettings).length === 0)
		{
			throw new TypeError('Color settings must be set');
		}
	}

	#initParams(params: TodoEditorParams): void
	{
		this.#container = params.container;
		this.#borderColor = this.#isValidBorderColor(params.borderColor)
			? params.borderColor
			: TodoEditorV2.BorderColor.DEFAULT
		;

		this.#ownerTypeId = params.ownerTypeId;
		this.#ownerId = params.ownerId;
		this.#currentUser = params.currentUser;
		this.#user = Runtime.clone(params.currentUser);
		this.#pingSettings = params.pingSettings || {};
		this.#copilotSettings = params.copilotSettings || null;
		this.#colorSettings = params.colorSettings;
		this.#calendarSettings = params.calendarSettings || {};
		this.#defaultTitle = this.#getDefaultTitle();
		this.#defaultDescription = Type.isString(params.defaultDescription)
			? params.defaultDescription
			: this.#getDefaultDescription()
		;
		this.#deadline = Type.isDate(params.deadline) ? params.deadline : null;
		if (!this.#deadline)
		{
			this.setDefaultDeadLine(false);
		}

		if (Type.isArrayFilled(params.actionMenuSettings?.hiddenActionItems))
		{
			this.#hiddenActionItems = params.actionMenuSettings.hiddenActionItems;
		}

		this.#popupMode = Type.isBoolean(params.popupMode) ? params.popupMode : false;

		if (Type.isPlainObject(params.analytics))
		{
			this.#analytics = Analytics.createFromToDoEditorData({
				analyticSection: params.analytics.section,
				analyticSubSection: params.analytics.subSection,
			});
		}
	}

	#prepareContainer(): void
	{
		Dom.addClass(this.#container, this.#getClassname());
	}

	setMode(mode: String): TodoEditorV2
	{
		if (!Object.values(TodoEditorMode).includes(mode))
		{
			throw new Error(`Unknown TodoEditor mode ${mode}`);
		}

		this.#mode = mode;

		if (this.#layoutComponent)
		{
			this.#layoutComponent.setMode(mode);
		}

		return this;
	}

	show(): void
	{
		this.#layoutApp = BitrixVue.createApp(TodoEditorComponent, {
			deadline: this.#deadline,
			defaultTitle: this.#defaultTitle,
			currentUser: this.#currentUser,
			pingSettings: this.#pingSettings,
			colorSettings: this.#colorSettings,
			actionsPopup: this.#getActionsPopup(),
			blocks: this.#getBlocks(),
			mode: this.#mode,
			analytics: this.#getAnalyticsInstance(),
			textEditor: this.getTextEditor(),
			itemIdentifier: {
				entityTypeId: this.#ownerTypeId,
				entityId: this.#ownerId,
			},
		});

		this.#layoutComponent = this.#layoutApp.mount(this.#container);
	}

	getTextEditor(): BasicEditor
	{
		if (this.#textEditor !== null)
		{
			return this.#textEditor;
		}

		this.#textEditor = new BasicEditor({
			removePlugins: ['BlockToolbar'],
			minHeight: 50,
			maxHeight: this.#popupMode ? 126 : 600,
			content: this.#defaultDescription,
			placeholder: Loc.getMessage('CRM_ACTIVITY_TODO_ADD_PLACEHOLDER_ROLLED'),
			paragraphPlaceholder: Loc.getMessage(
				Type.isPlainObject(this.#copilotSettings)
					? 'CRM_ACTIVITY_TODO_ADD_PLACEHOLDER_WITH_COPILOT_MSGVER_1'
					: null,
			),
			toolbar: [],
			floatingToolbar: [
				'bold', 'italic', 'underline', 'strikethrough',
				'|',
				'link', 'copilot',
			],
			collapsingMode: true,
			copilot: {
				copilotOptions: Type.isPlainObject(this.#copilotSettings) ? this.#copilotSettings : null,
				triggerBySpace: true,
			},
			events: {
				onFocus: () => {
					this.#onInputFocus();
				},
				onEmptyContentToggle: (event: BaseEvent) => {
					this.#eventEmitter.emit('onEmptyContentToggle', { isEmpty: event.getData().isEmpty });
				},
				onCollapsingToggle: (event: BaseEvent) => {
					this.#eventEmitter.emit('onCollapsingToggle', { isOpen: event.getData().isOpen });
				},
				onMetaEnter: () => {
					this.#onSaveHotkeyPressed();
				},
			},
		});

		return this.#textEditor;
	}

	#getActionsPopup(): ActionsPopup
	{
		if (this.#actionsPopup === null)
		{
			const items: ActionMenuItem[] = [
				{
					id: 'calendar',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_CALENDAR',
					svgData: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.9907 10.3123H13.1133C13.3394 10.3123 13.5227 10.4944 13.5227 10.7191V11.8343C13.5227 12.0589 13.3394 12.241 13.1133 12.241H11.9907C11.7646 12.241 11.5813 12.0589 11.5813 11.8343V10.7191C11.5813 10.4944 11.7646 10.3123 11.9907 10.3123Z" fill="#A8ADB4"/><path d="M10.2021 10.3126H9.07947C8.85336 10.3126 8.67007 10.4947 8.67007 10.7193V11.8345C8.67007 12.0591 8.85336 12.2412 9.07947 12.2412H10.2021C10.4282 12.2412 10.6115 12.0591 10.6115 11.8345V10.7193C10.6115 10.4947 10.4282 10.3126 10.2021 10.3126Z" fill="#A8ADB4"/><path d="M10.2011 13.2054H9.07852C8.85242 13.2054 8.66912 13.3874 8.66912 13.6121V14.7273C8.66912 14.9519 8.85242 15.134 9.07852 15.134H10.2011C10.4272 15.134 10.6105 14.9519 10.6105 14.7273V13.6121C10.6105 13.3874 10.4272 13.2054 10.2011 13.2054Z" fill="#A8ADB4"/><path d="M13.1133 13.2054H11.9907C11.7646 13.2054 11.5813 13.3874 11.5813 13.6121V14.7273C11.5813 14.9519 11.7646 15.134 11.9907 15.134H13.1133C13.3394 15.134 13.5227 14.9519 13.5227 14.7273V13.6121C13.5227 13.3874 13.3394 13.2054 13.1133 13.2054Z" fill="#A8ADB4"/><path d="M14.9029 10.3123H16.0255C16.2516 10.3123 16.4349 10.4944 16.4349 10.7191V11.8343C16.4349 12.0589 16.2516 12.241 16.0255 12.241H14.9029C14.6768 12.241 14.4935 12.0589 14.4935 11.8343V10.7191C14.4935 10.4944 14.6768 10.3123 14.9029 10.3123Z" fill="#A8ADB4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17.1486 5.691V5.15946H18.3308C19.4103 5.2275 20.2467 6.16407 20.2272 7.28562V17.9164C20.2272 18.5032 19.7685 18.9795 19.201 18.9795H5.80482C5.23836 18.9795 4.77862 18.5032 4.77862 17.9164V7.28562C4.77451 7.23034 4.77246 7.17612 4.77246 7.12191C4.77451 6.03544 5.62627 5.15734 6.67505 5.15946H7.85724V5.691C7.85724 6.57123 8.54582 7.28562 9.39654 7.28562C10.2473 7.28562 10.9359 6.57123 10.9359 5.691V5.15946H14.07V5.691C14.07 6.57123 14.7596 7.28562 15.6093 7.28562C16.459 7.28562 17.1486 6.57123 17.1486 5.691ZM18.1748 16.8533H6.83106V8.40898H18.1748V16.8533Z" fill="#A8ADB4"/><path d="M10.1507 4.31111V5.4805C10.1507 5.91211 9.81308 6.26186 9.39644 6.26186C8.9798 6.26186 8.64218 5.91211 8.64218 5.4805V4.31111L8.64771 4.20959C8.69329 3.82206 9.01469 3.52246 9.40157 3.52442C9.81821 3.52762 10.1528 3.8795 10.1507 4.31111Z" fill="#A8ADB4"/><path d="M16.3215 4.33979V5.44858C16.3215 5.85574 16.0024 6.18636 15.6083 6.18636C15.2142 6.1853 14.8971 5.85468 14.8971 5.44752V4.33979C14.8971 3.93157 15.2163 3.60201 15.6093 3.60201C16.0024 3.60201 16.3215 3.93157 16.3215 4.33979Z" fill="#A8ADB4"/></svg>',
					hidden: !this.#canUseCalendarBlock(),
				},
				// temporary commented
				// {
				// 	id: 'slot',
				// 	messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_SLOT',
				// 	svgData: '<svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.48621 17.5597H9.71158L8.73957 19.6426H5.48731C4.93592 19.6426 4.48842 19.1759 4.48842 18.601V8.18467C4.48442 8.13051 4.48242 8.07738 4.48242 8.02426C4.48442 6.95972 5.3135 6.09933 6.33437 6.10142H7.4851V6.62223C7.4851 7.4847 8.15536 8.18467 8.98344 8.18467C9.81152 8.18467 10.4818 7.4847 10.4818 6.62223V6.10142H13.5351V6.62223C13.5351 7.4847 14.2064 8.18467 15.0334 8.18467C15.8605 8.18467 16.5318 7.4847 16.5318 6.62223V6.10142H17.7853C18.8361 6.16808 19.6502 7.08575 19.6313 8.18467V11.9389L17.6335 10.274V9.28577H6.48621V17.5597ZM9.71763 6.41636V5.27057C9.71963 4.84767 9.39399 4.50289 8.98844 4.49977C8.58288 4.49768 8.25125 4.83829 8.24925 5.26015V5.27057V6.41636C8.24925 6.83926 8.57789 7.18196 8.98344 7.18196C9.38899 7.18196 9.71763 6.83926 9.71763 6.41636ZM15.7266 6.38426V5.29784C15.7266 4.89786 15.4159 4.57495 15.0333 4.57495C14.6507 4.57495 14.3401 4.89786 14.3401 5.29784V6.38322C14.3401 6.78216 14.6487 7.10611 15.0323 7.10715C15.4159 7.10715 15.7266 6.7832 15.7266 6.38426ZM8.77829 11.1139C8.50215 11.1139 8.27829 11.3377 8.27829 11.6139V12.5036C8.27829 12.7797 8.50215 13.0036 8.77829 13.0036H9.66803C9.94418 13.0036 10.168 12.7797 10.168 12.5036V11.6139C10.168 11.3377 9.94418 11.1139 9.66803 11.1139H8.77829ZM11.1129 11.6511C11.1129 11.375 11.3368 11.1511 11.6129 11.1511H12.5027C12.7788 11.1511 13.0027 11.375 13.0027 11.6511V12.5409C13.0027 12.817 12.7788 13.0409 12.5027 13.0409H11.6129C11.3368 13.0409 11.1129 12.817 11.1129 12.5409V11.6511ZM16.9787 12.1586C16.9787 11.9791 17.2088 11.8845 17.3531 12.0046L22.4009 16.205C22.5328 16.3148 22.5328 16.506 22.4009 16.6158L17.3531 20.8162C17.2088 20.9363 16.9787 20.8417 16.9787 20.6622V17.9552C16.9513 17.9654 16.9214 17.971 16.8903 17.971C13.9663 17.9713 11.4309 19.8957 10.3896 20.8119C10.23 20.9523 9.96759 20.8299 10.0157 20.6312C10.4226 18.9502 11.9512 14.6044 16.8959 14.4746C16.9249 14.4738 16.9529 14.4784 16.9787 14.4873V12.1586Z" fill="#A8ADB4"/></svg>',
				// },
				{
					type: 'delimiter',
					hidden: !this.#canUseCalendarBlock(),
				},
				{
					id: 'client',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_CLIENT',
					svgData: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19.1102 18.7826C19.5562 18.6205 19.8037 18.1517 19.7122 17.686L19.3963 16.0768C19.3963 15.4053 18.5019 14.6382 16.7409 14.1912C16.1442 14.0278 15.577 13.7745 15.0596 13.4403C14.9464 13.3768 14.9636 12.7904 14.9636 12.7904L14.3964 12.7056C14.3964 12.658 14.3479 11.9547 14.3479 11.9547C15.0265 11.7309 14.9567 10.4105 14.9567 10.4105C15.3877 10.6451 15.6684 9.60016 15.6684 9.60016C16.1781 8.14839 15.4145 8.23617 15.4145 8.23617C15.5481 7.34989 15.5481 6.44915 15.4145 5.56287C15.075 2.62285 9.96373 3.42099 10.5698 4.38119C9.07596 4.11109 9.41682 7.44748 9.41682 7.44748L9.74084 8.31146C9.29173 8.5974 9.37993 8.92555 9.47844 9.29212C9.51952 9.44494 9.56238 9.60443 9.56886 9.77033C9.60016 10.6029 10.1192 10.4304 10.1192 10.4304C10.1512 11.8045 10.8415 11.9834 10.8415 11.9834C10.9712 12.8464 10.8904 12.6995 10.8904 12.6995L10.276 12.7725C10.2844 12.9687 10.2681 13.1652 10.2275 13.3576C9.87062 13.5137 9.6521 13.638 9.43575 13.761C9.21426 13.8869 8.99503 14.0116 8.6319 14.1679C7.24504 14.7644 5.73779 15.5403 5.46984 16.5849C5.3915 16.8903 5.31478 17.3009 5.24543 17.729C5.17275 18.1776 5.42217 18.6168 5.84907 18.7728C7.71183 19.4533 9.81409 19.8566 12.0441 19.9044H12.942C15.1614 19.8568 17.2541 19.4572 19.1102 18.7826Z" fill="#A8ADB4"/></svg>',
				},
				{
					id: 'colleague',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_COLLEAGUE',
					svgData: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.3717 14.7295C15.3717 14.7295 15.8697 16.6051 16.1536 17.9919C16.2039 18.2372 16.0599 18.4795 15.8198 18.5503C14.2716 19.0067 12.5157 19.2655 10.6546 19.268H10.5989C8.33167 19.2649 6.22074 18.8816 4.45101 18.2238C4.09757 18.0925 3.90181 17.7203 3.97453 17.3503C4.14815 16.467 4.33844 15.5288 4.43784 15.1361C4.64903 14.3016 5.83236 13.6816 6.92075 13.205C7.20527 13.0805 7.37691 12.9811 7.55037 12.8806C7.72026 12.7822 7.89189 12.6828 8.17301 12.5578C8.20474 12.4044 8.21749 12.2477 8.21099 12.0911L8.69326 12.0328C8.69326 12.0328 8.75664 12.1501 8.6547 11.4606C8.6547 11.4606 8.11342 11.3162 8.0883 10.2184C8.0883 10.2184 7.68169 10.3563 7.65715 9.69107C7.65216 9.55801 7.61854 9.43016 7.58634 9.30774C7.50923 9.0145 7.4403 8.75241 7.79269 8.52443L7.53826 7.83494C7.53826 7.83494 7.27128 5.168 8.4438 5.38499C7.96824 4.6188 11.98 3.98123 12.2465 6.33026C12.3513 7.03836 12.3513 7.758 12.2465 8.46609C12.2465 8.46609 12.8459 8.39609 12.4457 9.55574C12.4457 9.55574 12.2254 10.3893 11.8872 10.2012C11.8872 10.2012 11.9421 11.2561 11.4093 11.4349C11.4093 11.4349 11.4472 11.9966 11.4472 12.0349L11.8927 12.104C11.8927 12.104 11.8804 12.5724 11.9681 12.6231C12.3741 12.8901 12.8192 13.0925 13.2875 13.2231C14.6706 13.5801 15.3717 14.1928 15.3717 14.7295Z" fill="#A8ADB4"/><path d="M21.5203 15.2542C21.5307 15.4621 21.5426 15.6981 21.5546 15.9373C21.5725 16.2952 21.3506 16.6229 21.0072 16.7257C19.8897 17.0604 18.6226 17.3309 17.2483 17.5217H16.816C16.7901 17.1556 16.4584 15.8876 16.266 15.1519C16.1897 14.8602 16.1352 14.6521 16.1304 14.6171C16.1056 13.9241 15.496 13.3052 14.4537 12.8823C14.5326 12.7757 14.6001 12.6612 14.6553 12.5407C14.8014 12.3594 14.992 12.2187 15.2085 12.1324L15.2252 11.5899L14.081 11.2324C14.081 11.2324 13.7869 11.095 13.7576 11.095C13.7915 11.0115 13.8339 10.9318 13.8841 10.857C13.906 10.7987 14.0445 10.3635 14.0445 10.3635C13.8779 10.5775 13.6825 10.7675 13.4638 10.9282C13.6639 10.5746 13.8337 10.2047 13.9712 9.82247C14.0619 9.45455 14.1226 9.07991 14.1529 8.7022C14.2312 8.01581 14.3536 7.33514 14.5192 6.66437C14.638 6.32965 14.8474 6.03436 15.1241 5.81126C15.533 5.5276 16.0102 5.35795 16.5067 5.31982H16.5651C17.0624 5.35763 17.5406 5.52729 17.9503 5.81126C18.2273 6.03393 18.4368 6.32909 18.5555 6.66379C18.7209 7.33461 18.8433 8.01526 18.9221 8.70162C18.9574 9.07094 19.0211 9.437 19.1128 9.79651C19.2503 10.1855 19.4171 10.5635 19.6118 10.9273C19.3927 10.7671 19.1969 10.5773 19.0299 10.3635C19.0299 10.3635 19.1377 10.7584 19.1593 10.8167C19.2185 10.9049 19.2712 10.9972 19.317 11.0929C19.2887 11.0929 18.9937 11.2303 18.9937 11.2303L17.8495 11.5879L17.8658 12.1307C18.0825 12.2167 18.2731 12.3574 18.4191 12.539C18.4883 12.7133 18.5979 12.8687 18.7389 12.9925C19.015 13.0885 19.2812 13.2108 19.5338 13.3577C19.9163 13.5702 20.149 13.6309 20.5105 13.8534C21.1937 14.2737 21.4835 14.5246 21.5201 15.2515L21.5203 15.2542Z" fill="#A8ADB4"/></svg>',
					componentId: 'calendar',
					componentParams: {
						showUserSelector: true,
					},
					hidden: !this.#canUseCalendarBlock(),
				},
				{
					type: 'delimiter',
				},
				{
					id: 'address',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_ADDRESS',
					svgData: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.5059 3.50928C9.12771 3.50928 6.41309 6.22284 6.41309 9.6021C6.41309 13.2243 10.1465 18.3027 11.7684 20.338C12.1493 20.816 12.8582 20.8132 13.2357 20.3325C14.8534 18.2721 18.5987 13.1219 18.5987 9.6021C18.5987 6.22284 15.8841 3.50928 12.5059 3.50928ZM12.506 12.3707C10.9556 12.3707 9.73636 11.1526 9.73636 9.60108C9.73636 8.05063 10.9545 6.83142 12.506 6.83142C14.0565 6.83142 15.2757 8.04956 15.2757 9.60108C15.2757 11.1526 14.0565 12.3707 12.506 12.3707Z" fill="#A8ADB4"/></svg>',
					hidden: !this.#canUseAddressBlock(),
				},
				{
					id: 'room',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_ROOM',
					svgData: '<svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.23231 5.98876C6.98167 4.03949 18.4413 4.15905 20.0081 5.98876C21.5749 7.81847 21.8554 15.3273 20.0081 17.0395C19.6571 17.3648 18.9666 17.6131 18.0668 17.793C18.0471 18.8982 17.9669 21.1391 17.6418 21.1391C17.3455 21.1391 15.2474 19.3102 13.962 18.1643C10.2879 18.232 6.19211 17.778 5.23231 17.0395C3.55525 15.7491 3.48296 7.93803 5.23231 5.98876ZM6.27368 14.5419C6.87367 14.6452 7.7036 14.7319 8.66214 14.7982C8.72234 14.54 8.79838 14.2423 8.8529 14.029C8.89254 13.8739 8.9208 13.7633 8.92333 13.7447C8.93599 13.3759 9.43469 13.0463 9.97766 12.8211C9.93653 12.7643 9.90135 12.7034 9.87269 12.6394C9.79701 12.5432 9.69766 12.4684 9.58444 12.4221L9.57579 12.1334L10.172 11.9424C10.172 11.9424 10.3259 11.8694 10.3407 11.8694C10.3231 11.825 10.301 11.7825 10.2747 11.7426C10.2656 11.718 10.218 11.5663 10.1989 11.5056L10.191 11.4805C10.2773 11.5942 10.3789 11.6955 10.4928 11.7814C10.3883 11.593 10.2999 11.3962 10.2283 11.1929C10.181 10.997 10.1494 10.7976 10.1336 10.5966C10.0927 10.2313 10.029 9.86897 9.94254 9.51169C9.88118 9.33425 9.77217 9.17712 9.62748 9.05751C9.41503 8.90666 9.16566 8.81625 8.90592 8.7959H8.87605C8.61632 8.81632 8.36698 8.90673 8.15451 9.05751C8.0096 9.17698 7.9005 9.33415 7.83924 9.51169C7.75307 9.86902 7.68931 10.2313 7.64834 10.5966C7.62998 10.7932 7.59675 10.988 7.54893 11.1796C7.47744 11.3864 7.39056 11.5877 7.28902 11.7816C7.40367 11.6961 7.50591 11.5951 7.59282 11.4815C7.59282 11.4815 7.53662 11.6922 7.52531 11.7229C7.49449 11.7699 7.46708 11.819 7.44328 11.8699C7.4583 11.8699 7.61206 11.9429 7.61206 11.9429L8.20819 12.1334L8.19958 12.4221C8.08625 12.4682 7.98689 12.543 7.9113 12.6392C7.87548 12.7316 7.81841 12.8143 7.74472 12.8805C7.60094 12.9315 7.46246 12.9964 7.33121 13.0741C7.13253 13.187 6.91378 13.2603 6.68715 13.2896C6.45896 13.328 6.31499 13.7045 6.31499 13.7045C6.31464 13.7106 6.29359 14.1341 6.27368 14.5419ZM9.28982 14.8373C11.3578 14.9552 13.8758 14.9842 15.9706 14.8924C15.848 14.3765 15.7382 13.954 15.7382 13.954C15.7382 13.954 15.3331 13.2757 14.5348 13.0651C14.2636 12.9878 14.0063 12.8682 13.7724 12.7109C13.7348 12.6134 13.72 12.5086 13.729 12.4045L13.4731 12.3645C13.4731 12.342 13.4512 12.0104 13.4512 12.0104C13.7587 11.9049 13.7271 11.2824 13.7271 11.2824C13.9224 11.3929 14.0496 10.901 14.0496 10.901C14.2806 10.217 13.9345 10.2581 13.9345 10.2581C13.9951 9.84024 13.9951 9.41586 13.9345 8.99799C13.7808 7.61217 11.4644 7.98794 11.7391 8.44097C11.0622 8.31303 11.2167 9.88648 11.2167 9.88648L11.3636 10.2933C11.1602 10.4278 11.2001 10.5825 11.2446 10.7553C11.2632 10.8274 11.2825 10.9027 11.2854 10.9811C11.2996 11.3739 11.5343 11.2923 11.5343 11.2923C11.5489 11.9401 11.8615 12.0252 11.8615 12.0252C11.9202 12.432 11.8838 12.3618 11.8838 12.3618L11.6054 12.3963C11.6092 12.4887 11.6019 12.5813 11.5835 12.672C11.4219 12.7457 11.3229 12.8042 11.2252 12.8619C11.1246 12.9214 11.0254 12.98 10.8604 13.0537C10.232 13.3343 9.54886 13.7007 9.42766 14.193C9.39512 14.3251 9.34534 14.5617 9.28982 14.8373ZM16.6009 14.86C17.5258 14.806 18.3408 14.7254 18.9595 14.6151C18.9386 14.1847 18.9145 13.6995 18.914 13.693C18.914 13.693 18.7709 13.3189 18.5442 13.2808C18.3191 13.2516 18.1018 13.1789 17.9044 13.0667C17.774 12.9895 17.6364 12.925 17.4936 12.8744C17.4204 12.8085 17.3637 12.7264 17.328 12.6346C17.253 12.5391 17.1543 12.4647 17.0416 12.419L17.0333 12.1321L17.6255 11.9429C17.6255 11.9429 17.7783 11.8703 17.7932 11.8703C17.7695 11.8198 17.7422 11.7709 17.7115 11.7242C17.7003 11.6937 17.6445 11.4845 17.6445 11.4845C17.7308 11.5974 17.8324 11.6977 17.9463 11.7826C17.8454 11.59 17.7591 11.39 17.6881 11.1845C17.6406 10.9942 17.6075 10.8007 17.5893 10.6053C17.5486 10.2425 17.4853 9.88251 17.3997 9.52753C17.3388 9.35114 17.2304 9.195 17.0864 9.07631C16.8754 8.92651 16.6276 8.8367 16.3696 8.81641H16.3399C16.0819 8.83662 15.8342 8.92644 15.6231 9.07631C15.4793 9.19514 15.371 9.35125 15.31 9.52753C15.2242 9.88246 15.1609 10.2425 15.1202 10.6053C15.1046 10.805 15.0731 11.0031 15.0262 11.1978C14.9551 11.3996 14.8672 11.5952 14.7634 11.7825C14.8767 11.6971 15.0035 11.4835 15.0035 11.4835C15.0035 11.4835 14.9918 11.7131 14.9804 11.7437C14.9543 11.7834 14.9323 11.8256 14.9149 11.8697C14.9296 11.8697 15.0825 11.9423 15.0825 11.9423L15.6746 12.1321L15.6661 12.419C15.5535 12.4649 15.3162 12.6391 15.2754 12.6955C15.8148 12.9192 16.3102 13.3665 16.323 13.7329C16.3255 13.7515 16.3539 13.8629 16.3938 14.0188C16.4536 14.2532 16.5393 14.5883 16.6009 14.86Z" fill="#A8ADB4"/></svg>',
					componentId: 'calendar',
					componentParams: {
						showLocation: true,
						isLocked: !this.#isLocationFeatureEnabled(),
					},
					hidden: !this.#canUseAddressBlock() || !this.#canUseCalendarBlock(),
				},
				{
					type: 'delimiter',
					hidden: !this.#canUseAddressBlock() || !this.#canUseCalendarBlock(),
				},
				{
					id: 'link',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_LINK',
					svgData: '<svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4859 14.0353L17.7859 15.3353C18.5909 16.1402 18.5914 17.4575 17.7865 18.2624C16.9815 19.0674 15.6637 19.0674 14.8588 18.2624L13.5588 16.9624C12.9846 16.3883 12.8287 15.5561 13.0731 14.8323L13.474 15.2332C13.8267 15.586 14.4045 15.587 14.7578 15.2338C15.111 14.8805 15.1105 14.3022 14.7578 13.9494L14.3569 13.5485C15.0802 13.3046 15.9118 13.4611 16.4859 14.0353ZM7.27004 10.6737L5.97001 9.37368C5.16509 8.56875 5.16561 7.25145 5.97054 6.44652C6.77547 5.64159 8.09224 5.64159 8.89717 6.44652L10.1972 7.74655C10.7713 8.32068 10.9283 9.15282 10.6839 9.87558L10.047 9.23865C9.69373 8.88538 9.11594 8.88538 8.76267 9.23865C8.4094 9.59192 8.40992 10.1692 8.76319 10.5225L9.40012 11.1594C8.67684 11.4043 7.84417 11.2478 7.27004 10.6737ZM11.4334 6.67575L9.96797 5.21034C8.52873 3.7711 6.1736 3.7711 4.73436 5.21034C3.29512 6.64959 3.29512 9.00471 4.73436 10.444L6.19977 11.9094C7.41658 13.1262 9.28184 13.302 10.7022 12.4615L11.7715 13.5307C10.9304 14.9506 11.1068 16.8164 12.3231 18.0327L13.789 19.4986C15.2283 20.9379 17.5834 20.9379 19.0226 19.4986C20.4619 18.0594 20.4619 15.7043 19.0226 14.265L17.5567 12.7991C16.3404 11.5828 14.4746 11.4064 13.0553 12.2469L11.9861 11.1777C12.826 9.75783 12.6502 7.89257 11.4334 6.67575Z" fill="#A8ADB4"/></svg>',
				},
				{
					id: 'file',
					messageCode: 'CRM_ACTIVITY_TODO_ACTIONS_FILE',
					svgData: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.6123 10.7026C18.7309 10.8212 18.7309 11.0135 18.6123 11.1321L17.8165 11.9279C17.6979 12.0465 17.5056 12.0465 17.387 11.9279L12.7007 7.24153C11.3529 5.89374 9.14743 5.89374 7.79963 7.24153C6.45184 8.58932 6.45184 10.7948 7.79963 12.1426L13.5486 17.8916C14.3938 18.7368 15.7665 18.7368 16.6118 17.8916C17.457 17.0463 17.457 15.6736 16.6118 14.8284L11.4754 9.69206C11.1323 9.34889 10.5933 9.34889 10.2502 9.69206C9.90699 10.0352 9.90699 10.5742 10.2502 10.9173L14.3239 14.991C14.4425 15.1096 14.4425 15.3019 14.3239 15.4205L13.5281 16.2163C13.4095 16.3349 13.2172 16.3349 13.0986 16.2163L9.0249 12.1426C8.00783 11.1255 8.00783 9.48386 9.0249 8.46679C10.042 7.44973 11.6836 7.44973 12.7007 8.46679L17.837 13.6031C19.3561 15.1222 19.3561 17.5977 17.837 19.1168C16.3179 20.6359 13.8424 20.6359 12.3233 19.1168L6.57437 13.3679C4.55268 11.3462 4.55268 8.03795 6.57437 6.01626C8.59606 3.99458 11.9043 3.99458 13.926 6.01626L18.6123 10.7026Z" fill="#A8ADB4"/></svg>',
				},
			];

			this.#prepareActionItems(items);

			this.#actionsPopup = new ActionsPopup(items);
		}

		return this.#actionsPopup;
	}

	#prepareActionItems(items: ActionMenuItem[]): void
	{
		items.forEach((item) => {
			// eslint-disable-next-line no-param-reassign
			item.hidden = (item.id && this.#hiddenActionItems.includes(item.id)) || item.hidden;
		});
	}

	#bindEvents(params: TodoEditorParams): void
	{
		this.#eventEmitter = new EventEmitter();
		this.#eventEmitter.setEventNamespace('Crm.Activity.TodoEditor');

		if (Type.isObject(params.events))
		{
			Object.keys(params.events).forEach((eventName) => {
				if (Type.isFunction(params.events[eventName]))
				{
					this.#eventEmitter.subscribe(eventName, params.events[eventName]);
				}
			});
		}

		EventEmitter.subscribe(Events.EVENT_UPDATE_CLICK, this.onUpdateHandler);
		EventEmitter.subscribe(Events.EVENT_REPEAT_CLICK, this.onRepeatHandler);
	}

	async onUpdateHandler(event: BaseEvent): void
	{
		const canShowPrefilledComponent = await this.#canShowPrefilledComponent();
		if (!canShowPrefilledComponent)
		{
			return;
		}

		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();
		if (!menuBar)
		{
			return;
		}

		menuBar.setActiveItemById('todo');

		const { entityData, blocksData } = await this.#getUpdateOrRepeatActionData(event.getData());

		this
			.setActivityId(entityData.id)
			.setCurrentUser(entityData.currentUser)
		;

		await this.#showPrefilledComponent(entityData, blocksData, TodoEditorMode.UPDATE);
	}

	async #canShowPrefilledComponent(): Promise
	{
		if (Dom.hasClass(this.#container, '--is-edit'))
		{
			return new Promise((resolve) => {
				UI.Dialogs.MessageBox.show({
					modal: true,
					title: Loc.getMessage('CRM_ACTIVITY_TODO_CONFIRM_DIALOG_TITLE'),
					message: Loc.getMessage('CRM_ACTIVITY_TODO_CONFIRM_DIALOG_MESSAGE'),
					buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
					okCaption: Loc.getMessage('CRM_ACTIVITY_TODO_CONFIRM_DIALOG_OK_BUTTON'),
					onOk: (messageBox) => {
						resolve(true);
						messageBox.close();
					},
					onCancel: (messageBox) => {
						resolve(false);
						messageBox.close();
					},
				});
			});
		}

		return Promise.resolve(true);
	}

	async onRepeatHandler(event: BaseEvent): void
	{
		const canShowPrefilledComponent = await this.#canShowPrefilledComponent();
		if (!canShowPrefilledComponent)
		{
			return;
		}

		const { entityData, blocksData } = await this.#getUpdateOrRepeatActionData(event.getData());

		this.#setActiveMenuBarItem();
		this
			.setActivityId(null)
			.setCurrentUser(entityData.currentUser)
			.setDefaultDeadLine()
		;

		entityData.deadline = this.#deadline;
		const calendar = blocksData?.find((blockData) => blockData.id === 'calendar');
		if (Type.isObject(calendar))
		{
			calendar.data.from = this.#deadline.getTime();
		}

		await this.#showPrefilledComponent(entityData, blocksData, TodoEditorMode.COPY);
	}

	#setActiveMenuBarItem(): void
	{
		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();
		if (menuBar)
		{
			menuBar.setActiveItemById('todo');
		}
	}

	async #getUpdateOrRepeatActionData(data: Object): Promise
	{
		const { activityId, ownerTypeId, ownerId } = data;
		const { data: { entityData, blocksData } } = await this.#fetchSettings({
			id: activityId,
			ownerTypeId,
			ownerId,
		});

		return {
			entityData,
			blocksData,
		};
	}

	async #showPrefilledComponent(entityData: Object, blocksData: Object, mode: string = TodoEditorMode.ADD): void
	{
		await this.#initLayoutComponentForEdit(entityData, blocksData);

		this.getTextEditor().expand();
		this.#scrollToTop();
		this.setMode(mode);
		this.setFocused();
	}

	async #initLayoutComponentForEdit(entityData: Object, blocksData: Object[]): Promise
	{
		return new Promise((resolve) => {
			void this.#clearValue().then(() => {
				this.#layoutComponent.setData(entityData);

				blocksData.forEach(({ id, data }) => {
					if (Type.isBoolean(data.active) && data.active === false)
					{
						return;
					}

					this.#layoutComponent.setBlockFilledValues(id, data);
					this.#layoutComponent.setBlockActive(id);
				});

				resolve();
			});
		});
	}

	#scrollToTop(): void
	{
		window.scrollTo({
			top: 0,
			behavior: 'smooth',
		});
	}

	async #fetchSettings({ id, ownerId, ownerTypeId }): Promise
	{
		const data = {
			id,
			ownerId,
			ownerTypeId,
		};

		return new Promise((resolve) => {
			Ajax
				.runAction(this.#getConfigActionPath(), { data })
				.then(resolve)
				.catch((errors) => {
					console.error(errors);
				})
			;
		});
	}

	#getConfigActionPath(): String
	{
		return 'crm.activity.todo.fetchSettings';
	}

	save(): Promise
	{
		if (this.#loadingPromise)
		{
			return this.#loadingPromise;
		}

		// wrap BX.Promise in native js promise
		this.#loadingPromise = new Promise((resolve, reject) => {
			this.#getSaveActionData().then((data) => {
				const analytics = this.#getAnalyticsLabel(data);

				Ajax
					.runAction(this.#getSaveActionPath(), {
						data,
						analytics,
					})
					.then((response) => {
						this.#currentUser = this.#user;
						resolve(response);
					})
					.catch(reject)
				;
			}).catch(reject);
		}).catch((response) => {
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});

			// so that on error returned promise is marked as rejected
			throw response;
		}).finally(() => {
			this.#loadingPromise = null;
		});

		return this.#loadingPromise;
	}

	#getSaveActionData(): Promise
	{
		return new Promise((resolve) => {
			void this.#getSaveActionDataSettings().then((settings) => {
				const userData = this.#layoutComponent.getData();
				const data = {
					ownerTypeId: this.#ownerTypeId,
					ownerId: this.#ownerId,
					title: userData.title,
					description: userData.description,
					responsibleId: userData.responsibleUserId,
					deadline: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), userData.deadline),
					parentActivityId: this.#parentActivityId,
					settings,
					pingOffsets: userData.pingOffsets,
					colorId: userData.colorId,
					isCalendarSectionChanged: userData.isCalendarSectionChanged,
				};

				if (this.#mode === TodoEditorMode.UPDATE)
				{
					data.id = this.#activityId;
				}
				else if (this.#mode === TodoEditorMode.COPY)
				{
					data.isCopy = true;
				}

				resolve(data);
			});
		});
	}

	#getSaveActionPath(): String
	{
		return (this.#mode === TodoEditorMode.UPDATE ? 'crm.activity.todo.update' : 'crm.activity.todo.add');
	}

	#getSaveActionDataSettings(): Promise
	{
		const result = this.#layoutComponent.getExecutedBlocksData();

		return Promise.resolve(result);
	}

	#getAnalyticsLabel(data: Object): ?Object
	{
		const analyticsLabel = this.#getAnalyticsInstance();
		if (analyticsLabel === null)
		{
			return null;
		}

		const isNew = Type.isNil(data.id);
		analyticsLabel
			.setEvent(isNew ? EventIds.activityCreate : EventIds.activityEdit)
			.setElement(isNew ? ElementIds.createButton : ElementIds.editButton)
		;

		// eslint-disable-next-line no-param-reassign
		data = Runtime.clone(data);

		const pingOffsets = data.pingOffsets.map((value) => Number(value));
		const defaultOffsets = this.#pingSettings.selectedValues;
		if (JSON.stringify(pingOffsets) !== JSON.stringify(defaultOffsets))
		{
			analyticsLabel.setPingSettings(data.pingOffsets.join(','));
		}

		const defaultColorId = 'default';
		if (data.colorId !== defaultColorId)
		{
			analyticsLabel.setColorId(data.colorId);
		}

		const blockTypes = [];
		const calendarBlockId = TodoEditorBlocksCalendar.methods.getId();
		data.settings.forEach((block) => {
			if (data.isCalendarSectionChanged && block.id === calendarBlockId)
			{
				blockTypes.push('section_calendar');
			}
			else
			{
				blockTypes.push(block.id);
			}
		});
		if (Type.isArrayFilled(blockTypes))
		{
			analyticsLabel.setBlockTypes(blockTypes);
		}

		if (this.#defaultTitle !== data.title)
		{
			analyticsLabel.setIsTitleChanged();
		}

		if (this.#defaultDescription !== data.description && Type.isStringFilled(data.description))
		{
			analyticsLabel.setIsDescriptionChanged();
		}

		return analyticsLabel.getData();
	}

	#getBlocks(): BlockSettings[]
	{
		const blocks = [];

		if (this.#canUseCalendarBlock())
		{
			blocks.push(this.#getCalendarBlockSettings());
		}

		blocks.push(
			this.#getClientBlockSettings(),
			this.#getLinkBlockSettings(),
			this.#getFileBlockSettings(),
		);

		if (this.#canUseAddressBlock())
		{
			blocks.push(this.#getAddressBlockSettings());
		}

		return blocks;
	}

	#getCalendarBlockSettings(): BlockSettings
	{
		return {
			id: TodoEditorBlocksCalendar.methods.getId(),
			title: Loc.getMessage('CRM_ACTIVITY_TODO_CALENDAR_BLOCK_TITLE'),
			icon: 'crm-activity__todo-editor-v2_calendar-icon.svg',
			settings: this.#calendarSettings,
		};
	}

	#getClientBlockSettings(): BlockSettings
	{
		return {
			id: TodoEditorBlocksClient.methods.getId(),
			title: Loc.getMessage('CRM_ACTIVITY_TODO_CLIENT_BLOCK_TITLE'),
			icon: 'crm-activity__todo-editor-v2_client-icon-v2.svg',
			settings: {
				entityTypeId: this.#ownerTypeId,
				entityId: this.#ownerId,
			},
		};
	}

	#getLinkBlockSettings(): BlockSettings
	{
		return {
			id: TodoEditorBlocksLink.methods.getId(),
			title: Loc.getMessage('CRM_ACTIVITY_TODO_LINK_BLOCK_TITLE'),
			icon: 'crm-activity__todo-editor-v2_link-icon-v2.svg',
		};
	}

	#getFileBlockSettings(): BlockSettings
	{
		return {
			id: TodoEditorBlocksFile.methods.getId(),
			title: Loc.getMessage('CRM_ACTIVITY_TODO_FILE_BLOCK_TITLE'),
			icon: 'crm-activity__todo-editor-v2_file-icon.svg',
			settings: {
				entityTypeId: this.#ownerTypeId,
				entityId: this.#ownerId,
			},
		};
	}

	#getAddressBlockSettings(): BlockSettings
	{
		return {
			id: TodoEditorBlocksAddress.methods.getId(),
			title: Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_BLOCK_TITLE'),
			icon: 'crm-activity__todo-editor-v2_address-icon-v2.svg',
			settings: {
				entityTypeId: this.#ownerTypeId,
				entityId: this.#ownerId,
			},
		};
	}

	#canUseAddressBlock(): boolean
	{
		const settings = Extension.getSettings('crm.activity.todo-editor-v2');

		return settings?.canUseAddressBlock === true;
	}

	#canUseCalendarBlock(): boolean
	{
		const settings = Extension.getSettings('crm.activity.todo-editor-v2');

		return settings?.canUseCalendarBlock === true;
	}

	getDeadline(): ?Date
	{
		return this.#layoutComponent?.getData().deadline ?? null;
	}

	getDescription(): String
	{
		return this.getTextEditor().getText();
	}

	setParentActivityId(activityId: Number): TodoEditorV2
	{
		this.#parentActivityId = activityId;

		return this;
	}

	setActivityId(activityId: ?Number): TodoEditorV2
	{
		this.#activityId = activityId;

		return this;
	}

	setCurrentUser(currentUser: Object): TodoEditorV2
	{
		this.#currentUser = currentUser;

		return this;
	}

	setDeadline(deadLine: String): TodoEditorV2
	{
		const value = DateTimeFormat.parse(deadLine);
		if (Type.isDate(value))
		{
			this.#layoutComponent.setDeadline(value);
			this.#deadline = value;
		}

		return this;
	}

	setDefaultDeadLine(isNeedUpdateLayout: Boolean = true): TodoEditorV2
	{
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		const defaultDate = BX.parseDate(Loc.getMessage('CRM_TIMELINE_TODO_EDITOR_DEFAULT_DATETIME'));
		if (Type.isDate(defaultDate))
		{
			this.#deadline = defaultDate;
		}
		else
		{
			this.#deadline = new Date();
			this.#deadline.setMinutes(0);
			this.#deadline.setTime(this.#deadline.getTime() + 60 * 60 * 1000); // next hour
		}

		if (isNeedUpdateLayout)
		{
			this.#layoutComponent.setDeadline(this.#deadline);
		}

		return this;
	}

	setFocused(): void
	{
		this.getTextEditor().focus(null, { defaultSelection: 'rootEnd' });
	}

	setDescription(description: String): TodoEditorV2
	{
		this.getTextEditor().setText(description);

		return this;
	}

	cancel(params: CancelParams = {}): Promise
	{
		const animateCollapse = this.#shouldAnimateCollapse();

		if (params?.sendAnalytics === false)
		{
			this.getTextEditor().collapse(animateCollapse);

			return this.#clearValue();
		}

		const analytics = this.#getAnalyticsInstance();
		if (analytics === null)
		{
			this.getTextEditor().collapse(animateCollapse);

			return this.#clearValue();
		}

		analytics
			.setEvent(EventIds.activityCancel)
			.setElement(ElementIds.cancelButton)
		;

		const subSection = params?.analytics?.subSection;
		if (Type.isStringFilled(subSection))
		{
			analytics.setSubSection(subSection);
		}

		const element = params?.analytics?.element;
		if (Type.isStringFilled(element))
		{
			analytics.setElement(element);
		}

		const notificationSkipPeriod = params?.analytics?.notificationSkipPeriod;
		if (Type.isStringFilled(notificationSkipPeriod))
		{
			analytics.setNotificationSkipPeriod(notificationSkipPeriod);
		}

		analytics.send();

		this.getTextEditor().collapse(animateCollapse);

		return this.#clearValue();
	}

	#getAnalyticsInstance(): ?Analytics
	{
		const data = this.#analytics?.getData();

		if (!data)
		{
			return null;
		}

		return new Analytics(data.c_section, data.c_sub_section);
	}

	#clearValue(): Promise
	{
		this.#parentActivityId = null;

		return this.#clearData();
	}

	resetToDefaults(): Promise
	{
		this.setDescription(this.#getDefaultDescription());
		this.getTextEditor().collapse(this.#shouldAnimateCollapse());

		return this.#clearData();
	}

	#shouldAnimateCollapse(): boolean
	{
		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();

		return menuBar && menuBar.getFirstItemIdWithLayout() === 'todo';
	}

	#clearData(): Promise
	{
		this.#currentUser = this.#user;

		this.setDefaultDeadLine();
		this.setMode(TodoEditorMode.ADD);

		this.#layoutComponent.resetTitleAndDescription();
		this.#layoutComponent.resetPingOffsetsToDefault();
		this.#layoutComponent.resetResponsibleUserToDefault(this.#currentUser);
		this.#layoutComponent.resetColorSelectorToDefault();
		this.#layoutComponent.resetCurrentActivityId();

		Dom.removeClass(this.#container, '--is-edit');
		this.#layoutComponent.closeBlocks();

		return new Promise((resolve) => {
			setTimeout(resolve, 10);
		});
	}

	#getDefaultTitle(): String
	{
		return Loc.getMessage('CRM_ACTIVITY_TODO_ADD_TITLE_DEFAULT');
	}

	#getDefaultDescription(): String
	{
		let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT';
		if (this.#ownerTypeId === BX.CrmEntityType.enumeration.deal)
		{
			messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT_DEAL';
		}

		return Loc.getMessage(messagePhrase);
	}

	#onInputFocus(): void
	{
		Dom.addClass(this.#container, '--is-edit');
		this.#eventEmitter.emit('onFocus');
	}

	#onSaveHotkeyPressed(): void
	{
		this.#eventEmitter.emit('onSaveHotkeyPressed');
	}

	#isValidBorderColor(borderColor: string): boolean
	{
		return Type.isString(borderColor) && TodoEditorV2.BorderColor[borderColor.toUpperCase()];
	}

	#getClassname(): string
	{
		return `crm-activity__todo-editor-v2 --border-${this.#borderColor}`;
	}

	#isLocationFeatureEnabled(): boolean
	{
		return Extension.getSettings('crm.activity.todo-editor-v2').get('locationFeatureEnabled');
	}
}
