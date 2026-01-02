import { Type } from 'main.core';

import { OptionService } from '../../src/option-service';

describe('OptionService', () => {
	it('Should be a function', () => {
		assert(Type.isFunction(OptionService));
	});
});
