import { Loc } from 'main.core';
import { OpenLinesMessageManager } from 'imopenlines.v2.lib.message-manager';

describe('OpenLinesMessageManager', () => {
	function createMessageManager(componentId: string, componentParams: { imolForm: string } | null)
	{
		return new OpenLinesMessageManager({
			componentId,
			componentParams,
		});
	}

	describe('checkComponentInOpenLinesList', () => {
		it('Should return true if componentId is in the list of custom OL messages (OpenLinesComponentList)', () => {
			const manager = createMessageManager('HiddenMessage');

			const result = manager.checkComponentInOpenLinesList();
			assert.equal(result, true);
		});

		it('Should return false if componentId is not in the list of custom OL messages (OpenLinesComponentList)', () => {
			const manager = createMessageManager('TestComponent');

			const result = manager.checkComponentInOpenLinesList();
			assert.equal(result, false);
		});
	});

	describe('getUpdatedComponentId', () => {
		it('Should convert a custom OL message with a “like” form parameter to a FeedbackFormMessage', () => {
			const manager = createMessageManager('bx-imopenlines-message', {
				imolForm: 'like',
			});

			const result = manager.getMessageComponent();
			assert.equal(result, 'FeedbackFormMessage');
		});

		it('Should convert a custom OL message with a “form” form parameter to a SystemMessage', () => {
			const manager = createMessageManager('bx-imopenlines-form', {
				imolForm: 'form',
			});

			const result = manager.getMessageComponent();
			assert.equal(result, 'SystemMessage');
		});

		it('Should return the current componentId if it is not in the list of OL messages that need to be replaced (componentForReplace)', () => {
			const manager = createMessageManager('HiddenMessage');

			const result = manager.getMessageComponent();
			assert.equal(result, 'HiddenMessage');
		});
	});
});
