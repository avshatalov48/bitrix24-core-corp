import { Icon } from './layout/icon';
import { Header } from './layout/header';
import { Body } from './layout/body';
import { Footer } from './layout/footer';
import { MarketPanel } from './layout/marketPanel';
import { UserPick } from './layout/userPick';
import { Dom, Runtime } from "main.core";
import { BaseEvent } from "main.core.events";
import { Loader } from "main.loader";
import { StreamType } from '../stream-type';

export const Item = {
	components: {
		Icon,
		Header,
		Body,
		Footer,
		MarketPanel,
		UserPick
	},
	props: {
		initialLayout: Object,
		id: String,
		useShortTimeFormat: Boolean,
		isLogMessage: Boolean,
		isReadOnly: Boolean,
		currentUser: Object | null,
		onAction: Function,
		streamType: {
			type: Number,
			required: false,
			default: StreamType.history,
		}
	},
	data(): Object
	{
		return {
			layout: this.initialLayout,
			isFaded: false,
			loader: Object.freeze(null),
		}
	},
	provide() {
		return {
			isLogMessage: !!this.initialLayout?.isLogMessage,
			isReadOnly: this.isReadOnly,
			currentUser: this.currentUser,
		};
	},
	created(): void
	{
		this.$Bitrix.eventEmitter.subscribe('crm:timeline:item:action', this.onActionEvent);
	},
	beforeDestroy(): void
	{
		this.$Bitrix.eventEmitter.unsubscribe('crm:timeline:item:action', this.onActionEvent);
	},
	methods: {
		onActionEvent(event: BaseEvent): void
		{
			const eventData = event.getData();
			this.onAction(Runtime.clone(eventData));
		},
		setLayout(newLayout: Object): void
		{
			this.layout = newLayout;
			this.isFaded = false;
			this.$Bitrix.eventEmitter.emit('layout:updated');
		},

		setFaded(faded: boolean)
		{
			this.isFaded = faded;
		},

		showLoader(showLoader: boolean)
		{
			if (showLoader)
			{
				this.setFaded(true);
				if (!this.loader)
				{
					this.loader = new Loader();
				}
				this.loader.show(this.$el.parentNode);
			}
			else
			{
				if (this.loader)
				{
					this.loader.hide();
				}
				this.setFaded(false);
			}
		},

		getContentBlockById(blockId: string): ?Object
		{
			if (!this.$refs.body)
			{
				return null;
			}

			return this.$refs.body.getContentBlockById(blockId);
		},

		getLogo(): ?Object
		{
			if (!this.$refs.body)
			{
				return null;
			}

			return this.$refs.body.getLogo();
		},

		getHeaderChangeStreamButton(): ?Object
		{
			if (!this.$refs.header)
			{
				return null;
			}

			return this.$refs.header.getChangeStreamButton();
		},

		getFooterButtonById(buttonId: string): ?Object
		{
			if (!this.$refs.footer)
			{
				return null;
			}

			return this.$refs.footer.getButtonById(buttonId);
		},

		getFooterMenu(): ?Object
		{
			if (!this.$refs.footer)
			{
				return null;
			}

			return this.$refs.footer.getMenu();
		},

		highlightContentBlockById(blockId: string, isHighlighted: boolean): void
		{
			if (!isHighlighted)
			{
				this.isFaded = false;
			}
			const block = this. getContentBlockById(blockId);
			if (!block)
			{
				return;
			}
			if (isHighlighted)
			{
				this.isFaded = true;
				Dom.addClass(block.$el, '--highlighted');
			}
			else
			{
				this.isFaded = false;
				Dom.removeClass(block.$el, '--highlighted');
			}
		}
	},
	computed: {
		timelineCardClassname() {
			return {
				'crm-timeline__card': true,
				'crm-timeline__card-scope': true,
				'--stream-type-history': this.streamType === StreamType.history,
				'--stream-type-scheduled': this.streamType === StreamType.scheduled,
				'--stream-type-pinned': this.streamType === StreamType.pinned,
				'--log-message': !!this.layout.isLogMessage,
			}
		}
	},
	template: `
		<div :data-id="id" :class="timelineCardClassname">
			<div class="crm-timeline__card_fade" v-if="isFaded"></div>
			<div class="crm-timeline__card_icon_container">
				<Icon v-bind="layout.icon"></Icon>
			</div>
			<Header v-if="layout.header" v-bind="layout.header" :use-short-time-format="useShortTimeFormat" ref="header"></Header>
			<Body v-if="layout.body" v-bind="layout.body" ref="body"></Body>
			<Footer v-if="layout.footer" v-bind="layout.footer" ref="footer"></Footer>
			<MarketPanel v-if="layout.marketPanel" v-bind="layout.marketPanel"></MarketPanel>
		</div>
	`
};
