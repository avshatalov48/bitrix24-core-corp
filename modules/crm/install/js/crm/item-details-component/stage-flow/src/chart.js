import { Dom, Text } from 'main.core';
import { StageFlow } from 'ui.stageflow';
import { Button } from 'ui.buttons';
import { PermissionChecker as StagePermissionChecker } from 'crm.stage.permission-checker';
import { StageModel } from 'crm.stage-model';

import './css/chart.css';

export class Chart extends StageFlow.Chart
{
	permissionChecker: StagePermissionChecker;
	getStageModelCallback: (id: number) => ?StageModel;
	isNewItem: boolean = false;

	constructor(
		params: Object, /** @see StageFlow.Chart */
		stages: Array,
		permissionChecker: StagePermissionChecker,
		gettingStageModelCallback: (id: number) => StageModel,
		isNewItem: boolean = false,
	)
	{
		super(params, stages);

		this.permissionChecker = permissionChecker;
		this.getStageModelCallback = gettingStageModelCallback;
		this.isNewItem = isNewItem;

		if (!this.isNewItem)
		{
			this.#adjustDisableStages();
		}
	}

	onStageMouseHover(stage: Stage): void
	{
		this.increaseStageWidthForNameVisibility(stage);
		if (stage.isDisabled())
		{
			return;
		}

		super.onStageMouseHover(stage);
	}

	onStageClick(stage: Stage): void
	{
		if (!this.#isHasPermissionToMove(stage.getId()))
		{
			this.permissionChecker.showMissPermissionError();

			return;
		}

		super.onStageClick(stage);
	}

	onFinalStageClick(stage: Stage): void
	{
		if (!this.#isHasPermissionToMoveAtLeastOneTerminationStage())
		{
			this.permissionChecker.showMissPermissionError();

			return;
		}

		super.onFinalStageClick(stage);
	}

	setCurrentStageId(stageId: number): Chart
	{
		super.setCurrentStageId(stageId);

		this.#adjust();

		return this;
	}

	getSemanticPopupSuccessButton(): Button
	{
		const successButton = super.getSemanticPopupSuccessButton();
		if (!this.#isHasPermissionToMoveSuccessStage())
		{
			this.#prepareDisableSemanticButton(successButton);
		}

		return successButton;
	}

	getSemanticPopupFailureButton(): ?Button
	{
		const failureButton = super.getSemanticPopupFailureButton();
		if (failureButton === null)
		{
			return null;
		}

		if (!this.#isHasPermissionToMoveAtLeastOneFailureStages())
		{
			this.#prepareDisableSemanticButton(failureButton);
		}

		return failureButton;
	}

	getFinalStagePopupFailStage(stage: Stage): HTMLElement
	{
		const finalStage = super.getFinalStagePopupFailStage(stage);

		if (!this.#isHasPermissionToMove(stage.getId()))
		{
			finalStage.onclick = (event: MouseEvent) => {
				event.preventDefault();
				this.permissionChecker.showMissPermissionError();
			};

			Dom.addClass(finalStage, '--disabled');
		}

		return finalStage;
	}

	setCheckedStageInFailStagesWrapper(failStageListWrapper: HTMLElement): void
	{
		const failStages = [...this.extractFinalStagePopupFailStages(failStageListWrapper)];
		const failStageInputs = failStages.map((radioButtonNode) => {
			return radioButtonNode.querySelector('input');
		});

		const firstAvailableFailStage = this.getFirstFailStage();
		if (!firstAvailableFailStage)
		{
			return;
		}

		const relatedRadioButton = failStageInputs.find((radioButton: HTMLInputElement) => {
			const stageId = radioButton?.dataset?.stageId;
			if (stageId)
			{
				return firstAvailableFailStage.getId() === Text.toInteger(stageId);
			}

			return false;
		});

		if (relatedRadioButton)
		{
			relatedRadioButton.checked = true;
		}
	}

	getFirstFailStage(): ?Stage
	{
		const stages = [...this.stages.values()];

		return stages.find((stage: Stage) => stage.isFail() && this.#isHasPermissionToMove(stage.getId()));
	}

	getFirstFailStageName(): ?string
	{
		// get first fail stage name without permissions check
		return super.getFirstFailStage()?.getName();
	}

	#prepareDisableSemanticButton(button: Button): void
	{
		button
			.setDisabled()
			.setProps({ disabled: null }) // necessary in order to show a notification about miss permissions
			.bindEvent('click', this.permissionChecker.showMissPermissionError)
		;
	}

	#getCurrentStage(): ?StageModel
	{
		return this.getStageModelCallback(this.currentStage);
	}

	#getCurrentStatusId(): ?string
	{
		return this.#getCurrentStage()?.getStatusId();
	}

	#getStage(id: number): ?StageModel
	{
		return this.getStageModelCallback(id);
	}

	#isHasPermissionToMove(stageFlowId: number): boolean
	{
		const compareStage = this.#getStage(stageFlowId);
		if (!compareStage)
		{
			return false;
		}

		return this.permissionChecker.isHasPermissionToMove(this.#getCurrentStatusId(), compareStage.getStatusId());
	}

	#isHasPermissionToMoveAtLeastOneTerminationStage(): boolean
	{
		return this.permissionChecker.isHasPermissionToMoveAtLeastOneTerminationStage(this.#getCurrentStatusId());
	}

	#isHasPermissionToMoveSuccessStage(): boolean
	{
		return this.permissionChecker.isHasPermissionToMoveSuccessStage(this.#getCurrentStatusId());
	}

	#isHasPermissionToMoveAtLeastOneFailureStages(): boolean
	{
		return this.permissionChecker.isHasPermissionToMoveAtLeastOneFailureStage(this.#getCurrentStatusId());
	}

	#isDisableStageFlow(flowStage: Stage): boolean
	{
		if (flowStage.isFinal())
		{
			return false;
		}

		if (flowStage === this.getFinalStage())
		{
			return !this.#isHasPermissionToMoveAtLeastOneTerminationStage();
		}

		return !this.#isHasPermissionToMove(flowStage.getId());
	}

	#adjust(): void
	{
		this.#adjustDisableStages();
		this.#adjustSemanticsSelectorPopupButtons();
	}

	#adjustDisableStages(): void
	{
		this.stages.forEach((stage: Stage) => {
			stage.setDisable(this.#isDisableStageFlow(stage));
		});
	}

	#adjustSemanticsSelectorPopupButtons(): void
	{
		const popup = super.getSemanticSelectorPopup();
		popup.setButtons([
			this.getSemanticPopupSuccessButton(),
			this.getSemanticPopupFailureButton(),
		]);
	}
}
