__MFUCallback = function(data, loading_id)
{
	var id = false;
	var type = false;

	if (!!data.fileID)
	{
		id = data.fileID;
		type = 'file';
	}
	else if (!!data.elementID)
	{
		id = data.elementID;
		type = 'element';
	}
	else if (!!data.diskID)
	{
		id = data.diskID;
		type = 'disk';
	}
		
	if (!!id && BX('mfu_file_container'))
	{
		var hidden = BX.create('INPUT', {
			props: {
				'id': 'mfu_' + type + '_id_' + id,
				'type': 'hidden',
				'name': BX.message('MFUControlNameFull'),
				'value': (type == 'disk' ? 'n' : '') + id
			}
		});
		BX('mfu_file_container').appendChild(hidden);
		
		if (BX('newpost_photo_counter'))
		{
			if (BX('newpost_photo_counter').value == '')
				BX('newpost_photo_counter').value = 0;
			BX('newpost_photo_counter').value = parseInt(BX('newpost_photo_counter').value) + 1;
			
			if (BX('newpost_photo_counter_title') && BX('newpost_photo_counter_title').firstChild)
			{
				BX.adjust(BX('newpost_photo_counter_title').firstChild, {
					html : BX('newpost_photo_counter').value
				});
				BX('newpost_photo_counter_title').style.display = 'block';
			}
		}
	}

	if (!id)
	{
		oMPF.hideProgressBar(loading_id);
	}
}

