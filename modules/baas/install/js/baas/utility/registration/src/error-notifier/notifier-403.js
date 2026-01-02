import { Loc } from 'main.core';
import Notifier0 from './notifier-0';

export default class Notifier403 extends Notifier0
{
	static STATUS = 403;

	getTitle(): String
	{
		return Loc.getMessage('BAAS_ERROR_REGISTRATION_TITLE_403');
	}

	getMessage(): String
	{
		return Loc.getMessage(
			'BAAS_ERROR_REGISTRATION_MESSAGE_403',
			{ '#effectiveUrl#': this.effectiveUrl },
		);
	}
}
