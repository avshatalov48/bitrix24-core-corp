import { Guide } from 'ui.tour';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import { FlowCopilotAdvice } from './spot/flow-copilot-advice';

import { Spot } from './spot/spot';
import { MyTasks } from './spot/my-tasks';
import { TaskStart } from './spot/task-start';

type Params = {
	id: number,
	autoSave: boolean,
	overlay: boolean,
};

export class Clue
{
	static SPOT = Object.freeze({
		MY_TASKS: new MyTasks(),
		TASK_START: new TaskStart(),
		FLOW_COPILOT_ADVICE: new FlowCopilotAdvice(),
	});

	#params: Params;

	#spot: ?Spot = null;
	#guide: ?Guide = null;

	constructor(params: Params)
	{
		this.#params = params;
	}

	show(spot: Spot, bindElement: HTMLElement): void
	{
		this.#spot = spot;
		this.#spot.setTargetElement(bindElement);

		this.#guide = new Guide({
			id: this.#params.id,
			autoSave: this.#params.autoSave === true,
			overlay: this.#params.overlay === true,
			simpleMode: true,
			onEvents: true,
			steps: [
				{
					target: bindElement,
					iconSrc: this.#spot.getIconSrc(),
					title: this.#spot.getTitle(),
					text: this.#spot.getText(),
					position: 'bottom',
					condition: {
						top: true,
						bottom: false,
						color: this.#spot.getConditionColor(),
					},
				},
			],
		});

		BannerDispatcher.normal.toQueue((onDone) => {
			const guidePopup = this.#guide.getPopup();

			const onClose = () => {
				this.#spot.close();
				onDone();
			};

			guidePopup.setWidth(this.#spot.getWidth());
			guidePopup.setAngle({ offset: this.#spot.getAngleOffset() });
			guidePopup.setAutoHide(this.#spot.isAutoHide());

			guidePopup.subscribe('onClose', onClose);
			guidePopup.subscribe('onDestroy', onClose);

			this.#spot.showLight();
			this.#guide.start();
		});
	}

	isShown(): boolean
	{
		if (this.#guide === null)
		{
			return false;
		}

		return this.#guide.getPopup().isShown();
	}

	close(): void
	{
		if (this.#guide)
		{
			this.#guide.close();
		}

		if (this.#spot)
		{
			this.#spot.close();
		}
	}

	adjustPosition(bindElement: HTMLElement): void
	{
		if (this.#guide)
		{
			this.#guide.getPopup().setBindElement(bindElement);
			this.#guide.getPopup().adjustPosition();
		}

		if (this.#spot)
		{
			this.#spot.setTargetElement(bindElement);
			this.#spot.showLight();
		}
	}
}
