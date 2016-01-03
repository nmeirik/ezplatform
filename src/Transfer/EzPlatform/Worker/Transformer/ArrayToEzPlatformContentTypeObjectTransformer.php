<?php

/*
 * This file is part of Transfer.
 *
 * For the full copyright and license information, please view the LICENSE file located
 * in the root directory.
 */

namespace Transfer\EzPlatform\Worker\Transformer;

use Symfony\Component\Config\Definition\Processor as ConfigProcessor;
use Transfer\EzPlatform\Data\ContentTypeObject;
use Transfer\EzPlatform\Data\Definition\ContentTypeConfiguration;
use Transfer\EzPlatform\Data\FieldDefinitionObject;
use Transfer\EzPlatform\Exception\InvalidDataStructureException;
use Transfer\Worker\WorkerInterface;

/**
 * Transforms array to Transfer eZ Platform Content Type object.
 *
 * @author Harald Tollefsen <harald@netmaking.no>
 */
class ArrayToEzPlatformContentTypeObjectTransformer implements WorkerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($array)
    {
        if(!is_array($array) || empty($array)) {
            return;
        }

        $array = array_key_exists('contenttypes', $array) ? $array['contenttypes'] : $array;

        $cts = [];
        foreach($array as $identifier => $contenttype) {
            $processor = new ConfigProcessor();
            $processedConfiguration = $processor->processConfiguration(
                new ContentTypeConfiguration(),
                array('contenttypes' => $contenttype)
            );
            $cts[] = new ContentTypeObject($identifier, $processedConfiguration);

        }
        return $cts;
    }
}
