<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 20:23
 */

return [
    //The services configuration is passed to a Zend\ServiceManager
    //Please see the official documentation of Zend\ServiceManager for more information
    //The service manager is used to resolve dependencies for the processing system
    //Here we configure how the ServiceManager can build the plugins required for the example
    'services' => [
        'invokables' => [
            'Prooph\ProcessingExample\Plugin\UserDataProvider' => 'Prooph\ProcessingExample\Plugin\UserDataProvider',
            'target-file-writer'                    => 'Prooph\ProcessingExample\Plugin\UserDataWriter',
            'in_memory_event_store_set_up'          => 'Prooph\ProcessingExample\Plugin\SetUpEventStore'
        ]
    ],
    //All configuration options for the processing system itself must be placed under the root config name "processing"
    'processing' => [
        //These are the plugins we want to use. Note: we only define the aliases used in the Zend\ServiceManager config above
        //Prooph\Processing\Environment uses the aliases to request the plugins from the ServiceManager
        'plugins' => [
            'Prooph\ProcessingExample\Plugin\UserDataProvider',
            'target-file-writer',
            'in_memory_event_store_set_up'
        ],
        //The processes section provides the workflow configuration. Each process should start with a message either
        //a "collect-data" command or a "new-data-collected" event- We will come to this later.
        //Our example workflow will start when the workflow processor receives a message with the name:
        //"processing-message-proophprocessingexampletypesourceuser-collect-data"
        'processes' => [
            "processing-message-proophprocessingexampletypesourceuser-collect-data" => [
                "process_type" => 'linear_messaging',
                "tasks" => [
                    [
                        "task_type"     => 'collect_data',
                        "source"        => 'Prooph\ProcessingExample\Plugin\UserDataProvider',
                        "processing_type"   => 'Prooph\ProcessingExample\Type\SourceUser'
                    ],
                    [
                        "task_type"     => 'process_data',
                        "target"        => "target-file-writer",
                        "allowed_types" => ['Prooph\ProcessingExample\Type\SourceUser']
                    ]
                ]
            ]
        ],
    ],
];