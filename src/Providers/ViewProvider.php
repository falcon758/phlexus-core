<?php

/**
 * This file is part of the Phlexus CMS.
 *
 * (c) Phlexus CMS <cms@phlexus.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phlexus\Providers;

use Phalcon\Mvc\View;

class ViewProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName = 'view';

    /**
     * Register application service.
     *
     * @param array $parameters
     */
    public function register(array $parameters = []): void
    {
        $di = $this->getDI();

        $di->setShared($this->providerName, function () use ($di, $parameters) {
            $view = new View();
            
            if (!empty($parameters['engines'])) {
                foreach ($parameters['engines'] as $extension => $config) {
                    if (!is_array($config) || !isset($config['class'])) {
                        continue;
                    }

                    $view->registerEngines(
                        [
                            $extension => function ($view) use ($di, $config) {
                                $engine = new $config['class']($view, $di);
                               
                                if (isset($config['options'])) {
                                    $engine->setOptions($config['options']);
                                }

                                $compiler = $engine->getCompiler();
                                $compiler->addFunction('assetsPath', '\Phlexus\Helpers::phlexusAssetsPath');
                
                                return $engine;
                            }
                        ]
                    );
                }    
            }

            return $view;
        });
    }
}
