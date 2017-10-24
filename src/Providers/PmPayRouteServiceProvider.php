<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;


class PmPayRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router) {

		// Routes for display PmPay settings
		$router->get('test','PmPay\Controllers\SettingsController@loadConfiguration');
	}
}

?>