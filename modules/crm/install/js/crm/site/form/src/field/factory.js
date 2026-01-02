import * as BaseField from './base/controller';
import * as StringField from './string/controller';
import * as EmailField from './email/controller';
import * as PhoneField from './phone/controller';
import * as IntegerField from './integer/controller';
import * as DoubleField from './double/controller';
import * as MoneyField from './money/controller';
import * as TextField from './text/controller';
import * as BoolField from './bool/controller';
import * as RadioField from './radio/controller';
import * as CheckboxField from './checkbox/controller';
import * as SelectField from './select/controller';
import * as FileField from './file/controller';
import * as ListField from './list/controller';
import * as ProductField from './product/controller';
import * as DateTimeField from './datetime/controller';
import * as DateField from './date/controller';
import * as AgreementField from './agreement/controller';
import * as NameField from './name/controller';
import * as SecondNameField from './secondname/controller';
import * as LastNameField from './lastname/controller';
import * as CompanyNameField from './companyname/controller';
import * as LayoutField from './layout/controller';
import * as ResourceBookingField from './resourcebooking/controller';
import * as BookingField from './booking/controller';
import * as AddressField from './address/controller';
import * as RqField from './rq/controller';
import * as Container from './container/controller';

const controllers = [
	StringField.Controller,
	PhoneField.Controller,
	EmailField.Controller,
	IntegerField.Controller,
	DoubleField.Controller,
	MoneyField.Controller,
	TextField.Controller,
	BoolField.Controller,
	RadioField.Controller,
	SelectField.Controller,
	CheckboxField.Controller,
	FileField.Controller,
	ListField.Controller,
	ProductField.Controller,
	DateTimeField.Controller,
	DateField.Controller,
	AgreementField.Controller,
	NameField.Controller,
	SecondNameField.Controller,
	LastNameField.Controller,
	CompanyNameField.Controller,
	LayoutField.Controller,
	ResourceBookingField.Controller,
	BookingField.Controller,
	Container.Controller,
	AddressField.Controller,
	RqField.Controller,
];

const component = BaseField.Controller.component();
component.components = {
	...component.components,
	...controllers.reduce((acc, controller) => ({
		...acc,
		[`field-${controller.type()}`]: controller.component(),
	}), {}),
};

class Factory
{
	static create(options: BaseField.Options): BaseField.Controller
	{
		const Controller = controllers.find((it) => options.type === it.type());

		if (!Controller)
		{
			throw new Error(`Unknown field type '${options.type}'`);
		}

		return new Controller(options);
	}

	static getControllers(): BaseField.Controller[]
	{
		return controllers;
	}

	static getComponent(): Object
	{
		return component;
	}
}

export { Factory };
