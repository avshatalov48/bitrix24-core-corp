<?php

namespace Bitrix\Tasks\Slider\Exception;

use Exception;

class SliderException extends Exception
{
	public function show(): void
	{
		echo "
			<script>
				BX.UI.Notification.Center.notify({
					content: '{$this->getMessage()}'
				});
			</script>
		";
	}
}