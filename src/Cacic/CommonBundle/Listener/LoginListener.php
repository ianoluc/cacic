<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 01/12/14
 * Time: 13:32
 */

namespace Cacic\CommonBundle\Listener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Cacic\CommonBundle\Entity\Log;


/**
 * Custom login listener.
 */
class LoginListener
{
    /** @var \Symfony\Component\Security\Core\SecurityContext */
    private $securityContext;
    /** @var \Doctrine\ORM\EntityManager */
    private $em;
    /**
     * Constructor
     *
     * @param SecurityContext $securityContext
     * @param Doctrine $doctrine
     */
    public function __construct(SecurityContext $securityContext, Doctrine $doctrine)
    {
        $this->securityContext = $securityContext;
        $this->em = $doctrine->getManager();
    }
    /**
     * Do the magic.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            // user has just logged in
        }
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            // user has logged in using remember_me cookie
        }
        // do some other magic here
        $session = $event->getRequest()->getSession();
        $referrer = $session->get('referrer');
        if (empty($referrer)) {
            $referrer = $event->getRequest()->getUri();
        }

        $user = $event->getAuthenticationToken()->getUser();
        $log = new Log();
        $log->setIdUsuario($user);
        $log->setDtAcao(new \DateTime());
        $log->setNmTabela('acesso');
        $log->setCsAcao('ACE');
        $log->setTeIpOrigem($event->getRequest()->getClientIp());
        $log->setNmScript($referrer);

        // Registra login
        $this->em->persist($log);
        $this->em->flush();

        // TODO: Redireciona para útima página visitada
    }
} 