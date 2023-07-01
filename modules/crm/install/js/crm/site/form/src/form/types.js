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

const EventTypes = {
	initBefore: 'init:before',
	init: 'init',
	show: 'show',
	showFirst: 'show:first',
	hide: 'hide',
	submit: 'submit',
	submitBefore: 'submit:before',
	sendSuccess: 'send:success',
	sendError: 'send:error',
	destroy: 'destroy',
	fieldFocus: 'field:focus',
	fieldBlur: 'field:blur',
	fieldChangeSelected: 'field:change:selected',
	view: 'view',
};
const ViewTypes = ['inline', 'popup', 'panel', 'widget'];
const ViewPositions = ['left', 'center', 'right'];
const ViewVerticals = ['top', 'bottom'];
type View = {
	type: ?string;
	position: ?string;
	vertical: ?string;
	delay: ?number;
	hideOnOverlayClick: ?boolean;
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

type Identification = {
	type: string;
	id: string;
	sec: ?string;
	address: ?string;
};

type AnalyticsItem = {
	name: ?string,
	code: ?string,
};
type Analytics = {
	field: ?AnalyticsItem,
	category: ?string,
	template: ?AnalyticsItem,
	eventTemplate: ?AnalyticsItem,
};

type ReCaptcha = {
	key: ?string,
	use: ?boolean,
};

type DependenceCondition = {
	target: string;
	event: string;
	value: string;
	operation: ?string;
};
type DependenceAction = {
	target: string;
	type: string;
	value: string;
};

type Dependence = {
	condition: DependenceCondition;
	action: DependenceAction;
};
type DependenceGroup = {
	id: string;
	typeId: number;
	logic: string;
	list: Array<Dependence>;
};

type SubmitResponseRedirect = {
	url: ?string;
	delay: ?number;
};
type RefillResponse = {
	active: ?boolean;
	caption: ?string;
};
type SubmitResponse = {
	resultId: ?number;
	pay: ?boolean;
	message: ?string;
	redirect: ?SubmitResponseRedirect;
	refill: ?RefillResponse;
};

type ProxyItem = {
	source: Array<string>;
	target: string;
};
type Proxy = {
	fonts: Array<ProxyItem>;
};

type Abuse = {
	link: string;
}

type Options = {
	id: ?string;
	identification: ?Identification;
	provider: ?Provider;
	languages: ?Array;
	messages: ?Object;
	language: ?string;
	visible: ?boolean;
	editMode: ?boolean;
	title: ?string;
	desc: ?string;
	buttonCaption: ?string;
	useSign: ?boolean;
	view: ?string|View;
	node: ?Element;
	design: ?string;
	fields: Array<Field.Options>;
	agreements: Array<Field.AgreementField.Options>;
	properties: Object;
	date: ?DateOptions;
	currency: ?Currency;
	analytics: ?Analytics;
	recaptcha: ?ReCaptcha;
	analyticsHandler: ?Function;
	dependencies: ?Array<DependenceGroup>;
	handlers: ?Object;
	proxy: ?Proxy;
	hideOnOverlayClick: ?Boolean;
	abuse: ?Abuse;
};

export {
	Provider,
	DateOptions as Date,
	Currency,
	Options,
	View,
	EventTypes,
	ViewTypes,
	ViewPositions,
	ViewVerticals,
	Identification,
	SubmitResponse,
	Analytics,
	AnalyticsItem,
	ReCaptcha,
	DependenceGroup,
	Dependence,
	DependenceAction,
	DependenceCondition,
	Proxy,
	ProxyItem,
	Abuse,
}
