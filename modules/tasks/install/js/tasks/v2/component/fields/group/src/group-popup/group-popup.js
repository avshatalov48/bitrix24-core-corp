import { Event, Loc } from 'main.core';
import type { PopupOptions } from 'main.popup';

import { Popup } from 'ui.vue3.components.popup';
import { Button as UiButton, AirButtonStyle, ButtonSize } from 'ui.vue3.components.button';

import { GroupType, Model } from 'tasks.v2.const';
import { openGroup } from 'tasks.v2.lib.open-group';
import { groupService } from 'tasks.v2.provider.service.group-service';
import { userService } from 'tasks.v2.provider.service.user-service';
import type { GroupModel } from 'tasks.v2.model.groups';
import type { TaskModel } from 'tasks.v2.model.tasks';
import './group-popup.css';

export const GroupPopup = {
	components: {
		Popup,
		UiButton,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		getBindElement: {
			type: Function,
			required: true,
		},
	},
	emits: ['close'],
	setup(): Object
	{
		return {
			AirButtonStyle,
			ButtonSize,
		};
	},
	data(): Object
	{
		return {
			isPopupShown: false,
			/** @type GroupInfo */
			groupInfo: {},
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		group(): ?GroupModel
		{
			return this.$store.getters[`${Model.Groups}/getById`](this.task.groupId);
		},
		bindElement(): HTMLElement
		{
			return this.getBindElement();
		},
		popupId(): string
		{
			return 'tasks-field-group-popup';
		},
		popupOptions(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				padding: 24,
				minWidth: 260,
				maxWidth: 400,
				offsetTop: 8,
				targetContainer: document.body,
			};
		},
		groupOwnerUrl(): string
		{
			return userService.getUrl(this.groupInfo.ownerId);
		},
		groupMembersCountFormatted(): string
		{
			return Loc.getMessagePlural('TASKS_V2_GROUP_COUNT_MEMBERS', this.groupInfo.numberOfMembers, {
				'#COUNT#': this.groupInfo.numberOfMembers,
			});
		},
		groupAboutFormatted(): string
		{
			return {
				[GroupType.Collab]: this.loc('TASKS_V2_GROUP_ABOUT_COLLAB'),
				[GroupType.Scrum]: this.loc('TASKS_V2_GROUP_ABOUT_SCRUM'),
			}[this.group?.type] ?? this.loc('TASKS_V2_GROUP_ABOUT');
		},
	},
	mounted(): void
	{
		Event.bind(this.bindElement, 'click', this.handleClick);
		Event.bind(this.bindElement, 'mouseenter', this.handleMouseEnter);
		Event.bind(this.bindElement, 'mouseleave', this.handleMouseLeave);
	},
	methods: {
		openGroup(): void
		{
			void openGroup(this.group.id, this.group.type);
		},
		handleClick(): void
		{
			this.clearTimeouts();
		},
		handleMouseEnter(): void
		{
			if (!this.group)
			{
				return;
			}

			this.groupInfoPromise = groupService.getGroupInfo(this.group.id);

			this.clearTimeouts();
			this.showTimeout = setTimeout(() => this.showPopup(), 400);
		},
		handleMouseLeave(): void
		{
			Event.unbind(document, 'mouseover', this.updateHoverElement);
			Event.bind(document, 'mouseover', this.updateHoverElement);

			this.clearTimeouts();
			this.closeTimeout = setTimeout(() => {
				if (!this.$refs.container?.contains(this.hoverElement) && !this.bindElement.contains(this.hoverElement))
				{
					this.closePopup();
				}
			}, 100);
		},
		updateHoverElement(event: MouseEvent): void
		{
			this.hoverElement = event.target;
		},
		async showPopup(): Promise<void>
		{
			if (this.groupInfoPromise)
			{
				this.groupInfo = await this.groupInfoPromise;
			}

			if (!this.group)
			{
				return;
			}

			this.clearTimeouts();
			this.isPopupShown = true;
			await this.$nextTick();
			this.$refs.popup.adjustPosition();
		},
		closePopup(): void
		{
			this.clearTimeouts();
			this.isPopupShown = false;
			Event.unbind(this.$refs.container, 'mouseleave', this.handleMouseLeave);
			Event.unbind(document, 'mouseover', this.updateHoverElement);
		},
		clearTimeouts(): void
		{
			clearTimeout(this.closeTimeout);
			clearTimeout(this.showTimeout);
		},
	},
	template: `
		<Popup
			v-if="isPopupShown"
			:id="popupId"
			:options="popupOptions"
			ref="popup"
			@close="closePopup"
		>
			<div class="tasks-field-group-popup" ref="container">
				<div class="tasks-field-group-popup-header">
					<img class="tasks-field-group-popup-image" :src="group?.image" :alt="group?.name">
					<div class="tasks-field-group-popup-title-container">
						<div class="tasks-field-group-popup-title" :title="group?.name">{{ group?.name }}</div>
						<div class="tasks-field-group-popup-subtitle">{{ groupMembersCountFormatted }}</div>
					</div>
				</div>
				<div class="tasks-field-group-popup-button">
					<UiButton
						:text="groupAboutFormatted"
						:style="AirButtonStyle.OUTLINE_ACCENT_2"
						:size="ButtonSize.SMALL"
						:wide="true"
						@click="openGroup"
					/>
				</div>
				<div class="tasks-field-group-popup-info">
					<div class="tasks-field-group-popup-field">
						<div class="tasks-field-group-popup-field-title">{{ loc('TASKS_V2_GROUP_OWNER') }}</div>
						<div class="tasks-field-group-popup-field-value">
							<a :href="groupOwnerUrl">{{ groupInfo.ownerName }}</a>
						</div>
					</div>
					<div class="tasks-field-group-popup-field">
						<div class="tasks-field-group-popup-field-title">{{ loc('TASKS_V2_GROUP_DATE_CREATE') }}</div>
						<div class="tasks-field-group-popup-field-value">{{ groupInfo.dateCreate }}</div>
					</div>
					<div class="tasks-field-group-popup-field">
						<div class="tasks-field-group-popup-field-title">{{ loc('TASKS_V2_GROUP_SUBJECT') }}</div>
						<div class="tasks-field-group-popup-field-value">{{ groupInfo.subjectTitle }}</div>
					</div>
				</div>
			</div>
		</Popup>
	`,
};
