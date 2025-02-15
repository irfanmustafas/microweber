<?php
namespace Microweber\Utils\Backup\Readers;

use Microweber\Utils\Backup\Readers\Vendors\WordpressReader;

class CsvReader extends DefaultReader
{
	use WordpressReader;
	
	public function readData()
	{
		$csv = $this->readCsv($this->file);
		
		if (isset($csv[0]['title']) && isset($csv[0]['content_body'])) {
			return $this->readWordpress($csv);
		}
	}
	
	public function uniqueColumns(array $columns):array {
		$values = [];
		
		foreach ($columns as $value) {
			$count = 0;
			$value = $original = trim($value);
			
			while (in_array($value, $values)) {
				$value = $original . '-' . ++$count;
			}
			
			$values[] = $value;
		}
		
		return $values;
	}

	public function readCsv(string $file, int $length = 1000, string $delimiter = ','): array
	{
		$handle = fopen($file, 'r');
		$hashes = [];
		$values = [];
		$header = null;
		$headerUnique = null;

		if (! $handle) {
			return $values;
		}

		$header = fgetcsv($handle, $length, $delimiter);

		if (! $header) {
			return $values;
		}

		$headerUnique = $this->uniqueColumns($header);

		while (false !== ($data = fgetcsv($handle, $length, $delimiter))) {
			$hash = md5(serialize($data));

			if (! isset($hashes[$hash])) {
				$hashes[$hash] = true;
				$values[] = array_combine($headerUnique, $data);
			}
		}

		fclose($handle);

		return $values;
	}
}