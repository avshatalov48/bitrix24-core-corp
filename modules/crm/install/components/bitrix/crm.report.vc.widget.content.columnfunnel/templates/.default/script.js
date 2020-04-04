;(function(){
	BX.namespace('BX.Crm.Report');
	BX.Crm.Report.ColumnFunnel =  function(options)
	{
		this.columnFunnelWrapperContainer = options.context.querySelector('.crm-report-column-funnel-wrapper');
		this.scaleBoxContainer = options.context.querySelector('.crm-report-column-funnel-scale-box');
		this.infoPopupContentTemplate = options.context.querySelector('[data-role="info-popup-content-template"]');
		this.firstColumnsContainer = this.columnFunnelWrapperContainer.querySelector('[data-role="first-columns-container"]');
		this.secondColumnsContainer = this.columnFunnelWrapperContainer.querySelector('[data-role="second-columns-container"]');
		this.columnsValues = [];
		this.maxColumnValue = null;
		this.minColumnValue = null;
		this.informationPopup = null;
		this.data = options.data;
		this.columns = [];
		this.init();
	};

	BX.Crm.Report.ColumnFunnel.prototype = {
		init: function ()
		{
			for (var i = 0; i < this.data.length; i++)
			{
				var entity = this.data[i];
				if (entity.columns === undefined)
				{
					continue;
				}

				for (var j = 0; j < entity.columns.length; j++)
				{
					var column = entity.columns[j];
					var columnNode = this.buildColumn(column);

					if (i === 0)
					{
						this.firstColumnsContainer.appendChild(columnNode);
					}

					if (i === 1)
					{
						this.secondColumnsContainer.appendChild(columnNode);
					}
					column.node = columnNode;


					BX.bind(columnNode, 'mouseover', this.handleColumnMouseOver.bind(this, column));
					BX.bind(columnNode, 'mouseout', this.handleColumnMouseOut.bind(this));
					this.columns.push(column);
				}
			}

			this.renderScaleItems();

			setTimeout(function() {
				this.adjustColumnsHeight();
			}.bind(this), 500);

		},
		renderScaleItems: function()
		{
			var maxValue = this.getColumnMaxValue();
			var minValue = this.getColumnMinValue();

			var scaleItemValues = [];
			scaleItemValues.push(0);
			if (maxValue !== minValue)
			{
				var scaleItemSeparator = 1;

				if (maxValue < scaleItemSeparator)
				{
					scaleItemValues.push(maxValue);
				}
				else
				{
					var scaleItemCount = Math.round(maxValue / scaleItemSeparator);


					if (scaleItemCount === 1)
					{
						scaleItemValues.push(maxValue);
					}
					else
					{
						if (scaleItemCount > 7)
						{
							scaleItemSeparator = Math.round(maxValue / 7);
							scaleItemCount = Math.round(maxValue / scaleItemSeparator);
						}

						var value = 0;
						for (var i = 1; i <= scaleItemCount; i++)
						{
							value = i * scaleItemSeparator;

							if (value >= maxValue)
							{
								continue;
							}
							scaleItemValues.push(value);
						}
						scaleItemValues.push(maxValue);
					}
				}
			}
			else
			{

				if (maxValue > 0)
				{
					scaleItemValues.push(maxValue);
				}
				else
				{
					this.scaleBoxContainer.classList.add('crm-report-column-funnel-scale-single-value');
				}
			}

			scaleItemValues.reverse();
			scaleItemValues.forEach(function (value)
			{
				this.scaleBoxContainer.appendChild(BX.create('div', {
					html: value,
					attrs: {
						className:'crm-report-column-funnel-scale-item'
					}
				}))
			}.bind(this));

		},
		buildColumn: function(column)
		{
			return BX.create('div', {
				props: {
					className: 'crm-report-column-funnel-through-funnel-widget-item'
				},
				style: {
					backgroundColor: column.color
				}

			});
		},
		adjustColumnsHeight: function()
		{
			for (var i = 0; i < this.columns.length; i++)
			{
				var currentColumnNode = this.columns[i].node;
				var currentColumnValue = this.columns[i].value;
				var calculatedPercentValue = this.calculatePercentValue(currentColumnValue);
				currentColumnNode.style.height = (calculatedPercentValue + 1) + '%';
			}
		},
		calculatePercentValue: function(value)
		{
			var maxValue = this.getColumnMaxValue();
			var currentValue = value;
			var percentValue = 0;
			if (maxValue > 0)
			{
				percentValue = (currentValue * 100) / maxValue;
			}

			return percentValue;
		},
		handleColumnMouseOver: function(columnObject)
		{
			this.openInformationPopup(columnObject)
		},
		handleColumnMouseOut: function()
		{
			this.informationPopup.close();
		},
		openInformationPopup: function (columnObject)
		{
			if (this.informationPopup !== null)
			{
				this.informationPopup.destroy();
			}
			this.informationPopup = new BX.PopupWindow('widget-column-funnel-information-popup', columnObject.node, {
				bindOptions: {
					position: "top"
				},
				offsetLeft: 30,
				offsetTop: -1,
				noAllPaddings: true,
				autoHide: false,
				draggable: {restrict: false},
				content: this.getInformationPopupContent(columnObject)
			});
			this.informationPopup.show();
		},
		getInformationPopupContent: function(columnObject)
		{
			this.infoPopupContentTemplateLabel = this.infoPopupContentTemplate.querySelector('[data-role="info-popup-content-label"]');

			this.infoPopupContentTemplateTopCard =  this.infoPopupContentTemplate.querySelector('[data-role="info-popup-top-card"]');
			this.infoPopupContentTemplateTopTitle = this.infoPopupContentTemplate.querySelector('[data-role="info-popup-top-title"]');
			this.infoPopupContentTemplateTopValue = this.infoPopupContentTemplate.querySelector('[data-role="info-popup-top-value"]');

			this.infoPopupContentTemplateFirstTitle = this.infoPopupContentTemplate.querySelector('[data-role="info-popup-first-title"]');
			this.infoPopupContentTemplateFirstValue = this.infoPopupContentTemplate.querySelector('[data-role="info-popup-first-value"]');

			this.infoPopupContentTemplate.style.borderColor = columnObject.color;
			this.infoPopupContentTemplateLabel.innerText = columnObject.title;

			if (columnObject.topAdditionalTitle !== undefined)
			{
				this.infoPopupContentTemplateTopCard.style.display = 'block';
				this.infoPopupContentTemplateTopTitle.innerText = columnObject.topAdditionalTitle;
				this.infoPopupContentTemplateTopValue.innerHTML = columnObject.topAdditionalValue;
			}
			else
			{
				this.infoPopupContentTemplateTopCard.style.display = 'none';
			}

			this.infoPopupContentTemplateFirstTitle.innerText = columnObject.firstAdditionalTitle;
			this.infoPopupContentTemplateFirstValue.innerHTML = columnObject.firstAdditionalValue;

			return this.infoPopupContentTemplate;
		},
		getColumnMaxValue: function()
		{
			if (this.maxColumnValue !== null)
			{
				return this.maxColumnValue;
			}

			if (this.getColumnValues().length > 0)
			{
				this.maxColumnValue = Math.max.apply(null, this.getColumnValues());
				return this.maxColumnValue;
			}
			this.maxColumnValue = 0;
			return this.maxColumnValue;
		},
		getColumnMinValue: function()
		{
			if (this.minColumnValue !== null)
			{
				return this.minColumnValue;
			}

			if (this.getColumnValues().length > 0)
			{
				this.minColumnValue = Math.min.apply(null, this.getColumnValues());
				return this.minColumnValue;
			}
			this.minColumnValue = 0;
			return this.minColumnValue;
		},
		getColumnValues: function()
		{
			if (this.columnsValues.length !== 0)
			{
				return this.columnsValues;
			}

			for (var i = 0; i < this.columns.length; i++)
			{
				this.columnsValues.push(this.columns[i].value);
			}

			return this.columnsValues;
		}

	}
})();