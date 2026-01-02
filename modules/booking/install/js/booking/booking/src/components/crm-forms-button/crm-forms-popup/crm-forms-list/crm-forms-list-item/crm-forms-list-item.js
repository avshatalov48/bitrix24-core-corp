// eslint-disable-next-line no-unused-vars
import type { FormsMenuItem } from 'booking.model.forms-menu';
import { AirButtonStyle } from 'booking.component.button';

import { CrmFormsListItemNameLink } from './crm-forms-list-item-name-link';
import './crm-forms-list-item.css';

// @vue/component
export const CrmFormsListItem = {
	name: 'CrmFormsListItem',
	components: {
		CrmFormsListItemNameLink,
	},
	props: {
		/**
		 * @type {FormsMenuItem}
		 */
		item: {
			type: Object,
			required: true,
		},
		canEdit: Boolean,
	},
	setup(): Object
	{
		return {
			AirButtonStyle,
		};
	},
	data(): Object
	{
		return {
			copedPopupTimeoutId: null,
		};
	},
	methods: {
		copyLink(): void
		{
			if (!BX.clipboard.isCopySupported())
			{
				return;
			}

			BX.clipboard.copy(this.item.publicUrl);
			this.showCopedPopup();
		},
		showCopedPopup(): void
		{
			this.popup?.destroy();
			this.popup = new BX.PopupWindow(
				`booking_open_crm_forms_popup_item_${this.item.id}_clipboard_copy`,
				this.$refs.copy,
				{
					content: this.loc('BOOKING_OPEN_CRM_FORMS_POPUP_FORMS_LIST_ITEM_LINK_COPED'),
					darkMode: true,
					autoHide: true,
					zIndex: 1000,
					angle: true,
					offsetLeft: 20,
					bindOptions: {
						position: 'top',
					},
				},
			);
			this.popup.show();
			this.copedPopupTimeoutId = setTimeout(() => {
				this.popup.close();
				this.popup?.destroy();
			}, 1200);
		},
	},
	template: `
		<div class="booking--booking--crm-forms-popup--item">
			<div class="ui-icon-set --o-form booking--booking--crm-forms-popup--item__icon"></div>
			<div
				class="booking--booking--crm-forms-popup--item__name"
				:title="item.name"
			>
				<CrmFormsListItemNameLink :canEdit :editUrl="item.editUrl">
					<span>{{ item.name }}</span>
				</CrmFormsListItemNameLink>
			</div>
			<div
				ref="copy"
				:class="['booking--booking--crm-forms-popup--item__copy-btn', AirButtonStyle.OUTLINE_NO_ACCENT]"
				role="button"
				tabindex="0"
				@click="copyLink"
			>
				{{ loc('BOOKING_OPEN_CRM_FORMS_POPUP_FORMS_LIST_ITEM_COPY_LINK_BUTTON_LABEL') }}
				<div class="ui-icon-set --o-copy booking--booking--crm-forms-popup--item__copy-btn-icon"></div>
			</div>
		</div>
	`,
};
