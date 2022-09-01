<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-youdu.
 *
 * @link     https://github.com/youduphp/hyperf-youdu
 * @document https://github.com/youduphp/hyperf-youdu/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace YouduPhp\HyperfYoudu;

use GuzzleHttp\ClientInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use YouduPhp\Youdu\Application as App;
use YouduPhp\Youdu\Config;

/**
 * @mixin App
 */
class Application
{
    protected string $name = 'default';

    #[Inject(value: 'youdu.guzzle.client', required: false)]
    protected ?ClientInterface $client = null;

    #[Inject(value: 'youdu.cache', required: false)]
    protected ?CacheInterface $cache = null;

    public function __construct(?string $name = null)
    {
        if (! is_null($name)) {
            $this->name = $name;
        }
    }

    public function __call($name, $arguments)
    {
        return $this->getApplication()->{$name}(...$arguments);
    }

    protected function getApplication(): App
    {
        static $app = null;

        if (is_null($app)) {
            /** @var ContainerInterface $container */
            $container = ApplicationContext::getContainer();
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            if (! $config->has("youdu.applications.{$this->name}")) {
                throw new \RuntimeException(sprintf('The application "%s" is not exists.', $this->name));
            }

            $appConfig = new Config([
                'api' => (string) $config->get('youdu.api', ''),
                'timeout' => (int) $config->get('youdu.timeout', 5),
                'buin' => (int) $config->get('youdu.buin', 0),
                'app_id' => (string) $config->get("youdu.applications.{$this->name}.app_id", ''),
                'aes_key' => (string) $config->get("youdu.applications.{$this->name}.aes_key", ''),
                'tmp_path' => is_writable($config->get('youdu.tmp_path')) ? $config->get('youdu.tmp_path') : '/tmp',
            ]);

            $app = new App($appConfig, $this->client, $this->cache);
        }

        return $app;
    }
}
