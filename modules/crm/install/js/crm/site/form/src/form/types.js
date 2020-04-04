import * as Field from "../field/registry";

type ProviderForm = {
	id: string;
	sec: string;
	lang: string;
	address: string;
};
type ProviderUser = {
	name: string;
	secondName: string;
	lastName: string;
	phone: string;
	email: string;
	companyName: string;
};
type Provider = {
	form: ?ProviderForm|string,
	user: ?ProviderUser|Function;
};

const ViewTypes = ['inline', 'popup', 'panel', 'widget'];
const ViewPositions = ['left', 'center', 'right'];
const ViewVerticals = ['top', 'bottom'];
type View = {
	type: ?string;
	position: ?string;
	vertical: ?string;
	delay: ?number;
};

type DateOptions = {
	dateFormat: ?string;
	dateTimeFormat: ?string;
	sundayFirstly: ?Boolean;
};

type Currency = {
	code: string;
	title: string;
	format: string;
};

type Handlers = {
	hide: Array<Function>;
	show: Array<Function>;
};

type Options = {
	id: ?string;
	provider: ?Provider;
	languages: ?Array;
	messages: ?Object;
	language: ?string;
	visible: ?boolean;
	title: ?string;
	desc: ?string;
	buttonCaption: ?string;
	useSign: ?boolean;
	view: ?string;
	node: ?Element;
	design: ?string;
	fields: Array<Field.Options>;
	agreements: Array<Field.AgreementField.Options>;
	date: ?DateOptions;
	currency: ?Currency;
	handlers: ?Handlers;
};

export {
	Provider,
	DateOptions as Date,
	Currency,
	Options,
	View,
	ViewTypes,
	ViewPositions,
	ViewVerticals,
}