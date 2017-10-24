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

	/**
	 *
	 * @var systemService
	 */
	private $settingsService;

	public function __construct(
		Request $request,
		Response $response,
		SystemService $systemService,
		SettingsService $settingsService
	) {
		$this->request = $request;
		$this->response = $response;
		$this->systemService = $systemService;
		$this->SettingsService = $settingsService;
	}

    public function loadConfiguration(Twig $twig, $settingType):string
    {
    	$plentyId = $this->systemService->getPlentyId();


        return $twig->render(
        		'PmPay::Settings.Configuration',
        		[
        			'plentyId' => $plentyId,
        			'settingType' => $settingType
        		]
        	);
    }

    // public function loadConfigurationCreditCard(Twig $twig):string 
    // {
    // 	$plentyId = $this->systemService->getPlentyId();
    // 	return $twig->render(
    //     		'PmPay::Settings.CreditCard',
    //     		[
    //     			'plentyId' => $plentyId,
    //     			'settingType' => 'pmpay_cc'
    //     		]
    //     	);
    // }

    /**
	 * Save Skrill backend configuration
	 *
	 */
	public function saveConfiguration()
	{
		$settingType = $this->request->get('settingType');
		$plentyId = $this->request->get('plentyId');

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

		$result = $this->settingsService->saveSettings($plentyId, $settings);

		if ($result == 1)
		{
			$status = 'success';
		}
		else
		{
			$status = 'failed';
		}

		// return $this->response->redirectTo('pmpay/settings/'.$settingType.'?status='.$status);
	}
}