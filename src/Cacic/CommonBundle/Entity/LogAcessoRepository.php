<?php

namespace Cacic\CommonBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * LogAcessoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LogAcessoRepository extends EntityRepository
{
    /**
     * Função que retorna o último acesso para o computador solicitado
     *
     * @param $computador
     */
    public function ultimoAcesso( $computador ) {
        $qb = $this->createQueryBuilder('acesso')
            ->select('acesso')
            ->where('acesso.idComputador = :computador')
            ->orderBy('acesso.data', 'desc')
            ->setMaxResults(1)
            ->setParameter('computador', $computador );

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     *
     * Realiza pesquisa por LOGs de ACESSO ou ATIVIDADES segundo parâmetros informados
     * @param string|array $tipoPesquisa
     * @param date $dataInicio
     * @param date $dataFim
     * @param array $locais
     */
    public function pesquisar( $dataInicio, $dataFim, $locais )
    {

        // Monta a Consulta básica...
        $query = $this->createQueryBuilder('log')
            ->select('rede.idRede', 'rede.nmRede', 'rede.teIpRede', 'loc.nmLocal', 'loc.sgLocal', 'COUNT(DISTINCT comp.teNodeAddress) as numComp')
            ->innerJoin('log.idComputador', 'comp')
            ->innerJoin('comp.idRede', 'rede')
            ->innerJoin('rede.idLocal', 'loc')
            ->groupBy('rede', 'loc.nmLocal', 'loc.sgLocal');

        /**
         * Verifica os filtros que foram parametrizados
         */
        if ( $dataInicio )
            $query->andWhere( 'log.data >= :dtInicio' )->setParameter('dtInicio', ( $dataInicio.' 00:00:00' ));

        if ( $dataFim )
            $query->andWhere( 'log.data <= :dtFim' )->setParameter('dtFim', ( $dataFim.' 23:59:59' ));

        if ( count($locais) )
            $query->andWhere( 'loc.idLocal IN (:locais)' )->setParameter('locais', $locais);


        return $query->getQuery()->execute();
    }


    public function gerarRelatorioRede( $filtros, $idRede, $dataInicio, $dataFim )
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('te_node_address', 'teNodeAddress');
        $rsm->addScalarResult('id_computador', 'idComputador');
        $rsm->addScalarResult('te_ip_computador', 'teIpComputador');
        $rsm->addScalarResult('nm_computador', 'nmComputador');
        $rsm->addScalarResult('id_so', 'idSo');
        $rsm->addScalarResult('sg_so', 'sgSo');
        $rsm->addScalarResult('id_rede', 'idRede');
        $rsm->addScalarResult('nm_rede', 'nmRede');
        $rsm->addScalarResult('te_ip_rede', 'teIpRede');
        $rsm->addScalarResult('max_data', 'data');
        $rsm->addScalarResult('nm_local', 'nmLocal');
        $rsm->addScalarResult('id_local', 'idLocal');

        $sql = "
SELECT c0_.te_node_address AS te_node_address,
	string_agg(DISTINCT CAST(c0_.id_computador AS text), ', ') as id_computador,
	string_agg(DISTINCT c0_.te_ip_computador, ', ') as te_ip_computador,
	string_agg(DISTINCT c0_.nm_computador, ', ') AS nm_computador,
	string_agg(DISTINCT CAST(s2_.id_so AS text), ', ') AS id_so,
	string_agg(DISTINCT s2_.sg_so, ', ') AS sg_so,
	string_agg(DISTINCT CAST(r3_.id_rede AS text), ', ') AS id_rede,
	string_agg(DISTINCT r3_.nm_rede, ', ') AS nm_rede,
	string_agg(DISTINCT r3_.te_ip_rede, ', ') AS te_ip_rede,
	max(l1_.data) AS max_data,
	l4_.nm_local AS nm_local,
	l4_.id_local AS id_local
FROM log_acesso l1_
INNER JOIN computador c0_ ON l1_.id_computador = c0_.id_computador
INNER JOIN so s2_ ON c0_.id_so = s2_.id_so
INNER JOIN rede r3_ ON c0_.id_rede = r3_.id_rede
INNER JOIN local l4_ ON r3_.id_local = l4_.id_local
WHERE  1 = 1
";

        /**
         * Verifica os filtros que foram parametrizados
         */
        if ( $dataInicio ) {
            $sql .= " AND l1_.data >= ? ";
        }

        if ( $dataFim ) {
            $sql .= " AND l1_.data <= ?";
        }

        if ( $idRede ) {
            $sql .= " AND c0_.id_rede IN (?)";
        }

        $sql .= "
GROUP BY c0_.te_node_address,
	l4_.nm_local,
	l4_.id_local
        ";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        /**
         * Verifica os filtros que foram parametrizados
         */
        if ( $dataInicio ) {
            $query->setParameter(1, ( $dataInicio.' 00:00:00' ));
        }


        if ( $dataFim )
            $query->setParameter(2, ( $dataFim.' 23:59:59' ));

        if ( $idRede )
            $query->setParameter(3, $idRede);


        return $query->execute();
    }

    /**
     *
     * Total de computadores monitorados nos últimos 30 dias
     *
     */

    public function countPorComputador() {

        $query = $this->createQueryBuilder('log')
            ->select('COUNT(DISTINCT comp.teNodeAddress)')
            ->innerJoin('CacicCommonBundle:Computador','comp', 'WITH', 'log.idComputador = comp.idComputador')
            ->andWhere( 'log.data >= (current_date() - 30)' );

        $query = $query->getQuery();
        $query->useResultCache(true);
        $query->setResultCacheLifetime(600);

        return $query->execute();
    }

    public function faturamentoCsv( $dataInicio, $dataFim, $locais )
    {

        // Monta a Consulta básica...
        $query = $this->createQueryBuilder('log')
            ->select( 'loc.nmLocal', 'rede.nmRede', 'rede.teIpRede', 'COUNT(DISTINCT comp.teNodeAddress) as numComp')
            ->innerJoin('log.idComputador', 'comp')
            ->innerJoin('comp.idRede', 'rede')
            ->innerJoin('rede.idLocal', 'loc')
            ->groupBy('rede', 'loc.nmLocal', 'loc.sgLocal');

        /**
         * Verifica os filtros que foram parametrizados
         */
        if ( $dataInicio )
            $query->andWhere( 'log.data >= :dtInicio' )->setParameter('dtInicio', ( $dataInicio.' 00:00:00' ));

        if ( $dataFim )
            $query->andWhere( 'log.data <= :dtFim' )->setParameter('dtFim', ( $dataFim.' 23:59:59' ));

        if ( count($locais) )
            $query->andWhere( 'loc.idLocal IN (:locais)' )->setParameter('locais', $locais);


        return $query->getQuery()->execute();
    }

    public function listarCsv( $filtros, $idRede, $dataInicio, $dataFim )
    {

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('nm_computador', 'nmComputador');
        $rsm->addScalarResult('te_node_address', 'teNodeAddress');
        $rsm->addScalarResult('te_ip_computador', 'teIpComputador');
        $rsm->addScalarResult('sg_so', 'sgSo');
        $rsm->addScalarResult('nm_local', 'nmLocal');
        $rsm->addScalarResult('nm_rede', 'nmRede');
        $rsm->addScalarResult('max_data', 'data');

        $sql = "
SELECT c0_.te_node_address AS te_node_address,
	string_agg(DISTINCT c0_.te_ip_computador, ', ') as te_ip_computador,
	string_agg(DISTINCT c0_.nm_computador, ', ') AS nm_computador,
	string_agg(DISTINCT s2_.sg_so, ', ') AS sg_so,
	string_agg(DISTINCT r3_.nm_rede, ', ') AS nm_rede,
	max(l1_.data) AS max_data,
	l4_.nm_local AS nm_local
FROM log_acesso l1_
INNER JOIN computador c0_ ON l1_.id_computador = c0_.id_computador
INNER JOIN so s2_ ON c0_.id_so = s2_.id_so
INNER JOIN rede r3_ ON c0_.id_rede = r3_.id_rede
INNER JOIN local l4_ ON r3_.id_local = l4_.id_local
WHERE  1 = 1
";

        /**
         * Verifica os filtros que foram parametrizados
         */
        if ( $dataInicio ) {
            $sql .= " AND l1_.data >= ? ";
        }

        if ( $dataFim ) {
            $sql .= " AND l1_.data <= ?";
        }

        if ( $idRede ) {
            $sql .= " AND c0_.id_rede IN (?)";
        }

        $sql .= "
GROUP BY c0_.te_node_address,
	l4_.nm_local,
	l4_.id_local
        ";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        /**
         * Verifica os filtros que foram parametrizados
         */
        if ( $dataInicio ) {
            $query->setParameter(1, ( $dataInicio.' 00:00:00' ));
        }


        if ( $dataFim )
            $query->setParameter(2, ( $dataFim.' 23:59:59' ));

        if ( $idRede )
            $query->setParameter(3, $idRede);


        return $query->execute();
    }

}
