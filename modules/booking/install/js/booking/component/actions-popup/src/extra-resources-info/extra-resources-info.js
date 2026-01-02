import { Loc } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as UiIcon, Outline, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { Button as UiButton, AirButtonStyle, ButtonColor, ButtonSize, ButtonStyle } from 'booking.component.button';
import type { BookingModel } from 'booking.model.bookings';
import { bookingService } from 'booking.provider.service.booking-service';

import { ExtraResourcesDialog } from './extra-resources-dialog';
import { ExtraResourcesInfoPopup } from './extra-resources-info-popup';
import './extra-resources-info.css';

// @vue/component
export const ExtraResourcesInfo = {
	name: 'ExtraResourceInfo',
	components: {
		UiButton,
		UiIcon,
		ExtraResourcesDialog,
		ExtraResourcesInfoPopup,
	},
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	setup(): ExtraResourcesInfoData
	{
		const iconProductName = Outline.PRODUCT;
		const iconEditName = Outline.EDIT_M;
		const iconHelpName = IconSet.HELP;

		return {
			iconProductName,
			iconEditName,
			iconHelpName,
			AirButtonStyle,
			ButtonColor,
			ButtonSize,
			ButtonStyle,
		};
	},
	data(): Object
	{
		return {
			shownResourcesSelector: false,
			showDialogInfo: false,
		};
	},
	computed: {
		...mapGetters({
			getBookingById: `${Model.Bookings}/getById`,
		}),
		booking(): BookingModel
		{
			return this.getBookingById(this.id);
		},
		subtitle(): string
		{
			if (this.hasExtraResources)
			{
				const count = this.extraResourcesIds.length;

				return Loc.getMessagePlural('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_SUBTITLE', count, {
					'#COUNT#': count,
				});
			}

			return this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_SUBTITLE_EMPTY');
		},
		extraResourcesIds(): number[]
		{
			return this.booking.resourcesIds.slice(1);
		},
		hasExtraResources(): boolean
		{
			return this.extraResourcesIds.length > 0;
		},
		iconProductColor(): string
		{
			return this.hasExtraResources ? 'rgba(0, 117, 255, 1)' : 'rgba(201, 204, 208, 1)';
		},
	},
	methods: {
		toggleResourcesSelector(): void
		{
			this.shownResourcesSelector = true;
			this.$emit('freeze');
		},
		toggleResourcesInfo(): void
		{
			this.showDialogInfo = this.hasExtraResources ? !this.showDialogInfo : this.showDialogInfo;
		},
		hideResourcesSelector(): void
		{
			this.shownResourcesSelector = false;
			this.$emit('unfreeze');
		},
		saveBookingExtraResources(extraResources: number[]): void
		{
			this.hideResourcesSelector();

			if (!this.hasExtraResourcesChanged(extraResources))
			{
				return;
			}

			const resourcesIds = new Set([this.booking.resourcesIds[0], ...extraResources]);

			void bookingService.update({
				id: this.booking.id,
				resourcesIds: [...resourcesIds],
			});
		},
		hasExtraResourcesChanged(extraResources: number[]): boolean
		{
			const bookingExtraResources = this.booking.resourcesIds.slice(1).sort();
			if (bookingExtraResources.length !== extraResources.length)
			{
				return true;
			}

			return [...bookingExtraResources].sort().join(',') !== [...extraResources].sort().join(',');
		},
	},
	template: `
		<div class="booking-actions-popup__item booking--actions-popup--extra-resources-info">
			<div class="booking--actions-popup--extra-resources-info__row">
				<div
					:class="[
						'booking-actions-popup-item-icon',
						'booking--actions-popup--extra-resources-info__icon-product-bg',
						{
							'--active': hasExtraResources
						}
					]"
				>
					<UiIcon
						:name="iconProductName"
						:color="iconProductColor"
					/>
				</div>
				<div class="booking--actions-popup--extra-resources-info__content">
					<div class="booking--actions-popup--extra-resources-info__title">
						<span>{{ loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_TITLE') }}</span>
					</div>
					<div class="booking--actions-popup--extra-resources-info__subtitle">
						<span
							ref="button"
							data-element="amount-additional-resources"
							:class="{ '--fill': hasExtraResources }"
							@click="toggleResourcesInfo"
						>
							{{ subtitle }}
						</span>
						<ExtraResourcesInfoPopup
							v-if="showDialogInfo"
							v-model:visible="showDialogInfo"
							:bindElement="$refs.button"
							:booking
							:resourceId
						/>
					</div>
				</div>
				<div ref="edit" class="booking--actions-popup--extra-resources-info__icon-edit">
					<UiButton
						data-element="btn-toggle-resources-selector"
						:icon="iconEditName"
						:buttonClass="['--air', ButtonStyle.NO_CAPS, AirButtonStyle.OUTLINE_NO_ACCENT]"
						:color="ButtonColor.LIGHT_BORDER"
						:size="ButtonSize.SMALL"
						@click="toggleResourcesSelector"
					/>
					<ExtraResourcesDialog
						v-if="shownResourcesSelector"
						:booking
						:resourceId
						@save="saveBookingExtraResources"
					/>
				</div>
			</div>
		</div>
	`,
};

type ExtraResourcesInfoData = {
	iconProductName: string;
	iconEditName: string;
	iconHelpName: string;
}
