<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2015
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
     * Element maps
     *
     * @access  protected
     * @var     array
     */
    protected $maps = array();

    /**
     * Callback registry
     *
     * @access  protected
     * @var     array
     */
    protected $callbacks = array();

    /**
     * {@inheritdoc}
     */
    public function register(array $element, $key = 'default')
    {
        if (empty($element['map'])) {
            throw new EssenceException('['.$key.'] Element map empty/not set');
        }

        if (! is_array($element['map'])) {
            throw new EssenceException('['.$key.'] Element map must be an array');
        }

        if (! isset($element['callback'])) {
            throw new EssenceException('['.$key.'] Element callback must be set');
        }

        if (! $element['callback'] instanceof Closure) {
            throw new EssenceException('['.$key.'] Element callback must a Closure');
        }

        $this->maps[$key] = $element['map'];
        $this->callbacks[$key] = $element['callback'];
    }
}