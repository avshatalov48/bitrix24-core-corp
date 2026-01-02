import { Loader } from 'main.loader';
import type { PopupOptions } from 'main.popup';
import { Popup } from '../../layout/popup';
import '../../css/file-checking-popup.css';

export const CheckingProgressPopup = {
	emits: ['close'],
	props: {},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				width: 440,
				closeIcon: false,
				noAllPaddings: true,
				overlay: true,
				background: 'transparent',
			};
		},
	},
	mounted()
	{
		const loader = new Loader({
			target: this.$refs.loader,
			size: 65,
			color: 'var(--ui-color-primary)',
			strokeWidth: 4,
			mode: 'inline',
		});
		loader.show();
	},
	methods: {
		onClose()
		{
			this.$emit('close');
		},
	},
	components: {
		Popup,
	},
	// language=Vue
	template: `
		<Popup
			id="file-check-progress"
			@close="this.onClose"
			:options="popupOptions"
		>
			<div class="file-check file-check-progress">
				<div ref="loader" class="file-check-loader"></div>
			</div>
		</Popup>
	`,
};
