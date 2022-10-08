BX.TasksImport = (function ()
{
	var TasksImport = function(parameters)
	{
		this.timerId = null;
		this.fileErrors = {};

		var _self = this;
		var step = parseInt(parameters.step);
		var formId = parameters.formId;

		this.bindElements(formId, parameters.isFramePopup, step);

		window.addEventListener('resize', function()
		{
			_self.setImportExampleTableContainerSize(formId);
		}, false);

		this.showErrors(parameters.errors);
		if (step === 3)
		{
			this.setImportErrorsContainerHeight(formId);
			this.startImport(parameters.importFileParameters);
		}
	};

	TasksImport.prototype.validateFile = function(file)
	{
		var fileExtension;
		var notSelectedErrorText = BX.message('TASKS_IMPORT_ERROR_FILE_NOT_SELECTED');
		var wrongExtensionErrorText = BX.message('TASKS_IMPORT_ERROR_FILE_WRONG_EXTENSION');

		if (!file)
		{
			if ('FILE_LABEL' in this.fileErrors && this.fileErrors['FILE_LABEL'] === notSelectedErrorText)
				return false;

			this.fileErrors = {FILE_LABEL: notSelectedErrorText};
			TasksImport.prototype.showErrors(this.fileErrors);
			return false;
		}
		else if ((fileExtension = file.name.substring(file.name.lastIndexOf('.') + 1).toLowerCase()) !== 'csv')
		{
			if ('FILE_LABEL' in this.fileErrors && this.fileErrors['FILE_LABEL'] === wrongExtensionErrorText)
				return false;

			this.fileErrors = {FILE_LABEL: wrongExtensionErrorText + ' (.' + fileExtension + ')'};
			TasksImport.prototype.showErrors(this.fileErrors);
			return false;
		}

		return true;
	};

	TasksImport.prototype.bindElements = function(formId, isFramePopup, step)
	{
		if (isFramePopup)
		{
			if (step === 3)
				TasksImport.slider.bindClose(BX('next'));
			TasksImport.slider.bindClose(BX('cancel'));
		}
		else
		{
			if (step === 3)
				BX.bind(BX('force_import_stop'), 'click', function()
				{
					BX.submit(BX(formId), 'cancel');
				});

			BX.bind(BX('cancel'), 'click', function()
			{
				BX.submit(BX(formId), 'cancel');
			});
		}

		BX.bind(BX('file'), 'change', function()
		{
			var fileName = BX.message('TASKS_IMPORT_FILE_NOT_SELECTED');
			var fileValue = BX('file').value;
			if (fileValue !== "")
			{
				var lastSlash = fileValue.lastIndexOf('\\');
				if (lastSlash !== -1)
					fileName = fileValue.substr(lastSlash + 1);
			}
			BX('file_name').value = fileName;
			BX('hidden_from_tmp_dir').value = 'N';
		});

		BX.bind(BX('back'), 'click', function()
		{
			BX.submit(BX(formId), 'back');
		});

		BX.bind(BX('stop'), 'click', function()
		{
			TasksImport.prototype.stopImport();
		});

		BX.bind(BX('force_import_stop'), 'click', function()
		{
			TasksImport.prototype.stopImport();
		});
	};

	TasksImport.prototype.setImportExampleTableContainerSize = function(formId)
	{
		var formWidth = BX(formId).offsetWidth;
		var rightColumnWidth = formWidth - 40;
		BX('tasks_import_example_table_container').style.width = rightColumnWidth + 'px';
	};

	TasksImport.prototype.setImportErrorsContainerHeight = function(formId)
	{
		var formHeight = BX(formId).offsetHeight;
		BX('error_imports_messages_container').style.maxHeight = formHeight + 'px';
	};

	TasksImport.prototype.showErrors = function(errors)
	{
		Object.keys(errors).forEach(function(upperElementName)
		{
			var elementName = upperElementName.toLowerCase();

			if (document.getElementById(elementName + '_alert'))
				BX(elementName + '_alert').remove();

			if (elementName !== 'required_fields')
				BX(elementName).className += ' tasks-field-error';

			var errorText = BX.create('div', {
				props: {
					id: elementName + '_alert',
					className: 'ui-alert ui-alert-danger tasks-alerts-text',
					innerHTML: errors[upperElementName]
				}
			});

			BX(elementName + '_container').appendChild(errorText);
		});
	};

	TasksImport.prototype.startImport = function(parameters)
	{
		BX.ajax.runComponentAction('bitrix:tasks.import', 'startImport', {
			mode: 'ajax',
			data: {
				importParameters: parameters
			}
		}).then(
			function(response)
			{
				var data = response.data;
				var importsTotalCount = data['IMPORTS_TOTAL_COUNT'];
				var successfulImports = data['SUCCESSFUL_IMPORTS'];
				var errorImports = data['ERROR_IMPORTS'];
				var interval = 0;

				if (BX('processed_count').innerHTML === "0")
				{
					BX('progress_bar').max = importsTotalCount;
					BX('imports_total_count').innerHTML = importsTotalCount;
				}

				if (successfulImports > 0 || errorImports > 0)
				{
					BX('progress_bar').value += successfulImports + errorImports;
					BX('processed_count').innerHTML = BX('progress_bar').value;
					BX('successful_imports').innerHTML = parseInt(BX('successful_imports').innerHTML) + successfulImports;
					BX('error_imports').innerHTML = parseInt(BX('error_imports').innerHTML) + errorImports;

					interval = 1000 * data['MAX_EXECUTION_TIME'] / (successfulImports + errorImports);
					TasksImport.resetTimer(interval);

					if (errorImports > 0)
					{
						TasksImport.showImportErrors(data['ERROR_IMPORTS_MESSAGES'], false);
					}

					if (!data['ALL_LINES_LOADED'])
					{
						if (BX('hidden_force_import_stop').value === 'N')
						{
							data['ERROR_IMPORTS_MESSAGES'] = [];
							TasksImport.prototype.startImport(data);
						}
						else
						{
							TasksImport.doFinalProgressActions('STOPPED');
						}
					}
					else if (parseInt(BX('progress_bar').value) === parseInt(importsTotalCount))
					{
						BX('hidden_import_done').value = 'Y';
						TasksImport.doFinalProgressActions('DONE');
					}
					else
					{
						TasksImport.doFinalProgressActions('ERROR');
					}
				}
				else
				{
					TasksImport.doFinalProgressActions('DONE');
				}
			},
			function(response)
			{
				BX('imports_total_count').innerHTML = 0;

				TasksImport.doFinalProgressActions('ERROR');
				TasksImport.showImportErrors(response.errors, true);
			}
		);
	};

	TasksImport.prototype.stopImport = function()
	{
		if (BX('hidden_import_done').value === 'Y' || BX('hidden_force_import_stop').value === 'Y')
			return;

		BX('hidden_force_import_stop').value = 'Y';
		BX('stop').className = BX('stop').className.replace('ui-btn-icon-stop', 'ui-btn-clock');
		BX('force_import_stop').className = BX('force_import_stop').className.replace('ui-btn-icon-stop', 'ui-btn-clock');
	};

	TasksImport.resetTimer = function(interval)
	{
		TasksImport.stopTimer();
		TasksImport.setTimer(interval);
	};

	TasksImport.setTimer = function(interval)
	{
		this.timerId = setInterval(TasksImport.increaseProcessedTasksCount, interval + 200);
	};

	TasksImport.stopTimer = function()
	{
		if (this.timerId)
			clearInterval(this.timerId);
	};

	TasksImport.increaseProcessedTasksCount = function()
	{
		BX('processed_count').innerHTML = parseInt(BX('processed_count').innerHTML) + 1;
	};

	TasksImport.showImportErrors = function(errors, fatal)
	{
		var className = '';
		var lineText = BX.message('TASKS_IMPORT_LINE');
		if (fatal)
		{
			className = 'tasks-import-results-error-messages-danger';
			lineText = '';
		}

		errors.forEach(function(errorMessage)
		{
			var errorElement = BX.create("div", {
				text: lineText + (fatal ? errorMessage.message : errorMessage),
				props: {
					className: className
				}
			});
			BX('error_imports_messages_container').appendChild(errorElement);
		});
	};

	TasksImport.doFinalProgressActions = function(form)
	{
		TasksImport.stopTimer();
		TasksImport.changeFooterButtons();
		TasksImport.setFinalFormOfForceImportStopButton(form);
		TasksImport.slider.bindClose(BX('force_import_stop'));
	};

	TasksImport.changeFooterButtons = function()
	{
		BX('stop').style.display = 'none';
		BX('next').style.display = 'inline-block';
		BX('back').style.display = 'inline-block';
	};

	TasksImport.setFinalFormOfForceImportStopButton = function(form)
	{
		if (form === 'DONE')
		{
			BX('force_import_stop').className = BX('force_import_stop').className.replace('ui-btn-clock', 'ui-btn-icon-info');
			if (BX('force_import_stop').className.indexOf('ui-btn-icon-info') === -1)
				BX('force_import_stop').className = BX('force_import_stop').className.replace('ui-btn-icon-stop', 'ui-btn-icon-info');
			BX('force_import_stop').className += ' ui-btn-success';
		}
		else if (form === 'STOPPED')
		{
			BX('force_import_stop').className = BX('force_import_stop').className.replace('ui-btn-clock', 'ui-btn-icon-info');
			BX('force_import_stop').className += ' ui-btn-danger';
		}
		else if (form === 'ERROR')
		{
			BX('hidden_force_import_stop').value = 'Y';
			BX('force_import_stop').className = BX('force_import_stop').className.replace('ui-btn-icon-stop', 'ui-btn-icon-info');
			BX('force_import_stop').className += ' ui-btn-danger';
		}
		BX('force_import_stop').innerHTML = BX.message('TASKS_IMPORT_' + form);
	};

	TasksImport.slider =
		{
			bindClose: function(element)
			{
				BX.bind(element, 'click', this.close);
			},
			close: function()
			{
				window.top.BX.SidePanel.Instance.close();
			}
		};

	return TasksImport;
})();

(function()
{
	'use strict';

	BX.namespace('Tasks.Component');

	if (BX.getClass('BX.Tasks.Component.TasksImport'))
		return;

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksImport = BX.Tasks.Component.extend({
		sys: {
			code: 'import'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
			},

			bindEvents: function()
			{

			}

			// add more methods, then call them like this.methodName()
		}
	});

	// may be some sub-controllers here...

}).call(this);