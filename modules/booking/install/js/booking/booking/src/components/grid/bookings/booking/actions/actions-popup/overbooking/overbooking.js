import { Text } from 'main.core';
import { computed } from 'ui.vue3';
import { mapGetters } from 'ui.vue3.vuex';
import { hint } from 'ui.vue3.directives.hint';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { limit } from 'booking.lib.limit';
import { bookingService } from 'booking.provider.service.booking-service';
import { BookingAnalytics } from 'booking.lib.analytics';
import { ClientData } from 'booking.model.clients';
import type { BookingModel, DealData } from 'booking.model.bookings';
import 'ui.icon-set.main';

import './overbooking.css';

type OverbookingData = {
	plusIcon: string,
	plusIconSize: number,
	plusIconColor: string,
};

// @vue/component
export const Overbooking = {
	name: 'BookingActionsPopupOverbooking',
	components: {
		Icon,
	},
	directives: {
		hint,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['close'],
	setup(props): OverbookingData
	{
		const plusIcon = IconSet.PLUS_20;
		const plusIconSize = 20;
		const plusIconColor = computed((): string => {
			return props.disabled ? 'var(--ui-color-palette-gray-20)' : 'var(--ui-color-palette-gray-60)';
		});

		return {
			plusIcon,
			plusIconSize,
			plusIconColor,
		};
	},
	computed: {
		...mapGetters({
			getBookingById: `${Model.Bookings}/getById`,
			dictionary: `${Model.Dictionary}/getBookingVisitStatuses`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			timezone: `${Model.Interface}/timezone`,
			embedItems: `${Model.Interface}/embedItems`,
		}),
		booking(): BookingModel
		{
			return this.getBookingById(this.bookingId);
		},
		hasOverbookingHint(): Object | undefined
		{
			if (!this.disabled)
			{
				return undefined;
			}

			return {
				text: this.loc('BB_ACTIONS_POPUP_OVERBOOKING_DISABLED_HINT'),
			};
		},
		clients(): ClientData[]
		{
			const clients = this.embedItems.filter((item: DealData) => {
				return item.entityTypeId === 'CONTACT' || item.entityTypeId === 'COMPANY';
			});

			return clients.map((item: DealData) => {
				return {
					id: item.value,
					type: {
						code: item.entityTypeId,
						module: item.moduleId,
					},
				};
			});
		},
	},
	methods: {
		async addOverbooking(): void
		{
			if (this.disabled)
			{
				return;
			}

			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			const overbooking: BookingModel = {
				...this.booking,
				id: Text.getRandom(10),
				clients: this.clients,
				counter: 0,
				counters: [],
				createdAt: Date.now(),
				externalData: this.embedItems,
				isConfirmed: false,
				name: null,
				note: null,
				resourcesIds: [this.resourceId],
				primaryClient: undefined,
				rrule: null,
				timezoneFrom: this.timezone,
				timezoneTo: this.timezone,
				updatedAt: Date.now(),
				visitStatus: this.dictionary.Unknown,
			};
			delete overbooking.name;

			const result = await bookingService.add(overbooking);
			if (result.success && result.booking)
			{
				BookingAnalytics.sendAddBooking({ isOverbooking: true });
			}

			this.$emit('close');
		},
	},
	template: `
		<div
			v-hint="hasOverbookingHint"
			class="booking-actions-popup__item-overbooking-button"
			:class="{'--disabled': disabled}"
			role="button"
			tabindex="0"
			@click="addOverbooking"
		>
			<Icon :name="plusIcon" :size="plusIconSize" :color="plusIconColor"/>
			<div class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_LABEL') }}
			</div>
		</div>
	`,
};
