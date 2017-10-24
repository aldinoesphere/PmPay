<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;


class PmPayRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router) {

		// Routes for display General settings
		$router->get('pmpay/settings/{settingType}','PmPay\Controllers\SettingsController@loadConfiguration');

		// Routes for 
		$router->post('pmpay/save','PmPay\Controllers\SettingsController@saveConfiguration');
	}
}

?>