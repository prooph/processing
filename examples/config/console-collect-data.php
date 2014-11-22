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
    'services' => [
        'invokables' => [
            'GingerExample\Plugin\UserDataProvider' => 'GingerExample\Plugin\UserDataProvider',
            'target-file-writer' => 'GingerExample\Plugin\UserDataWriter',
            'in_memory_event_store_set_up' => 'GingerExample\Plugin\SetUpEventStore'
        ]
    ],
    'ginger' => [
        'plugins' => [
            'GingerExample\Plugin\UserDataProvider',
            'target-file-writer',
            'in_memory_event_store_set_up'
        ],
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
        'buses' => [
            'read_user_data_command_bus' => [
                'type' => 'command_bus',
                'targets' => [
                    'GingerExample\Plugin\UserDataProvider',
                ],
                'message_handler' => 'GingerExample\Plugin\UserDataProvider'
            ],
            'write_user_data_command_bus' => [
                'type' => 'command_bus',
                'targets' => [
                    'target-file-writer',
                ],
                'message_handler' => 'target-file-writer'
            ]
        ]
    ]
];