import { Popup, PopupManager, PopupOptions } from 'main.popup';

const POPUP_CONTAINER_PREFIX = '#popup-window-content-';
const POPUP_ID = 'feedback-popup-wrapper';
const POPUP_BORDER_RADIUS = '10px';

// @vue/component
export const PopupWrapper = {
	name: 'PopupWrapper',
	emits: ['close'],
	computed:
	{
		popupContainer(): string
		{
			return `${POPUP_CONTAINER_PREFIX}${POPUP_ID}`;
		},
	},
	created()
	{
		this.instance = this.getPopupInstance();
		this.instance.show();
	},
	mounted()
	{
		this.instance.adjustPosition({
			forceBindPosition: true,
			position: this.getConfig().bindOptions.position,
		});
	},
	beforeUnmount()
	{
		if (!this.instance)
		{
			return;
		}

		this.closePopup();
	},
	methods:
	{
		getPopupInstance(): Popup
		{
			if (!this.instance)
			{
				PopupManager.getPopupById(this.id)?.destroy();

				this.instance = new Popup(this.getConfig());
			}

			return this.instance;
		},
		getConfig(): PopupOptions
		{
			return {
				id: POPUP_ID,
				bindOptions: {
					position: 'bottom',
				},
				width: 463,
				padding: 32,
				minHeight: 470,
				className: 'market-detail__app-rating_feedback-popup',
				cacheable: false,
				closeIcon: true,
				autoHide: true,
				closeByEsc: true,
				animation: 'fading',
				events: {
					onPopupClose: this.closePopup.bind(this),
					onPopupDestroy: this.closePopup.bind(this),
				},
				overlay: {
					backgroundColor: '#000',
					opacity: 50,
				},
				contentBorderRadius: POPUP_BORDER_RADIUS,
			};
		},
		closePopup()
		{
			this.$emit('close');
			this.instance.destroy();
			this.instance = null;
		},
	},
	template: `
		<Teleport :to="popupContainer">
			<slot></slot>
		</Teleport>
	`,
};
