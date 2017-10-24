<?php
 
namespace PmPay\Controllers;
 
 
use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
 
class SettingsController extends Controller
{
    public function loadConfiguration(Twig $twig):string
    {
        return $twig->render(
        		'PmPay::Settings.Configuration',
        		[
					'status' => $this->request->get('status'),
					'locale' => substr($_COOKIE['plentymarkets_lang_'], 0, 2),
					'plentyId' => $plentyId,
					'settingType' => $settingType				]
        	);
    }
}