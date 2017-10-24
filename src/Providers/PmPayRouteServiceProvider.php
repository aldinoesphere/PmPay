<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;


class PmPayRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router) {

		// Routes for display Skrill settings
		$router->get('pmpay','PmPay\Controllers\SettingsController@loadConfiguration');
	}
}

?>