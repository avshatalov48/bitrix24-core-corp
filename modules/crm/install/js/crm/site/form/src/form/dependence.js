import * as Type from "./types";
import {Controller} from "./controller";
import {BaseField} from "../field/registry";

const ConditionEvents = {change: 'change'};
const Operations = {
	equal: '=',
	notEqual: '!=',
	greater: '>',
	greaterOrEqual: '>=',
	less: '<',
	lessOrEqual: '<=',
	empty: 'empty',
	any: 'any',
	contain: 'contain',
	notContain: '!contain',
};
const OperationAliases = {
	notEqual: '<>',
};
const ActionTypes = {show: 'show', hide: 'hide', change: 'change'};
const OppositeActionTypes = {
	[ActionTypes.hide]: ActionTypes.show,
	[ActionTypes.show]: ActionTypes.hide,
};

class Manager
{
	#form: Controller;
	#list: Array<Type.Dependence> = [];

	constructor(form: Controller)
	{
		this.#form = form;
		this.#form.subscribeAll(this.onFormEvent.bind(this));
	}

	onFormEvent(data: any, obj: Object, type: string)
	{
		if (this.#list.length === 0)
		{
			return;
		}

		let event;
		switch (type)
		{
			case Type.EventTypes.fieldChangeSelected:
				event = ConditionEvents.change;
				break;
			case Type.EventTypes.fieldBlur:
			default:
				return;
		}

		this.trigger(data.field, event);
	}

	setDependencies(deps: Array<Type.Dependence> = [])
	{
		this.#list = [];
		deps.forEach(dep => this.addDependence(dep));
		this.#form.getFields().forEach(field => this.trigger(field, ConditionEvents.change));
	}

	addDependence(dep: Type.Dependence)
	{
		if (typeof dep !== 'object' || typeof dep.condition !== 'object' || typeof dep.action !== 'object')
		{
			return;
		}
		if (!dep.condition.target || !dep.condition.event || !ConditionEvents[dep.condition.event])
		{
			return;
		}
		if (!dep.action.target || !dep.action.type || !ActionTypes[dep.action.type])
		{
			return;
		}

		dep.condition.operation = ConditionOperations.indexOf(dep.condition.operation) > 0
			? dep.condition.operation
			: Operations.equal;

		const item = {
			condition: {
				target: '',
				event: '',
				value: '',
				operation: '',
				...dep.condition
			},
			action: {
				target: '',
				type: '',
				value: '',
				...dep.action
			},
		};

		this.#list.push(item);
	}

	trigger(field: BaseField, event: string)
	{
		this.#list.forEach(dep => {
			// 1. check event
			if (dep.condition.event !== event)
			{
				return;
			}

			// 2. check target
			if (dep.condition.target !== field.name)
			{
				return;
			}

			// 3.check value&operation
			const isOpposite = field.values()
				.filter(value => this.compare(value, dep.condition.value, dep.condition.operation))
				.length === 0;

			// 4. run action
			this.#form.getFields().forEach(field => {
				if (dep.action.target !== field.name)
				{
					return;
				}

				let actionType = dep.action.type;
				if (isOpposite)
				{
					actionType = OppositeActionTypes[dep.action.type];
					if (!actionType)
					{
						return;
					}
				}

				this.runAction({...dep.action, type: actionType}, field);
			});
		});
	}

	runAction(action: Type.DependenceAction, field: BaseField)
	{
		switch(action.type)
		{
			case ActionTypes.change:
				//field.visible = true;
				return;

			case ActionTypes.show:
				field.visible = true;
				return;

			case ActionTypes.hide:
				field.visible = false;
				return;
		}
	}

	compare(a: string, b: string, operation: string)
	{
		switch(operation)
		{
			case Operations.greater:
				return parseFloat(a) > parseFloat(b);
			case Operations.greaterOrEqual:
				return parseFloat(a) >= parseFloat(b);
			case Operations.less:
				return parseFloat(a) < parseFloat(b);
			case Operations.lessOrEqual:
				return parseFloat(a) <= parseFloat(b);
			case Operations.empty:
				return !a;
			case Operations.any:
				return !!a;
			case Operations.contain:
				return a.indexOf(b) >= 0;
			case Operations.notContain:
				return a.indexOf(b) < 0;
			case Operations.notEqual:
				return a !== b;
			case Operations.equal:
			default:
				return a === b;
		}
	}
}

const ConditionOperations = [];
for (let operationName in Operations)
{
	ConditionOperations.push(Operations[operationName]);
}
for (let operationName in OperationAliases)
{
	ConditionOperations.push(Operations[operationName]);
}


export default Manager;