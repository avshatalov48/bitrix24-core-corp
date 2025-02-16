import { PopupOptions } from 'main.popup';
import { StickyPopup } from 'booking.component.popup';
import { ClientPopupContent } from './client-popup-content/client-popup-content';

export type { CurrentClient } from './client-popup-content/types';

export const ClientPopup = {
	name: 'ClientPopup',
	emits: ['create', 'close'],
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		currentClient: {
			type: Object,
			default: null,
		},
		offsetTop: {
			type: Number,
			default: null,
		},
		offsetLeft: {
			type: Number,
			default: null,
		},
	},
	computed: {
		popupId(): string
		{
			return 'booking-booking-client-popup';
		},
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				width: 305,
				offsetLeft: this.offsetLeft ?? 0 - this.bindElement.offsetWidth,
				offsetTop: this.offsetTop ?? this.bindElement.offsetHeight,
				autoHideHandler: ({ target }) => {
					const content = this.$refs.content;
					const isClickInside = this.$refs.popup.contains(target);
					const isDropdownClick = content.getClientsPopup()?.getPopupContainer()?.contains(target);
					const isFillingTheContact = content.hasClient;

					return !isDropdownClick && !isClickInside && !isFillingTheContact;
				},
			};
		},
	},
	mounted()
	{
		this.onAdjustPosition();
	},
	methods: {
		onAdjustPosition(): void
		{
			this.$refs.content.getClientsPopup()?.adjustPosition();
		},
		closePopup(): void
		{
			this.$emit('close');
		},
	},
	components: {
		StickyPopup,
		ClientPopupContent,
	},
	template: `
		<StickyPopup
			v-slot="{adjustPosition}"
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
			@adjustPosition="onAdjustPosition"
		>
			<ClientPopupContent
				:adjust-position
				:current-client
				ref="content"
				@create="$emit('create', $event)"
				@close="closePopup"
			/>
		</StickyPopup>
	`,
};
