type EnumInt = {
	id: number;
	name: string;
};

type EnumString = {
	id: string;
	name: string;
};

type Language = EnumString;

type Views = {
	types: Array<EnumString>;
	positions: Array<EnumString>;
	verticals: Array<EnumString>;
};

type Currency = {
	code: string;
	title: string;
	format: string;
};

type Payment = {
	enabled: boolean;
	payers: Array<EnumInt>;
	systems: Array<EnumInt>;
};

type Deal = {
	categories: Array<EnumInt>;
};

type DocumentScheme = {
	id: number;
	name: string;
	entities: Array<string>;
	description: string;
};

type Document = {
	schemes: Array<DocumentScheme>;
	duplicateModes: Array<EnumString>;
	deal: Deal;
};

type Callback = {
	enabled: boolean;
	from: Array;
};

type Captcha = {
	hasDefaults: boolean;
};

type Sign = {
	canRemove: boolean;
};

type Personalization = {
	list: Array<EnumString>;
};

type DependingField = {
	types: Array<string>;
};
type DependingCondition = {
	events: Array<EnumString>;
	operations: Array<EnumString>;
};
type DependingAction = {
	types: Array<EnumString>;
};
type Depending = {
	field: DependingField;
	action: DependingAction;
	condition: DependingCondition;
};

type Dictionary = {
	languages: Array<Language>;
	views: Views;
	currencies: Array<Currency>;
	payment: Payment;
	document: Document;
	callback: Callback;
	captcha: Captcha;
	templates: Array<string>;
	personalization: Personalization;
	depending: Depending;
	sign: Sign;
};

export default Dictionary;