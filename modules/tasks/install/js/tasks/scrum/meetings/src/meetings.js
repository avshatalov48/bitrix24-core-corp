import { Event, Loc, Tag, Text, Type, Dom, ajax } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Loader } from 'main.loader';
import { Menu } from 'main.popup';

import { PopupComponentsMaker } from 'ui.popupcomponentsmaker';

import { CreatedEvent, CreatedEventType } from './created.event';
import { ListEvents } from './list.events';
import { RequestSender } from './request.sender';
import { Culture, CultureData } from './culture';

import 'ui.design-tokens';
import 'ui.fonts.opensans';
import 'ui.hint';
import 'ui.icons.b24';
import 'ui.icons.service';

import '../css/base.css';

type Params = {
	groupId: number
}

type MeetingsResponse = {
	data: {
		listEvents: Array<CreatedEventType>,
		mapCreatedEvents: ?MapCreatedEvents,
		todayEvent: ?CreatedEventType,
		isTemplatesClosed: boolean,
		defaultSprintDuration: number,
		calendarSettings: CalendarSettings,
		culture: CultureData
	}
}

type CalendarSettings = {
	workTimeStart: number,
	weekDays: Object,
	weekStart: Object,
	interval: number
}

type ChatsResponse = {
	data: {
		chats: Array<Chat>
	}
}

type MapCreatedEvents = {
	daily?: number,
	planning?: number,
	review?: number,
	retrospective?: number
}

type Chat = {
	id: number,
	type: string,
	icon: string,
	name: string,
	users: Array<ChatUser>
}

type ChatUser = {
	id: number,
	photo: {
		src: string,
	},
	name: string,
	pathToUser: string
}

type EventTemplate = {
	id: string,
	entityId: string,
	name: string,
	desc: string,
	from: Date,
	to: Date,
	rrule: Rrule,
	roles: Array<EntityList>,
	uiClass: string,
	color: string,
	hint: string
}

type EntityList = {
	id: string,
	entityId: string
}

type Rrule = {
	FREQ: string,
	INTERVAL: number,
	BYDAY: {
		WE: string,
		FR: string
	}
}

export class Meetings
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);

		this.requestSender = new RequestSender();

		this.todayEvent = new CreatedEvent();
		this.listEvents = new ListEvents();
		this.listEvents.subscribe('showView', this.onShowView.bind(this));

		this.menu = null;
		this.eventTemplatesMenu = null;
	}

	showMenu(targetNode: HTMLElement)
	{
		if (this.menu && this.menu.isShown())
		{
			this.menu.close();

			return;
		}

		const response = this.requestSender.getMeetings({
			groupId: this.groupId,
		}).then((meetingsResponse: MeetingsResponse) => {
			Culture.getInstance().setData(meetingsResponse.data.culture);

			return meetingsResponse;
		});

		this.menu = new PopupComponentsMaker({
			id: 'tasks-scrum-meetings-widget',
			target: targetNode,
			cacheable: false,
			content: [
				{
					html: [
						{
							html: this.renderMeetings(response),
						},
					],
				},
				{
					html: [
						{
							html: this.renderChats(response),
						},
					],
				},
			],
		});

		this.menu.show();
	}

	renderMeetings(response: Promise): Promise
	{
		return response
			.then((meetingsResponse: MeetingsResponse) => {
				this.meetingsNode = Tag.render`
					<div class="tasks-scrum__widget-meetings tasks-scrum__widget-meetings--scope">
						${this.renderMeetingsHeader(meetingsResponse)}
						${this.renderEventTemplates(meetingsResponse)}
						${this.renderScheduledMeetings(meetingsResponse)}
					</div>
				`
				;

				return this.meetingsNode;
			})
			.catch((meetingsResponse) => {
				this.requestSender.showErrorAlert(meetingsResponse);
			})
		;
	}

	renderChats(response: Promise): HTMLElement
	{
		return response
			.then((chatsResponse: ChatsResponse) => {
				const chats = chatsResponse.data.chats;

				return Tag.render`
					<div class="tasks-scrum__widget-meetings tasks-scrum__widget-meetings--scope">
						<div class="tasks-scrum__widget-meetings--header">
							<div
								class="ui-icon ui-icon-service-livechat tasks-scrum__widget-meetings--icon-chats"
							><i></i></div>
							<div class="tasks-scrum__widget-meetings--header-title">
								${Loc.getMessage('TSM_CHATS_HEADER_TITLE')}
							</div>
						</div>
						${this.renderChatsList(chats)}
						${this.renderChatsEmpty(chats)}
	
					</div>
				`;
			})
			.catch((errorResponse) => {
				this.requestSender.showErrorAlert(errorResponse);
			})
		;
	}

	renderChatsList(chats: Array<Chat>): HTMLElement
	{
		const visibility = chats.length > 0 ? '--visible' : '';

		return Tag.render`
			<div class="tasks-scrum__widget-meetings--chat-content ${visibility}">
				${
					chats.map((chat: Chat) => {
						const chatIconClass = chat.icon === '' ? 'default' : '';
						const chatIconStyle = chat.icon === '' ? '' : `background-image: url('${chat.icon}');`;
						const chatNode = Tag.render`
							<div class="tasks-scrum__widget-meetings--chat-container">
								<div class="ui-icon ui-icon-common-company tasks-scrum__widget-meetings--chat-icon">
									<i
									class="chat-icon ${chatIconClass}"
										style="${chatIconStyle}"
									></i>
								</div>
								<div class="tasks-scrum__widget-meetings--chat-info">
									<div class="tasks-scrum__widget-meetings--chat-name">
										${chat.name}
									</div>
									<div class="users-icon tasks-scrum__widget-meetings--chat-users">
										${this.renderChatUser(chat.users)}
									</div>
								</div>
							</div>
						`;

						Event.bind(chatNode, 'click', this.openChat.bind(this, chat, chatNode));

						return chatNode;
					})
				}
			</div>
		`;
	}

	openChat(chat: Chat, chatNode: HTMLElement)
	{
		const loader = new Loader({
			target: chatNode,
			size: 34,
			mode: 'inline',
			color: 'rgba(82, 92, 105, 0.9)',
		});

		loader.show();

		this.requestSender.getChat({
			groupId: this.groupId,
			chatId: chat.id,
		})
			.then(() => {
				if (top.window.BXIM)
				{
					top.BXIM.openMessenger(`chat${parseInt(chat.id, 10)}`);

					this.menu.close();
				}
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderChatUser(users: Array<ChatUser>): HTMLElement
	{
		const uiIconClasses = 'tasks-scrum__widget-meetings--chat-icon-user ui-icon ui-icon-common-user';

		return Tag.render`
			${
				users.map((user: ChatUser) => {
					const src = user.photo ? encodeURI(Text.encode(user.photo.src)) : null;
					const photoStyle = src ? `background-image: url('${src}');` : '';

					return Tag.render`
						<div class="user-icon ${uiIconClasses}" title="${Text.encode(user.name)}">
							<i style="${photoStyle}"></i>
						</div>
					`;
				})
			}
		`;
	}

	renderChatsEmpty(chats: Array<Chat>): HTMLElement
	{
		const visibility = chats.length > 0 ? '' : '--visible';

		return Tag.render`
			<div class="tasks-scrum__widget-meetings--content">
				<div class="tasks-scrum__widget-meetings--empty-chats ${visibility}">
					<div class="tasks-scrum__widget-meetings--empty-name">
						${Loc.getMessage('TSM_CHATS_EMPTY_TITLE')}
					</div>
					<div class="tasks-scrum__widget-meetings--empty-text">
						${Loc.getMessage('TSM_CHATS_EMPTY_TEXT')}
					</div>
				</div>
			</div>
		`;
	}

	renderMeetingsHeader(response: MeetingsResponse): HTMLElement
	{
		const calendarSettings = response.data.calendarSettings;

		const uiClasses = 'ui-btn-split ui-btn-light-border ui-btn-xs ui-btn-light ui-btn-no-caps ui-btn-round';

		const node = Tag.render`
			<div class="tasks-scrum__widget-meetings--header">

				<div class="ui-icon ui-icon-service-calendar tasks-scrum__widget-meetings--icon-calendar">
					<i></i>
				</div>

				<div class="tasks-scrum__widget-meetings--header-title">
					${Loc.getMessage('TSM_MEETINGS_HEADER_TITLE')}
				</div>

				<div class="tasks-scrum__widget-meetings--btn-create ${uiClasses}">
					<button class="ui-btn-main" data-role="create-default-event">
						${Loc.getMessage('TSM_MEETINGS_CREATE_BUTTON')}
					</button>
					<div class="ui-btn-menu" data-role="show-menu-event-templates"></div>
				</div>

			</div>
		`;

		const button = node.querySelector('button');
		const menu = node.querySelector('.ui-btn-menu');

		Event.bind(button, 'click', this.showEventSidePanel.bind(this));
		Event.bind(menu, 'click', this.showMenuWithEventTemplates.bind(this, button, calendarSettings));

		return node;
	}

	renderEventTemplates(response: MeetingsResponse): HTMLElement
	{
		const mapCreatedEvents = response.data.mapCreatedEvents;
		const listEvents = response.data.listEvents;
		const isTemplatesClosed = response.data.isTemplatesClosed;
		const calendarSettings = response.data.calendarSettings;

		const templateVisibility = (
			isTemplatesClosed
			|| this.isAllEventsCreated(mapCreatedEvents)
				? ''
				: '--visible'
		);
		const emptyVisibility = (isTemplatesClosed && listEvents.length === 0 ? '--visible' : '');

		const contentVisibilityClass = emptyVisibility === '' && templateVisibility === '' ? '--content-hidden' : '';

		const node = Tag.render`
			<div class="tasks-scrum__widget-meetings--content ${contentVisibilityClass}">

				<div class="tasks-scrum__widget-meetings--creation-block ${templateVisibility}">
					<span
						class="tasks-scrum__widget-meetings--creation-close-btn"
						data-role="close-event-templates"
					></span>
					<div class="tasks-scrum__widget-meetings--create-element-info">
						${Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATES_INFO')}
					</div>
					${
						[...this.getEventTemplates(calendarSettings).values()]
							.map((eventTemplate: EventTemplate) => {
								if (
									Type.isArray(mapCreatedEvents)
									|| Type.isUndefined(mapCreatedEvents[eventTemplate.id])
								)
								{
									return this.renderEventTemplate(eventTemplate);
								}

									return '';
							})
					}
				</div>
	
				<div class="tasks-scrum__widget-meetings--empty-meetings ${emptyVisibility}">
					<div class="tasks-scrum__widget-meetings--empty-name">
						${Loc.getMessage('TSM_MEETINGS_EMPTY_TITLE')}
					</div>
					<div class="tasks-scrum__widget-meetings--empty-text">
						${Loc.getMessage('TSM_MEETINGS_EMPTY_TEXT')}
					</div>
					<button class="tasks-scrum__widget-meetings--one-click-btn ui-qr-popupcomponentmaker__btn">
						${Loc.getMessage('TSM_MEETINGS_CREATE_ONE_CLICK')}
					</button>
				</div>
			</div>
		`;

		const closeButton = node.querySelector('.tasks-scrum__widget-meetings--creation-close-btn');
		const oneClickButton = node.querySelector('.tasks-scrum__widget-meetings--one-click-btn');

		const templatesNode = node.querySelector('.tasks-scrum__widget-meetings--creation-block');
		const emptyNode = node.querySelector('.tasks-scrum__widget-meetings--empty-meetings');

		Event.bind(closeButton, 'click', () => {
			Dom.removeClass(templatesNode, '--visible');
			const isExistsEvent = listEvents.length;
			if (!isExistsEvent)
			{
				Dom.addClass(emptyNode, '--visible');
			}
			this.requestSender.closeTemplates({
				groupId: this.groupId,
			})
				.catch((errorResponse) => {
					this.requestSender.showErrorAlert(errorResponse);
				})
			;
		});
		Event.bind(oneClickButton, 'click', () => {
			Dom.addClass(templatesNode, '--visible');
			Dom.removeClass(emptyNode, '--visible');
		});

		return node;
	}

	renderScheduledMeetings(response: MeetingsResponse): HTMLElement
	{
		const todayEvent = response.data.todayEvent;
		const listEvents = response.data.listEvents;

		const todayEventVisibility = (Type.isNull(todayEvent) ? '' : '--visible');

		this.listEvents.setTodayEvent(todayEvent);

		return Tag.render`
			<div class="tasks-scrum__widget-meetings--timetable">
				<div class="tasks-scrum__widget-meetings--timetable-container ${todayEventVisibility}">
					<div class="tasks-scrum__widget-meetings--timetable-title">
						${Loc.getMessage('TSM_TODAY_EVENT_TITLE')}
					</div>
					${this.todayEvent.render(todayEvent)}
				</div>
				${this.listEvents.render(listEvents, todayEvent)}
			</div>
		`;
	}

	renderEventTemplate(eventTemplate: EventTemplate): HTMLElement
	{
		const node = Tag.render`
			<div class="tasks-scrum__widget-meetings--create-element ${Text.encode(eventTemplate.uiClass)}">
				<div class="tasks-scrum__widget-meetings--create-element-title">
					<span class="tasks-scrum__widget-meetings--create-element-name">
						${Text.encode(eventTemplate.name)}
					</span>
					<span class="ui-hint">
						<i
							class="ui-hint-icon"
							data-hint="${eventTemplate.hint}"
							data-hint-no-icon
							data-hint-html
						></i>
					</span>
				</div>
				<div class="tasks-scrum__widget-meetings--create-btn">
					<button
						class="ui-qr-popupcomponentmaker__btn"
						data-role="create-event-template-${eventTemplate.id}"
					>
						${Loc.getMessage('TSM_MEETINGS_CREATE_BUTTON')}
					</button>
				</div>
			</div>
		`;

		const createButton = node.querySelector('.tasks-scrum__widget-meetings--create-btn');

		Event.bind(createButton, 'click', this.openCalendarSidePanel.bind(this, eventTemplate));

		this.initHints(node);

		return node;
	}

	onShowView()
	{
		this.menu.close();
	}

	showEventSidePanel()
	{
		this.openCalendarSidePanel();
	}

	showMenuWithEventTemplates(targetNode: HTMLElement, calendarSettings: CalendarSettings)
	{
		if (this.eventTemplatesMenu && this.eventTemplatesMenu.getPopupWindow().isShown())
		{
			this.eventTemplatesMenu.close();

			return;
		}

		this.eventTemplatesMenu = new Menu({
			id: 'tsm-event-templates-menu',
			bindElement: targetNode,
			closeByEsc: true,
		});

		this.eventTemplatesMenu.addMenuItem({
			text: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_BLANK'),
			delimiter: true,
		});

		this.getEventTemplates(calendarSettings)
			.forEach((eventTemplate: EventTemplate) => {
				this.eventTemplatesMenu.addMenuItem({
					text: eventTemplate.name,
					onclick: (event, menuItem) => {
						this.openCalendarSidePanel(eventTemplate);
						menuItem.getMenuWindow().close();
					},
				});
			})
		;

		this.eventTemplatesMenu.getPopupWindow()
			.subscribe('onClose', () => {
				this.eventTemplatesMenu.destroy();
			})
		;

		this.eventTemplatesMenu.show();
	}

	openCalendarSidePanel(eventTemplate?: EventTemplate)
	{
		const participantsEntityList = eventTemplate ? eventTemplate.roles : [];

		const formData = eventTemplate
			? {
				name: eventTemplate.name,
				description: eventTemplate.desc,
				from: eventTemplate.from,
				to: eventTemplate.to,
				color: eventTemplate.color,
				rrule: eventTemplate.rrule,
			}
			: {
				name: '',
				description: '',
				color: '#86b100',
			}
		;

		const sliderId = Text.getRandom();

		new window.top.BX.Calendar.SliderLoader(
			0,
			{
				sliderId: sliderId,
				participantsSelectorEntityList: [
					{
						id: 'user',
					},
					{
						id: 'project-roles',
						options: {
							projectId: this.groupId,
						},
						dynamicLoad: true,
					},
				],
				formDataValue: formData,
				participantsEntityList: participantsEntityList,
				type: 'group',
				ownerId: this.groupId,
			},
		).show();

		top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onLoad', (event: BaseEvent) => {
			const [sliderEvent] = event.getCompatData();
			if (sliderId === sliderEvent.getSlider().getUrl().toString())
			{
				top.BX.Event.EventEmitter.subscribeOnce(
					'BX.Calendar:onEntrySave',
					(baseEvent: BaseEvent) => {
						const data = baseEvent.getData();
						if (sliderId === data.sliderId)
						{
							if (eventTemplate)
							{
								this.requestSender.saveEventInfo({
									groupId: this.groupId,
									templateId: eventTemplate.id,
									eventId: data.responseData.entryId,
								})
									.then(() => {
										this.menu.close();
									})
									.catch((response) => {
										this.requestSender.showErrorAlert(response);
									})
								;
							}

							ajax.runAction(
								'bitrix:tasks.scrum.info.saveAnalyticsLabel',
								{
									data: {},
									analyticsLabel: {
										scrum: 'Y',
										action: 'create_meet',
										template: eventTemplate ? eventTemplate.id : 'custom',
									},
								},
							);
						}
					},
				);
			}
		});
	}

	getEventTemplates(calendarSettings: CalendarSettings): Set<EventTemplate>
	{
		const eventTemplates = new Set();

		const daysNumberMap = {
			MO: 1,
			TU: 2,
			WE: 3,
			TH: 4,
			FR: 5,
			SA: 6,
			SU: 7,
		};

		const weekStartDay = calendarSettings.weekStart[Object.keys(calendarSettings.weekStart)[0]];
		const weekStartDayNumber = daysNumberMap[weekStartDay];

		eventTemplates.add({
			id: 'daily',
			entityId: 'project-roles',
			name: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_DAILY'),
			desc: '',
			from: new Date((new Date()).setHours(calendarSettings.workTimeStart, 0, 0)),
			to: new Date((new Date()).setHours(calendarSettings.workTimeStart, 15, 0)),
			rrule: {
				FREQ: 'WEEKLY',
				INTERVAL: 1,
				BYDAY: calendarSettings.weekDays,
			},
			roles: [
				{
					id: `${this.groupId}_M`,
					entityId: 'project-roles',
				},
				{
					id: `${this.groupId}_E`,
					entityId: 'project-roles',
				},
			],
			uiClass: 'widget-meetings__sprint-daily',
			color: '#2FC6F6',
			hint: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_DAILY'),
		});

		eventTemplates.add({
			id: 'planning',
			entityId: 'project-roles',
			name: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_PLANNING'),
			desc: '',
			from: new Date(
				this.getNextWeekStartDate(weekStartDayNumber)
					.setHours(calendarSettings.workTimeStart, 0, 0),
			),
			to: new Date(
				this.getNextWeekStartDate(weekStartDayNumber)
					.setHours(calendarSettings.workTimeStart + 1, 0, 0),
			),
			rrule: {
				FREQ: 'WEEKLY',
				INTERVAL: calendarSettings.interval,
				BYDAY: calendarSettings.weekStart,
			},
			roles: [
				{
					id: `${this.groupId}_A`,
					entityId: 'project-roles',
				},
				{
					id: `${this.groupId}_M`,
					entityId: 'project-roles',
				},
				{
					id: `${this.groupId}_E`,
					entityId: 'project-roles',
				},
			],
			uiClass: 'widget-meetings__sprint-planning',
			color: '#DA51D4',
			hint: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_PLANNING'),
		});

		eventTemplates.add({
			id: 'review',
			entityId: 'project-roles',
			name: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_REVIEW'),
			desc: '',
			from: new Date(
				this.getNextWeekStartDate(weekStartDayNumber)
					.setHours(calendarSettings.workTimeStart, 0, 0),
			),
			to: new Date(
				this.getNextWeekStartDate(weekStartDayNumber)
					.setHours(calendarSettings.workTimeStart + 1, 0, 0),
			),
			rrule: {
				FREQ: 'WEEKLY',
				INTERVAL: calendarSettings.interval,
				BYDAY: calendarSettings.weekStart,
			},
			roles: [
				{
					id: `${this.groupId}_A`,
					entityId: 'project-roles',
				},
				{
					id: `${this.groupId}_M`,
					entityId: 'project-roles',
				},
				{
					id: `${this.groupId}_E`,
					entityId: 'project-roles',
				},
			],
			uiClass: 'widget-meetings__sprint-review',
			color: '#FF5752',
			hint: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_REVIEW'),
		});

		eventTemplates.add({
			id: 'retrospective',
			entityId: 'project-roles',
			name: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_RETROSPECTIVE'),
			desc: '',
			from: new Date(
				this.getNextWeekStartDate(weekStartDayNumber)
					.setHours(calendarSettings.workTimeStart, 0, 0),
			),
			to: new Date(
				this.getNextWeekStartDate(weekStartDayNumber)
					.setHours(calendarSettings.workTimeStart + 1, 0, 0),
			),
			rrule: {
				FREQ: 'WEEKLY',
				INTERVAL: calendarSettings.interval,
				BYDAY: calendarSettings.weekStart,
			},
			roles: [
				{
					id: `${this.groupId}_M`,
					entityId: 'project-roles',
				},
				{
					id: `${this.groupId}_E`,
					entityId: 'project-roles',
				},
			],
			uiClass: 'widget-meetings__sprint-retrospective',
			color: '#FF5752',
			hint: Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_RETROSPECTIVE'),
		});

		return eventTemplates;
	}

	isAllEventsCreated(mapCreatedEvents: ?MapCreatedEvents): boolean
	{
		if (Type.isArray(mapCreatedEvents))
		{
			return false;
		}

		return (
			Type.isInteger(mapCreatedEvents.daily)
			&& Type.isInteger(mapCreatedEvents.planning)
			&& Type.isInteger(mapCreatedEvents.review)
			&& Type.isInteger(mapCreatedEvents.retrospective)
		);
	}

	getNextWeekStartDate(weekStartDayNumber: number): Date
	{
		const date = new Date();
		const targetDate = new Date();

		const delta = weekStartDayNumber - date.getDay();

		if (delta >= 0)
		{
			targetDate.setDate(date.getDate() + delta);
		}
		else
		{
			targetDate.setDate(date.getDate() + 7 + delta);
		}

		return targetDate;
	}

	initHints(node: HTMLElement)
	{
		BX.UI.Hint.init(node);
	}
}
