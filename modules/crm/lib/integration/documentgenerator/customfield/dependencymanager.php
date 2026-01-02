<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField;

use Bitrix\Main\Web\Json;

final class DependencyManager
{
	private array $dependencies = [];

	public function addDependency(string $fieldId, array $dependency): void
	{
		if (!isset($this->dependencies[$fieldId]))
		{
			$this->dependencies[$fieldId] = [];
		}

		$this->dependencies[$fieldId][] = $dependency;
	}

	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	public function generateJavaScript(): string
	{
		if (empty($this->getDependencies()))
		{
			return '';
		}

		$dependencies = Json::encode($this->dependencies);

		return <<<JS
			BX.ready(function() 
			{
				const customFieldsDependencies = 
				{
					dependencies: JSON.parse('$dependencies'),

					init: function() 
					{
						this.bindEvents();
						this.checkAllDependencies();
					},

					bindEvents: function() 
					{
						let self = this;
						for (let fieldId in this.dependencies)
						{
							let deps = this.dependencies[fieldId];
							
							for (let i = 0; i < deps.length; i++)
							{
								this.bindDependencyEvent(fieldId, deps[i]);
							}
						}
					},

					bindDependencyEvent: function(targetFieldId, dependency) 
					{
						let self = this;

						let sourceField = BX(dependency.field);
						if (sourceField)
						{
							BX.bind(sourceField, 'change', function()
							{
								self.checkFieldDependencies(targetFieldId);
							});
						}
					},

					checkAllDependencies: function()
					{
						for (let fieldId in this.dependencies)
						{
							this.checkFieldDependencies(fieldId);
						}
					},

					checkFieldDependencies: function(fieldId)
					{
						let deps = this.dependencies[fieldId];
						let showField = true;
						
						for (let i = 0; i < deps.length; i++)
						{
							if (!this.evaluateDependency(deps[i]))
							{
								showField = false;
								break;
							}
						}

						this.toggleField(fieldId, showField);
					},

					evaluateDependency: function(dependency)
					{
						let sourceField = BX(dependency.field);
						if (!sourceField)
						{
							return false;
						}

						let value = this.getFieldValue(sourceField);

						switch (dependency.condition)
						{
							case 'equals':
								return value == dependency.value;
							case 'not_equals':
								return value != dependency.value;
							case 'contains':
								return value.indexOf(dependency.value) !== -1;
							case 'not_empty':
								return value !== '';
							case 'empty':
								return value === '';
							case 'in':
								return dependency.value.indexOf(value) !== -1;
							case 'not_in':
								return dependency.value.indexOf(value) === -1;
							default:
								return true;
						}
					},

					getFieldValue: function(field)
					{
						if (field.type === 'radio') 
						{
							let radioGroup = document.getElementsByName(field.name);

							for (let i = 0; i < radioGroup.length; i++)
							{
								if (radioGroup[i].checked)
								{
									return radioGroup[i].value;
								}
							}

							return '';
						}

						return field.value || '';
					},
					
					toggleField: function(fieldId, show) 
					{
						let fieldContainer = BX('custom-field-' + fieldId);
						if (fieldContainer)
						{
							fieldContainer.style.display = show ? 'block' : 'none';
						}
					}
				};

			customFieldsDependencies.init();
		});
JS;
	}
}
