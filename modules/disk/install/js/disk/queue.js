BX.Disk.Queue = (function ()
{
	var Queue = function (parameters)
	{
		this.isProcessing = false;
		this.items = [];
		this.setEvents();
	};

	Queue.prototype.setEvents = function()
	{};

	Queue.prototype.process = function()
	{
		if(this.items.length <= 0)
		{
			return;
		}

		if(this.isProcessing)
		{
			return;
		}

		this.isProcessing = true;
		var item = this.items.shift();

		item.run(BX.delegate(function(){
			this.isProcessing = false;
			this.process();
		}, this));
	};

	Queue.prototype.push = function(item)
	{
		if(BX.is_subclass_of(item, BX.Disk.QueueItem))
		{
			this.items.push(item);
		}

		return this;
	};

	return Queue;
})();

BX.Disk.QueueItem = (function ()
{
	var QueueItem = function (object, runHandler, eventFinish)
	{
		this.object = object;
		this.runHandler = runHandler;
		this.successFallback = null;

		this.subscribeOnFinish(eventFinish);
	};

	QueueItem.prototype.run = function(successFallback)
	{
		this.successFallback = successFallback;

		return this.runHandler.call(this, this.object);
	};

	QueueItem.prototype.subscribeOnFinish = function(eventDescription)
	{
		var resultDescription = eventDescription.call(this, this.object);

		if(BX.type.isString(resultDescription))
		{
			BX.addCustomEvent(resultDescription, BX.delegate(this.onFinishItem, this));
		}
		else if(BX.type.isArray(resultDescription))
		{
			BX.addCustomEvent(resultDescription[0], resultDescription[1], BX.delegate(this.onFinishItem, this));
		}
	};

	QueueItem.prototype.onFinishItem = function()
	{
		this.successFallback.call();
	};

	return QueueItem;
})();