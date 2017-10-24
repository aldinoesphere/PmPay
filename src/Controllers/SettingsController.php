<?php
 
namespace PmPay\Controllers;
 
 
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Application;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Frontend\Services\SystemService;
 
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

	public function __construct(
		Request $request,
		Response $response,
		SystemService $systemService
	) {
		$this->request = $request;
		$this->response = $response;
		$this->systemService = $systemService;
	}

    public function loadConfiguration(Twig $twig):string
    {
    	$plentyId = $this->systemService->getPlentyId();
        return $twig->render(
        		'PmPay::Settings.Configuration',
        		[
        			'plentyId' => $plentyId,
        			'settingType' => 'pmpay_general'
        		]
        	);
    }

    public function loadConfigurationCreditCard(Twig $twig):string 
    {
    	$plentyId = $this->systemService->getPlentyId();
    	return $twig->render(
        		'PmPay::Settings.CreditCard',
        		[
        			'plentyId' => $plentyId,
        			'settingType' => 'pmpay_cc'
        		]
        	);
    }

    /**
	 * Save Skrill backend configuration
	 *
	 */
	public function saveConfiguration()
	{
		$settingType = $this->request->get('settingType');
		$plentyId = $this->request->get('plentyId');

		$oldConfiguration = $this->loadSetting($plentyId, $settingType);

		$settings['settingType'] = $settingType;

		if ($settingType == 'pmpay_general')
		{
			$settings['settings'][0]['PID_'.$plentyId] = array(
				'userId' => $this->request->get('userId'),
				'password' => $this->request->get('password'),
				'merchantEmail' => $this->request->get('merchantEmail'),
				'shopUrl' => $this->request->get('shopUrl')
			);
		}
		else
		{
			$settings['settings'][0]['PID_'.$plentyId] = array(
				'enabled' => $this->request->get('enabled'),
				'cardTypes' => implode(',', $this->request->get('cardTypes[]')),
				'transactionMode' => $this->request->get('transactionMode'),
				'entityId' => $this->request->get('entityId')
			);
		};

		$result = $this->settingsService->saveConfiguration($settings);

		if ($result == 1)
		{
			$status = 'success';
		}
		else
		{
			$status = 'failed';
		}

		return $this->response->redirectTo('pmpay/settings/');
	}
}