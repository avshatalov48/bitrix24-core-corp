;(function () {
	
"use strict";

var namespace = BX.namespace('BX.Intranet.UserProfile.Password');
if (namespace.EntityEditor)
{
	return;
}
namespace.EntityEditor = function(params) {
	this.init(params);
};

namespace.EntityEditor.prototype =
{
	init: function(params)
	{
		BX.addCustomEvent("BX.UI.EntityEditorControlFactory:onInitialize", BX.proxy(function (params, eventArgs) {
			eventArgs.methods["password"] = this.userProfilePasswordEntity;
		}, this));
	},

	userProfilePasswordEntity: function (type, controlId, settings)
	{
		if (type === "password")
		{
			return BX.Intranet.UserProfile.Password.EntityEditorPassword.create(controlId, settings);
		}

		return null;
	}
};


BX.load(['/bitrix/js/ui/entity-editor/js/editor.js'], function ()
{
	if(typeof BX.Intranet.UserProfile.Password.EntityEditorPassword === "undefined")
	{
		BX.Intranet.UserProfile.Password.EntityEditorPassword = function()
		{
			BX.Intranet.UserProfile.Password.EntityEditorPassword.superclass.constructor.apply(this);
			this._input = null;
			this._innerWrapper = null;
		};

		BX.extend(BX.Intranet.UserProfile.Password.EntityEditorPassword, BX.UI.EntityEditorField);
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.getModeSwitchType = function(mode)
		{
			var result = BX.UI.EntityEditorModeSwitchType.common;
			if(mode === BX.UI.EntityEditorMode.edit)
			{
				result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
			}
			return result;
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.getContentWrapper = function()
		{
			return this._innerWrapper;
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.focus = function()
		{
			if(!this._input)
			{
				return;
			}

			BX.focus(this._input);
			BX.UI.EditorTextHelper.getCurrent().setPositionAtEnd(this._input);
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.getLineCount = function()
		{
			return this._schemeElement.getDataIntegerParam("lineCount", 1);
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.layout = function(options)
		{
			if(this._hasLayout)
			{
				return;
			}

			this.ensureWrapperCreated({ classNames: [ "ui-entity-editor-content-block-field-text" ] });
			this.adjustWrapper();

			if(!this.isNeedToDisplay())
			{
				this.registerLayout(options);
				this._hasLayout = true;
				return;
			}

			var name = this.getName();
			var title = this.getTitle();

			this._input = null;
			this._inputContainer = null;
			this._innerWrapper = null;

			if(this.isDragEnabled())
			{
				this._wrapper.appendChild(this.createDragButton());
			}

			if(this._mode === BX.UI.EntityEditorMode.edit)
			{
				this._wrapper.appendChild(this.createTitleNode(title));

				this._inputContainer = BX.create("div",
					{
						attrs: { className: "ui-ctl ui-ctl-textbox ui-ctl-w100" }
					}
				);

				this._input = BX.create("input",
					{
						attrs:
							{
								name: name,
								className: "ui-ctl-element",
								type: "password",
								value: "",
								id: this._id.toLowerCase() + "_text"
							}
					}
				);

				this._inputContainer.appendChild(this._input);


				if(this.isNewEntity())
				{
					var placeholder = this.getCreationPlaceholder();
					if(placeholder !== "")
					{
						this._input.setAttribute("placeholder", placeholder);
					}
				}

				BX.bind(this._input, "input", this._changeHandler);

				this._descContainer = BX.create("div",
					{
						attrs: { className: "ui-entity-editor-field-description" },
						text: this.getDescription()
					}
				);

				this._innerWrapper = BX.create("div",
					{
						props: { className: "ui-entity-editor-content-block" },
						children: [ this._inputContainer, this._descContainer ]
					});
			}
			else// if(this._mode === BX.UI.EntityEditorMode.view)
			{
				this._wrapper.appendChild(this.createTitleNode(title));

				this._innerWrapper = BX.create("div",
					{
						props: { className: "ui-entity-editor-content-block" },
						children:
							[
								BX.create("div",
									{
										props: { className: "ui-entity-editor-content-block-text" },
										html: "******"
									})
							]
					});
			}

			this._wrapper.appendChild(this._innerWrapper);

			if(this.isContextMenuEnabled())
			{
				this._wrapper.appendChild(this.createContextMenuButton());
			}

			if(this.isDragEnabled())
			{
				this.initializeDragDropAbilities();
			}

			this.registerLayout(options);
			this._hasLayout = true;
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.doClearLayout = function(options)
		{
			this._input = null;
			//BX.unbind(this._innerWrapper, "click", this._viewClickHandler);
			this._innerWrapper = null;
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.refreshLayout = function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(!this._isValidLayout)
			{
				BX.Intranet.UserProfile.Password.EntityEditorPassword.superclass.refreshLayout.apply(this, arguments);
				return;
			}

			if(this._mode === BX.UI.EntityEditorMode.edit && this._input)
			{
				this._input.value = this.getValue();
			}
			else if(this._mode === BX.UI.EntityEditorMode.view && this._innerWrapper)
			{
				this._innerWrapper.innerHTML = BX.util.htmlspecialchars(this.getValue());
			}
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.getRuntimeValue = function()
		{
			return (this._mode === BX.UI.EntityEditorMode.edit && this._input
					? BX.util.trim(this._input.value) : ""
			);
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.validate = function(result)
		{
			if(!(this._mode === BX.UI.EntityEditorMode.edit && this._input))
			{
				throw "BX.Intranet.UserProfile.Password.EntityEditorPassword. Invalid validation context";
			}

			this.clearError();

			if(this.hasValidators())
			{
				return this.executeValidators(result);
			}

			var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
			if(!isValid)
			{
				result.addError(BX.UI.EntityValidationError.create({ field: this }));
				this.showRequiredFieldError(this._input);
			}
			return isValid;
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.showError =  function(error, anchor)
		{
			BX.Intranet.UserProfile.Password.EntityEditorPassword.superclass.showError.apply(this, arguments);
			if(this._input)
			{
				BX.addClass(this._input, "ui-entity-card-content-error");
			}
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.clearError =  function()
		{
			BX.Intranet.UserProfile.Password.EntityEditorPassword.superclass.clearError.apply(this);
			if(this._input)
			{
				BX.removeClass(this._input, "ui-entity-card-content-error");
			}
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.save = function()
		{
			if(this._input)
			{
				this._model.setField(this.getName(), this._input.value, { originator: this });
			}
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.processModelChange = function(params)
		{
			if(BX.prop.get(params, "originator", null) === this)
			{
				return;
			}

			if(!BX.prop.getBoolean(params, "forAll", false)
				&& BX.prop.getString(params, "name", "") !== this.getName()
			)
			{
				return;
			}

			this.refreshLayout();
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.getFocusInputID = function()
		{
			return this._id.toLowerCase() + "_text";
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.prototype.getDescription = function()
		{
			var data = this.getData();
			var desc = BX.prop.getString(data, "desc", "");
			return desc;
		};
		BX.Intranet.UserProfile.Password.EntityEditorPassword.create = function(id, settings)
		{
			var self = new BX.Intranet.UserProfile.Password.EntityEditorPassword();
			self.initialize(id, settings);
			return self;
		};
	}
});

})();