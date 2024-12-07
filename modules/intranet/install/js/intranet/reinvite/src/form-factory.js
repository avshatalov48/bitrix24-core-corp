import { Form } from './form';
import { PhoneForm } from './phone-form';
import { EmailForm } from './email-form';
import { FormType } from './types';

export class FormFactory
{
	constructor() {}

	static create(type: FormType, options): Form
	{
		switch (type)
		{
			case FormType.EMAIL:
				return new EmailForm(options);
			case FormType.PHONE:
				return new PhoneForm(options);
			default:
				throw new Error('Unknown ContextType value: ' + type);
		}
	}
}