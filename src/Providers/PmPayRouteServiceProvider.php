<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Plugin\Routing\ApiRouter;


/**
* @package PmPay\Providers
*/
class PmPayRouteServiceProvider extends RouteServiceProvider
{
	/**
	* mapping the router
	*
	* @param Router $router
	* @param ApiRouter $apiRouter
	*/
	public function map(Router $router, ApiRouter $apiRouter) {

		// Routes for display Skrill settings
		$router->get('pmpay/settings/pmpay_general', 'PmPay\Controllers\SettingsController@loadConfiguration');

		// Routes for save Skrill settings
		$router->post('pmpay/settings/save', 'PmPay\Controllers\SettingsController@saveConfiguration');
	}
}

?>