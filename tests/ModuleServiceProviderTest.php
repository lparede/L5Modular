<?php

namespace ArtemSchander\L5Modular\Tests\Commands;

// use Illuminate\Contracts\Foundation\Application;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Filesystem\Filesystem;
use ArrayAccess;
use Mockery;

use ArtemSchander\L5Modular\Tests\TestCase;
use ArtemSchander\L5Modular\ModuleServiceProvider;

/**
 * @author Artem Schander
 */
class ModuleServiceProviderTest extends TestCase
{
    private $serviceProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceProvider = new ModuleServiceProvider($this->app);
        $this->finder = $this->app['files'];
    }

    protected function tearDown(): void
    {
        if ($this->finder->isDirectory(base_path('app/Modules/FooBar'))) {
            $this->finder->deleteDirectory(base_path('app/Modules/FooBar'));
        }
        Mockery::close();
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('modules', [
            'generate' => [
                'controller' => true,
                'model' => true,
                'view' => true,
                'translation' => true,
                'routes' => true,
                'migration' => true,
                'seeder' => true,
                'factory' => true,
                'helpers' => true,
            ],
            'default' => [
                'routing' => [ 'web', 'api', 'simple' ],
                'structure' => [
                    'controllers' => 'Controllers',
                    'models' => 'Models',
                    'views' => 'resources/views',
                    'translations' => 'resources/lang',
                    'routes' => 'routes',
                    'migrations' => 'database/migrations',
                    'seeds' => 'database/seeds',
                    'factories' => 'database/factories',
                    'helpers' => '',
                ],
            ],
            'specific' => [
                // 'ExampleModule' => [
                //     'enabled' => false,
                //     'routing' => [ 'simple' ],
                //     'structure' => [
                //         'views' => 'Views',
                //         'translations' => 'Translations',
                //     ],
                // ],
            ],
        ]);
    }

    /** @test */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(ModuleServiceProvider::class, $this->serviceProvider);
    }

    /** @test */
    public function it_registeres_the_package()
    {
        $this->app->setBasePath(__DIR__ . '/..');

        $app = Mockery::mock(ArrayAccess::class);
        $serviceProvider = new ModuleServiceProvider($app);

        $app->shouldReceive('singleton')
            ->once()
            ->andReturnNull();

        $app->shouldReceive('configPath')
            ->once()
            ->with('modules.php')
            ->andReturn('config/modules.php');

        $configRepository = Mockery::mock(ConfigRepository::class);

        $configRepository->shouldReceive('set')
            ->once();
        $configRepository->shouldReceive('get')
            ->once()
            ->andReturn([]);

        $app->shouldReceive('offsetGet')
            ->zeroOrMoreTimes()
            ->with('config')
            ->andReturn($configRepository);

        $app->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(false);

        $result = $serviceProvider->register();
        $this->assertNull($result);
    }

    /** @test */
    public function it_bootes_a_module()
    {
        $basePath = realpath($this->app['path.base']);
        $this->artisan('make:module', ['name' => 'foo-bar']);

        $app = Mockery::mock(ArrayAccess::class);
        $fileSystem = Mockery::mock(FileSystem::class);
        $serviceProvider = new ModuleServiceProvider($app);

        $fileSystem->shouldReceive('directories')
            ->once()
            ->andReturn([ 'FooBar' ]);

        $app->shouldReceive('routesAreCached')
            ->once()
            ->andReturn(false);

        $fileSystem->shouldReceive('exists')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/routes/api.php')
            ->andReturn(true);

        $fileSystem->shouldReceive('exists')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/routes/web.php')
            ->andReturn(true);

        $fileSystem->shouldReceive('exists')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/routes/routes.php')
            ->andReturn(true);

        $fileSystem->shouldReceive('exists')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/helpers.php')
            ->andReturn(true);

        $fileSystem->shouldReceive('isDirectory')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/resources/views')
            ->andReturn(true);

        $app->shouldReceive('afterResolving')
            ->times(3);

        $app->shouldReceive('resolved')
            ->times(3);

        $fileSystem->shouldReceive('isDirectory')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/resources/lang')
            ->andReturn(true);

        $fileSystem->shouldReceive('isDirectory')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/database/migrations')
            ->andReturn(true);

        $fileSystem->shouldReceive('isDirectory')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/database/factories')
            ->andReturn(true);

        $app->shouldReceive('make')
            ->once()
            ->with(Factory::class)
            ->andReturn($app);

        $app->shouldReceive('load')
            ->once()
            ->with($basePath . '/app/Modules/FooBar/database/factories');

        $result = $serviceProvider->boot($fileSystem);
        $this->assertNull($result);
    }
}
