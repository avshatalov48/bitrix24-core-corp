BX.EncodingHandler = (function()
{
	var EncodingHandler = function(){};

	EncodingHandler.prototype.handleEncodings = function(parameters)
	{
		var formId = parameters.formId;
		var file = parameters.file;
		var resultEncodingElementId = parameters.resultEncodingElementId;

		if (!file || file.name.substring(file.name.lastIndexOf('.') + 1).toLowerCase() !== 'csv')
		{
			BX.submit(BX(formId), 'next');
			return;
		}

		var reader = new FileReader;
		reader.readAsBinaryString(file);
		reader.onload = BX.delegate(function(event)
		{
			var content = event.target.result;

			if (this.checkStringOnUtf8(content))
			{
				BX(resultEncodingElementId).value = 'UTF-8';
				BX.submit(BX(formId), 'next');
			}
			else
			{
				this.getPopupWithEncodings(parameters, file);
			}
		}, this);
	};

	EncodingHandler.prototype.checkStringOnUtf8 = function(string)
	{
		var bytes = this.unpackBinaryString(string);

		var prevBits8and7 = 0;
		var utfCoefficient = 0;

		bytes.forEach(function(byte)
		{
			var hiBits8and7 = byte & 0xC0;

			if (hiBits8and7 === 0x80)
			{
				if (prevBits8and7 === 0xC0)
					utfCoefficient++;
				else if ((prevBits8and7 & 0x80) === 0x00)
					utfCoefficient--;
			}
			else if (prevBits8and7 === 0xC0)
			{
				utfCoefficient--;
			}
			prevBits8and7 = hiBits8and7;
		});

		return (utfCoefficient > 0);
	};

	EncodingHandler.prototype.unpackBinaryString = function(str)
	{
		var bytes = [];

		for (var i = 0; i < str.length; i++)
		{
			var char = str.charCodeAt(i);
			bytes.push(char & 0xFF);
		}

		return bytes;
	};

	EncodingHandler.prototype.getPopupWithEncodings = function(parameters, file)
	{
		var charsets = parameters.charsets;
		var formId = parameters.formId;
		var resultEncodingElementId = parameters.resultEncodingElementId;

		this._readers = {};

		charsets.forEach(BX.delegate(function(charset)
		{
			this._readers[charset] = new FileReader();
			this._readers[charset].readAsText(file, charset);
			this._readers[charset].onload = BX.delegate(function ()
			{
				var results = this.getEncodedResults();
				if (results)
					EncodingHandler.prototype.createPopup(results, formId, resultEncodingElementId);
			}, this);
		}, this));
	};

	EncodingHandler.prototype.getEncodedResults = function()
	{
		var finished = true;

		Object.keys(this._readers).forEach(BX.delegate(function(charset)
		{
			if (this._readers[charset].readyState !== 2)
				finished = false;
		}, this));

		if (finished)
		{
			var results = {};

			Object.keys(this._readers).forEach(BX.delegate(function(charset)
			{
				results[charset] = this._readers[charset].result;
			}, this));

			return results;
		}

		return finished;
	};

	EncodingHandler.prototype.createPopup = function(results, formId, resultEncodingElementId)
	{
		var tableContent = [];
		var maxTextWidth = 50;

		Object.keys(results).forEach(function(charset)
		{
			var encodedText = results[charset].substring(0, maxTextWidth);

			var label = BX.create('label', {
				props: {
					className: 'popup-window-label'
				},
				text: charset
			});

			var button = BX.create('button', {
				props: {
					className: 'popup-window-custom-button'
				},
				text: encodedText,
				events: {
					click: function() {
						popupWindow.close();
						BX(resultEncodingElementId).value = charset;
						BX.submit(BX(formId), 'next');
					}
				}
			});

			var tr = BX.create('tr', {
				children: [
					BX.create('td', {
						props: {
							className: 'popup-window-table-col-left'
						},
						children: [label]
					}),
					BX.create('td', {children: [button]})
				]
			});

			tableContent.push(tr);
		});

		var popupWindow = new BX.PopupWindow(
			"popup_window",
			null,
			{
				content: BX.create('table', {children: tableContent}),
				closeByEsc: true,
				closeIcon: false,
				autoHide: true,
				titleBar: {
					content: BX.create("label", {
						props: {className: 'popup-window-custom-title'},
						html: BX.message('CRM_IMPORT_CSV_POPUP_WINDOW_TITLE')
					})
				},
				overlay: {backgroundColor: '#000', opacity: 50},
				draggable: false,
				events: {
					onPopupClose: function() { this.destroy(); }
				}
			});

		popupWindow.show();
	};

	return EncodingHandler;
})();