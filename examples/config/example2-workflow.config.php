<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 20:23
 */

return [
    //The services configuration is passed to a Zend\ServiceManager
    //Please see the official documentation of Zend\ServiceManager for more information
    //The service manager is used to resolve dependencies for the Ginger system
    //Here we configure how the ServiceManager can build the plugins required for the example
    'services' => [
        'invokables' => [
            'GingerExample\Plugin\UserDataProvider' => 'GingerExample\Plugin\UserDataProvider',
            'target-file-writer'                    => 'GingerExample\Plugin\UserDataWriter',
            'in_memory_event_store_set_up'          => 'GingerExample\Plugin\SetUpEventStore'
        ]
    ],
    //All configuration options for the Ginger system itself must be placed under the root config name "ginger"
    'ginger' => [
        //These are the plugins we want to use. Note: we only define the aliases used in the Zend\ServiceManager config above
        //Ginger\Environment uses the aliases to request the plugins from the ServiceManager
        'plugins' => [
            'GingerExample\Plugin\UserDataProvider',
            'target-file-writer',
            'in_memory_event_store_set_up'
        ],
        //The processes section provides the workflow configuration. Each process should start with a message either
        //a "collect-data" command or a "new-data-collected" event- We will come to this later.
        //Our example workflow will start when the workflow processor receives a message with the name:
        //"ginger-message-gingerexampletypesourceuser-collect-data"
        'processes' => [
            "ginger-message-gingerexampletypesourceuser-collect-data" => [
                "process_type" => 'linear_messaging',
                "tasks" => [
                    [
                        "task_type"     => 'collect_data',
                        "source"        => 'GingerExample\Plugin\UserDataProvider',
                        "ginger_type"   => 'GingerExample\Type\SourceUser'
                    ],
                    [
                        "task_type"     => 'process_data',
                        "target"        => "target-file-writer",
                        "allowed_types" => ['GingerExample\Type\SourceUser']
                    ]
                ]
            ]
        ],
    ],
];