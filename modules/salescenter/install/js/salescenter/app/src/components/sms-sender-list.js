import {PopupMenuWindow} from 'main.popup';
import {Manager} from 'salescenter.manager';
import {Type} from 'main.core';
import {Vue} from "ui.vue";

const SmsSenderListBlock = {
	props:['list','config'],
	computed:
		{
			getSenderCode()
			{
				return this.config.sender.code;
			},

			getConfigUrl()
			{
				return this.config.url;
			},

			localize()
			{
				return Vue.getFilteredPhrases('SALESCENTER_SENDER_LIST_CONTENT_');
			},
		},
	methods:
		{
			openSlider()
			{
				Manager.openSlider(this.getConfigUrl).then(() => this.onConfigure());
			},

			onConfigure()
			{
				this.$emit('on-configure');
			},

			onSelectedSender(value)
			{
				this.$emit('on-selected', value);
			},

			render(target, array)
			{
				let menuItems = [];
				let setItem = (ev) => {
					target.innerHTML = ev.target.innerHTML;
					this.setCode(ev.currentTarget.getAttribute('data-item-sender-value'));
					this.popupMenu.close();
				};

				for(let index in array)
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

			getName()
			{
				if (Type.isArray(this.list))
				{
					for (let index in this.list)
					{
						if (!this.list.hasOwnProperty(index))
						{
							continue;
						}

						if (this.list[index].id === this.getSenderCode)
						{
							return this.list[index].name;
						}
					}
				}
				return null;
			},

			setCode(value)
			{
				if(typeof value === 'string')
				{
					this.onSelectedSender(value);
					return;
				}

				this.onSelectedSender(value.target.value);
			},

			isShow()
			{
				return Type.isString(this.getName());
			}
		},
	template: `
		<div v-if="isShow()" class="salescenter-app-payment-by-sms-item-container-sms-content-info">
			<slot name="sms-sender-list-text-send-from"></slot>
			<span @click="render($event.target, list)">{{getName()}}</span>
		</div>
	`
};

export {
	SmsSenderListBlock
}