<?php  
namespace PmPay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Plugin\Routing\ApiRouter;


class PmPayRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router, ApiRouter $apiRouter) {

		$apiRouter->version(
			['v1'],
			['namespace' => 'PmPay\Controllers', 'middleware' => 'oauth'],
			function ($apiRouter) {
				$apiRouter->post('payment/pmpay/settings/', 'SettingsController@saveSettings');
				$apiRouter->get('payment/pmpay/settings/{settingType}', 'SettingsController@loadSettings');
				$apiRouter->get('payment/pmpay/setting/{plentyId}/{settingType}', 'SettingsController@loadSetting');
			}
		);

		// Routes for display General settings
		$router->get('pmpay/settings/{settingType}','PmPay\Controllers\SettingsController@loadConfiguration');

		// Routes for 
		$router->post('pmpay/settings/save','PmPay\Controllers\SettingsController@saveConfiguration');

		// Routes for PmPay payment widget
		$router->get('payment/pmpay/pay/{id}', 'PmPay\Controllers\PaymentController@handlePayment');

		// Routes for PmPay status_url
		$router->get('payment/pmpay/status', 'PmPay\Controllers\PaymentNotificationController@handleStatus');

		// Routes for PmPay payment return
		$router->post('payment/pmpay/return', 'PmPay\Controllers\PaymentController@handleReturn'); 

		// Routes for PmPay payment validation
		$router->get('payment/pmpay/validate/{id}/', 'PmPay\Controllers\PaymentController@handleValidation');
	}
}

?>