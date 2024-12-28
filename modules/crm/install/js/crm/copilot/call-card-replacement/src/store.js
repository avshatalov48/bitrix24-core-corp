import mutations from './store/mutations';
import getters from './store/getters';
import actions from './store/actions';
import { Text } from 'main.core';

export type ApplicationState = {
	guid: string,
	callId: string,
	assessment: {
		id: ?number,
		title: ?string,
		prompt: ?string,
	},
	hasAvailableSelectorItems: boolean,
	isAttachCallAssessment: boolean,
};

export default () => {
	return {
		state: {
			guid: Text.getRandom(16),
			callId: null,
			assessment: {
				id: null,
				title: null,
				prompt: null,
			},
			hasAvailableSelectorItems: false,
			isAttachCallAssessment: true,
		},
		mutations,
		getters,
		actions,
	};
};
