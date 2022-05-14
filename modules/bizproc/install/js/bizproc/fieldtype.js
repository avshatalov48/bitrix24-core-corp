;if (!BX.getClass('BX.Bizproc.FieldType')) (function(BX)
{
	'use strict';
	BX.namespace('BX.Bizproc');

	var isMultiple = function(property)
	{
		return (property.Multiple === true)
	};

	var toMultipleValue = function(value)
	{
		if (!BX.type.isArray(value))
		{
			return [value];
		}
		return value;
	};

	var normalizeDateValue = function(value)
	{
		return value ? value.replace(/(\s\[-?[0-9]+\])$/, '') : '';
	};

	var getOptions = function (property)
	{
		return property.Options ? property.Options : {};
	};

	var getPlaceholder = function (property)
	{
		let placeholder = '';
		if (!BX.Type.isUndefined(property.Placeholder))
		{
			placeholder = String(property.Placeholder);
		}

		return placeholder;
	}

	var FieldType = {
		renderControl: function (documentType, property, fieldName, value, renderMode) {
			if (!renderMode || renderMode === 'public')
			{
				return this.renderControlPublic(documentType, property, fieldName, value);
			}
			if (renderMode === 'designer')
			{
				return this.renderControlDesigner(documentType, property, fieldName, value);
			}

			return BX.create('div', {text: 'incorrect render mode'});
		},

		renderControlPublic: function (documentType, property, fieldName, value)
		{
			var node,
				renderer = this.getRenderFunctionName(property),
				needInit = true;

			if (BX.type.isString(documentType))
			{
				documentType = documentType.split('@');
			}

			if (renderer)
			{
				if (isMultiple(property) && property.Type !== 'select')
				{
					var subNodes = [], me = this;

					toMultipleValue(value).forEach(function(v)
					{
						subNodes.push(
							me[renderer](property, fieldName, v)
						);
					});

					if (subNodes.length <= 0)
					{
						subNodes.push(
							me[renderer](property, fieldName)
						);
					}

					node = this.wrapMultipleControls(property, fieldName, subNodes);
				}
				else
				{
					node = BX.create('div', {
						children: [this[renderer](property, fieldName, value)]
					});
				}
			}
			else
			{
				node = BX.create('div', {text: '...'});

				BX.ajax.post(
					'/bitrix/tools/bizproc_get_field.php',
					{
						'DocumentType' : documentType,
						'Field' : {Field: fieldName, Form: 'sfa_form'},
						'Value' : (value || ''),
						'Type' : property,
						'Als' : 0,
						'rnd' : Math.random(),
						'Mode' : '',
						'Func' : '',
						'sessid' : BX.bitrix_sessid(),
						'RenderMode': 'public'
					},
					function(v) {
						if (v)
						{
							node.innerHTML = v;
							this.initControl(node, property);
						}
					}.bind(this)
				);

				if (property['Type'] === 'user')
				{
					needInit = false;
				}
			}

			if (needInit && node)
			{
				this.initControl(node, property);
			}

			return node;
		},
		renderControlDesigner: function (documentType, property, fieldName, value)
		{
			var node = BX.create('div', {text: '...'});

			BX.ajax.post(
				'/bitrix/tools/bizproc_get_field.php',
				{
					DocumentType: documentType,
					Field: {Field: fieldName, Form: 'sfa_form'},
					Value: (value || ''),
					Type: property,
					Als: 1,
					rnd: Math.random(),
					Mode: '',
					Func: '',
					sessid: BX.bitrix_sessid(),
					RenderMode: 'designer',
				},
				function (valueNode) {
					if (valueNode)
					{
						node.innerHTML = valueNode;

						if (typeof BX.Bizproc.Selector !== 'undefined')
						{
							BX.Bizproc.Selector.initSelectors(node);
						}
					}
				}
			);

			return node;
		},
		formatValuePrintable: function(property, value)
		{
			var result;
			switch (property['Type'])
			{
				case 'bool':
				case 'UF:boolean':
					result = BX.message(
						value === 'Y' ? 'BIZPROC_JS_BP_FIELD_TYPE_YES' : 'BIZPROC_JS_BP_FIELD_TYPE_NO'
					);
					break;

				case 'select':
				case 'internalselect':
					var options = property['Options'] || {};
					if (BX.type.isArray(value))
					{
						result = [];
						value.forEach(function(v)
						{
							result.push(options[v]);
						});
						result = result.join(', ');
					}
					else
					{
						result = options[value];
					}

					break;

				case 'date':
				case 'UF:date':
				case 'datetime':
					result = normalizeDateValue(value);
					break;
				case 'text':
				case 'int':
				case 'double':
				case 'string':
					result = value;
					break;
				case 'user':
					result = [];
					var i, name, pair, matches, pairs = BX.Type.isArray(value) ? value : value.split(',');

					for (i = 0; i < pairs.length; ++i)
					{
						pair = BX.util.trim(pairs[i]);
						if (matches = pair.match(/(.*)\[([A-Z]{0,2}\d+)\]/))
						{
							name =  BX.util.trim(matches[1]);
							result.push(name);
						}
						else
						{
							result.push(pair);
						}
					}
					result = result.join(', ');
					break;
				default:
					if (BX.type.isString(value))
					{
						result = value;
					}
					else
					{
						result = '(?)';
					}

					break;
			}

			return result;
		},

		/**
		 * System functions
		 */
		getRenderFunctionName: function(property)
		{
			var renderer;
			switch (property['Type'])
			{
				case 'B':
				case 'bool':
				case 'UF:boolean':
					renderer = 'createBoolNode';
					break;

				case 'date':
				case 'UF:date':
				case 'datetime':
				case 'S:Date':
				case 'S:DateTime':
					renderer = 'createDateNode';
					break;

				case 'L':
				case 'select':
				case 'internalselect':
					renderer = 'createSelectNode';
					break;

				case 'T':
				case 'text':
					renderer = 'createTextNode';
					break;

				case 'N':
				case 'int':
				case 'double':
					renderer = 'createNumericNode';
					break;

				case 'S':
				case 'string':
					renderer = 'createStringNode';
					break;

				case 'F':
				case 'file':
					renderer = 'createFileNode';
					break;
			}
			return renderer;
		},
		wrapMultipleControls: function(property, fieldName, controls)
		{
			var wrapper = BX.create('div', {children: controls});

			var btn = BX.create('a', {
				attrs: {
					className: 'bizproc-type-control-clone-btn'
				},
				text: BX.message('BIZPROC_JS_BP_FIELD_TYPE_ADD'),
				events: {
					click: function(event)
					{
						event.preventDefault();
						FieldType.cloneControl(property, fieldName, this.parentNode)
					}
				}
			});

			wrapper.appendChild(BX.create('div', {children: [btn]}));

			return wrapper;
		},
		cloneControl: function(property, name, node)
		{
			var renderer = this.getRenderFunctionName(property);

			if (renderer)
			{
				var controlNode = this[renderer](property, name);

				if (controlNode && node.parentNode)
				{
					var wrapper = BX.create('div', {children: [controlNode]});
					this.initControl(wrapper, property);
					node.parentNode.insertBefore(wrapper, node);
				}
			}
		},
		createControlOptions: function (property, callbackFunction)
		{
			var options = getOptions(property);
			var str = '';
			for (var i in options)
			{
				if (String(i) !== String(options[i]))
				{
					str += '[' + i + ']' + options[i];
				}
				else
				{
					str += options[i];
				}
				str += '\n';
			}
			var rnd = BX.util.getRandomString(3);
			var textarea = BX.create('textarea', {
				attrs: {
					id: "bizproc_fieldtype_select_form_options_" + rnd
				}
			});
			textarea.innerHTML = BX.util.htmlspecialchars(str);

			var me = this;
			var button = BX.create('button', {
				attrs: {
					type: 'button'
				},
				text: BX.Loc.getMessage('BIZPROC_JS_BP_FIELD_TYPE_SELECT_OPTIONS3'),
				events: {
					click: function () {
						callbackFunction(me.parseSelectFormOptions(rnd));
					}
				}
			});

			var wrapper = BX.create('div');
			wrapper.appendChild(textarea);
			wrapper.appendChild(BX.create('br'));
			wrapper.innerHTML += BX.Loc.getMessage('BIZPROC_JS_BP_FIELD_TYPE_SELECT_OPTIONS1');
			wrapper.appendChild(BX.create('br'));
			wrapper.innerHTML += BX.Loc.getMessage('BIZPROC_JS_BP_FIELD_TYPE_SELECT_OPTIONS2');
			wrapper.appendChild(BX.create('br'));
			wrapper.appendChild(button);

			return wrapper;
		},
		parseSelectFormOptions: function(rnd)
		{
			var result = {};
			var str = document.getElementById('bizproc_fieldtype_select_form_options_' + rnd).value;
			if (!str)
			{
				return result;
			}

			var rows = str.split(/[\r\n]+/);
			var pattern = /\[([^\]]+)].+/;

			for (var i in rows)
			{
				var row = BX.util.trim(rows[i]);
				if (row.length > 0)
				{
					var matches = row.match(pattern);
					if (matches)
					{
						var position = row.indexOf(']');
						result[matches[1]] = row.substr(position + 1);
					}
					else
					{
						result[row] = row;
					}
				}
			}

			return result;
		},
		createBoolNode: function(property, fieldName, value)
		{
			var yesLabel = BX.message('BIZPROC_JS_BP_FIELD_TYPE_YES');
			var noLabel = BX.message('BIZPROC_JS_BP_FIELD_TYPE_NO');

			yesLabel = yesLabel.charAt(0).toUpperCase() + yesLabel.slice(1);
			noLabel = noLabel.charAt(0).toUpperCase() + noLabel.slice(1);

			var node = BX.create('select', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-bool'
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : '')
				},
				props: {
					name: fieldName + (isMultiple(property) ? '[]' : '')
				},
				children: [
					BX.create('option', {
						props: {value: ''},
						text: BX.message('BIZPROC_JS_BP_FIELD_TYPE_NOT_SELECTED')
					})
				]
			});
			var optionY = BX.create('option', {
				props: {value: 'Y'},
				text: yesLabel
			});

			if (value === 'Y' || value === 1 || value === '1')
			{
				optionY.setAttribute('selected', 'selected');
			}

			var optionN = BX.create('option', {
				props: {value: 'N'},
				text: noLabel
			});

			if (value === 'N' || value === 0 || value === '0')
			{
				optionN.setAttribute('selected', 'selected');
			}

			node.appendChild(optionY);
			node.appendChild(optionN);

			return node;
		},
		createDateNode: function(property, fieldName, value)
		{
			var type = property['Type'];
			if (type === 'UF:date' || type === 'S:Date')
			{
				type = 'date';
			}
			if (type === 'S:DateTime')
			{
				type = 'datetime';
			}

			var input = BX.create('input', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-' + type
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : ''),
					'data-role': 'inline-selector-target',
					'data-selector-type': type,
					placeholder: getPlaceholder(property),
				},
				props: {
					type: 'text',
					name: fieldName + (isMultiple(property) ? '[]' : ''),
					value: normalizeDateValue(value)
				}
			});

			var dlg = BX.Bizproc.Automation && BX.Bizproc.Automation.Designer.getRobotSettingsDialog();
			if (!dlg)
			{
				var img = BX.create('img', {
					attrs: {
						src: '/bitrix/js/main/core/images/calendar-icon.gif',
						className: 'calendar-icon',
						border: '0'
					},
					events: {
						click: function(e)
						{
							e.preventDefault();
							BX.calendar({
								node: this,
								field: input,
								bTime: (type === 'datetime'),
								bHideTime: (type === 'date')
							});
						}
					}
				});

				var lc;

				if (property['Settings'] && property['Settings']['timezones'])
				{
					lc = BX.create('select', {
						props: {name: 'tz_' + (fieldName + (isMultiple(property) ? '[]' : ''))},
						attrs: {className: 'bizproc-type-control-date-lc'}
					});

					property['Settings']['timezones'].forEach(function(zone)
					{
						var option = BX.create('option', {
							props: {value: zone.value},
							text: zone.text
						});
						if (zone.value === 'current')
						{
							option.setAttribute('selected', 'selected');
						}
						lc.appendChild(option);
					});
				}

				return BX.create('div', {children: [input, img, lc]});
			}

			return input;
		},
		createNumericNode: function(property, fieldName, value)
		{
			return BX.create('input', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-int'
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : ''),
					'data-role': 'inline-selector-target',
					placeholder: getPlaceholder(property),
				},
				props: {
					type: 'text',
					name: fieldName + (isMultiple(property) ? '[]' : ''),
					value: (value || '')
				}
			});
		},
		createStringNode: function(property, fieldName, value)
		{
			return BX.create('input', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-string'
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : ''),
					'data-role': 'inline-selector-target',
					placeholder: getPlaceholder(property),
				},
				props: {
					type: 'text',
					name: fieldName + (isMultiple(property) ? '[]' : ''),
					value: (value || '')
				}
			});
		},
		createFileNode: function(property, fieldName, value)
		{
			var dlg = BX.Bizproc.Automation && BX.Bizproc.Automation.Designer.getRobotSettingsDialog();
			if (!dlg)
			{
				var input = BX.create('input', {
					props: {
						type: 'file',
						name: fieldName + (isMultiple(property) ? '[]' : ''),
						value: (value || '')
					},
					events: {
						change: function()
						{
							this.nextSibling.textContent = BX.Bizproc.FieldType.File.parseLabel(this.value);
						}
					}
				});

				var buttonWrapper = BX.create('span', {
					children: [BX.create('span', {
						attrs: {
							className: 'webform-small-button'
						},
						text: BX.message('BIZPROC_JS_BP_FIELD_TYPE_CHOOSE_FILE')
					})]
				});

				return BX.create('div', {
					children: [
						buttonWrapper,
						input,
						BX.create('span', {
							attrs: {
								className: 'bizproc-type-control-file-label'
							}
						})
					],
					attrs: {
						className: 'bizproc-type-control bizproc-type-control-file'
							+ (isMultiple(property) ? ' bizproc-type-control-multiple' : '')
					}
				});
			}

			return BX.create('input', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-file-selectable'
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : ''),
					'data-role': 'inline-selector-target',
					'data-selector-type': 'file'
				},
				props: {
					type: 'text',
					name: fieldName + (isMultiple(property) ? '[]' : ''),
					value: (value || '')
				}
			});
		},
		createTextNode: function(property, fieldName, value)
		{
			return BX.create('textarea', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-text'
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : ''),
					'data-role': 'inline-selector-target',
					rows: 5,
					cols: 40,
					placeholder: getPlaceholder(property),
				},
				props: {
					name: fieldName + (isMultiple(property) ? '[]' : ''),
					value: (value || '')
				},
			});
		},
		createSelectNode: function(property, fieldName, value)
		{
			var isEqual = function(needle, haystack)
			{
				if (!needle || !haystack)
				{
					return false;
				}

				if (BX.type.isArray(haystack))
				{
					return BX.util.in_array(needle, haystack);
				}

				return (needle.toString() === haystack.toString());
			};

			var option, node = BX.create('select', {
				attrs: {
					className: 'bizproc-type-control bizproc-type-control-select'
						+ (isMultiple(property) ? ' bizproc-type-control-multiple' : '')
				},
				props: {
					name: fieldName + (isMultiple(property) ? '[]' : '')
				},
			});

			var getDefaultOption = function()
			{
				return BX.create('option', {
					props: { value: '' },
					text: BX.message('BIZPROC_JS_BP_FIELD_TYPE_NOT_SELECTED')
				});
			}

			if (isMultiple(property))
			{
				node.setAttribute('multiple', 'multiple');
				node.setAttribute('size', '5');
			}
			option = getDefaultOption();
			if (BX.Type.isNil(value) || value.length === 0)
			{
				option.setAttribute('selected', 'selected');
			}
			node.appendChild(option);

			if (BX.type.isPlainObject(property['Options']))
			{
				for (var key in property['Options'])
				{
					if (!property['Options'].hasOwnProperty(key))
					{
						continue;
					}

					option = BX.create('option', {
						props: {value: key},
						text: BX.Text.decode(property['Options'][key])
					});

					if (isEqual(key, value))
					{
						option.setAttribute('selected', 'selected');
					}

					node.appendChild(option);
				}
			}
			else if (BX.type.isArray(property['Options']))
			{
				for (var i = 0; i < property['Options'].length; ++i)
				{
					option = BX.create('option', {
						props: {value: i},
						text: BX.Text.decode(property['Options'][i])
					});

					if (isEqual(i, value))
					{
						option.setAttribute('selected', 'selected');
					}

					node.appendChild(option);
				}
			}

			return node;
		},
		initControl: function(controlNode, property)
		{
			var dlg;
			if (dlg = BX.Bizproc.Automation && BX.Bizproc.Automation.Designer.getRobotSettingsDialog())
			{
				dlg.template.initRobotSettingsControls(dlg.robot, controlNode);
			}
			else if (dlg = BX.Bizproc.Automation && BX.Bizproc.Automation.Designer.getTriggerSettingsDialog())
			{
				dlg.component.triggerManager.initSettingsDialogControls(controlNode);
			}
			else if (property && property['Type'] === 'user' && BX.Bizproc.UserSelector)
			{
				BX.Bizproc.UserSelector.decorateNode(controlNode.querySelector('[data-role="user-selector"]'));
			}
		},
		getDocumentFields: function()
		{
			const component = BX.Bizproc.Automation && BX.Bizproc.Automation.Designer.component;
			if (component)
			{
				return component.data['DOCUMENT_FIELDS'];
			}
			if (BX.Bizproc.Automation && BX.Bizproc.Automation.API.documentFields)
			{
				return BX.Bizproc.Automation.API.documentFields;
			}

			return [];
		},
		getDocumentUserGroups: function()
		{
			if (BX.Bizproc.Automation && BX.Bizproc.Automation.API.documentUserGroups)
			{
				return BX.Bizproc.Automation.API.documentUserGroups;
			}
			return [];
		}
	};

	FieldType.File = {
		parseLabel: function(str)
		{
			var i;
			if (str.lastIndexOf('\\'))
			{
				i = str.lastIndexOf('\\')+1;
			}
			else
			{
				i = str.lastIndexOf('/')+1;
			}
			return str.slice(i);
		}
	};


	BX.Bizproc.FieldType = FieldType;
})(window.BX || window.top.BX);