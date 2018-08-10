<?php
return [
    'settings' => [
        
        // monolog settings
        'logger' => [
            'name' => 'app',
			'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/logs/app.log',
        ],
		
		// Doctrine Settings
        'doctrine' => [
            'meta' => [
                'entity_path' => [
                    __DIR__ . '/src/com/sprint/sms/api/domain'
                ],
                'auto_generate_proxies' => true,
                'proxy_dir' =>  __DIR__ . '/cache/proxies',
                'cache' => null,
            ],
			
			// MYSQL
			'connection' => [
                'driver'   => 'pdo_mysql',
                'host'     => 'localhost',
				'port'     => '3306',
                'dbname'   => 'sprint_sms',
                'user'     => 'sprint',
                'password' => 'sprint1234',
            ]
			
			// SQL SERVER
			/*
			'connection' => [
                'driver'   => 'pdo_sqlsrv',
                'host'     => 'localhost',
				'port'     => '1433',
                'dbname'   => 'sprint_sms',
                'user'     => 'sprint',
                'password' => 'sprint1234',
            ]
			*/
			
			// ORACLE
			/*
			'connection' => [
                'driver'   => 'oci8',
                'host'     => 'localhost',
				'port'     => '1521',
                'dbname'   => 'xe',
                'user'     => 'sprint',
                'password' => 'sprint1234',
            ]
			*/
			
			// POSTGRE SQL
			/*
			'connection' => [
                'driver'   => 'pdo_pgsql',
                'host'     => 'localhost',
				'port'     => '5432',
                'dbname'   => 'sprint_sms',
                'user'     => 'sprint',
                'password' => 'sprint1234',
            ]
			*/
        ],
		
		// Cache Settings
		// Cara kerjanya akan dicek ke memory terlebih dahulu, jika tidak tersedia maka akan diambil ke data source (bisa DB, File, dll) 
		// selanjutnya object akan disimpan ke memory. Tipe data yang disimpan berupa array().
		// - limit    : maksimum jumlah object yang disimpan di memory
		// - age      : maksimum umur (diisi 0 = UNLIMITED) object di memory (tapi tidak akan dibuang dari memory sampai ada request), dalam satuan milidetik
		// - allowNull: object null akan disimpan di memory (untuk menghindari pemanggilan yg selalu ke database jika object memang tidak tersedia)
        'cache' => [
			'provider' => "\App\com\sprint\sms\api\cache\ApcuCacheProvider",
			'group' => [
				App\com\sprint\sms\api\util\AppConstant::CACHE_ACCESS_ID => [
					'limit' => 100,
					'age' => 0,
					'allowNull' => 1
				],
				App\com\sprint\sms\api\util\AppConstant::CACHE_ACCESS_ROLE => [
					'limit' => 100,
					'age' => 0,
					'allowNull' => 1
				],
				App\com\sprint\sms\api\util\AppConstant::CACHE_API_LIST => [
					'limit' => 10,
					'age' => 0,
					'allowNull' => 1
				],
				
				
				
				'TEST' => [
					'limit' => 10,
					'age' => 0,
					'allowNull' => 1
				]
			]			
		],
		
		// API Settings
		// Daftar API yang bisa diakses
		// Untuk yang 'private', berarti setiap request harus mengirim Access Key
		'api' => [
			'file' => getenv('APP_DIR') . '/api.list',
		]
    ],
];
