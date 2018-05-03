<?php

/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace SmartyCache\Smarty;

use Doctrine\Common\Cache\FilesystemCache;
use Thelia\Core\HttpFoundation\Request;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\SmartyPluginDescriptor;

/**
 * Class Cache
 * @package SmartyCache\Smarty
 * @author Julien Chanséaume <julien@thelia.net>
 */
class Cache extends AbstractSmartyPlugin
{

    const REF_DELIMITER = ",";

    const CACHE_DIR = 'smarty-fragment';

    /** @var Request $request */
    protected $request;

    protected $cacheDir;

    protected $environment;

    /** @var FilesystemCache */
    protected $cacheDriver;

    protected $defaultParams = [];

    /** @var string $cacheKey */
    protected $cacheKey = null;

    public function __construct(
        Request $request,
        $cacheDir,
        $environment
    ) {
        $this->request = $request;
        $this->cacheDir = $cacheDir;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function getPluginDescriptors()
    {
        return [
            new SmartyPluginDescriptor('block', 'tcache', $this, 'cacheBlock'),
        ];
    }

    /**
     * Caches the result for a smarty block.
     *
     * @param $params
     * @param $content
     * @param $template
     * @param $repeat
     * @return false|string|null
     */
    public function cacheBlock($params, $content, $template, &$repeat)
    {
        $key = $this->getParam($params, 'key');
        if (null == $key) {
            throw new \InvalidArgumentException(
                "Missing 'key' parameter in cache arguments"
            );
        }

        $ttl = $this->getParam($params, 'ttl');
        if (null == $ttl) {
            throw new \InvalidArgumentException(
                "Missing 'ttl' parameter in cache arguments"
            );
        }

        if ('dev' === $this->environment) {
            if (null !== $content) {
                $repeat = false;
                return $content;
            }
            return;
        }

        if (null === $this->cacheDriver) {
            $this->cacheDriver = new FilesystemCache($this->getCacheDir());

            $session = $this->request->getSession();

            if (null !== $session) {
                $customer = $session->getCustomerUser();
                $admin = $session->getAdminUser();

                $this->defaultParams = [
                    'lang' => $session->getLang(true)->getId(),
                    'currency' => $session->getCurrency(true)->getId(),
                    'customer' => null !== $customer ? $customer->getId() : '0',
                    'admin' => null !== $admin ? $admin->getId() : '0',
                    'role' => sprintf('%s%s', null !== $admin ? 'ADMIN' : '', null !== $customer ? 'CUSTOMER' : '')
                ];
            }
        }

        $cacheKey = $this->generateKey($params);

        if (null === $content) {
            $cacheContent = $this->cacheDriver->fetch($cacheKey);

            // first call - return cache if it exists
            if ($cacheContent) {
                $repeat = false;
                return $cacheContent;
            }
        } else {
            $this->cacheDriver->save($cacheKey, $content, $ttl);

            $repeat = false;
            return $content;
        }
    }

    /**
     * get the cache directory for sitemap
     *
     * @return mixed|string
     */
    protected function getCacheDir()
    {
        $cacheDir = $this->cacheDir;
        $cacheDir = rtrim($cacheDir, '/');
        $cacheDir .= '/' . self::CACHE_DIR . '/';

        return $cacheDir;
    }

    protected function generateKey($params)
    {
        $keyParams = array_merge(
            $params,
            [
                'ttl' => null,
                'lang' => $this->getBoolParam($params, 'lang', true) ? $this->defaultParams['lang'] : null,
                'currency' => $this->getBoolParam($params, 'currency', true) ? $this->defaultParams['currency'] : null,
                'customer' => $this->getBoolParam($params, 'customer', false) ? $this->defaultParams['customer'] : null,
                'admin' => $this->getBoolParam($params, 'admin', false) ? $this->defaultParams['admin'] : null,
                'role' => $this->getBoolParam($params, 'role', false) ? $this->defaultParams['role'] : null
            ]
        );

        $key = [];

        ksort($keyParams);

        foreach ($keyParams as $k => $v) {
            if (null !== $v) {
                $key[] = $k . '=' . $v;
            }
        }

        return md5(implode('.', $key));
    }

    protected function getBoolParam($params, $key, $default)
    {
        $return = $this->getParam($params, $key, $default);
        $return = filter_var($return, FILTER_VALIDATE_BOOLEAN);

        if (null === $return) {
            $return = $default;
        }

        return $return;
    }
}
