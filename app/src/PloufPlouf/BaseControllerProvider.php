<?php

namespace PloufPlouf;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;

abstract class BaseControllerProvider implements ControllerProviderInterface
{
    /** @var Application */
    protected $app;

    /**
     * Assign a value to the "menu" twig global.
     * @param string $menu
     */
    protected function menu($menu) {
        $this->app['twig']->addGlobal('menu', $menu);
    }

    /**
     * Return error(s) as json
     *
     * @param mixed $error
     * @param Application $app
     * @param int $code http code to return
     *
     * @return JsonResponse
     */
    protected function returnError($error, Application $app, $code=500) {

        $key = 'message';
        if(is_array($error)) { $key = 'errors'; }

        return $app->json([$key => $error], $code, array('X-Status-Code' => $code));
    }

    /**
     * Get all errors from a form
     * @url http://stackoverflow.com/a/15693527/1031015
     *
     * @param \Symfony\Component\Form\Form $form
     * @return array
     */
    protected function getErrorMessages(\Symfony\Component\Form\Form $form)
    {
        $errors = [];

        foreach($form->all() as $k => $input) {
            if (count($input->getErrors()) > 0) {
                $errors[$k] = $input->getErrors()[0]->getMessage();
            }
        }

        return $errors;
    }

    public function connect(Application $app) {
       $this->app = $app;
    }

}