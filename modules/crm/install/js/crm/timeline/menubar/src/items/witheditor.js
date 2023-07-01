import {Dom} from "main.core";
import Item from "../item";

/** @memberof BX.Crm.Timeline.MenuBar */

export default class WithEditor extends Item
{
	initializeLayout(): void
	{
		this._ownerTypeId = this.getEntityTypeId();
		this._ownerId = this.getEntityId();

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

		this._hideButtonsOnBlur = true;

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

	onFocus(e)
	{
		this.setFocused(true);
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
					this.setFocused(false);
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
		this.emitFinishEditEvent();
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
