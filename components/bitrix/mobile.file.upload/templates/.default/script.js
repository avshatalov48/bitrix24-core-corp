__MFUCallback = function(data)
{
	if (data.fileID && BX('mfu_file_container'))
	{
		var hidden = BX.create('INPUT', {
			props: {
				'id': 'mfu_file_id_' + data.fileID,
				'type': 'hidden',
				'name': BX.message('MFUControlNameFull'),
				'value': data.fileID
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
}