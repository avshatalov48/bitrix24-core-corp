import {ControlButton} from '../../src/control-button';
import {Dom} from 'main.core';

let controlButtonObj = null;

beforeEach(() => {
	controlButtonObj = new ControlButton({
		container:  Dom.create('div'),
		entityType: 'calendar_event',
		entityId: 1,
	});
});

describe('ControlButton', () => {
	it('Should be a function', () => {
		assert(typeof ControlButton === 'function');
	});

	it('should construct init values', () => {
		assert.equal(controlButtonObj.entityType, 'calendar_event');
		assert.equal(controlButtonObj.entityId, 1);
	});
});
