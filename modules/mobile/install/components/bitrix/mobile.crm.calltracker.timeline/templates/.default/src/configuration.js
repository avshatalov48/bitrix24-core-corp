export default class Configuration
{
	static componentName: string = 'bitrix:mobile.crm.calltracker.timeline';
	static signedParameters: string = '';
	static currentAuthor = {
		'AUTHOR_ID': 0,
		'AUTHOR': {
			'FORMATTED_NAME': 'Guest',
			'SHOW_URL': '',
			'IMAGE_URL': ''
		}
	};

	static set({componentName, signedParameters, currentAuthor}) {
		Configuration.componentName = componentName;
		Configuration.signedParameters = signedParameters;
		Configuration.currentAuthor = currentAuthor;
	}
}