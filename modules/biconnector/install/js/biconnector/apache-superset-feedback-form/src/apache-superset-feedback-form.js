import { Extension, Loc } from 'main.core';

export class ApacheSupersetFeedbackForm
{
	static requestIntegrationFormOpen()
	{
		const settingsCollection = Extension.getSettings('biconnector.apache-superset-feedback-form');
		BX.UI.Feedback.Form.open(
			{
				id: 'order_dashboard',
				title: Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_FEEDBACK_FORM_INTEGRATION_REQUEST_FORM'),
				portalUri: 'https://bitrix24.team',
				forms: [
					{zones: ['ru'], id: 2399, lang: 'ru', sec: '5depoh'},
					{zones: ['kz'], id: 2400, lang: 'ru', sec: 'oeh1qd'},
					{zones: ['by'], id: 2401, lang: 'ru', sec: 'rmf9fh'},
					{zones: ['com', 'eu', 'in', 'uk', 'com/my', 'com/th', 'jp', 'id', 'cn'], id: 1930, lang: 'en', sec: 'lg4wsd'},
					{zones: ['com.br'], id: 1964, lang: 'pt', sec: 'n4evxs'},
					{zones: ['de'], id: 1965, lang: 'de', sec: 'i95dp6'},
					{zones: ['es', 'co', 'mx'], id: 1966, lang: 'es', sec: 'zlemun'},
					{zones: ['pl'], id: 1967, lang: 'pl', sec: 'hg6mms'},
					{zones: ['fr'], id: 1968, lang: 'fr', sec: '8rao53'},
					{zones: ['it'], id: 1969, lang: 'it', sec: 'o13tam'},
					{zones: ['vn'], id: 1970, lang: 'vn', sec: '7w04lu'},
					{zones: ['com.br'], id: 1971, lang: 'tr', sec: 'm0i3bs'},
				],
				defaultForm: {id: 1930, lang: 'en', sec: 'lg4wsd'},
				presets: {
					from_domain: settingsCollection.get('fromDomain'),
				}
			}
		);
	}

	static feedbackFormOpen()
	{
		const settingsCollection = Extension.getSettings('biconnector.apache-superset-feedback-form');
		BX.UI.Feedback.Form.open(
			{
				id: 'feedback',
				title: Loc.getMessage('BICONNECTOR_APACHE_SUPERSET_FEEDBACK_FORM_FEEDBACK_FORM'),
				forms: [
					{ zones: ['ru', 'kz', 'by'], id: 656, lang: 'ru', sec: 'wxu17b' },
					{ zones: ['com.br'], id: 658, lang: 'br', sec: 'g0yc31' },
					{ zones: ['de'], id: 660, lang: 'de', sec: 'woterc' },
					{ zones: ['es'], id: 664, lang: 'es', sec: '9ri3ml' },
				],
				defaultForm: { id: 662, lang: 'en', sec: '3tamv8' },
				presets: {
					from_domain: settingsCollection.get('fromDomain'),
				}
			}
		);
	}
}