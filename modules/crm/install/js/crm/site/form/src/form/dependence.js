import * as Type from "./types";
import {Controller} from "./controller";
import {BaseField} from "../field/registry";
import {Page} from "./pager";

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
	#groups: Array<Type.DependenceGroup> = [];

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

	setDependencies(depGroups: Array<Type.DependenceGroup> = [])
	{
		this.#list = [];
		this.#groups = depGroups.filter(depGroup => {
			return Array.isArray(depGroup.list) && depGroup.list.length > 0;
		}).map(depGroup => {
			const group = {
				logic: depGroup.logic || 'or',
				list: [],
				typeId: depGroup.typeId || 0,
			};
			depGroup.list.forEach(dep => this.addDependence(dep, group));
			return group;
		}).filter(group => group.list.length > 0);

		this.#form.getFields().forEach(field => this.trigger(field, ConditionEvents.change));
	}

	addDependence(dep: Type.Dependence, group: Type.DependenceGroup)
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
		if (group)
		{
			group.list.push(item);
			item.group = group;
		}

		return item;
	}

	trigger(field: BaseField, event: string)
	{
		this.#list.filter(dep => {

			// 1. check event
			if (dep.condition.event !== event)
			{
				return false;
			}

			// 2. check target
			if (dep.condition.target !== field.name)
			{
				return false;
			}

			// 3. check group

			return true;

		}).forEach(dep => {

			let list;
			let logicAnd = true;
			if (dep.group && dep.group.typeId > 0)
			{
				logicAnd = dep.group.logic === 'and';
				list = dep.group.list.map(dep => {
					let field = this.#form.getFields().filter(field => dep.condition.target === field.name)[0];
					return {dep, field};
				});
			}
			else
			{
				list = [{dep, field}];
			}

			// 3.check value&operation
			const checkFunction = item => {
				const dep = item.dep;
				const field: BaseField = item.field;
				const values = field.getComparableValues();
				if (values.length === 0)
				{
					values.push('');
				}

				return values
					.filter(value => this.compare(value, dep.condition.value, dep.condition.operation))
					.length === 0
				;
			};

			const isOpposite = logicAnd
				? list.some(checkFunction)
				: list.every(checkFunction)
			;

			// 4. run action
			this.getFieldsByTarget(dep.action.target).forEach(field => {
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

	getFieldsByTarget(target: string)
	{
		const fields = [];
		this.#form.pager.pages.forEach((page: Page) => {
			let currentSectionEquals = false;
			page.fields.forEach((field: BaseField) => {
				const equals = target === field.name;
				if (field.type === 'layout' && field.content.type === 'section')
				{
					if (equals)
					{
						currentSectionEquals = true;
					}
					else
					{
						currentSectionEquals = false;
						return;
					}
				}
				else if (!equals && !currentSectionEquals)
				{
					return;
				}

				fields.push(field);
			});
		});

		return fields;
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
		a = a === null ? '' : a;
		b = b === null ? '' : b;

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