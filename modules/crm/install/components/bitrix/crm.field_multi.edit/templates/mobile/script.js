if(typeof(BX.CrmFieldMultiEditor) === "undefined")
{
	BX.CrmFieldMultiEditor = function()
	{
		this.id = "";
		this.mnemonic = "";
		this.typeName = "";
		this.defaultTypeValue = "";
		this.referenceNames = {};
		this.referenceSelectorNames = {};
		this.container = null;
	};

	BX.CrmFieldMultiEditor.prototype =
	{
		initialize: function(id, mnemonic, typeName, container, referenceNames, defaultTypeValue)
		{
			this.id = id ? id : "";
			this.mnemonic = mnemonic ? mnemonic : ""; 
			this.typeName = typeName ? typeName : "";
			this.referenceNames = referenceNames ? referenceNames : {};
			this.referenceSelectorNames = referenceNames ? BX.util.array_keys(referenceNames) : {};
			this.defaultTypeValue = defaultTypeValue ? defaultTypeValue : "";

			if(!container)
			{
				throw "CrmFieldMultiEditor: Container is not defined!";
			}
			this.container = container;
		},
		getId: function()
		{
			return this.id;
		},
		getItemCount: function()
		{
			var itemContainers = this.container.querySelectorAll("[data-role='bx-crm-edit-fm-item']");
			return itemContainers ? itemContainers.length : 0;
		},
		createItem: function()
		{
			var itemCount = this.getItemCount();

			var typeInputName = this.mnemonic + "[" + this.typeName + "]" + "[n" + (itemCount + 1) + "][VALUE_TYPE]";
			this.container.insertBefore(
				BX.create(
					"DIV",
					{
						attrs: { className: "mobile-grid-field-contact-info", "data-role": "bx-crm-edit-fm-item" },
						children:
							[
								BX.create("DIV", {
										attrs: { className:"mobile-grid-field-select" },
										children: [
											BX.create("INPUT", {
												props:
												{
													type: "hidden",
													name: typeInputName,
													id: typeInputName,
													value: this.defaultTypeValue
												}
											}),
											BX.create("a", {
												attrs: { href: "javascript:void(0)", className: "mobile-grid-field-contact-info-title"},
												html: this.referenceSelectorNames[0],
												events: {
													"click" : BX.proxy(
														function()
														{
															this.self.showTypeSelector(this.itemId);
														}, {self: this, itemId: typeInputName})
												}
											})
										]
								}),
								BX.create("DIV", {
									attrs: { className:"mobile-grid-field-text", style: "padding-right: 50px" },
									children: [
										BX.create("INPUT", {
											props:
											{
												type: "text",
												name: this.mnemonic + "[" + this.typeName + "]" + "[n" + (itemCount + 1) + "][VALUE]"
											}
										})
									]
								}),
								BX.create("del", {
										events:
										{
											click: BX.delegate(this.onItemDelete, this)
										}
								})
							]
					}
				),
				BX.findChild(this.container, { className: "mobile-grid-button" }, true, false).parentNode
			);

			BX.onCustomEvent(
				window,
				'CrmFieldMultiEditorItemCreated',
				[this, this.id]
			);
		},
		deleteItem: function(wrapper)
		{
			var input = wrapper.querySelector("[data-role='entity-input-value']");
			if(input)
			{
				input.value = "";
			}

			if (wrapper)
			{
				wrapper.style.display = "none";
			}
		},
		onItemDelete: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this.deleteItem(e.target.parentNode);
		},
		showTypeSelector: function(inputId)
		{
			if (!BX(inputId))
				return;

			BXMobileApp.UI.SelectPicker.show({
				callback: BX.proxy(function(data)
				{
					for(var i in this.referenceNames)
					{
						if (i == data.values[0])
						{
							BX(inputId).nextSibling.innerHTML = data.values[0];
							BX(inputId).value = this.referenceNames[i];
							break;
						}
					}
				}, this),
				values: this.referenceSelectorNames,
				default_value: this.referenceSelectorNames[0]
			});
		}
	};

	BX.CrmFieldMultiEditor.items = {};
	BX.CrmFieldMultiEditor.create = function(id, mnemonic, typeName, container, referenceNames, defaultTypeValue)
	{
		var self = new BX.CrmFieldMultiEditor();
		self.initialize(id, mnemonic, typeName, container, referenceNames, defaultTypeValue);
		this.items[id] = self;
		return self;
	};
}
