import { mapGetters } from 'ui.vue3.vuex';
import { hint } from 'ui.vue3.directives.hint';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import { limit } from 'booking.lib.limit';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { ClientPopup } from 'booking.component.client-popup';
import { Model } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import type { ClientData } from 'booking.model.clients';

import './client.css';

export const Empty = {
	emits: ['popupShown', 'popupClosed'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	directives: { hint },
	components: {
		Button,
		Icon,
		ClientPopup,
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isLoading: true,
			shownClientPopup: false,
		};
	},
	methods: {
		showClientPopup(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			this.shownClientPopup = true;
			this.$emit('popupShown');
		},
		hideClientPopup(): void
		{
			this.shownClientPopup = false;
			this.$emit('popupClosed');
		},
		async addClientsToBook(clients: ClientData[]): Promise<void>
		{
			const booking = this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
			await bookingService.update({
				id: booking.id,
				clients,
			});
		},
	},
	computed: {
		...mapGetters({
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		btnIcon(): string
		{
			return this.isFeatureEnabled ? ButtonIcon.ADD : ButtonIcon.LOCK;
		},
		userIcon(): string
		{
			return IconSet.PERSON;
		},
		personSize(): number
		{
			return 26;
		},
		callIcon(): string
		{
			return IconSet.TELEPHONY_HANDSET_1;
		},
		messageIcon(): string
		{
			return IconSet.CHATS_1;
		},
		iconSize(): number
		{
			return 20;
		},
		iconColor(): string
		{
			return 'var(--ui-color-palette-gray-20)';
		},
		soonHint(): Object
		{
			return {
				text: this.loc('BOOKING_BOOKING_SOON_HINT'),
				popupOptions: {
					offsetLeft: -60,
				},
			};
		},
	},
	template: `
		<div class="booking-actions-popup__item-client-icon-container">
			<div class="booking-actions-popup__item-client-icon">
				<Icon :name="userIcon" :size="personSize" :color="iconColor"/>
			</div>
		</div>
		<div class="booking-actions-popup__item-client-info --empty">
			<div class="booking-actions-popup__item-client-info-label --empty">
				{{loc('BB_ACTIONS_POPUP_CLIENT_EMPTY_NAME_LABEL')}}
			</div>
			<div class="booking-actions-popup__item-client-info-empty">
				<div></div>
				<div></div>
			</div>
			<div
				class="booking-actions-popup-item-buttons booking-actions-popup__item-client-info-btn"
				ref="clientButton"
			>
				<Button
					:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_EMPTY_LABEL')"
					:size="ButtonSize.EXTRA_SMALL"
					:color="ButtonColor.PRIMARY"
					:icon="btnIcon"
					:round="true"
					@click="showClientPopup"
				/>
			</div>
			<ClientPopup
				v-if="shownClientPopup"
				:bindElement="this.$refs.clientButton"
				@create="addClientsToBook"
				@close="hideClientPopup"
			/>
		</div>
		<div v-hint="soonHint" class="booking-actions-popup__item-client-action">
			<Icon :name="callIcon" :size="iconSize" :color="iconColor"/>
			<Icon :name="messageIcon" :size="iconSize" :color="iconColor"/>
		</div>
	`,
};
