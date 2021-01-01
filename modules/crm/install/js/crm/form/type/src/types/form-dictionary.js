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

type DocumentDeal = {
	categories: Array<EnumInt>;
};
type DocumentLead = {
	enabled: boolean;
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
	deal: DocumentDeal;
	lead: DocumentLead;
};

type Callback = {
	enabled: boolean;
	from: Array;
};

type Captcha = {
	hasDefaults: boolean;
};

type Personalization = {
	list: Array<EnumString>;
};

type DepGroup = {
	types: Array<EnumInt>;
};
type DepField = {
	types: Array<string>;
};

type DepCondition = {
	events: Array<EnumString>;
	operations: Array<EnumString>;
};

type DepAction = {
	types: Array<EnumString>;
};

type Deps = {
	group: DepGroup;
	field: DepField;
	action: DepAction;
	condition: DepCondition;
};

type Restriction = {
	helper: string;
};

type Product = {
	isCloud: boolean;
	isRegionRussian: boolean;
};

export type FormDictionary = {
	languages: Array<Language>;
	views: Views;
	currencies: Array<Currency>;
	payment: Payment;
	document: Document;
	callback: Callback;
	captcha: Captcha;
	templates: Array<string>;
	personalization: Personalization;
	deps: Deps;
	restriction: Restriction;
	product: Product;
};