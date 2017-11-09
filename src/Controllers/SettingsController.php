<?php

namespace PmPay\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Application;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Frontend\Services\SystemService;
use PmPay\Services\Database\SettingsService;
use PmPay\Helper\PaymentHelper;

/**
* Class SettingsController
* @package PmPay\Controllers
*/
class SettingsController extends Controller
{
	use Loggable;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

	/**
	 *
	 * @var systemService
	 */
	private $systemService;

	/**
	 * @var settingsService
	 */
	private $settingsService;

	/**
	 * SettingsController constructor.
	 * @param SettingsService $settingsService
	 */
	public function __construct(
					Request $request,
					Response $response,
					SystemService $systemService,
					SettingsService $settingsService
	) {
		$this->request = $request;
		$this->response = $response;
		$this->systemService = $systemService;
		$this->settingsService = $settingsService;
	}

	/**
	 * save the settings
	 *
	 * @param Request $request
	 */
	public function saveSettings(Request $request)
	{
		return $this->settingsService->saveSettings($request->get('settingType'), $request->get('settings'));
	}

	/**
	 * load the settings
	 *
	 * @param string $settingType
	 * @return array
	 */
	public function loadSettings($settingType)
	{
		return $this->settingsService->loadSettings($settingType);
	}

	/**
	 * Load the settings for one webshop
	 *
	 * @param string $plentyId
	 * @param string $settingType
	 * @return null|mixed
	 */
	public function loadSetting($plentyId, $settingType)
	{
		return $this->settingsService->loadSetting($plentyId, $settingType);
	}

	/**
	 * Display PmPay backend configuration
	 *
	 * @param Twig $twig
	 * @param string $settingType
	 * @return void
	 */
	public function loadConfiguration(Twig $twig, $settingType)
	{
		$plentyId = $this->systemService->getPlentyId();

		try {
			$configuration = $this->settingsService->getConfiguration($plentyId, $settingType);
		}
		catch (\Exception $e)
		{
			die('something wrong, please try again...');
		}
		if ($configuration['error']['code'] == '401')
		{
			die('access denied...');
		}

		$this->getLogger(__METHOD__)->error('PmPay:loadConfiguration', $configuration);

		return $twig->render(
						'PmPay::Settings.Configuration',
						array(
							'status' => $this->request->get('status'),
							'locale' => substr($_COOKIE['plentymarkets_lang_'], 0, 2),
							'plentyId' => $plentyId,
							'settingType' => $settingType,
							'setting' => $configuration
						)
		);
	}

	/**
	 * Save PmPay backend configuration
	 *
	 */
	public function saveConfiguration()
	{
		$settingType = $this->request->get('settingType');
		$plentyId = $this->request->get('plentyId');
		
		$settings = $this->setSettings($settingType, $plentyId, $this->request);
		array_push($settings, ['settingType' => $settingType]);

		$this->getLogger(__METHOD__)->error('PmPay:settings', $settings);

		$result = $this->settingsService->saveConfiguration($settings);

		if ($result == 1)
		{
			$status = 'success';
		}
		else
		{
			$status = 'failed';
		}
		$this->getLogger(__METHOD__)->error('PmPay:saveConfiguration', $settings);

		return $this->response->redirectTo('pmpay/settings/'.$settingType.'?status='.implode(',', $newCardTypes));
	}


	public function setSettings($settingType, $plentyId, $request) {
		$cardTypes = $this->request->get('cardTypes');
		$newCardTypes = [];

		foreach ($cardTypes as $key => $value) {
			array_push($newCardTypes, $value);
		}
		
		switch ($settingType) {
			case 'general-setting':
				return $settings['settings'][0]['PID_'.$plentyId] = array(
							'userId' => $request->get('userId'),
							'password' => $request->get('password'),
							'merchantEmail' => $request->get('merchantEmail'),
							'shopUrl' => $request->get('shopUrl')
						);
				break;

			case 'credit-card':
				return $settings['settings'][0]['PID_'.$plentyId] = array(
							'language' => $request->get('language'),
							'display' => $request->get('display'),
							'cardType' => implode(',', $newCardTypes),
							'transactionMode' => $request->get('transactionMode'),
							'entityId' => $request->get('entityId')
						);		
				break;

			case 'easy-credit':
				return $settings['settings'][0]['PID_'.$plentyId] = array(
							'server' => $request->get('language'),
							'enable' => $request->get('display'),
							'entityId' => $request->get('entityId')
						);
				break;
			
			default:
				return $settings[];
				break;
		}
	}
}