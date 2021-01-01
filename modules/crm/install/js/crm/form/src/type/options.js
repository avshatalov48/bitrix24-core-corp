import * as FormTypes from '../../../site/form/src/form/types';

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

type Document = {
	scheme: number;
	duplicateMode: string;
	deal: Deal;
};

type Responsible = {
	userId: number;
	checkWorkTime: boolean;
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

type Result = {
	success: ResultItem;
	failure: ResultItem;
	redirectDelay: number;
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

type Config = {
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
};

export default Config;