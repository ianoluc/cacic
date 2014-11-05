<?php

namespace Cacic\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * 
 * @author LightBase
 *
 */
class SecurityController extends Controller
{
	
	/**
	 * 
	 * Tela de login do CACIC
	 */
	public function loginAction(Request $request)
	{
		//$objRequest = $this->getRequest();
        $logger = $this->get('logger');
        $objRequest = $request;
        $objSession = $request->getSession();

        # Recupera a mensagem de erro, se existir
        if ( $objRequest->attributes->has( SecurityContext::AUTHENTICATION_ERROR ) )
        {
            $error = $objRequest->attributes->get( SecurityContext::AUTHENTICATION_ERROR );
        }
        else
        {
            $error = $objSession->get( SecurityContext::AUTHENTICATION_ERROR ); // Recupera a mensagem de erro da sessão
            $objSession->remove( SecurityContext::AUTHENTICATION_ERROR ); // Apaga a mensagem de erro da sessão
        }

        $logger->error("Erro de autenticação \n".$error);

        return $this->render(
            'CacicCommonBundle:Security:login.html.twig',
            array(
                'last_username' => $objSession->get( SecurityContext::LAST_USERNAME ), // último nome de usuário informado no formulário
                'error'         => $error,
                'url' => $_SERVER['SERVER_ADDR'],
            )
        );
	}
	
}