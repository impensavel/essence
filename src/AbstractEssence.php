<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2016
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace Impensavel\Essence;

use Closure;

abstract class AbstractEssence implements EssenceInterface
{
    /**
     * Element Property Map registry
     *
     * @var  array
     */
    protected $maps = array();

    /**
     * Data Handler registry
     *
     * @var  array
     */
    protected $handlers = array();

    /**
     * {@inheritdoc}
     */
    public function register(array $element, $key = 'default')
    {
        if (! is_array($element['map'])) {
            throw new EssenceException('['.$key.'] Element property map must be an array');
        }

        if (! isset($element['handler'])) {
            throw new EssenceException('['.$key.'] Element data handler is not set');
        }

        if (! $element['handler'] instanceof Closure) {
            throw new EssenceException('['.$key.'] Element data handler must be a Closure');
        }

        $this->maps[$key] = $element['map'];
        $this->handlers[$key] = $element['handler'];
    }
}
