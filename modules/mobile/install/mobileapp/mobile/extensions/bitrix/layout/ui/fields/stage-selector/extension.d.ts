import { BaseFieldProps, BaseFieldState } from '../base/extension';

export type Stage = {
	id: number;
	name: string;
	color: string;
	semantics: string;
}

export interface StageSelectorFieldProps extends BaseFieldProps {
	processStages: Stage[];
	successStages: Stage[];
	failedStages: Stage[];
	animationMode: string;
	notifyAboutReadOnlyStatus?: () => void;
	forceUpdate?: () => void;
}

export interface StageSelectorFieldState extends BaseFieldState {
	activeStageId: number;
	nextStageId: number | null;
}
