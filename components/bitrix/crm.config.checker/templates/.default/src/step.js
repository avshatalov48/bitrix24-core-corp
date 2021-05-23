import { Tag, Type } from "main.core";
import { EventEmitter } from 'main.core.events';

export default class Step {

	id: string;

	actual : boolean|null = null; // does not use yet
	correct : boolean|null = null;
	started : boolean = false;
	finished : boolean = false;

	statusAttributeName : String = "data-bx-status";
	url : String = "";

	errors : Map = new Map();
	notes : Map = new Map();

	constructor (options, iterator)
	{
		this.id = String(options["ID"]);

		this.iterator = iterator;
		this.nodeMain = document.querySelector("#" + options["NODE_ID"]);
		this.node = this.nodeMain.querySelector("[data-bx-block=\"info-block\"]");

		this.actual = options["IS_ACTUAL"];
		this.correct = options["IS_CORRECT"];
		this.started = options["IS_STARTED"];
		this.finished = options["IS_FINISHED"];

		EventEmitter.subscribe(this.iterator, "Iterator:reset", this.reset.bind(this));
		EventEmitter.subscribe(this.iterator, "Iterator:response", this.checkResponse.bind(this));
		EventEmitter.subscribe(this.iterator, "Iterator:error", this.setError.bind(this));

		this.adjustNode();
		this.adjustInfoBlock(options["ERRORS"], options["NOTES"]);

		const button = this.nodeMain.querySelector("[data-bx-url]");
		if (button)
		{
			this.url = button.getAttribute("data-bx-url");
			button.addEventListener("click", this.onClickUrl.bind(this))
		}
	}
	reset()
	{
		this.started = true;
		this.finished = false;
		this.actual = null;
		this.correct = null;
		this.adjustNode();
	}
	checkResponse({data:steps})
	{
		for (const stepId in steps)
		{
			if (steps.hasOwnProperty(stepId) && this.id === stepId)
			{
				this.actual = steps[stepId].actual;
				this.correct = steps[stepId].correct;
				this.started = steps[stepId].started;
				this.finished = steps[stepId].finished;
				this.adjustNode();
				this.adjustInfoBlock(steps[stepId]["errors"], steps[stepId]["notes"]);
			}
		}
	}
	adjustNode()
	{
		let status = "ok";
		if (!this.started)
		{
			status = "not-checked";
		}
		else if (!this.finished)
		{
			status = "in-progress";
		}
		else if (!this.actual)
		{
			status = "not-actual";
		}
		else if (!this.correct)
		{
			status = "not-correct";
		}
		this.nodeMain.setAttribute(this.statusAttributeName, status);
	}
	setError()
	{
		this.node.innerHTML = "Some error was occurred.";
	}
	adjustInfoBlock(errors:Object, notes:Object)
	{
		let child = this.node.lastChild;
		while (child)
		{
			this.node.removeChild(child);
			child = this.node.lastChild;
		}

		this.parseErrors(errors, notes);

		if (this.node.hasChildNodes())
		{
			Tag.style(this.node.parentNode)`
				height: ${this.node.offsetHeight}px;
				opacity: 1;
			`;
		}
		else
		{
			Tag.style(this.node.parentNode)`
				height: 0;
				opacity: 0;
			`;
		}
	}

	onClickUrl()
	{
		if (Type.isStringFilled(this.url))
		{
			BX.SidePanel.Instance.open(this.url);
		}
	}

	parseErrors(errors:Object, notes:Object)
	{
	}
}