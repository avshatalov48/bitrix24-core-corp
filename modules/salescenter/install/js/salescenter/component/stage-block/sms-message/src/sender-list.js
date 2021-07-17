import {PopupMenuWindow} from 'main.popup';
import {Manager} from 'salescenter.manager';
import {Vue} from "ui.vue";

const SenderList = {
	props: [
		'list',
		'initSelected',
		'settingUrl'
	],
	computed: {
		selectedSender()
		{
			return this.list.find(sender => sender.id === this.selected);
		},
		selectedSenderName()
		{
			return this.selectedSender ? this.selectedSender.name : '';
		},
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_SENDER_LIST_CONTENT_');
		},
	},
	data() {
		return {
			selected: null,
		};
	},
	created() {
		if (this.initSelected)
		{
			this.onSelectedSender(this.initSelected);
		}
		else if (this.list && this.list.length > 0)
		{
			this.onSelectedSender(this.list[0].id);
		}
	},
	methods: {
		openSlider()
		{
			Manager.openSlider(this.settingUrl).then(() => this.onConfigure());
		},
		onConfigure()
		{
			this.$emit('on-configure');
		},
		onSelectedSender(value)
		{
			this.selected = value;
			this.$emit('on-selected', value);
		},
		render(target, array)
		{
			let menuItems = [];
			let setItem = (ev) => {
				target.innerHTML = ev.target.innerHTML;
				this.onSelectedSender(ev.currentTarget.getAttribute('data-item-sender-value'));
				this.popupMenu.close();
			};

			for (let index in array)
			{
				if (!array.hasOwnProperty(index))
				{
					continue;
				}

				menuItems.push({
					text: array[index].name,
					dataset: {
						'itemSenderValue' : array[index].id
					},
					onclick: setItem
				})
			}

			menuItems.push({
				text: this.localize.SALESCENTER_SENDER_LIST_CONTENT_SETTINGS,
				onclick: ()=>{
					this.openSlider();
					this.popupMenu.close();
				}
			});

			this.popupMenu = new PopupMenuWindow({
				bindElement: target,
				items: menuItems,
			});

			this.popupMenu.show();
		},
	},
	template: `
		<div class="salescenter-app-payment-by-sms-item-container-sms-content-info">
			<slot name="sms-sender-list-text-send-from"></slot>
			<span @click="render($event.target, list)">{{selectedSenderName}}</span>
		</div>
	`
};

export {
	SenderList
}
