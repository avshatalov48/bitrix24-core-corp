import * as FormTypes from '../../../../site/form/src/form/types';

type PresetField = {
	entityName: string;
	fieldName: string;
	value: string | number;
};

type Payment = {
	use: boolean;
	payer: string;
	systems: Array<number>;
};

type Captcha = {
	use: boolean;
	key: ?string;
	secret: ?string;
};

type Deal = {
	category: string;
	duplicatesEnabled: boolean;
};
type DocumentDynamic = {
	category: number;
};

type Document = {
	scheme: number;
	duplicateMode: string;
	deal: Deal;
	dynamic: DocumentDynamic;
};

type Responsible = {
	users: Array<number>;
	checkWorkTime: boolean;
	supportWorkTime: boolean;
};

type Agreement = {
	id: number;
	checked: boolean;
	required: boolean;
};

type Agreements = {
	use: boolean;
	list: Array<Agreement>;
}

type ResultItem = {
	text: string;
	url: string;
};

type RefillItem = {
	active: boolean;
	caption: string;
};

type Result = {
	success: ResultItem;
	failure: ResultItem;
	redirectDelay: number;
	refill: RefillItem
};

type Callback = {
	from: string;
	text: string;
};

type ViewClick = {
	type: string;
	position: string;
	vertical: string;
};

type ViewAuto = {
	type: string;
	position: string;
	vertical: string;
	delay: number;
};

type Views = {
	click: ViewClick;
	auto: ViewAuto;
};

type AnalyticSteps = {
	name: string;
	code: string;
	event: string;
};
type Analytics = {
	category: string;
	steps: Array<AnalyticSteps>;
};

type IntegrationCaseForm = {
	id: string;
	title: string;
};
type IntegrationCaseAccount = {
	id: string;
	name: string;
};
type IntegrationCase = {
	active: boolean;
	date: string;
	providerCode: string;
	linkDirection: number;
	form: IntegrationCaseForm;
	account: IntegrationCaseAccount;
	fieldsMapping:Array<IntegrationMapping>;
};
type IntegrationMapping = {
	crmFieldKey:string;
	adsFieldKey:string;
	multiple:boolean;
	items: Object
}
type IntegrationProvider = {
	code: string;
	title: string;
	hasAuth: boolean;
	hasPages: boolean;
};
type Integration = {
	canUse: boolean;
	cases: Array<IntegrationCase>;
	providers: Array<IntegrationProvider>;
};

type EmbeddingScript = {
	text: string;
};
type EmbeddingScripts = {
	inline: EmbeddingScript;
	click: EmbeddingScript;
	auto: EmbeddingScript;
};
type EmbeddingView = {
	type: string;
	position: string;
	vertical: string;
	delay?: number;
};
type EmbeddingViews = {
	click: EmbeddingView;
	auto: EmbeddingView;
};
type Embedding = {
	scripts: EmbeddingScripts;
	views: EmbeddingViews;
};

export type FormOptions = {
	data: FormTypes.Options;
	id: number;
	name: string;
	templateId: string;
	presetFields: Array<PresetField>;
	payment: Payment;
	captcha: Captcha;
	document: Document;
	responsible: Responsible;
	agreements: Agreements;
	result: Result;
	callback: Callback;
	views: Views;
	analytics: Analytics;
	integration: Integration;
	embedding: Embedding;
};
