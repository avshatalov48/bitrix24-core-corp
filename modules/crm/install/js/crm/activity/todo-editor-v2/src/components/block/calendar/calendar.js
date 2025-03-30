import { SectionSelector as CalendarSectionSelector } from 'calendar.controls';
import 'ui.design-tokens';
import { Planner } from 'calendar.planner';
import { ajax as Ajax, Text, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { DateTimeFormat, Timezone } from 'main.date';
import { Dialog } from 'ui.entity-selector';
import type { BlockSettings } from '../../../todo-editor';
import { Events } from '../../todo-editor';
import { LocationSelector } from './location-selector';
import { SectionSelector } from './section-selector';

export const TodoEditorBlocksCalendar = {
	components: {
		LocationSelector,
		SectionSelector,
	},

	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		settings: {
			type: Object,
			required: true,
		},
		context: {
			type: Object,
			required: true,
		},
		filledValues: {
			type: Object,
		},
		isFocused: {
			type: Boolean,
		},
	},

	emits: [
		'close',
		'updateFilledValues',
	],

	data(): Object
	{
		const ownerId = this.settings.ownerId || this.context.userId;

		const selectedUserIds: Set<number> = new Set([this.settings.userId]);
		selectedUserIds.add(ownerId);

		const timestamp = (this.settings.from || Timezone.UserTime.getTimestamp()) * 1000;
		const millisecondsInFiveMinutes = 5 * 60 * 1000;

		// round timestamp to 5 minutes
		const from = Math.ceil(timestamp / millisecondsInFiveMinutes) * millisecondsInFiveMinutes;

		const duration = Number(this.settings.duration ?? 60 * 60) * 1000;
		const to = from + duration;

		const data = {
			selectedUserIds,
			from,
			to,
			duration,
			plannerInstance: null,
			showLocation: this.settings.showLocation ?? false,
			locationId: null,
			timezoneName: this.settings.timezoneName,
			ownerId,
			sectionId: this.settings.sectionId || null,
			config: {},
			canUseCalendarSectionSelector: (
				Type.isFunction(CalendarSectionSelector.getModes)
				&& CalendarSectionSelector.getModes().includes('inline')
			),
			sectionSelectorReadOnly: this.settings.sectionSelectorReadOnly ?? false,
		};

		return this.getPreparedData(data);
	},

	mounted(): void
	{
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_RESPONSIBLE_USER_CHANGE, this.onResponsibleUserChange);
		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_DEADLINE_CHANGE, this.onDeadlineChange);

		this.showPlanner();

		this.getPlanner().selector.subscribe('onChange', this.handlePlannerSelectorChanges.bind(this));

		const userIds = this.selectedUsersIdsArray;
		const data = this.prepareUpdatePlannerData(userIds);
		this.updatePlanner(userIds, data);

		if (this.settings.showUserSelector && this.isFocused)
		{
			this.showUserSelectorDialog();
		}
	},

	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe(Events.EVENT_DEADLINE_CHANGE, this.onDeadlineChange);
	},

	methods: {
		/* eslint-disable no-param-reassign */
		getPreparedData(data: Object): Object
		{
			const { filledValues } = this;

			if (Type.isObject(filledValues))
			{
				if (Type.isObject(filledValues.attendeesEntityList))
				{
					Object
						.values(filledValues.attendeesEntityList)
						.filter(({ entityId }) => entityId === 'user')
						.forEach(({ id }) => data.selectedUserIds.add(id))
					;
				}

				if (Type.isStringFilled(filledValues.location))
				{
					data.showLocation = true;
					data.locationId = Number(filledValues.location.split('_')[1]); //calendar_7_123, need 7 as id
				}

				if (Type.isObject(filledValues.selectedUserIds))
				{
					data.selectedUserIds = filledValues.selectedUserIds;
				}

				data.from = Number(filledValues.from);
				data.to = Number(filledValues.to);
				data.duration = Number(filledValues.duration);
				data.timezoneName = filledValues.timezoneFrom;
				data.ownerId = filledValues.ownerId;
				data.sectionId = filledValues.sectionId;

				if (!Type.isNil(filledValues.sectionId))
				{
					data.sectionId = Number(filledValues.sectionId);
				}
			}

			data.config = {};
			data.sectionSelectorReadOnly = false;

			if (Type.isObject(filledValues?.config))
			{
				data.config = filledValues.config;
			}
			else if (data.canUseCalendarSectionSelector)
			{
				void this.fetchConfig().then((response) => {
					this.config = response.data ?? {};
					this.sectionSelectorReadOnly = data.config.readOnly ?? false;

					if (Type.isNil(data.sectionId))
					{
						const defaultSection = data.config.sections.find((section) => section.DEFAULT === true);
						this.sectionId = defaultSection ? defaultSection.ID : data.config.sections[0].ID;
					}
				});
			}

			return data;
		},
		/* eslint-enable no-param-reassign */
		getId(): string
		{
			return 'calendar';
		},
		showPlanner(): void
		{
			this.getPlanner().show();
		},
		getPlanner(): Planner
		{
			if (this.plannerInstance === null)
			{
				this.plannerInstance = new Planner({
					wrap: this.$refs.plannerContainer,
					compactMode: false,
					showEntryName: false,
					minWidth: 770,
					minHeight: 104,
					height: 104,
					width: 770,
					entryTimezone: this.timezoneName,
				});
			}

			return this.plannerInstance;
		},
		prepareUpdatePlannerData(newUserIds: number[], oldUserIds: number[] = []): Object
		{
			const location = (this.locationId ? this.location : '');

			const data = {
				ownerId: this.ownerId,
				type: 'user',
				entityList: [],
				dateFrom: this.getFormattedDate('beforeOneWeek'),
				dateTo: this.getFormattedDate('afterTwoWeeks'),
				timezone: this.timezoneName,
				location,
				entries: false,
				prevUserList: oldUserIds,
				skipFeatureCheck: 'Y',
			};

			newUserIds.forEach((userId) => {
				data.entityList.push({
					entityId: 'user',
					id: userId,
					entityType: 'employee',
				});
			});

			return data;
		},
		updatePlanner(userIds: number[], data: Object): void
		{
			this.getPlanner().showLoader();

			Ajax
				.runAction('calendar.api.calendarajax.updatePlanner', { data })
				.then(
					(response) => {
						const accessibility = {};
						userIds.forEach((userId) => {
							if (response.data.accessibility[userId])
							{
								accessibility[userId] = response.data.accessibility[userId];
							}
							else
							{
								accessibility[userId] = [];
							}
						});

						if (this.locationId)
						{
							const roomId = `room_${this.locationId}`;
							accessibility[roomId] = response.data.accessibility[roomId];
						}

						this.getPlanner().hideLoader();
						this.getPlanner().update(response.data.entries, accessibility);

						this.onDataUpdate();
					},
					(response) => {
						console.error(response);
					},
				)
				.catch((errors) => {
					console.error(errors);
				})
			;
		},
		onDataUpdate(): void
		{
			this.updatePlannerSelector();

			this.$Bitrix.eventEmitter.emit(Events.EVENT_CALENDAR_CHANGE, {
				from: this.from,
				to: this.from + this.duration,
			});

			this.emitUpdateFilledValues();
		},
		emitUpdateFilledValues(): void
		{
			let { filledValues } = this;
			const { to, from, duration, location, selectedUserIds, ownerId, sectionId, config } = this;

			const newFilledValues = {
				to,
				from,
				duration,
				location,
				selectedUserIds,
				ownerId,
				sectionId,
				config,
			};
			filledValues = { ...filledValues, ...newFilledValues };
			this.$emit('updateFilledValues', this.getId(), filledValues);
		},
		updatePlannerSelector(): void
		{
			const dateFrom = this.createDateInstance(this.from);
			const dateTo = this.createDateInstance(this.from + this.duration);

			this.getPlanner().updateSelector(dateFrom, dateTo);
		},
		createDateInstance(timestamp: number | null = null, startOfDay: boolean = false): Date
		{
			if (!timestamp)
			{
				// eslint-disable-next-line no-param-reassign
				timestamp = Date.now();
			}

			const date = new Date(timestamp);
			if (startOfDay)
			{
				date.setHours(0, 0, 0, 0);
			}

			return date;
		},
		getFormattedDate(id: string): string
		{
			return this.getFormattedValue(id, DateTimeFormat.getFormat('SHORT_DATE_FORMAT'));
		},
		getFormattedValue(id: string, format: string): string
		{
			let timestamp = 0;

			switch (id)
			{
				case 'beforeOneWeek':
				{
					timestamp = this.from - 8 * 24 * 60 * 60 * 1000;
					break;
				}

				case 'from':
				{
					timestamp = this.from;
					break;
				}

				case 'to':
				{
					timestamp = this.from + this.duration;
					break;
				}

				case 'afterTwoWeeks':
				{
					timestamp = this.from + 14 * 24 * 60 * 60 * 1000;
					break;
				}

				default:
					timestamp = 0;
			}

			return DateTimeFormat.format(format, timestamp / 1000);
		},
		toggleCalendarSelectorDialog(): void
		{
			const sectionSelector = this.$refs.sectionSelector;
			if (sectionSelector.isShown())
			{
				sectionSelector.hide();
			}
			else
			{
				sectionSelector.show();
			}
		},
		showUserSelectorDialog(): void
		{
			setTimeout(() => {
				const dialog = Dialog.getById('todo-editor-calendar-user-selector-dialog');

				if (dialog?.isOpen())
				{
					dialog.hide();
				}
				else
				{
					this.getUserSelectorDialog().show();
				}
			}, 5);
		},
		getUserSelectorDialog(): Dialog
		{
			const preselectedItems = [];

			this.selectedUsersIdsArray.forEach((id) => {
				preselectedItems.push(['user', id]);
			});

			const undeselectedItems = [
				['user', this.context.userId],
				['user', this.settings.userId],
			];

			return new Dialog({
				id: 'todo-editor-calendar-user-selector-dialog',
				targetNode: this.$refs.userSelector,
				context: 'CRM_ACTIVITY_TODO_CALENDAR_RESPONSIBLE_USER',
				multiple: true,
				dropdownMode: true,
				showAvatars: true,
				enableSearch: true,
				width: 450,
				zIndex: 2500,
				entities: [{ id: 'user' }],
				preselectedItems,
				undeselectedItems,
				events: {
					'Item:onSelect': this.onSelectUser,
					'Item:onDeselect': this.onDeselectUser,
				},
			});
		},
		onSelectUser({ data: { item } }): void
		{
			this.selectedUserIds.add(item.id);
		},
		onDeselectUser({ data: { item } }): void
		{
			this.selectedUserIds.delete(item.id);
		},
		getSelectedUserIds(): Number[]
		{
			return this.selectedUserIds ?? [];
		},
		onResponsibleUserChange(event: BaseEvent): void
		{
			const { responsibleUserId } = event.getData();

			this.ownerId = responsibleUserId;
			this.selectedUserIds.add(responsibleUserId);
		},
		onDeadlineChange(event: BaseEvent): void
		{
			const data = event.getData();
			if (data)
			{
				const deadline = data.deadline.getTime();
				this.from = deadline;
				this.to = this.from + deadline;
			}
		},
		handlePlannerSelectorChanges({ data: { dateFrom, dateTo } }): void
		{
			this.from = dateFrom.getTime();
			this.duration = dateTo.getTime() - this.from;

			this.emitCalendarChange();
		},
		updateSettings(data: Object | null): void
		{
			if (!data || !data.deadline)
			{
				return;
			}

			this.from = data.deadline.getTime();
		},
		onSelectLocation({ action, id }): void
		{
			this.locationId = (action === 'select' ? id : null);
		},
		onCloseLocationBlock(): void
		{
			this.locationId = null;
			this.showLocation = false;
		},
		getExecutedData(): Object
		{
			const { duration, from, location, sectionId } = this;

			return {
				from,
				to: from + duration,
				duration,
				selectedUserIds: [...this.getSelectedUserIds()],
				sectionId,
				location,
			};
		},
		prepareDataOnBlockConstruct(data: BlockSettings, params: Object): Object
		{
			// eslint-disable-next-line no-param-reassign
			data.settings.from = params.currentDeadline.getTime() / 1000;

			// eslint-disable-next-line no-param-reassign
			data.settings.ownerId = params.responsibleUserId;

			// eslint-disable-next-line no-param-reassign
			data.settings.userId = params.userId;
		},
		fetchConfig(): Promise
		{
			const data = {
				activityId: this.context.activityId,
				entityTypeId: this.context.itemIdentifier?.entityTypeId,
				entityId: this.context.itemIdentifier?.entityId,
			};

			return new Promise((resolve) => {
				void Ajax.runAction('crm.activity.todo.getCalendarConfig', { data })
					.then((response) => {
						resolve(response);
					})
				;
			});
		},
		onChangeSection(sectionId: number): void
		{
			if (this.sectionId === sectionId)
			{
				return;
			}

			this.sectionId = sectionId;
			this.emitUpdateFilledValues();
			this.emitCalendarChange({ sectionId });
		},
		emitCalendarChange(additional: Object = {}): void
		{
			const data = {
				...additional,
				from: this.from,
				to: this.from + this.duration,
			};

			this.$Bitrix.eventEmitter.emit(Events.EVENT_CALENDAR_CHANGE, data);
		},
		hasSections(): boolean
		{
			return Type.isArrayFilled(this.config?.sections);
		},
	},

	computed: {
		encodedTitle(): string
		{
			return Text.encode(this.title);
		},
		iconStyles(): Object
		{
			if (!this.icon)
			{
				return {};
			}

			const path = `/bitrix/js/crm/activity/todo-editor-v2/images/${this.icon}`;

			return {
				background: `url('${encodeURI(Text.encode(path))}') center center`,
			};
		},
		usersList(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CALENDAR_BLOCK_USERS_LIST');
		},
		location(): string
		{
			return this.locationId ? `calendar_${this.locationId}` : '';
		},
		selectedUsersIdsArray(): number[]
		{
			return [...this.selectedUserIds];
		},
		changeTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CALENDAR_BLOCK_CHANGE_ACTION');
		},
	},

	created()
	{
		this.$watch(
			'ownerId',
			(newOwnerId: number, oldOwnerId: number) => {
				if (this.selectedUserIds.has(newOwnerId))
				{
					const userIds = this.selectedUsersIdsArray;
					const data = this.prepareUpdatePlannerData(userIds);
					this.updatePlanner(userIds, data);
				}
			},
		);

		this.$watch(
			'selectedUserIds',
			(newUserIds, oldUserIds) => {
				const data = this.prepareUpdatePlannerData(newUserIds, oldUserIds);
				this.updatePlanner(newUserIds, data);
			},
			{
				deep: true,
			},
		);

		this.$watch(
			'settings',
			(newSettings, oldSettings) => {
				const showLocation = Boolean(newSettings.showLocation ?? false);
				this.showLocation = Type.isStringFilled(this.filledValues?.location) || showLocation;

				if (oldSettings.showUserSelector !== newSettings.showUserSelector && newSettings.showUserSelector)
				{
					this.showUserSelectorDialog();
				}
			},
			{
				deep: true,
			},
		);
	},

	watch: {
		duration(): void
		{
			this.onDataUpdate();
		},
		from(): void
		{
			this.onDataUpdate();
		},
		to(): void
		{
			this.onDataUpdate();
		},
		locationId(newLocationId, oldLocationId): void
		{
			const newUserIds = this.selectedUsersIdsArray;

			const data = this.prepareUpdatePlannerData(newUserIds);

			this.updatePlanner(newUserIds, data);
		},
	},

	template: `
		<div class="crm-activity__todo-editor-v2_block-header">
			<span
				class="crm-activity__todo-editor-v2_block-header-icon"
				:style="iconStyles"
			></span>
			<span>{{ encodedTitle }}</span>
			<span
				v-if="canUseCalendarSectionSelector"
				class="crm-activity__todo-editor-v2_block-header-data"
			>
				<SectionSelector 
					ref="sectionSelector"
					:userId="context.userId"
					:sections="config?.sections ?? []"
					:trackingUsersList="config?.trackingUsersList ?? []"
					:selectedSectionId="sectionId"
					:readOnly="sectionSelectorReadOnly"
					@change="onChangeSection"
				/>
			</span>
			<span v-if="canUseCalendarSectionSelector && hasSections && !sectionSelectorReadOnly">
				<span
					@click="toggleCalendarSelectorDialog"
					class="crm-activity__todo-editor-v2_block-header-action"
				>
					{{ changeTitle }}
				</span>
			</span>
			<div
				@click="$emit('close', id)"
				class="crm-activity__todo-editor-v2_block-header-close"
			></div>
		</div>
		<div class="crm-activity__todo-editor-v2_block-subheader">
			<span
				ref="userSelector"
				@click="showUserSelectorDialog"
				class="crm-activity__todo-editor-v2_block-subheader-action"
			>
				{{ usersList }} ({{ selectedUsersIdsArray.length }})
			</span>
		</div>
		<div class="crm-activity__todo-editor-v2_block-body">
			<div class="crm-activity__settings_popup__calendar-container">
				<div ref="plannerContainer" class="crm-activity__settings_popup__calendar__planner-container"></div>
			</div>
		</div>
		<div v-if="showLocation">
			<LocationSelector
				@change="onSelectLocation"
				@close="onCloseLocationBlock"
				:locationId="locationId"
				:forceShowLocationSelectorDialog="isFocused && !this.settings.showUserSelector"
			/>
		</div>
	`,
};
