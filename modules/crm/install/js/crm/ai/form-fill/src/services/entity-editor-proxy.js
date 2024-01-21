import { Type, Dom, addCustomEvent } from 'main.core';
import { EditorControlsParams } from '../store/types';
import type { UserFieldModel } from '../store/types';
import { timeout } from './utils';

const controlOutlineClassName = 'bx-crm-ai-merge-fields-ee-control-outline';
const controlAiValueClassName = 'bx-crm-ai-merge-fields-ee-control-ai-value';

export class EntityEditorProxy
{
	#editor = null;

	#initialContainerTop;

	#onUserFieldDeployedCb = null;

	async init(entityEditor)
	{
		this.#editor = entityEditor;
		const correctionY = 5;
		this.#initialContainerTop = this.#editor.getContainer().getBoundingClientRect().y + correctionY;

		addCustomEvent(
			window,
			'BX.UI.EntityUserFieldLayoutLoader:onUserFieldDeployed',
			(field) => {
				if (!Type.isFunction(this.#onUserFieldDeployedCb))
				{
					return;
				}
				this.#onUserFieldDeployedCb(field);
			},
		);
	}

	setOnUserFieldDeployedCb(cb) {
		this.#onUserFieldDeployedCb = cb;
	}

	async getEditorControlsParams(fieldsIds: Set<string[]>): Promise<EditorControlsParams[]>
	{
		await timeout(10);
		const result: EditorControlsParams[] = [];

		let counter = 0;
		for (const control of this.#editor.getAllControls())
		{
			if (!fieldsIds.has(control.getId()) || !control.getWrapper())
			{
				continue;
			}

			const [value, model] = this.#getValueFromControl(control);

			result.push({
				fieldId: control.getId(),
				relatedFieldOffsetY: control.getWrapper().getBoundingClientRect().y,
				originalValue: value,
				originalModel: model,
				order: counter,
			});
			counter++;
		}

		return result;
	}

	async getEditorControlsPositions(fieldsIds: Set<string[]>): Promise<Map<string, number>>
	{
		const result = new Map();

		for (const control of this.#editor.getAllControls())
		{
			if (!fieldsIds.has(control.getId()) || !control.getWrapper())
			{
				continue;
			}
			const y = control.getWrapper().getBoundingClientRect().y;
			result.set(control.getId(), y - this.#initialContainerTop);
		}

		return result;
	}

	setControlOutline(fieldId: string, show: boolean): void
	{
		const control = this.#editor.getControlById(fieldId);

		if (!control)
		{
			return;
		}

		const wrapper = control.getWrapper();

		if (show)
		{
			Dom.addClass(wrapper, controlOutlineClassName);
		}
		else
		{
			Dom.removeClass(wrapper, controlOutlineClassName);
		}
	}

	setControlAiClass(fieldId: string, show: boolean): void
	{
		const control = this.#editor.getControlById(fieldId);

		if (!control)
		{
			return;
		}

		const wrapper = control.getWrapper();

		if (show)
		{
			Dom.addClass(wrapper, controlAiValueClassName);
		}
		else
		{
			Dom.removeClass(wrapper, controlAiValueClassName);
		}
	}

	async setFieldValue(fieldName: string, newValue: ControlValue)
	{
		const control = this.#editor.getControlById(fieldName);

		if (!control)
		{
			return;
		}

		switch (control.constructor)
		{
			case BX.Crm.EntityEditorText:
				this.#setPlainTextFieldValue(fieldName, newValue.value);
				break;
			case BX.UI.EntityEditorBB:
				this.#setEntityEditorBBValue(fieldName, newValue.value);
				this.#refreshControlLayout(control);
				break;
			case BX.Crm.EntityEditorUserField:
				this.#setUserFieldValue(fieldName, newValue.model);
				this.#refreshControlLayout(control);
				break;
			default:
				throw new Error('Not supported field type');
		}
	}

	#refreshControlLayout(control)
	{
		control.refreshLayout({ reset: true });
	}

	#setEntityEditorBBValue(fieldId, value)
	{
		const fieldKey = `${fieldId}_HTML`;
		const model = this.#editor.getModel();
		model.setField(fieldKey, value, { enableNotification: true });
	}

	#setPlainTextFieldValue(fieldId, value)
	{
		const model = this.#editor.getModel();
		model.setField(fieldId, value, { enableNotification: true });
	}

	#setUserFieldValue(fieldId, signedModel)
	{
		const model = this.#editor.getModel();
		model.setField(fieldId, signedModel);
	}

	#getValueFromControl(control): [?any, ?UserFieldModel] {
		const controlValue = control.getValue();

		let value: ?any = null;
		let ufModel: ?UserFieldModel = null;

		if (control.constructor === BX.UI.EntityEditorBB)
		{
			const model = this.#editor.getModel();
			const fieldKey = `${control.getId()}_HTML`;
			value = model.getField(fieldKey, '');
		}
		else if (
			Type.isObject(controlValue)
			&& Object.hasOwn(controlValue, 'VALUE')
		)
		{
			value = controlValue.VALUE;
			ufModel = controlValue;
		}
		else if (Type.isString(controlValue) || Type.isNumber(controlValue))
		{
			value = controlValue;
			ufModel = null;
		}

		return [value, ufModel];
	}
}

export interface ControlValue {
	model: ?UserFieldModel,
	value: string | number | null
}
