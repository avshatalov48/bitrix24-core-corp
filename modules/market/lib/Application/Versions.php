<?php

namespace Bitrix\Market\Application;

class Versions
{
	public static function getTextChanges(array $versions): array
	{
		$result = [];

		if (!empty($versions)) {
			ksort($versions);
		}

		$index = 0;
		foreach ($versions as $version => $text) {
			$result[] = [
				'INDEX' => $index++,
				'VERSION' => $version,
				'TEXT' => $text,
			];
		}

		return $result;
	}
}