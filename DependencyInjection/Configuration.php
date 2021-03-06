<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SerializerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use JMS\Serializer\Exception\InvalidArgumentException;

class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * @param boolean $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();

        $root = $tb
            ->root('jms_serializer', 'array')
                ->children()
                    ->booleanNode('enable_short_alias')->defaultTrue()->end()
        ;

        $this->addHandlersSection($root);
        $this->addSerializersSection($root);
        $this->addMetadataSection($root);
        $this->addVisitorsSection($root);

        return $tb;
    }

    private function addHandlersSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('handlers')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('datetime')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('default_format')->defaultValue(\DateTime::ISO8601)->end()
                            ->scalarNode('default_timezone')->defaultValue(date_default_timezone_get())->end()
                            ->scalarNode('cdata')->defaultTrue()->end()
                        ->end()
                   ->end()
                ->end()
            ->end()
        ;
    }

    private function addSerializersSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('property_naming')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('id')->cannotBeEmpty()->end()
                    ->scalarNode('separator')->defaultValue('_')->end()
                    ->booleanNode('lower_case')->defaultTrue()->end()
                    ->booleanNode('enable_cache')->defaultTrue()->end()
                ->end()
            ->end()
        ;
    }

    private function addMetadataSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('metadata')
                ->addDefaultsIfNotSet()
                ->fixXmlConfig('directory', 'directories')
                ->children()
                    ->scalarNode('cache')->defaultValue('file')->end()
                    ->scalarNode('default_exclusion_policy')->defaultValue('NONE')->end()
                    ->booleanNode('debug')->defaultValue($this->debug)->end()
                    ->arrayNode('file_cache')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('dir')->defaultValue('%kernel.cache_dir%/jms_serializer')->end()
                        ->end()
                    ->end()
                    ->booleanNode('auto_detection')->defaultTrue()->end()
                    ->booleanNode('infer_types_from_doctrine_metadata')
                        ->info('Infers type information from Doctrine metadata if no explicit type has been defined for a property.')
                        ->defaultTrue()
                    ->end()
                    ->arrayNode('directories')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('path')->isRequired()->end()
                                ->scalarNode('namespace_prefix')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addVisitorsSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('visitors')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('json')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('options')
                                ->defaultValue(0)
                                ->beforeNormalization()
                                    ->ifArray()->then(function($v) {
                                        $options = 0;
                                        foreach ($v as $option) {
                                            if (is_numeric($option)) {
                                                $options |= (int) $option;
                                            } elseif (defined($option)) {
                                                $options |= constant($option);
                                            } else {
                                                throw new InvalidArgumentException('Expected either an integer representing one of the JSON_ constants, or a string of the constant itself.');
                                            }
                                        }

                                        return $options;
                                    })
                                ->end()
                                ->beforeNormalization()
                                    ->ifString()->then(function($v) {
                                        if (is_numeric($v)) {
                                            $value = (int) $v;
                                        } elseif (defined($v)) {
                                            $value = constant($v);
                                        } else {
                                            throw new InvalidArgumentException('Expected either an integer representing one of the JSON_ constants, or a string of the constant itself.');
                                        }

                                        return $value;
                                    })
                                ->end()
                                ->validate()
                                    ->always(function($v) {
                                        if (!is_int($v)) {
                                            throw new InvalidArgumentException('Expected either integer value or a array of the JSON_ constants.');
                                        }

                                        return $v;
                                    })
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('xml')
                        ->fixXmlConfig('whitelisted-doctype', 'doctype_whitelist')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('doctype_whitelist')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
