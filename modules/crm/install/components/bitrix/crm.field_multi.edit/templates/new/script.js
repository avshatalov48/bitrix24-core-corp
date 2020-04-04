if(typeof(BX.CrmFieldMultiEditor) === "undefined")
{
	BX.CrmFieldMultiEditor = function()
	{
		this._id = "";
		this._mnemonic = "";
		this._typeName = "";
		this._referenceData = {};
		this._container = null;
	};

	BX.CrmFieldMultiEditor.prototype =
	{
		initialize: function(id, mnemonic, typeName, referenceData, container)
		{
			this._id = id;
			this._mnemonic = mnemonic;
			this._typeName = typeName;
			this._referenceData = referenceData ? referenceData : {};

			if(!container)
			{
				throw "CrmFieldMultiEditor: Container is not defined!";
			}
			this._container = container;
		},
		getId: function()
		{
			return this._id;
		},
		getItemCount: function()
		{
			var itemContainers = BX.findChildren(this._container, { tagName:"DIV", className:"bx-crm-edit-fm-item" }, false);
			return itemContainers ? itemContainers.length : 0;
		},
		createItem: function()
		{
			var itemCount = this.getItemCount();

			var select = BX.create(
				"SELECT",
				{
					props:
					{
						name: this._mnemonic + "[" + this._typeName + "]" + "[n" + (itemCount + 1) + "][VALUE_TYPE]",
						size: 1
					},
					attrs: { className:"bx-crm-edit-input bx-crm-edit-input-small" }
				}
			);

			var data = this._referenceData;
			var refValData =  typeof(data["REFERENCE_ID"]) != "undefined" ? data["REFERENCE_ID"] : [];
			var refTextData =  typeof(data["REFERENCE"]) != "undefined" ? data["REFERENCE"] : [];
			for(var i = 0; i < refValData.length; i++)
			{
				var option;
				if(!BX.browser.IsIE())
				{
					option = BX.create(
							"OPTION",
							{
								props: { value:refValData[i] },
								text : refTextData[i]
							}
					);

					select.add(option, null);
				}
				else
				{
					option = BX.create("OPTION");

					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option);
					}

					option.innerHTML = BX.util.htmlspecialchars(refTextData[i]);
					option.value = refValData[i];
				}
			}

			this._container.insertBefore(
				BX.create(
					"DIV",
					{
						attrs: { className: "bx-crm-edit-fm-item" },
						children:
							[
								BX.create(
									"INPUT",
									{
										props:
										{
											type: "text",
											name: this._mnemonic + "[" + this._typeName + "]" + "[n" + (itemCount + 1) + "][VALUE]"
										},
										attrs: { className:"bx-crm-edit-input" }
									}
								),
								select,
								BX.create(
									"DIV",
									{
										attrs: { className:"delete-action" },
										events:
										{
											click: BX.delegate(this.onItemDelete, this)
										}
									}
								)
							]
					}
				),
				BX.findChild(this._container, { className: "bx-crm-edit-fm-add" }, true, false)
			);

			BX.onCustomEvent(
				window,
				'CrmFieldMultiEditorItemCreated',
				[this, this._id]
			);
		},
		_deleteItem: function(wrapper)
		{
			if(wrapper)
			{
				var input = BX.findChild(wrapper, { tagName:"INPUT", className: "bx-crm-edit-input" }, true, false);
				if(input)
				{
					input.value = "";
				}

				BX.addClass(wrapper, "bx-crm-edit-fm-item-deleted");
				BX.removeClass(wrapper, "bx-crm-edit-fm-item");
			}

			BX.onCustomEvent(
				window,
				'CrmFieldMultiEditorItemDeleted',
				[this, this._id]
			);

			if(this.getItemCount() === 0)
			{
				this.createItem();
			}
		},
		deleteItem: function(id)
		{
			this._deleteItem(
				BX.findParent(
					BX.findChild(this._container, { tagName:"INPUT", attribute:{ name:this._prepareValueInputName(id) }  }, true, false),
					{ tagName:"DIV", className:"bx-crm-edit-fm-item" }
				)
			);
		},
		onItemDelete: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this._deleteItem(
				BX.findParent(
					e.target,
					{ tagName:"DIV", className:"bx-crm-edit-fm-item" }
				)
			);
		},
		_prepareValueInputName: function(id)
		{
			return this._mnemonic + "[" + this._typeName + "]" + "[" + id + "][VALUE]";
		}
	};

	BX.CrmFieldMultiEditor.items = {};
	BX.CrmFieldMultiEditor.create = function(id, mnemonic, typeName, referenceData, container)
	{
		var self = new BX.CrmFieldMultiEditor();
		self.initialize(id, mnemonic, typeName, referenceData, container);
		this.items[id] = self;
		return self;
	};
}
