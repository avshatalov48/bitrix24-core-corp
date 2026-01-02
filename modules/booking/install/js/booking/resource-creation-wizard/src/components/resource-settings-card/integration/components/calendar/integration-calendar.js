import { createNamespacedHelpers } from 'ui.vue3.vuex';
import { BIcon as Icon, Main } from 'ui.icon-set.api.vue';

import { EntitySelectorEntity, Model } from 'booking.const';
import { Switcher } from 'booking.component.switcher';
import { Reminder as UiReminder } from 'booking.component.reminder';

import { SettingsItem } from '../settings-item';
import { SettingsSelector } from '../settings-selector';

const { mapGetters: mapResourceGetters, mapActions } = createNamespacedHelpers(Model.ResourceCreationWizard);

// @vue/component
export const IntegrationCalendar = {
	name: 'IntegrationCalendar',
	components: {
		Icon,
		Switcher,
		SettingsItem,
		SettingsSelector,
		UiReminder,
	},
	setup(): IntegrationCalendarData
	{
		const iconName = Main.CALENDAR_1;
		const iconColor = 'var(--ui-color-primary)';
		const iconSize = 22;
		const entityRoomId = EntitySelectorEntity.Room;
		const entityUserId = EntitySelectorEntity.User;

		return {
			iconName,
			iconColor,
			iconSize,
			entityRoomId,
			entityUserId,
		};
	},
	data(): Object
	{
		return {
			isVisible: null,
		};
	},
	computed: {
		...mapResourceGetters({
			entityCalendar: 'entityCalendar',
			isIntegrationCalendarEnabled: 'isIntegrationCalendarEnabled',
			invalidIntegrationCalendarUser: 'invalidIntegrationCalendarUser',
		}),
		isEnabled: {
			get(): boolean
			{
				return this.$store.getters[`${Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`];
			},
			set(isIntegrationCalendarEnabled: boolean): boolean
			{
				this.$store.dispatch(
					`${Model.ResourceCreationWizard}/setIsIntegrationCalendarEnabled`,
					isIntegrationCalendarEnabled,
				);
			},
		},
		reminders: {
			get(): Array
			{
				return (this.entityCalendar?.data?.reminders || []).map((r) => r.count);
			},
			set(reminders: number[]): void
			{
				this.updateResourceEntityCalendar({
					reminders: reminders.map((r: number) => {
						return {
							type: 'min',
							count: r,
						};
					}),
				});
			},
		},
		isDisabled(): boolean
		{
			if (!this.isEnabled)
			{
				return true;
			}

			return !this.isEnabled || !this.entityCalendar || (this.entityCalendar?.data.userIds.length === 0);
		},
		userErrorMessage(): string | null
		{
			if (!this.isEnabled || !this.invalidIntegrationCalendarUser)
			{
				return null;
			}

			return this.loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_USER_ERROR_MESSAGE');
		},
	},
	watch: {
		invalidIntegrationCalendarUser(invalid: boolean): void
		{
			if (invalid)
			{
				this.scrollToUserField();
			}
		},
	},
	beforeMount(): void
	{
		this.isVisible = this.$store.getters[`${Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`];
	},
	methods: {
		...mapActions([
			'updateResource',
			'updateResourceEntityCalendar',
			'createResourceEntityCalendar',
			'setInvalidIntegrationCalendarUser',
		]),
		async toggleEnabled(): void
		{
			if (!this.entityCalendar)
			{
				await this.createResourceEntityCalendar();
			}

			this.isEnabled = !this.isEnabled;

			if (this.isEnabled && !this.isVisible)
			{
				this.isVisible = true;
			}

			if (!this.isEnabled)
			{
				await this.setInvalidIntegrationCalendarUser(false);
			}
		},
		async updateUsers(userIds: number[]): Pormise<void>
		{
			await this.updateResourceEntityCalendar({
				userIds: userIds.map((id) => parseInt(id, 10)),
			});

			if (userIds.length > 0)
			{
				await this.setInvalidIntegrationCalendarUser(false);
			}
		},
		async updateLocationId(locationIds: number[]): Promise<void>
		{
			await this.updateResourceEntityCalendar({
				locationId: locationIds.length > 0 ? (parseInt(locationIds[0], 10) ?? null) : null,
			});
		},
		scrollToUserField(): void
		{
			this.$refs.user.$el?.scrollIntoView(true, {
				behavior: 'smooth',
				block: 'center',
			});
		},
	},
	template: `
		<div class="resource-creation-wizard__integration-block">
			<div class="resource-creation-wizard__integration-block-header">
				<Icon :name="iconName" :size="iconSize" :color="iconColor"/>
				<div class="resource-creation-wizard__integration-block-title">
					{{ loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_TITLE') }}
				</div>
				<Switcher
					class="resource-creation-wizard__integration-block-switcher"
					data-id="resource-creation-wizard__integration-block-switcher-calendar"
					:hiddenText="true"
					:model-value="isEnabled"
					@toggle="toggleEnabled"
				/>
			</div>
			<div class="resource-creation-wizard__integration-block-description">
				{{ loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_TEXT') }}
			</div>
			<div
				v-if="isVisible"
				class="resource-creation-wizard__integration-block-settings"
			>
				<SettingsItem
					ref="user"
					:title="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_USER_TITLE')"
					:description="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_USER_TEXT')"
					:errorMessage="userErrorMessage"
				>
					<SettingsSelector
						:class="{ '--warning': entityCalendar?.data?.userIds.length === 0 && entityCalendar?.data?.locationId}"
						id="resource-creation-wizard__integration-block-users"
						data-element="resource-creation-wizard__integration-block-users"
						:entitiesId="entityUserId"
						:values="entityCalendar?.data.userIds ?? []"
						:disabled="!isEnabled"
						@change="updateUsers"
					/>
				</SettingsItem>
				<SettingsItem
					:title="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_ROOM_TITLE')"
					:description="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_ROOM_TEXT')"
				>
					<SettingsSelector
						id="resource-creation-wizard__integration-block-resource"
						data-element="resource-creation-wizard__integration-block-resource"
						:entitiesId="entityRoomId"
						:multiple="false"
						:values="entityCalendar?.data.locationId ? [String(entityCalendar.data.locationId)] : []"
						:disabled="isDisabled"
						tab
						@change="updateLocationId"
					/>
				</SettingsItem>
				<SettingsItem
					:title="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_REMINDER_TITLE')"
					:description="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_REMINDER_TEXT')"
				>
					<UiReminder
						v-model="reminders"
						:disabled="isDisabled"
					/>
				</SettingsItem>
			</div>
		</div>
	`,
};

type IntegrationCalendarData = {
	iconName: string;
	iconColor: string;
	iconSize: number;
	entityRoomId: string;
	entityUserId: string;
}
