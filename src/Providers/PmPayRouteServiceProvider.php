<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;


class PmPayRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router) {

		// Routes for display General settings
		$router->get('pmpay/general-setting','PmPay\Controllers\SettingsController@loadConfiguration');

		// Routes for display Credit Card settings
		$router->get('pmpay/credit-card','PmPay\Controllers\SettingsController@loadConfigurationCreditCard');

		// Routes for 
		$router->post('pmpay/save','PmPay\Controllers\SettingsController@saveConfiguration');
	}
}

?>