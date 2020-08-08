import {Dom, Type, ajax as Ajax, Event, Reflection} from "main.core";
import {Loader} from "main.loader";

export class Component
{
	analyticsLabel = '';
	errorsContainer;
	params;

	constructor(form, params)
	{
		this.params = params;
		this.form = form;
		this.saveButton = document.getElementById('ui-button-panel-save');
		this.cancelButton = document.getElementById('ui-button-panel-cancel');
		this.deleteButton = document.getElementById('ui-button-panel-remove');

		if(Type.isString(params.analyticsLabel))
		{
			this.analyticsLabel = params.analyticsLabel;
		}
		if(Type.isString(params.method))
		{
			this.method = params.method;
		}
		if(Type.isDomNode(params.errorsContainer))
		{
			this.errorsContainer = params.errorsContainer;
		}
	}

	init()
	{
		this.bindEvents();
	}

	bindEvents()
	{
		Event.bind(this.saveButton, 'click', (event) =>
			{
				this.save(event);
			}, {
				passive: false
			}
		);

		if(this.deleteButton)
		{
			Event.bind(this.deleteButton, 'click', (event) =>
			{
				this.delete(event);
			});
		}
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new Loader({size: 150});
		}

		return this.loader;
	}

	startProgress()
	{
		this.isProgress = true;
		if(!this.getLoader().isShown())
		{
			this.getLoader().show(this.form);
		}
		this.hideErrors();
	}

	stopProgress()
	{
		this.isProgress = false;
		this.getLoader().hide();
		setTimeout(() =>
		{
			Dom.removeClass(this.saveButton, 'ui-btn-wait');
			Dom.removeClass(this.closeButton, 'ui-btn-wait');
			if(this.deleteButton)
			{
				Dom.removeClass(this.deleteButton, 'ui-btn-wait');
			}
		}, 200);
	}

	prepareData()
	{

	}

	save(event)
	{
		event.preventDefault();
		if(!this.form)
		{
			return;
		}
		if(this.isProgress)
		{
			return;
		}
		if(!this.method)
		{
			return;
		}
		this.startProgress();
		let data = this.prepareData();

		Ajax.runAction(this.method, {
			analyticsLabel: this.analyticsLabel,
			data: data
		}).then((response) =>
		{
			this.afterSave(response);
			this.stopProgress();
		}).catch((response) =>
		{
			this.showErrors(response.errors);
			this.stopProgress();
		});
	}

	afterSave(response)
	{
		this.addDataToSlider('response', response);
	}

	getSlider()
	{
		if(Reflection.getClass('BX.SidePanel'))
		{
			return BX.SidePanel.Instance.getSliderByWindow(window);
		}

		return null;
	}

	addDataToSlider(key, data)
	{
		if(Type.isString(key) && Type.isPlainObject(data))
		{
			let slider = this.getSlider();
			if(slider)
			{
				slider.data.set(key, data);
			}
		}
	}

	showErrors(errors)
	{
		let text = '';
		errors.forEach(({message}) =>
		{
			text += message;
		});
		if(Type.isDomNode(this.errorsContainer))
		{
			this.errorsContainer.innerText = text;
		}
		else
		{
			console.error(text);
		}
	}

	hideErrors()
	{
		if(Type.isDomNode(this.errorsContainer))
		{
			this.errorsContainer.innerText = '';
		}
	}

	delete(event)
	{
		event.preventDefault();
	}

	getPermissionSelectors()
	{
		return [];
	}

	getPermissions()
	{
		let permissions = [];

		this.getPermissionSelectors().forEach((permission) =>
		{
			let node = this.form.querySelector(permission.selector);
			if(node)
			{
				permissions = [...permissions, ...this.getPermission(node, permission.action)];
			}
		});

		if(permissions.length <= 0)
		{
			permissions = false;
		}

		return permissions;
	}

	getPermission(node, action)
	{
		let permissions = [];
		const inputs = Array.from(node.querySelectorAll('input[type="hidden"]'));
		const select = node.querySelector('select');
		inputs.forEach((input) =>
		{
			if(input.value && Type.isString(input.value) && input.value.length > 0)
			{
				permissions.push({
					accessCode: input.value,
					permission: select.value,
					action: action
				});
			}
		});

		return permissions;
	}
}