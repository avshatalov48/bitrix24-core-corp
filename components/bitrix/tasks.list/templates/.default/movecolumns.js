/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2014 Bitrix
 * 
 * Expects that first column is not draggable
 */

function tasks_TableColumnMove(table, lastColumnNotDraggable, onColumnMoved, draggableDivClsName, draggedClsName)
{
	this.table         = table;
	this.onColumnMoved = onColumnMoved;
	this.columns       = [];
	this.bxblank       = null;
	this.deltaX        = null;
	this.objectWidth   = null;
	this.bxMovedDiv    = null;
	this.cellOffsets   = [];
	this.dragMap       = [];
	this.dragMapLength = 0;
	this.dragLock      = false;
	this.prevTargetCellIndex = null;
	this.dragReady     = false;
	this.draggedCell   = null;		// currently dragged cell
	this.draggedCellLeftOffset = null;
	this.leftDragMargin = 0;
	this.rightDragMargin = 0;

	this.draggableDivClsName = draggableDivClsName;
	this.draggedClsName  = draggedClsName;


	this.onDragStart = function(object)
	{
		var i, iMax, offset = 0;
		var x = null;
		var parentCell = null;
		var divWidth;
		var divHeight;
		var tableWidth;

		this.dragReady = false;

		tableWidth = this.fixTableWidth();

		this.rightDragMargin = tableWidth - 25;

		this.objectWidth = object.offsetWidth;

		divWidth  = this.objectWidth + 'px';
		divHeight = object.offsetHeight + 'px';

		this.draggedCell = parentCell = object.parentNode;

		if (window.event && window.event.pageX)
			x = window.event.pageX;

		this.bxblank = BX.create(
			'DIV',
			{
				style:
				{
					height : divHeight,
					width  : divWidth
				}
			}
		);

		iMax = table.rows[0].cells.length - 1;
		if (lastColumnNotDraggable)
			--iMax;

		this.cellOffsets = [];
		for (i = 0; i <= iMax; ++i)
		{
			offset += table.rows[0].cells[i].offsetWidth;
			this.cellOffsets.push(offset);
		}

		this.prevTargetCellIndex = parentCell.cellIndex;

		this.draggedCellLeftOffset = this.cellOffsets[parentCell.cellIndex - 1];

		this.bxMovedDiv = BX.create('DIV', {             //div to move
			style: {
				position : 'absolute',
				top      : '0px',
				left     : this.draggedCellLeftOffset + 'px',
				zIndex   : '100',
				height   : divHeight,
				width    : divWidth
			},
			children: [object]
		});

		BX.addClass(this.bxMovedDiv, this.draggedClsName);

		if (x !== null)
			this.deltaX = x - this.draggedCellLeftOffset;
		else
			this.deltaX = null;

		// prepare cells map (relative zero at left border of currently dragged cell)
		offset = 0;
		this.dragMap = [];
		for (i = 1; i < (iMax - 1); ++i)
			this.dragMap.push(this.cellOffsets[i]);

		this.dragMapLength = this.dragMap.length;

		parentCell.appendChild(this.bxblank);
		parentCell.appendChild(this.bxMovedDiv);

		this.dragReady = true;
	};

	this.onDrag = function(object, x, y)
	{
		var centerPosition, i, targetCellIndex, tmp;

		if ( ! this.dragReady )
			return;

		if (this.dragLock)
			return;

		if (this.deltaX === null)
			this.deltaX = x - this.draggedCellLeftOffset;

		this.dragLock = true;

		x -= this.deltaX;

		if (x < this.leftDragMargin)
			this.bxMovedDiv.style.left = this.leftDragMargin + 'px';
		else if (x > this.rightDragMargin)
			this.bxMovedDiv.style.left = this.rightDragMargin + 'px';
		else
			this.bxMovedDiv.style.left = x + 'px';

		centerPosition = x + 10;	// + this.objectWidth / 2;

	 	targetCellIndex = 1;
		for (i = 0; i < this.dragMapLength; ++i)
		{
			if (this.dragMap[i] < centerPosition)
				++targetCellIndex;
			else
				break;
		}

		if (targetCellIndex != this.prevTargetCellIndex)
		{
			this.onTargetChanged(
				this.draggedCell.cellIndex,
				this.prevTargetCellIndex,
				targetCellIndex,
				(function(self){
					return function(){
						self.prevTargetCellIndex = targetCellIndex;
						self.dragLock = false;
					};
				})(this)
			);
		}
		else
		{
			this.prevTargetCellIndex = targetCellIndex;
			this.dragLock = false;
		}
	};

	this.onTargetChanged = function(draggedCellIndex, prevTargetCellIndex, targetCellIndex, onReady)
	{
		var targetDiv, targetCell, blankCell, blankCellWidthWas, distance;

		targetCell = table.rows[0].cells[targetCellIndex];
		targetDiv  = BX.findChild(targetCell, {tagName: 'DIV', className: this.draggableDivClsName}, false, false);

		blankCell         = this.bxblank.parentNode;
		blankCellWidthWas = blankCell.offsetWidth;

		this.bxblank.style.width  = targetCell.offsetWidth + 'px';
		targetCell.insertBefore(this.bxblank, targetDiv);

		if (targetCellIndex > prevTargetCellIndex)
			distance = -1 * blankCellWidthWas;
		else
			distance = targetDiv.offsetWidth;

		targetDiv.style.position = 'absolute';

		this.animateMoving(
			0, 
			4, 
			targetDiv, 
			distance, 
			blankCellWidthWas - targetDiv.offsetWidth,
			function(){
				targetDiv.style.width = blankCellWidthWas + 'px';
				targetDiv.style.position = '';
				targetDiv.style.top = '';
				targetDiv.style.left = '';
				blankCell.insertBefore(targetDiv, null);
				onReady();
			}
		);
	};

	this.animateMoving = function(step, totalSteps, object, distance, deltaWidth, onReady)
	{
		var x, delta;

		if (step == (totalSteps - 1))
		{
			onReady();
			return;
		}

		if (step == 0)
		{
			object.style.width = object.offsetWidth + 'px';
			object.style.top = '0px';
			object.style.left = (this.cellOffsets[object.parentNode.cellIndex] - object.offsetWidth) + 'px';
		}

		object.style.left  = parseInt(object.style.left) + (distance / totalSteps) + 'px';
		object.style.width = parseInt(object.style.width) + (deltaWidth / totalSteps) + 'px';

		window.setTimeout(
			(function(self){
				return function(){
					self.animateMoving(step + 1, totalSteps, object, distance, deltaWidth, onReady);
				};
			})(this),
			140 / totalSteps
		);
	};

	this.onDragStop = function(object)
	{
		var dragContainer, prevCellIndex, newCellIndex;

		this.dragReady = false;

		dragContainer = object.parentNode;
		prevCellIndex = dragContainer.parentNode.cellIndex;

		BX.removeClass(dragContainer, this.draggedClsName);

		object.style.width = this.bxblank.offsetWidth + 'px';
		this.bxblank.parentNode.replaceChild(object, this.bxblank);

		dragContainer.parentNode.removeChild(dragContainer);

		newCellIndex = object.parentNode.cellIndex;
		this.onColumnMoved(prevCellIndex, newCellIndex);
	};

	this.fixTableWidth = function()
	{
		var allTableColumns, resizableDiv, tableWidth, widthes = [];

		allTableColumns = this.table.rows[0].cells;

		// fix widthes of all columns
		tableWidth = 0;

		// Firstly, get real width of every column in the table
		for (i = 0; i < allTableColumns.length; i++)
		{
			widthes[i] = parseInt(allTableColumns[i].offsetWidth);
			tableWidth = tableWidth + widthes[i];
		}

		// Now, set it up in style.width
		for (i = 0; i < allTableColumns.length; i++)
		{
			allTableColumns[i].style.width = widthes[i] + 'px';

			resizableDiv = BX.findChild(
				allTableColumns[i],
				{tagName: "div"},
				false,		// recursive
				false		// get_all
			);

			if (resizableDiv)
				resizableDiv.style.width = widthes[i] +'px';
		}

		this.table.style.width = tableWidth + 'px';
		this.table.style.tableLayout = "fixed";

		return (tableWidth);
	};

	// init
	(function(self){
		var i, iMax, div;

		window.jsDD.Enable();

		self.leftDragMargin = table.rows[0].cells[0].offsetWidth;

		table.style.position = 'relative';
		iMax = table.rows[0].cells.length - 1;

		if (lastColumnNotDraggable)
			--iMax;

		for (i = 0; i <= iMax; ++i)
		{
			div = BX.findChild(table.rows[0].cells[i], {tagName: 'DIV'}, false, false);

			if (i != 0)
			{
				div.onbxdragstart = function(){ self.onDragStart(this); };
				div.onbxdrag      = function(x, y){ self.onDrag(this, x, y); };
				div.onbxdraghover = function(dest, x, y){};
				div.onbxdragstop  = function(){ self.onDragStop(this); };
			}

			if (i != 0)
				jsDD.registerObject(div);

			self.columns.push(div);
		}
	})(this);
}
