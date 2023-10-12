<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Security\Random;

class ActiveFieldAssembler extends FieldAssembler
{

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			if (!isset($row['data'][$columnId]))
			{
				$row['columns'][$columnId] = null;
				continue;
			}

			$checked = 'false';
			$id = 0;
			$switcherId = \CUtil::JSEscape(Random::getString('6'));
			$isCanWrite = CurrentUser::get()->canDoOperation('biconnector_key_manage');
			$isDisable = $isCanWrite ? 'false' : 'true';

			if (isset($row['data']['ID'], $row['data']['ACTIVE']))
			{
				$checked = (string)$row['data']['ACTIVE'] === 'Y' ? 'true' : 'false';
				$id = (int)$row['data']['ID'];
			}

			$result = "
				<span id='switcher-{$switcherId}'/>
				<script>
					(new BX.UI.Switcher({
						node: document.getElementById('switcher-$switcherId'),
						checked: {$checked},
						id: '{$switcherId}',
						disabled: {$isDisable}
					})).handlers = {
						checked: () => {
                            BX.BIConnector.KeysGrid.deactivateKey({$id}, this);
						},
						unchecked: () => {
							BX.BIConnector.KeysGrid.activateKey({$id}, this);
						}
					};
				</script>
			";
			$row['columns'][$columnId] = $result;
		}

		return $row;
	}
}