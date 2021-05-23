import {Event, Type, ajax} from 'main.core';
import {ButtonManager, Button} from 'ui.buttons';
import Step from './step';
import StepTelephony from './steptelephony';
import StepCrmForm from './stepcrmform';
import StepImconnector from './stepimconnector';
import StepMessageService from './stepmessageservice';
import StepPaySystem from './steppaysystem';
import {EventEmitter} from 'main.core.events';

const stepMappings = {
	'Step' : Step,
	'StepTelephony' : StepTelephony,
	'StepCrmForm' : StepCrmForm,
	'StepImconnector' : StepImconnector,
	'StepMessageService' : StepMessageService,
	'StepPaySystem' : StepPaySystem
};

export default class Iterator {
	id: string;

	started: boolean = false;
	finished: boolean = false;

	steps: Map = new Map();

	resetButton: ?Button = null;
	closeButton: ?Button = null;

	componentName: string;
	signedParameters: Object;

	constructor(id, data)
	{
		this.id = id;

		this.started = data.started;
		this.finished = data.finished;

		this.steps = new Map();

		data.steps.map((stepOption) => {
			this.addStep(stepOption);
		});

		if (data["buttons"]["start"])
		{
			this.resetButton = ButtonManager.createFromNode(data["buttons"]["start"]);
			Event.bind(
				this.resetButton.getContainer(),
				"click",
				(event) => {
					event.preventDefault();
					this.start();
				}
			);
			EventEmitter.subscribe(this, "Iterator:reset", () => { this.resetButton.setWaiting(true); });
			EventEmitter.subscribe(this, "Iterator:finish", () => { this.resetButton.setWaiting(false); })
			EventEmitter.subscribe(this, "Iterator:error", () => { this.resetButton.setWaiting(false); });
		}

		this.componentName = data["componentName"];
		this.signedParameters = data["signedParameters"];
	}
	addStep(stepOption)
	{
		const id = String(stepOption["ID"]);
		const stepClassName = id.substring(id.lastIndexOf("\\") + 1);
		let step;
		if (stepMappings[stepClassName])
		{
			step = new stepMappings[stepClassName](stepOption, this);
		}
		else
		{
			step = new Step(stepOption, this);
		}
		EventEmitter.subscribe(step, "Step:action", ({target, data}) => {
			this.execute(target.id, data.action, data.data);
		});
		this.steps.set(id, step);
	}
	getId()
	{
		return this.id;
	}
	start()
	{
		EventEmitter.emit(this, "Iterator:reset", []);
		this.send("reset");
	}
	continue()
	{
		EventEmitter.emit(this, "Iterator:continue", []);
		this.send("continue");
	}
	finish()
	{
		EventEmitter.emit(this, "Iterator:finish", []);
		this.resetButton.setDisabled(true);
	}
	error({errors})
	{
		EventEmitter.emit(this, "Iterator:error", data);
	}
	execute(stepId, stepAction, stepData)
	{
		this.send("executeStep", {
			stepId : stepId,
			stepAction: stepAction,
			stepData : stepData
		});
	}
	send(action, data)
	{
		data = Type.isPlainObject(data) ? data : {};
		ajax
			.runComponentAction(
				this.componentName,
				action,
				{
					signedParameters: this.signedParameters,
					mode: "class",
					data: data
				},
			)
			.then(this.response.bind(this), this.error.bind(this));
	}
	response({data})
	{
		this.started = data.started;
		this.finished = data.finished;

		EventEmitter.emit(this, "Iterator:response", data["stepSteps"]);

		if (this.finished !== true)
		{
			this.continue();
		}
		else
		{
			this.finish();
		}
	}
}