BX.UploaderUtils = {
	dataURLToBlob : function(dataURL)
	{
		var marker = ';base64,', parts, contentType, raw, rawLength;
		if(dataURL.indexOf(marker) == -1) {
			parts = dataURL.split(',');
			contentType = parts[0].split(':')[1];
			raw = parts[1];
			return new Blob([raw], {type: contentType});
		}

		parts = dataURL.split(marker);
		contentType = parts[0].split(':')[1];
		raw = window.atob(parts[1]);
		rawLength = raw.length;

		var uInt8Array = new Uint8Array(rawLength);

		for(var i = 0; i < rawLength; ++i) {
			uInt8Array[i] = raw.charCodeAt(i);
		}

		return new Blob([uInt8Array], {type: contentType});
	},
	getFormattedSize : function (size, precision)
	{
		var a = ["b", "Kb", "Mb", "Gb", "Tb"], pos = 0;
		while(size >= 1024 && pos < 4)
		{
			size /= 1024;
			pos++;
		}

		return (Math.round(size * (precision > 0 ? precision * 10 : 1) ) / (precision > 0 ? precision * 10 : 1)) +
			" " + BX.message("FILE_SIZE_" + a[pos]);
	}
};
