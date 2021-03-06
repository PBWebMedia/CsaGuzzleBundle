<?php

namespace Csa\Bundle\GuzzleBundle\Factory;

use GuzzleHttp\Command\Guzzle\Description;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;

class DescriptionFactory
{
    private $resources = [];
    private $loader;
    private $debug;
    private $descriptions = [];
    private $cacheDir;

    public function __construct(LoaderInterface $loader, $cacheDir, $debug = false)
    {
        $this->loader = $loader;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function addResource($alias, $resource)
    {
        $this->resources[$alias] = $resource;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function getDescription($alias)
    {
        $this->loadDescriptions();

        if (!isset($this->descriptions[$alias])) {
            throw new \InvalidArgumentException('Unknown description alias');
        }

        return new Description($this->descriptions[$alias]);
    }

    /**
     * @inheritdoc
     */
    public function loadDescriptions()
    {
        if ($this->descriptions) {
            return;
        }

        $class = 'descriptionsMetadata';
        $cachePath = $this->cacheDir . '/csa/guzzle/' . $class . '.php';
        $resources = [];
        $descriptions = [];

        $cache = new ConfigCache($cachePath, $this->debug);

        if (!$cache->isFresh()) {
            foreach ($this->getResources() as $alias => $resource) {
                $resources[] = new FileResource($resource);
                $descriptions[$alias] = $this->loader->load($resource);
            }

            $descriptions = var_export($descriptions, true);

            $code = "<?php return $descriptions;";

            $cache->write($code, $resources);
        }

        $this->descriptions = require_once $cachePath;
    }
}
