import { Text } from 'main.core';
import { hint } from 'ui.vue3.directives.hint';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.crm';
import 'ui.hint';

import { Switcher } from 'booking.component.switcher';
import { NotificationChannel } from 'booking.const';
import { ChannelMenu } from '../channel-menu/channel-menu';

type Messenger = $Values<NotificationChannel>;

export const ResourceNotification = {
	name: 'ResourceNotification',
	emits: ['update:checked'],
	directives: { hint },
	props: {
		title: {
			type: String,
			required: true,
		},
		view: {
			type: String,
			required: true,
		},
		checked: {
			type: Boolean,
			default: false,
		},
		hideSwitcher: {
			type: Boolean,
			default: false,
		},
		disableSwitcher: {
			type: Boolean,
			default: false,
		},
	},
	data(): { messenger: Messenger }
	{
		return {
			IconSet,
			messenger: NotificationChannel.WhatsApp,
		};
	},
	components: {
		UiSwitcher: Switcher,
		ChannelMenu,
		Icon,
	},
	created(): void
	{
		this.hintManager = BX.UI.Hint.createInstance({
			id: this.containerId,
			popupParameters: {
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
			},
		});
	},
	mounted(): void
	{
		this.hintManager.init(this.$el);
	},
	updated(): void
	{
		this.hintManager.init(this.$el);
	},
	computed: {
		containerId(): string
		{
			return `brwc-resource-notification-container-${Text.getRandom(5)}`;
		},
		templateHeader(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_TEXT');
		},
		soonHint(): Object
		{
			return {
				text: this.loc('BRCW_BOOKING_SOON_HINT'),
				popupOptions: {
					offsetLeft: 60,
					targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
				},
			};
		},
	},
	methods: {
		handleChannelChange(selectedChannel: string): void
		{
			this.messenger = selectedChannel;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { [this.templateTypeField]: selectedType });
		},
		getChooseTemplateButton(): HTMLElement
		{
			return this.$refs.chooseTemplateBtn || null;
		},
	},
	template: `
		<div class="ui-form resource-creation-wizard__form-notification">
			<div class="resource-creation-wizard__form-notification-info">
				<div class="resource-creation-wizard__form-notification-info-title-row">
					<Icon :name="IconSet.CHAT_LINE" :class="{'--disabled': !checked}"/>
					<div
						class="resource-creation-wizard__form-notification-info-title"
						:class="{'--disabled': !checked}"
					>
						{{ title }}
					</div>
					<div
						v-if="!hideSwitcher"
						class="resource-creation-wizard__form-notification-info-switcher"
					>
						<UiSwitcher
							:data-id="'brcw-resource-notification-info-switcher-' + view"
							:model-value="checked"
							:disabled="disableSwitcher"
							@update:model-value="$emit('update:checked', $event)"
							v-hint="disableSwitcher && soonHint"
						/>
					</div>
				</div>
				<div class="resource-creation-wizard__form-notification-info-text-row">
					<div class="resource-creation-wizard__form-notification__info-text-row-message-text">
						{{ templateHeader }}
						<ChannelMenu
							:current-channel="messenger"
							@update:value="handleChannelChange"
						/>
					</div>
				</div>
				<slot name="notification-template" :messenger="messenger"/>
			</div>
			<slot/>
		</div>
	`,
};
