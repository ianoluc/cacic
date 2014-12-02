<?php

namespace Cacic\CommonBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Formulário para cadastro do Patrimônio
 *
 * Class PatrimonioType
 * @package Cacic\CommonBundle\Form\Type
 */
class PatrimonioType extends AbstractType
{

    public function buildForm( FormBuilderInterface $builder, array $options )
    {

        $builder->add(
            'computerName',
            null,
            array(
                'label'=>'Nome do computador'
            )
        );
        $builder->add(
            'Coordenacao_Setor',
            null,
            array(
                'label'=>'Coordenaçao/Setor'
            )
        );
        $builder->add(
            'IDPatrimonio',
            null,
            array(
                'label'=>'Numero do Patrimonio'
            )
        );
        $builder->add(
            'IPComputer',
            null,
            array(
                'label' => 'IP do Computador'
            )
        );
        $builder->add(
            'PatrimonioMonitor1',
            null,
            array(
                'label' => 'Patrimonio do Monitor 1'
            )
        );
        $builder->add(
            'PatrimonioMonitor2',
            null,
            array(
                'label' => 'Patrimonio do Monitor 2'
            )
        );
        $builder->add(
            'Sala',
            null,
            array(
                'label' => 'Número da sala'
            )
        );
        $builder->add(
            'UserLogado',
            null,
            array(
                'label' => 'CPF do Responsável'
            )
        );

        $builder->add(
            'UserName',
            null,
            array(
                'label' => 'Nome do Responsável'
            )
        );

    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'patrimonio';
    }

}