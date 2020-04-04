BX.namespace("BX.Mobile.Crm.ConvertSync");

BX.Mobile.Crm.ConvertSync = {
	init : function(data, captions)
	{
		if (data.entityType)
		{
			var legendNode = BX('bx-crm-convert-sync-block').querySelector('[data-role="crm-convert-sync-legend"]');
			if (legendNode)
			{
				legendNode.innerHTML = BX.message("M_CRM_CONVERT_SYNC_LEGEND_" + data.entityType.toUpperCase());
			}
		}

		if (data.fields)
		{
			var fieldsCount = data.fields.length;
			var curCounter = fieldsCount > 5 ? 5 : fieldsCount;

			for (var i = 0; i < curCounter; i++)
			{
				BX("bx-convert-crm-fields").appendChild(
					BX.create("div", {
						attrs: {
							className: "mobile-crm-convert-field-item"
						},
						html: data.fields[i]
					})
				);
			}

			if (fieldsCount > 5)
			{
				var moreFieldsNode = BX.create("div", {
					attrs: {
						style: 'display:none',
						id: "bx-crm-convert-sync-more-block"
					}
				});

				for (i = 5; i < fieldsCount; i++)
				{
					moreFieldsNode.appendChild(
						BX.create("div", {
							attrs: {
								className: "mobile-crm-convert-field-item"
							},
							html: data.fields[i]
						})
					);
				}

				BX("bx-convert-crm-fields").appendChild(moreFieldsNode);
				BX("bx-convert-crm-fields").appendChild(BX.create("a", {
					attrs: {className: "mobile-crm-convert-field-item"},
					html: BX.message("M_CRM_CONVERT_SYNC_MORE") + " " + (fieldsCount - 5),
					events: {
						click: function(){
							BX.toggle(BX('bx-crm-convert-sync-more-block'));
							this.style.display = "none";
						}
					}
				}));
			}
		}

		var entityList = BX('bx-crm-convert-sync-block').querySelector('[data-role="crm-convert-sync-entities"]');
		for(var entityTypeName in data.config)
		{
			if(!data.config.hasOwnProperty(entityTypeName))
			{
				continue;
			}

			var entityConfig = data.config[entityTypeName];
			var enableSync = BX.type.isNotEmptyString(entityConfig["enableSync"]) && entityConfig["enableSync"] === "Y";
			if(!enableSync)
			{
				continue;
			}

			var inputId = entityTypeName;
			var checkbox = BX.create("input", { props: { id: inputId, type: "checkbox", checked: true } });

			var label = BX.create("label",
				{
					props: { htmlFor: inputId },
					html: captions[entityTypeName.toUpperCase()],
					attrs: { className: "mobile-crm-convert-field-item-select mobile-crm-convert-field-item-selected" },
					events: {
						click: function () {
							BX.toggleClass(this, "mobile-crm-convert-field-item-selected");
						}
					}
				}
			);

			entityList.appendChild(checkbox);
			entityList.appendChild(label);
		}

		var config = data.config;

		BX.bind(BX('bx-convert-crm-button'), 'click', BX.proxy(function() {
			var checkboxes = entityList.querySelectorAll('input');
			if (checkboxes)
			{
				for (var i=0; i<checkboxes.length; i++)
				{
					this.config[checkboxes[i].id].enableSync = checkboxes[i].checked ? "Y" : "N";
				}

				BXMobileApp.onCustomEvent("onCrmConvertSynchSettings", {config: this.config, convertId: this.convertId}, true);
				BXMobileApp.UI.Page.close();
			}
		}, {config: data.config, convertId: data.convertId}));
	}
};