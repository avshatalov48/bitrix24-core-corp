import {Dom} from "main.core";
import Manager from "./manager";

/** @memberof BX.Crm.Timeline */
export default class Editor
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._manager = null;

		this._ownerTypeId = 0;
		this._ownerId = 0;

		this._container = null;
		this._input = null;
		this._saveButton = null;
		this._cancelButton = null;
		this._ghostInput = null;

		this._saveButtonHandler = BX.delegate(this.onSaveButtonClick, this);
		this._cancelButtonHandler = BX.delegate(this.onCancelButtonClick, this);
		this._focusHandler = BX.delegate(this.onFocus, this);
		this._blurHandler = BX.delegate(this.onBlur, this);
		this._keyupHandler = BX.delegate(this.resizeForm, this);
		this._delayedKeyupHandler = BX.delegate(
			function()
			{
				setTimeout(this.resizeForm.bind(this), 0);
			},
			this
		);

		this._isVisible = true;
		this._hideButtonsOnBlur = true;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._manager = this.getSetting("manager");
		if(!(this._manager instanceof Manager))
		{
			throw "Editor. Manager instance is not found.";
		}

		this._ownerTypeId = this.getSetting("ownerTypeId", 0);
		this._ownerId = this.getSetting("ownerId", 0);

		this._container = BX(this.getSetting("container"));
		this._input = BX(this.getSetting("input"));
		this._saveButton = BX(this.getSetting("button"));
		this._cancelButton = BX(this.getSetting("cancelButton"));

		BX.bind(this._saveButton, "click", this._saveButtonHandler);
		if(this._cancelButton)
		{
			BX.bind(this._cancelButton, "click", this._cancelButtonHandler);
		}

		this.bindInputHandlers();
		this.doInitialize();
	}

	doInitialize()
	{
	}

	bindInputHandlers()
	{
		BX.bind(this._input, "focus", this._focusHandler);
		BX.bind(this._input, "blur", this._blurHandler);
		BX.bind(this._input, "keyup", this._keyupHandler);
		BX.bind(this._input, "cut", this._delayedKeyupHandler);
		BX.bind(this._input, "paste", this._delayedKeyupHandler);
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	setVisible(visible)
	{
		visible = !!visible;
		if(this._isVisible === visible)
		{
			return;
		}

		this._isVisible = visible;
		if (this._container)
		{
			this._container.style.display = visible ? "" : "none";
		}
	}

	isVisible()
	{
		return this._isVisible;
	}

	onFocus(e)
	{
		BX.addClass(this._container, "focus");
	}

	onBlur(e)
	{
		if(!this._hideButtonsOnBlur)
		{
			return;
		}

		if(this._input.value === "")
		{
			window.setTimeout(
				BX.delegate(function() {
					BX.removeClass(this._container, "focus");
					this._input.style.minHeight = "";
				}, this),
				200
			);
		}
	}

	onSaveButtonClick(e)
	{
		Dom.addClass(this._saveButton, 'ui-btn-wait');
		const removeButtonWaitClass = () => Dom.removeClass(this._saveButton, 'ui-btn-wait');

		const saveResult = this.save();
		if (saveResult instanceof BX.Promise || saveResult instanceof Promise)
		{
			saveResult.then(
				() => removeButtonWaitClass(),
				() => removeButtonWaitClass()
			);
		}
		else
		{
			removeButtonWaitClass();
		}
	}

	onCancelButtonClick()
	{
		this.cancel();
		this._manager.processEditingCancellation(this);
	}

	save()
	{
	}

	cancel()
	{
	}

	release()
	{
		if(this._ghostInput)
		{
			this._ghostInput = BX.remove(this._ghostInput);
		}
	}

	ensureGhostCreated()
	{
		if(this._ghostInput)
		{
			return this._ghostInput;
		}

		this._ghostInput = BX.create('div', {
			props: { className: 'crm-entity-stream-content-new-comment-textarea-shadow' },
			text: this._input.value
		});

		this._ghostInput.style.width = this._input.offsetWidth + 'px';
		document.body.appendChild(this._ghostInput);
		return this._ghostInput;
	}

	resizeForm()
	{
		const ghost = this.ensureGhostCreated();
		const computedStyle = getComputedStyle(this._input);
		const diff = parseInt(computedStyle.paddingBottom) +
			parseInt(computedStyle.paddingTop) +
			parseInt(computedStyle.borderTopWidth) +
			parseInt(computedStyle.borderBottomWidth) || 0;

		ghost.innerHTML = BX.util.htmlspecialchars(this._input.value.replace(/[\r\n]{1}/g, '<br>'));
		this._input.style.minHeight = ghost.scrollHeight + diff + 'px'
	}
}
