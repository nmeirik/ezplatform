<?php

/*
 * This file is part of Transfer.
 *
 * For the full copyright and license information, please view the LICENSE file located
 * in the root directory.
 */

namespace Transfer\EzPlatform\Tests\Repository\Manager;

use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use Psr\Log\NullLogger;
use Transfer\Data\TreeObject;
use Transfer\Data\ValueObject;
use Transfer\EzPlatform\Data\ContentObject;
use Transfer\EzPlatform\Repository\ContentTreeService;
use Transfer\EzPlatform\Repository\ObjectService;
use Transfer\EzPlatform\Tests\EzPlatformTestCase;

class ContentTreeServiceTest extends EzPlatformTestCase
{
    /** @var ContentTreeService $service */
    private $service;

    public function setUp()
    {
        $objectService = new ObjectService(static::$repository);
        $objectService->setLogger(new NullLogger());
        $this->service = new ContentTreeService(static::$repository, $objectService);
        $this->service->setLogger(new NullLogger());
    }

    public function testCreate()
    {
        $rootContentObject = new ContentObject(array(
            'title' => 'Test',
            'name' => 'Test',
        ));
        $rootContentObject->setContentType('_test_article');
        $rootContentObject->setLanguage('eng-GB');
        $rootContentObject->setRemoteId('content_tree_service_test_1');
        $rootContentObject->setPriority(1);

        $secondaryContentObject = new ContentObject(array(
            'title' => 'Test 2',
            'name' => 'Test 2',
        ));
        $secondaryContentObject->setContentType('_test_article');
        $secondaryContentObject->setLanguage('eng-GB');
        $secondaryContentObject->setRemoteId('content_tree_service_test_2');
        $secondaryContentObject->setPriority(1);
        $secondaryContentObject->setHidden(true);
        $secondaryContentObject->setMainObject(false);

        $treeObject = new TreeObject($rootContentObject);
        $treeObject->setProperty('location_id', 2);

        $treeObject->addNode($secondaryContentObject);
        $treeObject->addNode(new TreeObject($secondaryContentObject));

        $this->service->create($treeObject);
    }

    public function testCreateWithInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->service->create(new ValueObject(null));
    }

    public function testPublishTrashed()
    {
        $tree = $this->getTreeObject(2,
            $this->getContentObject(
                array(
                    'name' => 'Test folder',
                ),
                'folder',
                'content_trashed_folder_0'
            )
        );

        $this->service->create($tree);
        $contentInfo = static::$repository->getContentService()->loadContentInfoByRemoteId('content_trashed_folder_0');
        $this->assertInstanceOf(ContentInfo::class, $contentInfo);
        $location = static::$repository->getLocationService()->loadLocation($contentInfo->mainLocationId);
        static::$repository->getTrashService()->trash($location);
        $this->service->create($tree);
    }
}