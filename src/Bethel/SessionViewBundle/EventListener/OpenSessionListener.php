<?php
/**
 * Created by PhpStorm.
 * User: pms63443
 * Date: 2/9/15
 * Time: 11:35 AM
 */

namespace Bethel\SessionViewBundle\EventListener;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class OpenSessionListener {

    private $cookieName;
    private $logoutUrl;

    public function __construct($cookieName, $logoutUrl) {
        $this->cookieName = $cookieName;
        $this->logoutUrl = $logoutUrl;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();
        $pathArray = explode('/', ltrim($request->getPathInfo(), '/'));

        if(!($pathArray[0] == 'session' && ($pathArray[1] == 'open' || $pathArray[1] == 'checkout')) && !$request->cookies->get($this->cookieName)) {
            // Force CAS auth if we don't have a session cookie
            $redirectUrl = $this->logoutUrl . '?gateway=true&service=' .
                urlencode($request->getRequestUri());
            $response = new RedirectResponse($redirectUrl);
            $event->setResponse($response);
        }

    }

    public function onKernelResponse(FilterResponseEvent $event) {

        if(!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $pathArray = explode('/', ltrim($request->getPathInfo(), '/'));

        // Check if we're somewhere under open sessions
        if($pathArray[0] == 'session' && $pathArray[1] == 'open' && $request->cookies->get($this->cookieName)) {
            // Wipe out our session and cookie
            $userSession = $request->getSession();
            $userSession->invalidate();
            $response->headers->clearCookie($this->cookieName);
            return;
        }
    }
}