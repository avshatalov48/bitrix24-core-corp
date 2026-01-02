import { Event, Extension, Loc } from 'main.core';
import { PromoVideoPopup } from 'ui.promo-video-popup';

export const AdditionalInfoComponent = {
	props: {
		title: {
			type: String,
			required: true,
		},
	},

	mounted()
	{
		this.popup = null;
		this.region = Extension.getSettings('crm.repeat-sale.segment').get('region');

		Event.EventEmitter.subscribe('UI.PromoVideoPopup:accept', (event) => {
			this.popup?.hide();
		});
	},

	methods: {
		onClick(): void
		{
			if (this.popup === null)
			{
				this.popup = new PromoVideoPopup({
					angleOptions: null,
					targetOptions: '1',
					useOverlay: true,
					videoSrc: this.videoSrc,
					videoContainerMinHeight: 255,
					title: Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_HOW_IT_WORK_TITLE'),
					colors: {
						title: 'var(--ui-color-palette-black-base)',
					},
				});
			}

			this.popup.show();
		},
	},

	computed: {
		videoSrc(): string
		{
			let name = 'how-it-work-en';
			if (['kz', 'ru', 'by', 'uz'].includes(this.region))
			{
				name = 'how-it-work-ru';
			}

			return `/bitrix/js/crm/repeat-sale/segment/video/${name}.webm`;
		},
	},

	// language=Vue
	template: `
		<div class="crm-repeat-sale__segment-field-info">
			<span @click="onClick">
				{{title}}
			</span>
		</div>
	`,
};
