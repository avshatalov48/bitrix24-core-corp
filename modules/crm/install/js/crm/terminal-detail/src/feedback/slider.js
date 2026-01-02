BX.namespace("BX.Crm");

import { Runtime, Type } from 'main.core';

export default class Slider
{
	static openFeedbackForm(): void
	{
		void Runtime.loadExtension(['ui.feedback.form'])
			.then(() => {
				const settings = Runtime.getSettings('crm.terminal-detail');
				const hasPayment = settings.get('hasPaymentSystemConfigured');
				const hasCashbox = settings.get('hasCashboxConfigured');

				const formIdNumber = Math.round(Math.random() * 1000);
				BX.UI.Feedback.Form.open({
					id: `crm.feedback-${formIdNumber}`,
					forms: [
						{ zones: ['en'], id: 630, lang: 'en', sec: 'ypq6nz' },
						{ zones: ['com.br'], id: 632, lang: 'com.br', sec: 'ama2ql' },
						{ zones: ['ru', 'by', 'kz'], id: 628, lang: 'ru', sec: 'rgyboj' },
					],
					presets: {
						sender_page: 'terminal',
						is_payment_system: hasPayment ? 'yes' : 'no',
						is_cashbox: hasCashbox ? 'yes' : 'no',
					},
				});
			})
		;
	}

	static open(url, options)
	{
		if(!Type.isPlainObject(options))
		{
			options = {};
		}
		options = {...{cacheable: false, allowChangeHistory: false, events: {}}, ...options};
		return new Promise((resolve) =>
		{
			if(Type.isString(url) && url.length > 1)
			{
				options.events.onClose = function(event)
				{
					resolve(event.getSlider());
				};
				BX.SidePanel.Instance.open(url, options);
			}
			else
			{
				resolve();
			}
		});
	}
}