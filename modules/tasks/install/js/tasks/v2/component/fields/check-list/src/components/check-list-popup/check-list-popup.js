import { Event } from 'main.core';
import type { PopupOptions } from 'main.popup';

import { Popup } from 'ui.vue3.components.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { EventName, Model } from 'tasks.v2.const';

import './check-list-popup.css';

// @vue/component
export const CheckListPopup = {
	name: 'TaskCheckListPopup',
	components: {
		Popup,
	},
	inheritAttrs: false,
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['show', 'close', 'resize'],
	setup(): Object
	{
		return {
			resizeObserver: null,
		};
	},
	computed: {
		popupId(): string
		{
			return `tasks-check-list-popup-${this.taskId}`;
		},
		popupOptions(): PopupOptions
		{
			return {
				className: 'tasks-check-list-popup',
				width: 580,
				height: 500,
				borderRadius: '18px',
				offsetTop: 0,
				padding: 0,
				autoHide: true,
				closeByEsc: true,
				animation: {
					showClassName: 'tasks-check-list-popup-show',
					closeClassName: 'tasks-check-list-popup-close',
					closeAnimationType: 'animation',
				},
				events: {
					onClose: this.handleClose.bind(this),
				},
			};
		},
		...mapGetters({
			titleFieldOffsetHeight: `${Model.Interface}/titleFieldOffsetHeight`,
		}),
	},
	watch: {
		async titleFieldOffsetHeight(): Promise<void>
		{
			if (!this.$refs.childComponent)
			{
				return;
			}

			await this.$nextTick();

			this.resize();
		},
	},
	created(): void
	{
		this.resizeObserver = new ResizeObserver((entries: ResizeObserverEntry[]) => {
			for (const entry of entries)
			{
				if (entry.target === this.$refs.wrapper)
				{
					this.resize();
				}
			}
		});
	},
	mounted(): void
	{
		Event.bind(window, 'resize', this.resize);
	},
	beforeUnmount(): void
	{
		Event.unbind(window, 'resize', this.resize);
	},
	methods: {
		resize(): void
		{
			const popupInstance = this.$refs.childComponent?.getPopupInstance();
			if (popupInstance)
			{
				this.$emit('resize');

				popupInstance.adjustPosition();
			}
		},
		handleShow(): void
		{
			this.$emit('show', { popupInstance: this.$refs.childComponent.getPopupInstance() });

			this.$refs.childComponent?.getPopupInstance().adjustPosition();
			setTimeout(() => this.resizeObserver.observe(this.$parent.$refs.wrapper), 300);
		},
		handleClose(): void
		{
			this.resizeObserver.disconnect();
			this.$bitrix.eventEmitter.emit(EventName.CloseCheckList);

			this.$emit('close');
		},
	},
	template: `
		<Popup :options="popupOptions" ref="childComponent">
			<slot
				:handleShow="handleShow"
				:handleClose="handleClose"
			></slot>
		</Popup>
	`,
};
