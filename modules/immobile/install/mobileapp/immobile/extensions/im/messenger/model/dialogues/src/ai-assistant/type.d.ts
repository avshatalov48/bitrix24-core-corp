import { MessengerModel, PayloadData } from '../../../base';

declare type AiAssistantModelState = {
	notifyPanel: {
		isClosedNotifyPanel: boolean,
	},
};

declare type AiAssistantModelActions = 'dialoguesModel/aiAssistantModel/setIsClosedNotifyPanel';

declare type AiAssistantModelMutation = 'updateNotifyPanel';

declare interface AiAssistantNotifyPanelUpdateData extends PayloadData
{
	notifyPanel: {
		isClosedNotifyPanel: boolean
	}
};

declare type AiAssistantModel = MessengerModel<AiAssistantModelState>;
