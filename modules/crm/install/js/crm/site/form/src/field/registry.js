import {Factory} from './factory';
import {Controller as BaseField, Options} from './base/controller';
import * as AgreementField from './agreement/controller';

export{
	Factory,
	Options,
	BaseField,
	AgreementField,
};
export let Type = {
	Base: BaseField,
	Agreement: AgreementField,
};