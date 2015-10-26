<?php

/*
 * This file is part of Transfer.
 *
 * For the full copyright and license information, please view the LICENSE file located
 * in the root directory.
 */

namespace Transfer\EzPlatform\Adapter;

use eZ\Publish\API\Repository\Repository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Transfer\Adapter\TargetAdapterInterface;
use Transfer\Adapter\Transaction\Request;
use Transfer\Adapter\Transaction\Response;
use Transfer\Data\TreeObject;
use Transfer\Data\ValueObject;
use Transfer\EzPlatform\Repository\ContentTreeService;
use Transfer\EzPlatform\Repository\ObjectService;

/**
 * eZ Platform adapter.
 */
class EzPlatformAdapter implements TargetAdapterInterface, LoggerAwareInterface
{
    /**
     * @var ContentTreeService
     */
    protected $treeService;

    /**
     * @var ObjectService
     */
    protected $objectService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);

        $this->objectService = new ObjectService($this->options['repository']);
        $this->treeService = new ContentTreeService($this->options['repository'], $this->objectService);
    }

    /**
     * Option configuration.
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'repository_current_user' => 'admin',
        ));

        $resolver->setRequired(array('repository'));
        $resolver->setAllowedTypes(array(
            'repository' => 'eZ\Publish\API\Repository\Repository',
            'repository_current_user' => array('string', 'null'),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Request $request)
    {
        /** @var Repository $repository */
        $repository = $this->options['repository'];
        $repository->beginTransaction();

        $this->treeService->setLogger($this->logger);
        $this->objectService->setLogger($this->logger);

        $response = new Response();

        $versionInfo = array();
        foreach ($request as $object) {
            if ($object instanceof TreeObject) {
                $service = $this->treeService;
            } else {
                $service = $this->objectService;
            }

            if ($this->options['repository_current_user']) {
                $service->setCurrentUser($this->options['repository_current_user']);
            }

            try {
                $object = $service->create($object);
                $versionInfo[] = $object;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $versionInfoObjects = array();
        foreach ($versionInfo as $versionInfoElement) {
            $versionInfoObjects[] = new ValueObject($versionInfoElement);
        }

        $response->setData(new \ArrayIterator($versionInfoObjects));

        $repository->commit();

        return $response;
    }
}