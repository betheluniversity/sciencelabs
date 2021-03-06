<?php

namespace Bethel\EntityBundle\EventListener;

use Bethel\EntityBundle\Entity\User;
use Bethel\WsapiBundle\Wsapi\WsRestApi;
use Doctrine\ORM\Event\LifecycleEventArgs;

class UserListener {
    protected $wsapi;

    public function __construct(WsRestApi $wsapi) {
        $this->wsapi = $wsapi;
    }

    public function prePersist(User $user, LifecycleEventArgs $args) {
        $names = $this->wsapi->getNames($user->getUsername());
        $em = $args->getEntityManager();
        /** @var $user \Bethel\EntityBundle\Entity\User */
        $user = $args->getEntity();

        if(count($names) > 0) {
            $firstName = $names[0]['firstName'];
            $lastName = $names[0]['lastName'];
            $preferredFirst = $names[0]['prefFirstName'];
            $firstName = $preferredFirst ? $preferredFirst : $firstName;

            if($firstName || $lastName) {
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
            }
        }
    }
}
