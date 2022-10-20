<?php
namespace Contract;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig(): array {
        return [
            \Zend\Loader\StandardAutoloader::class => [
                'namespaces' => [ __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__) ]
            ]
        ];
    }

    public function getConfig(): array
    {
        $config = [];

        $configFiles = [
            __DIR__ . '/config/module.config.php',
            __DIR__ . '/config/module.config.routes.act.php',
            __DIR__ . '/config/module.config.routes.contract.php',
            __DIR__ . '/config/module.config.routes.pricelist.php',
            __DIR__ . '/config/module.config.routes.specification.php',
            __DIR__ . '/config/module.config.routes.schedule.php'
        ];

        // Merge all module config options
        foreach($configFiles as $configFile) {
            $config = \Zend\Stdlib\ArrayUtils::merge($config, include $configFile);
        }

        return $config;
    }

    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $serviceManager = $e->getApplication()->getServiceManager();

        /**
         * Настраиваем сессии
         */
        $cfg = $serviceManager->get('config');
        $sessionCfg = new \Zend\Session\Config\SessionConfig();
        $sessionCfg->setOptions($cfg['session']);
        $sessionManager = new \Zend\Session\SessionManager($sessionCfg);
        $sessionManager->start();
        \Zend\Session\Container::setDefaultManager($sessionManager);
    }

    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                'fzcClient' => function($sm){
                    $cfg = $sm->get('config');
                    return (new \SP\Soap\FZCoreClient($cfg['service']['url']));
                },

                'reCaptchaClient' => function($sm){
                    $cfg = $sm->get('config');
                    return (new \SP\Form\ReCaptchaClient($cfg['captcha']));
                },

                'cache' => function($sm) {
                    return \Zend\Cache\StorageFactory::factory(
                        [
                            'adapter' => [
                                'name' => 'filesystem',
                                'options' => [
                                    'dirLevel' => 2,
                                    'cacheDir' => 'data/cache',
                                    'dirPermission' => 0755,
                                    'filePermission' => 0666,
                                    'ttl' => 3600 //1 hour
                                    //'namespaceSeparator' => '-db-'
                                ],
                            ],
                            'plugins' => [
                                'exception_handler' => ['throw_exceptions' => false],
                                'serializer'
                            ]
                        ]
                    );
                }
            ], //factories

            'invokables' => [
                'spc_contractWrapper'      => Model\Spc\ContractWrapper::class,
                'spc_actWrapper'           => Model\Spc\ActWrapper::class,
                'spc_pricelistWrapper'     => Model\Spc\PricelistWrapper::class,
                'spc_specificationWrapper' => Model\Spc\SpecificationWrapper::class,
                'spc_scheduleWrapper'      => Model\Spc\ScheduleWrapper::class,
                'spn_contractWrapper'      => Model\Spn\ContractWrapper::class
			],

        ];
    }

}