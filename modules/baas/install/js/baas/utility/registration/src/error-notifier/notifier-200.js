import { Loc } from 'main.core';
import Notifier0 from './notifier-0';

export default class Notifier200 extends Notifier0
{
	static STATUS = 200;

	getMessage(): String
	{
		return Loc.getMessage(
			'BAAS_ERROR_REGISTRATION_MESSAGE_200',
			{ '#effectiveUrl#': this.effectiveUrl },
		);
	}
}
