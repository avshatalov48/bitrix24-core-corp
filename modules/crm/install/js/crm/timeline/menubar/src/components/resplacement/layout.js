import { Runtime, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Loader } from 'main.loader';
import ActionType from './enums/action-type';
import ButtonType from './enums/button-type';

import Button from './layout/button';

export const ITEM_ACTION_EVENT = 'crm:activityplacement:item:action';

export const Layout = {
	components: {
		Button,
	},
	props: {
		id: String,
		appId: String,
		onAction: Function,
	},
	data(): Object
	{
		return {
			layout: {},
			loader: Object.freeze(null),
			isLoading: true,
			primaryButtonParams: this.getButtonParams(ButtonType.PRIMARY, null, this.layout?.primaryButton),
			secondaryButtonParams: this.getButtonParams(ButtonType.SECONDARY, null, this.layout?.secondaryButton),
			primaryButtonAction: Object.freeze({ type: ActionType.FOOTER_BUTTON_CLICK, value: ButtonType.PRIMARY }),
			secondaryButtonAction: Object.freeze({ type: ActionType.FOOTER_BUTTON_CLICK, value: ButtonType.SECONDARY }),
		};
	},
	created(): void
	{
		this.$Bitrix.eventEmitter.subscribe(ITEM_ACTION_EVENT, this.onActionEvent);
	},
	mounted() {
		this.showLoader(true);
	},
	beforeUnmount(): void
	{
		this.$Bitrix.eventEmitter.unsubscribe(ITEM_ACTION_EVENT, this.onActionEvent);
	},
	watch: {
		layout(newLayout)
		{
			this.primaryButtonParams = this.getButtonParams(
				ButtonType.PRIMARY,
				this.primaryButtonParams,
				newLayout.primaryButton,
			);
			this.secondaryButtonParams = this.getButtonParams(
				ButtonType.SECONDARY,
				this.secondaryButtonParams,
				newLayout.secondaryButton,
			);
		},
	},
	methods:
	{
		setLayout(newLayout: Object): void
		{
			this.layout = newLayout;
			this.$Bitrix.eventEmitter.emit('layout:updated');
		},
		showLoader(showLoader: boolean)
		{
			if (showLoader)
			{
				if (!this.loader)
				{
					this.loader = new Loader({ size: 50 });
				}
				this.loader.show(this.$refs.loader);
			}
			else if (this.loader)
			{
				this.loader.hide();
			}
			this.isLoading = showLoader;
		},
		setLayoutItemState(id: string, visible: ?Boolean, properties: ?Object, callback: function)
		{
			if (this.$refs.blocks.setLayoutItemState(id, visible, properties))
			{
				this.$nextTick((callback({ result: 'success' })));
			}
			else
			{
				this.$nextTick((callback({ result: 'error', errors: ['item not found'] })));
			}
		},
		setButtonState(id: string, state: ?Object, callback: function)
		{
			switch (id)
			{
				case ButtonType.PRIMARY:
					this.primaryButtonParams = this.getButtonParams(ButtonType.PRIMARY, this.primaryButtonParams, state);
					break;
				case ButtonType.SECONDARY:
					this.secondaryButtonParams = this.getButtonParams(ButtonType.SECONDARY, this.secondaryButtonParams, state);
					break;
			}

			this.$nextTick((callback({ result: 'success' })));
		},
		getButtonParams(buttonType: string, oldValue: ?Object, newValue: ?Object): ?Object
		{
			if (Type.isNull(newValue))
			{
				return null;
			}

			return {
				...oldValue,
				...newValue,
				type: buttonType,
			};
		},
		getAppId(): string
		{
			return this.appId;
		},
		onActionEvent(event: BaseEvent): void
		{
			const eventData = event.getData();
			this.onAction(Runtime.clone(eventData));
		},
	},
	computed:
	{
		hasPrimaryButton(): Boolean
		{
			return Boolean(this.primaryButtonParams);
		},
		hasSecondaryButton(): Boolean
		{
			return Boolean(this.secondaryButtonParams);
		},
	},
	template: `
		<div class="crm-entity-stream-restapp-loader" ref="loader" v-show="isLoading"></div>
		<BlocksCollection  
			v-show="!isLoading" 
			containerCssClass="crm-entity-stream-restapp-container"
			itemCssClass="crm-timeline__restapp-container_block"
			ref="blocks"
			:blocks="layout?.blocks ?? {}"></BlocksCollection>

		<div class="crm-entity-stream-restapp-btn-container" v-show="!isLoading && (hasPrimaryButton || hasSecondaryButton)">
			<Button v-if="hasPrimaryButton" v-bind="primaryButtonParams" :action="primaryButtonAction"></Button>
			<Button v-if="hasSecondaryButton" v-bind="secondaryButtonParams" :action="secondaryButtonAction"></Button>
		</div>
	`,
};
