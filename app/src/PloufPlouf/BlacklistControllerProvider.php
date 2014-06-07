<?php

namespace PloufPlouf;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Silex\ControllerProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Predis\Client;

class BlacklistControllerProvider extends BaseControllerProvider
{
    public function connect(Application $app)
    {
        parent::connect($app);
        $this->menu('blacklist');

        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $redis = $app['predis'];

        /**
         * Requested blacklist from mail link
         */
        $controllers->get('/add/{message_id}/{email_id}', function ($message_id, $email_id) use ($app, $redis) {

            if ($redis->EXISTS('mail:' . $message_id) && ($redis->HEXISTS('mail:' . $message_id, $email_id))) {
                $email = $redis->HGET('mail:' . $message_id, $email_id);
                $redis->SADD('global:blacklist', $email);

                return $app['twig']->render('blacklist_ok.html.twig', array('email' => $email));
            }
            else {
                return $app['twig']->render('blacklist_error.html.twig', array());
            }
		})
        ->assert('message_id', '\w+')
        ->assert('email_id', '\w+')
		->bind('blacklist_add');

        /**
         * Request blacklist removal
         */
        $controllers->match('/remove', function (Request $request) use ($app, $redis) {

            $twigData = [];

            // form wiyh just an email field
            $form = $app['form.factory']->createBuilder('form')
                ->add('email', 'text', array(
                    'constraints' => new Assert\Email()
                ))
                ->getForm();

            if ($request->isMethod('POST')) {

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $twigData['valid'] = true;

                    if ($redis->SISMEMBER('global:blacklist', $data['email'])) {
                        $email_id = uniqid();
                        $redis->SETEX('mail:removal:' . $email_id, $app['config']['blacklist']['message_blacklist_ttl'], $data['email']);

                        $message = \Swift_Message::newInstance()
                            ->setSubject('PloufPlouf - Blacklist removal')
                            ->setFrom('noreply@funkybits.fr')
                            ->setTo($data['email'])
                            ->setContentType('text/html')
                            ->setBody($app['twig']->render('email_blacklist.html.twig', ['email_id' => $email_id])
                            );

                        $app['mailer']->send($message);

                        unset($message);
                    }
                }
            }

            return $app['twig']->render('blacklist_remove.html.twig', array_merge(['form' => $form->createView()], $twigData));
        })
        ->method('GET|POST')
        ->bind('blacklist_remove');

        /**
         * Blacklist removal from email link
         */
        $controllers->get('/remove/confirm/{email_id}', function ($email_id) use ($app, $redis) {

            if ($redis->EXISTS('mail:removal:' . $email_id)) {
                $email = $redis->GET('mail:removal:' . $email_id);
                $redis->DEL('mail:removal:' . $email_id);
                $redis->SREM('global:blacklist', $email);

                return $app['twig']->render('blacklist_removal_ok.html.twig', array('email' => $email));
            }
            else {
                return $app['twig']->render('blacklist_removal_error.html.twig', array());
            }
        })
        ->assert('email_id', '\w+')
        ->bind('blacklist_remove_confirm');

		return $controllers;
    }
}