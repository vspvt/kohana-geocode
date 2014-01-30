<?php

use Geocoder\Geocoder;
use Geocoder\HttpAdapter\HttpAdapterInterface;
use Geocoder\Provider\ProviderInterface;

class Kohana_Geocode
{
	/** @var HttpAdapterInterface */
	protected $_adapter;
	/** @var Geocoder */
	protected $_geocoder;
	/** @var array */
	protected $_config;
	/** @var ProviderInterface[] */
	protected $_providers;

	/**
	 * @param array|null  $providersConfig
	 * @param int|null    $providersMaxResults
	 * @param string|null $adapter
	 */
	function __construct(array $providersConfig = NULL, $providersMaxResults = NULL, $adapter = NULL)
	{
		// Initializing adapter
		NULL !== $adapter or $adapter = $this->config('adapter');
		$adapter = "\\Geocoder\\HttpAdapter\\{$adapter}HttpAdapter";
		$this->_adapter = new $adapter();

		// Initializing Providers
		$this->_providers = [];
		NULL !== $providersConfig or $providersConfig = $this->config('providers');
		foreach ($providersConfig as $providerName => $providerConfig) {
			$instance = new ReflectionClass("\\Geocoder\\Provider\\{$providerName}Provider");
			/** @var ProviderInterface $providerObject */
			$providerObject = $instance->newInstanceArgs(Arr::merge(['adapter' => $this->_adapter], $providerConfig));
			NULL === $providersMaxResults or $providerObject->setMaxResults($providersMaxResults);

			$this->_providers[$providerObject->getName()] = $providerObject;
		}
	}

	/**
	 * @param array $providersConfig
	 * @param null  $providersMaxResults
	 * @param null  $adapter
	 *
	 * @return Geocode
	 */
	static function factory(array $providersConfig = NULL, $providersMaxResults = NULL, $adapter = NULL)
	{
		return new Geocode($providersConfig, $providersMaxResults, $adapter);
	}

	/**
	 * @param null|mixed  $path
	 * @param null|mixed  $default
	 * @param null|string $delimeter
	 *
	 * @return array|mixed
	 */
	public function config($path = NULL, $default = NULL, $delimeter = NULL)
	{
		if (NULL === $this->_config) {
			$this->_config = Kohana::$config->load('geocode')->as_array();
		}

		return NULL === $path
			? $this->_config
			: Arr::path($this->_config, $path, $default, $delimeter);
	}

	/**
	 * @param string      $value
	 * @param null        $limit
	 * @param null|string $using
	 * @param bool|null   $useChain
	 *
	 * @return \Geocoder\Result\ResultInterface
	 */
	public function geocode($value, $limit = NULL, $using = NULL, $useChain = NULL)
	{
		$instance = $this->instance($useChain);

		NULL === $limit or $instance->limit($limit);
		NULL === $using or $instance->using($using);

		return $instance->geocode($value);
	}

	/**
	 * @param null $useChain
	 *
	 * @return Geocoder
	 */
	protected function instance($useChain = NULL)
	{
		if (!isset($this->_geocoder) or NULL !== $useChain) {
			// Initializing Geocoder
			$this->_geocoder = new \Geocoder\Geocoder();

			if ($useChain && count($this->_providers) > 1) {
				$this->_geocoder->registerProvider(
					new \Geocoder\Provider\ChainProvider($this->_providers)
				);
			} else {
				$this->_geocoder->registerProviders($this->_providers);
			}
		}

		return $this->_geocoder;
	}

	/**
	 * @param           $latitude
	 * @param           $longitude
	 * @param null      $limit
	 * @param null      $using
	 * @param bool|null $useChain
	 *
	 * @return \Geocoder\Result\ResultInterface
	 */
	public function reverse($latitude, $longitude, $limit = NULL, $using = NULL, $useChain = NULL)
	{
		$instance = $this->instance($useChain);

		NULL === $limit or $instance->limit($limit);
		NULL === $using or $instance->using($using);

		return $instance->reverse($latitude, $longitude);
	}

}
