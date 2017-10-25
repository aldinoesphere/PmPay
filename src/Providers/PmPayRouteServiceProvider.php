<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;


class PmPayRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router) {

		$apiRouter->version(
			['v1'],
			['namespace' => 'PmPay\Controllers', 'middleware' => 'oauth'],
			function ($apiRouter) {
				$apiRouter->post('payment/skrill/settings/', 'SettingsController@saveSettings');
				$apiRouter->get('payment/skrill/settings/{settingType}', 'SettingsController@loadSettings');
				$apiRouter->get('payment/skrill/setting/{plentyId}/{settingType}', 'SettingsController@loadSetting');
			}
		);

		// Routes for display General settings
		$router->get('pmpay/{settingType}','PmPay\Controllers\SettingsController@loadConfiguration');

		// Routes for 
		$router->post('pmpay/save','PmPay\Controllers\SettingsController@saveConfiguration');
	}
}

?>