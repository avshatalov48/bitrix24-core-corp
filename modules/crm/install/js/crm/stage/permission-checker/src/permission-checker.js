import { Loc } from 'main.core';
import 'ui.notification';
import { StageModel } from 'crm.stage-model';

const SEMANTICS_TRANSLATE_RULES = {
	process: null,
	success: 'S',
	failure: 'F',
	apology: 'F',
};

export class PermissionChecker
{
	#stages: Map<string, StageModel>;

	constructor(stages: Map<string, StageModel>)
	{
		this.#stages = stages;
	}

	isHasPermissionToMove(fromStatusId: string, toStatusId: string): boolean
	{
		if (fromStatusId === toStatusId)
		{
			return true;
		}

		const targetStage = this.#stages.get(toStatusId);
		if (!targetStage)
		{
			return false;
		}

		if (targetStage.isAllowedMoveToAnyStage())
		{
			return true;
		}

		const stage = this.#stages.get(fromStatusId);
		if (!stage)
		{
			return false;
		}

		return (stage.getStagesToMove()?.includes(toStatusId) ?? false)
			|| (stage.isAllowedMoveToAnyStage())
		;
	}

	isHasPermissionToMoveAtLeastOneFailureStage(fromStatusId: string): boolean
	{
		return this.getStages()
			.some((stage) => {
				return stage.isFailure()
					&& this.isHasPermissionToMove(fromStatusId, stage.getStatusId())
			})
		;
	}

	isHasPermissionToMoveSuccessStage(fromStatusId: string): boolean
	{
		return this.getStages()
			.some((stage) => {
				return stage.isSuccess()
					&& this.isHasPermissionToMove(fromStatusId, stage.getStatusId())
				;
			})
		;
	}

	isHasPermissionToMoveAtLeastOneTerminationStage(fromStatusId: string): boolean
	{
		return this.isHasPermissionToMoveSuccessStage(fromStatusId)
			|| this.isHasPermissionToMoveAtLeastOneFailureStage(fromStatusId)
		;
	}

	showMissPermissionError(): void
	{
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_STAGE_MISS_PERMISSION_TO_MOVE_STAGE'),
			autoHideDelay: 2000,
		});
	}

	getStages(): Array<StageModel>
	{
		return [...this.#stages.values()];
	}

	static createFromStageModels(stages: Array<StageModel>): PermissionChecker
	{
		const stagesMap = new Map();

		stages.forEach((stage) => {
			stagesMap.set(stage.getStatusId(), stage);
		});

		return new PermissionChecker(stagesMap);
	}

	static createFromStageInfos(stageInfos: Array<Object>): PermissionChecker
	{
		const stageModels = [];

		stageInfos.forEach((stageInfo) => {
			const stageModelSemantics = SEMANTICS_TRANSLATE_RULES[stageInfo.semantics];

			const stageModelData = { ...stageInfo };
			stageModelData.semantics = stageModelSemantics;
			stageModelData.statusId = stageInfo.id;

			const stageModel = new StageModel(stageModelData);
			stageModels.push(stageModel);
		});

		return PermissionChecker.createFromStageModels(stageModels);
	}
}
