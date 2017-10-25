<?php

namespace PmPay\Migrations;

use PmPay\Models\Database\Settings;
use PmPay\Services\Database\SettingsService;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

/**
* Migration to create PmPay configuration tables
*
* Class CreatePmPayTables
* @package PmPay\Migrations
*/
class CreatePmPayTables
{
	/**
	 * Run on plugin build
	 *
	 * Create skrill configuration tables.
	 *
	 * @param Migrate $migrate
	 */
	public function run(Migrate $migrate)
	{
		/**
		 * Create the settings table
		 */
		try {
			$migrate->deleteTable(Settings::class);
		}
		catch (\Exception $e)
		{
			//Table does not exist
		}

		$migrate->createTable(Settings::class);

		// Set default payment method name in all supported languages.
		// $service = pluginApp(SettingsService::class);
		// $service->setInitialSettings();
	}
}
