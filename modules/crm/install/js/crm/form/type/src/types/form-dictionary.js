type EnumInt = {
	id: number;
	name: string;
};

type EnumString = {
	id: string;
	name: string;
};

type DescEnumString = {
	id: string;
	name: string;
	desc: string;
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
	mainEntity: number;
	hasInvoice: boolean;
	dynamic: boolean;
	description: string;
};

type DocumentDynamicCategory = {
	id: number;
	name: string;
	stages: Array<EnumInt>;
};
type DocumentDynamic = {
	id: number;
	name: string;
	categories: Array<DocumentDynamicCategory>;
};

type Document = {
	schemes: Array<DocumentScheme>;
	duplicateModes: Array<EnumString>;
	deal: DocumentDeal;
	lead: DocumentLead;
	dynamic: Array<DocumentDynamic>;
};

type Callback = {
	enabled: boolean;
	from: Array;
};

type WhatsApp = {
	enabled: boolean;
	text: string;
	isSetup: boolean;
	setupLink: string;
};

type Captcha = {
	hasDefaults: boolean;
};

type Personalization = {
	list: Array<EnumString>;
};

type Properties = {
	list: Array<DescEnumString>;
};

type DepGroup = {
	types: Array<EnumInt>;
};
type DepField = {
	disallowed: Array<string>;
	types: Array<string>;
};
type DepConditionOperation = {
	id: string;
	name: string;
	fieldTypes: Array<string>;
	excludeFieldTypes: Array<string>;
};

type DepCondition = {
	events: Array<EnumString>;
	operations: Array<DepConditionOperation>;
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

type IntegrationDirection = {
	id: number;
	code: string;
};

type IntegrationProviderMapping = {
	crmFieldType: string;
	adsFieldType: string;
};
type IntegrationProvider = {
	authUrl: string;
	defaultMapping: Array<IntegrationProviderMapping>;
	engineCode: string;
	group: {
		isAuthorized: boolean;
		hasAuth: boolean;
		authUrl: string;
		groupId: Array<number>;
	};
	hasAuth: boolean;
	icon: string;
	isSupportAccount: boolean;
	profile: Object;
	type: string;
	urlAccountList: string;
	urlFormList: string;
	urlInfo: string;
};
type Integration = {
	canUse: boolean;
	directions: Array<IntegrationDirection>;
	providers: Array<IntegrationProvider>;
};

export type FormDictionary = {
	languages: Array<Language>;
	views: Views;
	currencies: Array<Currency>;
	payment: Payment;
	document: Document;
	callback: Callback;
	whatsapp: WhatsApp;
	captcha: Captcha;
	templates: Array<string>;
	personalization: Personalization;
	properties: Properties;
	deps: Deps;
	restriction: Restriction;
	product: Product;
	integration: Integration;
	permissions: {
		userField: {
			add: boolean;
		},
		form: {
			edit: boolean;
		},
	};
	contentTypes: Array<EnumString>;
};