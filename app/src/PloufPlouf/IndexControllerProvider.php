<?php

namespace PloufPlouf;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;

class IndexControllerProvider extends BaseControllerProvider
{

    public function connect(Application $app)
    {
        parent::connect($app);

        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        /**
         * Landing page
         */
        $controllers->get('/', function (Request $request) use ($app) {
            $this->menu('home');

            $redis = $app['predis'];
            $picked = $redis->GET('stats:picked');
            if (is_null($picked)) { $picked = 0; }

			return $app['twig']->render('home.html.twig', array('picked' => $redis->GET('stats:picked')));
		})
		->bind('home');

		/**
		 * App page
         * @note currently load both form & success templates so we avoid additional http request later
		 */
		$controllers->get('/app', function (Request $request) use ($app) {
            $this->menu('app');

			return $app['twig']->render('app.html.twig', array());
		})
		->bind('app');

        /**
         * Submit
         * @todo refactor in services to get a slimmer controller
         * @return JsonResponse
         */
        $controllers->post('/submit', function (Request $request) use ($app) {

            $redis = $app['predis'];

            // create form
            $form = $app['form.factory']
                ->createBuilder(new DilemmaType(), null, ['csrf_protection' => false])
                ->getForm()
                ->handleRequest($request);

            // valid form
            if ($form->isValid()) {
                $data = $form->getData();

                // check that user has past the delay between (successful) picks (only picks w/ with emails) (if any)
                // we use a session id and ip to track user
                if ((!empty($data['email'])) || (count($data['emails']))) {
                    // if key exists in redis, delay is not met
                    if (($redis->GET('last_pick:session:' . $app['uid']) !== null) || ($redis->GET('last_pick:ip:' . $request->getClientIp()) !== null)) {
                        $this->returnError(
                            $app['translator']->trans('misc.timelimit', array('%delay%' => $app['config']['blacklist']['delay_between_pick_session'])),
                            $app
                        ); // we only display the session delay
                    }
                }

                /** @var Randomizer $randomizer */
                $randomizer = $app['randomizer'];
                $choices = $data['choices'];
                $pickedId = $randomizer->pick($choices);

                // pick is ok
                if ($pickedId !== false) {

                    // increase global counter
                    $redis->INCR('stats:picked');

                    $pickedValue = $choices[$pickedId];

                    $return = [
                        'status' => 'SUCCESS',
                        'picked_id' => $pickedId,
                        'picked_value' => $pickedValue
                    ];

                    // send mail(s)
                    if (!empty($data['email'])) {

                        $redis->INCR('stats:picked:with_emails');

                        /** @var Mailer $emailer */
                        $emailer = $app['emailer'];

                        // send mail & get how many has been sent (blacklist is managed in the class)
                        $numberOfEmailSent = $emailer->send(array_merge(
                            ['picked_id' => $pickedId, 'picked_value' => $pickedValue],
                            $data)
                        );

                        $redis->INCRBY('stats:picked:emails_sent', $numberOfEmailSent);
                        $return['emails'] = $numberOfEmailSent;

                        // if emails have been sent, flag the user to prevent another pick before the allowed delay
                        if ($numberOfEmailSent > 0) {
                            $redis->SETEX('last_pick:session:' . $app['uid'], $app['config']['blacklist']['delay_between_pick_session'], 'pick done');
                            $redis->SETEX('last_pick:ip:' . $request->getClientIp(), $app['config']['blacklist']['delay_between_pick_ip'], 'pick done');
                        }
                    }

                    return $app->json($return);
                }
                /** unknown error while picking
                 * @todo manage global errors in angular side (currently only form errors are displayed
                 */
                else { return $this->returnError($app['translator']->trans('misc.error'), $app); }
            }
            // invalid form
            else {

                return $this->returnError(
                    $this->getErrorMessages($form),
                    $app,
                    400
                );
            }
        })
        ->bind('submit');

		return $controllers;
    }
}