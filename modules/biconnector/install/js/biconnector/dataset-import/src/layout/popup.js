import { Popup as MainPopup, PopupManager } from 'main.popup';
import '../css/popup.css';

export const Popup = {
	emits: ['close'],
	props: {
		id: {
			type: String,
			required: true,
		},
		options: {
			type: Object,
			required: false,
			default: {},
		},
		wrapperClass: {
			type: String,
			required: false,
			default: '',
		},
	},
	data()
	{
		return {
			popupInstance: null,
		};
	},
	computed: {
		popupOptions()
		{
			return {
				id: this.id,
				content: this.$refs.content,
				autoHide: true,
				events: {
					onPopupClose: this.closePopup,
					onPopupDestroy: this.closePopup,
				},
				fixed: true,
				...this.options,
			};
		},
	},
	methods: {
		closePopup()
		{
			PopupManager.getPopupById(this.id)?.destroy();
			this.popupInstance = null;
			this.$emit('close');
		},
	},
	mounted()
	{
		if (!this.popupInstance)
		{
			this.popupInstance = new MainPopup(this.popupOptions);
		}

		this.popupInstance.show();
	},
	beforeUnmount(): void
	{
		this.closePopup();
	},
	template: `
		<div ref="content" :class="wrapperClass">
			<slot></slot>
		</div>
	`,
};
