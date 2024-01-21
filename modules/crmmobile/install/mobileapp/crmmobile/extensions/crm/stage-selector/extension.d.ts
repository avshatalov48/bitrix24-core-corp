import {
	StageSelectorFieldProps,
	StageSelectorFieldState,
} from '../../../../../../../mobile/install/mobileapp/mobile/extensions/bitrix/layout/ui/fields/stage-selector/extension';

export interface CrmStageSelectorProps extends StageSelectorFieldProps {
	entityTypeId: number;
	categoryId: number;
	isNewEntity: boolean;
	entityId: number;
	showReadonlyNotification: boolean;
}

export interface CrmStageSelectorState extends StageSelectorFieldState {
	activeStageId: number;
	nextStageId: number | null;
}
