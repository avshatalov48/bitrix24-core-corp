import { Dom, Loc, Tag, Event, Text } from 'main.core';
import { Util } from 'calendar.util';
import { Popup, PopupOptions } from 'main.popup';

type RuleData = {
	rule: {
		from: number,
		to: number,
		slotSize: number,
		weekdays: string[],
		weekdaysTitle: string,
	},
	durationFormatted: string,
	weekdaysFormatted: string,
};

export default {
	data(): Object {
		return {
			popup: null,
			moreLinkRef: null,
		};
	},
	props: {
		listItems: {
			type: Array,
			required: true,
			default: [],
		},
	},
	mounted()
	{
		const moreLink = this.$el.querySelector('[data-anchor="more-link"]');
		if (!moreLink)
		{
			return;
		}

		this.moreLinkRef = moreLink;

		Event.bind(this.moreLinkRef, 'click', () => this.openPopup());
		Dom.append(Tag.render`<i/>`, this.moreLinkRef);
	},
	computed: {
		items(): RuleData[]
		{
			return this.listItems.map((item) => item.properties);
		},
		formattedRules(): string[]
		{
			return this.items.map((item) => this.createItemText(item));
		},
		firstFormattedRule(): string
		{
			if (this.doShowMoreLink)
			{
				return Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_RANGE_WITH_MORE', {
					'#RANGE#': this.formattedRules[0],
					'#MORE_LINK_CLASS#': 'crm-timeline-calendar-sharing-slots-more',
					'#AMOUNT#': this.items.length - 1,
				});
			}

			return this.formattedRules[0] ?? '';
		},
		formattedDuration(): string
		{
			return Loc.getMessage(
				'CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_DURATION',
				{ '#DURATION#': this.items[0].durationFormatted },
			);
		},
		doShowMoreLink(): boolean
		{
			return this.items.length > 1;
		},
	},
	methods: {
		createItemText(item: RuleData): string
		{
			return Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_RANGE_V3', {
				'#WEEKDAYS#': Text.encode(item.weekdaysFormatted),
				'#FROM_TIME#': this.formatMinutes(item.rule.from),
				'#TO_TIME#': this.formatMinutes(item.rule.to),
			});
		},
		formatMinutes(minutes): string
		{
			const date = new Date(Util.parseDate('01.01.2000').getTime() + minutes * 60 * 1000);

			return Util.formatTime(date);
		},
		openPopup()
		{
			if (!this.moreLinkRef || this.popup?.isShown())
			{
				return;
			}

			this.popup = new Popup(this.getPopupOptions());
			this.popup.show();
		},
		getPopupOptions(): PopupOptions {
			return {
				content: this.getPopupContent(),
				autoHide: true,
				cacheable: false,
				animation: 'fading-slide',
				bindElement: this.moreLinkRef,
				closeByEsc: true,
			};
		},
		getPopupContent(): HTMLElement {
			const root = Tag.render`<div></div>`;
			this.formattedRules.forEach((item) => {
				Dom.append(Tag.render`<div class="crm-timeline-calendar-sharing-slots-more-popup-item">${item}</div>`, root);
			});

			return root;
		},
	},
	template: `
		<div class="crm-timeline-calendar-sharing-slots">
			<div class="crm-timeline-calendar-sharing-slots-block" v-html="firstFormattedRule"/>
			<div class="crm-timeline-calendar-sharing-slots-block">
				{{formattedDuration}}
			</div>
		</div>
	`,
};
