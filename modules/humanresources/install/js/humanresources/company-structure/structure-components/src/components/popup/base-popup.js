import { Popup, PopupManager } from 'main.popup';
import { Type } from 'main.core';
import type { PopupOptions } from 'main.popup';

const POPUP_CONTAINER_PREFIX = '#popup-window-content-';

export const BasePopup = {
	name: 'BasePopup',
	emits: ['close'],
	props: {
		id: {
			type: String,
			required: true,
		},
		config: {
			type: Object,
			required: false,
			default: {},
		},
	},
	computed: {
		popupContainer(): string
		{
			return `${POPUP_CONTAINER_PREFIX}${this.id}`;
		},
	},
	created(): void
	{
		this.instance = this.getPopupInstance();
		this.instance.show();
	},
	mounted(): void
	{
		this.instance.adjustPosition({
			forceBindPosition: true,
			position: this.getPopupConfig().bindOptions.position,
		});
	},
	beforeUnmount(): void
	{
		if (!this.instance)
		{
			return;
		}

		this.closePopup();
	},
	methods: {
		getPopupInstance(): Popup
		{
			if (!this.instance)
			{
				PopupManager.getPopupById(this.id)?.destroy();
				const config = this.getPopupConfig();
				this.instance = new Popup(config);

				if (this.config.angleOffset)
				{
					this.instance.setAngle({ offset: this.config.angleOffset });
				}
			}

			return this.instance;
		},
		getDefaultConfig(): PopupOptions
		{
			return {
				id: this.id,
				className: 'hr-structure-components-base-popup',
				autoHide: true,
				animation: 'fading-slide',
				bindOptions: {
					position: 'bottom',
				},
				cacheable: false,
				events: {
					onPopupClose: () => this.closePopup(),
					onPopupShow: async () => {
						const container = this.instance.getPopupContainer();
						await Promise.resolve();
						const { top } = container.getBoundingClientRect();
						const offset = top + container.offsetHeight - document.body.offsetHeight;
						if (offset > 0)
						{
							const margin = 5;
							this.instance.setMaxHeight(container.offsetHeight - offset - margin);
						}
					},
				},
			};
		},
		getPopupConfig(): PopupOptions
		{
			const defaultConfig = this.getDefaultConfig();
			const modifiedOptions = {};

			const defaultClassName = defaultConfig.className;
			if (this.config.className)
			{
				modifiedOptions.className = `${defaultClassName} ${this.config.className}`;
			}

			const offsetTop = this.config.offsetTop ?? defaultConfig.offsetTop;
			if (this.config.bindOptions?.position === 'top' && Type.isNumber(this.config.offsetTop))
			{
				modifiedOptions.offsetTop = offsetTop - 10;
			}

			return { ...defaultConfig, ...this.config, ...modifiedOptions };
		},
		closePopup(): void
		{
			this.$emit('close');
			this.instance.destroy();
			this.instance = null;
		},
		enableAutoHide(): void
		{
			this.getPopupInstance().setAutoHide(true);
		},
		disableAutoHide(): void
		{
			this.getPopupInstance().setAutoHide(false);
		},
		adjustPosition(): void
		{
			this.getPopupInstance().adjustPosition({
				forceBindPosition: true,
				position: this.getPopupConfig().bindOptions.position,
			});
		},
	},
	template: `
		<Teleport :to="popupContainer">
			<slot
				:adjustPosition="adjustPosition"
				:enableAutoHide="enableAutoHide"
				:disableAutoHide="disableAutoHide"
				:closePopup="closePopup"
			></slot>
		</Teleport>
	`,
};
