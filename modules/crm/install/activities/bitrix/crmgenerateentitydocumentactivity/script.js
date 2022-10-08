;(function(){
	"use strict";

	BX.namespace('BX.Crm.Activity');

	if(typeof BX.Crm.Activity.CrmGenerateEntityDocumentActivity !== "undefined")
	{
		return;
	}

	BX.Crm.Activity.CrmGenerateEntityDocumentActivity = {};

	BX.Crm.Activity.CrmGenerateEntityDocumentActivity.init = function(params)
	{
		this.documentType = params.documentType;
		this.entityType = params.entityType;
		this.entityTypeId = params.entityTypeId;
		this.selectTemplateNodeId = params.selectTemplateNodeId;
		this.selectMyCompanyNodeId = params.selectMyCompanyNodeId;
		this.selectMyCompanyRequisiteNodeId = params.selectMyCompanyRequisiteNodeId;
		this.selectMyCompanyBankDetailNodeId = params.selectMyCompanyBankDetailNodeId;
		this.selectFieldNodeId = params.selectFieldNodeId;
		this.textFieldNodeId = params.textFieldNodeId;
		this.deleteRowClassName = params.deleteRowClassName;
		this.openFieldInfoUrlClassName = params.openFieldInfoUrlClassName;
		this.addNewFieldButtonNodeId = params.addNewFieldButtonNodeId;
		this.fieldTableRowClassName = params.fieldTableRowClassName;
		this.fieldTableRowTagName = params.fieldTableRowTagName || 'tr';
		this.isRobot = params.isRobot === true;
		this.requisitesMap = params.requisitesMap;

		this.initEvents();
	};

	BX.Crm.Activity.CrmGenerateEntityDocumentActivity.initEvents = function()
	{
		BX.bind(BX(this.selectTemplateNodeId), 'change', BX.proxy(this.getTemplateFields, this));
		BX.bind(BX(this.selectFieldNodeId), 'change', BX.proxy(function()
		{
			if(BX(this.selectFieldNodeId).value.length > 0)
			{
				BX(this.textFieldNodeId).value = BX(this.selectFieldNodeId).value;
			}
		}, this));

		BX.bindDelegate(document, 'click', {className: this.deleteRowClassName}, function(event)
		{
			event.preventDefault();
			BX.remove(event.target.parentNode.parentNode);
		});

		BX.bindDelegate(document, 'click', {className: this.openFieldInfoUrlClassName}, function(event)
		{
			event.preventDefault();
			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(event.target.getAttribute('href'), {width: 845, cacheable: true});
			}
			else
			{
				location.href = event.target.getAttribute('href');
			}
		});

		BX.bind(BX(this.addNewFieldButtonNodeId), 'click', BX.proxy(this.addNewField, this));
		BX.bind(BX(this.selectMyCompanyNodeId), 'change', function(event) {
			event.preventDefault();
			var myCompanyId = parseInt(BX(this.selectMyCompanyNodeId).value);
			var requisites = this.requisitesMap.myCompanyRequisites[myCompanyId];
			this.adjustSelect(BX(this.selectMyCompanyRequisiteNodeId), requisites);
			this.adjustSelect(BX(this.selectMyCompanyBankDetailNodeId));
		}.bind(this));

		BX.bind(BX(this.selectMyCompanyRequisiteNodeId), 'change', function(event) {
			event.preventDefault();
			var requisiteId = parseInt(BX(this.selectMyCompanyRequisiteNodeId).value);
			var bankDetails = this.requisitesMap.myCompanyBankDetails[requisiteId];
			this.adjustSelect(BX(this.selectMyCompanyBankDetailNodeId), bankDetails);
		}.bind(this));
	};

	BX.Crm.Activity.CrmGenerateEntityDocumentActivity.adjustSelect = function(selectNodeId, variants)
	{
		var selectNode = BX(selectNodeId);
		if(!selectNode)
		{
			return;
		}
		var options = [selectNode.querySelector('option')];
		if(variants)
		{
			for(var variantId in variants)
			{
				if(variants.hasOwnProperty(variantId))
				{
					options.push(BX.Dom.create('option', {
						attrs: {
							value: parseInt(variantId),
						},
						text: variants[variantId]
					}));
				}
			}
		}
		BX.clean(selectNode);
		options.forEach(function(node) {
			selectNode.appendChild(node);
		}.bind(this));
	};

	BX.Crm.Activity.CrmGenerateEntityDocumentActivity.addNewField = function(event)
	{
		event.preventDefault();
		if(BX(this.textFieldNodeId).value.length <= 0)
		{
			return;
		}
		var name = BX(this.textFieldNodeId).value;
		var fieldRows = BX.findChildrenByClassName(document, this.fieldTableRowClassName, true);
		if(fieldRows.length > 1)
		{
			for(var i = 1; i < fieldRows.length; i++)
			{
				if(fieldRows[i].children && fieldRows[i].children[0] && fieldRows[i].children[0].dataset['placeholder'] === name)
				{
					return;
				}
			}
		}

		BX.ajax.post(
			'/bitrix/tools/bizproc_activity_ajax.php',
			{
				'site_id': BX.message('SITE_ID'),
				'sessid' : BX.bitrix_sessid(),
				'document_type' : this.documentType,
				'activity': 'CrmGenerateEntityDocumentActivity',
				'entity_type': this.entityType,
				'content_type': 'html',
				'customer_action' : 'getValuePropertyDialog',
				'placeholder': BX('add_new_field_text').value,
				'templateId': BX('id_template_id').value,
				'isRobot': this.isRobot === true ? 'y' : 'n',
		},
			BX.proxy(function(response)
			{
				if(response)
				{
					var fieldRows = BX.findChildrenByClassName(document, this.fieldTableRowClassName, true);
					var fieldToInsert = fieldRows[fieldRows.length - 1] || BX('add_new_field_tr');
					var newNode = BX.create(this.fieldTableRowTagName, {
						attrs: {
							className: this.fieldTableRowClassName
						},
						html: response
					});
					BX.insertAfter(newNode, fieldToInsert);

					if(BX.getClass('BX.Bizproc.Automation.Designer'))
					{
						var dlg = BX.Bizproc.Automation.Designer.getInstance().getRobotSettingsDialog();
						if (dlg)
						{
							dlg.template.initRobotSettingsControls(dlg.robot, newNode);
						}
					}
				}
			}, this)
		);
	};

	BX.Crm.Activity.CrmGenerateEntityDocumentActivity.getTemplateFields = function()
	{
		var templateId = BX(this.selectTemplateNodeId).value;
		if(templateId > 0 && this.entityTypeId > 0)
		{
			BX.ajax.runAction('crm.documentgenerator.template.getFields', {
				data: {
					id: templateId,
					entityTypeId: this.entityTypeId,
				}
			}).then(BX.proxy(function(response)
			{
				BX.cleanNode(BX(this.selectFieldNodeId));
				var options = [
					BX.create('option', {
						attrs: {
							value: ''
						},
						text: ''
					})
				];
				for(var i in response.data.templateFields)
				{
					if(response.data.templateFields.hasOwnProperty(i))
					{
						if(response.data.templateFields[i]['type'] && (response.data.templateFields[i]['type'] == 'IMAGE' || response.data.templateFields[i]['type'] == 'STAMP'))
						{
							continue;
						}
						options.push(BX.create('option', {
							attrs: {
								value: i
							},
							text: i + (response.data.templateFields[i].title ? ' (' + response.data.templateFields[i].title + ')' : '')
						}));
					}
				}
				var selectNode = BX(this.selectFieldNodeId);
				BX.adjust(selectNode, {
					children: options
				});
			}, this)).then(function(response)
			{
				alert(response.errors.pop().message);
			});
		}
	};

})(window);