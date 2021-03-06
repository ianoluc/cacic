<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 20/04/15
 * Time: 11:40
 */

namespace Cacic\WSBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Cacic\CommonBundle\Entity\ClassProperty;
use Cacic\CommonBundle\Entity\Software;
use Cacic\CommonBundle\Entity\ComputadorColeta;
use Cacic\CommonBundle\Entity\ComputadorColetaHistorico;
use Cacic\CommonBundle\Entity\PropriedadeSoftware;
use Doctrine\ORM\ORMException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMInvalidArgumentException;

class NeoColetaController extends NeoController {

    public function coletaAction(Request $request) {
        $logger = $this->get('logger');
        $status = $request->getContent();
        $em = $this->getDoctrine()->getManager();
        $dados = json_decode($status, true);

        if (empty($dados)) {
            $logger->error("JSON INVÁLIDO!!!!!!!!!!!!!!!!!!! Erro na COLETA");
            // Retorna erro se o JSON for inválido
            $error_msg = '{
                "message": "JSON Inválido",
                "codigo": 1
            }';


            $response = new JsonResponse();
            $response->setStatusCode('500');
            $response->setContent($error_msg);
            return $response;
        }

        $computador = $this->getComputador($dados, $request);

        if (empty($computador)) {
            // Se não identificar o computador, manda para o getUpdate
            $logger->error("Computador não identificado no getConfig. Necessário executar getUpdate");

            $error_msg = '{
                "message": "Computador não identificado",
                "codigo": 2
            }';


            $response = new JsonResponse();
            $response->setStatusCode('500');
            $response->setContent($error_msg);
            return $response;
        }

        //Verifica se a coleta foi forçada
        if ($computador->getForcaColeta() == 'true') {
            $computador->setForcaColeta('false');
            $this->getDoctrine()->getManager()->persist( $computador );
            $this->getDoctrine()->getManager()->flush();
        }

        $result1 = $this->setHardware($dados['hardware'], $computador);
        $result2 = $this->setSoftware($dados['software'], $computador);

        $response = new JsonResponse();
        if ($result1 && $result2) {
            $response->setStatusCode('200');
        } else {
            $response->setStatusCode('500');
        }

        return $response;

    }

    /**
     * Classe que persiste a coleta de hardware (WMI)
     *
     * @param $hardware
     * @param $computador
     * @return bool
     */

    public function setHardware($hardware, $computador) {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // Pega todas as propriedades de coleta
        foreach ($hardware as $classe => $valor) {
            $logger->debug("COLETA: Gravando dados da classe $classe");
            $this->setClasse($classe, $valor, $computador);
        }

        return true;
    }

    /**
     * Classe que grava todas as propriedades da classe de coleta
     *
     * @param $classe
     * @param $computador
     */
    public function setClasse($classe, $valor, $computador) {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        $classObject = $em->getRepository('CacicCommonBundle:Classe')->findByName($classe);

        $logger->debug("COLETA: Coletando classe $classe");

        // Adiciona isNotebook
        if ($classe == 'IsNotebook') {
            $logger->debug("Valor do isNotebook: ".print_r($valor, true));

            $computador->setIsNotebook($valor['Value']);

            $em->persist( $computador );
            $em->flush();

            return;
        }

        if (empty($classObject)) {
            $logger->debug("COLETA: Classe não cadastrada: $classe");
            return;
        }

        // Trata classe multivalorada
        if (!empty($valor[0])) {
            // Nesse caso a classe é multivalorada. Considero somente a primeira ocorrência
            $logger->debug("COLETA: Classe $classe multivalorada. Retornando somente primeiro elemento.");
            $valor = $valor[0];
        }

        // Eduardo: 2015-02-05
        // Verifica se o JSON com propriedades é válido
        $propriedades_array = @array_keys($valor);
        if (empty($propriedades_array)) {
            $logger->error("COLETA: erro na coleta da classe $classe. String retornada quando deveria ser um objeto JSON: ".print_r($valor, true));
            return;
        }

        foreach ($propriedades_array as $propriedade) {
            // Necessário pegar o EM novamente se estiver dando erro
            $em = $this->getDoctrine()->getManager();

            if (is_array($valor[$propriedade])) {
                $logger->debug("COLETA: Atributo $propriedade multivalorado não implementado na coleta");
                //$logger->debug("1111111111111111111111111111111111111111 ".print_r($valor, true));
                $valor[$propriedade] = $valor[$propriedade][0];
                //continue;
            }
            $logger->debug("COLETA: Gravando dados da propriedade $propriedade com o valor ".$valor[$propriedade]);

            try {

                // Pega o objeto para gravar
                $classProperty = $em->getRepository('CacicCommonBundle:ClassProperty')->findOneBy( array(
                    'nmPropertyName'=> $propriedade,
                    'idClass' => $classObject
                ));

                if (empty($classProperty)) {
                    $logger->info("COLETA: Cadastrando propriedade não encontrada $propriedade");

                    $classProperty = new ClassProperty();
                    $classProperty->setIdClass($classObject);
                    $classProperty->setNmPropertyName($propriedade);
                    $classProperty->setTePropertyDescription("Propriedade criada automaticamente: $propriedade");
                    $classProperty->setAtivo(true);

                    $em->persist($classProperty);
                    $em->flush();
                } else {
                    $ativo = $classProperty->getAtivo();
                    //$logger->debug("11111111111111111111111111111111111111111: |$ativo| |$propriedade|");
                    if ($ativo === false) {
                        // Só vou gravar a coleta se a propriedade estiver ativa
                        $logger->debug("COLETA: Pulando propriedade inativa: $propriedade");
                        continue;
                    }
                }

                // Garante unicidade das informações de coleta
                $computadorColeta = $em->getRepository('CacicCommonBundle:ComputadorColeta')->findOneBy(array(
                    'computador' => $computador,
                    'classProperty' => $classProperty
                ));
                if(empty($computadorColeta)) {
                    //$logger->debug("COLETA: Registrando nova coleta para o software $software no computador ".$computador->getIdComputador());

                    // Armazena no banco o objeto
                    $computadorColeta = new ComputadorColeta();
                    $computadorColeta->setComputador( $computador );
                    $computadorColeta->setClassProperty($classProperty);
                    $computadorColeta->setTeClassPropertyValue($valor[$propriedade]);
                    $computadorColeta->setIdClass($classObject);
                    $computadorColeta->setDtHrInclusao( new \DateTime() );
                    $computadorColeta->setAtivo(true);
                    $computadorColeta->setDtHrExclusao(null);

                    $em->persist( $computadorColeta );

                    // Pega novo computador gerado no computador coleta
                    $computador = $computadorColeta->getComputador();

                    // Gravo um histórico
                    $computadorColetaHistorico = new ComputadorColetaHistorico();
                    $computadorColetaHistorico->setComputadorColeta( $computadorColeta );
                    $computadorColetaHistorico->setComputador( $computador );
                    $computadorColetaHistorico->setClassProperty( $classProperty);
                    $computadorColetaHistorico->setTeClassPropertyValue( $valor[$propriedade] );
                    $computadorColetaHistorico->setDtHrInclusao( new \DateTime() );

                    $em->persist( $computadorColetaHistorico );
                } elseif ($computadorColeta->getTeClassPropertyValue() != $valor[$propriedade]) {

                    // Armazena no banco o objeto
                    $computadorColeta->setComputador( $computador );
                    $computadorColeta->setClassProperty($classProperty);
                    $computadorColeta->setTeClassPropertyValue($valor[$propriedade]);
                    $computadorColeta->setIdClass($classObject);
                    $computadorColeta->setDtHrInclusao( new \DateTime() );
                    $computadorColeta->setAtivo(true);
                    $computadorColeta->setDtHrExclusao(null);

                    $em->persist( $computadorColeta );

                    // Pega novo computador gerado no computador coleta
                    $computador = $computadorColeta->getComputador();

                    // Aqui é necessário uma entrada no histórico
                    $computadorColetaHistorico = new ComputadorColetaHistorico();
                    $computadorColetaHistorico->setComputadorColeta( $computadorColeta );
                    $computadorColetaHistorico->setComputador( $computador );
                    $computadorColetaHistorico->setClassProperty( $classProperty);
                    $computadorColetaHistorico->setTeClassPropertyValue( $valor[$propriedade] );
                    $computadorColetaHistorico->setDtHrInclusao( new \DateTime() );

                    $em->persist( $computadorColetaHistorico );
                }

                // Persiste os objetos dependentes para evitar erro no ORM
                $em->persist($computador);
                $em->persist($classObject);

            } catch(ORMException $e){
               // Reopen Entity Manager
               if (!$em->isOpen()) {

                   // reset the EM and all alias
                   $container = $this->container;
                   $container->set('doctrine.orm.entity_manager', null);
                   $container->set('doctrine.orm.default_entity_manager', null);
               }

                $logger->error("COLETA: Erro na inserção de dados da propriedade $propriedade.");
                $logger->debug($e);
            }
        }
        // Grava tudo da propriedade
        $em->flush();
    }

    public function setSoftware($software, $computador) {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();


        $classObject = $em->getRepository('CacicCommonBundle:Classe')->findOneBy( array(
            'nmClassName'=> 'SoftwareList'
        ));

        if (empty($classObject)) {
            $logger->error("COLETA: Classe SoftwareList não cadastrada");
            return false;
        }

        // Pega todas as propriedades de coleta
        $i = 0;
        foreach ($software as $classe => $valor) {
            $logger->debug("COLETA: Gravando dados do software $classe");
            $result = $this->setSoftwareElement($classe, $valor, $computador, $classObject);
            if (!$result) {
                // Limpa o cache se der erro
                $em->flush();
            }
            $i = $i + 1;
        }

        /*
         * Grava tudo
         */
        $em->flush();
        $logger->debug("COLETA: Coleta de software finalizada. Total de softwares coletados: $i");

        return true;
    }

    public function setSoftwareElement($software, $valor, $computador, $classObject) {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        if (empty($software)) {
            $logger->error("COLETA: Erro na coleta de software. Elemento nulo $software");
            return false;
        }

        try {

            // FIX: alteração para igualar os campos nome do software e descrição
            $idSoftware = $software;
            if (array_key_exists('description', $valor)) {
                $software = $valor['description'];
            }

            // Recupera classProperty para o software
            $classProperty = $em->getRepository('CacicCommonBundle:ClassProperty')->findOneBy(array(
                'idClass' => $classObject,
                'nmPropertyName' => $idSoftware
            ));
            if (empty($classProperty)) {

                $classProperty = new ClassProperty();
                $classProperty->setTePropertyDescription("Software detectado: $software");
                $classProperty->setNmPropertyName($idSoftware);
                $classProperty->setIdClass($classObject);
                $classProperty->setAtivo(true);

                // Se não der o flush aqui, as consultas de baixo não encontram o objeto
                $em->persist($classProperty);
                $em->flush();
            }

            // Landim: 2016-07-07
            // A coleta irá ignorar os softwares que estivem em relatório de exclusão, não mais a coluna ativo da entidade classProperty.
            //$ativo = $classProperty->getAtivo();
            //$logger->debug("11111111111111111111111111111111111111111: |$ativo| |$propriedade|");
            /*if ($ativo === false) {
                // Só vou gravar o software se a propriedade estiver ativa
                $logger->debug("COLETA: Pulando software inativo: $software");
                return false;
            }*/

            // Eduardo: 2015-08-06
            // A propriedade passa a ser um identificador para o Software
            $softwareObject = $em->getRepository('CacicCommonBundle:Software')->getByNameOrProperty(
                $software,
                $classProperty->getIdClassProperty()
            );
            if (empty($softwareObject)) {
                // Se nao existir, cria
                $logger->info("COLETA: Cadastrando software não encontrado $software");

                $softwareObject = new Software();
                $softwareObject->setNmSoftware($software);
                $softwareObject->setIdClassProperty($classProperty);

                // Se não der o flush aqui, as consultas de baixo não encontram o objeto
                $em->persist($softwareObject);
                $em->flush();
            }

            // Landim: 2016-07-07
            // Verifica se o sofware está em algum relatório de exclusão.
            foreach($softwareObject->getRelatorios() as $relatorio) {
                if($relatorio->getTipo() === "excluir") {
                        $logger->debug("COLETA: Pulando software que está em relatório de exclusão: $software");
                        return false;
                }
            }



            // Adiciona software ao computador
            $propSoftware = $em->getRepository('CacicCommonBundle:PropriedadeSoftware')->findOneBy(array(
                'classProperty' => $classProperty,
                'computador' => $computador,
                'software' => $softwareObject
            ));
            if (empty($propSoftware)) {

                $propSoftware = new PropriedadeSoftware();
                $propSoftware->setComputador($computador);
                $propSoftware->setSoftware($softwareObject);
                $propSoftware->setClassProperty($classProperty);
            }

            // Garante que o software deve estar ativo
            $propSoftware->setAtivo(true);
            $propSoftware->setDataExclusao(null);

            // Encontra coleta já feita para o Computador
            $computadorColeta = $em->getRepository('CacicCommonBundle:ComputadorColeta')->findOneBy(array(
                'computador' => $computador,
                'classProperty' => $classProperty
            ));

            if(empty($computadorColeta)) {
                //$logger->debug("COLETA: Registrando nova coleta para o software $software no computador ".$computador->getIdComputador());

                // Armazena no banco o objeto
                $computadorColeta = new ComputadorColeta();
                $computadorColeta->setComputador( $computador );
                $computadorColeta->setClassProperty($classProperty);
                $computadorColeta->setTeClassPropertyValue($software);
                $computadorColeta->setIdClass($classObject);
                $computadorColeta->setDtHrInclusao( new \DateTime() );
                $computadorColeta->setAtivo(true);
                $computadorColeta->setDtHrExclusao(null);

                $em->persist( $computadorColeta );

                // Pega novo computador gerado no computador coleta
                $computador = $computadorColeta->getComputador();

                // Gravo um histórico
                $computadorColetaHistorico = new ComputadorColetaHistorico();
                $computadorColetaHistorico->setComputadorColeta( $computadorColeta );
                $computadorColetaHistorico->setComputador( $computador );
                $computadorColetaHistorico->setClassProperty( $classProperty);
                $computadorColetaHistorico->setTeClassPropertyValue( $software );
                $computadorColetaHistorico->setDtHrInclusao( new \DateTime() );

                $em->persist( $computadorColetaHistorico );
            } elseif ($computadorColeta->getTeClassPropertyValue() != $software) {

                // Armazena no banco o objeto
                $computadorColeta->setComputador( $computador );
                $computadorColeta->setClassProperty($classProperty);
                $computadorColeta->setTeClassPropertyValue($software);
                $computadorColeta->setIdClass($classObject);
                $computadorColeta->setDtHrInclusao( new \DateTime() );
                $computadorColeta->setAtivo(true);
                $computadorColeta->setDtHrExclusao(null);

                $em->persist( $computadorColeta );

                // Pega novo computador gerado no computador coleta
                $computador = $computadorColeta->getComputador();

                // Aqui é necessário uma entrada no histórico
                $computadorColetaHistorico = new ComputadorColetaHistorico();
                $computadorColetaHistorico->setComputadorColeta( $computadorColeta );
                $computadorColetaHistorico->setComputador( $computador );
                $computadorColetaHistorico->setClassProperty( $classProperty);
                $computadorColetaHistorico->setTeClassPropertyValue( $software );
                $computadorColetaHistorico->setDtHrInclusao( new \DateTime() );

                $em->persist( $computadorColetaHistorico );
            }

            // Atualiza valores do Software
            // $softwareObject->setNmSoftware($software);
            if (array_key_exists('description', $valor)) {
                $softwareObject->setTeDescricaoSoftware($valor['description']);
                $propSoftware->setDisplayName($valor['description']);
                $classProperty->setPrettyName($valor['description']);
            }

            /*
            if (array_key_exists('name', $valor)) {
                $classProperty->setNmPropertyName($valor['name']);
            }
            */

            if (array_key_exists('url', $valor)) {
                $propSoftware->setUrlInfoAbout($valor['url']);
            }
            if (array_key_exists('version', $valor)) {
                $propSoftware->setDisplayVersion($valor['version']);
            }
            if (array_key_exists('publisher', $valor)) {
                $propSoftware->setPublisher($valor['publisher']);
            }

            // Persiste os objetos
            $em->persist($propSoftware);

            // Tem que adicionar isso aqui ou o Doctrine vai duplicar o software
            $em->flush();

        } catch(ORMException $e){
            // Reopen Entity Manager
            if (!$em->isOpen()) {

                // reset the EM and all alias
                $container = $this->container;
                $container->set('doctrine.orm.entity_manager', null);
                $container->set('doctrine.orm.default_entity_manager', null);
            }

            $logger->error("COLETA: Erro na inserçao de dados do software $software.");
            $logger->debug($e);

            return false;
        } catch(DBALException $e){
            // Reopen Entity Manager
            if (!$em->isOpen()) {

                // reset the EM and all alias
                $container = $this->container;
                $container->set('doctrine.orm.entity_manager', null);
                $container->set('doctrine.orm.default_entity_manager', null);
            }

            $logger->error("COLETA: Erro impossível de software repetido para $software.");
            $logger->debug($e);

            return false;
        }

        return true;
    }

    /**
     * Coleta diferencial
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function modificationsAction(Request $request) {
        $logger = $this->get('logger');
        $status = $request->getContent();
        $em = $this->getDoctrine()->getManager();
        $dados = json_decode($status, true);

        if (empty($dados)) {
            $logger->error("JSON INVÁLIDO!!!!!!!!!!!!!!!!!!! Erro na COLETA");
            // Retorna erro se o JSON for inválido
            $error_msg = '{
                "message": "JSON Inválido",
                "codigo": 1
            }';


            $response = new JsonResponse();
            $response->setStatusCode('500');
            $response->setContent($error_msg);
            return $response;
        }

        $computador = $this->getComputador($dados, $request);

        if (empty($computador)) {
            // Se não identificar o computador, manda para o getUpdate
            $logger->error("Computador não identificado no getConfig. Necessário executar getUpdate");

            $error_msg = '{
                "message": "Computador não identificado",
                "codigo": 2
            }';


            $response = new JsonResponse();
            $response->setStatusCode('500');
            $response->setContent($error_msg);
            return $response;
        }

        //Verifica se a coleta foi forçada
        if ($computador->getForcaColeta() == 'true') {
            $computador->setForcaColeta('false');
            $this->getDoctrine()->getManager()->persist( $computador );
            $this->getDoctrine()->getManager()->flush();
        }

        $result1 = true;
        if (array_key_exists('coletasInseridas', $dados)) {
            $result1 = $this->processaInseridas($dados['coletasInseridas'], $computador);
        }

        $result2 = true;
        if (array_key_exists('coletasRetiradas', $dados)) {
            $result2 = $this->processaRetiradas($dados['coletasRetiradas'], $computador);
        }

        $response = new JsonResponse();
        if ($result1 && $result2) {
            $response->setStatusCode('200');
        } else {
            $response->setStatusCode('500');
        }

        return $response;
    }

    /**
     * Processa propriedades inseridas
     * TODO: Por enquanto não tem utilidade. Pensar se é relevante em algum momento.
     *
     * @param $coletasInseridas
     * @param $computador
     * @return bool
     */
    public function processaInseridas($coletasInseridas, $computador) {
        // Por enquanto não faz nada com as coletas inseridas
        return true;
    }

    /**
     * Processa todas as coletas que foram identificadas como retiradas
     *
     * @param $coletasRetiradas
     * @param $computador
     * @return bool
     */
    public function processaRetiradas($coletasRetiradas, $computador) {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        if (array_key_exists('software', $coletasRetiradas)) {
            // Desativa os softwares no computador
            foreach ($coletasRetiradas['software'] as $software => $valor) {
                $logger->debug("MODIFICATIONS: Retirando dados do software $software para o computador ".$computador->getIdComputador());
                $this->retiraSoftware($software, $valor, $computador);
            }

            // NOTIFICAÇÕES
            $this->createNotification($computador, $coletasRetiradas['software'], 'Software');
        }

        if (array_key_exists('hardware', $coletasRetiradas)) {
            // Desativa os hardwares no computador
            foreach ($coletasRetiradas['hardware'] as $classe => $valor) {
                $logger->debug("MODIFICATIONS: Retirando dados da classe WMI $classe para o computador ".$computador->getIdComputador());
                $retira = $this->retiraClasse($classe, $valor, $computador);
                if(!$retira) {
                        unset($coletasRetiradas['hardware'][$classe]);
                }
            }

            // NOTIFICAÇÕES
            $this->createNotification($computador, $coletasRetiradas['hardware'], 'Hardware');
        }

        return true;
    }

    /**
     * Desativa o software para o valor fornecido
     *
     * @param $software
     * @param $valor
     * @param $computador
     * @return bool
     */
    public function retiraSoftware($software, $valor, $computador) {
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');

        $classObject = $em->getRepository('CacicCommonBundle:Classe')->findOneBy( array(
            'nmClassName'=> 'SoftwareList'
        ));

        $idSoftware = $software;
        /*if (array_key_exists('description', $valor)) {
            $software = $valor['description'];
        }*/

        $softwareObject = $em->getRepository('CacicCommonBundle:Software')->getByName($software);

        if (empty($softwareObject)) {
            // Se nao existir, cria
            $logger->error("MODIFICATIONS: Não foi possível encontrar o software $software. Não pode ser retirado porque não existe");

            return false;
        }

        // Recupera classProperty para o software
        $classProperty = $em->getRepository('CacicCommonBundle:ClassProperty')->findOneBy(array(
            'idClass' => $classObject->getIdClass(),
            'nmPropertyName' => $idSoftware
        ));
        if (empty($classProperty)) {
            $logger->error("MODIFICATIONS: O software $idSoftware não constitui uma propriedade da classe SoftwareList cadastrada. Não é possível retirar");

            return false;
        }

        // Procura software coletado no computador
        $propSoftware = $em->getRepository('CacicCommonBundle:PropriedadeSoftware')->findOneBy(array(
            'classProperty' => $classProperty,
            'software' => $softwareObject,
            'computador' => $computador
        ));

        if (empty($propSoftware)) {
            $logger->error("MODIFICATIONS: O software $software não foi encontrado no computador ".$computador->getIdComputador());

            return false;
        }

        // Se não falhou até aqui, desativa o software para o computador
        $propSoftware->setAtivo(false);
        $propSoftware->setDataExclusao(new \DateTime());

        // TODO: Inserir notificação aqui

        $em->persist($propSoftware);
        $em->flush();

        return true;
    }

    /**
     * Processa a classe a ser desativada
     *
     * @param $classe
     * @param $valor
     * @param $computador
     * @return bool
     */
    public function retiraClasse($classe, $valor, $computador) {
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');

        $classObject = $em->getRepository('CacicCommonBundle:Classe')->findOneBy( array(
            'nmClassName'=> $classe
        ));

        if($classe == 'Win32_SMBIOSMemory') {
                $logger->error("MODIFICATIONS: A classe $classe não está habilitada nas coletas. Não é possível registrar a remoção");
                return false;
        }

        if (empty($classObject)) {
            $logger->error("MODIFICATIONS: A classe $classe não está habilitada nas coletas. Não é possível registrar a remoção");

            return false;
        }

        // Trata classe multivalorada
        if (!empty($valor[0])) {
            // Nesse caso a classe é multivalorada. Considero somente a primeira ocorrência
            $logger->debug("MODIFICATIONS: Classe $classe multivalorada. Retornando somente primeiro elemento.");
            $valor = $valor[0];
        }

        // Eduardo: 2015-02-05
        // Verifica se o JSON com propriedades é válido
        $propriedades_array = @array_keys($valor);
        if (empty($propriedades_array)) {
            $logger->error("MODIFICATIONS: erro na coleta da classe $classe. String retornada quando deveria ser um objeto JSON: ".print_r($valor, true));
            return false;
        }

        foreach ($propriedades_array as $propriedade) {
            // Necessário pegar o EM novamente se estiver dando erro
            $em = $this->getDoctrine()->getManager();

            // Recupera classProperty para o software
            $classProperty = $em->getRepository('CacicCommonBundle:ClassProperty')->findOneBy(array(
                'idClass' => $classObject->getIdClass(),
                'nmPropertyName' => $propriedade
            ));

            if (empty($classProperty)) {
                $logger->error("MODIFICATIONS: Não foi possível encontrar o valor $propriedade para a classe $classe. Não é possível retirar");

                continue;
            }

            if (is_array($valor[$propriedade])) {
                $logger->debug("MODIFICATIONS: Atributo $propriedade multivalorado não implementado na coleta");
                //$logger->debug("1111111111111111111111111111111111111111 ".print_r($valor, true));
                $valor[$propriedade] = $valor[$propriedade][0];
                //continue;
            }
            $logger->debug("MODIFICATIONS: Retirando dados da propriedade $propriedade com o valor " . $valor[$propriedade]);

            $computadorColeta = $em->getRepository("CacicCommonBundle:ComputadorColeta")->findOneBy(array(
                'computador' => $computador,
                'classProperty' => $classProperty
            ));

            if (empty($computadorColeta)) {
                $logger->error("MODIFICATIONS: Não foi possível encontrar coletas da propriedade $propriedade para a classe $classe no computador ".$computador->getIdComputador());

                continue;
            }

            // Se chegou até aqui pode desativar
            $computadorColeta->setAtivo(false);
            $computadorColeta->setDtHrExclusao(new \DateTime());

            $em->persist($computadorColeta);
            $em->flush();
        }

        return true;
    }

    /**
     * Cria notificação para retirada de coleta
     *
     * @param $computador
     * @param $coletasRetiradas
     * @param $object
     * @return bool
     */
    public function createNotification($computador, $coletasRetiradas, $object) {
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');

        // Se for software, verifica se o software deve ser notificado
        if ($object == 'Software') {
            $coletasRetiradas = $this->softwareNotification($coletasRetiradas);

            if (empty($coletasRetiradas)) {
                return false;
            }
        }

        // Eduardo: 2016-02-12
        // MPOG pediu pra manter só o nome da classe
        $chaves = @array_keys($coletasRetiradas);
        if(sizeof($chaves) > 0) {
           $body = "Nome do Computador: ". $computador->getNmComputador() ."
             IP do Computador: ". $computador->getTeIpComputador() ."
             MAC: ". $computador->getTeNodeAddress() ."
             Rede: ". $computador->getIdRede()->getNmRede() ."

             Remoções identificadas: \n
             ". json_encode($chaves, JSON_PRETTY_PRINT) ."\n
            ";

          if ($this->container->hasParameter('mailer_from')) {
            $from = $this->getParameter('mailer_from');
          } else {
            $from = 'cacic@localhost';
         }

         // NOTIFICAÇÕES
         $notification = $computador->createNotification(
            'DEL',
            $object,
            "COLETA: $object removido",
            $body,
            $from
         );

         $em->persist($notification);
         $em->flush();
        }
        // Verifica se a notificação deve ser enviada por e-mail
        $email_notifications = $computador->getIdRede()->getIdLocal()->getConfiguracaoChave('email_notifications');

        if ($email_notifications == 'S') {
            // Envia notificações por e-mail somente se estiver explicitamente habilitado
            $message = \Swift_Message::newInstance()
                ->setSubject($notification->getSubject())
                ->setFrom($notification->getFromAddr())
                ->setTo($notification->getToAddr())
                ->setBody($notification->getBody());

            $this->get('mailer')->send($message);

        }

        return true;
    }

    /**
     * Verifica se os softwares retirados devem ser notificados
     *
     * @param $coletasRetiradas
     * @return array Retorna o array como com os softwares que devem ser notificados ou vazio
     */
    public function softwareNotification($coletasRetiradas) {
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');

        // Só insere de volta os softwares que devem ser notificados
        $saida = array();
        foreach (array_keys($coletasRetiradas) as $software) {
            $relatorio = $em->getRepository("CacicCommonBundle:SoftwareRelatorio")->findSoftware($software);

            if (!empty($relatorio)) {
                // Só notifica se o software estiver com a notificação habilitada explicitamente
                $habilita = $relatorio->getHabilitaNotificacao();
                if ($habilita === true) {
                    //$logger->debug("NOTIFICATIONS: Notificando retirada do software $software");
                    $saida[$software] = $coletasRetiradas[$software];
                }
            }
        }

        return $saida;
    }

}
