<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadListData;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Exception\TableNotFoundException;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData;
use Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use PHPUnit\Framework\Assert;

/**
 * These tests cover same tests like \Mautic\LeadBundle\Tests\Model\ListModelFunctionalTest.
 */
class ContactSegmentServiceFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var ReferenceRepository
     */
    private $fixtures;

    /**
     * @var ContactSegmentService
     */
    private $contactSegmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = $this->loadFixtures(
            [
                LoadCompanyData::class,
                LoadLeadListData::class,
                LoadLeadData::class,
                LeadFieldData::class,
                LoadPageHitData::class,
                LoadSegmentsData::class,
                LoadPageCategoryData::class,
                LoadRoleData::class,
                LoadUserData::class,
                LoadDncData::class,
                LoadClickData::class,
                LoadTagData::class,
            ],
            false
        )->getReferenceRepository();

        $this->contactSegmentService = static::getContainer()->get('mautic.lead.model.lead_segment_service');
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(
            [
                'leads',
                'lead_lists',
            ]
        );
    }

    public function testSegmentCountIsCorrect(): void
    {
        // purposively not using dataProvider here to avoid loading fixtures with each segment
        foreach ($this->provideSegments() as $segmentAlias => $expectedCount) {
            $reference       = $this->getReference($segmentAlias);
            $segmentContacts = $this->contactSegmentService->getTotalLeadListLeadsCount($reference);
            Assert::assertEquals(
                $expectedCount,
                $segmentContacts[$reference->getId()]['count'],
                sprintf('There should be %d in segment %s.', $expectedCount, $segmentAlias)
            );
        }
    }

    /**
     * @return array<string,int>
     */
    private function provideSegments(): array
    {
        return [
            'segment-test-1'                                                     => 1,
            'segment-test-2'                                                     => 4,
            'segment-test-3'                                                     => 24,
            'segment-test-4'                                                     => 1,
            'segment-test-5'                                                     => 53,
            'like-percent-end'                                                   => 32,
            'segment-test-without-filters'                                       => 0,
            'segment-test-exclude-segment-with-filters'                          => 7,
            'segment-test-include-segment-without-filters'                       => 0,
            'segment-test-exclude-segment-without-filters'                       => 11,
            'segment-test-include-segment-mixed-filters'                         => 24,
            'segment-test-exclude-segment-mixed-filters'                         => 30,
            'segment-test-mixed-include-exclude-filters'                         => 8,
            'segment-test-manual-membership'                                     => 12,
            'segment-test-include-segment-manual-members'                        => 12,
            'segment-test-exclude-segment-manual-members'                        => 25,
            'segment-test-exclude-segment-without-other-filters'                 => 42,
            'segment-test-include-segment-with-unrelated-segment-manual-removal' => 11,
            'segment-membership-regexp'                                          => 11,
            'segment-company-only-fields'                                        => 6,
            'segment-including-segment-with-company-only-fields'                 => 14,
            'name-is-not-equal-not-null-test'                                    => 54,
            'manually-unsubscribed-sms-test'                                     => 1,
            'clicked-link-in-any-email'                                          => 2,
            'did-not-click-link-in-any-email'                                    => 52,
            'clicked-link-in-any-email-on-specific-date'                         => 2,
            'clicked-link-in-any-sms'                                            => 3,
            'clicked-link-in-any-sms-on-specific-date'                           => 2,
            'tags-empty'                                                         => 52,
            'tags-not-empty'                                                     => 2,
            'segment-having-company'                                             => 50,
            'segment-not-having-company'                                         => 4,
            'has-email-and-visited-url'                                          => 4,
        ];
    }

    public function testSegmentRebuildCommand(): void
    {
        // exclude the segment
        $segmentTest3Ref = $this->getReference('segment-test-3');
        $lastRebuiltDate = $segmentTest3Ref->getLastBuiltDate();
        self::assertNull($lastRebuiltDate);

        $this->testSymfonyCommand(
            UpdateLeadListsCommand::NAME,
            [
                '--exclude' => [$segmentTest3Ref->getId()],
                '--env'     => 'test',
            ]
        );

        self::assertSame($lastRebuiltDate, $segmentTest3Ref->getLastBuiltDate(), 'Make sure the segment was not executed, if excluded.');

        $this->testSymfonyCommand(
            'mautic:segments:update',
            [
                '-i'    => $segmentTest3Ref->getId(),
                '--env' => 'test',
            ]
        );

        $segmentContacts = $this->contactSegmentService->getTotalLeadListLeadsCount($segmentTest3Ref);

        $this->assertEquals(
            24,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be 24 contacts in the segment-test-3 segment after rebuilding from the command line.'
        );

        self::assertNotSame($lastRebuiltDate, $segmentTest3Ref->getLastBuiltDate(), 'Make sure the segment was executed, if not excluded.');

        // Remove the title from all contacts, rebuild the list, and check that list is updated
        $this->em->getConnection()->executeQuery(sprintf('UPDATE %sleads SET title = NULL;', MAUTIC_TABLE_PREFIX));

        $this->testSymfonyCommand(
            'mautic:segments:update',
            [
                '-i'    => $segmentTest3Ref->getId(),
                '--env' => 'test',
            ]
        );

        $segmentContacts = $this->contactSegmentService->getTotalLeadListLeadsCount($segmentTest3Ref);

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be no contacts in the segment-test-3 segment after removing contact titles and rebuilding from the command line.'
        );

        $segmentTest40Ref      = $this->getReference('segment-test-include-segment-with-or');
        $this->testSymfonyCommand('mautic:segments:update', [
            '-i'    => $segmentTest40Ref->getId(),
            '--env' => 'test',
        ]);

        $segmentContacts = $this->contactSegmentService->getTotalLeadListLeadsCount($segmentTest40Ref);

        $this->assertEquals(
            11,
            $segmentContacts[$segmentTest40Ref->getId()]['count'],
            'There should be 11 contacts in the segment-test-include-segment-with-or segment after rebuilding from the command line.'
        );

        $segmentTest51Ref      = $this->getReference('has-email-and-visited-url');
        $this->testSymfonyCommand('mautic:segments:update', [
            '-i'    => $segmentTest51Ref->getId(),
            '--env' => 'test',
        ]);

        $segmentContacts = $this->contactSegmentService->getTotalLeadListLeadsCount($segmentTest51Ref);

        $this->assertEquals(
            4,
            $segmentContacts[$segmentTest51Ref->getId()]['count'],
            'There should be 4 contacts in the has-email-and-visited-url segment after rebuilding from the command line.'
        );

        // Change the url from page_hits with the right tracking_id, rebuild the list, and check that list is updated
        $this->em->getConnection()->executeQuery(sprintf(
            "UPDATE %spage_hits SET url = '%s' WHERE tracking_id = '%s';",
            MAUTIC_TABLE_PREFIX,
            'https://test/regex-segment-other.com',
            'abcdr')
        );

        $this->testSymfonyCommand(
            'mautic:segments:update',
            [
                '-i'    => $segmentTest51Ref->getId(),
                '--env' => 'test',
            ]
        );

        $segmentContacts = $this->contactSegmentService->getTotalLeadListLeadsCount($segmentTest51Ref);

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTest51Ref->getId()]['count'],
            'There should be no contacts in the has-email-and-visited-url segment after removing contact titles and rebuilding from the command line.'
        );
    }

    private function getReference(string $name): LeadList
    {
        /** @var LeadList $reference */
        $reference = $this->fixtures->getReference($name);

        return $reference;
    }

    public function testSegmentRebuildCommandFailsOnMissingTable(): void
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = $this->getContainer()->get('mautic.lead.model.lead_segment_service');
        $reference             = $this->fixtures->getReference('table-name-missing-in-filter');

        $this->expectException(TableNotFoundException::class);
        $contactSegmentService->getTotalLeadListLeadsCount($reference);
    }
}
