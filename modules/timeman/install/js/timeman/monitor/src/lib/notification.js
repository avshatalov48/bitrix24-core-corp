import {Type} from 'main.core';

export class Notification
{
	constructor(title, text, callback)
	{
		this.title = title.toString();
		this.text = text.toString();

		if (Type.isFunction(callback))
		{
			this.callback = callback;
		}

		return this;
	}

	show()
	{
		BXIM.playSound('newMessage1');

		const messageTemplate = BXIM.notify.createNotify({
			id: 0, type: 4,
			date: new Date(),
			params: {},
			title: this.title,
			text: this.text,
		}, true);

		const messageJs = `
			var notify = BX.findChildByClassName(document.body, "bx-notifier-item");
			
			notify.style.cursor = "pointer";
			
			BX.bind(notify, "click", function() {
				BX.desktop.onCustomEvent("main", "bxImClickPwtMessage", []);
				BX.desktop.windowCommand("close")
			});
			
			BX.bind(BX.findChildByClassName(notify, "bx-notifier-item-delete"), "click", function(event) { 
				BX.desktop.windowCommand("close"); 
				BX.MessengerCommon.preventDefault(event); 
			});
			
			BX.bind(notify, "contextmenu", function() {
				BX.desktop.windowCommand("close")
			});
		`;

		BXIM.desktop.openNewMessage('pwt' + (new Date), messageTemplate, messageJs);

		BX.desktop.addCustomEvent('bxImClickPwtMessage', () => this.click());
	}

	click()
	{
		if (Type.isFunction(this.callback))
		{
			this.callback();
		}

		BX.desktop.removeCustomEvents('bxImClickPwtMessage');
	}
}