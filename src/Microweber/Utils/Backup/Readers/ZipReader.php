<?php
namespace Microweber\Utils\Backup\Readers;

use Microweber\Utils\Backup\BackupManager;
use Microweber\Utils\Backup\Loggers\BackupImportLogger;

class ZipReader extends DefaultReader
{
	/**
	 * Read data from file
	 * @return \JsonMachine\JsonMachine[]
	 */
	public function readData()
	{
		$this->_checkPathsExists();
		
		BackupImportLogger::setLogInfo('Unzipping '.basename($this->file).' in userfiles...');
		
		$backupManager = new BackupManager();
		$backupLocation = $backupManager->getBackupLocation(). 'temp_backup_zip/';

		// Remove old files
		$this->_removeFilesFromPath($backupLocation);
		
		$unzip = new \Microweber\Utils\Unzip();
		$unzip->extract($this->file, $backupLocation, true);
		
		if ($backupLocation != false and is_dir($backupLocation)) {
			BackupImportLogger::setLogInfo('Media restored!');
			$copy = $this->_cloneDirectory($backupLocation, userfiles_path());
		}
		
		$mwContentJsonFile = $backupLocation. 'mw_content.json';
		
		if (is_file($mwContentJsonFile)) {
			$jsonReader = new JsonReader($mwContentJsonFile);
			return $jsonReader->readData();		
		} else {
			BackupImportLogger::setLogInfo('The zip file has no mw_content.json. Nothing to import.');
		}
		
	}
	
	/**
	 * Remove dir recursive
	 * @param string $dir
	 */
	private function _removeFilesFromPath($dir)
	{
		if (!is_dir($dir)) {
			return;
		}
		
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			@$todo($fileinfo->getRealPath());
		}

		@rmdir($dir);
	}
	
	private function _checkPathsExists() {
		
		if (userfiles_path()) {
			if (!is_dir(userfiles_path())) {
				mkdir_recursive(userfiles_path());
			}
		}
		
		if (media_base_path()) {
			if (!is_dir(media_base_path())) {
				mkdir_recursive(media_base_path());
			}
		}
	}
	
	/**
	 * Clone directory by path and destination
	 * @param stringh $source
	 * @param stringh $destination
	 * @return stringh|boolean
	 */
	private function _cloneDirectory($source, $destination)
	{
		if (is_file($source) and ! is_dir($destination)) {
			$destination = normalize_path($destination, false);
			$source = normalize_path($source, false);
			$destinationDir = dirname($destination);
			if (! is_dir($destinationDir)) {
				mkdir_recursive($destinationDir);
			}
			if (! is_writable($destination)) {
				// return;
			}

			return @copy($source, $destination);
		}

		if (! is_dir($destination)) {
			mkdir_recursive($destination);
		}

		if (is_dir($source)) {
			$dir = dir($source);
			if ($dir != false) {
				while (false !== $entry = $dir->read()) {
					if ($entry == '.' || $entry == '..') {
						continue;
					}
					if ($destination !== "$source/$entry" and $destination !== "$source" . DS . "$entry") {
						$this->_cloneDirectory("$source/$entry", "$destination/$entry");
					}
				}
			}

			$dir->close();
		}

		return true;
	}
}