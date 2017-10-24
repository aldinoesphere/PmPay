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
        			'settingType' => 'HelloWorld'
        		]
        	);
    }

    public function loadConfigurationCreditCard(Twig $twig):string 
    {
    	$plentyId = $this->systemService->getPlentyId();
    	return $twig->render(
        		'PmPay::Settings.CrefitCard',
        		[
        			'plentyId' => $plentyId,
        			'settingType' => 'CeditCard'
        		]
        	);
    }
}