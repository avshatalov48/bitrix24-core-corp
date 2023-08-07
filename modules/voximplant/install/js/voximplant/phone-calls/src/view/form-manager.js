import {Type} from 'main.core';

const nop = function(){}

export class FormManager
{
	constructor(params)
	{
		this.node = params.node;
		this.currentForm = null;
		this.callbacks = {
			onFormLoad: Type.isFunction(params.onFormLoad) ? params.onFormLoad : nop,
			onFormUnLoad: Type.isFunction(params.onFormUnLoad) ? params.onFormUnLoad : nop,
			onFormSend: Type.isFunction(params.onFormSend) ? params.onFormSend : nop
		}
	}

	/**
	 * @param {object} params
	 * @param {int} params.id
	 * @param {string} params.secCode
	 */
	load(params)
	{
		let formData = this.getFormData(params);
		window.Bitrix24FormLoader.load(formData);
		this.currentForm = formData;
	};

	unload()
	{
		if (this.currentForm)
		{
			window.Bitrix24FormLoader.unload(this.currentForm);
			this.currentForm = null;
		}
	};

	/**
	 * @param {object} params
	 * @param {int} params.id
	 * @param {string} params.secCode
	 * @returns {object}
	 */
	getFormData(params)
	{
		return {
			id: params.id,
			sec: params.secCode,
			type: 'inline',
			lang: 'ru',
			ref: window.location.href,
			node: this.node,
			handlers:
				{
					'load': this._onFormLoad.bind(this),
					'unload': this._onFormUnLoad.bind(this),
					'send': this.onFormSend.bind(this)
				},
			options:
				{
					'borders': false,
					'logo': false
				}
		}
	};

	_onFormLoad(form)
	{
		this.callbacks.onFormLoad(form);
	}

	_onFormUnLoad(form)
	{
		this.callbacks.onFormUnLoad(form);
	}

	onFormSend(form)
	{
		this.callbacks.onFormSend(form);
	}
}