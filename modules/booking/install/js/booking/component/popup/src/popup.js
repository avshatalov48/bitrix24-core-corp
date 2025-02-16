import { Dom } from 'main.core';
import { Popup as MainPopup, PopupManager, PopupOptions } from 'main.popup';
import { SliderIntegration } from './integration/slider-integration';
import './popup.css';

export const Popup = {
	emits: ['close'],
	props: {
		id: {
			type: String,
			required: true,
		},
		config: {
			type: Object,
			default: {},
		},
	},
	created(): void
	{
		new SliderIntegration(this);
	},
	beforeMount(): void
	{
		this.getPopupInstance().show();
	},
	mounted(): void
	{
		this.adjustPosition();
		this.getPopupInstance().getContentContainer().remove();

		const { angleBorderRadius } = this.config;
		Dom.style(this.container, '--booking-popup-angle-border-radius', angleBorderRadius);
	},
	beforeUnmount(): void
	{
		this.closePopup();
	},
	computed: {
		popupContainer(): string
		{
			return `#${this.id}`;
		},
		container(): HTMLElement
		{
			return this.getPopupInstance().getPopupContainer();
		},
		options(): PopupOptions
		{
			return { ...this.defaultOptions, ...this.config };
		},
		defaultOptions(): PopupOptions
		{
			return {
				id: this.id,
				cacheable: false,
				autoHide: true,
				autoHideHandler: ({ target }) => {
					const parentAutoHide = target !== this.container && !this.container.contains(target);
					const isAhaMoment = target.closest('.popup-window-ui-tour');

					return parentAutoHide && !isAhaMoment;
				},
				closeByEsc: true,
				animation: 'fading',
				events: {
					onPopupClose: this.closePopup,
					onPopupDestroy: this.closePopup,
				},
			};
		},
	},
	methods: {
		contains(element: HTMLElement): boolean
		{
			return this.container.contains(element) ?? false;
		},
		adjustPosition(): void
		{
			this.getPopupInstance().adjustPosition(this.options.bindOptions);
		},
		freeze(): void
		{
			this.getPopupInstance().setAutoHide(false);
		},
		unfreeze(): void
		{
			this.getPopupInstance().setAutoHide(this.options.autoHide);
		},
		getPopupInstance(): MainPopup
		{
			if (!this.instance)
			{
				PopupManager.getPopupById(this.id)?.destroy();

				this.instance = new MainPopup(this.options);
			}

			return this.instance;
		},
		closePopup(): void
		{
			this.instance?.destroy();
			this.instance = null;
			this.$emit('close');
		},
	},
	template: `
		<Teleport :to="popupContainer">
			<slot :adjustPosition="adjustPosition" :freeze="freeze" :unfreeze="unfreeze"></slot>
		</Teleport>
	`,
};
