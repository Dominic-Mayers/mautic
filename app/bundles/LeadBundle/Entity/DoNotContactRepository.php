<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;

/**
 * @extends CommonRepository<DoNotContact>
 */
class DoNotContactRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get a list of DNC entries based on channel and lead_id.
     *
     * @param string $channel
     *
     * @return DoNotContact[]
     */
    public function getEntriesByLeadAndChannel(Lead $lead, $channel)
    {
        return $this->findBy(['channel' => $channel, 'lead' => $lead]);
    }

    /**
     * @param string|null                         $channel
     * @param array<int,int|string>|int|null      $ids
     * @param int|null                            $reason
     * @param array<int,int|string>|int|true|null $listId
     * @param bool                                $combined
     *
     * @return array|int
     */
    public function getCount($channel = null, $ids = null, $reason = null, $listId = null, ChartQuery $chartQuery = null, $combined = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(dnc.id) as dnc_count')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc');

        if ($ids) {
            if (!is_array($ids)) {
                $ids = [(int) $ids];
            }
            $q->where(
                $q->expr()->in('dnc.channel_id', $ids)
            );
        }

        if ($channel) {
            $q->andWhere('dnc.channel = :channel')
                ->setParameter('channel', $channel);
        }

        if ($reason) {
            $q->andWhere('dnc.reason = :reason')
                ->setParameter('reason', $reason);
        }

        if ($listId) {
            if (!$combined) {
                $q->innerJoin('dnc', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'cs', 'cs.lead_id = dnc.lead_id');

                if (true === $listId && !$combined) {
                    $q->addSelect('cs.leadlist_id')
                        ->groupBy('cs.leadlist_id');
                } elseif (is_array($listId)) {
                    $q->andWhere(
                        $q->expr()->in('cs.leadlist_id', ':segmentIds')
                    );

                    $q->setParameter('segmentIds', $listId, ArrayParameterType::INTEGER);

                    $q->addSelect('cs.leadlist_id')
                        ->groupBy('cs.leadlist_id');
                } else {
                    $q->andWhere('cs.leadlist_id = :list_id')
                        ->setParameter('list_id', $listId);
                }
            } else {
                $subQ = $this->getEntityManager()->getConnection()->createQueryBuilder();
                $subQ->select('distinct(list.lead_id)')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'list')
                    ->andWhere(
                        $q->expr()->in('list.leadlist_id', ':segmentIds')
                    );

                $q->setParameter('segmentIds', $listId, ArrayParameterType::INTEGER);

                $q->innerJoin('dnc', sprintf('(%s)', $subQ->getSQL()), 'cs', 'cs.lead_id = dnc.lead_id');
            }
        }

        if ($chartQuery) {
            $chartQuery->applyDateFilters($q, 'date_added', 'dnc');
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if ((true === $listId || is_array($listId)) && !$combined) {
            // Return list group of counts
            $byList = [];
            foreach ($results as $result) {
                $byList[$result['leadlist_id']] = $result['dnc_count'];
            }

            return $byList;
        }

        return (isset($results[0])) ? $results[0]['dnc_count'] : 0;
    }

    /**
     * @return array
     */
    public function getTimelineStats($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('dnc.id, dnc.channel, dnc.channel_id, dnc.date_added, dnc.reason, dnc.comments, dnc.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc');

        if ($leadId) {
            $query->where($query->expr()->eq('dnc.lead_id', (int) $leadId));
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->like('dnc.channel', $query->expr()->literal('%'.$options['search'].'%'))
            );
        }

        return $this->getTimelineResults($query, $options, 'dnc.channel', 'dnc.date_added', [], ['date_added'], null, 'dnc.id');
    }

    /**
     * @param string|null    $channel
     * @param string[]|int[] $contacts Array of contact IDs to filter by
     *
     * @return mixed[]
     */
    public function getChannelList($channel, array $contacts = null): array
    {
        // If no contacts are sent then stop querying for all of the DNC records as it leads to the out of memory error.
        if (is_array($contacts) && empty($contacts)) {
            return [];
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dnc.lead_id');

        if (null === $channel) {
            $q->select('dnc.channel, dnc.reason, l.id as lead_id');
        } else {
            $q->select('l.id, dnc.reason')
                ->where('dnc.channel = :channel')
                ->setParameter('channel', $channel);
        }

        if ($contacts) {
            $q->andWhere(
                $q->expr()->in('l.id', $contacts)
            );
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $dnc = [];
        foreach ($results as $r) {
            if (isset($r['lead_id'])) {
                if (!isset($dnc[$r['lead_id']])) {
                    $dnc[$r['lead_id']] = [];
                }

                $dnc[$r['lead_id']][$r['channel']] = $r['reason'];
            } else {
                $dnc[$r['id']] = $r['reason'];
            }
        }

        unset($results);

        return $dnc;
    }
}
