import { Dom, Event } from 'main.core';
import type { PopupOptions } from 'main.popup';

import { Popup } from 'ui.vue3.components.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'tasks.v2.const';
import { TaskModel } from 'tasks.v2.model.tasks';
import { DescriptionEditor } from './description-editor';

import './description.css';

// @vue/component
export const DescriptionPopup = {
	name: 'TaskDescriptionPopup',
	components: {
		Popup,
		DescriptionEditor,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isShown: {
			type: Boolean,
			required: true,
		},
		doOpenInEditMode: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['show', 'close', 'resize'],
	setup(): Object
	{
		return {
			resizeObserver: null,
		};
	},
	data(): Object
	{
		return {
			popupHeight: 0,
		};
	},
	computed: {
		popupId(): string
		{
			return `tasks-field-description-popup-${this.taskId}`;
		},
		popupOptions(): PopupOptions
		{
			return {
				className: 'tasks-card-description-popup',
				minHeight: 360,
				maxHeight: this.popupMaxHeight,
				width: 580,
				offsetTop: 0,
				padding: 0,
				autoHide: false,
				closeByEsc: false,
				animation: {
					showClassName: 'tasks-description-popup-show',
					closeClassName: 'tasks-description-popup-close',
					closeAnimationType: 'animation',
				},
				events: {
					onAfterShow: () => this.$refs.editorComponent.focusToEnd(),
				},
			};
		},
		popupMaxHeight(): number
		{
			return document.body.offsetHeight - 120 - this.titleFieldOffsetHeight;
		},
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		...mapGetters({
			titleFieldOffsetHeight: `${Model.Interface}/titleFieldOffsetHeight`,
		}),
	},
	watch: {
		async titleFieldOffsetHeight(): Promise<void>
		{
			if (!this.$refs.popupComponent)
			{
				return;
			}

			this.resizeEditor();
			await this.$nextTick();
			this.onResize();
		},
	},
	created(): void
	{
		this.resizeObserver = new ResizeObserver((entries) => {
			for (const entry of entries)
			{
				if (entry.target === this.$refs.popupWrapper)
				{
					this.onResize();
				}
			}
		});
	},
	mounted(): void
	{
		Event.bind(window, 'resize', this.onResize);
	},
	beforeUnmount(): void
	{
		Event.unbind(window, 'resize', this.onResize);
	},
	methods: {
		onShow(): void
		{
			this.resizeEditor();
			this.$emit('show', { popupInstance: this.$refs.popupComponent.getPopupInstance() });
			this.$refs.popupComponent?.getPopupInstance().adjustPosition();
			setTimeout(() => this.resizeObserver.observe(this.$refs.popupWrapper), 300);
		},
		resizeEditor(): void
		{
			const popupInstance = this.$refs.popupComponent?.getPopupInstance();
			const popupContainer = popupInstance.getPopupContainer();
			this.$refs.editorComponent.hideEditor();
			Dom.style(popupContainer, 'min-height', 0);
			const popupWithoutEditorHeight = popupContainer.clientHeight;
			const additionalOffset = 240;
			const maxHeight = document.body.clientHeight
				- popupWithoutEditorHeight
				- additionalOffset
				- this.titleFieldOffsetHeight
			;
			Dom.style(popupContainer, 'min-height', '360px');
			this.$refs.editorComponent.showEditor();

			this.$refs.editorComponent.setMaxHeight(maxHeight);

			popupInstance.setOffset();
		},
		onResize(): void
		{
			const popupInstance = this.$refs.popupComponent?.getPopupInstance();
			if (popupInstance)
			{
				this.$emit('resize');
				popupInstance.adjustPosition();
			}
		},
		onClose(): void
		{
			this.resizeObserver.disconnect();
			this.$emit('close');
		},
	},
	template: `
		<Popup v-if="isShown" :options="popupOptions" ref="popupComponent">
			<div class="tasks-card-description-popup-wrapper" ref="popupWrapper">
				<DescriptionEditor
					ref="editorComponent"
					:taskId="taskId"
					:doOpenInEditMode="doOpenInEditMode"
					@show="onShow"
					@close="onClose"
				></DescriptionEditor>
			</div>
		</Popup>
	`,
};
