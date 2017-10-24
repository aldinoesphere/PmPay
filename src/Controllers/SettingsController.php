<?php
 
namespace PmPay\Controllers;
 
 
use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
 
class SettingsController extends Controller
{
    public function loadConfiguration(Twig $twig):string
    {
        return $twig->render('PmPay::Settings.Configuration');
    }
}