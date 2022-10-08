import {Icon} from './layout/icon';
import {Header} from './layout/header';
import {Body} from './layout/body';
import {Footer} from './layout/footer';
import {MarketPanel} from './layout/marketPanel';
import {Note} from './layout/note';
import {UserPick} from './layout/userPick';
import {BaseEvent} from "main.core.events";
import {StreamType} from '../stream-type';

export const Item = {
	components: {
		Icon,
		Header,
		Body,
		Footer,
		MarketPanel,
		Note,
		UserPick
	},
	props: {
		initialLayout: Object,
		id: String,
		useShortTimeFormat: Boolean,
		isLogMessage: Boolean,
		isReadOnly: Boolean,
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
		}
	},
	provide() {
		return {
			isLogMessage: !!this.initialLayout?.isLogMessage,
			isReadOnly: this.isReadOnly,
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
			this.onAction(eventData.action, eventData.actionParams);
		},
		setLayout(newLayout: Object): void
		{
			this.layout = newLayout;
		},
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
			<div class="crm-timeline__card_icon_container">
				<Icon v-bind="layout.icon"></Icon>
			</div>
			<Header v-if="layout.header" v-bind="layout.header" :use-short-time-format="useShortTimeFormat"></Header>
			<Body v-if="layout.body" v-bind="layout.body"></Body>
			<Footer v-if="layout.footer" v-bind="layout.footer"></Footer>
			<MarketPanel v-if="layout.marketPanel" v-bind="layout.marketPanel"></MarketPanel>
		</div>
	`
};
