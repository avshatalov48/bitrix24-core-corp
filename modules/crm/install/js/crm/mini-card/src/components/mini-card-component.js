import { BitrixVueComponentProps } from 'ui.vue3';
import { Text, Event } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { MiniCardItem } from '../lib/model/mini-card-item';
import { Loader } from './loader/loader';
import { MiniCardContent } from './mini-card-content';
import { EVENTS } from '../mini-card';

import '../styles.css';

// @vue/component
export const MiniCardComponent: BitrixVueComponentProps = {
	name: 'MiniCardComponent',

	components: {
		MiniCardContent,
		Loader,
	},

	props: {
		state: {
			type: Object,
			default: () => {
				return {
					appId: Text.getRandom(16),
					miniCardItem: null,
					isLoaded: false,
					popup: null,
				};
			},
		},
	},

	computed: {
		miniCardItem(): ?MiniCardItem
		{
			return this.state.miniCardItem;
		},

		isLoading(): boolean
		{
			return !this.state.isLoaded;
		},
	},

	mounted(): void
	{
		this.subscribeInternalEvents();
	},

	methods: {
		subscribeInternalEvents() {
			this.$Bitrix.eventEmitter.subscribe(
				EVENTS.INTERNAL_ON_MOUSE_ENTER_CHILD_POPUP,
				this.onMouseEnterChildPopup.bind(this),
			);

			this.$Bitrix.eventEmitter.subscribe(
				EVENTS.INTERNAL_ON_MOUSE_LEAVE_CHILD_POPUP,
				this.onMouseLeaveChildPopup.bind(this),
			);

			Event.EventEmitter.subscribe(EVENTS.GLOBAL_ON_CLOSE_MAIN_POPUP, this.onCloseMainPopup.bind(this));
		},

		onCloseMainPopup(event: BaseEvent): void
		{
			const appId = event.getData().appId;
			if (appId !== this.state.appId)
			{
				return;
			}

			this.$Bitrix.eventEmitter.emit(EVENTS.INTERNAL_ON_CLOSE_MAIN_POPUP);
		},

		onMouseEnterChildPopup(): void
		{
			const event = new BaseEvent({
				data: {
					appId: this.state.appId,
				},
			});

			Event.EventEmitter.emit(EVENTS.GLOBAL_ON_MOUSE_ENTER_CHILD_POPUP, event);
		},

		onMouseLeaveChildPopup(): void
		{
			const event = new BaseEvent({
				data: {
					appId: this.state.appId,
				},
			});

			Event.EventEmitter.emit(EVENTS.GLOBAL_ON_MOUSE_LEAVE_CHILD_POPUP, event);
		},
	},

	template: `
		<div class="crm-mini-card">
			<Loader v-if="isLoading" />
			<MiniCardContent v-else-if="miniCardItem !== null" :mini-card="miniCardItem" :popup="state.popup" />
		</div>
	`,
};
